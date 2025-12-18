<?php
// api/products.php
// 상품 CRUD API

// 세션 시작 (인증 체크용)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// CORS 설정 - 같은 도메인만 허용 (프로덕션용)
$allowed_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (strpos($allowed_origin, 'localhost') !== false || $allowed_origin === '') {
    header('Access-Control-Allow-Origin: ' . ($allowed_origin ?: '*'));
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

// 테이블 자동 생성
ensure_tables_exist();
require_once __DIR__ . '/../admin/guard.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$pathParts = $path ? explode('/', $path) : [];

// ID 추출 (있으면)
$id = isset($pathParts[0]) && is_numeric($pathParts[0]) ? (int)$pathParts[0] : null;


try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // 단일 상품 조회
                $product = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
                if ($product) {
                    echo json_encode($product);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                }
            } else {
                // 전체 상품 목록
                $products = db()->fetchAll("SELECT * FROM products ORDER BY id DESC");
                echo json_encode($products);
            }
            break;

        case 'POST':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            // 상품 등록
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name']) || empty($data['price'])) {
                http_response_code(400);
                echo json_encode(['error' => '상품명과 가격은 필수입니다.']);
                exit;
            }

            // category/type 값 검증 및 정리
            $category = $data['category'] ?? $data['type'] ?? '향수';
            $validCategories = ['향수', '바디미스트', '헤어미스트', '디퓨저', '섬유유연제', '룸스프레이'];
            // category가 너무 길거나 유효하지 않으면 기본값 사용
            if (strlen($category) > 50 || !in_array($category, $validCategories)) {
                $category = '향수';
            }

            $sql = "INSERT INTO products (name, type, price, originalPrice, rating, reviews, badge, `desc`, image, stock, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $data['name'],
                $category,
                (int)$data['price'],
                isset($data['originalPrice']) ? (int)$data['originalPrice'] : null,
                $data['rating'] ?? 0,
                $data['reviews'] ?? 0,
                $data['badge'] ?? null,
                $data['desc'] ?? '',
                $data['imageUrl'] ?? $data['image'] ?? null,
                (int)($data['stock'] ?? 0),
                $data['status'] ?? '판매중'
            ];

            $newId = db()->insert($sql, $params);
            $newProduct = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$newId]);

            http_response_code(201);
            echo json_encode($newProduct);
            break;

        case 'PUT':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            // 상품 수정
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => '상품 ID가 필요합니다.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // 기존 상품 확인
            $existing = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
            if (!$existing) {
                http_response_code(404);
                echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                exit;
            }

            // category/type 값 검증 및 정리
            $category = $data['category'] ?? $data['type'] ?? $existing['type'];
            // 유효한 카테고리 목록
            $validCategories = ['향수', '바디미스트', '헤어미스트', '디퓨저', '섬유유연제', '룸스프레이'];
            // category가 너무 길거나 유효하지 않으면 기본값 사용
            if (strlen($category) > 50 || !in_array($category, $validCategories)) {
                $category = $existing['type'] ?? '향수';
            }

            $sql = "UPDATE products SET
                    name = ?,
                    type = ?,
                    price = ?,
                    originalPrice = ?,
                    rating = ?,
                    reviews = ?,
                    badge = ?,
                    `desc` = ?,
                    image = ?,
                    stock = ?,
                    status = ?
                    WHERE id = ?";
            $params = [
                trim($data['name'] ?? $existing['name']),
                $category,
                (int)($data['price'] ?? $existing['price']),
                isset($data['originalPrice']) ? (int)$data['originalPrice'] : $existing['originalPrice'],
                $data['rating'] ?? $existing['rating'],
                $data['reviews'] ?? $existing['reviews'],
                $data['badge'] ?? $existing['badge'],
                $data['desc'] ?? $existing['desc'],
                trim($data['imageUrl'] ?? $data['image'] ?? $existing['image'] ?? ''),
                (int)($data['stock'] ?? $existing['stock'] ?? 0),
                $data['status'] ?? $existing['status'] ?? '판매중',
                $id
            ];

            try {
                db()->execute($sql, $params);
                $updated = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
                echo json_encode($updated);
            } catch (Exception $e) {
                error_log('Product update error: ' . $e->getMessage() . ' - SQL: ' . $sql . ' - Params: ' . json_encode($params));
                throw $e;
            }
            break;

        case 'DELETE':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            // 상품 삭제
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => '상품 ID가 필요합니다.']);
                exit;
            }

            $existing = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
            if (!$existing) {
                http_response_code(404);
                echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                exit;
            }

            db()->execute("DELETE FROM products WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => '상품이 삭제되었습니다.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => '허용되지 않는 메소드입니다.']);
    }
} catch (Exception $e) {
    // 프로덕션에서는 상세 에러 메시지 숨김
    error_log('Products API Error: ' . $e->getMessage());
    error_log('Products API Stack Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    // 개발 환경에서는 상세 오류 메시지 표시
    $errorMessage = '서버 오류가 발생했습니다.';
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        $errorMessage = '서버 오류: ' . $e->getMessage();
    }
    echo json_encode(['error' => $errorMessage, 'details' => $e->getMessage()]);
}
