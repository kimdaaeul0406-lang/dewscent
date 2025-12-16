<?php
// 세션이 안 켜져 있으면 켜기 (안전하게)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// MySQL 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // 비밀번호 있으면 여기에 입력
define('DB_NAME', 'dewscent');
define('DB_CHARSET', 'utf8mb4');
