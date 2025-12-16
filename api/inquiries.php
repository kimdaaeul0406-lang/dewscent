<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 자동 생성
ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];
// 관리자 여부 확인 (두 가지 방식 모두 지원)
$isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           !empty($_SESSION['admin_logged_in']);

// 관리자 API 체크 함수
function ensure_admin_api(): bool {
    // 두 가지 방식 모두 지원: admin_logged_in 또는 role = 'admin'
    $isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
               !empty($_SESSION['admin_logged_in']);
    
    if (!$isAdmin) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
        return false;
    }
    return true;
}

switch ($method) {
    case 'GET':
        // 문의 목록 조회
        // 관리자는 user_id 없이도 접근 가능
        if (!$isAdmin && empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 관리자는 모든 문의, 일반 사용자는 자신의 문의만
        if ($isAdmin) {
            $inquiries = db()->fetchAll(
                "SELECT i.*, u.name as user_name, u.email as user_email 
                 FROM inquiries i 
                 LEFT JOIN users u ON i.user_id = u.id 
                 ORDER BY i.created_at DESC"
            );
        } else {
            $inquiries = db()->fetchAll(
                "SELECT * FROM inquiries WHERE user_id = ? ORDER BY created_at DESC",
                [$_SESSION['user_id']]
            );
        }

        // 날짜 포맷팅
        foreach ($inquiries as &$inq) {
            if (!empty($inq['created_at'])) {
                $inq['createdAt'] = substr($inq['created_at'], 0, 10);
            }
            if (!empty($inq['answered_at'])) {
                $inq['answeredAt'] = substr($inq['answered_at'], 0, 10);
            }
            $inq['orderNo'] = $inq['order_no'] ?? null;
        }

        echo json_encode($inquiries, JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // 문의 등록
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            $data = $_POST;
        }

        $type = trim($data['type'] ?? '');
        $orderNo = trim($data['orderNo'] ?? $data['order_no'] ?? '');
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');

        if (!$type || !$title || !$content) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '필수 항목을 모두 입력해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $inquiryId = db()->insert(
                "INSERT INTO inquiries (user_id, type, order_no, title, content, status, created_at)
                 VALUES (?, ?, ?, ?, ?, 'waiting', NOW())",
                [$_SESSION['user_id'], $type, $orderNo ?: null, $title, $content]
            );

            $newInquiry = db()->fetchOne(
                "SELECT * FROM inquiries WHERE id = ?",
                [$inquiryId]
            );

            if ($newInquiry) {
                $newInquiry['createdAt'] = substr($newInquiry['created_at'], 0, 10);
                $newInquiry['orderNo'] = $newInquiry['order_no'] ?? null;
            }

            echo json_encode(['ok' => true, 'inquiry' => $newInquiry], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '문의 등록 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        // 관리자 답변 등록/수정
        if (!ensure_admin_api()) {
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $answer = trim($data['answer'] ?? '');

        if (!$id || !$answer) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '답변 내용을 입력해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            db()->execute(
                "UPDATE inquiries SET answer = ?, status = 'answered', answered_at = NOW() WHERE id = ?",
                [$answer, $id]
            );

            $updated = db()->fetchOne("SELECT * FROM inquiries WHERE id = ?", [$id]);
            if ($updated) {
                $updated['createdAt'] = substr($updated['created_at'], 0, 10);
                $updated['answeredAt'] = $updated['answered_at'] ? substr($updated['answered_at'], 0, 10) : null;
                $updated['orderNo'] = $updated['order_no'] ?? null;
            }

            echo json_encode(['ok' => true, 'inquiry' => $updated], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '답변 등록 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}

