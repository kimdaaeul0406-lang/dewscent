<?php
// api/products.php
// 상품 CRUD API

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

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
            // 상품 등록
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name']) || empty($data['price'])) {
                http_response_code(400);
                echo json_encode(['error' => '상품명과 가격은 필수입니다.']);
                exit;
            }

            $sql = "INSERT INTO products (name, type, price, originalPrice, rating, reviews, badge, `desc`, image, stock, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $data['name'],
                $data['category'] ?? $data['type'] ?? '향수',
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
                $data['name'] ?? $existing['name'],
                $data['category'] ?? $data['type'] ?? $existing['type'],
                (int)($data['price'] ?? $existing['price']),
                isset($data['originalPrice']) ? (int)$data['originalPrice'] : $existing['originalPrice'],
                $data['rating'] ?? $existing['rating'],
                $data['reviews'] ?? $existing['reviews'],
                $data['badge'] ?? $existing['badge'],
                $data['desc'] ?? $existing['desc'],
                $data['imageUrl'] ?? $data['image'] ?? $existing['image'],
                (int)($data['stock'] ?? $existing['stock'] ?? 0),
                $data['status'] ?? $existing['status'] ?? '판매중',
                $id
            ];

            db()->execute($sql, $params);
            $updated = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);

            echo json_encode($updated);
            break;

        case 'DELETE':
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
    http_response_code(500);
    echo json_encode(['error' => '서버 오류: ' . $e->getMessage()]);
}
