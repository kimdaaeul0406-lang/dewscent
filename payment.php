<?php
// .env 파일 로드
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/config.php';

// 토스페이먼츠 클라이언트 키 (프론트엔드용)
// getenv() 우선, 없으면 $_ENV에서 읽기 (XAMPP 환경 대응)
$tossClientKey = getenv('TOSS_CLIENT_KEY') ?: ($_ENV['TOSS_CLIENT_KEY'] ?? '');

// 키가 정상적으로 로드되었는지 확인
if (empty($tossClientKey)) {
    // 디버그 모드에서만 로그 출력
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log('[Payment] TOSS_CLIENT_KEY not loaded');
        error_log('[Payment] $_ENV keys: ' . implode(', ', array_keys($_ENV)));
    }
    die('
    <div style="font-family: Arial, sans-serif; padding: 2rem; max-width: 700px; margin: 4rem auto; background: #f8d7da; border: 2px solid #dc3545; border-radius: 8px;">
        <h2 style="color: #721c24; margin-bottom: 1rem;">❌ TOSS_CLIENT_KEY가 설정되지 않았습니다</h2>
        <p style="color: #721c24; margin-bottom: 1rem;">
            프로젝트 루트의 <strong>.env</strong> 파일에 다음 내용을 추가해주세요:
        </p>
        <pre style="background: #fff; padding: 1rem; border-radius: 4px; overflow-x: auto; color: #333; margin-bottom: 1rem;">
TOSS_CLIENT_KEY=test_ck_Z61JOxRQVENnO07bGq72rW0X9bAq
TOSS_SECRET_KEY=test_sk_DLJOpm5QrlLXNxLROKpNrPNdxbWn
TOSS_SECURITY_KEY=4f71f98f8ee426327e65d1c46fdabc3276d6eb1dba75245e5ff1416748dbe61d
        </pre>
        <p style="color: #721c24; font-size: 0.9rem;">
            💡 <strong>.env</strong> 파일은 프로젝트 루트 디렉토리(<code>' . htmlspecialchars(dirname(__DIR__)) . '</code>)에 있어야 합니다.
        </p>
        <p style="color: #721c24; font-size: 0.9rem; margin-top: 0.5rem;">
            파일을 수정한 후 페이지를 새로고침하세요.
        </p>
    </div>
    ');
}

