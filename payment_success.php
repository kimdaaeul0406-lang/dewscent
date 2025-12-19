<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_setup.php';
require_once __DIR__ . '/includes/header.php';

// 디버깅 모드: ?debug=1 파라미터가 있으면 상세 정보 표시
$debugMode = isset($_GET['debug']) && $_GET['debug'] == '1';

// URL 파라미터에서 결제 정보 받기
// 토스페이먼츠는 successUrl로 리다이렉트할 때 paymentKey, orderId, amount를 전달합니다
$paymentKey = $_GET['paymentKey'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$tossAmount = isset($_GET['amount']) ? (int)$_GET['amount'] : 0;

error_log('[Payment Success] 📥 URL 파라미터 수신: orderId=' . $orderId . ', paymentKey=' . ($paymentKey ? substr($paymentKey, 0, 20) . '...' : '없음') . ', amount=' . $tossAmount);
error_log('[Payment Success] 📥 전체 GET 파라미터: ' . json_encode($_GET, JSON_UNESCAPED_UNICODE));
error_log('[Payment Success] 📥 전체 REQUEST 파라미터: ' . json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));
error_log('[Payment Success] 📥 현재 URL: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '알 수 없음'));

// 디버깅 모드: 파라미터가 없으면 상세 정보 표시
if ($debugMode || (empty($paymentKey) || empty($orderId))) {
    if (empty($paymentKey) || empty($orderId)) {
        // 파라미터가 없을 때 디버깅 정보 표시
        ?>
        <!DOCTYPE html>
        <html lang="ko">
        <head>
            <meta charset="UTF-8">
            <title>결제 진행 데이터 확인</title>
            <style>
                body { font-family: 'Noto Sans KR', sans-serif; padding: 2rem; background: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; }
                .error { color: #f44336; background: #ffebee; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
                .info { background: #e3f2fd; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
                .code { background: #f5f5f5; padding: 1rem; border-radius: 4px; font-family: monospace; white-space: pre-wrap; }
                table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
                th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #5f7161; color: white; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>⚠️ 결제 진행 데이터 확인</h1>
                
                <div class="error">
                    <strong>문제:</strong> URL 파라미터가 없습니다.<br>
                    paymentKey 또는 orderId가 전달되지 않았습니다.
                </div>
                
                <div class="info">
                    <h3>받은 URL 파라미터:</h3>
                    <div class="code"><?php echo htmlspecialchars(json_encode($_GET, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></div>
                </div>
                
                <div class="info">
                    <h3>현재 URL:</h3>
                    <div class="code"><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '알 수 없음'); ?></div>
                </div>
                
                <?php
                // DB에서 최근 주문 확인
                try {
                    $recentOrders = db()->fetchAll(
                        "SELECT order_id, order_name, amount, status, created_at 
                         FROM payment_orders 
                         ORDER BY created_at DESC 
                         LIMIT 5"
                    );
                    
                    if ($recentOrders) {
                        echo '<h3>DB에 저장된 최근 주문:</h3>';
                        echo '<table>';
                        echo '<tr><th>주문번호</th><th>주문명</th><th>금액</th><th>상태</th><th>생성시간</th></tr>';
                        foreach ($recentOrders as $order) {
                            echo '<tr>';
                            echo '<td><strong>' . htmlspecialchars($order['order_id']) . '</strong></td>';
                            echo '<td>' . htmlspecialchars($order['order_name']) . '</td>';
                            echo '<td>₩' . number_format($order['amount']) . '</td>';
                            echo '<td>' . htmlspecialchars($order['status']) . '</td>';
                            echo '<td>' . htmlspecialchars($order['created_at']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                        
                        echo '<p><strong>가장 최근 주문:</strong> ' . htmlspecialchars($recentOrders[0]['order_id']) . '</p>';
                        echo '<p>이 orderId가 URL에 포함되어 있는지 확인하세요.</p>';
                    }
                } catch (Exception $e) {
                    echo '<p>DB 조회 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
                
                <div style="margin-top: 2rem;">
                    <a href="payment_debug_simple.php" style="padding: 0.5rem 1rem; background: #5f7161; color: white; text-decoration: none; border-radius: 4px;">진단 도구로 이동</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// DB에서 order_id로 조회하여 amount 복구
ensure_tables_exist();

error_log('[Payment Success] 🔍 DB 조회 시작: orderId=' . $orderId . ', paymentKey=' . substr($paymentKey, 0, 20) . '...');

$orderData = db()->fetchOne(
    "SELECT order_id, order_name, amount, customer_name, customer_email, status, payment_key, order_data 
     FROM payment_orders 
     WHERE order_id = ?",
    [$orderId]
);

if (!$orderData) {
    error_log('[Payment Success] ❌ DB에서 주문 데이터를 찾을 수 없음: orderId=' . $orderId);
    
    // 디버깅: DB에 있는 모든 orderId 확인
    try {
        $allOrderIds = db()->fetchAll("SELECT order_id FROM payment_orders ORDER BY created_at DESC LIMIT 10");
        error_log('[Payment Success] 🔍 DB에 있는 최근 orderId 목록: ' . json_encode(array_column($allOrderIds, 'order_id'), JSON_UNESCAPED_UNICODE));
        error_log('[Payment Success] 🔍 찾는 orderId: ' . $orderId);
        error_log('[Payment Success] 🔍 orderId 비교 (정확히 일치하는지): ' . (in_array($orderId, array_column($allOrderIds, 'order_id')) ? '일치함' : '일치하지 않음'));
    } catch (Exception $e) {
        error_log('[Payment Success] DB 목록 조회 실패: ' . $e->getMessage());
    }
    
    header('Location: /dewscent/payment_fail.php?error=' . urlencode('새로고침/뒤로가기 등으로 결제 진행 정보가 만료되었습니다. 다시 결제해주세요.') . '&orderId=' . urlencode($orderId));
    exit;
}

error_log('[Payment Success] ✅ DB 조회 성공: orderId=' . $orderId . ', amount=' . $orderData['amount'] . ', status=' . $orderData['status']);

// 이미 완료된 결제인 경우 (중복 호출 방지)
if ($orderData['status'] === 'DONE') {
    // 저장된 결과만 보여주기
    $paymentData = [
        'success' => true,
        'orderId' => $orderData['order_id'],
        'orderName' => $orderData['order_name'],
        'totalAmount' => $orderData['amount'],
        'method' => '카드',
        'approvedAt' => $orderData['updated_at'] ?? date('Y-m-d H:i:s')
    ];
} else {
    // DB에서 복구한 amount 사용 (프론트에서 받은 amount는 절대 사용하지 않음)
    $amount = (int)$orderData['amount'];
    
    if ($amount < 1000) {
        header('Location: /dewscent/payment_fail.php?error=' . urlencode('새로고침/뒤로가기 등으로 결제 진행 정보가 만료되었습니다. 다시 결제해주세요.') . '&orderId=' . urlencode($orderId));
    exit;
}

// 결제 승인 API 호출
$confirmUrl = SITE_URL . '/api/payments/confirm.php';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $confirmUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'paymentKey' => $paymentKey,
        'orderId' => $orderId,
            'amount' => $amount // DB에서 복구한 amount 사용
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
        CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
curl_close($ch);
    
    if ($curlError) {
        error_log('결제 승인 API 호출 오류: ' . $curlError);
        header('Location: /dewscent/payment_fail.php?error=' . urlencode('결제 승인 서버와 통신 중 오류가 발생했습니다.') . '&orderId=' . urlencode($orderId));
        exit;
    }

$paymentData = json_decode($response, true);

if ($httpCode !== 200 || !isset($paymentData['success']) || !$paymentData['success']) {
    $errorMessage = $paymentData['message'] ?? '결제 승인에 실패했습니다.';
        error_log('결제 승인 실패: orderId=' . $orderId . ', message=' . $errorMessage);
        header('Location: /dewscent/payment_fail.php?error=' . urlencode($errorMessage) . '&orderId=' . urlencode($orderId));
    exit;
}
}

// 결제 승인 성공 - confirm.php에서 이미 status=DONE으로 업데이트됨
// 주문 정보를 URL 파라미터로 전달하여 index.php에서 저장하도록 함
// (sessionStorage는 서버에서 접근할 수 없으므로)

// 주문 완료 페이지로 리다이렉트 (주문 정보는 index.php에서 sessionStorage를 통해 저장)
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
    $basePath = '';
} else {
    $basePath = $scriptDir;
}
$orderRedirectUrl = ($basePath ? $basePath : '') . '/index.php?order=' . urlencode($orderId) . '&paymentKey=' . urlencode($paymentKey) . '&amount=' . urlencode($tossAmount);

// 결제 승인이 완료되었으므로 주문 완료 페이지로 리다이렉트
header('Location: ' . $orderRedirectUrl);
exit;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 완료 - DewScent</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            background: var(--sage-lighter);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--sage);
        }
        .success-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 2rem;
            color: var(--sage);
            margin-bottom: 1rem;
        }
        .success-message {
            color: var(--mid);
            margin-bottom: 2rem;
        }
        .payment-info {
            background: var(--sage-bg);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .info-label {
            color: var(--light);
            font-size: 0.9rem;
        }
        .info-value {
            color: var(--dark);
            font-weight: 500;
        }
        .info-value.amount {
            color: var(--sage);
            font-size: 1.2rem;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .form-btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .form-btn.primary {
            background: var(--sage);
            color: var(--white);
        }
        .form-btn.primary:hover {
            background: var(--sage-hover);
        }
        .form-btn.secondary {
            background: var(--white);
            color: var(--sage);
            border: 2px solid var(--sage);
        }
        .form-btn.secondary:hover {
            background: var(--sage-bg);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1 class="success-title">결제가 완료되었습니다</h1>
        <p class="success-message">주문이 정상적으로 처리되었습니다. 감사합니다.</p>

        <div class="payment-info">
            <div class="info-row">
                <span class="info-label">주문번호</span>
                <span class="info-value"><?php echo htmlspecialchars($paymentData['orderId'] ?? $orderId, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">주문명</span>
                <span class="info-value"><?php echo htmlspecialchars($paymentData['orderName'] ?? '주문', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">결제 수단</span>
                <span class="info-value"><?php echo htmlspecialchars($paymentData['method'] ?? '카드', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">결제 금액</span>
                <span class="info-value amount">₩<?php echo number_format($paymentData['totalAmount'] ?? $orderData['amount'] ?? 0); ?></span>
            </div>
            <?php if (isset($paymentData['approvedAt'])): ?>
            <div class="info-row">
                <span class="info-label">결제 일시</span>
                <span class="info-value"><?php echo htmlspecialchars($paymentData['approvedAt'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="<?php echo htmlspecialchars($orderRedirectUrl, ENT_QUOTES, 'UTF-8'); ?>" class="form-btn primary">주문 내역 보기</a>
            <a href="<?php echo htmlspecialchars(($basePath ? $basePath : '') . '/index.php', ENT_QUOTES, 'UTF-8'); ?>" class="form-btn secondary">홈으로 이동</a>
        </div>
        
        <script>
        // payment_orders에서 주문 정보를 복구하여 orders 테이블에 저장
        // 쿠폰 사용 처리는 api/orders.php에서 함께 처리됨 (중복 방지)
        (function() {
            try {
                const orderId = '<?php echo htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8'); ?>';
                
                // 서버에서 주문 정보 복구 및 저장 요청
                fetch('/dewscent/api/orders.php?action=saveFromPayment&orderId=' + encodeURIComponent(orderId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.ok) {
                        console.log('[Payment Success] ✅ 주문이 DB에 저장되었습니다:', result);
                        // sessionStorage에서도 제거 (혹시 남아있을 수 있음)
                        sessionStorage.removeItem('pending_order');
                        
                        // 3초 후 주문 페이지로 자동 리다이렉트
                        setTimeout(() => {
                            const basePath = '<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>';
                            const redirectUrl = (basePath || '') + '/index.php?order=' + encodeURIComponent(orderId);
                            window.location.href = redirectUrl;
                        }, 3000);
                    } else if (result.requiresSessionStorage) {
                        // order_data가 없어서 sessionStorage 필요
                        console.log('[Payment Success] order_data가 없어 sessionStorage 사용');
                        const pendingOrderData = sessionStorage.getItem('pending_order');
                        if (pendingOrderData) {
                            const data = JSON.parse(pendingOrderData);
                            const order = data.order;
                            
                            if (order) {
                                // 주문 상품 정보를 서버에 전송
                                fetch('/dewscent/api/orders.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    credentials: 'include',
                                    body: JSON.stringify({
                                        orderNumber: order.id,
                                        items: order.items,
                                        customer: order.customer,
                                        payment: order.payment,
                                        total: order.payment.total
                                    })
                                })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.ok) {
                                        console.log('[Payment Success] ✅ 주문이 DB에 저장되었습니다:', result);
                                        sessionStorage.removeItem('pending_order');
                                    } else {
                                        console.error('[Payment Success] 주문 저장 실패:', result.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('[Payment Success] 주문 저장 오류:', error);
                                });
                            }
                        } else {
                            console.error('[Payment Success] sessionStorage에도 주문 정보가 없습니다.');
                        }
                    } else {
                        console.error('[Payment Success] 주문 저장 실패:', result.message);
                        // 실패해도 사용자에게는 성공 페이지 표시 (이미 결제는 완료됨)
                    }
                })
                .catch(error => {
                    console.error('[Payment Success] 주문 저장 오류:', error);
                    // 오류가 발생해도 사용자에게는 성공 페이지 표시
                });
            } catch (error) {
                console.error('[Payment Success] 주문 저장 처리 오류:', error);
            }
        })();
        </script>
    </div>
</body>
</html>

