<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $popups = db()->fetchAll(
                "SELECT id, title, content, link, image_url as imageUrl, `order`, active, 
                        start_date as startDate, end_date as endDate
                 FROM popups 
                 ORDER BY `order` ASC"
            );
            echo json_encode($popups, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '팝업 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'POST':
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            if ($method === 'POST') {
                db()->insert(
                    "INSERT INTO popups (title, content, link, image_url, `order`, active, start_date, end_date) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $data['title'] ?? '',
                        $data['content'] ?? null,
                        $data['link'] ?? null,
                        $data['imageUrl'] ?? null,
                        $data['order'] ?? 0,
                        $data['active'] ?? 1,
                        $data['startDate'] ?? null,
                        $data['endDate'] ?? null
                    ]
                );
            } else {
                db()->execute(
                    "UPDATE popups SET title = ?, content = ?, link = ?, image_url = ?, `order` = ?, active = ?, start_date = ?, end_date = ? WHERE id = ?",
                    [
                        $data['title'] ?? '',
                        $data['content'] ?? null,
                        $data['link'] ?? null,
                        $data['imageUrl'] ?? null,
                        $data['order'] ?? 0,
                        $data['active'] ?? 1,
                        $data['startDate'] ?? null,
                        $data['endDate'] ?? null,
                        $data['id'] ?? 0
                    ]
                );
            }
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '팝업 저장 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        try {
            db()->execute("DELETE FROM popups WHERE id = ?", [$id]);
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '팝업 삭제 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
