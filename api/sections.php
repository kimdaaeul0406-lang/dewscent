<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $sections = db()->fetchOne("SELECT * FROM sections WHERE id = 1");
        if (!$sections) {
            db()->insert("INSERT INTO sections (id) VALUES (1)");
            $sections = db()->fetchOne("SELECT * FROM sections WHERE id = 1");
        }
        
        echo json_encode([
            'emotionLabel' => $sections['emotion_label'] ?? 'FIND YOUR SCENT',
            'emotionTitle' => $sections['emotion_title'] ?? '오늘, 어떤 기분인가요?',
            'emotionSubtitle' => $sections['emotion_subtitle'] ?? '감정에 맞는 향기를 추천해드릴게요',
            'bestLabel' => $sections['best_label'] ?? 'BEST SELLERS',
            'bestTitle' => $sections['best_title'] ?? '가장 사랑받는 향기',
            'bestSubtitle' => $sections['best_subtitle'] ?? '많은 분들이 선택한 듀센트의 베스트셀러',
            'bestQuote' => $sections['best_quote'] ?? '— 향기는 기억을 여는 열쇠 —'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '섹션 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'PUT' || $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        db()->execute(
            "UPDATE sections SET 
                emotion_label = ?, emotion_title = ?, emotion_subtitle = ?,
                best_label = ?, best_title = ?, best_subtitle = ?, best_quote = ?
             WHERE id = 1",
            [
                $data['emotionLabel'] ?? 'FIND YOUR SCENT',
                $data['emotionTitle'] ?? '오늘, 어떤 기분인가요?',
                $data['emotionSubtitle'] ?? '감정에 맞는 향기를 추천해드릴게요',
                $data['bestLabel'] ?? 'BEST SELLERS',
                $data['bestTitle'] ?? '가장 사랑받는 향기',
                $data['bestSubtitle'] ?? '많은 분들이 선택한 듀센트의 베스트셀러',
                $data['bestQuote'] ?? '— 향기는 기억을 여는 열쇠 —'
            ]
        );
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '섹션 저장 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
