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

// 상품에 variants 추가하는 헬퍼 함수
function addVariantsToProduct($product) {
    if (!$product) return $product;
    try {
        $variants = db()->fetchAll(
            "SELECT id, volume, price, stock, is_default, sort_order
             FROM product_variants
             WHERE product_id = ?
             ORDER BY sort_order ASC, price ASC",
            [$product['id']]
        );
        $product['variants'] = $variants ?: [];
    } catch (Exception $e) {
        // variants 테이블이 없으면 빈 배열 반환
        $product['variants'] = [];
    }
    // type 필드를 category로도 매핑 (프론트엔드 호환성)
    if (isset($product['type']) && !isset($product['category'])) {
        $product['category'] = $product['type'];
    }
    // emotion_keys를 배열로 변환 (JSON 문자열인 경우)
    if (isset($product['emotion_keys']) && is_string($product['emotion_keys']) && !empty($product['emotion_keys'])) {
        try {
            $product['emotionKeys'] = json_decode($product['emotion_keys'], true);
            if (!is_array($product['emotionKeys'])) {
                $product['emotionKeys'] = [];
            }
        } catch (Exception $e) {
            $product['emotionKeys'] = [];
        }
    } else {
        $product['emotionKeys'] = [];
    }
    // fragrance_type을 fragranceType으로도 매핑
    if (isset($product['fragrance_type']) && !isset($product['fragranceType'])) {
        $product['fragranceType'] = $product['fragrance_type'];
    }
    // image를 imageUrl로도 매핑 (프론트엔드 호환성) - NULL이나 빈 문자열이 아닐 때만
    if (isset($product['image']) && $product['image'] !== null && $product['image'] !== '') {
        $trimmedImage = trim($product['image']);
        if ($trimmedImage !== '' && $trimmedImage !== 'null' && $trimmedImage !== 'NULL' && strlen($trimmedImage) > 10 && !isset($product['imageUrl'])) {
            $product['imageUrl'] = $trimmedImage;
        }
    }
    // detail_image를 detailImageUrl로도 매핑 (프론트엔드 호환성) - NULL이나 빈 문자열이 아닐 때만
    if (isset($product['detail_image']) && $product['detail_image'] !== null && $product['detail_image'] !== '') {
        $trimmedDetailImage = trim($product['detail_image']);
        if ($trimmedDetailImage !== '' && $trimmedDetailImage !== 'null' && $trimmedDetailImage !== 'NULL' && strlen($trimmedDetailImage) > 10 && !isset($product['detailImageUrl'])) {
            $product['detailImageUrl'] = $trimmedDetailImage;
        }
    }
    return $product;
}

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // 단일 상품 조회 (variants 포함)
                $product = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
                if ($product) {
                    $product = addVariantsToProduct($product);
                    echo json_encode($product);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                }
            } else {
                // 전체 상품 목록 (variants 포함)
                $products = db()->fetchAll("SELECT * FROM products ORDER BY id DESC");
                foreach ($products as &$p) {
                    $p = addVariantsToProduct($p);
                }
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

            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => '상품명은 필수입니다.']);
                exit;
            }

            // category/type 값 검증 및 정리
            $category = $data['category'] ?? $data['type'] ?? '향수';
            $validCategories = ['향수', '바디미스트', '헤어미스트', '디퓨저', '섬유유연제', '룸스프레이'];
            // category가 너무 길거나 유효하지 않으면 기본값 사용
            if (strlen($category) > 50 || !in_array($category, $validCategories)) {
                $category = '향수';
            }

            // price는 variants의 기본 가격이 있으면 사용, 없으면 0 (호환성 유지)
            $price = isset($data['price']) && $data['price'] > 0 ? (int)$data['price'] : 0;

            // 향기 타입 및 감정 키 처리
            $fragranceType = !empty($data['fragranceType']) ? trim($data['fragranceType']) : null;
            $emotionKeys = null;
            if (!empty($data['emotionKeys']) && is_array($data['emotionKeys'])) {
                $emotionKeys = json_encode($data['emotionKeys'], JSON_UNESCAPED_UNICODE);
            } elseif (!empty($data['emotionKeys']) && is_string($data['emotionKeys'])) {
                $emotionKeys = $data['emotionKeys'];
            }

            // 이미지 데이터 처리: 빈 문자열이면 NULL로
            $imageData = $data['imageUrl'] ?? $data['image'] ?? null;
            if ($imageData !== null) {
                $imageData = trim($imageData);
                if ($imageData === '' || $imageData === 'null' || $imageData === 'NULL') {
                    $imageData = null;
                }
            }
            
            $detailImageData = $data['detailImageUrl'] ?? $data['detail_image'] ?? null;
            if ($detailImageData !== null) {
                $detailImageData = trim($detailImageData);
                if ($detailImageData === '' || $detailImageData === 'null' || $detailImageData === 'NULL') {
                    $detailImageData = null;
                }
            }

            $sql = "INSERT INTO products (name, type, price, originalPrice, rating, reviews, badge, `desc`, image, detail_image, status, fragrance_type, emotion_keys)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $data['name'],
                $category,
                $price,
                isset($data['originalPrice']) ? (int)$data['originalPrice'] : null,
                $data['rating'] ?? 0,
                $data['reviews'] ?? 0,
                $data['badge'] ?? null,
                $data['desc'] ?? '',
                $imageData,
                $detailImageData,
                $data['status'] ?? '판매중',
                $fragranceType,
                $emotionKeys
            ];

            $newId = db()->insert($sql, $params);
            $newProduct = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$newId]);
            $newProduct = addVariantsToProduct($newProduct);

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

            // 향기 타입 및 감정 키 처리
            $fragranceType = isset($data['fragranceType']) ? (!empty($data['fragranceType']) ? trim($data['fragranceType']) : null) : $existing['fragrance_type'];
            $emotionKeys = null;
            if (isset($data['emotionKeys'])) {
                if (is_array($data['emotionKeys']) && count($data['emotionKeys']) > 0) {
                    $emotionKeys = json_encode($data['emotionKeys'], JSON_UNESCAPED_UNICODE);
                } elseif (is_string($data['emotionKeys']) && !empty($data['emotionKeys'])) {
                    $emotionKeys = $data['emotionKeys'];
                } else {
                    $emotionKeys = null;
                }
            } else {
                $emotionKeys = $existing['emotion_keys'] ?? null;
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
                    detail_image = ?,
                    status = ?,
                    fragrance_type = ?,
                    emotion_keys = ?
                    WHERE id = ?";
            // price는 variants의 기본 가격이 있으면 사용, 없으면 기존 값 유지 (호환성)
            $price = isset($data['price']) && $data['price'] > 0 ? (int)$data['price'] : ($existing['price'] ?? 0);

            // 이미지 데이터 처리: 빈 문자열이면 NULL로, 아니면 trim
            $imageData = $data['imageUrl'] ?? $data['image'] ?? null;
            if ($imageData !== null) {
                $imageData = trim($imageData);
                if ($imageData === '' || $imageData === 'null' || $imageData === 'NULL') {
                    $imageData = $existing['image'] ?? null;
                }
            } else {
                $imageData = $existing['image'] ?? null;
            }
            
            $detailImageData = $data['detailImageUrl'] ?? $data['detail_image'] ?? null;
            if ($detailImageData !== null) {
                $detailImageData = trim($detailImageData);
                if ($detailImageData === '' || $detailImageData === 'null' || $detailImageData === 'NULL') {
                    $detailImageData = $existing['detail_image'] ?? null;
                }
            } else {
                $detailImageData = $existing['detail_image'] ?? null;
            }

            $params = [
                trim($data['name'] ?? $existing['name']),
                $category,
                $price,
                isset($data['originalPrice']) ? (int)$data['originalPrice'] : $existing['originalPrice'],
                $data['rating'] ?? $existing['rating'],
                $data['reviews'] ?? $existing['reviews'],
                $data['badge'] ?? $existing['badge'],
                $data['desc'] ?? $existing['desc'],
                $imageData,
                $detailImageData,
                $data['status'] ?? $existing['status'] ?? '판매중',
                $fragranceType,
                $emotionKeys,
                $id
            ];

            try {
                db()->execute($sql, $params);
                $updated = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
                $updated = addVariantsToProduct($updated);
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
