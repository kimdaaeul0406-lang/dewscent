<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $emotionKey = $_GET['emotion_key'] ?? null;
    try {
        if ($emotionKey) {
            // 특정 감정의 추천 상품 조회
            $recommendations = db()->fetchAll(
                "SELECT er.id, er.emotion_key as emotionKey, er.product_id as productId, er.`order`
                 FROM emotion_recommendations er
                 WHERE er.emotion_key = ?
                 ORDER BY er.`order` ASC",
                [$emotionKey]
            );
        } else {
            // 전체 추천 상품 조회
            $recommendations = db()->fetchAll(
                "SELECT er.id, er.emotion_key as emotionKey, er.product_id as productId, er.`order`
                 FROM emotion_recommendations er
                 ORDER BY er.emotion_key, er.`order` ASC"
            );
        }
        echo json_encode($recommendations, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '추천 상품 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'POST' || $method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $emotionKey = $data['emotionKey'] ?? '';
    $productIds = $data['productIds'] ?? [];
    
    try {
        // 기존 추천 상품 삭제
        db()->execute("DELETE FROM emotion_recommendations WHERE emotion_key = ?", [$emotionKey]);
        
        // 새 추천 상품 추가
        $order = 0;
        foreach ($productIds as $productId) {
            db()->insert(
                "INSERT INTO emotion_recommendations (emotion_key, product_id, `order`) VALUES (?, ?, ?)",
                [$emotionKey, $productId, $order++]
            );
        }
        
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '추천 상품 저장 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'DELETE') {
    $emotionKey = $_GET['emotion_key'] ?? '';
    try {
        db()->execute("DELETE FROM emotion_recommendations WHERE emotion_key = ?", [$emotionKey]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '추천 상품 삭제 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
