<?php
/**
 * payment_orders 테이블 생성 스크립트
 * 
 * 사용법: 브라우저에서 /dewscent/create_payment_orders_table.php 접속
 */

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
    <title>payment_orders 테이블 생성</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            padding: 2rem;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .success {
            color: #4CAF50;
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .error {
            color: #f44336;
            background: #ffebee;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
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
        <h1>🔧 payment_orders 테이블 생성</h1>
        
        <?php
        try {
            $conn = db()->getConnection();
            
            // 테이블 존재 확인
            $tables = db()->fetchAll("SHOW TABLES LIKE 'payment_orders'");
            
            if (!empty($tables)) {
                echo '<div class="success">✅ payment_orders 테이블이 이미 존재합니다.</div>';
            } else {
                echo '<p>payment_orders 테이블을 생성합니다...</p>';
                
                // 테이블 생성
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS payment_orders (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id VARCHAR(100) NOT NULL UNIQUE COMMENT '주문번호 (PK)',
                        order_name VARCHAR(255) NOT NULL COMMENT '주문명',
                        amount INT NOT NULL COMMENT '결제 금액',
                        customer_name VARCHAR(100) NOT NULL COMMENT '구매자 이름',
                        customer_email VARCHAR(255) NOT NULL COMMENT '구매자 이메일',
                        status VARCHAR(20) DEFAULT 'READY' COMMENT 'READY, DONE, FAIL',
                        payment_key VARCHAR(255) DEFAULT NULL COMMENT '토스페이먼츠 paymentKey',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
                        INDEX idx_order_id (order_id),
                        INDEX idx_status (status),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                echo '<div class="success">✅ payment_orders 테이블을 성공적으로 생성했습니다!</div>';
                
                // 테이블 구조 확인
                $columns = db()->fetchAll("SHOW COLUMNS FROM payment_orders");
                echo '<h2>생성된 테이블 구조:</h2>';
                echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
                echo '<tr><th>컬럼명</th><th>타입</th><th>NULL</th><th>기본값</th><th>설명</th></tr>';
                foreach ($columns as $col) {
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? '(NULL)') . '</td>';
                    echo '<td>' . htmlspecialchars($col['Comment'] ?? '') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            // ensure_tables_exist() 함수도 실행하여 다른 테이블들도 확인
            echo '<h2>다른 테이블 확인</h2>';
            ensure_tables_exist();
            echo '<div class="success">✅ 모든 테이블이 준비되었습니다.</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="code">' . htmlspecialchars($e->getTraceAsString()) . '</div>';
        }
        ?>
        
        <p style="margin-top: 2rem;">
            <a href="debug_payment.php" style="padding: 0.5rem 1rem; background: #5f7161; color: white; text-decoration: none; border-radius: 4px;">
                디버깅 도구로 이동
            </a>
        </p>
    </div>
</body>
</html>
