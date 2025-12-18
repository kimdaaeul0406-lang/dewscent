<?php
/**
 * 토스페이먼츠 결제 승인 API
 * 
 * 토스페이먼츠에서 리다이렉트된 후,
 * paymentKey와 orderId를 받아 결제를 승인합니다.
 */

// .env 파일 로드
require_once __DIR__ . '/../../config/env.php';

session_start();
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 환경 변수에서 시크릿 키 읽기 (getenv() 우선, 없으면 $_ENV에서 읽기)
$tossSecretKey = getenv('TOSS_SECRET_KEY') ?: ($_ENV['TOSS_SECRET_KEY'] ?? '');

if (empty($tossSecretKey)) {
    error_log('[Payment Confirm] TOSS_SECRET_KEY가 설정되지 않았습니다.');

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '결제 설정이 올바르지 않습니다. .env 파일을 확인해주세요.']);
    exit;
}

// 키 검증 (마스킹 처리) - 디버그 모드에서만
if (defined('APP_DEBUG') && APP_DEBUG) {
    $maskedSecretKey = strlen($tossSecretKey) > 6 
        ? substr($tossSecretKey, 0, 6) . str_repeat('*', strlen($tossSecretKey) - 6)
        : str_repeat('*', strlen($tossSecretKey));
    error_log('[Payment Confirm] SECRET_KEY loaded: ' . $maskedSecretKey);
}

// JSON 요청 본문 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$paymentKey = trim($input['paymentKey'] ?? '');
$orderId = trim($input['orderId'] ?? '');
$amount = isset($input['amount']) ? (int)$input['amount'] : 0;

// 필수 파라미터 검증
if (empty($paymentKey) || empty($orderId) || $amount < 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '결제 정보가 올바르지 않습니다.']);
    exit;
}

// payment_orders 테이블에서 결제 진행 데이터 조회
try {
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/db_setup.php';
    
    error_log('[Payment Confirm] 🔍 DB 조회 시작: orderId=' . $orderId . ', 요청 amount=' . $amount);
    
    // DB에서 결제 데이터 조회
    $orderData = db()->fetchOne(
        "SELECT order_id, order_name, amount, customer_name, customer_email, status 
         FROM payment_orders 
         WHERE order_id = ?",
        [$orderId]
    );

    if (!$orderData) {
        error_log('[Payment Confirm] ❌ DB에서 주문 데이터를 찾을 수 없음: orderId=' . $orderId);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '새로고침/뒤로가기 등으로 결제 진행 정보가 만료되었습니다. 다시 결제해주세요.']);
        exit;
    }
    
    error_log('[Payment Confirm] ✅ DB 조회 성공: orderId=' . $orderId . ', DB amount=' . $orderData['amount'] . ', status=' . $orderData['status']);
    
    // 이미 완료된 결제인 경우 (중복 호출 방지)
    if ($orderData['status'] === 'DONE') {
        // 이미 완료된 결제이므로 성공 응답 반환 (중복 호출 방지)
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 'DONE',
            'orderId' => $orderData['order_id'],
            'orderName' => $orderData['order_name'],
            'totalAmount' => (int)$orderData['amount'],
            'message' => '이미 완료된 결제입니다.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // READY 상태가 아니면 실패
    if ($orderData['status'] !== 'READY') {
    http_response_code(400);
        echo json_encode(['success' => false, 'message' => '새로고침/뒤로가기 등으로 결제 진행 정보가 만료되었습니다. 다시 결제해주세요.']);
    exit;
}

    // DB에서 복구한 amount 사용 (프론트엔드에서 받은 amount는 절대 사용하지 않음)
    $dbAmount = (int)$orderData['amount'];
    
    // 금액 검증 (DB에서 복구한 amount와 요청한 amount 비교)
    // 주의: 토스페이먼츠가 전달한 amount와 DB의 amount가 다를 수 있으므로
    // DB의 amount를 우선 사용하되, 요청한 amount도 로그에 기록
    if ($dbAmount !== $amount) {
        error_log('[Payment Confirm] ⚠️ 금액 불일치: DB=' . $dbAmount . ', 요청=' . $amount . ' | orderId=' . $orderId . ' (DB 금액 사용)');
        // DB 금액을 사용하므로 경고만 로그에 남기고 계속 진행
    }
    
    // DB에서 복구한 amount로 덮어쓰기 (중요!)
    $amount = $dbAmount;
    
} catch (Exception $e) {
    error_log('[Payment Confirm] payment_orders 조회 실패: ' . $e->getMessage() . ' | orderId=' . $orderId);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '새로고침/뒤로가기 등으로 결제 진행 정보가 만료되었습니다. 다시 결제해주세요.']);
    exit;
}

