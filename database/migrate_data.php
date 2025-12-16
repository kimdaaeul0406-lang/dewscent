<?php
// 기존 데이터를 DB로 마이그레이션하는 스크립트
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>데이터 마이그레이션</title>";
echo "<style>body{font-family:sans-serif;margin:2rem;background:#f4f4f4;color:#333;}div{background:#fff;padding:1.5rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);margin-bottom:1rem;}h1{color:#5f7161;}h2{color:#c96473;font-size:1.1rem;margin-top:1.5rem;margin-bottom:0.5rem;}p{margin-bottom:0.5rem;}strong{color:#c96473;}.success{color:#5f7161;}.error{color:#c96473;}.info{color:#888;}</style></head><body><div>";
echo "<h1>DewScent 데이터 마이그레이션</h1>";

try {
    $conn = db()->getConnection();
    
    // 1. 상품 데이터 확인 및 기본 상품 추가
    echo "<h2>1. 상품 데이터 확인</h2>";
    $productCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM products");
    $productCount = (int)$productCount['cnt'];
    
    if ($productCount === 0) {
        echo "<p class='info'>기본 상품 데이터를 추가합니다...</p>";
        
        $defaultProducts = [
            ['name' => '라벤더 드림', 'type' => '향수', 'price' => 89000, 'desc' => '차분하고 편안한 라벤더 향', 'stock' => 50],
            ['name' => '로즈 가든', 'type' => '향수', 'price' => 95000, 'desc' => '우아한 장미 향', 'stock' => 30],
            ['name' => '시트러스 버스트', 'type' => '향수', 'price' => 85000, 'desc' => '상쾌한 시트러스 향', 'stock' => 40],
            ['name' => '바닐라 클라우드', 'type' => '바디미스트', 'price' => 45000, 'desc' => '따뜻한 바닐라 향', 'stock' => 60],
            ['name' => '오션 브리즈', 'type' => '바디미스트', 'price' => 42000, 'desc' => '시원한 바다 향', 'stock' => 55],
            ['name' => '우디 포레스트', 'type' => '디퓨저', 'price' => 65000, 'desc' => '깊은 숲의 향', 'stock' => 25],
            ['name' => '프레시 린넨', 'type' => '섬유유연제', 'price' => 18000, 'desc' => '깨끗한 린넨 향', 'stock' => 100],
        ];
        
        foreach ($defaultProducts as $p) {
            try {
                db()->insert(
                    "INSERT INTO products (name, type, price, `desc`, stock, status, rating, reviews, created_at)
                     VALUES (?, ?, ?, ?, ?, '판매중', 0, 0, NOW())",
                    [$p['name'], $p['type'], $p['price'], $p['desc'], $p['stock']]
                );
            } catch (Exception $e) {
                echo "<p class='error'>상품 '{$p['name']}' 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        echo "<p class='success'>✅ 기본 상품 데이터가 추가되었습니다.</p>";
    } else {
        echo "<p class='info'>ℹ️ 이미 {$productCount}개의 상품이 있습니다.</p>";
    }
    
    // 2. 회원 데이터 확인
    echo "<h2>2. 회원 데이터 확인</h2>";
    $userCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM users");
    $userCount = (int)$userCount['cnt'];
    echo "<p class='info'>현재 {$userCount}명의 회원이 있습니다.</p>";
    
    // 관리자 계정 확인
    $admin = db()->fetchOne("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
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
            echo "<p class='info'>이메일: {$adminEmail}<br>비밀번호: admin123</p>";
        } catch (Exception $e) {
            echo "<p class='error'>관리자 계정 생성 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ 관리자 계정이 이미 존재합니다.</p>";
    }
    
    // 3. 문의 데이터 확인
    echo "<h2>3. 문의 데이터 확인</h2>";
    $inquiryCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM inquiries");
    $inquiryCount = (int)$inquiryCount['cnt'];
    echo "<p class='info'>현재 {$inquiryCount}개의 문의가 있습니다.</p>";
    
    // 4. 리뷰 데이터 확인
    echo "<h2>4. 리뷰 데이터 확인</h2>";
    $reviewCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM reviews");
    $reviewCount = (int)$reviewCount['cnt'];
    echo "<p class='info'>현재 {$reviewCount}개의 리뷰가 있습니다.</p>";
    
    // 5. 테이블 구조 확인
    echo "<h2>5. 테이블 구조 확인</h2>";
    $tables = ['users', 'products', 'inquiries', 'reviews'];
    foreach ($tables as $table) {
        $exists = db()->fetchOne("SHOW TABLES LIKE '{$table}'");
        if ($exists) {
            $count = db()->fetchOne("SELECT COUNT(*) as cnt FROM {$table}");
            echo "<p class='success'>✅ {$table} 테이블: " . (int)$count['cnt'] . "개 레코드</p>";
        } else {
            echo "<p class='error'>❌ {$table} 테이블이 없습니다.</p>";
        }
    }
    
    echo "<h2>✅ 마이그레이션 완료</h2>";
    echo "<p>모든 데이터가 확인되었습니다.</p>";
    echo "<p><a href='../index.php' style='color:#5f7161;text-decoration:none;'>← 메인 페이지로 돌아가기</a></p>";
    echo "<p><a href='../admin/dashboard.php' style='color:#5f7161;text-decoration:none;'>← 관리자 대시보드로 가기</a></p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ <strong>데이터베이스 오류:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>DB 연결 정보 (config.php) 또는 테이블 권한을 확인해주세요.</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ <strong>오류:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";

