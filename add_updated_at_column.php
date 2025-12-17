<?php
/**
 * users 테이블에 updated_at 컬럼 추가 스크립트
 * 한 번만 실행하면 됩니다.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $conn = db()->getConnection();
    
    // updated_at 컬럼이 있는지 확인
    $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'updated_at'");
    
    if (empty($columns)) {
        // updated_at 컬럼 추가
        $conn->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "✅ updated_at 컬럼이 추가되었습니다.\n";
    } else {
        echo "ℹ️ updated_at 컬럼이 이미 존재합니다.\n";
    }
    
    echo "✅ 완료되었습니다.\n";
} catch (Exception $e) {
    echo "❌ 오류: " . $e->getMessage() . "\n";
}
