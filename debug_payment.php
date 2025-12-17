<?php
/**
 * 결제 시스템 디버깅 도구
 * 
 * 사용법:
 * - 브라우저에서 /dewscent/debug_payment.php?orderId=ORDER_xxx 접속
 * - 또는 /dewscent/debug_payment.php 로 전체 목록 확인
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
    <title>결제 시스템 디버깅</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            padding: 2rem;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #5f7161;
            padding-bottom: 0.5rem;
        }
        .section {
            margin: 2rem 0;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .section h2 {
            color: #5f7161;
            margin-top: 0;
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
            font-weight: 600;
        }
        tr:hover {
            background: #f0f0f0;
        }
        .status-ready {
            color: #2196F3;
            font-weight: bold;
        }
        .status-done {
            color: #4CAF50;
            font-weight: bold;
        }
        .status-fail {
            color: #f44336;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            background: #ffebee;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .success {
            color: #4CAF50;
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .form-group {
            margin: 1rem 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            max-width: 400px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 0.5rem 1rem;
            background: #5f7161;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #4a5a4b;
        }
        .code {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 결제 시스템 디버깅 도구</h1>
        
        <div class="section">
            <h2>주문번호로 조회</h2>
            <form method="GET">
                <div class="form-group">
                    <label>주문번호 (orderId):</label>
                    <input type="text" name="orderId" value="<?php echo htmlspecialchars($_GET['orderId'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="ORDER_20241217_xxx">
                </div>
                <button type="submit" class="btn">조회</button>
            </form>
        </div>

        <?php
        $orderId = $_GET['orderId'] ?? '';
        
        if ($orderId) {
            echo '<div class="section">';
            echo '<h2>📋 주문 정보: ' . htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') . '</h2>';
            
            // payment_orders 테이블 조회
            $orderData = db()->fetchOne(
                "SELECT * FROM payment_orders WHERE order_id = ?",
                [$orderId]
            );
            
            if ($orderData) {
                echo '<div class="success">✅ payment_orders 테이블에서 데이터를 찾았습니다.</div>';
                echo '<table>';
                echo '<tr><th>필드</th><th>값</th></tr>';
                foreach ($orderData as $key => $value) {
                    $displayValue = $value;
                    if ($key === 'status') {
                        $class = 'status-' . strtolower($value);
                        $displayValue = '<span class="' . $class . '">' . htmlspecialchars($value) . '</span>';
                    } else {
                        $displayValue = htmlspecialchars($value ?? '(NULL)');
                    }
                    echo '<tr><td><strong>' . htmlspecialchars($key) . '</strong></td><td>' . $displayValue . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="error">❌ payment_orders 테이블에서 데이터를 찾을 수 없습니다.</div>';
            }
            
            // payment_sessions 테이블 확인 (기존 호환성, 선택적)
            try {
                $tables = db()->fetchAll("SHOW TABLES LIKE 'payment_sessions'");
                if (!empty($tables)) {
                    $sessionData = db()->fetchOne(
                        "SELECT * FROM payment_sessions WHERE order_id = ?",
                        [$orderId]
                    );
                    
                    if ($sessionData) {
                        echo '<h3>payment_sessions 테이블 (기존 호환성)</h3>';
                        echo '<table>';
                        echo '<tr><th>필드</th><th>값</th></tr>';
                        foreach ($sessionData as $key => $value) {
                            echo '<tr><td><strong>' . htmlspecialchars($key) . '</strong></td><td>' . htmlspecialchars($value ?? '(NULL)') . '</td></tr>';
                        }
                        echo '</table>';
                    }
                }
            } catch (Exception $e) {
                // payment_sessions 테이블이 없어도 무시 (payment_orders를 사용하므로)
            }
            
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h2>📊 최근 결제 주문 목록 (최근 20개)</h2>
            <?php
            // 테이블 존재 확인 및 생성
            try {
                $tables = db()->fetchAll("SHOW TABLES LIKE 'payment_orders'");
                if (empty($tables)) {
                    echo '<div class="error">⚠️ payment_orders 테이블이 없습니다. 생성 중...</div>';
                    ensure_tables_exist();
                    echo '<div class="success">✅ payment_orders 테이블을 생성했습니다. 페이지를 새로고침하세요.</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ 테이블 확인/생성 중 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            try {
                $recentOrders = db()->fetchAll(
                    "SELECT * FROM payment_orders 
                     ORDER BY created_at DESC 
                     LIMIT 20"
                );
            
            if ($recentOrders) {
                echo '<table>';
                echo '<tr>';
                echo '<th>주문번호</th>';
                echo '<th>주문명</th>';
                echo '<th>금액</th>';
                echo '<th>상태</th>';
                echo '<th>생성일시</th>';
                echo '<th>수정일시</th>';
                echo '<th>액션</th>';
                echo '</tr>';
                
                foreach ($recentOrders as $order) {
                    $statusClass = 'status-' . strtolower($order['status']);
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($order['order_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['order_name']) . '</td>';
                    echo '<td>₩' . number_format($order['amount']) . '</td>';
                    echo '<td><span class="' . $statusClass . '">' . htmlspecialchars($order['status']) . '</span></td>';
                    echo '<td>' . htmlspecialchars($order['created_at']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['updated_at']) . '</td>';
                    echo '<td><a href="?orderId=' . urlencode($order['order_id']) . '" class="btn">상세보기</a></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            } else {
                echo '<div class="error">❌ payment_orders 테이블에 데이터가 없습니다.</div>';
            }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    echo '<div class="error">❌ payment_orders 테이블이 존재하지 않습니다. 위의 "시스템 상태 확인" 섹션에서 테이블을 생성하세요.</div>';
                } else {
                    echo '<div class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            ?>
        </div>
        
        <div class="section">
            <h2>🔧 시스템 상태 확인</h2>
            <?php
            // 테이블 존재 확인
            try {
                $tables = db()->fetchAll("SHOW TABLES LIKE 'payment_orders'");
                if ($tables) {
                    echo '<div class="success">✅ payment_orders 테이블이 존재합니다.</div>';
                    
                    // 테이블 구조 확인
                    $columns = db()->fetchAll("SHOW COLUMNS FROM payment_orders");
                    echo '<h3>테이블 구조:</h3>';
                    echo '<table>';
                    echo '<tr><th>컬럼명</th><th>타입</th><th>NULL</th><th>기본값</th></tr>';
                    foreach ($columns as $col) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                        echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                        echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                        echo '<td>' . htmlspecialchars($col['Default'] ?? '(NULL)') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="error">❌ payment_orders 테이블이 존재하지 않습니다.</div>';
                    echo '<p>테이블을 생성하려면 아래 버튼을 클릭하세요:</p>';
                    echo '<form method="POST">';
                    echo '<input type="hidden" name="create_table" value="1">';
                    echo '<button type="submit" class="btn">테이블 생성</button>';
                    echo '</form>';
                    
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_table'])) {
                        try {
                            ensure_tables_exist();
                            echo '<div class="success">✅ payment_orders 테이블을 생성했습니다. 페이지를 새로고침하세요.</div>';
                        } catch (Exception $e) {
                            echo '<div class="error">❌ 테이블 생성 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                    }
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ 테이블 확인 중 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<p>수동으로 테이블을 생성하려면:</p>';
                echo '<div class="code">';
                echo "CREATE TABLE IF NOT EXISTS payment_orders (\n";
                echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
                echo "    order_id VARCHAR(100) NOT NULL UNIQUE COMMENT '주문번호 (PK)',\n";
                echo "    order_name VARCHAR(255) NOT NULL COMMENT '주문명',\n";
                echo "    amount INT NOT NULL COMMENT '결제 금액',\n";
                echo "    customer_name VARCHAR(100) NOT NULL COMMENT '구매자 이름',\n";
                echo "    customer_email VARCHAR(255) NOT NULL COMMENT '구매자 이메일',\n";
                echo "    status VARCHAR(20) DEFAULT 'READY' COMMENT 'READY, DONE, FAIL',\n";
                echo "    payment_key VARCHAR(255) DEFAULT NULL COMMENT '토스페이먼츠 paymentKey',\n";
                echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',\n";
                echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',\n";
                echo "    INDEX idx_order_id (order_id),\n";
                echo "    INDEX idx_status (status),\n";
                echo "    INDEX idx_created_at (created_at)\n";
                echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="section">
            <h2>📝 로그 확인 가이드</h2>
            <p>PHP 에러 로그를 확인하세요:</p>
            <div class="code">
XAMPP: C:\xampp\apache\logs\error.log
또는
php.ini의 error_log 설정 경로
            </div>
            <p>로그에서 다음 키워드를 검색하세요:</p>
            <ul>
                <li><code>[Payment Ready]</code> - 결제 준비 시점</li>
                <li><code>[Payment Confirm]</code> - 결제 승인 시점</li>
                <li><code>[Payment Fail]</code> - 결제 실패 시점</li>
            </ul>
        </div>
        
        <div class="section">
            <h2>🧪 테스트 시나리오</h2>
            <ol>
                <li><strong>결제 시작:</strong> ready.php 호출 후 payment_orders에 status='READY' 레코드가 생성되는지 확인</li>
                <li><strong>결제 성공:</strong> payment_success.php에서 DB 조회 후 confirm 호출, status='DONE' 업데이트 확인</li>
                <li><strong>중복 호출:</strong> payment_success.php를 새로고침해도 confirm 재호출 없이 저장된 결과만 표시되는지 확인</li>
                <li><strong>결제 실패:</strong> payment_fail.php에서 status='FAIL' 업데이트 확인</li>
            </ol>
        </div>
    </div>
</body>
</html>
