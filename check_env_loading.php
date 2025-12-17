<?php
/**
 * .env 파일 로딩 확인 도구
 * 
 * .env 파일이 제대로 로드되는지 확인합니다.
 */

// 프로젝트 루트 경로
$projectRoot = __DIR__;
$envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>.env 파일 로딩 확인</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 .env 파일 로딩 확인</h1>
        
        <?php
        // Step 1: 파일 존재 확인
        echo '<div class="card">';
        echo '<h2>📁 Step 1: .env 파일 존재 확인</h2>';
        echo '<p><strong>프로젝트 루트:</strong> ' . htmlspecialchars($projectRoot) . '</p>';
        echo '<p><strong>.env 파일 경로:</strong> ' . htmlspecialchars($envPath) . '</p>';
        
        $envExists = file_exists($envPath);
        if ($envExists) {
            echo '<div class="ok">✅ .env 파일이 존재합니다.</div>';
            echo '<p>파일 크기: ' . filesize($envPath) . ' bytes</p>';
            echo '<p>파일 수정 시간: ' . date('Y-m-d H:i:s', filemtime($envPath)) . '</p>';
        } else {
            echo '<div class="error">❌ .env 파일이 없습니다!</div>';
            echo '<p>다음 경로에 .env 파일을 생성하세요:</p>';
            echo '<div class="code">' . htmlspecialchars($envPath) . '</div>';
        }
        echo '</div>';
        
        if ($envExists) {
            // Step 2: 파일 내용 확인
            echo '<div class="card">';
            echo '<h2>📄 Step 2: .env 파일 내용 확인</h2>';
            
            $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                echo '<div class="error">❌ .env 파일을 읽을 수 없습니다. 파일 권한을 확인하세요.</div>';
            } else {
                echo '<p>총 ' . count($lines) . '줄</p>';
                echo '<table>';
                echo '<tr><th>줄 번호</th><th>내용</th><th>상태</th></tr>';
                
                $hasClientKey = false;
                $hasSecretKey = false;
                $clientKeyValue = '';
                $secretKeyValue = '';
                
                foreach ($lines as $i => $line) {
                    $line = trim($line);
                    $status = '';
                    
                    // 빈 줄 또는 주석
                    if (empty($line) || strpos($line, '#') === 0) {
                        $status = '<span style="color: #999;">주석/빈 줄</span>';
                    }
                    // TOSS_CLIENT_KEY
                    elseif (strpos($line, 'TOSS_CLIENT_KEY=') === 0) {
                        $hasClientKey = true;
                        $parts = explode('=', $line, 2);
                        $clientKeyValue = isset($parts[1]) ? trim($parts[1], '"\'') : '';
                        $status = '<span style="color: #4CAF50;">✅ 발견</span>';
                    }
                    // TOSS_SECRET_KEY
                    elseif (strpos($line, 'TOSS_SECRET_KEY=') === 0) {
                        $hasSecretKey = true;
                        $parts = explode('=', $line, 2);
                        $secretKeyValue = isset($parts[1]) ? trim($parts[1], '"\'') : '';
                        $status = '<span style="color: #4CAF50;">✅ 발견</span>';
                    }
                    // 기타
                    else {
                        $status = '<span style="color: #999;">기타</span>';
                    }
                    
                    // 키 값은 마스킹
                    $displayLine = $line;
                    if (strpos($line, 'TOSS_CLIENT_KEY=') === 0 || strpos($line, 'TOSS_SECRET_KEY=') === 0) {
                        $parts = explode('=', $line, 2);
                        if (isset($parts[1])) {
                            $value = trim($parts[1], '"\'');
                            $masked = strlen($value) > 10 ? substr($value, 0, 10) . '...' : $value;
                            $displayLine = $parts[0] . '=' . $masked;
                        }
                    }
                    
                    echo '<tr>';
                    echo '<td>' . ($i + 1) . '</td>';
                    echo '<td><code>' . htmlspecialchars($displayLine) . '</code></td>';
                    echo '<td>' . $status . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                if (!$hasClientKey) {
                    echo '<div class="error">❌ TOSS_CLIENT_KEY가 .env 파일에 없습니다.</div>';
                }
                if (!$hasSecretKey) {
                    echo '<div class="error">❌ TOSS_SECRET_KEY가 .env 파일에 없습니다.</div>';
                }
            }
            echo '</div>';
            
            // Step 3: env.php 로더 테스트
            echo '<div class="card">';
            echo '<h2>🔄 Step 3: env.php 로더 테스트</h2>';
            
            // env.php 로드 전
            echo '<p><strong>env.php 로드 전:</strong></p>';
            echo '<div class="code">getenv(\'TOSS_CLIENT_KEY\'): ' . (getenv('TOSS_CLIENT_KEY') ?: '(없음)') . '
$_ENV[\'TOSS_CLIENT_KEY\']: ' . ($_ENV['TOSS_CLIENT_KEY'] ?? '(없음)') . '</div>';
            
            // env.php 로드
            require_once __DIR__ . '/config/env.php';
            
            // env.php 로드 후
            echo '<p style="margin-top: 1rem;"><strong>env.php 로드 후:</strong></p>';
            $loadedClientKey = getenv('TOSS_CLIENT_KEY') ?: ($_ENV['TOSS_CLIENT_KEY'] ?? '');
            $loadedSecretKey = getenv('TOSS_SECRET_KEY') ?: ($_ENV['TOSS_SECRET_KEY'] ?? '');
            
            echo '<div class="code">getenv(\'TOSS_CLIENT_KEY\'): ' . ($loadedClientKey ? substr($loadedClientKey, 0, 10) . '...' : '(없음)') . '
$_ENV[\'TOSS_CLIENT_KEY\']: ' . (isset($_ENV['TOSS_CLIENT_KEY']) ? substr($_ENV['TOSS_CLIENT_KEY'], 0, 10) . '...' : '(없음)') . '
getenv(\'TOSS_SECRET_KEY\'): ' . ($loadedSecretKey ? substr($loadedSecretKey, 0, 10) . '...' : '(없음)') . '
$_ENV[\'TOSS_SECRET_KEY\']: ' . (isset($_ENV['TOSS_SECRET_KEY']) ? substr($_ENV['TOSS_SECRET_KEY'], 0, 10) . '...' : '(없음)') . '</div>';
            
            if (empty($loadedClientKey) || empty($loadedSecretKey)) {
                echo '<div class="error">❌ 환경 변수가 로드되지 않았습니다!</div>';
                echo '<p>가능한 원인:</p>';
                echo '<ul style="margin-left: 1.5rem; margin-top: 0.5rem;">';
                echo '<li>.env 파일의 형식이 잘못되었을 수 있습니다 (공백, 따옴표 등)</li>';
                echo '<li>파일 권한 문제일 수 있습니다</li>';
                echo '<li>env.php 파일이 올바른 경로를 찾지 못했을 수 있습니다</li>';
                echo '</ul>';
            } else {
                echo '<div class="ok">✅ 환경 변수가 성공적으로 로드되었습니다!</div>';
                
                // 키 타입 확인
                $clientType = strpos($loadedClientKey, 'test_') === 0 ? '테스트' : (strpos($loadedClientKey, 'live_') === 0 ? '라이브' : '알 수 없음');
                $secretType = strpos($loadedSecretKey, 'test_') === 0 ? '테스트' : (strpos($loadedSecretKey, 'live_') === 0 ? '라이브' : '알 수 없음');
                
                echo '<p>CLIENT_KEY 타입: <strong>' . $clientType . '</strong></p>';
                echo '<p>SECRET_KEY 타입: <strong>' . $secretType . '</strong></p>';
                
                if ($clientType === '테스트' && $secretType === '테스트') {
                    echo '<div class="ok">✅ 두 키 모두 테스트 키입니다. 테스트 환경에 적합합니다.</div>';
                } elseif ($clientType !== $secretType) {
                    echo '<div class="error">❌ 키 타입이 일치하지 않습니다!</div>';
                }
            }
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="?" class="btn" style="display: inline-block; padding: 0.75rem 1.5rem; background: #5f7161; color: white; text-decoration: none; border-radius: 6px;">새로고침</a>
            <a href="check_toss_keys.php" class="btn" style="display: inline-block; padding: 0.75rem 1.5rem; background: #2196F3; color: white; text-decoration: none; border-radius: 6px; margin-left: 0.5rem;">키 확인</a>
        </div>
    </div>
</body>
</html>
