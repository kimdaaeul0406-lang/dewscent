<!-- 사이드 메뉴 오버레이 -->
<div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div>

<?php
// 사이드바 링크용 경로 설정
$sbInPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$sbPrefix = $sbInPages ? '' : 'pages/';
?>

<!-- 사이드 메뉴 -->
<nav class="side-menu" id="sideMenu">
    <div class="menu-header">
        <span class="menu-logo">DewScent</span>
        <button class="menu-close" onclick="toggleMenu()">×</button>
    </div>

    <div class="menu-body">
        <div class="menu-section">
            <h3 class="menu-title">
                마이페이지 <span class="menu-badge">장바구니</span>
            </h3>
            <div class="menu-links">
                <a href="#" onclick="toggleMenu();openModal('cartModal')">장바구니 보기</a>
                <a href="#" onclick="toggleMenu();openMypageTab('orders');return false;">주문내역</a>
                <a href="#" onclick="toggleMenu();openWishlist();return false;">위시리스트</a>
                <a href="#" onclick="toggleMenu();openModal('inquiryModal');return false;">1:1 문의하기</a>
                <a href="#" onclick="toggleMenu();openInquiryList();return false;">내 문의내역</a>
            </div>
        </div>

        <div class="menu-section">
            <h3 class="menu-title">당신의 향기를 찾아서</h3>
            <div class="menu-links">
                <a href="<?php echo $sbPrefix; ?>products.php" style="font-weight:600;color:var(--sage)">전체 보기</a>
                <a href="<?php echo $sbPrefix; ?>products.php?filter=best" onclick="toggleMenu();">많이 사랑받는 향기들</a>
                <a href="<?php echo $sbPrefix; ?>products.php?filter=new" onclick="toggleMenu();">새로 피어난 향기</a>
            </div>
        </div>

        <p class="menu-category">기분으로 향 찾기</p>
        <div class="menu-sub-links">
            <a href="#">차분해지고 싶은 날</a>
            <a href="#">따뜻함이 필요한 순간</a>
            <a href="#">설레고 싶은 아침</a>
            <a href="#">집중하고 싶은 시간</a>
        </div>

        <p class="menu-category">향으로 찾기</p>
        <div class="menu-sub-links">
            <a href="#">시트러스</a>
            <a href="#">플로럴</a>
            <a href="#">우디</a>
            <a href="#">머스크</a>
            <a href="#">오리엔탈</a>
            <a href="#">프레시</a>
        </div>

        <p class="menu-category">제품으로 찾기</p>
        <div class="menu-sub-links">
            <a href="#">향수</a>
            <a href="#">바디미스트</a>
            <a href="#">헤어미스트</a>
            <a href="#">디퓨저</a>
            <a href="#">섬유유연제</a>
            <a href="#">룸스프레이</a>
        </div>

        <div style="margin-top:3rem;padding-top:1.5rem;border-top:1px solid var(--border)">
            <div style="display:flex;gap:1rem;font-size:.85rem;align-items:center;flex-wrap:wrap">
                <a href="#" id="sbLoginLink" onclick="toggleMenu();openModal('loginModal')" style="color:var(--mid);text-decoration:none">로그인</a>
                <span id="sbDivider" style="color:var(--muted)">|</span>
                <a href="#" id="sbSignupLink" onclick="toggleMenu();openModal('signupModal')" style="color:var(--mid);text-decoration:none">회원가입</a>
                <a href="#" id="sbMypageLink" style="display:none;color:var(--mid);text-decoration:none" onclick="toggleMenu();openMypageTab('profile');return false;">마이페이지</a>
                <a href="#" id="sbLogoutLink" style="display:none;color:var(--mid);text-decoration:none" onclick="toggleMenu();logoutUser();return false;">로그아웃</a>
            </div>
            <p style="font-size:.7rem;color:var(--muted);margin-top:1rem">© DewScent</p>
        </div>
    </div>
</nav>
