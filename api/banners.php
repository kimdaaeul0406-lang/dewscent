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
            $banners = db()->fetchAll(
                "SELECT id, title, subtitle, link, image_url as imageUrl, `order`, active 
                 FROM banners 
                 ORDER BY `order` ASC"
            );
            echo json_encode($banners, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '배너 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'POST':
    case 'PUT':
        // 관리자 권한 체크 (필요시 추가)
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            if ($method === 'POST') {
                // 새 배너 생성
                db()->insert(
                    "INSERT INTO banners (title, subtitle, link, image_url, `order`, active) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $data['title'] ?? '',
                        $data['subtitle'] ?? null,
                        $data['link'] ?? null,
                        $data['imageUrl'] ?? null,
                        $data['order'] ?? 0,
                        $data['active'] ?? 1
                    ]
                );
            } else {
                // 배너 수정
                db()->execute(
                    "UPDATE banners SET title = ?, subtitle = ?, link = ?, image_url = ?, `order` = ?, active = ? WHERE id = ?",
                    [
                        $data['title'] ?? '',
                        $data['subtitle'] ?? null,
                        $data['link'] ?? null,
                        $data['imageUrl'] ?? null,
                        $data['order'] ?? 0,
                        $data['active'] ?? 1,
                        $data['id'] ?? 0
                    ]
                );
            }
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '배너 저장 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        try {
            db()->execute("DELETE FROM banners WHERE id = ?", [$id]);
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '배너 삭제 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
