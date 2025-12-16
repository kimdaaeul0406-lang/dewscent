<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "상품 상세 | DewScent";
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
			<div id="productDetailContainer" style="min-height:400px;display:flex;align-items:center;justify-content:center;">
				<p style="color:var(--light);">상품 정보를 불러오는 중...</p>
			</div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include __DIR__ . '/../includes/modals.php'; ?>
<script src="../public/js/api.js?v=4"></script>
<script src="../public/js/main.js?v=4"></script>
<script>
// URL에서 상품 ID 가져오기
function getProductIdFromUrl() {
	const params = new URLSearchParams(window.location.search);
	return params.get('id') || null;
}

// 상품 상세 정보 렌더링
async function renderProductDetail() {
	const container = document.getElementById('productDetailContainer');
	if (!container) return;
	
	const productId = getProductIdFromUrl();
	if (!productId) {
		container.innerHTML = `
			<div style="text-align:center;padding:3rem;">
				<p style="color:var(--mid);font-size:1.1rem;margin-bottom:1rem;">상품을 찾을 수 없습니다.</p>
				<a href="products.php" class="form-btn primary">상품 목록으로</a>
			</div>
		`;
		return;
	}
	
	// 상품 정보 가져오기
	let product = null;
	
	// 먼저 products 배열에서 찾기
	if (typeof products !== 'undefined' && products.length > 0) {
		product = products.find(p => p.id === parseInt(productId));
	}
	
	// products 배열에 없으면 API에서 가져오기
	if (!product && typeof API !== 'undefined' && API.getProduct) {
		product = await API.getProduct(parseInt(productId));
		// API에서 가져온 상품을 products 배열에 추가 (없는 경우)
		if (product && typeof products !== 'undefined' && !products.find(p => p.id === product.id)) {
			products.push(product);
		}
	}
	
	// 여전히 없으면 API.getProducts()로 전체 목록에서 찾기
	if (!product && typeof API !== 'undefined' && API.getProducts) {
		const allProducts = await API.getProducts();
		product = allProducts.find(p => p.id === parseInt(productId));
		if (product && typeof products !== 'undefined') {
			products = allProducts;
		}
	}
	
	if (!product) {
		container.innerHTML = `
			<div style="text-align:center;padding:3rem;">
				<p style="color:var(--mid);font-size:1.1rem;margin-bottom:1rem;">상품을 찾을 수 없습니다.</p>
				<p style="color:var(--light);font-size:.9rem;margin-bottom:1.5rem;">상품 ID: ${productId}</p>
				<a href="products.php" class="form-btn primary">상품 목록으로</a>
			</div>
		`;
		return;
	}
	
	// 최근 본 상품에 추가
	if (typeof addToRecentProducts === 'function') {
		addToRecentProducts(product.id);
	} else if (typeof addRecentlyViewed === 'function') {
		addRecentlyViewed(product.id);
	}
	
	// 상품 상세 정보 렌더링
	const productIndex = typeof products !== 'undefined' ? products.findIndex(p => p.id === product.id) : -1;
	
	container.innerHTML = `
		<div style="display:flex;gap:0;max-width:900px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
			<!-- 상품 이미지 -->
			<div style="width:45%;min-height:500px;background:${product.imageUrl ? `url(${product.imageUrl})` : 'linear-gradient(135deg,var(--sage-lighter),var(--sage-light))'};background-size:cover;background-position:center;position:relative;">
				${product.badge ? `<span class="product-badge" style="position:absolute;top:1rem;left:1rem;">${product.badge}</span>` : ''}
				<button class="product-wishlist" data-id="${product.id}" onclick="toggleWishlist(this)" style="position:absolute;top:1rem;right:1rem;">
					${typeof inWishlist === 'function' && inWishlist(product.id) ? '♥' : '♡'}
				</button>
			</div>
			
			<!-- 상품 정보 -->
			<div style="width:55%;padding:2rem;display:flex;flex-direction:column;position:relative;">
				<p style="font-size:.8rem;color:var(--light);margin-bottom:.3rem;">DewScent</p>
				<h1 style="font-size:1.5rem;font-weight:500;color:var(--dark);margin-bottom:.5rem;">${product.name}</h1>
				
				<div class="product-modal-rating" style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;">
					<span class="stars" style="color:var(--ivory);">${'★'.repeat(Math.round(product.rating || 4))}</span>
					<span style="font-size:.8rem;color:var(--light);">${product.rating || 4} (${product.reviews || 0}개 리뷰)</span>
				</div>
				
				<p style="font-size:1.4rem;font-weight:600;color:var(--sage);margin-bottom:1.5rem;">
					₩${product.price.toLocaleString()}
					${product.originalPrice ? `<span style="font-size:1rem;color:var(--light);text-decoration:line-through;margin-left:.5rem;">₩${product.originalPrice.toLocaleString()}</span>` : ''}
				</p>
				
				${product.desc ? `
				<p style="font-size:.9rem;color:var(--mid);line-height:1.8;margin-bottom:1.5rem;">${product.desc}</p>
				` : ''}
				
				<!-- 용량 선택 -->
				<div class="product-options" style="margin-bottom:1.5rem;">
					<p class="option-label" style="font-size:.85rem;font-weight:500;margin-bottom:.8rem;">용량 선택</p>
					<div class="option-btns" style="display:flex;gap:.5rem;flex-wrap:wrap;">
						<button class="option-btn selected" data-size="30" onclick="selectProductSize(this, 30)" style="padding:.6rem 1.2rem;border:1px solid var(--sage);background:var(--sage-bg);border-radius:8px;font-size:.85rem;cursor:pointer;color:var(--sage);">30ml</button>
						<button class="option-btn" data-size="50" onclick="selectProductSize(this, 50)" style="padding:.6rem 1.2rem;border:1px solid var(--border);background:#fff;border-radius:8px;font-size:.85rem;cursor:pointer;transition:all 0.3s;">50ml</button>
						<button class="option-btn" data-size="100" onclick="selectProductSize(this, 100)" style="padding:.6rem 1.2rem;border:1px solid var(--border);background:#fff;border-radius:8px;font-size:.85rem;cursor:pointer;transition:all 0.3s;">100ml</button>
					</div>
				</div>
				
				<!-- 타입 선택 -->
				<div class="product-options" style="margin-bottom:1.5rem;">
					<p class="option-label" style="font-size:.85rem;font-weight:500;margin-bottom:.8rem;">타입 선택</p>
					<div class="option-btns" style="display:flex;gap:.5rem;flex-wrap:wrap;">
						<button class="option-btn selected" data-type="perfume" onclick="selectProductType(this, 'perfume')" style="padding:.6rem 1.2rem;border:1px solid var(--sage);background:var(--sage-bg);border-radius:8px;font-size:.85rem;cursor:pointer;color:var(--sage);">향수</button>
						<button class="option-btn" data-type="mist" onclick="selectProductType(this, 'mist')" style="padding:.6rem 1.2rem;border:1px solid var(--border);background:#fff;border-radius:8px;font-size:.85rem;cursor:pointer;transition:all 0.3s;">바디미스트</button>
						<button class="option-btn" data-type="diffuser" onclick="selectProductType(this, 'diffuser')" style="padding:.6rem 1.2rem;border:1px solid var(--border);background:#fff;border-radius:8px;font-size:.85rem;cursor:pointer;transition:all 0.3s;">디퓨저</button>
					</div>
				</div>
				
				<!-- 액션 버튼 -->
				<div style="display:flex;gap:.75rem;margin-top:auto;margin-bottom:1rem;">
					${(product.stock !== undefined && product.stock <= 0) || product.status === '품절' 
					  ? `<button class="form-btn secondary" style="flex:1;padding:.75rem;" disabled>품절</button>`
					  : `<button class="form-btn primary" style="flex:1;padding:.75rem;" onclick="addProductToCartFromDetail(${product.id})">장바구니</button>
				         <button class="form-btn ivory" style="flex:1;padding:.75rem;" onclick="buyProductNowFromDetail(${product.id})">바로 구매</button>
				         <button class="product-wishlist" data-id="${product.id}" onclick="toggleWishlist(this)" style="width:48px;height:48px;border-radius:8px;border:1px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;font-size:1.2rem;cursor:pointer;">
				           ${typeof inWishlist === 'function' && inWishlist(product.id) ? '♥' : '♡'}
				         </button>`
					}
				</div>
				
				<!-- 리뷰 보기 버튼 -->
				<button class="form-btn secondary" style="width:100%;padding:.75rem;background:var(--sage-bg);color:var(--sage);border:1px solid var(--sage-lighter);" onclick="openReviewList();if(typeof currentProduct === 'undefined') { currentProduct = product; }">
					리뷰 보기 (${product.reviews || 0})
				</button>
			</div>
		</div>
	`;
	
	// currentProduct 설정 (리뷰 기능용)
	if (typeof window !== 'undefined') {
		window.currentProduct = product;
		window.currentProductSize = 30;
		window.currentProductType = 'perfume';
	}
}

