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

// 로컬 설정 파일 로드 (있는 경우)
$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    require_once $localConfigPath;
}

// .env 파일 로드 (있는 경우)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // 주석 건너뛰기
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // 따옴표 제거
            $value = trim($value, '"\'');
            if (!empty($key) && !getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// 사이트 설정
define('SITE_NAME', 'DewScent');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/dewscent');

// 카카오 소셜 로그인 설정
// 우선순위: config.local.php (define) > .env (getenv) > 환경 변수 (getenv)
$kakaoClientId = '';
if (defined('KAKAO_CLIENT_ID')) {
    // config.local.php에서 이미 정의된 경우
    $kakaoClientId = KAKAO_CLIENT_ID;
} else {
    // .env 또는 환경 변수에서 읽기
    $kakaoClientId = getenv('KAKAO_CLIENT_ID') ?: '';
}

if (empty($kakaoClientId)) {
    throw new RuntimeException('KAKAO_CLIENT_ID가 설정되지 않았습니다. .env 또는 config.local.php 파일을 확인하세요.');
}

// 아직 정의되지 않은 경우에만 define
if (!defined('KAKAO_CLIENT_ID')) {
    define('KAKAO_CLIENT_ID', $kakaoClientId);
}
if (!defined('KAKAO_REDIRECT_URI')) {
    define('KAKAO_REDIRECT_URI', SITE_URL . '/api/kakao_callback.php');
}

// 네이버 소셜 로그인 설정
// 우선순위: config.local.php (define) > .env (getenv) > 환경 변수 (getenv)
$naverClientId = '';
$naverClientSecret = '';

if (defined('NAVER_CLIENT_ID')) {
    // config.local.php에서 이미 정의된 경우
    $naverClientId = NAVER_CLIENT_ID;
} else {
    // .env 또는 환경 변수에서 읽기
    $naverClientId = getenv('NAVER_CLIENT_ID') ?: '';
}

if (defined('NAVER_CLIENT_SECRET')) {
    // config.local.php에서 이미 정의된 경우
    $naverClientSecret = NAVER_CLIENT_SECRET;
} else {
    // .env 또는 환경 변수에서 읽기
    $naverClientSecret = getenv('NAVER_CLIENT_SECRET') ?: '';
}

if (empty($naverClientId) || empty($naverClientSecret)) {
    throw new RuntimeException('NAVER_CLIENT_ID 또는 NAVER_CLIENT_SECRET이 설정되지 않았습니다. .env 또는 config.local.php 파일을 확인하세요.');
}

// 아직 정의되지 않은 경우에만 define
if (!defined('NAVER_CLIENT_ID')) {
    define('NAVER_CLIENT_ID', $naverClientId);
}
if (!defined('NAVER_CLIENT_SECRET')) {
    define('NAVER_CLIENT_SECRET', $naverClientSecret);
}
if (!defined('NAVER_REDIRECT_URI')) {
    define('NAVER_REDIRECT_URI', SITE_URL . '/api/naver_callback.php');
}
