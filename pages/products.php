<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "전체 상품 | DewScent";
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
	<section class="best-section">
		<div class="page-container">
			<div class="products-hero">
				<p class="products-hero-label">ALL SCENTS</p>
				<h1 class="products-hero-title">모든 향기를 만나보세요</h1>
				<p class="products-hero-sub">당신만의 향기를 찾는 여정</p>
			</div>

			<!-- 필터/정렬 바 -->
			<div class="products-toolbar">
				<div class="products-toolbar-left">
					<div class="view-toggle">
						<button class="view-btn active" data-view="grid" title="카드형">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="3" width="7" height="7"></rect>
								<rect x="14" y="3" width="7" height="7"></rect>
								<rect x="3" y="14" width="7" height="7"></rect>
								<rect x="14" y="14" width="7" height="7"></rect>
							</svg>
						</button>
						<button class="view-btn" data-view="list" title="리스트형">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<line x1="8" y1="6" x2="21" y2="6"></line>
								<line x1="8" y1="12" x2="21" y2="12"></line>
								<line x1="8" y1="18" x2="21" y2="18"></line>
								<line x1="3" y1="6" x2="3.01" y2="6"></line>
								<line x1="3" y1="12" x2="3.01" y2="12"></line>
								<line x1="3" y1="18" x2="3.01" y2="18"></line>
							</svg>
						</button>
					</div>
					<span class="products-count">총 <strong id="productCount">0</strong>개</span>
				</div>
				<div class="products-toolbar-right">
					<select class="products-sort" id="productSort">
						<option value="popular">인기순</option>
						<option value="newest">최신순</option>
						<option value="price-low">낮은 가격순</option>
						<option value="price-high">높은 가격순</option>
					</select>
					<div class="toolbar-hamburger" onclick="toggleMenu()">
						<span></span><span></span><span></span>
					</div>
				</div>
			</div>

			<!-- 상품 그리드/리스트 -->
			<div class="products-grid" id="allProductsGrid"></div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/modals.php'; ?>
<script src="../public/js/api.js?v=4"></script>
<script src="../public/js/main.js?v=4"></script>
<script>
// 전체 상품 페이지 전용 스크립트
let currentView = 'grid';
let currentSort = 'popular';

// 상품 데이터 (main.js의 products 배열 사용)
function getAllProducts() {
	return typeof products !== 'undefined' ? products : [];
}

function sortProducts(list, sortBy) {
	const sorted = [...list];
	switch(sortBy) {
		case 'popular':
			// 리뷰 수 기준 (인기순)
			sorted.sort((a, b) => b.reviews - a.reviews);
			break;
		case 'newest':
			// ID 역순 (최신순 - 실제로는 createdAt 사용)
			sorted.sort((a, b) => b.id - a.id);
			break;
		case 'price-low':
			sorted.sort((a, b) => a.price - b.price);
			break;
		case 'price-high':
			sorted.sort((a, b) => b.price - a.price);
			break;
	}
	return sorted;
}

function renderAllProducts() {
	const container = document.getElementById('allProductsGrid');
	const countEl = document.getElementById('productCount');
	if (!container) return;

	const allProds = getAllProducts();
	const sorted = sortProducts(allProds, currentSort);

	countEl.textContent = sorted.length;

	if (currentView === 'grid') {
		container.className = 'products-grid';
		container.innerHTML = sorted.map((product, idx) => `
			<div class="product-card" onclick="openProductModal(${products.indexOf(product)})">
				<div class="product-image">
					${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
					<button class="product-wishlist" data-id="${product.id}" onclick="event.stopPropagation();toggleWishlist(this)">
						${inWishlist(product.id) ? '♥' : '♡'}
					</button>
				</div>
				<div class="product-info">
					<p class="product-brand">DewScent</p>
					<p class="product-name">${product.name}</p>
					<div class="product-rating">
						<span class="stars">${'★'.repeat(Math.round(product.rating))}</span>
						<span class="count">(${product.reviews})</span>
					</div>
					<p class="product-price">₩${product.price.toLocaleString()}</p>
				</div>
			</div>
		`).join('');
	} else {
		container.className = 'products-list';
		container.innerHTML = sorted.map((product, idx) => `
			<div class="product-list-item" onclick="openProductModal(${products.indexOf(product)})">
				<div class="product-list-image">
					${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
				</div>
				<div class="product-list-info">
					<div class="product-list-top">
						<p class="product-brand">DewScent</p>
						<p class="product-name">${product.name}</p>
						<p class="product-desc">${product.desc}</p>
					</div>
					<div class="product-list-bottom">
						<div class="product-rating">
							<span class="stars">${'★'.repeat(Math.round(product.rating))}</span>
							<span class="count">(${product.reviews}개 리뷰)</span>
						</div>
						<p class="product-price">₩${product.price.toLocaleString()}</p>
					</div>
				</div>
				<div class="product-list-actions">
					<button class="product-wishlist" data-id="${product.id}" onclick="event.stopPropagation();toggleWishlist(this)">
						${inWishlist(product.id) ? '♥' : '♡'}
					</button>
					<button class="product-list-cart" onclick="event.stopPropagation();quickAddToCart(${product.id})">담기</button>
				</div>
			</div>
		`).join('');
	}
}

// 빠른 장바구니 추가
function quickAddToCart(productId) {
	const product = products.find(p => p.id === productId);
	if (!product) return;
	
	const cart = JSON.parse(localStorage.getItem('dewscent_cart') || '[]');
	const existing = cart.find(item => item.id === productId);
	if (existing) {
		existing.qty += 1;
	} else {
		cart.push({ id: productId, name: product.name, price: product.price, qty: 1, size: '30ml', type: '향수' });
	}
	localStorage.setItem('dewscent_cart', JSON.stringify(cart));
	
	// 장바구니 카운트 업데이트
	const countEl = document.getElementById('cartCount');
	if (countEl) countEl.textContent = cart.reduce((sum, item) => sum + item.qty, 0);
	
	alert(`${product.name}이(가) 장바구니에 담겼습니다.`);
}

// 정렬 변경
document.getElementById('productSort').addEventListener('change', (e) => {
	currentSort = e.target.value;
	renderAllProducts();
});

// 뷰 토글
document.querySelectorAll('.view-btn').forEach(btn => {
	btn.addEventListener('click', () => {
		document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
		btn.classList.add('active');
		currentView = btn.dataset.view;
		renderAllProducts();
	});
});

// 초기 렌더링 (상품 로드 완료 후)
document.addEventListener('DOMContentLoaded', async () => {
	// main.js의 loadProducts()가 완료될 때까지 기다림
	await loadProducts();
	renderAllProducts();
});
</script>
</body>
</html>

