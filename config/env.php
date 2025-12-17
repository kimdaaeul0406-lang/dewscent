<?php
/**
 * .env 파일 로더
 * 
 * 프로젝트 루트의 .env 파일을 읽어서 환경 변수로 설정합니다.
 * XAMPP/Apache 환경에서 getenv()가 .env를 자동으로 로드하지 않으므로
 * 이 파일을 통해 수동으로 로드합니다.
 */

// 프로젝트 루트 경로 찾기 (이 파일이 config/env.php에 있다고 가정)
$rootPath = dirname(__DIR__);
$envPath = $rootPath . DIRECTORY_SEPARATOR . '.env';

// .env 파일이 없으면 안내 문구 출력 후 종료
if (!file_exists($envPath)) {
    die('
    <div style="font-family: Arial, sans-serif; padding: 2rem; max-width: 600px; margin: 4rem auto; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;">
        <h2 style="color: #856404; margin-bottom: 1rem;">⚠️ .env 파일이 없습니다</h2>
        <p style="color: #856404; margin-bottom: 1rem;">
            프로젝트 루트에 <strong>.env</strong> 파일을 생성하고 다음 내용을 추가해주세요:
        </p>
        <pre style="background: #fff; padding: 1rem; border-radius: 4px; overflow-x: auto; color: #333;">
TOSS_CLIENT_KEY=your_client_key_here
TOSS_SECRET_KEY=your_secret_key_here
TOSS_SECURITY_KEY=your_security_key_here
        </pre>
        <p style="color: #856404; margin-top: 1rem; font-size: 0.9rem;">
            또는 <strong>.env.example</strong> 파일을 참고하여 .env 파일을 생성하세요.
        </p>
    </div>
    ');
}

// .env 파일 읽기
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines === false) {
    die('
    <div style="font-family: Arial, sans-serif; padding: 2rem; max-width: 600px; margin: 4rem auto; background: #f8d7da; border: 2px solid #dc3545; border-radius: 8px;">
        <h2 style="color: #721c24; margin-bottom: 1rem;">❌ .env 파일을 읽을 수 없습니다</h2>
        <p style="color: #721c24;">
            .env 파일이 존재하지만 읽을 수 없습니다. 파일 권한을 확인해주세요.
        </p>
    </div>
    ');
}

// 각 줄 파싱하여 환경 변수 설정
foreach ($lines as $line) {
    // 앞뒤 공백 제거
    $line = trim($line);
    
    // 빈 줄 건너뛰기
    if (empty($line)) {
        continue;
    }
    
    // 주석(#)으로 시작하는 줄 건너뛰기
    if (strpos($line, '#') === 0) {
        continue;
    }
    
    // = 기호가 없으면 건너뛰기
    if (strpos($line, '=') === false) {
        continue;
    }
    
    // KEY=VALUE 형태로 분리
    list($key, $value) = explode('=', $line, 2);
    
    // 키와 값의 앞뒤 공백 제거
    $key = trim($key);
    $value = trim($value);
    
    // 키가 비어있으면 건너뛰기
    if (empty($key)) {
        continue;
    }
    
    // 따옴표 제거 (큰따옴표, 작은따옴표 모두 처리)
    $value = trim($value, '"\'');
    
    // 환경 변수 설정 (기존 값이 있으면 덮어쓰지 않음)
    if (!getenv($key)) {
        putenv("$key=$value");
    }
    
    // $_ENV와 $_SERVER는 항상 업데이트
    // (getenv()가 제대로 작동하지 않는 환경에서도 사용할 수 있도록)
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// 디버그 모드에서 로드된 환경 변수 확인 (TOSS 관련만)
if (defined('APP_DEBUG') && APP_DEBUG) {
    $loadedTossKeys = [];
    foreach (['TOSS_CLIENT_KEY', 'TOSS_SECRET_KEY', 'TOSS_SECURITY_KEY'] as $key) {
        $value = $_ENV[$key] ?? '';
        if (!empty($value)) {
            $masked = strlen($value) > 6 ? substr($value, 0, 6) . str_repeat('*', strlen($value) - 6) : str_repeat('*', strlen($value));
            $loadedTossKeys[] = $key . '=' . $masked;
        }
    }
    if (!empty($loadedTossKeys)) {
        error_log('[ENV Loader] Loaded TOSS keys: ' . implode(', ', $loadedTossKeys));
    } else {
        error_log('[ENV Loader] No TOSS keys found in .env file');
    }
}

