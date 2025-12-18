<?php
// api/variants.php
// 상품 용량별 가격 (variants) CRUD API

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// CORS 설정
$allowed_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (strpos($allowed_origin, 'localhost') !== false || $allowed_origin === '') {
    header('Access-Control-Allow-Origin: ' . ($allowed_origin ?: '*'));
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';
require_once __DIR__ . '/../admin/guard.php';

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];

// URL에서 product_id와 variant_id 추출
// 예: /api/variants.php/5 (product_id=5의 모든 variants)
// 예: /api/variants.php/5/10 (product_id=5의 variant_id=10)
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$pathParts = $path ? explode('/', $path) : [];
$productId = isset($pathParts[0]) && is_numeric($pathParts[0]) ? (int)$pathParts[0] : null;
$variantId = isset($pathParts[1]) && is_numeric($pathParts[1]) ? (int)$pathParts[1] : null;

// GET 파라미터로도 product_id 받기
if (!$productId && isset($_GET['product_id'])) {
    $productId = (int)$_GET['product_id'];
}

try {
    switch ($method) {
        case 'GET':
            // 특정 상품의 variants 조회
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => '상품 ID가 필요합니다.']);
                exit;
            }

            $variants = db()->fetchAll(
                "SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, price ASC",
                [$productId]
            );
            echo json_encode($variants ?: []);
            break;

        case 'POST':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // product_id는 URL 또는 body에서 가져오기
            $productId = $productId ?: ($data['product_id'] ?? null);

            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => '상품 ID가 필요합니다.']);
                exit;
            }

            // 상품 존재 여부 확인
            $product = db()->fetchOne("SELECT id FROM products WHERE id = ?", [$productId]);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                exit;
            }

            // 단일 variant 추가 또는 여러 개 한번에 추가
            $variants = isset($data['variants']) ? $data['variants'] : [$data];
            $insertedIds = [];

            foreach ($variants as $variant) {
                if (empty($variant['volume']) || !isset($variant['price'])) {
                    continue;
                }

                // 중복 체크
                $existing = db()->fetchOne(
                    "SELECT id FROM product_variants WHERE product_id = ? AND volume = ?",
                    [$productId, $variant['volume']]
                );

                if ($existing) {
                    // 업데이트
                    db()->execute(
                        "UPDATE product_variants SET price = ?, stock = ?, is_default = ?, sort_order = ? WHERE id = ?",
                        [
                            (int)$variant['price'],
                            (int)($variant['stock'] ?? 0),
                            (int)($variant['is_default'] ?? 0),
                            (int)($variant['sort_order'] ?? 0),
                            $existing['id']
                        ]
                    );
                    $insertedIds[] = $existing['id'];
                } else {
                    // 새로 추가
                    $newId = db()->insert(
                        "INSERT INTO product_variants (product_id, volume, price, stock, is_default, sort_order)
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [
                            $productId,
                            $variant['volume'],
                            (int)$variant['price'],
                            (int)($variant['stock'] ?? 0),
                            (int)($variant['is_default'] ?? 0),
                            (int)($variant['sort_order'] ?? 0)
                        ]
                    );
                    $insertedIds[] = $newId;
                }
            }

            // 결과 반환
            $result = db()->fetchAll(
                "SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, price ASC",
                [$productId]
            );

            http_response_code(201);
            echo json_encode(['success' => true, 'variants' => $result]);
            break;

        case 'PUT':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            if (!$variantId && !$productId) {
                http_response_code(400);
                echo json_encode(['error' => 'variant ID 또는 product ID가 필요합니다.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if ($variantId) {
                // 단일 variant 수정
                $existing = db()->fetchOne("SELECT * FROM product_variants WHERE id = ?", [$variantId]);
                if (!$existing) {
                    http_response_code(404);
                    echo json_encode(['error' => 'variant를 찾을 수 없습니다.']);
                    exit;
                }

                db()->execute(
                    "UPDATE product_variants SET volume = ?, price = ?, stock = ?, is_default = ?, sort_order = ? WHERE id = ?",
                    [
                        $data['volume'] ?? $existing['volume'],
                        (int)($data['price'] ?? $existing['price']),
                        (int)($data['stock'] ?? $existing['stock']),
                        (int)($data['is_default'] ?? $existing['is_default']),
                        (int)($data['sort_order'] ?? $existing['sort_order']),
                        $variantId
                    ]
                );

                $updated = db()->fetchOne("SELECT * FROM product_variants WHERE id = ?", [$variantId]);
                echo json_encode($updated);
            } else {
                // 전체 variants 교체 (product_id로)
                $variants = $data['variants'] ?? [];

                // 기존 variants 삭제
                db()->execute("DELETE FROM product_variants WHERE product_id = ?", [$productId]);

                // 새로 추가
                foreach ($variants as $index => $variant) {
                    if (empty($variant['volume']) || !isset($variant['price'])) {
                        continue;
                    }
                    db()->insert(
                        "INSERT INTO product_variants (product_id, volume, price, stock, is_default, sort_order)
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [
                            $productId,
                            $variant['volume'],
                            (int)$variant['price'],
                            (int)($variant['stock'] ?? 0),
                            (int)($variant['is_default'] ?? 0),
                            (int)($variant['sort_order'] ?? $index)
                        ]
                    );
                }

                $result = db()->fetchAll(
                    "SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC",
                    [$productId]
                );
                echo json_encode(['success' => true, 'variants' => $result]);
            }
            break;

        case 'DELETE':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            if ($variantId) {
                // 단일 variant 삭제
                $existing = db()->fetchOne("SELECT id FROM product_variants WHERE id = ?", [$variantId]);
                if (!$existing) {
                    http_response_code(404);
                    echo json_encode(['error' => 'variant를 찾을 수 없습니다.']);
                    exit;
                }

                db()->execute("DELETE FROM product_variants WHERE id = ?", [$variantId]);
                echo json_encode(['success' => true, 'message' => 'variant가 삭제되었습니다.']);
            } else if ($productId) {
                // 상품의 모든 variants 삭제
                db()->execute("DELETE FROM product_variants WHERE product_id = ?", [$productId]);
                echo json_encode(['success' => true, 'message' => '모든 variants가 삭제되었습니다.']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'variant ID 또는 product ID가 필요합니다.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => '허용되지 않는 메소드입니다.']);
    }
} catch (Exception $e) {
    error_log('Variants API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '서버 오류가 발생했습니다.']);
}
