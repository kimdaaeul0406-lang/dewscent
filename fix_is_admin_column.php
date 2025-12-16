<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>is_admin 컬럼 추가</title>";
echo "<style>body{font-family:sans-serif;margin:2rem;background:#f4f4f4;color:#333;}";
echo "div{background:#fff;padding:1.5rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo "h1{color:#5f7161;}p{margin-bottom:0.5rem;}";
echo ".success{color:#28a745;font-weight:600;}";
echo ".error{color:#dc3545;font-weight:600;}";
echo ".info{color:#17a2b8;}</style></head><body><div>";
echo "<h1>DewScent - is_admin 컬럼 추가</h1>";

try {
    $conn = db()->getConnection();
    
    // 현재 테이블 구조 확인
    $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'is_admin'");
    
    if (empty($columns)) {
        // is_admin 컬럼이 없으면 추가
        echo "<p class='info'>is_admin 컬럼을 추가합니다...</p>";
        $conn->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)' AFTER address");
        echo "<p class='success'>✅ is_admin 컬럼이 성공적으로 추가되었습니다.</p>";
    } else {
        echo "<p class='info'>ℹ️ is_admin 컬럼이 이미 존재합니다.</p>";
    }
    
    // 테이블 구조 확인
    echo "<h2>users 테이블 구조 확인</h2>";
    $allColumns = db()->fetchAll("SHOW COLUMNS FROM users");
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>Null</th><th>기본값</th></tr>";
    foreach ($allColumns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p class='success'>🎉 작업이 완료되었습니다.</p>";
    echo "<p>이제 로그인을 다시 시도해보세요.</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ 데이터베이스 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>DB 연결 정보 (config.php) 또는 테이블 권한을 확인해주세요.</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";

