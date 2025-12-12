<?php
// cart.php : 세션 기반 장바구니 페이지

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/data/products.php';

// -----------------------------
// 장바구니 초기화
// -----------------------------
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 장바구니에 상품 추가
function cart_add($product_id, $qty = 1)
{
    $product = find_product_by_id($product_id);
    if (!$product) return;

    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['qty'] += $qty;
            return;
        }
    }

    $_SESSION['cart'][] = [
        'id'    => $product['id'],
        'name'  => $product['name'],
        'price' => $product['price'],
        'qty'   => $qty,
    ];
}

// 수량 변경
function cart_update($product_id, $qty)
{
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['id'] == $product_id) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$index]); // 0 이하면 삭제
            } else {
                $_SESSION['cart'][$index]['qty'] = $qty;
            }
            return;
        }
    }
}

// 상품 삭제
function cart_remove($product_id)
{
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['id'] == $product_id) {
            unset($_SESSION['cart'][$index]);
            return;
        }
    }
}

// -----------------------------
// 요청 처리 (POST)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'add') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        if ($pid > 0) {
            cart_add($pid, max(1, $qty));
        }
        header('Location: cart.php');
        exit;
    }

    if ($action === 'update') {
        if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $pid => $q) {
                cart_update((int)$pid, (int)$q);
            }
        }
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove') {
        $pid = (int)($_POST['product_id'] ?? 0);
        if ($pid > 0) {
            cart_remove($pid);
        }
        header('Location: cart.php');
        exit;
    }
}

// -----------------------------
// 합계 계산
// -----------------------------
$cart = array_values($_SESSION['cart']);
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$shipping = $subtotal >= 50000 ? 0 : ($subtotal > 0 ? 3000 : 0);
$total = $subtotal + $shipping;

$pageTitle = "장바구니 | DewScent";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- 폰트 (index.php와 동일) -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Noto+Sans+KR:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- 공통 스타일 (캐시 방지용 버전 파라미터) -->
    <link rel="stylesheet" href="public/css/style.css?v=6">
</head>
<body class="cart-page">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- ✅ 인트로 없이 바로 보이게 -->
    <main id="main" class="visible">
        <!-- ✅ home의 best-section 스타일 재사용 (전체 그라데이션) -->
        <section class="best-section">
            <div class="best-header">
                <h2 class="section-title">장바구니</h2>
                <a href="index.php" class="view-all">쇼핑 계속하기</a>
            </div>

            <?php if (empty($cart)): ?>
                <!-- ✅ 이모지 제거, 깔끔하게 텍스트만 -->
                <div class="cart-empty">
                    <p>장바구니가 비어 있습니다.</p>
                    <a href="index.php" class="form-btn ivory" style="margin-top:1rem;">
                        쇼핑 계속하기
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-layout" style="display:flex;gap:2rem;align-items:flex-start;">
                    <!-- 왼쪽: 상품 목록 -->
                    <form method="post" action="cart.php" style="flex:2;">
                        <input type="hidden" name="action" value="update">

                        <div class="cart-items">
                            <?php foreach ($cart as $item): ?>
                                <div class="cart-item">
                                    <div class="cart-item-image"></div>
                                    <div class="cart-item-info">
                                        <p class="cart-item-name">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </p>
                                        <p class="cart-item-price">
                                            ₩<?= number_format($item['price']) ?> × <?= $item['qty'] ?>
                                            = <strong>₩<?= number_format($item['price'] * $item['qty']) ?></strong>
                                        </p>
                                        <div class="cart-item-qty">
                                            <span style="font-size:0.85rem;color:var(--mid);margin-right:.5rem;">
                                                수량
                                            </span>
                                            <input
                                                type="number"
                                                name="qty[<?= $item['id'] ?>]"
                                                value="<?= $item['qty'] ?>"
                                                min="0"
                                                style="width:60px;padding:0.25rem;text-align:center;border:1px solid var(--border);border-radius:999px;"
                                            >
                                            <!-- 삭제 버튼은 별도 폼 -->
                                            <form method="post" action="cart.php" style="display:inline-block;margin-left:1rem;">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="cart-item-remove">
                                                    삭제
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top:1rem;text-align:right;">
                            <button type="submit" class="form-btn secondary" style="font-size:0.85rem;">
                                수량 변경 적용
                            </button>
                        </div>
                    </form>

                    <!-- 오른쪽: 합계 박스 -->
                    <div class="cart-summary" style="flex:1;">
                        <div class="cart-row">
                            <span>상품 금액</span>
                            <span>₩<?= number_format($subtotal) ?></span>
                        </div>
                        <div class="cart-row">
                            <span>배송비</span>
                            <span>
                                <?php if ($shipping === 0): ?>
                                    무료
                                <?php else: ?>
                                    ₩<?= number_format($shipping) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="cart-row total">
                            <span>결제 예정 금액</span>
                            <span>₩<?= number_format($total) ?></span>
                        </div>

                        <div class="cart-actions" style="margin-top:1.5rem;display:flex;flex-direction:column;gap:.75rem;">
                            <a href="index.php" class="form-btn ivory">
                                쇼핑 계속하기
                            </a>
                            <!-- checkout.php는 다음 단계에서 만들 예정 -->
                            <a href="checkout.php" class="form-btn primary">
                                주문 / 결제 진행
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/includes/modals.php'; ?>

    <script src="public/js/api.js?v=4"></script>
    <script src="public/js/main.js?v=4"></script>
</body>
</html>
