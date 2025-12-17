<?php
/**
 * 에러 로그 위치 확인 도구
 * 
 * PHP error_log()가 어디에 저장되는지 확인합니다.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>에러 로그 위치 확인</title>
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
        <h1>📋 에러 로그 위치 확인</h1>
        
        <?php
        // Step 1: PHP 설정 확인
        echo '<div class="card">';
        echo '<h2>⚙️ Step 1: PHP 설정 확인</h2>';
        
        $errorLogSetting = ini_get('error_log');
        $logErrors = ini_get('log_errors');
        $displayErrors = ini_get('display_errors');
        
        echo '<table>';
        echo '<tr><th>설정 항목</th><th>값</th></tr>';
        echo '<tr><td>error_log</td><td><code>' . htmlspecialchars($errorLogSetting ?: '(기본값 사용)') . '</code></td></tr>';
        echo '<tr><td>log_errors</td><td><code>' . ($logErrors ? 'On' : 'Off') . '</code></td></tr>';
        echo '<tr><td>display_errors</td><td><code>' . ($displayErrors ? 'On' : 'Off') . '</code></td></tr>';
        echo '</table>';
        
        if (empty($errorLogSetting)) {
            echo '<div class="info">';
            echo '<p><strong>error_log가 설정되지 않았습니다.</strong></p>';
            echo '<p>기본값을 사용합니다. XAMPP에서는 보통 다음 위치에 저장됩니다:</p>';
            echo '<ul style="margin-left: 1.5rem; margin-top: 0.5rem;">';
            echo '<li><code>C:\\xampp\\apache\\logs\\error.log</code> (Apache 에러 로그)</li>';
            echo '<li><code>C:\\xampp\\php\\logs\\php_error_log</code> (PHP 에러 로그)</li>';
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="ok">✅ error_log가 설정되어 있습니다: <code>' . htmlspecialchars($errorLogSetting) . '</code></div>';
        }
        echo '</div>';
        
        // Step 2: 일반적인 로그 파일 위치 확인
        echo '<div class="card">';
        echo '<h2>📁 Step 2: 일반적인 로그 파일 위치</h2>';
        
        $commonLogPaths = [
            'C:\\xampp\\apache\\logs\\error.log' => 'Apache 에러 로그',
            'C:\\xampp\\php\\logs\\php_error_log' => 'PHP 에러 로그',
            'C:\\xampp\\apache\\logs\\access.log' => 'Apache 접근 로그',
        ];
        
        if (!empty($errorLogSetting)) {
            $commonLogPaths[$errorLogSetting] = 'PHP 설정의 error_log';
        }
        
        echo '<table>';
        echo '<tr><th>경로</th><th>설명</th><th>존재 여부</th><th>크기</th><th>최종 수정</th></tr>';
        
        foreach ($commonLogPaths as $path => $description) {
            $exists = file_exists($path);
            $size = $exists ? filesize($path) : 0;
            $modified = $exists ? date('Y-m-d H:i:s', filemtime($path)) : '-';
            
            echo '<tr>';
            echo '<td><code style="font-size: 0.85rem;">' . htmlspecialchars($path) . '</code></td>';
            echo '<td>' . htmlspecialchars($description) . '</td>';
            echo '<td>' . ($exists ? '<span style="color: #4CAF50;">✅ 있음</span>' : '<span style="color: #999;">❌ 없음</span>') . '</td>';
            echo '<td>' . ($exists ? number_format($size) . ' bytes' : '-') . '</td>';
            echo '<td style="font-size: 0.85rem;">' . htmlspecialchars($modified) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
        
        // Step 3: 테스트 로그 작성
        echo '<div class="card">';
        echo '<h2>🧪 Step 3: 테스트 로그 작성</h2>';
        
        $testMessage = '[TEST] ' . date('Y-m-d H:i:s') . ' - 에러 로그 위치 확인 테스트';
        error_log($testMessage);
        
        echo '<div class="info">';
        echo '<p>테스트 로그를 작성했습니다:</p>';
        echo '<div class="code">' . htmlspecialchars($testMessage) . '</div>';
        echo '<p style="margin-top: 1rem;">위의 로그 파일들을 확인하여 이 메시지가 어디에 기록되었는지 확인하세요.</p>';
        echo '</div>';
        echo '</div>';
        
        // Step 4: 결제 로그 확인 가이드
        echo '<div class="card">';
        echo '<h2>💡 결제 로그 확인 가이드</h2>';
        echo '<div class="info">';
        echo '<p><strong>결제 관련 로그를 찾는 방법:</strong></p>';
        echo '<ol style="margin-left: 1.5rem; margin-top: 0.5rem;">';
        echo '<li>위의 로그 파일 중 하나를 메모장으로 엽니다</li>';
        echo '<li>Ctrl+F로 "[Payment Ready]"를 검색합니다</li>';
        echo '<li>또는 파일 끝부분을 확인합니다 (최신 로그가 아래쪽에 있습니다)</li>';
        echo '</ol>';
        echo '<p style="margin-top: 1rem;"><strong>로그 파일이 너무 크면:</strong></p>';
        echo '<ul style="margin-left: 1.5rem; margin-top: 0.5rem;">';
        echo '<li>PowerShell에서: <code>Get-Content "C:\\xampp\\apache\\logs\\error.log" -Tail 100</code></li>';
        echo '<li>또는 <a href="check_payment_logs.php" style="color: #2196F3;">check_payment_logs.php</a> 도구를 사용하세요</li>';
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        ?>
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="?" class="btn">새로고침</a>
            <a href="check_payment_logs.php" class="btn">결제 로그 확인</a>
        </div>
    </div>
</body>
</html>


