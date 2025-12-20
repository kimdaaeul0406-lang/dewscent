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
    // 배포 서버에서 리버스 프록시 사용 시 HTTP_X_FORWARDED_PROTO 체크
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    } else {
        ini_set('session.cookie_secure', '0'); // HTTP에서는 명시적으로 0
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

// 로컬 설정 파일 로드 (있는 경우) - 최우선
$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    require_once $localConfigPath;
}

// .env 파일 로드 (있는 경우) - 환경 변수를 먼저 로드해야 함
$envPath = __DIR__ . '/../.env';
$envLoaded = false;

if (file_exists($envPath)) {
    error_log('[Config] .env 파일 발견: ' . $envPath);
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $loadedCount = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        // 빈 줄 또는 주석 건너뛰기
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        // KEY=VALUE 형식 파싱
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // 따옴표 제거
            $value = trim($value, '"\'');
            
            if (!empty($key)) {
                // 환경 변수 설정 (putenv와 $_ENV 둘 다 설정)
                putenv("$key=$value");
                $_ENV[$key] = $value;
                // $_SERVER에도 설정 (일부 환경에서 필요)
                $_SERVER[$key] = $value;
                $loadedCount++;
            }
        }
    }
    
    $envLoaded = true;
    error_log('[Config] .env 파일에서 ' . $loadedCount . '개 변수 로드됨');
} else {
    error_log('[Config] .env 파일을 찾을 수 없음: ' . $envPath);
}

// MySQL 데이터베이스 설정 (환경변수 우선, 없으면 기본값)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'dewscent');
define('DB_CHARSET', 'utf8mb4');

// 사이트 설정
define('SITE_NAME', 'DewScent');

// SITE_URL 자동 감지 (HTTPS 우선, Mixed Content 방지)
$siteUrl = getenv('SITE_URL');
if (empty($siteUrl)) {
    // 현재 요청이 HTTPS인지 확인
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) 
                ? 'https' : 'http';
    
    // 호스트 자동 감지
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // 경로 자동 감지
    $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptPath = str_replace('\\', '/', $scriptPath);
    // admin이나 api 디렉토리인 경우 상위 디렉토리로
    if (strpos($scriptPath, '/admin') !== false || strpos($scriptPath, '/api') !== false) {
        $scriptPath = dirname($scriptPath);
    }
    // 루트 디렉토리인 경우 빈 문자열로
    if ($scriptPath === '/' || $scriptPath === '.') {
        $scriptPath = '';
    }
    
    $siteUrl = $protocol . '://' . $host . $scriptPath;
} else {
    // 환경 변수에 설정되어 있어도 현재 요청이 HTTPS면 HTTPS로 강제
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        $siteUrl = preg_replace('/^http:/', 'https:', $siteUrl);
    }
}
define('SITE_URL', rtrim($siteUrl, '/'));

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

// 카카오 키가 없어도 사이트는 작동하도록 함 (소셜 로그인 기능만 비활성화)
if (empty($kakaoClientId)) {
    error_log('[Config] 경고: KAKAO_CLIENT_ID가 설정되지 않았습니다. 카카오 로그인 기능이 비활성화됩니다.');
    $kakaoClientId = 'NOT_SET'; // 기본값 설정
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

// 네이버 키가 없어도 사이트는 작동하도록 함 (소셜 로그인 기능만 비활성화)
if (empty($naverClientId) || empty($naverClientSecret)) {
    error_log('[Config] 경고: NAVER_CLIENT_ID 또는 NAVER_CLIENT_SECRET이 설정되지 않았습니다. 네이버 로그인 기능이 비활성화됩니다.');
    $naverClientId = $naverClientId ?: 'NOT_SET';
    $naverClientSecret = $naverClientSecret ?: 'NOT_SET';
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
