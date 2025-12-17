<?php
/**
 * coupons 테이블 생성 스크립트
 * 
 * 사용법: 브라우저에서 이 파일을 열거나 PHP CLI로 실행
 * http://localhost/dewscent/create_coupons_table.php
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
    <title>쿠폰 테이블 생성</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            padding: 2rem;
            background: #f0f0f0;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
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
        .success {
            color: #4CAF50;
            font-weight: bold;
            padding: 1rem;
            background: #e8f5e9;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .error {
            color: #f44336;
            font-weight: bold;
            padding: 1rem;
            background: #ffebee;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .info {
            color: #2196F3;
            padding: 1rem;
            background: #e3f2fd;
            border-radius: 8px;
            margin: 1rem 0;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>쿠폰 테이블 생성</h1>
        
        <?php
        try {
            $conn = db()->getConnection();
            
            // coupons 테이블이 이미 존재하는지 확인
            $tableExists = false;
            try {
                $result = db()->fetchOne("SELECT 1 FROM coupons LIMIT 1");
                $tableExists = true;
            } catch (Exception $e) {
                $tableExists = false;
            }
            
            if ($tableExists) {
                echo '<div class="info">✅ coupons 테이블이 이미 존재합니다.</div>';
                echo '<p><a href="admin/dashboard.php" class="btn">관리자 대시보드로 이동</a></p>';
            } else {
                echo '<div class="info">⚠️ coupons 테이블이 존재하지 않습니다. 생성하겠습니다...</div>';
                
                // coupons 테이블 생성
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS coupons (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        code VARCHAR(50) NOT NULL UNIQUE COMMENT '쿠폰 코드',
                        name VARCHAR(255) NOT NULL COMMENT '쿠폰명',
                        type VARCHAR(20) NOT NULL COMMENT 'percent 또는 fixed',
                        value INT NOT NULL COMMENT '할인율(%) 또는 할인금액(원)',
                        min_amount INT DEFAULT 0 COMMENT '최소 주문 금액',
                        max_discount INT DEFAULT 0 COMMENT '최대 할인 금액 (percent 타입일 때, 0이면 제한없음)',
                        start_date DATE DEFAULT NULL COMMENT '시작일',
                        end_date DATE DEFAULT NULL COMMENT '종료일',
                        usage_limit INT DEFAULT 0 COMMENT '사용 제한 횟수 (0이면 무제한)',
                        used_count INT DEFAULT 0 COMMENT '사용된 횟수',
                        active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_code (code),
                        INDEX idx_active (active),
                        INDEX idx_dates (start_date, end_date)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                echo '<div class="success">✅ coupons 테이블이 성공적으로 생성되었습니다!</div>';
                
                // user_coupons 테이블도 확인 및 생성
                $userCouponsExists = false;
                try {
                    $result = db()->fetchOne("SELECT 1 FROM user_coupons LIMIT 1");
                    $userCouponsExists = true;
                } catch (Exception $e) {
                    $userCouponsExists = false;
                }
                
                if (!$userCouponsExists) {
                    $conn->exec("
                        CREATE TABLE IF NOT EXISTS user_coupons (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL COMMENT '사용자 ID',
                            coupon_id INT NOT NULL COMMENT '쿠폰 ID',
                            received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '받은 시간',
                            used TINYINT(1) DEFAULT 0 COMMENT '사용 여부',
                            used_at TIMESTAMP NULL DEFAULT NULL COMMENT '사용한 시간',
                            order_id INT DEFAULT NULL COMMENT '사용한 주문 ID',
                            UNIQUE KEY unique_user_coupon (user_id, coupon_id),
                            INDEX idx_user_id (user_id),
                            INDEX idx_coupon_id (coupon_id),
                            INDEX idx_used (used),
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    echo '<div class="success">✅ user_coupons 테이블이 성공적으로 생성되었습니다!</div>';
                }
                
                // coupon_usages 테이블도 확인 및 생성
                $couponUsagesExists = false;
                try {
                    $result = db()->fetchOne("SELECT 1 FROM coupon_usages LIMIT 1");
                    $couponUsagesExists = true;
                } catch (Exception $e) {
                    $couponUsagesExists = false;
                }
                
                if (!$couponUsagesExists) {
                    $conn->exec("
                        CREATE TABLE IF NOT EXISTS coupon_usages (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL COMMENT '사용자 ID',
                            coupon_id INT NOT NULL COMMENT '쿠폰 ID',
                            order_id INT NOT NULL COMMENT '주문 ID',
                            order_number VARCHAR(50) NOT NULL COMMENT '주문번호',
                            discount_amount INT NOT NULL COMMENT '할인 금액',
                            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_user_coupon (user_id, coupon_id),
                            INDEX idx_order_id (order_id),
                            INDEX idx_order_number (order_number),
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    echo '<div class="success">✅ coupon_usages 테이블이 성공적으로 생성되었습니다!</div>';
                }
                
                // 기본 쿠폰 데이터 삽입 (없는 경우만)
                $existingCoupon = db()->fetchOne("SELECT id FROM coupons WHERE code = 'WELCOME10'");
                if (!$existingCoupon) {
                    $conn->exec("
                        INSERT INTO coupons (code, name, type, value, min_amount, max_discount, active, created_at)
                        VALUES ('WELCOME10', '신규 회원 10% 할인', 'percent', 10, 0, 10000, 1, NOW())
                    ");
                    echo '<div class="success">✅ 기본 쿠폰(WELCOME10)이 생성되었습니다!</div>';
                }
                
                echo '<p><a href="admin/dashboard.php" class="btn">관리자 대시보드로 이동</a></p>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="error">스택 트레이스: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></div>';
        }
        ?>
    </div>
</body>
</html>
