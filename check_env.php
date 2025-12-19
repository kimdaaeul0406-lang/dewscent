<?php
// .env 파일 확인 스크립트
// 배포 서버에서 실행하여 .env 파일이 제대로 로드되는지 확인

echo "<h1>.env 파일 확인</h1>";

// 1. .env 파일 경로 확인
$envPath = __DIR__ . '/.env';
echo "<h2>1. .env 파일 경로</h2>";
echo "경로: <code>" . $envPath . "</code><br>";
echo "존재 여부: " . (file_exists($envPath) ? '<span style="color:green;">존재함</span>' : '<span style="color:red;">존재하지 않음</span>') . "<br>";

if (file_exists($envPath)) {
    echo "읽기 권한: " . (is_readable($envPath) ? '<span style="color:green;">읽기 가능</span>' : '<span style="color:red;">읽기 불가</span>') . "<br>";
    echo "파일 크기: " . filesize($envPath) . " bytes<br>";
}

// 2. .env 파일 내용 확인 (보안상 값은 마스킹)
if (file_exists($envPath)) {
    echo "<h2>2. .env 파일 내용 (키만 표시)</h2>";
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "<pre>";
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            echo htmlspecialchars($line) . "\n";
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // 값 마스킹
            if (strlen($value) > 10) {
                $value = substr($value, 0, 6) . str_repeat('*', strlen($value) - 6);
            } else {
                $value = str_repeat('*', strlen($value));
            }
            echo htmlspecialchars($key . '=' . $value) . "\n";
        }
    }
    echo "</pre>";
}

// 3. config.php에서 .env 로드 후 환경 변수 확인
echo "<h2>3. config.php 로드 후 환경 변수 확인</h2>";
require_once __DIR__ . '/includes/config.php';

$envVars = [
    'DB_HOST',
    'DB_USER',
    'DB_NAME',
    'SITE_URL',
    'KAKAO_CLIENT_ID',
    'NAVER_CLIENT_ID'
];

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr><th>변수명</th><th>getenv()</th><th>\$_ENV</th><th>정의된 상수</th></tr>";

foreach ($envVars as $var) {
    $getenvValue = getenv($var);
    $envValue = $_ENV[$var] ?? 'not set';
    $constValue = defined($var) ? constant($var) : 'not defined';
    
    // 값 마스킹
    if ($getenvValue && strlen($getenvValue) > 10) {
        $getenvValue = substr($getenvValue, 0, 6) . '***';
    }
    if ($envValue !== 'not set' && strlen($envValue) > 10) {
        $envValue = substr($envValue, 0, 6) . '***';
    }
    if ($constValue !== 'not defined' && strlen($constValue) > 10) {
        $constValue = substr($constValue, 0, 6) . '***';
    }
    
    echo "<tr>";
    echo "<td><strong>$var</strong></td>";
    echo "<td>" . ($getenvValue ? htmlspecialchars($getenvValue) : '<span style="color:red;">없음</span>') . "</td>";
    echo "<td>" . ($envValue !== 'not set' ? htmlspecialchars($envValue) : '<span style="color:red;">없음</span>') . "</td>";
    echo "<td>" . ($constValue !== 'not defined' ? htmlspecialchars($constValue) : '<span style="color:red;">없음</span>') . "</td>";
    echo "</tr>";
}

echo "</table>";

// 4. 세션 정보 확인
echo "<h2>4. 세션 정보</h2>";
echo "세션 상태: " . (session_status() === PHP_SESSION_ACTIVE ? '<span style="color:green;">활성</span>' : '<span style="color:red;">비활성</span>') . "<br>";
echo "세션 ID: " . (session_status() === PHP_SESSION_ACTIVE ? session_id() : 'N/A') . "<br>";
echo "세션 쿠키 설정:<br>";
echo "<pre>";
echo "cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
echo "</pre>";

// 5. HTTPS 감지 확인
echo "<h2>5. HTTPS 감지</h2>";
echo "\$_SERVER['HTTPS']: " . ($_SERVER['HTTPS'] ?? 'not set') . "<br>";
echo "\$_SERVER['HTTP_X_FORWARDED_PROTO']: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "<br>";
echo "\$_SERVER['SERVER_PORT']: " . ($_SERVER['SERVER_PORT'] ?? 'not set') . "<br>";
echo "감지된 프로토콜: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP') . "<br>";

// 6. SITE_URL 확인
echo "<h2>6. SITE_URL 확인</h2>";
echo "정의된 SITE_URL: <code>" . (defined('SITE_URL') ? SITE_URL : 'not defined') . "</code><br>";
echo "현재 요청 URL: <code>" . (isset($_SERVER['HTTP_HOST']) ? ($_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : 'N/A') . "</code><br>";

// 7. DB 연결 테스트
echo "<h2>7. DB 연결 테스트</h2>";
try {
    require_once __DIR__ . '/includes/db.php';
    $test = db()->fetchOne("SELECT 1 as test");
    echo '<span style="color:green;">DB 연결 성공</span><br>';
    echo "DB_NAME: " . DB_NAME . "<br>";
} catch (Exception $e) {
    echo '<span style="color:red;">DB 연결 실패: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
}

echo "<hr>";
echo "<p><strong>참고:</strong> 이 파일은 배포 서버에서 확인용으로만 사용하세요. 확인 후 삭제하거나 접근을 차단하세요.</p>";
?>
