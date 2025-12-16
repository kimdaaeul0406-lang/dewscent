<?php
// 에러 표시 켜기
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. 테스트 시작<br>";

// config 파일 확인
require_once __DIR__ . '/includes/config.php';
echo "2. config.php 로드 완료<br>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";

// db.php 로드
require_once __DIR__ . '/includes/db.php';
echo "3. db.php 로드 완료<br>";

// DB 연결 테스트
try {
    $pdo = db()->getConnection();
    echo "4. DB 연결 성공!<br>";

    // 테이블 확인
    $tables = db()->fetchAll("SHOW TABLES");
    echo "5. 테이블 목록:<br>";
    print_r($tables);

} catch (Exception $e) {
    echo "DB 연결 실패: " . $e->getMessage();
}
