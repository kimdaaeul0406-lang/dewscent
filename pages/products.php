<?php
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

// URL 파라미터에서 필터 읽기
function getUrlFilter() {
	const params = new URLSearchParams(window.location.search);
	return params.get('filter') || null;
}

// URL 파라미터에서 검색어 읽기
function getUrlSearch() {
	const params = new URLSearchParams(window.location.search);
	return params.get('search') || null;
}

// 페이지 변경 시 첫 페이지로 리셋
function resetToFirstPage() {
	window.currentPage = 1;
}

// URL 파라미터에서 카테고리 읽기
function getUrlCategory() {
	const params = new URLSearchParams(window.location.search);
	return params.get('category') || null;
}

// URL 파라미터에서 향기 타입 읽기
function getUrlFragrance() {
	const params = new URLSearchParams(window.location.search);
	return params.get('fragrance') || null;
}

// 상품 데이터 (main.js의 products 배열 사용)
function getAllProducts() {
	let allProds = typeof products !== 'undefined' ? products : [];
	const filter = getUrlFilter();
	const search = getUrlSearch();
	const category = getUrlCategory();
	const fragrance = getUrlFragrance();
	
	// 카테고리 필터 적용 (가장 우선)
	if (category) {
		allProds = allProds.filter(p => {
			const pCategory = p.category || p.type || '';
			return pCategory === category;
		});
	}
	
	// 향기 타입 필터 적용
	if (fragrance) {
		allProds = allProds.filter(p => {
			const pFragrance = p.fragrance_type || p.fragranceType || '';
			return pFragrance === fragrance;
		});
	}
	
	// 검색어 필터 적용
	if (search) {
		const searchLower = search.toLowerCase();
		allProds = allProds.filter(p => {
			return p.name.toLowerCase().includes(searchLower) ||
			       (p.desc && p.desc.toLowerCase().includes(searchLower)) ||
			       (p.category && p.category.toLowerCase().includes(searchLower)) ||
			       (p.type && p.type.toLowerCase().includes(searchLower));
		});
	}
	
	// 필터 적용
	if (filter === 'best') {
		// BEST 배지가 있거나 리뷰 수가 많은 상품 (인기 상품)
		allProds = allProds.filter(p => {
			return p.badge === 'BEST' || (p.reviews && p.reviews >= 50);
		});
		// 리뷰 수 기준으로 정렬 (인기순)
		allProds.sort((a, b) => (b.reviews || 0) - (a.reviews || 0));
	} else if (filter === 'new') {
		// NEW 배지가 있는 상품
		allProds = allProds.filter(p => p.badge === 'NEW');
		// 최신순 정렬
		allProds.sort((a, b) => (b.id || 0) - (a.id || 0));
	}
	
	return allProds;
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

// 페이징 변수
window.currentPage = 1;
const PRODUCTS_PER_PAGE = 24; // 한 페이지에 24개씩 표시

function renderAllProducts(page) {
	if (!page) page = window.currentPage || 1;
	window.currentPage = page;
	const container = document.getElementById('allProductsGrid');
	const countEl = document.getElementById('productCount');
	if (!container) return;

	const allProds = getAllProducts();
	
	// 필터가 적용된 경우 정렬은 이미 getAllProducts에서 처리됨
	const sorted = getUrlFilter() ? allProds : sortProducts(allProds, currentSort);
	
	// 페이지 제목 업데이트
	const filter = getUrlFilter();
	const search = getUrlSearch();
	const category = getUrlCategory();
	const fragrance = getUrlFragrance();
	const titleEl = document.querySelector('.products-hero-title');
	const subEl = document.querySelector('.products-hero-sub');
	
	if (search) {
		if (titleEl) titleEl.textContent = `"${search}" 검색 결과`;
		if (subEl) subEl.textContent = `${sorted.length}개의 상품을 찾았습니다`;
	} else if (category) {
		if (titleEl) titleEl.textContent = `${category} 상품`;
		if (subEl) subEl.textContent = `${sorted.length}개의 ${category} 상품을 만나보세요`;
	} else if (fragrance) {
		if (titleEl) titleEl.textContent = `${fragrance} 향기`;
		if (subEl) subEl.textContent = `${sorted.length}개의 ${fragrance} 향기 상품을 만나보세요`;
	} else if (filter === 'best') {
		if (titleEl) titleEl.textContent = '많이 사랑받는 향기들';
		if (subEl) subEl.textContent = '베스트셀러와 인기 상품을 만나보세요';
	} else if (filter === 'new') {
		if (titleEl) titleEl.textContent = '새로 피어난 향기';
		if (subEl) subEl.textContent = '새롭게 출시된 향기를 만나보세요';
	}

	// 페이징 적용
	const totalCount = sorted.length;
	const startIdx = (page - 1) * PRODUCTS_PER_PAGE;
	const endIdx = startIdx + PRODUCTS_PER_PAGE;
	const paginatedProducts = sorted.slice(startIdx, endIdx);
	
	countEl.textContent = totalCount;

	// 상품이 없을 때 메시지 표시
	if (totalCount === 0) {
		container.className = 'products-grid';
		container.innerHTML = `
			<div style="grid-column:1/-1;display:flex;align-items:center;justify-content:center;min-height:400px;padding:4rem 2rem;">
				<div style="text-align:center;max-width:500px;">
					<p style="font-size:1.3rem;color:var(--sage);margin-bottom:.8rem;font-weight:500;font-family:'Cormorant Garamond',serif;">상품을 준비중 입니다</p>
					<p style="font-size:.95rem;color:var(--light);line-height:1.6;">곧 새로운 상품을 만나보실 수 있습니다.</p>
				</div>
			</div>
		`;
		return;
	}

		if (currentView === 'grid') {
		container.className = 'products-grid';
		container.innerHTML = paginatedProducts.map((product, idx) => {
			const img = product.imageUrl || product.image || '';
			const hasImage = img && img.trim() && img !== 'null' && img !== 'NULL' && img.length > 10;
			const imgSrc = hasImage ? img.replace(/'/g, '') : '';
			return `
			<div class="product-card" onclick="openProductModal(${products.indexOf(product)})">
				<div class="product-image" style="position:relative;background-color:var(--sage-lighter);overflow:hidden;">
					${hasImage ? `<img src="${imgSrc}" loading="lazy" width="300" height="375" alt="${(product.name || '').replace(/"/g, '&quot;')}" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.style.display='none';this.parentElement.style.background='var(--sage-lighter)';">` : ''}
					${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
					<button class="product-wishlist" data-id="${product.id}" onclick="event.stopPropagation();toggleWishlist(this)">
						${inWishlist(product.id) ? '♥' : '♡'}
					</button>
				</div>
				<div class="product-info">
					<p class="product-brand">DewScent</p>
					${product.category || product.type ? `<p class="product-category">${product.category || product.type}</p>` : ''}
					<p class="product-name">${product.name}</p>
					<div class="product-rating">
						<span class="stars">${'★'.repeat(Math.round(product.rating))}</span>
						<span class="count">(${product.reviews})</span>
					</div>
					<p class="product-price">
						${product.variants && product.variants.length > 0 
							? `₩${(product.variants.find(v => v.is_default)?.price || product.variants[0].price || product.price).toLocaleString()}부터`
							: `₩${product.price.toLocaleString()}`
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
	} else {
		container.className = 'products-list';
		container.innerHTML = paginatedProducts.map((product, idx) => {
			const img = product.imageUrl || product.image || '';
			const hasImage = img && img.trim() && img !== 'null' && img !== 'NULL' && img.length > 10;
			const imgSrc = hasImage ? img.replace(/'/g, '') : '';
			return `
			<div class="product-list-item" onclick="openProductModal(${products.indexOf(product)})">
				<div class="product-list-image" style="background-color:var(--sage-lighter);overflow:hidden;">
					${hasImage ? `<img src="${imgSrc}" loading="lazy" width="150" height="150" alt="${(product.name || '').replace(/"/g, '&quot;')}" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.style.display='none';this.parentElement.style.background='var(--sage-lighter)';">` : ''}
					${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
				</div>
				<div class="product-list-info">
					<div class="product-list-top">
						<p class="product-brand">DewScent</p>
						${product.category || product.type ? `<p class="product-category">${product.category || product.type}</p>` : ''}
						<p class="product-name">${product.name}</p>
						<p class="product-desc">${product.desc}</p>
					</div>
					<div class="product-list-bottom">
						<div class="product-rating">
							<span class="stars">${'★'.repeat(Math.round(product.rating))}</span>
							<span class="count">(${product.reviews}개 리뷰)</span>
						</div>
						<div>
							<p class="product-price">
								${product.variants && product.variants.length > 0 
									? `₩${(product.variants.find(v => v.is_default)?.price || product.variants[0].price || product.price).toLocaleString()}부터`
									: `₩${product.price.toLocaleString()}`
								}
							</p>
							${product.variants && product.variants.length > 0 
								? `<p style="font-size:.75rem;color:var(--light);margin-top:.25rem;">${product.variants.length}가지 용량</p>`
								: ''
							}
						</div>
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
	
	// 페이징 컨트롤 추가
	renderPagination(totalCount, page);
}

// 페이징 컨트롤 렌더링
function renderPagination(totalCount, currentPage) {
	const totalPages = Math.ceil(totalCount / PRODUCTS_PER_PAGE);
	if (totalPages <= 1) return; // 페이지가 1개 이하면 페이징 표시 안 함
	
	const container = document.getElementById('allProductsGrid');
	if (!container) return;
	
	// 기존 페이징 제거
	const existingPagination = container.parentElement.querySelector('.products-pagination');
	if (existingPagination) {
		existingPagination.remove();
	}
	
	// 페이징 생성
	const pagination = document.createElement('div');
	pagination.className = 'products-pagination';
	pagination.style.cssText = 'grid-column:1/-1;display:flex;justify-content:center;align-items:center;gap:0.5rem;margin-top:2rem;padding:1rem;';
	
	// 이전 버튼
	const prevBtn = document.createElement('button');
	prevBtn.textContent = '이전';
	prevBtn.disabled = currentPage === 1;
	prevBtn.style.cssText = 'padding:0.5rem 1rem;border:1px solid var(--border);background:white;border-radius:4px;cursor:pointer;';
	if (!prevBtn.disabled) {
		prevBtn.style.cursor = 'pointer';
		prevBtn.onclick = () => {
			window.currentPage = currentPage - 1;
			renderAllProducts(window.currentPage);
			window.scrollTo({ top: 0, behavior: 'smooth' });
		};
	} else {
		prevBtn.style.cursor = 'not-allowed';
		prevBtn.style.opacity = '0.5';
	}
	
	// 페이지 번호
	const pageInfo = document.createElement('span');
	pageInfo.textContent = `${currentPage} / ${totalPages}`;
	pageInfo.style.cssText = 'padding:0 1rem;color:var(--mid);';
	
	// 다음 버튼
	const nextBtn = document.createElement('button');
	nextBtn.textContent = '다음';
	nextBtn.disabled = currentPage >= totalPages;
	nextBtn.style.cssText = 'padding:0.5rem 1rem;border:1px solid var(--border);background:white;border-radius:4px;cursor:pointer;';
	if (!nextBtn.disabled) {
		nextBtn.style.cursor = 'pointer';
		nextBtn.onclick = () => {
			window.currentPage = currentPage + 1;
			renderAllProducts(window.currentPage);
			window.scrollTo({ top: 0, behavior: 'smooth' });
		};
	} else {
		nextBtn.style.cursor = 'not-allowed';
		nextBtn.style.opacity = '0.5';
	}
	
	pagination.appendChild(prevBtn);
	pagination.appendChild(pageInfo);
	pagination.appendChild(nextBtn);
	
	// 컨테이너 다음에 추가
	container.parentElement.appendChild(pagination);
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
document.getElementById('productSort')?.addEventListener('change', (e) => {
	currentSort = e.target.value;
	resetToFirstPage();
	renderAllProducts(1);
});

// 뷰 토글
document.querySelectorAll('.view-btn').forEach(btn => {
	btn.addEventListener('click', () => {
		document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
		btn.classList.add('active');
		currentView = btn.dataset.view;
		resetToFirstPage();
		renderAllProducts(1);
	});
});
});

// 초기 렌더링 (상품 로드 완료 후)
document.addEventListener('DOMContentLoaded', async () => {
	// 상품 개수 표시 먼저 업데이트 (placeholder 제거)
	const countEl = document.getElementById('productCount');
	if (countEl) countEl.textContent = '0';
	
	try {
		// main.js의 loadProducts()가 완료될 때까지 기다림
		await loadProducts();
		
		// 에러가 발생했는지 확인
		if (window.productLoadError) {
			const container = document.getElementById('allProductsGrid');
			if (container) {
				container.innerHTML = `
					<div style="grid-column:1/-1;display:flex;align-items:center;justify-content:center;min-height:400px;padding:4rem 2rem;">
						<div style="text-align:center;max-width:500px;">
							<p style="font-size:1.3rem;color:var(--rose);margin-bottom:.8rem;font-weight:500;font-family:'Cormorant Garamond',serif;">상품을 불러오는 중 오류가 발생했습니다</p>
							<p style="font-size:.95rem;color:var(--light);line-height:1.6;margin-bottom:1rem;">${window.productLoadError}</p>
							<p style="font-size:.85rem;color:var(--light);line-height:1.6;">페이지를 새로고침하거나 잠시 후 다시 시도해주세요.</p>
						</div>
					</div>
				`;
			}
			console.error('[products.php] 상품 로드 에러:', window.productLoadError);
		} else {
			renderAllProducts(1);
		}
	} catch (e) {
		console.error('[products.php] 렌더링 에러:', e);
		const container = document.getElementById('allProductsGrid');
		const countEl = document.getElementById('productCount');
		if (container) {
			container.innerHTML = `
				<div style="grid-column:1/-1;display:flex;align-items:center;justify-content:center;min-height:400px;padding:4rem 2rem;">
					<div style="text-align:center;max-width:500px;">
						<p style="font-size:1.3rem;color:var(--rose);margin-bottom:.8rem;font-weight:500;font-family:'Cormorant Garamond',serif;">오류가 발생했습니다</p>
						<p style="font-size:.95rem;color:var(--light);line-height:1.6;">${e.message || '알 수 없는 오류가 발생했습니다.'}</p>
					</div>
				</div>
			`;
		}
		if (countEl) countEl.textContent = '0';
	}
	
	// Lazy loading 초기화
	if (typeof initLazyLoading === 'function') {
		initLazyLoading();
	}
});
</script>
</body>
</html>

