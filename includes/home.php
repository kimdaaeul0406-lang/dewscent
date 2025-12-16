<!-- 공지사항/이벤트 배너 -->
<section class="notice-section" id="noticeSection" style="display:none;padding:1rem 3rem;background:var(--sage-bg);">
    <div id="noticeBanner" style="max-width:1200px;margin:0 auto;">
        <!-- JS로 공지/이벤트가 동적으로 로드됩니다 -->
    </div>
</section>

<section class="slider-section">
    <div class="slider-container">
        <div class="slider-track" id="sliderTrack">
            <!-- 관리자에서 등록한 배너가 동적으로 로드됩니다 -->
        </div>
    </div>
    <div class="slider-dots" id="sliderDots">
        <!-- 동적으로 생성됩니다 -->
    </div>
</section>

<section class="search-section">
    <div class="search-wrapper">
        <input type="text" class="search-input" placeholder="찾으시는 향기를 검색하세요...">
        <button class="search-btn">검색</button>
    </div>
    <div class="hamburger" onclick="toggleMenu()">
        <span></span><span></span><span></span>
    </div>
</section>

<section class="emotion-section">
    <div class="section-header">
        <p class="emotion-label" id="emotionLabel">FIND YOUR SCENT</p>
        <h2 class="section-title" id="emotionTitle">오늘, 어떤 기분인가요?</h2>
        <p class="emotion-sub" id="emotionSubtitle">감정에 맞는 향기를 추천해드릴게요</p>
    </div>
    <div class="emotion-grid" id="emotionGrid">
        <!-- 관리자에서 등록한 감정 카드가 동적으로 로드됩니다 -->
    </div>
</section>

<section class="best-section">
    <div class="best-header">
        <p class="best-label">BEST SELLERS</p>
        <h2 class="section-title">가장 사랑받는 향기</h2>
        <p class="best-desc">많은 분들이 다시 찾은, 꾸준히 사랑받는 향기입니다.</p>
    </div>
    <div class="products-grid" id="bestProductsGrid"></div>
    <div class="best-footer">
        <a href="pages/products.php?badge=BEST" class="best-more">MORE BEST →</a>
    </div>
</section>

<section class="best-section">
    <div class="best-header">
        <p class="best-label">NEW ARRIVALS</p>
        <h2 class="section-title">새로운 향기</h2>
        <p class="best-desc">막 도착한 신상품을 만나보세요.</p>
    </div>
    <div class="products-grid" id="newProductsGrid"></div>
    <div class="best-footer">
        <a href="pages/products.php?badge=NEW" class="best-more">MORE NEW →</a>
    </div>
</section>

<section class="best-section" id="recentProductsSection" style="display:none;">
    <div class="best-header">
        <p class="best-label">RECENTLY VIEWED</p>
        <h2 class="section-title">최근 본 상품</h2>
        <p class="best-desc">방금 전 본 상품을 다시 확인해보세요</p>
    </div>
    <div class="products-grid" id="recentProducts"></div>
</section>
