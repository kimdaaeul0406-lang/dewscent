<?php
/**
 * 토스페이먼츠 결제 준비 API
 * 
 * 클라이언트에서 받은 주문 정보를 검증하고,
 * 토스페이먼츠 결제 준비 API를 호출하여 결제창에 필요한 정보를 생성합니다.
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

// 환경 변수에서 키 읽기 (getenv() 우선, 없으면 $_ENV에서 읽기)
$tossClientKey = getenv('TOSS_CLIENT_KEY') ?: ($_ENV['TOSS_CLIENT_KEY'] ?? '');
$tossSecretKey = getenv('TOSS_SECRET_KEY') ?: ($_ENV['TOSS_SECRET_KEY'] ?? '');

if (empty($tossClientKey) || empty($tossSecretKey)) {
    // 키 검증 (마스킹 처리)
    $maskedClientKey = strlen($tossClientKey) > 6 
        ? substr($tossClientKey, 0, 6) . str_repeat('*', strlen($tossClientKey) - 6)
        : str_repeat('*', strlen($tossClientKey));
    $maskedSecretKey = strlen($tossSecretKey) > 6 
        ? substr($tossSecretKey, 0, 6) . str_repeat('*', strlen($tossSecretKey) - 6)
        : str_repeat('*', strlen($tossSecretKey));
    
    error_log('[Payment Ready] TOSS_CLIENT_KEY: ' . $maskedClientKey . ', TOSS_SECRET_KEY: ' . $maskedSecretKey);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '결제 설정이 올바르지 않습니다. .env 파일을 확인해주세요.']);
    exit;
}

// 키 검증 (마스킹 처리) - 디버그 모드에서만
if (defined('APP_DEBUG') && APP_DEBUG) {
    $maskedClientKey = strlen($tossClientKey) > 6 
        ? substr($tossClientKey, 0, 6) . str_repeat('*', strlen($tossClientKey) - 6)
        : str_repeat('*', strlen($tossClientKey));
    $maskedSecretKey = strlen($tossSecretKey) > 6 
        ? substr($tossSecretKey, 0, 6) . str_repeat('*', strlen($tossSecretKey) - 6)
        : str_repeat('*', strlen($tossSecretKey));
    error_log('[Payment Ready] Keys loaded - CLIENT: ' . $maskedClientKey . ', SECRET: ' . $maskedSecretKey);
}

// JSON 요청 본문 파싱
$rawInput = file_get_contents('php://input');
error_log('[Payment Ready] 📥 요청 본문: ' . $rawInput);

$input = json_decode($rawInput, true);

if (!$input) {
    error_log('[Payment Ready] ❌ JSON 파싱 실패');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

error_log('[Payment Ready] 📥 파싱된 입력값: ' . json_encode($input, JSON_UNESCAPED_UNICODE));

// 입력값 검증
$orderName = trim($input['orderName'] ?? '');
$amount = isset($input['amount']) ? (int)$input['amount'] : 0;
$customerName = trim($input['customerName'] ?? '');
$customerEmail = trim($input['customerEmail'] ?? '');
$paymentMethod = $input['paymentMethod'] ?? 'card';

error_log('[Payment Ready] 🔍 검증 전 값: orderName=' . $orderName . ', amount=' . $amount . ', customerName=' . $customerName . ', customerEmail=' . $customerEmail);

// 서버 측 검증 (프론트엔드 값 신뢰 금지)
if (empty($orderName) || $amount < 1000 || empty($customerName) || empty($customerEmail)) {
    error_log('[Payment Ready] ❌ 입력값 검증 실패: orderName=' . ($orderName ? '있음' : '없음') . ', amount=' . $amount . ', customerName=' . ($customerName ? '있음' : '없음') . ', customerEmail=' . ($customerEmail ? '있음' : '없음'));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '주문 정보가 올바르지 않습니다.']);
    exit;
}

// 이메일 형식 검증
if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    error_log('[Payment Ready] ❌ 이메일 형식 검증 실패: ' . $customerEmail);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '올바른 이메일 주소를 입력해주세요.']);
    exit;
}

error_log('[Payment Ready] ✅ 입력값 검증 통과');

// 주문번호 생성 (고유성 보장)
$orderId = 'ORDER_' . date('YmdHis') . '_' . uniqid();

// 성공/실패 URL 생성
$baseUrl = SITE_URL;
$successUrl = $baseUrl . '/payment_success.php';
$failUrl = $baseUrl . '/payment_fail.php';

// 토스페이먼츠 결제 준비 API 호출
$tossApiUrl = 'https://api.tosspayments.com/v1/payments/ready';

$requestData = [
    'amount' => $amount,
    'orderId' => $orderId,
    'orderName' => $orderName,
    'customerName' => $customerName,
    'customerEmail' => $customerEmail,
    'successUrl' => $successUrl,
    'failUrl' => $failUrl
];

// requestPayment 방식을 사용하므로 method 파라미터는 제거
// (requestPayment에서 직접 결제 수단을 지정하므로)

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
    error_log('토스페이먼츠 API 요청 실패: ' . $curlError);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '결제 서버와 통신 중 오류가 발생했습니다.']);
    exit;
}

$tossResponse = json_decode($response, true);

// 토스페이먼츠 응답 상세 로깅
error_log('[Payment Ready] 🔍 토스페이먼츠 응답: httpCode=' . $httpCode . ', response=' . json_encode($tossResponse, JSON_UNESCAPED_UNICODE));

// requestPayment 방식을 사용하므로 nextRedirectPcUrl은 필수가 아님
if ($httpCode !== 200 || !isset($tossResponse['paymentKey'])) {
    $errorMessage = $tossResponse['message'] ?? '결제 준비에 실패했습니다.';
    $errorCode = $tossResponse['code'] ?? 'UNKNOWN';
    
    error_log('[Payment Ready] ❌ 토스페이먼츠 결제 준비 실패: code=' . $errorCode . ', message=' . $errorMessage);
    error_log('[Payment Ready] ❌ 요청 데이터: ' . json_encode($requestData, JSON_UNESCAPED_UNICODE));
    
    // 사용자 친화적인 에러 메시지
    $userMessage = '결제 준비에 실패했습니다.';
    if ($errorCode === 'NOT_FOUND_PAYMENT_SESSION') {
        $userMessage = '결제 세션이 만료되었습니다. 다시 시도해주세요.';
    } elseif (!empty($errorMessage)) {
        $userMessage = $errorMessage;
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $userMessage, 'code' => $errorCode], JSON_UNESCAPED_UNICODE);
    exit;
}

// DB에 임시 주문 레코드 저장 (payment_orders 테이블)
// 중요: 세션 기반이 아닌 DB 기반으로 결제 진행 데이터를 저장
try {
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/db_setup.php';
    ensure_tables_exist();
    
    // payment_orders 테이블에 임시 주문 저장
    $existing = db()->fetchOne(
        "SELECT order_id FROM payment_orders WHERE order_id = ?",
        [$orderId]
    );
    
    if ($existing) {
        // 기존 데이터 업데이트
        db()->execute(
            "UPDATE payment_orders SET 
                order_name = ?,
                amount = ?,
                customer_name = ?,
                customer_email = ?,
                status = 'READY',
                payment_key = NULL,
                updated_at = NOW()
             WHERE order_id = ?",
            [
                $orderName,
                $amount,
                $customerName,
                $customerEmail,
                $orderId
            ]
        );
        error_log('[Payment Ready] payment_orders 업데이트: orderId=' . $orderId);
    } else {
        // 새 레코드 삽입
        db()->insert(
            "INSERT INTO payment_orders 
                (order_id, order_name, amount, customer_name, customer_email, status, payment_key) 
             VALUES (?, ?, ?, ?, ?, 'READY', NULL)",
            [
                $orderId,
                $orderName,
                $amount,
                $customerName,
                $customerEmail
            ]
        );
        error_log('[Payment Ready] payment_orders 저장: orderId=' . $orderId);
    }
    
    // 저장 확인
    $verify = db()->fetchOne(
        "SELECT order_id, amount, status FROM payment_orders WHERE order_id = ?",
        [$orderId]
    );
    
    if (!$verify) {
        throw new Exception('payment_orders 저장 후 검증 실패');
    }
    
    error_log('[Payment Ready] ✅ payment_orders 저장 성공: orderId=' . $orderId . ', amount=' . $amount . ', status=' . $verify['status']);
    
} catch (Exception $e) {
    // DB 저장 실패는 심각한 문제
    error_log('[Payment Ready] ❌ payment_orders 저장 실패: ' . $e->getMessage() . ' | orderId=' . $orderId . ' | Stack: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '결제 준비 중 오류가 발생했습니다. 다시 시도해주세요.']);
    exit;
}

// 클라이언트에 반환할 정보
// 중요: orderId는 DB에 저장된 것과 동일한 값이어야 함
error_log('[Payment Ready] ✅ 응답 반환: orderId=' . $orderId . ', paymentKey=' . substr($tossResponse['paymentKey'], 0, 20) . '...');

echo json_encode([
    'success' => true,
    'clientKey' => $tossClientKey, // 프론트엔드에서 토스페이먼츠 결제창 호출용
    'paymentKey' => $tossResponse['paymentKey'],
    'orderId' => $orderId, // DB에 저장된 orderId와 동일
    'amount' => $amount,
    'orderName' => $orderName,
    'customerName' => $customerName,
    'customerEmail' => $customerEmail,
    'successUrl' => $successUrl, // 토스페이먼츠가 자동으로 paymentKey, orderId, amount를 URL 파라미터로 추가함
    'failUrl' => $failUrl,
    'nextRedirectPcUrl' => $tossResponse['nextRedirectPcUrl'] ?? '',
    'nextRedirectMobileUrl' => $tossResponse['nextRedirectMobileUrl'] ?? ''
], JSON_UNESCAPED_UNICODE);

