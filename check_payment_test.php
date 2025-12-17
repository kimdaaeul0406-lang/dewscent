<?php
/**
 * 결제 테스트 환경 확인 도구
 * 
 * 테스트 결제가 실패하는 원인을 확인합니다.
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_setup.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 테스트 환경 확인</title>
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
        }
        .step {
            background: #f9f9f9;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            border-left: 4px solid #5f7161;
        }
        .ok { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .info { color: #2196F3; font-weight: bold; }
        .code {
            background: #2d2d2d;
            color: #d4d4d4;
            padding: 1rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 0.5rem 0;
            white-space: pre-wrap;
            font-size: 0.9rem;
        }
        .test-card {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border-left: 4px solid #2196F3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 결제 테스트 환경 확인</h1>
        
        <?php
        // Step 1: 환경 변수 확인
        echo '<div class="step">';
        echo '<h2>🔑 Step 1: 토스페이먼츠 테스트 키 확인</h2>';
        
        $tossClientKey = getenv('TOSS_CLIENT_KEY') ?: ($_ENV['TOSS_CLIENT_KEY'] ?? '');
        $tossSecretKey = getenv('TOSS_SECRET_KEY') ?: ($_ENV['TOSS_SECRET_KEY'] ?? '');
        
        if (empty($tossClientKey) || empty($tossSecretKey)) {
            echo '<p class="error">❌ 토스페이먼츠 키가 설정되지 않았습니다.</p>';
            echo '<p>.env 파일에 다음을 추가하세요:</p>';
            echo '<div class="code">TOSS_CLIENT_KEY=test_ck_Z61JOxRQVENnO07bGq72rW0X9bAq
TOSS_SECRET_KEY=test_sk_DLJOpm5QrlLXNxLROKpNrPNdxbWn</div>';
        } else {
            $isTestKey = strpos($tossClientKey, 'test_') === 0;
            
            if ($isTestKey) {
                echo '<p class="ok">✅ 테스트 키가 설정되어 있습니다.</p>';
                $maskedClientKey = strlen($tossClientKey) > 10 
                    ? substr($tossClientKey, 0, 10) . '...' 
                    : $tossClientKey;
                $maskedSecretKey = strlen($tossSecretKey) > 10 
                    ? substr($tossSecretKey, 0, 10) . '...' 
                    : $tossSecretKey;
                echo '<div class="code">TOSS_CLIENT_KEY: ' . htmlspecialchars($maskedClientKey) . "\n";
                echo 'TOSS_SECRET_KEY: ' . htmlspecialchars($maskedSecretKey) . '</div>';
            } else {
                echo '<p class="warning">⚠️ 실제 운영 키가 설정되어 있습니다. 테스트 환경에서는 테스트 키를 사용해야 합니다.</p>';
            }
        }
        
        echo '</div>';
        
        // Step 2: 테스트 카드 정보
        echo '<div class="step">';
        echo '<h2>💳 Step 2: 테스트 카드 정보</h2>';
        echo '<div class="test-card">';
        echo '<p class="info">테스트 결제 시 다음 카드 정보를 사용하세요:</p>';
        echo '<table>';
        echo '<tr><th>항목</th><th>값</th></tr>';
        echo '<tr><td>카드번호</td><td><strong>1234-5678-9012-3456</strong></td></tr>';
        echo '<tr><td>유효기간</td><td><strong>12/34</strong> (미래 날짜)</td></tr>';
        echo '<tr><td>CVC</td><td><strong>123</strong></td></tr>';
        echo '<tr><td>카드 비밀번호</td><td><strong>12</strong> (앞 2자리)</td></tr>';
        echo '</table>';
        echo '<p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">💡 테스트 환경에서는 실제 결제가 발생하지 않습니다.</p>';
        echo '</div>';
        echo '</div>';
        
        // Step 3: 최근 결제 실패 내역
        echo '<div class="step">';
        echo '<h2>📊 Step 3: 최근 결제 내역</h2>';
        
        try {
            ensure_tables_exist();
            
            $recentOrders = db()->fetchAll(
                "SELECT order_id, order_name, amount, status, created_at, updated_at 
                 FROM payment_orders 
                 ORDER BY created_at DESC 
                 LIMIT 10"
            );
            
            if ($recentOrders) {
                echo '<table>';
                echo '<tr><th>주문번호</th><th>주문명</th><th>금액</th><th>상태</th><th>생성시간</th></tr>';
                foreach ($recentOrders as $order) {
                    $statusClass = '';
                    if ($order['status'] === 'DONE') $statusClass = 'ok';
                    elseif ($order['status'] === 'FAIL') $statusClass = 'error';
                    elseif ($order['status'] === 'READY') $statusClass = 'warning';
                    
                    echo '<tr>';
                    echo '<td><code style="font-size: 0.85rem;">' . htmlspecialchars(substr($order['order_id'], 0, 30)) . '...</code></td>';
                    echo '<td>' . htmlspecialchars($order['order_name']) . '</td>';
                    echo '<td>₩' . number_format($order['amount']) . '</td>';
                    echo '<td class="' . $statusClass . '">' . htmlspecialchars($order['status']) . '</td>';
                    echo '<td style="font-size: 0.85rem;">' . htmlspecialchars($order['created_at']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p>아직 결제 시도가 없습니다.</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        echo '</div>';
        
        // Step 4: 문제 해결 가이드
        echo '<div class="step">';
        echo '<h2>💡 문제 해결 가이드</h2>';
        echo '<h3>결제 실패가 발생하는 경우:</h3>';
        echo '<ol style="margin-left: 1.5rem; margin-top: 1rem;">';
        echo '<li><strong>테스트 키 확인:</strong> .env 파일에 test_로 시작하는 키가 있는지 확인</li>';
        echo '<li><strong>테스트 카드 사용:</strong> 위의 테스트 카드 정보를 정확히 입력</li>';
        echo '<li><strong>브라우저 콘솔 확인:</strong> F12를 눌러서 에러 메시지 확인</li>';
        echo '<li><strong>최소 금액:</strong> 결제 금액이 1,000원 이상인지 확인</li>';
        echo '<li><strong>네트워크 확인:</strong> 토스페이먼츠 API 서버와 통신이 가능한지 확인</li>';
        echo '</ol>';
        echo '</div>';
        ?>
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="?" class="btn" style="display: inline-block; padding: 0.75rem 1.5rem; background: #5f7161; color: white; text-decoration: none; border-radius: 6px;">새로고침</a>
            <a href="test_payment_simple.php" class="btn" style="display: inline-block; padding: 0.75rem 1.5rem; background: #2196F3; color: white; text-decoration: none; border-radius: 6px; margin-left: 0.5rem;">결제 시스템 테스트</a>
        </div>
    </div>
</body>
</html>
