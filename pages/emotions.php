<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "기분으로 향 찾기 | DewScent";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= htmlspecialchars($pageTitle) ?></title>
	<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Noto+Sans+KR:wght@200;300;400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../public/css/style.css?v=6">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main id="main" class="visible">
	<!-- 검색 섹션 (햄버거 메뉴 포함) -->
	<section class="search-section">
		<div class="search-wrapper">
			<input type="text" class="search-input" placeholder="찾으시는 향기를 검색하세요...">
			<button class="search-btn">검색</button>
		</div>
		<div class="hamburger" onclick="toggleMenu()">
			<span></span><span></span><span></span>
		</div>
	</section>

	<section class="emotion-page-section">
		<div class="page-container">
			<div class="emotion-page-header">
				<p class="emotion-page-label">FIND YOUR SCENT</p>
				<h1 class="emotion-page-title">오늘, 어떤 기분인가요?</h1>
				<p class="emotion-page-sub">감정에 맞는 향기를 추천해드릴게요</p>
			</div>

			<!-- 감정 카드 그리드 -->
			<div class="emotion-grid" id="emotionPageGrid">
				<!-- JavaScript로 동적 로드 -->
			</div>

			<!-- 선택된 감정의 추천 상품 -->
			<div id="emotionProductsSection" style="display:none;margin-top:4rem;">
				<div class="products-grid" id="emotionProductsGrid" style="margin-top:2rem;">
					<!-- 추천 상품이 여기에 표시됩니다 -->
				</div>
			</div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/modals.php'; ?>

<script src="../public/js/api.js"></script>
<script src="../public/js/main.js"></script>
<script>
// URL에서 감정 키 가져오기
function getUrlEmotion() {
	const params = new URLSearchParams(window.location.search);
	return params.get('emotion') || '';
}

// 감정 카드 로드
function loadEmotionCards() {
	const grid = document.getElementById('emotionPageGrid');
	if (!grid) return;

	let emotions = [];
	if (typeof API !== 'undefined' && API.getActiveEmotions) {
		emotions = API.getActiveEmotions();
		emotions = emotions.sort((a, b) => (a.order || 0) - (b.order || 0));
	}

	if (emotions.length === 0) {
		grid.innerHTML = '<p style="text-align:center;color:var(--light);padding:2rem;">관리자에서 감정 카드를 등록해주세요.</p>';
		return;
	}

	const currentEmotion = getUrlEmotion();

	grid.innerHTML = emotions.map(e => {
		const isActive = e.key === currentEmotion;
		return `
			<div class="emotion-card ${e.key} ${isActive ? 'active' : ''}" 
				 data-emotion="${e.key}" 
				 onclick="selectEmotion('${e.key}', ${JSON.stringify(e).replace(/"/g, '&quot;')})">
				<div class="emotion-visual"></div>
				<h3 class="emotion-title">${e.title}</h3>
				<p class="emotion-desc">${e.desc}</p>
			</div>
		`;
	}).join('');

	// URL에 감정이 있으면 자동으로 선택
	if (currentEmotion) {
		const emotion = emotions.find(e => e.key === currentEmotion);
		if (emotion) {
			selectEmotion(currentEmotion, emotion);
		}
	}
}

// 감정 선택
async function selectEmotion(emotionKey, emotionData) {
	// URL 업데이트 (페이지 새로고침 없이)
	window.history.pushState({emotion: emotionKey}, '', `?emotion=${emotionKey}`);
	
	// 활성 카드 업데이트
	document.querySelectorAll('.emotion-card').forEach(card => {
		card.classList.remove('active');
		if (card.dataset.emotion === emotionKey) {
			card.classList.add('active');
		}
	});

	// 추천 상품 로드 (제목은 상단 헤더에 고정, 중복 제거)
	await loadEmotionProducts(emotionKey, emotionData);
}

