<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>DB 자동 설정</title>";
echo "<style>body{font-family:sans-serif;margin:2rem;background:#f4f4f4;color:#333;}";
echo "div{background:#fff;padding:1.5rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);margin-bottom:1rem;}";
echo "h1{color:#5f7161;}h2{color:#c96473;margin-top:1.5rem;}";
echo "p{margin-bottom:0.5rem;}";
echo ".success{color:#28a745;font-weight:600;}";
echo ".error{color:#dc3545;font-weight:600;}";
echo ".info{color:#17a2b8;}";
echo ".warning{color:#ffc107;}";
echo "table{border-collapse:collapse;width:100%;margin-top:1rem;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#f8f9fa;}</style></head><body>";
echo "<div><h1>DewScent 데이터베이스 자동 설정</h1>";

try {
    $conn = db()->getConnection();
    echo "<p class='success'>✅ 데이터베이스 연결 성공</p>";
    
    // 1. users 테이블 확인 및 생성/수정
    echo "<h2>1. users 테이블 설정</h2>";
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(50) NOT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                address TEXT DEFAULT NULL,
                is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='success'>✅ users 테이블 확인 완료</p>";
        
        // name 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'name'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN name VARCHAR(50) NOT NULL DEFAULT '' AFTER id");
                echo "<p class='success'>✅ name 컬럼 추가 완료</p>";
            } catch (Exception $e) {
                echo "<p class='warning'>⚠️ name 컬럼 추가 중 오류 (무시됨): " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        // address 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'address'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL AFTER phone");
                echo "<p class='success'>✅ address 컬럼 추가 완료</p>";
            } catch (Exception $e) {
                echo "<p class='warning'>⚠️ address 컬럼 추가 중 오류 (무시됨): " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        // is_admin 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'is_admin'");
        if (empty($columns)) {
            try {
                // address 컬럼이 있으면 AFTER address, 없으면 마지막에 추가
                $hasAddress = !empty(db()->fetchAll("SHOW COLUMNS FROM users LIKE 'address'"));
                if ($hasAddress) {
                    $conn->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)' AFTER address");
                } else {
                    $conn->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)'");
                }
                echo "<p class='success'>✅ is_admin 컬럼 추가 완료</p>";
            } catch (Exception $e) {
                echo "<p class='warning'>⚠️ is_admin 컬럼 추가 중 오류 (무시됨): " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='info'>ℹ️ is_admin 컬럼이 이미 존재합니다</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ users 테이블 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 2. products 테이블 확인 및 생성
    echo "<h2>2. products 테이블 설정</h2>";
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                type VARCHAR(50) NOT NULL COMMENT '향수, 바디미스트, 디퓨저, 섬유유연제',
                price INT NOT NULL,
                originalPrice INT DEFAULT NULL COMMENT '할인 전 원가 (없으면 NULL)',
                rating DECIMAL(2,1) DEFAULT 0.0,
                reviews INT DEFAULT 0,
                badge VARCHAR(20) DEFAULT NULL COMMENT 'BEST, NEW, SALE 등',
                `desc` TEXT COMMENT '상품 설명',
                image VARCHAR(255) DEFAULT NULL COMMENT '상품 이미지 경로',
                stock INT DEFAULT 0 COMMENT '재고 수량',
                status VARCHAR(20) DEFAULT '판매중' COMMENT '판매중, 품절, 숨김',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='success'>✅ products 테이블 확인 완료</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ products 테이블 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 3. inquiries 테이블 확인 및 생성
    echo "<h2>3. inquiries 테이블 설정</h2>";
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL COMMENT 'shipping, exchange, product, order, other',
                order_no VARCHAR(50) DEFAULT NULL COMMENT '주문번호 (선택)',
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'waiting' COMMENT 'waiting, answered',
                answer TEXT DEFAULT NULL COMMENT '관리자 답변',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                answered_at TIMESTAMP NULL DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='success'>✅ inquiries 테이블 확인 완료</p>";
        
        // 인덱스 추가
        try {
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_inquiries_user ON inquiries(user_id)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_inquiries_status ON inquiries(status)");
        } catch (Exception $e) {
            // 인덱스가 이미 존재할 수 있음
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ inquiries 테이블 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 4. reviews 테이블 확인 및 생성
    echo "<h2>4. reviews 테이블 설정</h2>";
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                rating INT NOT NULL COMMENT '1-5',
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_product (user_id, product_id) COMMENT '한 사용자는 한 상품당 하나의 리뷰만 작성 가능'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='success'>✅ reviews 테이블 확인 완료</p>";
        
        // 인덱스 추가
        try {
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_reviews_product ON reviews(product_id)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_reviews_user ON reviews(user_id)");
        } catch (Exception $e) {
            // 인덱스가 이미 존재할 수 있음
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ reviews 테이블 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 5. 테이블 상태 확인
    echo "<h2>5. 테이블 상태 확인</h2>";
    $tables = ['users', 'products', 'inquiries', 'reviews'];
    echo "<table>";
    echo "<tr><th>테이블명</th><th>레코드 수</th><th>상태</th></tr>";
    foreach ($tables as $table) {
        try {
            $result = db()->fetchOne("SELECT COUNT(*) as cnt FROM $table");
            $count = (int)($result['cnt'] ?? 0);
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$count</td>";
            echo "<td class='success'>✅ 정상</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>-</td>";
            echo "<td class='error'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // 6. 관리자 계정 확인
    echo "<h2>6. 관리자 계정 확인</h2>";
    try {
        // is_admin 컬럼이 있는지 먼저 확인
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'is_admin'");
        if (empty($columns)) {
            echo "<p class='warning'>⚠️ is_admin 컬럼이 없어 관리자 계정을 확인할 수 없습니다.</p>";
            echo "<p class='info'>위의 users 테이블 설정에서 is_admin 컬럼이 추가되었는지 확인하세요.</p>";
        } else {
            $admin = db()->fetchOne("SELECT id, email, name FROM users WHERE is_admin = 1 LIMIT 1");
            if ($admin) {
                echo "<p class='success'>✅ 관리자 계정이 존재합니다</p>";
                echo "<p class='info'>이메일: {$admin['email']}, 이름: {$admin['name']}</p>";
            } else {
                echo "<p class='warning'>⚠️ 관리자 계정이 없습니다. 생성하시겠습니까?</p>";
                echo "<p><a href='insert_default_data.php' style='color:#17a2b8;'>기본 데이터 삽입 페이지로 이동</a></p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ 관리자 계정 확인 중 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h2>7. 완료</h2>";
    echo "<p class='success'>🎉 데이터베이스 설정이 완료되었습니다!</p>";
    echo "<p>이제 로그인과 관리자 대시보드를 사용할 수 있습니다.</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ 데이터베이스 연결 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>DB 연결 정보 (config.php)를 확인해주세요.</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";

