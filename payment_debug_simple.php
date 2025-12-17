<?php
/**
 * 결제 문제 간단 진단 도구
 * 
 * 사용법: 결제 시도 후 이 페이지를 열어서 확인하세요
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_setup.php';

ensure_tables_exist();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 문제 진단</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans KR', sans-serif;
            padding: 2rem;
            background: #f0f0f0;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #5f7161;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #5f7161;
        }
        .step {
            background: #f9f9f9;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            border-left: 4px solid #5f7161;
        }
        .step h2 {
            color: #5f7161;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .ok { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: white;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #5f7161;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #5f7161;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 0.5rem 0.5rem 0.5rem 0;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #4a5a4b;
        }
        .code {
            background: #2d2d2d;
            color: #d4d4d4;
            padding: 1rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 0.5rem 0;
            white-space: pre-wrap;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 결제 문제 진단 도구</h1>
        
        <div class="info-box">
            <strong>사용 방법:</strong><br>
            1. 결제를 시도하세요 (카드결제하기 버튼 클릭)<br>
            2. 결제창이 열리면 이 페이지를 새로고침하세요 (F5)<br>
            3. 아래 결과를 확인하세요
        </div>

        <?php
        // Step 1: 최근 payment_orders 데이터 확인
        echo '<div class="step">';
        echo '<h2>📊 Step 1: DB에 저장된 최근 주문 확인</h2>';
        
        try {
            $recentOrders = db()->fetchAll(
                "SELECT order_id, order_name, amount, status, created_at, updated_at 
                 FROM payment_orders 
                 ORDER BY created_at DESC 
                 LIMIT 5"
            );
            
            if ($recentOrders) {
                echo '<p class="ok">✅ payment_orders 테이블에 데이터가 있습니다.</p>';
                echo '<table>';
                echo '<tr><th>주문번호</th><th>주문명</th><th>금액</th><th>상태</th><th>생성시간</th></tr>';
                foreach ($recentOrders as $order) {
                    $statusClass = '';
                    if ($order['status'] === 'DONE') $statusClass = 'ok';
                    elseif ($order['status'] === 'FAIL') $statusClass = 'error';
                    elseif ($order['status'] === 'READY') $statusClass = 'warning';
                    
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($order['order_id']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($order['order_name']) . '</td>';
                    echo '<td>₩' . number_format($order['amount']) . '</td>';
                    echo '<td class="' . $statusClass . '">' . htmlspecialchars($order['status']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['created_at']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                // 가장 최근 주문
                $latestOrder = $recentOrders[0];
                echo '<p><strong>가장 최근 주문:</strong> ' . htmlspecialchars($latestOrder['order_id']) . ' (상태: ' . htmlspecialchars($latestOrder['status']) . ')</p>';
            } else {
                echo '<p class="error">❌ payment_orders 테이블에 데이터가 없습니다.</p>';
                echo '<p>결제를 시도했는지 확인하세요.</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        echo '</div>';
        
        // Step 2: payment_success.php 시뮬레이션
        echo '<div class="step">';
        echo '<h2>🧪 Step 2: payment_success.php 동작 시뮬레이션</h2>';
        
        if (!empty($recentOrders)) {
            $testOrderId = $recentOrders[0]['order_id'];
            echo '<p>가장 최근 주문으로 테스트: <strong>' . htmlspecialchars($testOrderId) . '</strong></p>';
            
            try {
                // payment_success.php에서 하는 것처럼 DB 조회
                $orderData = db()->fetchOne(
                    "SELECT order_id, order_name, amount, customer_name, customer_email, status, payment_key 
                     FROM payment_orders 
                     WHERE order_id = ?",
                    [$testOrderId]
                );
                
                if ($orderData) {
                    echo '<p class="ok">✅ DB에서 주문 데이터를 찾았습니다!</p>';
                    echo '<div class="code">';
                    echo 'order_id: ' . htmlspecialchars($orderData['order_id']) . "\n";
                    echo 'amount: ₩' . number_format($orderData['amount']) . "\n";
                    echo 'status: ' . htmlspecialchars($orderData['status']) . "\n";
                    echo 'order_name: ' . htmlspecialchars($orderData['order_name']) . "\n";
                    echo '</div>';
                    
                    if ($orderData['status'] === 'READY') {
                        echo '<p class="warning">⚠️ 상태가 READY입니다. confirm API를 호출할 수 있습니다.</p>';
                    } elseif ($orderData['status'] === 'DONE') {
                        echo '<p class="ok">✅ 이미 완료된 결제입니다. 중복 호출 방지가 작동합니다.</p>';
                    } elseif ($orderData['status'] === 'FAIL') {
                        echo '<p class="error">❌ 실패한 결제입니다.</p>';
                    }
                } else {
                    echo '<p class="error">❌ DB에서 주문 데이터를 찾을 수 없습니다.</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p class="warning">⚠️ 테스트할 주문이 없습니다. 먼저 결제를 시도하세요.</p>';
        }
        
        echo '</div>';
        
        // Step 3: 실제 payment_success.php URL 확인
        echo '<div class="step">';
        echo '<h2>🔗 Step 3: payment_success.php URL 확인</h2>';
        
        if (!empty($recentOrders)) {
            $testOrderId = $recentOrders[0]['order_id'];
            $testUrl = SITE_URL . '/payment_success.php?orderId=' . urlencode($testOrderId) . '&paymentKey=TEST_KEY&amount=' . $recentOrders[0]['amount'];
            
            echo '<p><strong>테스트 URL:</strong></p>';
            echo '<div class="code">' . htmlspecialchars($testUrl) . '</div>';
            echo '<p><a href="' . htmlspecialchars($testUrl) . '" class="btn" target="_blank">이 URL로 테스트하기</a></p>';
            echo '<p class="warning">⚠️ 실제 paymentKey가 없으므로 에러가 발생할 수 있습니다. 하지만 DB 조회는 확인할 수 있습니다.</p>';
        }
        
        echo '</div>';
        
        // Step 4: 문제 해결 가이드
        echo '<div class="step">';
        echo '<h2>💡 문제 해결 가이드</h2>';
        
        echo '<h3>만약 "결제 진행 데이터가 존재하지 않습니다" 오류가 발생한다면:</h3>';
        echo '<ol style="margin-left: 1.5rem; margin-top: 1rem;">';
        echo '<li><strong>Step 1에서 데이터가 보이나요?</strong><br>';
        echo '   → 보이면: DB 저장은 정상입니다. 문제는 다른 곳에 있습니다.<br>';
        echo '   → 안 보이면: ready.php에서 DB 저장이 실패했습니다. 로그를 확인하세요.</li>';
        echo '<li><strong>Step 2에서 데이터를 찾을 수 있나요?</strong><br>';
        echo '   → 찾을 수 있으면: DB 조회는 정상입니다.<br>';
        echo '   → 찾을 수 없으면: orderId가 일치하지 않을 수 있습니다.</li>';
        echo '<li><strong>실제 결제 시도 시:</strong><br>';
        echo '   → 브라우저 주소창을 확인하세요. payment_success.php?orderId=xxx&paymentKey=xxx 형태인지 확인<br>';
        echo '   → orderId가 Step 1에서 본 것과 같은지 확인</li>';
        echo '</ol>';
        
        echo '</div>';
        ?>
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="?" class="btn">새로고침</a>
            <a href="debug_payment.php" class="btn">상세 디버깅 도구</a>
        </div>
    </div>
</body>
</html>
