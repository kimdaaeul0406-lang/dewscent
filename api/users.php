<?php
/**
 * 사용자 관리 API
 * - DELETE: 회원 탈퇴
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];

// 로그인 확인
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    switch ($method) {
        case 'DELETE':
            // 회원 탈퇴
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                $data = $_POST;
            }
            
            $password = trim($data['password'] ?? '');
            
            // 비밀번호 확인
            if (empty($password)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => '비밀번호를 입력해주세요.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 사용자 정보 조회
            $user = db()->fetchOne(
                "SELECT id, email, password, is_admin FROM users WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => '사용자를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 관리자는 탈퇴 불가
            if (!empty($user['is_admin'])) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'message' => '관리자 계정은 탈퇴할 수 없습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 비밀번호 확인
            $storedPassword = $user['password'] ?? '';
            $verified = password_verify($password, $storedPassword);
            
            // 기존에 평문으로 저장된 데이터 호환
            if (!$verified && $storedPassword !== '') {
                $verified = hash_equals($storedPassword, $password);
            }
            
            if (!$verified) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'message' => '비밀번호가 일치하지 않습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 진행 중인 주문이 있는지 확인
            $activeOrders = db()->fetchAll(
                "SELECT id FROM orders 
                 WHERE user_id = ? 
                 AND status IN ('pending', 'paid', 'preparing', 'shipping')",
                [$userId]
            );
            
            if (!empty($activeOrders)) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false, 
                    'message' => '진행 중인 주문이 있어 탈퇴할 수 없습니다. 주문을 완료하거나 취소한 후 다시 시도해주세요.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 트랜잭션 시작
            $conn = db()->getConnection();
            $conn->beginTransaction();
            
            try {
                // 사용자 관련 데이터 삭제 (외래키 CASCADE로 자동 삭제됨)
                // - reviews (CASCADE)
                // - inquiries (CASCADE)
                // - user_coupons (CASCADE)
                // - coupon_usages (CASCADE)
                // - orders는 SET NULL이므로 user_id만 NULL로 변경
                
                // 사용자 삭제
                db()->execute("DELETE FROM users WHERE id = ?", [$userId]);
                
                $conn->commit();
                
                // 세션 삭제
                session_destroy();
                
                echo json_encode([
                    'ok' => true, 
                    'message' => '회원 탈퇴가 완료되었습니다.'
                ], JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    $errorMsg = '처리 중 오류가 발생했습니다.';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $errorMsg .= ' ' . $e->getMessage();
        error_log('User API error: ' . $e->getMessage());
    }
    echo json_encode(['ok' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
}
