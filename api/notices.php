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
            $notices = db()->fetchAll(
                "SELECT id, type, title, content, image_url as imageUrl, link, 
                        start_date as startDate, end_date as endDate, active, created_at as createdAt
                 FROM notices 
                 ORDER BY created_at DESC"
            );
            echo json_encode($notices, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '공지사항 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'POST':
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            if ($method === 'POST') {
                db()->insert(
                    "INSERT INTO notices (type, title, content, image_url, link, start_date, end_date, active) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $data['type'] ?? 'notice',
                        $data['title'] ?? '',
                        $data['content'] ?? null,
                        $data['imageUrl'] ?? null,
                        $data['link'] ?? null,
                        $data['startDate'] ?? null,
                        $data['endDate'] ?? null,
                        $data['active'] ?? 1
                    ]
                );
            } else {
                db()->execute(
                    "UPDATE notices SET type = ?, title = ?, content = ?, image_url = ?, link = ?, start_date = ?, end_date = ?, active = ? WHERE id = ?",
                    [
                        $data['type'] ?? 'notice',
                        $data['title'] ?? '',
                        $data['content'] ?? null,
                        $data['imageUrl'] ?? null,
                        $data['link'] ?? null,
                        $data['startDate'] ?? null,
                        $data['endDate'] ?? null,
                        $data['active'] ?? 1,
                        $data['id'] ?? 0
                    ]
                );
            }
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '공지사항 저장 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        try {
            db()->execute("DELETE FROM notices WHERE id = ?", [$id]);
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '공지사항 삭제 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
