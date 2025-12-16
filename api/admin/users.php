<?php
// config.php를 먼저 require하여 세션 설정이 적용되도록
require_once __DIR__ . '/../../includes/config.php';

// 세션이 시작되지 않았으면 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 자동 생성
ensure_tables_exist();

// 관리자 권한 체크 (두 가지 방식 모두 지원)
$isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           !empty($_SESSION['admin_logged_in']);

// 디버깅: 세션 정보 로그 (개발 환경에서만)
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log('Admin Users API - Session check: role=' . ($_SESSION['role'] ?? 'null') . ', admin_logged_in=' . (isset($_SESSION['admin_logged_in']) ? 'true' : 'false') . ', user_id=' . ($_SESSION['user_id'] ?? 'null'));
    error_log('Admin Users API - Session ID: ' . session_id());
    error_log('Admin Users API - All session vars: ' . print_r($_SESSION, true));
}

if (!$isAdmin) {
    http_response_code(401);
    $debugMsg = defined('APP_DEBUG') && APP_DEBUG ? ' (role: ' . ($_SESSION['role'] ?? 'null') . ', admin_logged_in: ' . (isset($_SESSION['admin_logged_in']) ? 'true' : 'false') . ')' : '';
    echo json_encode(['ok' => false, 'message' => '관리자 권한이 필요합니다.' . $debugMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // 회원 목록 조회
        try {
            $users = db()->fetchAll(
                "SELECT id, name, email, phone, address, is_admin, created_at 
                 FROM users 
                 ORDER BY created_at DESC"
            );

            // 날짜 포맷팅
            foreach ($users as &$user) {
                if (!empty($user['created_at'])) {
                    $user['joinedAt'] = substr($user['created_at'], 0, 10);
                }
                $user['status'] = $user['is_admin'] ? '관리자' : '일반';
            }

            echo json_encode($users, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            $errorMsg = '회원 목록 조회 중 오류가 발생했습니다.';
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $errorMsg .= ' ' . $e->getMessage();
                error_log('Admin Users API Error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
            }
            echo json_encode(['ok' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}

