<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $mainProducts = db()->fetchAll(
            "SELECT product_id as productId, `order`
             FROM main_products 
             ORDER BY `order` ASC"
        );
        $productIds = array_map(function($item) {
            return $item['productId'];
        }, $mainProducts);
        echo json_encode($productIds, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '메인 상품 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'PUT' || $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productIds = $data['productIds'] ?? [];
    
    try {
        // 기존 메인 상품 삭제
        db()->execute("DELETE FROM main_products");
        
        // 새 메인 상품 추가
        $order = 0;
        foreach ($productIds as $productId) {
            db()->insert(
                "INSERT INTO main_products (product_id, `order`) VALUES (?, ?)",
                [$productId, $order++]
            );
        }
        
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '메인 상품 저장 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
