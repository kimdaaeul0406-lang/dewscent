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
        <p class="best-label" id="bestLabel">MOST LOVED</p>
        <h2 class="section-title" id="bestTitle">다시 찾게 되는 향기</h2>
        <p class="best-desc" id="bestSubtitle">한 번 스친 향기가 오래 기억에 남을 때가 있어요.<br>많은 분들이 다시 찾은 향기를 소개합니다.</p>
    </div>
    <div class="products-grid" id="productsGrid"></div>
    <div class="best-footer">
        <p class="best-quote" id="bestQuote">— 향기는 기억을 여는 열쇠 —</p>
        <a href="pages/products.php" class="best-more">MORE SCENTS →</a>
    </div>
</section>