// 용량 선택
function selectProductSize(btn, size) {
	document.querySelectorAll('[data-size]').forEach(b => {
		b.classList.remove('selected');
		b.style.border = '1px solid var(--border)';
		b.style.background = '#fff';
		b.style.color = '';
	});
	btn.classList.add('selected');
	btn.style.border = '1px solid var(--sage)';
	btn.style.background = 'var(--sage-bg)';
	btn.style.color = 'var(--sage)';
	if (typeof window !== 'undefined') {
		window.currentProductSize = size;
	}
}

// 타입 선택
function selectProductType(btn, type) {
	document.querySelectorAll('[data-type]').forEach(b => {
		b.classList.remove('selected');
		b.style.border = '1px solid var(--border)';
		b.style.background = '#fff';
		b.style.color = '';
	});
	btn.classList.add('selected');
	btn.style.border = '1px solid var(--sage)';
	btn.style.background = 'var(--sage-bg)';
	btn.style.color = 'var(--sage)';
	if (typeof window !== 'undefined') {
		window.currentProductType = type;
	}
}

// 장바구니에 추가 (상세 페이지용)
function addProductToCartFromDetail(productId) {
	if (typeof products === 'undefined' || !products.length) {
		alert('상품 정보를 불러오는 중입니다. 잠시 후 다시 시도해주세요.');
		return;
	}
	
	const product = products.find(p => p.id === productId);
	if (!product) {
		alert('상품을 찾을 수 없습니다.');
		return;
	}
	
	if ((product.stock !== undefined && product.stock <= 0) || product.status === '품절') {
		alert('품절된 상품입니다.');
		return;
	}
	
	const size = window.currentProductSize || 30;
	const type = window.currentProductType || 'perfume';
	
	if (typeof addToCart === 'function') {
		// addToCart 함수가 객체를 받는지 확인
		if (typeof cart !== 'undefined') {
			cart.push({
				id: product.id,
				name: product.name,
				price: product.price,
				imageUrl: product.imageUrl,
				size: size + 'ml',
				type: type === 'perfume' ? '향수' : type === 'mist' ? '바디미스트' : '디퓨저',
				qty: 1
			});
			if (typeof updateCartCount === 'function') {
				updateCartCount();
			}
			alert('장바구니에 추가되었습니다.');
		} else {
			alert('장바구니 기능을 사용할 수 없습니다. 메인 페이지에서 이용해주세요.');
		}
	} else {
		alert('장바구니 기능을 사용할 수 없습니다. 메인 페이지에서 이용해주세요.');
	}
}

