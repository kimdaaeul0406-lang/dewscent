<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_setup.php';
require_once __DIR__ . '/includes/header.php';

$error = $_GET['error'] ?? '결제 처리 중 오류가 발생했습니다.';
$errorCode = $_GET['code'] ?? '';

// URL 파라미터에서 orderId 받기
$orderId = $_GET['orderId'] ?? '';

// orderId가 있으면 payment_orders 테이블에 status=FAIL 업데이트
if (!empty($orderId)) {
    try {
        ensure_tables_exist();
        db()->execute(
            "UPDATE payment_orders SET 
                status = 'FAIL',
                updated_at = NOW()
             WHERE order_id = ?",
            [$orderId]
        );
        error_log('[Payment Fail] payment_orders 업데이트: orderId=' . $orderId . ', status=FAIL');
    } catch (Exception $e) {
        error_log('[Payment Fail] payment_orders 업데이트 실패: ' . $e->getMessage() . ' | orderId=' . $orderId);
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 실패 - DewScent</title>
    <link rel="stylesheet" href="/dewscent/public/css/style.css">
    <style>
        .fail-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            text-align: center;
        }
        .fail-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            background: var(--rose-lighter);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--rose);
        }
        .fail-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 2rem;
            color: var(--rose);
            margin-bottom: 1rem;
        }
        .fail-message {
            color: var(--mid);
            margin-bottom: 2rem;
            padding: 1rem;
            background: var(--rose-lighter);
            border-radius: 8px;
        }
        .error-code {
            color: var(--light);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .form-btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .form-btn.primary {
            background: var(--sage);
            color: var(--white);
        }
        .form-btn.primary:hover {
            background: var(--sage-hover);
        }
        .form-btn.secondary {
            background: var(--white);
            color: var(--sage);
            border: 2px solid var(--sage);
        }
        .form-btn.secondary:hover {
            background: var(--sage-bg);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="fail-container">
        <div class="fail-icon">✗</div>
        <h1 class="fail-title">결제에 실패했습니다</h1>
        <div class="fail-message">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($errorCode)): ?>
                <div class="error-code">오류 코드: <?php echo htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="/dewscent/payment.php" class="form-btn primary">다시 시도</a>
            <a href="/dewscent/" class="form-btn secondary">홈으로 이동</a>
        </div>
    </div>
</body>
</html>

