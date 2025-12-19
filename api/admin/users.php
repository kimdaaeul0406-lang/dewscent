<?php
// 에러 리포팅 (배포 서버 디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

error_log('[Admin Users API] ========== 요청 시작 ==========');
error_log('[Admin Users API] Request URI: ' . $_SERVER['REQUEST_URI']);
error_log('[Admin Users API] Request Method: ' . $_SERVER['REQUEST_METHOD']);

// config.php를 먼저 require하여 세션 설정이 적용되도록
require_once __DIR__ . '/../../includes/config.php';

// 세션이 시작되지 않았으면 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log('[Admin Users API] Session ID: ' . session_id());
error_log('[Admin Users API] Session Status: ' . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE'));

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 자동 생성
try {
    ensure_tables_exist();
    error_log('[Admin Users API] Tables ensured');
} catch (Exception $e) {
    error_log('[Admin Users API] Table creation error: ' . $e->getMessage());
}

// 관리자 권한 체크 (두 가지 방식 모두 지원)
$isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           !empty($_SESSION['admin_logged_in']);

error_log('[Admin Users API] isAdmin check: ' . ($isAdmin ? 'true' : 'false'));
error_log('[Admin Users API] role: ' . ($_SESSION['role'] ?? 'not set'));
error_log('[Admin Users API] admin_logged_in: ' . (isset($_SESSION['admin_logged_in']) ? ($_SESSION['admin_logged_in'] ? 'true' : 'false') : 'not set'));

if (!$isAdmin) {
    http_response_code(401);
    $debugMsg = defined('APP_DEBUG') && APP_DEBUG ? ' (role: ' . ($_SESSION['role'] ?? 'null') . ', admin_logged_in: ' . (isset($_SESSION['admin_logged_in']) ? 'true' : 'false') . ')' : '';
    error_log('[Admin Users API] Access denied - not admin');
    echo json_encode(['ok' => false, 'message' => '관리자 권한이 필요합니다.' . $debugMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

error_log('[Admin Users API] Access granted - continuing');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // 회원 목록 조회
        try {
            error_log('[Admin Users API] DB query 시작');
            $startTime = microtime(true);
            
            $users = db()->fetchAll(
                "SELECT id, name, email, phone, address, is_admin, created_at 
                 FROM users 
                 ORDER BY created_at DESC"
            );

            $queryTime = microtime(true) - $startTime;
            error_log('[Admin Users API] DB query 완료: ' . round($queryTime * 1000, 2) . 'ms, Users count: ' . count($users));

            // 날짜 포맷팅
            foreach ($users as &$user) {
                if (!empty($user['created_at'])) {
                    $user['joinedAt'] = substr($user['created_at'], 0, 10);
                }
                $user['status'] = $user['is_admin'] ? '관리자' : '일반';
            }

            error_log('[Admin Users API] 응답 전송: ' . count($users) . '개 회원');
            echo json_encode($users, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            $errorMsg = '회원 목록 조회 중 오류가 발생했습니다.';
            error_log('[Admin Users API] Error: ' . $e->getMessage());
            error_log('[Admin Users API] Stack trace: ' . $e->getTraceAsString());
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $errorMsg .= ' ' . $e->getMessage();
            }
            echo json_encode(['ok' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}

