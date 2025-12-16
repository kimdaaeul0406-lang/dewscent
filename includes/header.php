<?php
// 어떤 페이지인지 확인 (index.php 에서만 인트로 보여주기)
$currentPage = basename($_SERVER['PHP_SELF']);
$showIntro = ($currentPage === 'index.php');
// pages/ 하위에서 include 된 경우 로고 경로 보정
$inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$basePrefix = $inPages ? '../' : '';
?>

<script>
  window.DS_BASE_URL = "<?php echo rtrim(SITE_URL, '/'); ?>";
</script>

<?php if ($showIntro): ?>
<div class="intro" id="intro">
    <p class="intro-sub">당신의 향기를 찾아서</p>
    <h1 class="intro-logo">DewScent</h1>
</div>
<?php endif; ?>

<header>
    <!-- 왼쪽: 짧은 문장 -->
    <div class="header-left">
        <p>당신의 향기를 찾아서 떠나는 여행을 시작해보세요.</p>
    </div>

    <!-- 가운데: 로고만 심플하게 -->
    <div class="header-center">
        <a href="<?php echo $basePrefix; ?>index.php" class="logo">DewScent</a>
    </div>

    <!-- 오른쪽: 로그인 / 회원가입 / 마이페이지 / 로그아웃 / 장바구니(팝업) -->
    <div class="header-right">
        <a href="#" id="loginLink" onclick="openModal('loginModal'); return false;">로그인</a>
        <a href="#" id="signupLink" onclick="openModal('signupModal'); return false;">회원가입</a>

        <!-- 나중에 백엔드 붙일 때 쓸 마이페이지/로그아웃 자리 -->
        <a href="#" id="mypageLink" style="display:none;" onclick="openMypageTab('profile'); return false;">마이페이지</a>
        <a href="#" id="logoutLink" style="display:none;" onclick="logoutUser(); return false;">로그아웃</a>

        <!-- 무조건 팝업 장바구니로만 동작하게 -->
        <a href="#"
           class="cart-link"
           onclick="renderCart(); openModal('cartModal'); return false;">
            장바구니
            <span class="cart-count" id="cartCount">0</span>
        </a>
    </div>
</header>

<?php include __DIR__ . '/auth_state.php'; ?>