// 감정별 추천 상품 로드
async function loadEmotionProducts(emotionKey, emotionData) {
	const section = document.getElementById('emotionProductsSection');
	const grid = document.getElementById('emotionProductsGrid');

	if (!section || !grid) return;

	// 상단 헤더 제목 업데이트 (하나만 표시)
	const titleEl = document.querySelector('.emotion-page-title');
	const subEl = document.querySelector('.emotion-page-sub');
	if (titleEl) titleEl.textContent = emotionData.title || '오늘, 어떤 기분인가요?';
	if (subEl) subEl.textContent = emotionData.desc || '감정에 맞는 향기를 추천해드릴게요';

	// 로딩 표시
	grid.innerHTML = '<p style="text-align:center;padding:2rem;color:var(--light);">추천 상품을 불러오는 중...</p>';
	section.style.display = 'block';

	// 추천 상품 가져오기 (전체보기 페이지에서는 10개 추천)
	let recommendations = [];
	if (typeof API !== 'undefined' && API.getEmotionRecommendations) {
		try {
			recommendations = await API.getEmotionRecommendations(emotionKey, 10);
		} catch (e) {
			console.error('추천 상품 로드 실패:', e);
		}
	}

	if (!recommendations || recommendations.length === 0) {
		grid.innerHTML = `
			<div style="grid-column:1/-1;display:flex;align-items:center;justify-content:center;min-height:400px;padding:4rem 2rem;">
				<div style="text-align:center;max-width:500px;">
					<p style="font-size:1.3rem;color:var(--sage);margin-bottom:.8rem;font-weight:500;font-family:'Cormorant Garamond',serif;">상품을 준비중 입니다</p>
					<p style="font-size:.95rem;color:var(--light);line-height:1.6;">이 감정에 맞는 추천 상품이 아직 설정되지 않았습니다.<br>관리자 페이지에서 추천 상품을 설정해주세요.</p>
				</div>
			</div>
		`;
		return;
	}

	// 상품 그리드 렌더링 (기본 스타일과 동일하게)
	grid.innerHTML = recommendations.map((product, index) => {
		const productIndex = typeof products !== 'undefined' ? products.findIndex(p => p.id === product.id) : -1;
		
		const img = product.imageUrl || product.image || "";
		const imageStyle = (() => {
			if (img && img.trim() && img !== "null" && img !== "NULL" && img.length > 10) {
				const imageUrl = img.startsWith("data:") ? img : `'${img}'`;
				return `background-image:url(${imageUrl}) !important;background-size:cover !important;background-position:center !important;background-color:transparent !important;`;
			}
			return "";
		})();
		
		// 품절 체크
		const isSoldOut = (() => {
			if (product.status === "품절") return true;
			if (product.variants && Array.isArray(product.variants) && product.variants.length > 0) {
				const hasStock = product.variants.some((v) => {
					if (v.stock == null || typeof v.stock !== "number") return true;
					return v.stock > 0;
				});
				return !hasStock;
			}
			return product.status !== "판매중";
		})();
		
		return `
			<div class="product-card" onclick="openProductModal(${productIndex >= 0 ? productIndex : index})">
				<div class="product-image" style="position:relative;${imageStyle}">
					${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
					${isSoldOut ? `<div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;border-radius:12px;z-index:1;">
						<span style="background:var(--rose);color:#fff;padding:.5rem 1rem;border-radius:8px;font-weight:600;font-size:.9rem;">품절</span>
					</div>` : ''}
					<button class="product-wishlist" data-id="${product.id}" onclick="event.stopPropagation();toggleWishlist(this)">
						${typeof inWishlist !== 'undefined' && inWishlist(product.id) ? '♥' : '♡'}
					</button>
				</div>
				<div class="product-info">
					<p class="product-brand">DewScent</p>
					${product.category || product.type ? `<p class="product-category">${product.category || product.type}</p>` : ''}
					<p class="product-name">${product.name}</p>
					<div class="product-rating">
						<span class="stars">${'★'.repeat(Math.round(product.rating || 0))}</span>
						<span class="rating-count">(${product.reviews || 0})</span>
					</div>
					<p class="product-price">
						${product.variants && product.variants.length > 0 
							? `₩${(product.variants.find(v => v.is_default)?.price || product.variants[0].price || product.price).toLocaleString()}부터`
							: `₩${(product.price || 0).toLocaleString()}`
						}
					</p>
					${product.variants && product.variants.length > 0 
						? `<p style="font-size:.75rem;color:var(--light);margin-top:.25rem;">${product.variants.length}가지 용량</p>`
						: ''
					}
				</div>
			</div>
		`;
	}).join('');
}

// 페이지 로드 시 초기화
(async function init() {
	// 상품 데이터 로드
	if (typeof loadProducts === 'function') {
		await loadProducts();
	}
	
	// 감정 카드 로드
	loadEmotionCards();
})();
</script>

<style>
.emotion-page-section {
	padding: 4rem 2rem;
	min-height: 80vh;
}

.emotion-page-header {
	text-align: center;
	margin-bottom: 3rem;
}

.emotion-page-label {
	font-size: 0.85rem;
	letter-spacing: 2px;
	color: var(--sage);
	margin-bottom: 0.5rem;
	text-transform: uppercase;
}

.emotion-page-title {
	font-family: 'Cormorant Garamond', serif;
	font-size: 2.5rem;
	color: var(--dark);
	margin-bottom: 0.5rem;
}

.emotion-page-sub {
	font-size: 1rem;
	color: var(--mid);
}

.emotion-card.active {
	border: 2px solid var(--sage);
	background: var(--sage-bg);
}
</style>
</body>
</html>
