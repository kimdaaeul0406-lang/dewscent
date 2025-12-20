<?php
/**
 * 쿠폰 API
 * - GET: 쿠폰 목록 조회
 * - POST: 쿠폰 받기
 * - POST /validate: 쿠폰 검증
 * - POST /use: 쿠폰 사용
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 관리자 권한 확인 함수
function isAdmin() {
    return !empty($_SESSION['admin_logged_in']) || 
           (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

// 로그인 확인이 필요한 액션
$requireAuth = in_array($action, ['receive', 'my', 'use']);

if ($requireAuth && empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'my') {
                // 내 쿠폰 목록 조회 (사용하지 않은 쿠폰만 - used = 0)
                $userCoupons = db()->fetchAll(
                    "SELECT uc.*, c.code, c.name, c.type, c.value, c.min_amount, c.max_discount, 
                            c.start_date, c.end_date, c.active
                     FROM user_coupons uc
                     JOIN coupons c ON uc.coupon_id = c.id
                     WHERE uc.user_id = ? AND uc.used = 0
                     ORDER BY uc.received_at DESC",
                    [$userId]
                );
                
                // 활성 쿠폰만 필터링
                $now = date('Y-m-d');
                $activeCoupons = array_filter($userCoupons, function($uc) use ($now) {
                    if (!$uc['active']) return false;
                    if ($uc['start_date'] && $uc['start_date'] > $now) return false;
                    if ($uc['end_date'] && $uc['end_date'] < $now) return false;
                    return true;
                });
                
                echo json_encode([
                    'success' => true,
                    'coupons' => array_values($activeCoupons)
                ], JSON_UNESCAPED_UNICODE);
            } elseif ($action === 'my_all') {
                // 내 쿠폰 목록 조회 (사용한 쿠폰 포함 - used = 0, 1 모두)
                $userCoupons = db()->fetchAll(
                    "SELECT uc.*, c.code, c.name, c.type, c.value, c.min_amount, c.max_discount, 
                            c.start_date, c.end_date, c.active
                     FROM user_coupons uc
                     JOIN coupons c ON uc.coupon_id = c.id
                     WHERE uc.user_id = ?
                     ORDER BY uc.used ASC, uc.received_at DESC",
                    [$userId]
                );
                
                echo json_encode([
                    'success' => true,
                    'coupons' => $userCoupons
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // 전체 쿠폰 목록 조회
                // 관리자는 모든 쿠폰을 볼 수 있고, 일반 사용자는 활성 쿠폰만 볼 수 있음
                $isAdmin = (!empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') ||
                          (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
                
                if ($isAdmin) {
                    // 관리자: 모든 쿠폰 조회
                    $coupons = db()->fetchAll(
                        "SELECT * FROM coupons ORDER BY created_at DESC"
                    );
                } else {
                    // 일반 사용자: 활성 쿠폰만 조회
                    $now = date('Y-m-d');
                    $coupons = db()->fetchAll(
                        "SELECT * FROM coupons 
                         WHERE active = 1 
                         AND (start_date IS NULL OR start_date <= ?)
                         AND (end_date IS NULL OR end_date >= ?)
                         AND (usage_limit = 0 OR used_count < usage_limit)
                         ORDER BY created_at DESC",
                        [$now, $now]
                    );
                }
                
                echo json_encode([
                    'success' => true,
                    'coupons' => $coupons
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            if (!$input) {
                error_log('[Coupons API] JSON 파싱 실패: ' . $rawInput);
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '잘못된 요청 형식입니다.']);
                exit;
            }
            
            if ($action === 'receive') {
                // 쿠폰 받기
                $couponId = (int)($input['couponId'] ?? 0);
                
                if (!$couponId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '쿠폰 ID가 필요합니다.']);
                    exit;
                }
                
                // 쿠폰 존재 확인
                $coupon = db()->fetchOne("SELECT * FROM coupons WHERE id = ? AND active = 1", [$couponId]);
                if (!$coupon) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '유효하지 않은 쿠폰입니다.']);
                    exit;
                }
                
                // 이미 받은 쿠폰인지 확인
                $existing = db()->fetchOne(
                    "SELECT id, used FROM user_coupons WHERE user_id = ? AND coupon_id = ?",
                    [$userId, $couponId]
                );
                
                if ($existing) {
                    // 이미 사용한 쿠폰인 경우
                    if ($existing['used'] == 1) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => '이미 사용한 쿠폰입니다. 다시 받을 수 없습니다.']);
                        exit;
                    }
                    // 받았지만 아직 사용하지 않은 쿠폰
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '이미 받은 쿠폰입니다.']);
                    exit;
                }
                
                // coupon_usages에서도 확인 (이미 사용한 쿠폰인지)
                $alreadyUsed = db()->fetchOne(
                    "SELECT id FROM coupon_usages WHERE user_id = ? AND coupon_id = ?",
                    [$userId, $couponId]
                );
                
                if ($alreadyUsed) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '이미 사용한 쿠폰입니다. 다시 받을 수 없습니다.']);
                    exit;
                }
                
                // 쿠폰 받기
                db()->insert(
                    "INSERT INTO user_coupons (user_id, coupon_id, received_at) VALUES (?, ?, NOW())",
                    [$userId, $couponId]
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => '쿠폰을 받았습니다.'
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif ($action === 'validate') {
                // 쿠폰 검증
                $code = strtoupper(trim($input['code'] ?? ''));
                $amount = (int)($input['amount'] ?? 0);
                
                if (!$code) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'valid' => false, 'message' => '쿠폰 코드를 입력해주세요.']);
                    exit;
                }
                
                $now = date('Y-m-d');
                $coupon = db()->fetchOne(
                    "SELECT * FROM coupons 
                     WHERE code = ? AND active = 1 
                     AND (start_date IS NULL OR start_date <= ?)
                     AND (end_date IS NULL OR end_date >= ?)
                     AND (usage_limit = 0 OR used_count < usage_limit)",
                    [$code, $now, $now]
                );
                
                if (!$coupon) {
                    echo json_encode([
                        'success' => true,
                        'valid' => false,
                        'message' => '유효하지 않은 쿠폰입니다.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                if ($amount < $coupon['min_amount']) {
                    echo json_encode([
                        'success' => true,
                        'valid' => false,
                        'message' => "최소 주문 금액 ₩" . number_format($coupon['min_amount']) . " 이상이어야 합니다."
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // 로그인한 경우 사용 가능한 쿠폰인지 확인
                if ($userId) {
                    // 보유한 쿠폰인지 확인 (used = 0인 것만)
                    $userCoupon = db()->fetchOne(
                        "SELECT * FROM user_coupons 
                         WHERE user_id = ? AND coupon_id = ? AND used = 0",
                        [$userId, $coupon['id']]
                    );
                    
                    if (!$userCoupon) {
                        // used = 1인 경우 이미 사용된 쿠폰
                        $usedCoupon = db()->fetchOne(
                            "SELECT id FROM user_coupons 
                             WHERE user_id = ? AND coupon_id = ? AND used = 1",
                            [$userId, $coupon['id']]
                        );
                        
                        if ($usedCoupon) {
                            error_log("[Coupons API] validate: 이미 사용된 쿠폰 (user_coupons.used=1): userId={$userId}, couponId={$coupon['id']}");
                            echo json_encode([
                                'success' => true,
                                'valid' => false,
                                'message' => '이미 사용한 쿠폰입니다. 계정당 한 번만 사용 가능합니다.'
                            ], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                        
                        // 보유하지 않은 쿠폰
                        error_log("[Coupons API] validate: 보유하지 않은 쿠폰: userId={$userId}, couponId={$coupon['id']}");
                        echo json_encode([
                            'success' => true,
                            'valid' => false,
                            'message' => '보유하지 않은 쿠폰입니다.'
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    
                    // coupon_usages에서도 확인 (이중 체크)
                    $alreadyUsed = db()->fetchOne(
                        "SELECT id FROM coupon_usages 
                         WHERE user_id = ? AND coupon_id = ?",
                        [$userId, $coupon['id']]
                    );
                    
                    if ($alreadyUsed) {
                        error_log("[Coupons API] validate: 이미 사용된 쿠폰 (coupon_usages 존재): userId={$userId}, couponId={$coupon['id']}");
                        echo json_encode([
                            'success' => true,
                            'valid' => false,
                            'message' => '이미 사용한 쿠폰입니다. 계정당 한 번만 사용 가능합니다.'
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'valid' => true,
                    'coupon' => $coupon
                ], JSON_UNESCAPED_UNICODE);
                
            } elseif ($action === 'use') {
                // 쿠폰 사용
                $couponId = (int)($input['couponId'] ?? 0);
                $orderId = (int)($input['orderId'] ?? 0);
                $orderNumber = trim($input['orderNumber'] ?? '');
                $discountAmount = (int)($input['discountAmount'] ?? 0);
                
                if (!$couponId || !$orderNumber || !$discountAmount) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
                    exit;
                }
                
                // 쿠폰 정보 가져오기
                $coupon = db()->fetchOne("SELECT * FROM coupons WHERE id = ?", [$couponId]);
                if (!$coupon) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '유효하지 않은 쿠폰입니다.']);
                    exit;
                }
                
                // 모든 쿠폰은 계정당 한 번만 사용 가능
                $alreadyUsed = db()->fetchOne(
                    "SELECT id FROM coupon_usages 
                     WHERE user_id = ? AND coupon_id = ?",
                    [$userId, $couponId]
                );
                
                if ($alreadyUsed) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '이미 사용한 쿠폰입니다. 계정당 한 번만 사용 가능합니다.']);
                    exit;
                }
                
                // 사용 가능한 쿠폰인지 확인
                $userCoupon = db()->fetchOne(
                    "SELECT * FROM user_coupons 
                     WHERE user_id = ? AND coupon_id = ? AND used = 0",
                    [$userId, $couponId]
                );
                
                if (!$userCoupon) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '사용할 수 없는 쿠폰입니다.']);
                    exit;
                }
                
                // 쿠폰 사용 처리
                db()->getConnection()->beginTransaction();
                
                try {
                    // 이미 사용된 쿠폰인지 다시 한 번 확인 (트랜잭션 내에서)
                    $alreadyUsedInTx = db()->fetchOne(
                        "SELECT id FROM coupon_usages 
                         WHERE user_id = ? AND coupon_id = ?",
                        [$userId, $couponId]
                    );
                    
                    if ($alreadyUsedInTx) {
                        db()->getConnection()->rollBack();
                        error_log("[Coupons API] 이미 사용된 쿠폰: userId={$userId}, couponId={$couponId}");
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => '이미 사용한 쿠폰입니다. 계정당 한 번만 사용 가능합니다.']);
                        exit;
                    }
                    
                    // user_coupons 업데이트 (used = 0인 것만 업데이트하여 중복 방지)
                    $updateResult = db()->execute(
                        "UPDATE user_coupons SET used = 1, used_at = NOW(), order_id = ? 
                         WHERE user_id = ? AND coupon_id = ? AND used = 0",
                        [$orderId > 0 ? $orderId : null, $userId, $couponId]
                    );
                    
                    error_log("[Coupons API] user_coupons 업데이트 결과: {$updateResult}행 업데이트됨 (userId={$userId}, couponId={$couponId})");
                    
                    // 업데이트된 행이 없으면 이미 사용된 쿠폰
                    if ($updateResult === 0) {
                        db()->getConnection()->rollBack();
                        error_log("[Coupons API] 업데이트된 행이 없음 - 이미 사용된 쿠폰: userId={$userId}, couponId={$couponId}");
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => '이미 사용한 쿠폰입니다.']);
                        exit;
                    }
                    
                    // coupon_usages에 기록 (중복 사용 방지)
                    // order_id가 0이면 NULL로 설정 (테이블이 NULL 허용인 경우)
                    try {
                        $usageId = db()->insert(
                            "INSERT INTO coupon_usages (user_id, coupon_id, order_id, order_number, discount_amount) 
                             VALUES (?, ?, ?, ?, ?)",
                            [$userId, $couponId, $orderId > 0 ? $orderId : 0, $orderNumber, $discountAmount]
                        );
                        error_log("[Coupons API] coupon_usages 기록 성공: id={$usageId} (userId={$userId}, couponId={$couponId})");
                    } catch (Exception $e) {
                        // order_id가 NOT NULL이고 0이 허용되지 않는 경우
                        error_log("[Coupons API] coupon_usages 기록 실패 (계속 진행): " . $e->getMessage());
                        // user_coupons의 used만 업데이트하고 coupon_usages는 건너뜀
                        // 하지만 중복 체크는 user_coupons의 used로도 가능
                    }
                    
                    // coupons의 used_count 증가
                    db()->execute(
                        "UPDATE coupons SET used_count = used_count + 1 WHERE id = ?",
                        [$couponId]
                    );
                    
                    db()->getConnection()->commit();
                    
                    error_log("[Coupons API] 쿠폰 사용 성공: userId={$userId}, couponId={$couponId}, orderNumber={$orderNumber}");
                    
                    echo json_encode([
                        'success' => true,
                        'message' => '쿠폰이 사용되었습니다.'
                    ], JSON_UNESCAPED_UNICODE);
                    
                } catch (Exception $e) {
                    db()->getConnection()->rollBack();
                    error_log("[Coupons API] 쿠폰 사용 실패: " . $e->getMessage());
                    error_log("[Coupons API] 쿠폰 사용 실패 스택: " . $e->getTraceAsString());
                    throw $e;
                }
                
            } elseif ($action === 'save') {
                // 관리자: 쿠폰 생성/수정
                if (!isAdmin()) {
                    error_log('[Coupons API] 관리자 권한 없음: admin_logged_in=' . (isset($_SESSION['admin_logged_in']) ? '1' : '0') . ', role=' . ($_SESSION['role'] ?? '없음'));
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
                    exit;
                }
                
                try {
                    $couponId = (int)($input['id'] ?? 0);
                    $code = strtoupper(trim($input['code'] ?? ''));
                    $name = trim($input['name'] ?? '');
                    $type = $input['type'] ?? 'percent';
                    $value = (int)($input['value'] ?? 0);
                    $minAmount = (int)($input['minAmount'] ?? 0);
                    $maxDiscount = (int)($input['maxDiscount'] ?? 0);
                    $startDate = !empty($input['startDate']) ? $input['startDate'] : null;
                    $endDate = !empty($input['endDate']) ? $input['endDate'] : null;
                    $usageLimit = (int)($input['usageLimit'] ?? 0);
                    $active = isset($input['active']) ? (int)$input['active'] : 1;
                    
                    error_log('[Coupons API] 쿠폰 저장 시도: id=' . $couponId . ', code=' . $code . ', name=' . $name);
                    
                    if (empty($code) || empty($name) || $value <= 0) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => '필수 항목을 모두 입력해주세요.']);
                        exit;
                    }
                    
                    // 쿠폰 코드 중복 확인 (수정 시 자기 자신 제외)
                    $existing = db()->fetchOne(
                        "SELECT id FROM coupons WHERE code = ? AND id != ?",
                        [$code, $couponId]
                    );
                    
                    if ($existing) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => '이미 존재하는 쿠폰 코드입니다.']);
                        exit;
                    }
                    
                    if ($couponId > 0) {
                        // 수정
                        $result = db()->execute(
                            "UPDATE coupons SET
                                code = ?, name = ?, type = ?, value = ?,
                                min_amount = ?, max_discount = ?,
                                start_date = ?, end_date = ?,
                                usage_limit = ?, active = ?
                             WHERE id = ?",
                            [$code, $name, $type, $value, $minAmount, $maxDiscount, 
                             $startDate, $endDate, $usageLimit, $active, $couponId]
                        );
                        error_log('[Coupons API] 쿠폰 수정 완료: id=' . $couponId);
                        echo json_encode(['success' => true, 'message' => '쿠폰이 수정되었습니다.']);
                    } else {
                        // 생성
                        $newId = db()->insert(
                            "INSERT INTO coupons 
                                (code, name, type, value, min_amount, max_discount, 
                                 start_date, end_date, usage_limit, active, used_count)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)",
                            [$code, $name, $type, $value, $minAmount, $maxDiscount,
                             $startDate, $endDate, $usageLimit, $active]
                        );
                        error_log('[Coupons API] 쿠폰 생성 완료: id=' . $newId);
                        echo json_encode(['success' => true, 'message' => '쿠폰이 생성되었습니다.', 'id' => $newId]);
                    }
                } catch (Exception $e) {
                    error_log('[Coupons API] 쿠폰 저장 실패: ' . $e->getMessage());
                    error_log('[Coupons API] 쿠폰 저장 실패 스택: ' . $e->getTraceAsString());
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => '쿠폰 저장 중 오류가 발생했습니다: ' . $e->getMessage()]);
                    exit;
                }
                
            } elseif ($action === 'delete') {
                // 관리자: 쿠폰 삭제
                if (!isAdmin()) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
                    exit;
                }
                
                try {
                    $couponId = (int)($input['id'] ?? 0);
                    
                    if (!$couponId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => '쿠폰 ID가 필요합니다.']);
                        exit;
                    }
                    
                    error_log('[Coupons API] 쿠폰 삭제 시도: id=' . $couponId);
                    db()->execute("DELETE FROM coupons WHERE id = ?", [$couponId]);
                    error_log('[Coupons API] 쿠폰 삭제 완료: id=' . $couponId);
                    echo json_encode(['success' => true, 'message' => '쿠폰이 삭제되었습니다.']);
                } catch (Exception $e) {
                    error_log('[Coupons API] 쿠폰 삭제 실패: ' . $e->getMessage());
                    error_log('[Coupons API] 쿠폰 삭제 실패 스택: ' . $e->getTraceAsString());
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => '쿠폰 삭제 중 오류가 발생했습니다: ' . $e->getMessage()]);
                    exit;
                }
                
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('쿠폰 API 오류: ' . $e->getMessage());
    error_log('쿠폰 API 오류 스택: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '서버 오류가 발생했습니다.',
        'error' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
