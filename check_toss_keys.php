<?php
/**
 * 토스페이먼츠 키 확인 도구
 * 
 * 라이브 키와 테스트 키를 혼용하는 문제를 확인합니다.
 */

require_once __DIR__ . '/config/env.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>토스페이먼츠 키 확인</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans KR', sans-serif;
            padding: 2rem;
            background: #f0f0f0;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #5f7161;
            margin-bottom: 1.5rem;
        }
        .card {
            background: #f9f9f9;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            border-left: 4px solid #5f7161;
        }
        .ok { 
            color: #4CAF50; 
            font-weight: bold; 
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .error { 
            color: #f44336; 
            font-weight: bold; 
            background: #ffebee;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .warning { 
            color: #ff9800; 
            font-weight: bold; 
            background: #fff3e0;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .info { 
            color: #2196F3; 
            font-weight: bold; 
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .code {
            background: #2d2d2d;
            color: #d4d4d4;
            padding: 1rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 0.5rem 0;
            white-space: pre-wrap;
            font-size: 0.9rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #5f7161;
            color: white;
        }
        .key-display {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #5f7161;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 0.5rem 0.5rem 0.5rem 0;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #4a5a4b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 토스페이먼츠 키 확인</h1>
        
        <?php
        // 환경 변수에서 키 읽기
        $tossClientKey = getenv('TOSS_CLIENT_KEY') ?: ($_ENV['TOSS_CLIENT_KEY'] ?? '');
        $tossSecretKey = getenv('TOSS_SECRET_KEY') ?: ($_ENV['TOSS_SECRET_KEY'] ?? '');
        
        // 키 타입 확인 함수
        function checkKeyType($key, $keyName) {
            if (empty($key)) {
                return ['status' => 'error', 'message' => '키가 설정되지 않았습니다.', 'type' => 'none'];
            }
            
            // 테스트 키 확인
            if (strpos($key, 'test_ck_') === 0 || strpos($key, 'test_sk_') === 0) {
                return ['status' => 'ok', 'message' => '✅ 테스트 키입니다.', 'type' => 'test'];
            }
            
            // 라이브 키 확인
            if (strpos($key, 'live_ck_') === 0 || strpos($key, 'live_sk_') === 0) {
                return ['status' => 'warning', 'message' => '⚠️ 라이브(운영) 키입니다. 테스트 환경에서는 테스트 키를 사용해야 합니다.', 'type' => 'live'];
            }
            
            // 알 수 없는 형식
            return ['status' => 'error', 'message' => '❌ 알 수 없는 키 형식입니다. test_ck_ 또는 live_ck_로 시작해야 합니다.', 'type' => 'unknown'];
        }
        
        // CLIENT_KEY 확인
        echo '<div class="card">';
        echo '<h2>📱 TOSS_CLIENT_KEY 확인</h2>';
        
        $clientKeyInfo = checkKeyType($tossClientKey, 'CLIENT_KEY');
        
        if (!empty($tossClientKey)) {
            $maskedKey = strlen($tossClientKey) > 20 
                ? substr($tossClientKey, 0, 20) . '...' . substr($tossClientKey, -10)
                : $tossClientKey;
            echo '<div class="key-display">' . htmlspecialchars($maskedKey) . '</div>';
        }
        
        echo '<div class="' . $clientKeyInfo['status'] . '">';
        echo htmlspecialchars($clientKeyInfo['message']);
        echo '</div>';
        echo '</div>';
        
        // SECRET_KEY 확인
        echo '<div class="card">';
        echo '<h2>🔐 TOSS_SECRET_KEY 확인</h2>';
        
        $secretKeyInfo = checkKeyType($tossSecretKey, 'SECRET_KEY');
        
        if (!empty($tossSecretKey)) {
            $maskedKey = strlen($tossSecretKey) > 20 
                ? substr($tossSecretKey, 0, 20) . '...' . substr($tossSecretKey, -10)
                : $tossSecretKey;
            echo '<div class="key-display">' . htmlspecialchars($maskedKey) . '</div>';
        }
        
        echo '<div class="' . $secretKeyInfo['status'] . '">';
        echo htmlspecialchars($secretKeyInfo['message']);
        echo '</div>';
        echo '</div>';
        
        // 키 혼용 확인
        echo '<div class="card">';
        echo '<h2>⚠️ 키 혼용 확인</h2>';
        
        if (empty($tossClientKey) || empty($tossSecretKey)) {
            echo '<div class="error">❌ CLIENT_KEY 또는 SECRET_KEY가 설정되지 않았습니다.</div>';
        } else {
            $clientType = $clientKeyInfo['type'];
            $secretType = $secretKeyInfo['type'];
            
            if ($clientType === 'test' && $secretType === 'test') {
                echo '<div class="ok">✅ 두 키 모두 테스트 키입니다. 테스트 환경에 적합합니다.</div>';
            } elseif ($clientType === 'live' && $secretType === 'live') {
                echo '<div class="warning">⚠️ 두 키 모두 라이브 키입니다. 테스트 환경에서는 테스트 키를 사용해야 합니다.</div>';
            } elseif ($clientType !== $secretType) {
                echo '<div class="error">❌ <strong>키 혼용 오류!</strong> CLIENT_KEY와 SECRET_KEY의 타입이 일치하지 않습니다.</div>';
                echo '<div class="info">';
                echo '<p><strong>문제:</strong></p>';
                echo '<ul style="margin-left: 1.5rem; margin-top: 0.5rem;">';
                if ($clientType === 'test') {
                    echo '<li>CLIENT_KEY는 테스트 키입니다.</li>';
                } elseif ($clientType === 'live') {
                    echo '<li>CLIENT_KEY는 라이브 키입니다.</li>';
                }
                if ($secretType === 'test') {
                    echo '<li>SECRET_KEY는 테스트 키입니다.</li>';
                } elseif ($secretType === 'live') {
                    echo '<li>SECRET_KEY는 라이브 키입니다.</li>';
                }
                echo '</ul>';
                echo '<p style="margin-top: 1rem;"><strong>해결 방법:</strong></p>';
                echo '<p>두 키 모두 같은 타입(테스트 또는 라이브)으로 설정해야 합니다.</p>';
                echo '</div>';
            } else {
                echo '<div class="warning">⚠️ 키 형식을 확인할 수 없습니다.</div>';
            }
        }
        
        echo '</div>';
        
        // 올바른 테스트 키 예시
        echo '<div class="card">';
        echo '<h2>📝 올바른 테스트 키 예시</h2>';
        echo '<div class="info">';
        echo '<p>테스트 환경에서는 다음 형식의 키를 사용해야 합니다:</p>';
        echo '<div class="code">TOSS_CLIENT_KEY=test_ck_Z61JOxRQVENnO07bGq72rW0X9bAq
TOSS_SECRET_KEY=test_sk_DLJOpm5QrlLXNxLROKpNrPNdxbWn</div>';
        echo '<p style="margin-top: 1rem;"><strong>주의사항:</strong></p>';
        echo '<ul style="margin-left: 1.5rem; margin-top: 0.5rem;">';
        echo '<li>CLIENT_KEY는 <code>test_ck_</code>로 시작해야 합니다.</li>';
        echo '<li>SECRET_KEY는 <code>test_sk_</code>로 시작해야 합니다.</li>';
        echo '<li>두 키 모두 같은 타입(테스트 또는 라이브)이어야 합니다.</li>';
        echo '<li>라이브 키(<code>live_ck_</code>, <code>live_sk_</code>)는 실제 결제가 발생하므로 테스트 환경에서 사용하면 안 됩니다.</li>';
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        
        // .env 파일 경로 안내
        echo '<div class="card">';
        echo '<h2>📁 .env 파일 위치</h2>';
        
        // 프로젝트 루트 경로 찾기 (이 파일이 프로젝트 루트에 있다고 가정)
        $projectRoot = __DIR__;
        $envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
        
        echo '<div class="info">';
        echo '<p><strong>프로젝트 루트:</strong></p>';
        echo '<div class="code">' . htmlspecialchars($projectRoot) . '</div>';
        echo '<p style="margin-top: 1rem;"><strong>.env 파일 경로:</strong></p>';
        echo '<div class="code">' . htmlspecialchars($envPath) . '</div>';
        
        $envExists = file_exists($envPath);
        echo '<p style="margin-top: 1rem;">파일이 존재하는지 확인: ' . ($envExists ? '<span style="color: #4CAF50;">✅ 존재함</span>' : '<span style="color: #f44336;">❌ 없음</span>') . '</p>';
        
        if (!$envExists) {
            echo '<div class="warning" style="margin-top: 1rem;">';
            echo '<p><strong>⚠️ .env 파일이 없습니다!</strong></p>';
            echo '<p>프로젝트 루트에 .env 파일을 생성하고 다음 내용을 추가하세요:</p>';
            echo '<div class="code">TOSS_CLIENT_KEY=test_ck_Z61JOxRQVENnO07bGq72rW0X9bAq
TOSS_SECRET_KEY=test_sk_DLJOpm5QrlLXNxLROKpNrPNdxbWn</div>';
            echo '</div>';
        } else {
            // .env 파일 내용 확인 (키만)
            $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                $hasClientKey = false;
                $hasSecretKey = false;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, 'TOSS_CLIENT_KEY=') === 0) {
                        $hasClientKey = true;
                    }
                    if (strpos($line, 'TOSS_SECRET_KEY=') === 0) {
                        $hasSecretKey = true;
                    }
                }
                
                if (!$hasClientKey || !$hasSecretKey) {
                    echo '<div class="warning" style="margin-top: 1rem;">';
                    echo '<p><strong>⚠️ .env 파일에 키가 없습니다!</strong></p>';
                    if (!$hasClientKey) {
                        echo '<p>❌ TOSS_CLIENT_KEY가 없습니다.</p>';
                    }
                    if (!$hasSecretKey) {
                        echo '<p>❌ TOSS_SECRET_KEY가 없습니다.</p>';
                    }
                    echo '</div>';
                }
            }
        }
        
        echo '</div>';
        echo '</div>';
        ?>
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="?" class="btn">새로고침</a>
            <a href="check_payment_test.php" class="btn">결제 테스트 확인</a>
        </div>
    </div>
</body>
</html>
