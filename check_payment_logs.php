<?php
/**
 * 결제 로그 확인 도구
 * PHP 에러 로그에서 결제 관련 로그만 필터링하여 보여줍니다
 */

$logFile = 'C:\xampp\apache\logs\error.log'; // XAMPP 기본 경로
$customLogFile = $_GET['logfile'] ?? '';

if ($customLogFile && file_exists($customLogFile)) {
    $logFile = $customLogFile;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 로그 확인</title>
    <style>
        body {
            font-family: 'Noto Sans KR', monospace;
            padding: 2rem;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
        }
        .log-entry {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-left: 3px solid #555;
            background: #252526;
        }
        .log-entry.ready {
            border-left-color: #4ec9b0;
        }
        .log-entry.confirm {
            border-left-color: #569cd6;
        }
        .log-entry.fail {
            border-left-color: #f48771;
        }
        .log-entry.success {
            border-left-color: #89d185;
        }
        .log-time {
            color: #808080;
            font-size: 0.9em;
        }
        .log-message {
            color: #d4d4d4;
        }
        .error {
            color: #f48771;
            background: #3a1f1f;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .form-group {
            margin: 1rem 0;
        }
        .form-group input {
            width: 100%;
            max-width: 600px;
            padding: 0.5rem;
            background: #3c3c3c;
            border: 1px solid #555;
            color: #d4d4d4;
            border-radius: 4px;
        }
        .btn {
            padding: 0.5rem 1rem;
            background: #0e639c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 결제 로그 확인</h1>
        
        <div class="form-group">
            <form method="GET">
                <label>로그 파일 경로:</label>
                <input type="text" name="logfile" value="<?php echo htmlspecialchars($logFile, ENT_QUOTES, 'UTF-8'); ?>" placeholder="C:\xampp\apache\logs\error.log">
                <button type="submit" class="btn">조회</button>
            </form>
        </div>
        
        <?php
        if (!file_exists($logFile)) {
            echo '<div class="error">❌ 로그 파일을 찾을 수 없습니다: ' . htmlspecialchars($logFile) . '</div>';
            echo '<p>다른 경로를 시도해보세요:</p>';
            echo '<ul>';
            echo '<li>C:\xampp\apache\logs\error.log</li>';
            echo '<li>php.ini의 error_log 설정 경로 확인</li>';
            echo '</ul>';
        } else {
            $lines = file($logFile);
            $paymentLogs = [];
            
            // 결제 관련 로그만 필터링
            foreach ($lines as $line) {
                if (stripos($line, '[Payment') !== false || 
                    stripos($line, 'payment') !== false ||
                    stripos($line, '결제') !== false) {
                    $paymentLogs[] = $line;
                }
            }
            
            // 최근 100개만 표시
            $paymentLogs = array_slice($paymentLogs, -100);
            $paymentLogs = array_reverse($paymentLogs);
            
            if (empty($paymentLogs)) {
                echo '<div class="error">결제 관련 로그를 찾을 수 없습니다.</div>';
            } else {
                echo '<h2>최근 결제 로그 (' . count($paymentLogs) . '개)</h2>';
                foreach ($paymentLogs as $log) {
                    $class = '';
                    if (stripos($log, '[Payment Ready]') !== false) {
                        $class = 'ready';
                    } elseif (stripos($log, '[Payment Confirm]') !== false) {
                        $class = stripos($log, '✅') !== false ? 'confirm success' : 'confirm';
                    } elseif (stripos($log, '[Payment Fail]') !== false || stripos($log, '❌') !== false) {
                        $class = 'fail';
                    }
                    
                    echo '<div class="log-entry ' . $class . '">';
                    echo '<span class="log-message">' . htmlspecialchars($log) . '</span>';
                    echo '</div>';
                }
            }
        }
        ?>
    </div>
</body>
</html>