// 키가 정상적으로 로드되었으면 디버그 모드에서만 마스킹된 키 출력
if (defined('APP_DEBUG') && APP_DEBUG) {
    $maskedKey = strlen($tossClientKey) > 6 
        ? substr($tossClientKey, 0, 6) . str_repeat('*', strlen($tossClientKey) - 6)
        : str_repeat('*', strlen($tossClientKey));
    error_log('[Payment] TOSS_CLIENT_KEY loaded: ' . $maskedKey);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제하기 - DewScent</title>
    <link rel="stylesheet" href="public/css/style.css">
    <script src="https://js.tosspayments.com/v1/payment"></script>
    <style>
        .payment-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .payment-header h1 {
            font-family: "Cormorant Garamond", serif;
            font-size: 2rem;
            color: var(--sage);
            margin-bottom: 0.5rem;
        }
        .payment-header p {
            color: var(--light);
            font-size: 0.9rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
            font-size: 0.95rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--sage);
        }
        .payment-methods {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .payment-method {
            flex: 1;
            padding: 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .payment-method:hover {
            border-color: var(--sage-light);
            background: var(--sage-bg);
        }
        .payment-method input[type="radio"] {
            display: none;
        }
        .payment-method input[type="radio"]:checked + label {
            color: var(--sage);
            font-weight: 600;
        }
        .payment-method.selected {
            border-color: var(--sage);
            background: var(--sage-lighter);
        }
        .payment-method label {
            display: block;
            cursor: pointer;
            color: var(--dark);
        }
        .payment-summary {
            background: var(--sage-bg);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        .summary-row.total {
            border-top: 2px solid var(--sage-lighter);
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--sage);
        }
        .form-btn {
            width: 100%;
            padding: 1rem;
            background: var(--sage);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .form-btn:hover {
            background: var(--sage-hover);
        }
        .form-btn:disabled {
            background: var(--muted);
            cursor: not-allowed;
        }
        .error-message {
            background: var(--rose-lighter);
            color: var(--rose);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
    </style>
</head>
<body>
    <?php 
    $currentPage = basename($_SERVER['PHP_SELF']);
    $inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
    $basePrefix = $inPages ? '../' : '';
    include __DIR__ . '/includes/header.php'; 
    ?>
    
    <div class="payment-container">
        <div class="payment-header">
            <h1>결제하기</h1>
            <p>주문 정보를 확인하고 결제를 진행해주세요</p>
        </div>

        <div class="error-message" id="errorMessage"></div>

        <form id="paymentForm">
            <div class="form-group">
                <label class="form-label">주문명</label>
                <input type="text" id="orderName" class="form-input" placeholder="예: DewScent 향수 구매" required>
            </div>

            <div class="form-group">
                <label class="form-label">결제 금액 (원)</label>
                <input type="number" id="amount" class="form-input" placeholder="예: 50000" min="1000" required>
            </div>

            <div class="form-group">
                <label class="form-label">구매자 이름</label>
                <input type="text" id="customerName" class="form-input" placeholder="이름을 입력하세요" required>
            </div>

            <div class="form-group">
                <label class="form-label">구매자 이메일</label>
                <input type="email" id="customerEmail" class="form-input" placeholder="email@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">결제 수단</label>
                <div class="payment-methods">
                    <div class="payment-method selected">
                        <input type="radio" name="paymentMethod" id="methodCard" value="card" checked>
                        <label for="methodCard">카드 결제</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" name="paymentMethod" id="methodVirtual" value="virtual">
                        <label for="methodVirtual">가상계좌</label>
                    </div>
                </div>
            </div>

            <div class="payment-summary">
                <div class="summary-row">
                    <span>주문명</span>
                    <span id="summaryOrderName">-</span>
                </div>
                <div class="summary-row">
                    <span>결제 금액</span>
                    <span id="summaryAmount">₩0</span>
                </div>
                <div class="summary-row total">
                    <span>총 결제금액</span>
                    <span id="summaryTotal">₩0</span>
                </div>
            </div>

            <button type="submit" class="form-btn" id="payButton">결제하기</button>
        </form>
    </div>

    <script>
        const clientKey = '<?php echo htmlspecialchars($tossClientKey, ENT_QUOTES, 'UTF-8'); ?>';
        const paymentMethods = document.querySelectorAll('.payment-method');
        const orderNameInput = document.getElementById('orderName');
        const amountInput = document.getElementById('amount');
        const customerNameInput = document.getElementById('customerName');
        const customerEmailInput = document.getElementById('customerEmail');
        const summaryOrderName = document.getElementById('summaryOrderName');
        const summaryAmount = document.getElementById('summaryAmount');
        const summaryTotal = document.getElementById('summaryTotal');
        const errorMessage = document.getElementById('errorMessage');

        // 결제 수단 선택
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                paymentMethods.forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            });
        });

        // 주문명/금액 실시간 업데이트
        orderNameInput.addEventListener('input', updateSummary);
        amountInput.addEventListener('input', updateSummary);

        function updateSummary() {
            const orderName = orderNameInput.value || '-';
            const amount = parseInt(amountInput.value) || 0;
            
            summaryOrderName.textContent = orderName;
            summaryAmount.textContent = '₩' + amount.toLocaleString();
            summaryTotal.textContent = '₩' + amount.toLocaleString();
        }

        // 폼 제출
        document.getElementById('paymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const orderName = orderNameInput.value.trim();
            const amount = parseInt(amountInput.value);
            const customerName = customerNameInput.value.trim();
            const customerEmail = customerEmailInput.value.trim();
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;

            // 유효성 검사
            if (!orderName || !amount || amount < 1000 || !customerName || !customerEmail) {
                showError('모든 필드를 올바르게 입력해주세요.');
                return;
            }

            const payButton = document.getElementById('payButton');
            payButton.disabled = true;
            payButton.textContent = '처리 중...';

            try {
                // 결제 준비 API 호출
                const response = await fetch('/dewscent/api/payments/ready.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        orderName: orderName,
                        amount: amount,
                        customerName: customerName,
                        customerEmail: customerEmail,
                        paymentMethod: paymentMethod
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || '결제 준비에 실패했습니다.');
                }

                // 토스페이먼츠 SDK 확인
                if (typeof Payment === 'undefined') {
                    console.error('토스페이먼츠 SDK가 로드되지 않았습니다.');
                    showError('결제 시스템을 불러올 수 없습니다. 페이지를 새로고침해주세요.');
                    payButton.disabled = false;
                    payButton.textContent = '결제하기';
                    return;
                }

                // 토스페이먼츠 결제창 호출
                const tossPayments = Payment(clientKey);
                
                if (!tossPayments) {
                    console.error('토스페이먼츠 인스턴스 생성 실패');
                    showError('결제 시스템 초기화에 실패했습니다.');
                    payButton.disabled = false;
                    payButton.textContent = '결제하기';
                    return;
                }

                // URL 정규화 (로컬호스트 대응)
                const baseUrl = window.location.origin;
                const successUrl = data.successUrl.startsWith('http') 
                    ? data.successUrl 
                    : baseUrl + data.successUrl;
                const failUrl = data.failUrl.startsWith('http') 
                    ? data.failUrl 
                    : baseUrl + data.failUrl;

                // 결제 파라미터 준비
                const paymentParams = {
                        amount: data.amount,
                        orderId: data.orderId,
                        orderName: data.orderName,
                        customerName: data.customerName,
                        customerEmail: data.customerEmail,
                        successUrl: successUrl,
                        failUrl: failUrl
                };

                console.log('토스페이먼츠 결제창 호출:', {
                    clientKey: clientKey ? clientKey.substring(0, 10) + '...' : '없음',
                    paymentMethod: paymentMethod,
                    paymentParams: paymentParams,
                    nextRedirectPcUrl: data.nextRedirectPcUrl
                });

                // nextRedirectPcUrl이 있으면 리다이렉트 방식 사용
                if (data.nextRedirectPcUrl) {
                    console.log('리다이렉트 방식으로 결제창 열기:', data.nextRedirectPcUrl);
                    window.location.href = data.nextRedirectPcUrl;
                    return; // 리다이렉트되므로 여기서 종료
                }

                // requestPayment 방식 사용
                try {
                    if (paymentMethod === 'card') {
                        // 카드 결제
                        await tossPayments.requestPayment('카드', paymentParams);
                } else if (paymentMethod === 'virtual') {
                    // 가상계좌
                        await tossPayments.requestPayment('가상계좌', paymentParams);
                    }
                    console.log('결제창 호출 완료');
                } catch (paymentError) {
                    console.error('결제창 호출 오류:', paymentError);
                    let errorMsg = '결제창을 열 수 없습니다.';
                    if (paymentError.message) {
                        errorMsg += '\n' + paymentError.message;
                    }
                    if (paymentError.code) {
                        errorMsg += '\n오류 코드: ' + paymentError.code;
                    }
                    showError(errorMsg);
                    payButton.disabled = false;
                    payButton.textContent = '결제하기';
                    return;
                }
            } catch (error) {
                console.error('Payment error:', error);
                showError(error.message || '결제 처리 중 오류가 발생했습니다.');
                payButton.disabled = false;
                payButton.textContent = '결제하기';
            }
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>