// 토스페이먼츠 결제 승인 API 호출
// 중요: DB에서 복구한 amount 사용 (변수명은 $amount로 통일)
$tossApiUrl = 'https://api.tosspayments.com/v1/payments/confirm';

$requestData = [
    'paymentKey' => $paymentKey,
    'orderId' => $orderId,
    'amount' => $amount // DB에서 복구한 amount (위에서 $amount = $dbAmount로 설정됨)
];

error_log('[Payment Confirm] 🚀 토스페이먼츠 confirm API 호출 시작');
error_log('[Payment Confirm] 📤 요청 데이터: orderId=' . $orderId . ', paymentKey=' . substr($paymentKey, 0, 30) . '..., amount=' . $dbAmount);
error_log('[Payment Confirm] 📤 전체 요청: ' . json_encode($requestData, JSON_UNESCAPED_UNICODE));

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $tossApiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode($tossSecretKey . ':'),
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log('토스페이먼츠 승인 API 요청 실패: ' . $curlError);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '결제 승인 중 오류가 발생했습니다.']);
    exit;
}

$tossResponse = json_decode($response, true);

// 토스페이먼츠 응답 상세 로깅
error_log('[Payment Confirm] 🔍 토스페이먼츠 응답: httpCode=' . $httpCode . ', response=' . json_encode($tossResponse, JSON_UNESCAPED_UNICODE));

if ($httpCode !== 200 || !isset($tossResponse['status'])) {
    $errorMessage = $tossResponse['message'] ?? '결제 승인에 실패했습니다.';
    $errorCode = $tossResponse['code'] ?? 'UNKNOWN';
    
    error_log('[Payment Confirm] ❌ 토스페이먼츠 결제 승인 실패: code=' . $errorCode . ', message=' . $errorMessage . ' | orderId=' . $orderId . ', paymentKey=' . substr($paymentKey, 0, 20) . '...');
    
    // NOT_FOUND_PAYMENT_SESSION 에러인 경우 특별 처리
    if ($errorCode === 'NOT_FOUND_PAYMENT_SESSION') {
        // DB에는 있지만 토스페이먼츠 세션이 만료된 경우
        // DB 상태를 FAIL로 업데이트
        try {
            require_once __DIR__ . '/../../includes/db.php';
            db()->execute(
                "UPDATE payment_orders SET status = 'FAIL', updated_at = NOW() WHERE order_id = ?",
                [$orderId]
            );
            error_log('[Payment Confirm] ⚠️ 토스페이먼츠 세션 만료로 인해 payment_orders status를 FAIL로 업데이트: orderId=' . $orderId);
        } catch (Exception $e) {
            error_log('[Payment Confirm] payment_orders FAIL 업데이트 실패: ' . $e->getMessage());
        }
        
        // 사용자 친화적인 메시지 반환
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '새로고침/뒤로가기 등으로 결제 진행 정보가 만료되었습니다. 다시 결제해주세요.',
            'code' => $errorCode
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 기타 에러
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'code' => $errorCode
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 결제 성공 - payment_orders 테이블에 status=DONE, payment_key 저장
try {
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/db_setup.php';
    
    // payment_orders 테이블 업데이트
    $updateResult = db()->execute(
        "UPDATE payment_orders SET 
            status = 'DONE',
            payment_key = ?,
            updated_at = NOW()
         WHERE order_id = ?",
        [$paymentKey, $orderId]
    );
    
    if ($updateResult) {
        error_log('[Payment Confirm] ✅ payment_orders 업데이트 완료: orderId=' . $orderId . ', status=DONE, paymentKey=' . substr($paymentKey, 0, 20) . '...');
    } else {
        error_log('[Payment Confirm] ⚠️ payment_orders 업데이트 실패 (영향받은 행 없음): orderId=' . $orderId);
    }
} catch (Exception $e) {
    error_log('[Payment Confirm] payment_orders 업데이트 실패: ' . $e->getMessage() . ' | orderId=' . $orderId);
    // 업데이트 실패해도 결제는 성공했으므로 계속 진행
}

// 결제 정보 반환 (민감한 정보 제외)
echo json_encode([
    'success' => true,
    'status' => $tossResponse['status'],
    'orderId' => $tossResponse['orderId'],
    'orderName' => $tossResponse['orderName'],
    'totalAmount' => $tossResponse['totalAmount'],
    'method' => $tossResponse['method'] ?? '',
    'approvedAt' => $tossResponse['approvedAt'] ?? ''
], JSON_UNESCAPED_UNICODE);

