<?php
// 기본 데이터 삽입 스크립트 (프로젝트 루트)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>기본 데이터 삽입</title>";
echo "<style>body{font-family:sans-serif;margin:2rem;background:#f4f4f4;color:#333;}div{background:#fff;padding:1.5rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);margin-bottom:1rem;}h1{color:#5f7161;}h2{color:#c96473;font-size:1.1rem;margin-top:1.5rem;margin-bottom:0.5rem;}p{margin-bottom:0.5rem;}strong{color:#c96473;}.success{color:#5f7161;}.error{color:#c96473;}.info{color:#888;}</style></head><body><div>";
echo "<h1>DewScent 기본 데이터 삽입</h1>";

try {
    $conn = db()->getConnection();
    
    // 1. 기본 상품 데이터 추가
    echo "<h2>1. 기본 상품 데이터</h2>";
    $productCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM products");
    $productCount = (int)$productCount['cnt'];
    
    if ($productCount === 0) {
        echo "<p class='info'>기본 상품 데이터를 추가합니다...</p>";
        
        $defaultProducts = [
            ['name' => 'Morning Dew', 'type' => '향수', 'price' => 89000, 'originalPrice' => 110000, 'rating' => 4.8, 'reviews' => 128, 'badge' => 'BEST', 'desc' => '아침 이슬처럼 맑고 청량한 향기입니다.', 'stock' => 50],
            ['name' => 'Rose Garden', 'type' => '바디미스트', 'price' => 65000, 'originalPrice' => null, 'rating' => 4.9, 'reviews' => 89, 'badge' => 'NEW', 'desc' => '로맨틱한 장미 정원을 거니는 듯한 우아한 향기입니다.', 'stock' => 60],
            ['name' => 'Golden Hour', 'type' => '향수', 'price' => 105000, 'originalPrice' => null, 'rating' => 4.7, 'reviews' => 156, 'badge' => null, 'desc' => '황금빛 노을처럼 따스하고 포근한 향기입니다.', 'stock' => 40],
            ['name' => 'Forest Mist', 'type' => '디퓨저', 'price' => 78000, 'originalPrice' => 98000, 'rating' => 4.6, 'reviews' => 72, 'badge' => 'SALE', 'desc' => '숲속의 신선한 공기를 담은 청량한 향기입니다.', 'stock' => 35],
            ['name' => 'Ocean Breeze', 'type' => '섬유유연제', 'price' => 32000, 'originalPrice' => null, 'rating' => 4.5, 'reviews' => 203, 'badge' => null, 'desc' => '바다 바람처럼 시원하고 깨끗한 향기입니다.', 'stock' => 100],
            ['name' => 'Velvet Night', 'type' => '향수', 'price' => 125000, 'originalPrice' => null, 'rating' => 4.9, 'reviews' => 67, 'badge' => 'NEW', 'desc' => '밤의 신비로움을 담은 관능적인 향기입니다.', 'stock' => 25],
            ['name' => 'Citrus Burst', 'type' => '바디미스트', 'price' => 55000, 'originalPrice' => 68000, 'rating' => 4.4, 'reviews' => 145, 'badge' => 'SALE', 'desc' => '상큼한 시트러스가 톡톡 터지는 활기찬 향기입니다.', 'stock' => 70],
            ['name' => 'Soft Cotton', 'type' => '섬유유연제', 'price' => 28000, 'originalPrice' => null, 'rating' => 4.7, 'reviews' => 312, 'badge' => 'BEST', 'desc' => '갓 세탁한 면처럼 포근하고 깨끗한 향기입니다.', 'stock' => 150],
        ];
        
        $inserted = 0;
        foreach ($defaultProducts as $p) {
            try {
                db()->insert(
                    "INSERT INTO products (name, type, price, originalPrice, rating, reviews, badge, `desc`, stock, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '판매중', NOW())",
                    [
                        $p['name'], 
                        $p['type'], 
                        $p['price'], 
                        $p['originalPrice'], 
                        $p['rating'], 
                        $p['reviews'], 
                        $p['badge'], 
                        $p['desc'], 
                        $p['stock']
                    ]
                );
                $inserted++;
            } catch (Exception $e) {
                echo "<p class='error'>상품 '{$p['name']}' 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        echo "<p class='success'>✅ {$inserted}개의 기본 상품이 추가되었습니다.</p>";
    } else {
        echo "<p class='info'>ℹ️ 이미 {$productCount}개의 상품이 있습니다.</p>";
    }
    
    // 2. 관리자 계정 확인 및 생성
    echo "<h2>2. 관리자 계정</h2>";
    $admin = db()->fetchOne("SELECT id, email FROM users WHERE is_admin = 1 LIMIT 1");
    if (!$admin) {
        echo "<p class='info'>관리자 계정을 생성합니다...</p>";
        try {
            $adminEmail = 'admin@dewscent.com';
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            db()->insert(
                "INSERT INTO users (email, password, name, is_admin, created_at)
                 VALUES (?, ?, '관리자', 1, NOW())",
                [$adminEmail, $adminPassword]
            );
            echo "<p class='success'>✅ 관리자 계정이 생성되었습니다.</p>";
            echo "<p class='info'><strong>이메일:</strong> {$adminEmail}<br><strong>비밀번호:</strong> admin123</p>";
            echo "<p class='info' style='font-size:0.85rem;color:#888;'>※ 보안을 위해 로그인 후 비밀번호를 변경하세요.</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "<p class='info'>ℹ️ 관리자 계정이 이미 존재합니다.</p>";
            } else {
                echo "<p class='error'>관리자 계정 생성 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<p class='info'>ℹ️ 관리자 계정이 이미 존재합니다. (이메일: {$admin['email']})</p>";
    }
    
    // 3. 테이블 구조 및 데이터 확인
    echo "<h2>3. 데이터베이스 상태 확인</h2>";
    $tables = [
        'users' => '회원',
        'products' => '상품',
        'inquiries' => '문의',
        'reviews' => '리뷰'
    ];
    
    foreach ($tables as $table => $label) {
        try {
            $exists = db()->fetchOne("SHOW TABLES LIKE '{$table}'");
            if ($exists) {
                $count = db()->fetchOne("SELECT COUNT(*) as cnt FROM {$table}");
                $countNum = (int)$count['cnt'];
                $status = $countNum > 0 ? 'success' : 'info';
                echo "<p class='{$status}'>✅ {$label} 테이블: {$countNum}개 레코드</p>";
            } else {
                echo "<p class='error'>❌ {$label} 테이블이 없습니다. <a href='create_tables.php' style='color:#c96473;'>테이블 생성하기</a></p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ {$label} 테이블 확인 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h2>✅ 완료</h2>";
    echo "<p>기본 데이터 삽입이 완료되었습니다.</p>";
    echo "<p><a href='index.php' style='color:#5f7161;text-decoration:none;font-weight:500;'>← 메인 페이지로 돌아가기</a></p>";
    echo "<p><a href='admin/dashboard.php' style='color:#5f7161;text-decoration:none;font-weight:500;'>← 관리자 대시보드로 가기</a></p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ <strong>데이터베이스 오류:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>DB 연결 정보 (config.php) 또는 테이블 권한을 확인해주세요.</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ <strong>오류:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";

