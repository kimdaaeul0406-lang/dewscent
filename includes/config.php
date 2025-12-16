<?php
// DewScent 설정 파일

// 환경 설정 (production 또는 development)
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');

// 에러 표시 설정
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// 세션 보안 설정 (세션 시작 전에만 설정 가능)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    // 세션 쿠키 경로를 루트로 설정하여 모든 하위 경로에서 사용 가능하도록
    ini_set('session.cookie_path', '/');

    // HTTPS 환경에서만 Secure 쿠키 사용
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

// 세션 고정 공격 방지 - 로그인 시 ID 재생성
function regenerate_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// MySQL 데이터베이스 설정 (환경변수 우선, 없으면 기본값)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'dewscent');
define('DB_CHARSET', 'utf8mb4');

// 사이트 설정
define('SITE_NAME', 'DewScent');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/dewscent');
