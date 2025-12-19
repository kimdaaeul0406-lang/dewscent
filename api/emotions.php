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
            $emotions = db()->fetchAll(
                "SELECT id, `key`, title, `desc`, `order`, active 
                 FROM emotions 
                 ORDER BY `order` ASC"
            );
            echo json_encode($emotions, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '감정 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'POST':
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            if ($method === 'POST') {
                db()->insert(
                    "INSERT INTO emotions (`key`, title, `desc`, `order`, active) 
                     VALUES (?, ?, ?, ?, ?)",
                    [
                        $data['key'] ?? '',
                        $data['title'] ?? '',
                        $data['desc'] ?? null,
                        $data['order'] ?? 0,
                        $data['active'] ?? 1
                    ]
                );
            } else {
                db()->execute(
                    "UPDATE emotions SET title = ?, `desc` = ?, `order` = ?, active = ? WHERE id = ?",
                    [
                        $data['title'] ?? '',
                        $data['desc'] ?? null,
                        $data['order'] ?? 0,
                        $data['active'] ?? 1,
                        $data['id'] ?? 0
                    ]
                );
            }
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '감정 저장 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        try {
            db()->execute("DELETE FROM emotions WHERE id = ?", [$id]);
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '감정 삭제 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