// 바로 구매 (상세 페이지용)
function buyProductNowFromDetail(productId) {
	if (typeof products === 'undefined' || !products.length) {
		alert('상품 정보를 불러오는 중입니다. 잠시 후 다시 시도해주세요.');
		return;
	}
	
	const product = products.find(p => p.id === productId);
	if (!product) {
		alert('상품을 찾을 수 없습니다.');
		return;
	}
	
	if ((product.stock !== undefined && product.stock <= 0) || product.status === '품절') {
		alert('품절된 상품입니다.');
		return;
	}
	
	const size = window.currentProductSize || 30;
	const type = window.currentProductType || 'perfume';
	
	if (typeof cart !== 'undefined' && typeof openModal === 'function') {
		// 장바구니에 추가 (alert 없이)
		cart.push({
			id: product.id,
			name: product.name,
			price: product.price,
			imageUrl: product.imageUrl,
			size: size + 'ml',
			type: type === 'perfume' ? '향수' : type === 'mist' ? '바디미스트' : '디퓨저',
			qty: 1
		});
		if (typeof updateCartCount === 'function') {
			updateCartCount();
		}
		// 결제 모달 열기
		setTimeout(() => {
			if (typeof updateCheckoutSummary === 'function') {
				updateCheckoutSummary();
			}
			if (typeof loadSavedCheckoutInfo === 'function') {
				loadSavedCheckoutInfo();
			}
			openModal('checkoutModal');
		}, 100);
	} else {
		alert('구매 기능을 사용할 수 없습니다. 메인 페이지에서 이용해주세요.');
	}
}

// 페이지 로드 시 상품 정보 렌더링
(async function() {
	try {
		// 상품 목록 로드
		if (typeof loadProducts === 'function') {
			await loadProducts();
		} else if (typeof API !== 'undefined' && API.getProducts) {
			// loadProducts 함수가 없으면 직접 API 호출
			if (typeof products === 'undefined') {
				window.products = [];
			}
			products = await API.getProducts();
		} else if (typeof API !== 'undefined' && API.getPublicProducts) {
			// getPublicProducts 사용
			if (typeof products === 'undefined') {
				window.products = [];
			}
			products = await API.getPublicProducts();
		}
		
		// 상품 상세 정보 렌더링
		renderProductDetail();
	} catch (error) {
		console.error('상품 로드 오류:', error);
		const container = document.getElementById('productDetailContainer');
		if (container) {
			container.innerHTML = `
				<div style="text-align:center;padding:3rem;">
					<p style="color:var(--mid);font-size:1.1rem;margin-bottom:1rem;">상품을 불러오는 중 오류가 발생했습니다.</p>
					<p style="color:var(--light);font-size:.9rem;margin-bottom:1.5rem;">${error.message || '알 수 없는 오류'}</p>
					<a href="products.php" class="form-btn primary">상품 목록으로</a>
				</div>
			`;
		}
	}
})();
</script>
</body>
</html>

