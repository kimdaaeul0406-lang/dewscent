<?php
// 문의 및 리뷰 테이블 자동 생성 스크립트 (프로젝트 루트)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>DB 테이블 생성</title>";
echo "<style>body{font-family:sans-serif;margin:2rem;background:#f4f4f4;color:#333;}div{background:#fff;padding:1.5rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}h1{color:#5f7161;}p{margin-bottom:0.5rem;}strong{color:#c96473;}.success{color:#5f7161;}.error{color:#c96473;}</style></head><body><div>";
echo "<h1>DewScent DB 테이블 생성</h1>";

try {
    $conn = db()->getConnection();
    
    // inquiries 테이블 확인 및 생성
    $stmt = $conn->query("SHOW TABLES LIKE 'inquiries'");
    $inquiriesExists = $stmt->fetch();
    
    if (!$inquiriesExists) {
        $conn->exec("CREATE TABLE inquiries (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // 인덱스 추가
        try {
            $conn->exec("CREATE INDEX idx_inquiries_user ON inquiries(user_id)");
        } catch (PDOException $e) {
            // 인덱스가 이미 존재할 수 있음
        }
        try {
            $conn->exec("CREATE INDEX idx_inquiries_status ON inquiries(status)");
        } catch (PDOException $e) {
            // 인덱스가 이미 존재할 수 있음
        }
        
        echo "<p class='success'>✅ <strong>'inquiries' 테이블이 생성되었습니다.</strong></p>";
    } else {
        echo "<p>ℹ️ <strong>'inquiries' 테이블이 이미 존재합니다.</strong></p>";
    }
    
    // reviews 테이블 확인 및 생성
    $stmt = $conn->query("SHOW TABLES LIKE 'reviews'");
    $reviewsExists = $stmt->fetch();
    
    if (!$reviewsExists) {
        $conn->exec("CREATE TABLE reviews (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // 인덱스 추가
        try {
            $conn->exec("CREATE INDEX idx_reviews_product ON reviews(product_id)");
        } catch (PDOException $e) {
            // 인덱스가 이미 존재할 수 있음
        }
        try {
            $conn->exec("CREATE INDEX idx_reviews_user ON reviews(user_id)");
        } catch (PDOException $e) {
            // 인덱스가 이미 존재할 수 있음
        }
        
        echo "<p class='success'>✅ <strong>'reviews' 테이블이 생성되었습니다.</strong></p>";
    } else {
        echo "<p>ℹ️ <strong>'reviews' 테이블이 이미 존재합니다.</strong></p>";
    }
    
    echo "<p class='success'>🎉 <strong>데이터베이스 테이블 생성이 완료되었습니다.</strong></p>";
    echo "<p>이제 문의와 리뷰 기능을 사용할 수 있습니다.</p>";
    echo "<p><a href='index.php' style='color:#5f7161;text-decoration:none;'>← 메인 페이지로 돌아가기</a></p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ <strong>데이터베이스 오류:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>DB 연결 정보 (config.php) 또는 테이블 권한을 확인해주세요.</p>";
    echo "<p style='font-size:0.85rem;color:#888;'>오류 상세: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ <strong>오류:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";

