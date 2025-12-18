<footer>
    <?php
    // 현재 경로가 /pages/ 하위인지에 따라 링크 프리픽스 결정
    $inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
    $basePrefix = $inPages ? '' : 'pages/';
    ?>
    <div class="footer-content">
        <div>
            <p class="footer-brand">DewScent</p>
            <p class="footer-desc">
                당신의 향기를 찾아서<br>
                다시 맡고 싶은 향을 담다<br><br>
                서울시 강남구 테헤란로 123<br>
                Tel. 02-1234-5678<br>
                hello@dewscent.kr
            </p>
        </div>
        <div>
            <p class="footer-title">SHOP</p>
            <div class="footer-links">
                <a href="<?php echo $basePrefix; ?>products.php">전체 상품</a>
                <a href="<?php echo $basePrefix; ?>products.php?category=향수">향수</a>
                <a href="<?php echo $basePrefix; ?>products.php?category=바디미스트">바디미스트</a>
                <a href="<?php echo $basePrefix; ?>products.php?category=헤어미스트">헤어미스트</a>
                <a href="<?php echo $basePrefix; ?>products.php?category=디퓨저">디퓨저</a>
                <a href="<?php echo $basePrefix; ?>products.php?category=섬유유연제">섬유유연제</a>
                <a href="<?php echo $basePrefix; ?>products.php?category=룸스프레이">룸스프레이</a>
            </div>
        </div>
        <div>
            <p class="footer-title">SUPPORT</p>
            <div class="footer-links">
                <a href="<?php echo $basePrefix; ?>support-cs.php">고객센터</a>
                <a href="<?php echo $basePrefix; ?>support-shipping.php">배송안내</a>
                <a href="<?php echo $basePrefix; ?>support-returns.php">교환/반품</a>
                <a href="<?php echo $basePrefix; ?>support-faq.php">FAQ</a>
                <a href="#" onclick="openModal('inquiryModal');return false;">1:1 문의</a>
            </div>
        </div>
        <div>
            <p class="footer-title">ABOUT</p>
            <div class="footer-links">
                <a href="<?php echo $basePrefix; ?>about-brand.php">브랜드 소개</a>
                <a href="<?php echo $basePrefix; ?>about-stores.php">매장 안내</a>
                <a href="<?php echo $basePrefix; ?>about-partnership.php">제휴 문의</a>
                <a href="https://instagram.com" target="_blank" rel="noopener">인스타그램</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?php echo date('Y'); ?> DewScent. All rights reserved.</p>
        <div>
            <a href="#">이용약관</a>
            <a href="#">개인정보처리방침</a>
            <a href="#" onclick="openModal('adminLoginModal'); return false;">관리자</a>
        </div>
    </div>
</footer>
