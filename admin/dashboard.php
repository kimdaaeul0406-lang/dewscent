<?php
require_once __DIR__ . '/guard.php';
ensure_admin();

$pageTitle = "관리자 대시보드 | DewScent";
$adminEmail = $_SESSION['admin_email'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= htmlspecialchars($pageTitle) ?></title>
	<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Noto+Sans+KR:wght@200;300;400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../public/css/style.css?v=7">
	<script src="../public/js/api.js?v=4"></script>
	<style>
		/* 관리 영역 간단 레이아웃 */
		.admin-wrap { max-width: 1100px; margin: 0 auto; }
		.admin-top { display:flex; justify-content: space-between; align-items:center; margin-bottom: 1rem; }
		.admin-tabs { display:flex; gap:.5rem; flex-wrap: wrap; }
		.admin-tab { padding:.5rem 1rem; border:1px solid var(--border); border-radius:999px; background:#fff; cursor:pointer; font-size:.9rem; }
		.admin-tab.active { border-color: var(--sage); color: var(--sage); background: var(--sage-bg); }
		.admin-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:1rem; }
		.table { width:100%; border-collapse: collapse; }
		.table th, .table td { padding:.75rem; border-bottom: 1px solid var(--border); text-align:left; font-size:.9rem; }
		.table th { color: var(--light); font-weight:500; }
		.badge { padding:.2rem .5rem; border:1px solid var(--border); border-radius:999px; font-size:.7rem; color: var(--mid); background:#fff; }
		.kpis { display:grid; grid-template-columns: repeat(4,1fr); gap:1rem; margin-bottom:1rem; }
		.kpi { background:#fff; border:1px solid var(--border); border-radius:16px; padding:1rem; }
		.kpi h4 { font-size:.8rem; color:var(--light); margin-bottom:.3rem; }
		.kpi strong { font-size:1.1rem; }
		@media (max-width: 900px) { .kpis { grid-template-columns: repeat(2,1fr); } }
		@media (max-width: 520px) { .kpis { grid-template-columns: 1fr; } }
		/* 문의 관리 스타일 */
		.inquiry-admin-item { border:1px solid var(--border); border-radius:10px; margin-bottom:.75rem; overflow:hidden; }
		.inquiry-admin-header { display:flex; justify-content:space-between; align-items:center; padding:.75rem 1rem; background:var(--sage-bg); cursor:pointer; }
		.inquiry-admin-header:hover { background:var(--sage-lighter); }
		.inquiry-admin-left { display:flex; align-items:center; gap:.5rem; }
		.inquiry-admin-body { padding:1rem; display:none; border-top:1px solid var(--border); }
		.inquiry-admin-item.open .inquiry-admin-body { display:block; }
		.inquiry-admin-content { background:#f9f9f9; padding:.75rem; border-radius:8px; margin-bottom:1rem; font-size:.9rem; line-height:1.6; }
		.inquiry-admin-answer { margin-top:1rem; }
		.inquiry-admin-answer textarea { width:100%; padding:.75rem; border:1px solid var(--border); border-radius:8px; resize:none; font-size:.9rem; }
		.inquiry-admin-answer button { margin-top:.5rem; }
		.status-badge { padding:.2rem .5rem; border-radius:4px; font-size:.7rem; }
		.status-badge.waiting { background:var(--border); color:var(--mid); }
		.status-badge.answered { background:var(--sage-lighter); color:var(--sage); }
		.type-badge { font-size:.7rem; padding:.2rem .5rem; border-radius:4px; background:var(--sage); color:#fff; }
		.type-badge.exchange { background:#d4a5a5; }
		.type-badge.shipping { background:#c9b896; color:#333; }
		.type-badge.product { background:#888; }
		.type-badge.order { background:#6b8cce; }
		/* 이미지 업로드 스타일 */
		.image-upload-wrap { display:flex; gap:.5rem; align-items:center; }
		.image-upload-wrap input[type="text"] { flex:1; }
		.image-upload-btn { padding:.5rem 1rem; background:var(--sage); color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:.85rem; white-space:nowrap; }
		.image-upload-btn:hover { background:var(--sage-dark, #6b7a5f); }
		.image-preview { max-width:150px; max-height:100px; border-radius:8px; margin-top:.5rem; object-fit:cover; border:1px solid var(--border); }
		.image-upload-input { display:none; }
	</style>
</head>
<body class="cart-page">
	<header>
		<div class="header-left"></div>
		<div class="header-center">
			<a href="dashboard.php" class="logo">DewScent Admin</a>
		</div>
		<div class="header-right">
			<span style="font-size:.8rem;color:var(--light)"><?= htmlspecialchars($adminEmail) ?></span>
			<a href="logout.php" class="cart-link">로그아웃</a>
		</div>
	</header>

	<main id="main" class="visible">
		<section class="best-section">
			<div class="admin-wrap">
				<div class="admin-top">
					<h2 class="section-title">대시보드</h2>
					<div class="admin-tabs" id="adminTabs">
						<button class="admin-tab active" data-tab="overview">개요</button>
						<button class="admin-tab" data-tab="banners">배너</button>
						<button class="admin-tab" data-tab="popups">팝업</button>
						<button class="admin-tab" data-tab="emotions">감정</button>
						<button class="admin-tab" data-tab="sections">타이틀</button>
						<button class="admin-tab" data-tab="mainproducts">메인상품</button>
						<button class="admin-tab" data-tab="products">상품</button>
						<button class="admin-tab" data-tab="reviews">리뷰</button>
						<button class="admin-tab" data-tab="inquiries">문의</button>
						<button class="admin-tab" data-tab="users">회원</button>
						<button class="admin-tab" data-tab="orders">주문</button>
						<button class="admin-tab" data-tab="settings">설정</button>
					</div>
				</div>

				<div class="admin-card" id="tab-overview">
					<div class="kpis">
						<div class="kpi">
							<h4>오늘 가입</h4>
							<strong>0</strong>
						</div>
						<div class="kpi">
							<h4>오늘 주문</h4>
							<strong>0</strong>
						</div>
						<div class="kpi">
							<h4>답변 대기 문의</h4>
							<strong id="kpi-waiting-inquiries">0</strong>
						</div>
						<div class="kpi">
							<h4>총 문의</h4>
							<strong id="kpi-total-inquiries">0</strong>
						</div>
					</div>
					<p style="font-size:.85rem;color:var(--light)">백엔드 연동 후, API를 통해 실제 데이터가 표시됩니다.</p>
				</div>

				<div class="admin-card" id="tab-users" style="display:none">
					<h3 style="margin-bottom:1rem;font-size:1rem;">회원 목록</h3>
					<table class="table">
						<thead>
							<tr>
								<th>ID</th>
								<th>이름</th>
								<th>이메일</th>
								<th>가입일</th>
								<th>상태</th>
							</tr>
						</thead>
						<tbody id="usersTableBody">
							<tr><td colspan="5" style="text-align:center;color:var(--light)">데이터 없음 (연동 예정)</td></tr>
						</tbody>
					</table>
				</div>

				<div class="admin-card" id="tab-orders" style="display:none">
					<h3 style="margin-bottom:1rem;font-size:1rem;">주문 목록</h3>
					<table class="table">
						<thead>
							<tr>
								<th>주문번호</th>
								<th>고객</th>
								<th>금액</th>
								<th>상태</th>
								<th>주문일</th>
							</tr>
						</thead>
						<tbody id="ordersTableBody">
							<tr><td colspan="5" style="text-align:center;color:var(--light)">데이터 없음 (연동 예정)</td></tr>
						</tbody>
					</table>
				</div>

				<div class="admin-card" id="tab-products" style="display:none">
					<!-- 설명 박스 -->
					<div style="background:linear-gradient(135deg,#eef5f3,#f5ebe8);padding:1rem;border-radius:12px;margin-bottom:1rem;display:flex;gap:1rem;align-items:center;">
						<div style="width:80px;height:60px;background:var(--sage-lighter);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;">🧴</div>
						<div>
							<strong style="color:var(--sage);">상품 관리</strong>
							<p style="font-size:.85rem;color:var(--mid);margin-top:.25rem;">전체 상품 목록입니다. 등록/수정/삭제 및 미리보기가 가능합니다.</p>
						</div>
					</div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
						<h3 style="font-size:1rem;">상품 관리</h3>
						<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="openProductForm()">+ 새 상품 등록</button>
					</div>
					
					<!-- 상품 등록/수정 폼 (숨김) -->
					<div id="productFormWrap" style="display:none;background:var(--sage-bg);padding:1rem;border-radius:10px;margin-bottom:1rem;">
						<h4 id="productFormTitle" style="margin-bottom:1rem;font-size:.95rem;">새 상품 등록</h4>
						<input type="hidden" id="productEditId">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
							<div>
								<label style="font-size:.8rem;color:var(--light);">상품명 *</label>
								<input type="text" id="prodName" class="form-input" placeholder="상품명">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">가격 (원) *</label>
								<input type="number" id="prodPrice" class="form-input" placeholder="42000">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">카테고리</label>
								<select id="prodCategory" class="form-input">
									<option value="향수">향수</option>
									<option value="바디미스트">바디미스트</option>
									<option value="헤어미스트">헤어미스트</option>
									<option value="디퓨저">디퓨저</option>
									<option value="섬유유연제">섬유유연제</option>
									<option value="룸스프레이">룸스프레이</option>
								</select>
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">재고</label>
								<input type="number" id="prodStock" class="form-input" placeholder="50" value="0">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">상태</label>
								<select id="prodStatus" class="form-input">
									<option value="판매중">판매중</option>
									<option value="품절">품절</option>
									<option value="숨김">숨김</option>
								</select>
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">배지</label>
								<select id="prodBadge" class="form-input">
									<option value="">없음</option>
									<option value="NEW">NEW</option>
									<option value="BEST">BEST</option>
									<option value="SALE">SALE</option>
								</select>
							</div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">상품 설명</label>
								<textarea id="prodDesc" class="form-input" rows="2" placeholder="상품에 대한 간단한 설명" style="resize:none;"></textarea>
							</div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">이미지</label>
								<div class="image-upload-wrap">
									<input type="text" id="prodImageUrl" class="form-input" placeholder="URL 입력 또는 파일 업로드">
									<input type="file" id="prodImageFile" class="image-upload-input" accept="image/*" onchange="uploadProductImage(this)">
									<button type="button" class="image-upload-btn" onclick="document.getElementById('prodImageFile').click()">파일 선택</button>
								</div>
								<img id="prodImagePreview" class="image-preview" style="display:none;">
							</div>
						</div>
						<div style="display:flex;gap:.5rem;margin-top:1rem;flex-wrap:wrap;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveProduct()">저장</button>
							<button class="badge" style="cursor:pointer;background:#6b8cce;color:#fff;border:none;padding:.5rem 1rem;" onclick="previewProduct()">미리보기</button>
							<button class="badge" style="cursor:pointer;border:none;padding:.5rem 1rem;" onclick="closeProductForm()">취소</button>
						</div>
					</div>
					
					<!-- 상품 목록 -->
					<div style="overflow-x:auto;">
						<table class="table">
							<thead>
								<tr>
									<th>ID</th>
									<th>상품명</th>
									<th>카테고리</th>
									<th>가격</th>
									<th>재고</th>
									<th>상태</th>
									<th>배지</th>
									<th>관리</th>
								</tr>
							</thead>
							<tbody id="productsTableBody">
								<tr><td colspan="8" style="text-align:center;color:var(--light)">불러오는 중...</td></tr>
							</tbody>
						</table>
					</div>
				</div>

				<div class="admin-card" id="tab-inquiries" style="display:none">
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
						<h3 style="font-size:1rem;">문의 관리</h3>
						<div style="display:flex;gap:.5rem;">
							<button class="badge" onclick="filterInquiries('all')" id="filter-all" style="cursor:pointer;background:var(--sage-bg)">전체</button>
							<button class="badge" onclick="filterInquiries('waiting')" id="filter-waiting" style="cursor:pointer">답변대기</button>
							<button class="badge" onclick="filterInquiries('answered')" id="filter-answered" style="cursor:pointer">답변완료</button>
						</div>
					</div>
					<div id="inquiriesAdminBody">
						<p style="text-align:center;color:var(--light);padding:2rem;">문의 내역이 없습니다.</p>
					</div>
				</div>

				<!-- 배너 관리 -->
				<div class="admin-card" id="tab-banners" style="display:none">
					<!-- 설명 박스 -->
					<div style="background:linear-gradient(135deg,#e8f0e5,#f5ebe8);padding:1rem;border-radius:12px;margin-bottom:1rem;display:flex;gap:1rem;align-items:center;">
						<div style="width:80px;height:60px;background:var(--sage-lighter);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;">🎠</div>
						<div>
							<strong style="color:var(--sage);">메인 슬라이더 배너</strong>
							<p style="font-size:.85rem;color:var(--mid);margin-top:.25rem;">메인 페이지 상단에 빙글빙글 돌아가는 이벤트 배너입니다.</p>
						</div>
						<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;margin-left:auto;" onclick="previewBannerSlider()">🔍 미리보기</button>
					</div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
						<h3 style="font-size:1rem;">배너/캐러셀 관리</h3>
						<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="openBannerForm()">+ 새 배너</button>
					</div>
					<div id="bannerFormWrap" style="display:none;background:var(--sage-bg);padding:1rem;border-radius:10px;margin-bottom:1rem;">
						<h4 id="bannerFormTitle" style="margin-bottom:1rem;font-size:.95rem;">새 배너 등록</h4>
						<input type="hidden" id="bannerEditId">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
							<div><label style="font-size:.8rem;color:var(--light);">제목 *</label><input type="text" id="bannerTitle" class="form-input" placeholder="배너 제목"></div>
							<div><label style="font-size:.8rem;color:var(--light);">부제목</label><input type="text" id="bannerSubtitle" class="form-input" placeholder="부제목"></div>
							<div><label style="font-size:.8rem;color:var(--light);">링크</label><input type="text" id="bannerLink" class="form-input" placeholder="pages/products.php"></div>
							<div><label style="font-size:.8rem;color:var(--light);">순서</label><input type="number" id="bannerOrder" class="form-input" value="1" min="1"></div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">이미지</label>
								<div class="image-upload-wrap">
									<input type="text" id="bannerImageUrl" class="form-input" placeholder="URL 입력 또는 파일 업로드">
									<input type="file" id="bannerImageFile" class="image-upload-input" accept="image/*" onchange="uploadBannerImage(this)">
									<button type="button" class="image-upload-btn" onclick="document.getElementById('bannerImageFile').click()">파일 선택</button>
								</div>
								<img id="bannerImagePreview" class="image-preview" style="display:none;">
							</div>
							<div><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;"><input type="checkbox" id="bannerActive" checked> 활성화</label></div>
						</div>
						<div style="display:flex;gap:.5rem;margin-top:1rem;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveBanner()">저장</button>
							<button class="badge" style="cursor:pointer;border:none;padding:.5rem 1rem;" onclick="closeBannerForm()">취소</button>
						</div>
					</div>
					<div id="bannersTableWrap"><table class="table"><thead><tr><th>순서</th><th>제목</th><th>링크</th><th>상태</th><th>관리</th></tr></thead><tbody id="bannersTableBody"><tr><td colspan="5" style="text-align:center;color:var(--light)">불러오는 중...</td></tr></tbody></table></div>
				</div>

				<!-- 팝업 관리 -->
				<div class="admin-card" id="tab-popups" style="display:none">
					<!-- 설명 박스 -->
					<div style="background:linear-gradient(135deg,#f5ebe8,#e8f0e5);padding:1rem;border-radius:12px;margin-bottom:1rem;display:flex;gap:1rem;align-items:center;">
						<div style="width:80px;height:60px;background:var(--rose-lighter);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--rose);">POPUP</div>
						<div>
							<strong style="color:var(--rose);">사이트 팝업</strong>
							<p style="font-size:.85rem;color:var(--mid);margin-top:.25rem;">메인 페이지 진입 시 나타나는 이벤트/공지 팝업입니다.</p>
						</div>
						<button class="badge" style="cursor:pointer;background:var(--rose);color:#fff;border:none;padding:.5rem 1rem;margin-left:auto;" onclick="previewPopup()">🔍 미리보기</button>
					</div>
					<!-- 향기 테스트 관리 -->
					<div style="background:var(--sage-bg);padding:1rem;border-radius:10px;margin-bottom:1rem;">
						<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
							<div>
								<strong style="color:var(--sage);">향기 테스트 팝업</strong>
								<p style="font-size:.8rem;color:var(--light);margin-top:.25rem;">"오늘 기분에 어울리는 향기를 찾아볼까요?" 웰컴 팝업</p>
							</div>
							<div style="display:flex;gap:.5rem;">
								<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem .75rem;" onclick="resetWelcomeHidden()">일주일 안보기 초기화</button>
								<button class="badge" style="cursor:pointer;border:none;padding:.5rem .75rem;" onclick="checkWelcomeStatus()">상태 확인</button>
							</div>
						</div>
						<p id="welcomeStatusText" style="font-size:.8rem;color:var(--mid);margin-top:.5rem;"></p>
					</div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
						<h3 style="font-size:1rem;">이벤트 팝업 관리</h3>
						<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="openPopupForm()">+ 새 팝업</button>
					</div>
					<p style="font-size:.85rem;color:var(--light);margin-bottom:1rem;">* 최대 5개까지 동시 표시 가능. 방문자는 "일주일간 안보기" 선택 가능.</p>
					<div id="popupFormWrap" style="display:none;background:var(--sage-bg);padding:1rem;border-radius:10px;margin-bottom:1rem;">
						<h4 id="popupFormTitle" style="margin-bottom:1rem;font-size:.95rem;">새 팝업 등록</h4>
						<input type="hidden" id="popupEditId">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
							<div><label style="font-size:.8rem;color:var(--light);">제목 *</label><input type="text" id="popupTitle" class="form-input" placeholder="팝업 제목"></div>
							<div><label style="font-size:.8rem;color:var(--light);">링크 (선택)</label><input type="text" id="popupLink" class="form-input" placeholder="클릭 시 이동할 링크"></div>
							<div><label style="font-size:.8rem;color:var(--light);">시작일</label><input type="date" id="popupStartDate" class="form-input"></div>
							<div><label style="font-size:.8rem;color:var(--light);">종료일</label><input type="date" id="popupEndDate" class="form-input"></div>
							<div><label style="font-size:.8rem;color:var(--light);">순서</label><input type="number" id="popupOrder" class="form-input" value="1" min="1"></div>
							<div><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;"><input type="checkbox" id="popupActive" checked> 활성화</label></div>
							<div style="grid-column:1/-1;"><label style="font-size:.8rem;color:var(--light);">내용</label><textarea id="popupContent" class="form-input" rows="3" placeholder="팝업에 표시할 내용" style="resize:none;"></textarea></div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">이미지</label>
								<div class="image-upload-wrap">
									<input type="text" id="popupImageUrl" class="form-input" placeholder="URL 입력 또는 파일 업로드">
									<input type="file" id="popupImageFile" class="image-upload-input" accept="image/*" onchange="uploadPopupImage(this)">
									<button type="button" class="image-upload-btn" onclick="document.getElementById('popupImageFile').click()">파일 선택</button>
								</div>
								<img id="popupImagePreview" class="image-preview" style="display:none;">
							</div>
						</div>
						<div style="display:flex;gap:.5rem;margin-top:1rem;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="savePopup()">저장</button>
							<button class="badge" style="cursor:pointer;border:none;padding:.5rem 1rem;" onclick="closePopupForm()">취소</button>
						</div>
					</div>
					<div id="popupsTableWrap"><table class="table"><thead><tr><th>순서</th><th>제목</th><th>기간</th><th>상태</th><th>관리</th></tr></thead><tbody id="popupsTableBody"><tr><td colspan="5" style="text-align:center;color:var(--light)">불러오는 중...</td></tr></tbody></table></div>
				</div>

				<!-- 감정 카드 관리 -->
				<div class="admin-card" id="tab-emotions" style="display:none">
					<!-- 설명 박스 -->
					<div style="background:linear-gradient(135deg,#e8f0e5,#eef5f3);padding:1rem;border-radius:12px;margin-bottom:1rem;display:flex;gap:1rem;align-items:center;">
						<div style="width:80px;height:60px;background:var(--aqua);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;">💭</div>
						<div>
							<strong style="color:var(--sage);">감정 선택 카드</strong>
							<p style="font-size:.85rem;color:var(--mid);margin-top:.25rem;">메인 페이지 "오늘, 어떤 기분인가요?" 섹션의 감정 카드입니다.</p>
						</div>
						<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;margin-left:auto;" onclick="previewEmotions()">🔍 미리보기</button>
					</div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
						<h3 style="font-size:1rem;">감정 카드 관리</h3>
						<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="openEmotionForm()">+ 새 감정</button>
					</div>
					<div id="emotionFormWrap" style="display:none;background:var(--sage-bg);padding:1rem;border-radius:10px;margin-bottom:1rem;">
						<h4 id="emotionFormTitle" style="margin-bottom:1rem;font-size:.95rem;">새 감정 등록</h4>
						<input type="hidden" id="emotionEditId">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
							<div><label style="font-size:.8rem;color:var(--light);">키 (영문, 예: calm)</label><input type="text" id="emotionKey" class="form-input" placeholder="calm"></div>
							<div><label style="font-size:.8rem;color:var(--light);">제목 *</label><input type="text" id="emotionCardTitle" class="form-input" placeholder="차분해지고 싶은 날"></div>
							<div><label style="font-size:.8rem;color:var(--light);">설명</label><input type="text" id="emotionCardDesc" class="form-input" placeholder="마음이 고요해지는 향"></div>
							<div><label style="font-size:.8rem;color:var(--light);">순서</label><input type="number" id="emotionOrder" class="form-input" value="1" min="1"></div>
							<div><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;"><input type="checkbox" id="emotionActive" checked> 활성화</label></div>
						</div>
						<div style="display:flex;gap:.5rem;margin-top:1rem;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveEmotion()">저장</button>
							<button class="badge" style="cursor:pointer;border:none;padding:.5rem 1rem;" onclick="closeEmotionForm()">취소</button>
						</div>
					</div>
					<div id="emotionsTableWrap"><table class="table"><thead><tr><th>순서</th><th>키</th><th>제목</th><th>설명</th><th>상태</th><th>관리</th></tr></thead><tbody id="emotionsTableBody"><tr><td colspan="6" style="text-align:center;color:var(--light)">불러오는 중...</td></tr></tbody></table></div>
				</div>

				<!-- 섹션 타이틀 관리 -->
				<div class="admin-card" id="tab-sections" style="display:none">
					<h3 style="margin-bottom:1rem;font-size:1rem;">섹션 타이틀 관리</h3>
					<p style="font-size:.85rem;color:var(--light);margin-bottom:1rem;">메인 페이지의 각 섹션에 표시되는 타이틀과 설명을 수정합니다.</p>
					<div style="display:grid;grid-template-columns:1fr;gap:1.5rem;">
						<div style="background:var(--sage-bg);padding:1rem;border-radius:10px;">
							<h4 style="margin-bottom:.75rem;font-size:.9rem;">감정 섹션</h4>
							<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
								<div><label style="font-size:.8rem;color:var(--light);">라벨 (영문)</label><input type="text" id="sectionEmotionLabel" class="form-input" placeholder="FIND YOUR SCENT"></div>
								<div><label style="font-size:.8rem;color:var(--light);">타이틀</label><input type="text" id="sectionEmotionTitle" class="form-input" placeholder="오늘, 어떤 기분인가요?"></div>
								<div style="grid-column:1/-1;"><label style="font-size:.8rem;color:var(--light);">부제목</label><input type="text" id="sectionEmotionSubtitle" class="form-input" placeholder="감정에 맞는 향기를 추천해드릴게요"></div>
							</div>
						</div>
						<div style="background:var(--sage-bg);padding:1rem;border-radius:10px;">
							<h4 style="margin-bottom:.75rem;font-size:.9rem;">베스트 섹션</h4>
							<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
								<div><label style="font-size:.8rem;color:var(--light);">라벨 (영문)</label><input type="text" id="sectionBestLabel" class="form-input" placeholder="MOST LOVED"></div>
								<div><label style="font-size:.8rem;color:var(--light);">타이틀</label><input type="text" id="sectionBestTitle" class="form-input" placeholder="다시 찾게 되는 향기"></div>
								<div style="grid-column:1/-1;"><label style="font-size:.8rem;color:var(--light);">부제목 (줄바꿈은 &lt;br&gt; 사용)</label><input type="text" id="sectionBestSubtitle" class="form-input" placeholder="한 번 스친 향기가..."></div>
								<div style="grid-column:1/-1;"><label style="font-size:.8rem;color:var(--light);">하단 인용문</label><input type="text" id="sectionBestQuote" class="form-input" placeholder="— 향기는 기억을 여는 열쇠 —"></div>
							</div>
						</div>
					</div>
					<div style="margin-top:1rem;"><button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveSections()">저장</button></div>
				</div>

				<!-- 메인 상품 배치 -->
				<div class="admin-card" id="tab-mainproducts" style="display:none">
					<!-- 설명 박스 -->
					<div style="background:linear-gradient(135deg,#f5ebe8,#eef5f3);padding:1rem;border-radius:12px;margin-bottom:1rem;display:flex;gap:1rem;align-items:center;">
						<div style="width:80px;height:60px;background:var(--ivory-light);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--ivory);">BEST</div>
						<div>
							<strong style="color:var(--ivory);">메인 베스트 상품</strong>
							<p style="font-size:.85rem;color:var(--mid);margin-top:.25rem;">메인 페이지 "다시 찾게 되는 향기" 섹션에 표시될 상품입니다.</p>
						</div>
					</div>
					<h3 style="margin-bottom:1rem;font-size:1rem;">메인 페이지 상품 배치</h3>
					<!-- 자동 배치 옵션 -->
					<div style="background:var(--sage-bg);padding:1rem;border-radius:10px;margin-bottom:1rem;">
						<p style="font-size:.9rem;font-weight:500;margin-bottom:.5rem;">빠른 설정</p>
						<div style="display:flex;gap:.5rem;flex-wrap:wrap;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem .75rem;" onclick="autoSelectBest()">BEST 상품만</button>
							<button class="badge" style="cursor:pointer;background:var(--rose);color:#fff;border:none;padding:.5rem .75rem;" onclick="autoSelectNew()">NEW 상품만</button>
							<button class="badge" style="cursor:pointer;background:var(--ivory);color:#fff;border:none;padding:.5rem .75rem;" onclick="autoSelectBestAndNew()">BEST + NEW</button>
							<button class="badge" style="cursor:pointer;border:none;padding:.5rem .75rem;" onclick="clearMainProducts()">전체 해제</button>
						</div>
					</div>
					<p style="font-size:.85rem;color:var(--light);margin-bottom:1rem;">아래에서 직접 선택하거나, 위 버튼으로 자동 선택하세요.</p>
					<div id="mainProductsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;"></div>
					<div style="margin-top:1rem;"><button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveMainProducts()">저장</button></div>
				</div>

				<!-- 리뷰 관리 -->
				<div class="admin-card" id="tab-reviews" style="display:none">
					<h3 style="margin-bottom:1rem;font-size:1rem;">리뷰 관리</h3>
					<p style="font-size:.85rem;color:var(--light);margin-bottom:1rem;">등록된 리뷰를 확인하고 관리하세요.</p>
					<div id="reviewsAdminBody"><p style="text-align:center;color:var(--light);padding:2rem;">리뷰 목록을 불러오는 중...</p></div>
				</div>

				<!-- 사이트 설정 -->
				<div class="admin-card" id="tab-settings" style="display:none">
					<h3 style="margin-bottom:1rem;font-size:1rem;">사이트 설정</h3>
					<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
						<div><label style="font-size:.8rem;color:var(--light);">사이트명</label><input type="text" id="settingSiteName" class="form-input" placeholder="DewScent"></div>
						<div><label style="font-size:.8rem;color:var(--light);">슬로건</label><input type="text" id="settingSlogan" class="form-input" placeholder="당신의 향기를 찾아서"></div>
						<div><label style="font-size:.8rem;color:var(--light);">이메일</label><input type="email" id="settingEmail" class="form-input" placeholder="hello@dewscent.kr"></div>
						<div><label style="font-size:.8rem;color:var(--light);">전화번호</label><input type="text" id="settingPhone" class="form-input" placeholder="02-1234-5678"></div>
						<div style="grid-column:1/-1;"><label style="font-size:.8rem;color:var(--light);">주소</label><input type="text" id="settingAddress" class="form-input" placeholder="서울시 강남구 테헤란로 123"></div>
						<div><label style="font-size:.8rem;color:var(--light);">운영시간</label><input type="text" id="settingHours" class="form-input" placeholder="평일 10:00 ~ 17:00"></div>
						<div><label style="font-size:.8rem;color:var(--light);">카카오톡 채널</label><input type="text" id="settingKakao" class="form-input" placeholder="듀센트 고객센터"></div>
						<div style="grid-column:1/-1;"><label style="font-size:.8rem;color:var(--light);">인스타그램 URL</label><input type="text" id="settingInstagram" class="form-input" placeholder="https://instagram.com/dewscent"></div>
					</div>
					<div style="margin-top:1rem;"><button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveSiteSettings()">저장</button></div>
				</div>

			</div>
		</section>
	</main>

	<script>
		// 간단한 탭 전환 + 데이터 로딩
		const loaded = { overview: true, users: false, orders: false, products: false, inquiries: false, settings: true };

		// 문의 관리 관련
		const INQUIRY_KEY = "dewscent_inquiries";
		let currentInquiryFilter = 'all';

		function getInquiries() {
			try { return JSON.parse(localStorage.getItem(INQUIRY_KEY)) || []; }
			catch { return []; }
		}
		function setInquiries(list) {
			localStorage.setItem(INQUIRY_KEY, JSON.stringify(list));
		}
		function getTypeLabel(type) {
			const labels = { shipping: "배송", exchange: "교환/환불", product: "상품", order: "주문/결제", other: "기타" };
			return labels[type] || "기타";
		}

		function renderAdminInquiries() {
			const container = document.getElementById('inquiriesAdminBody');
			if (!container) return;

			let inquiries = getInquiries();
			if (currentInquiryFilter !== 'all') {
				inquiries = inquiries.filter(inq => inq.status === currentInquiryFilter);
			}

			if (!inquiries.length) {
				container.innerHTML = `<p style="text-align:center;color:var(--light);padding:2rem;">문의 내역이 없습니다.</p>`;
				return;
			}

			container.innerHTML = inquiries.map(inq => `
				<div class="inquiry-admin-item" data-id="${inq.id}">
					<div class="inquiry-admin-header" onclick="toggleAdminInquiry(${inq.id})">
						<div class="inquiry-admin-left">
							<span class="type-badge ${inq.type}">${getTypeLabel(inq.type)}</span>
							<strong style="font-size:.9rem;">${inq.title}</strong>
							<span style="font-size:.8rem;color:var(--light)">${inq.userId}</span>
						</div>
						<div style="display:flex;align-items:center;gap:.5rem;">
							<span class="status-badge ${inq.status}">${inq.status === 'answered' ? '답변완료' : '답변대기'}</span>
							<span style="font-size:.75rem;color:var(--light)">${inq.createdAt}</span>
						</div>
					</div>
					<div class="inquiry-admin-body">
						<div class="inquiry-admin-content">
							${inq.orderNo ? `<p style="font-size:.8rem;color:var(--light);margin-bottom:.5rem;">주문번호: ${inq.orderNo}</p>` : ''}
							<p>${inq.content.replace(/\n/g, '<br>')}</p>
						</div>
						${inq.answer ? `
							<div style="background:var(--sage-bg);padding:.75rem;border-radius:8px;margin-bottom:1rem;">
								<p style="font-size:.75rem;font-weight:600;color:var(--sage);margin-bottom:.5rem;">관리자 답변 (${inq.answeredAt || ''})</p>
								<p style="font-size:.9rem;line-height:1.6;">${inq.answer.replace(/\n/g, '<br>')}</p>
							</div>
						` : ''}
						<div class="inquiry-admin-answer">
							<textarea id="answer-${inq.id}" rows="3" placeholder="답변을 입력하세요...">${inq.answer || ''}</textarea>
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.4rem .8rem;" onclick="submitAdminAnswer(${inq.id})">
								${inq.answer ? '답변 수정' : '답변 등록'}
							</button>
						</div>
					</div>
				</div>
			`).join('');
		}

		function toggleAdminInquiry(id) {
			const item = document.querySelector(`.inquiry-admin-item[data-id="${id}"]`);
			if (item) item.classList.toggle('open');
		}

		function submitAdminAnswer(id) {
			const textarea = document.getElementById('answer-' + id);
			if (!textarea) return;
			const answer = textarea.value.trim();
			if (!answer) {
				alert('답변 내용을 입력해주세요.');
				return;
			}

			const inquiries = getInquiries();
			const idx = inquiries.findIndex(inq => inq.id === id);
			if (idx === -1) return;

			inquiries[idx].answer = answer;
			inquiries[idx].status = 'answered';
			inquiries[idx].answeredAt = new Date().toISOString().split('T')[0];
			setInquiries(inquiries);

			alert('답변이 등록되었습니다.');
			renderAdminInquiries();
		}

		function filterInquiries(filter) {
			currentInquiryFilter = filter;
			document.querySelectorAll('#tab-inquiries .badge').forEach(b => b.style.background = '');
			document.getElementById('filter-' + filter).style.background = 'var(--sage-bg)';
			renderAdminInquiries();
		}

		function renderUsers() {
			const tbody = document.getElementById('usersTableBody');
			if (!tbody) return;
			tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">불러오는 중...</td></tr>`;
			API.getUsers().then((rows) => {
				if (!rows || !rows.length) {
					tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">데이터 없음</td></tr>`;
					return;
				}
				tbody.innerHTML = rows.map((u) => `
					<tr>
						<td>${u.id}</td>
						<td>${u.name}</td>
						<td>${u.email}</td>
						<td>${u.joinedAt || ""}</td>
						<td>${u.status || ""}</td>
					</tr>
				`).join('');
			});
		}

		function renderAdminOrders() {
			const tbody = document.getElementById('ordersTableBody');
			if (!tbody) return;
			tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">불러오는 중...</td></tr>`;
			API.getAdminOrders().then((rows) => {
				if (!rows || !rows.length) {
					tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">데이터 없음</td></tr>`;
					return;
				}
				tbody.innerHTML = rows.map((o) => `
					<tr>
						<td>${o.id}</td>
						<td>${o.customer}</td>
						<td>₩${(o.total || 0).toLocaleString()}</td>
						<td>${o.status || ""}</td>
						<td>${o.orderedAt || ""}</td>
					</tr>
				`).join('');
			});
		}

		// ========== 상품 관리 ==========
		function renderProducts() {
			const tbody = document.getElementById('productsTableBody');
			if (!tbody) return;
			tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--light)">불러오는 중...</td></tr>`;
			
			API.getProducts().then((rows) => {
				if (!rows || !rows.length) {
					tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--light)">등록된 상품이 없습니다.</td></tr>`;
					return;
				}
				tbody.innerHTML = rows.map((p) => `
					<tr>
						<td>${p.id}</td>
						<td style="font-weight:500;">${p.name}</td>
						<td>${p.category || '향수'}</td>
						<td>₩${(p.price || 0).toLocaleString()}</td>
						<td>${p.stock ?? 0}</td>
						<td><span class="status-badge ${p.status === '판매중' ? 'answered' : 'waiting'}">${p.status || ''}</span></td>
						<td>${p.badge ? `<span class="badge" style="background:var(--sage);color:#fff;">${p.badge}</span>` : '-'}</td>
						<td>
							<button class="badge" style="cursor:pointer;font-size:.7rem;" onclick="editProduct(${p.id})">수정</button>
							<button class="badge" style="cursor:pointer;font-size:.7rem;color:var(--rose);" onclick="deleteProduct(${p.id})">삭제</button>
						</td>
					</tr>
				`).join('');
			});
		}

		// 상품 폼 열기 (등록)
		function openProductForm() {
			document.getElementById('productFormWrap').style.display = 'block';
			document.getElementById('productFormTitle').textContent = '새 상품 등록';
			document.getElementById('productEditId').value = '';
			// 폼 초기화
			document.getElementById('prodName').value = '';
			document.getElementById('prodPrice').value = '';
			document.getElementById('prodCategory').value = '향수';
			document.getElementById('prodStock').value = '0';
			document.getElementById('prodStatus').value = '판매중';
			document.getElementById('prodBadge').value = '';
			document.getElementById('prodDesc').value = '';
			document.getElementById('prodImageUrl').value = '';
		}

		// 상품 폼 닫기
		function closeProductForm() {
			document.getElementById('productFormWrap').style.display = 'none';
		}

		// 상품 수정 폼 열기
		async function editProduct(id) {
			const product = await API.getProduct(id);
			if (!product) {
				alert('상품을 찾을 수 없습니다.');
				return;
			}
			document.getElementById('productFormWrap').style.display = 'block';
			document.getElementById('productFormTitle').textContent = '상품 수정';
			document.getElementById('productEditId').value = id;
			document.getElementById('prodName').value = product.name || '';
			document.getElementById('prodPrice').value = product.price || '';
			document.getElementById('prodCategory').value = product.category || '향수';
			document.getElementById('prodStock').value = product.stock || 0;
			document.getElementById('prodStatus').value = product.status || '판매중';
			document.getElementById('prodBadge').value = product.badge || '';
			document.getElementById('prodDesc').value = product.desc || '';
			document.getElementById('prodImageUrl').value = product.imageUrl || '';
			// 이미지 미리보기
			const preview = document.getElementById('prodImagePreview');
			if (product.imageUrl) {
				preview.src = product.imageUrl;
				preview.style.display = 'block';
			} else {
				preview.style.display = 'none';
				preview.src = '';
			}
		}

		// 상품 저장 (등록/수정)
		async function saveProduct() {
			const editId = document.getElementById('productEditId').value;
			const name = document.getElementById('prodName').value.trim();
			const price = document.getElementById('prodPrice').value;
			
			if (!name) {
				alert('상품명을 입력해주세요.');
				return;
			}
			if (!price || parseInt(price) <= 0) {
				alert('올바른 가격을 입력해주세요.');
				return;
			}

			const data = {
				name: name,
				price: parseInt(price),
				category: document.getElementById('prodCategory').value,
				stock: parseInt(document.getElementById('prodStock').value) || 0,
				status: document.getElementById('prodStatus').value,
				badge: document.getElementById('prodBadge').value,
				desc: document.getElementById('prodDesc').value.trim(),
				imageUrl: document.getElementById('prodImageUrl').value.trim(),
			};

			try {
				if (editId) {
					await API.updateProduct(parseInt(editId), data);
					alert('상품이 수정되었습니다.');
				} else {
					await API.createProduct(data);
					alert('상품이 등록되었습니다.');
				}
				closeProductForm();
				renderProducts();
			} catch (e) {
				alert('오류가 발생했습니다: ' + e.message);
			}
		}

		// 상품 삭제
		async function deleteProduct(id) {
			if (!confirm('정말 이 상품을 삭제하시겠습니까?')) return;
			try {
				await API.deleteProduct(id);
				alert('상품이 삭제되었습니다.');
				renderProducts();
			} catch (e) {
				alert('삭제 중 오류가 발생했습니다.');
			}
		}

		// 상품 미리보기
		function previewProduct() {
			const name = document.getElementById('prodName').value.trim() || '상품명';
			const price = parseInt(document.getElementById('prodPrice').value) || 0;
			const category = document.getElementById('prodCategory').value || '향수';
			const badge = document.getElementById('prodBadge').value || '';
			const desc = document.getElementById('prodDesc').value.trim() || '상품 설명';
			const imageUrl = document.getElementById('prodImageUrl').value.trim() || '';
			
			const previewHtml = `
				<!DOCTYPE html>
				<html lang="ko">
				<head>
					<meta charset="UTF-8">
					<meta name="viewport" content="width=device-width, initial-scale=1.0">
					<title>미리보기 - ${name}</title>
					<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Noto+Sans+KR:wght@200;300;400;500;600&display=swap" rel="stylesheet">
					<link rel="stylesheet" href="../public/css/style.css?v=7">
					<style>
						body { background: var(--sage-bg); min-height: 100vh; padding: 2rem; }
						.preview-container { max-width: 350px; margin: 2rem auto; }
						.preview-title { text-align: center; margin-bottom: 2rem; font-family: 'Cormorant Garamond', serif; color: var(--sage); }
					</style>
				</head>
				<body>
					<h1 class="preview-title">상품 미리보기</h1>
					<div class="preview-container">
						<div class="product-card">
							<div class="product-image" style="${imageUrl ? 'background-image:url('+imageUrl+');background-size:cover;' : ''}">
								${badge ? '<span class="product-badge">'+badge+'</span>' : ''}
								<button class="product-wishlist">♡</button>
							</div>
							<div class="product-info">
								<p class="product-brand">DewScent</p>
								<p class="product-name">${name}</p>
								<div class="product-rating">
									<span class="stars">★★★★★</span>
									<span class="rating-count">(0)</span>
								</div>
								<p class="product-price">₩${price.toLocaleString()}</p>
							</div>
						</div>
						<div style="margin-top:1.5rem;padding:1rem;background:#fff;border-radius:12px;border:1px solid var(--border);">
							<p style="font-size:.85rem;color:var(--light);margin-bottom:.5rem;">카테고리: ${category}</p>
							<p style="font-size:.9rem;line-height:1.6;">${desc}</p>
						</div>
					</div>
				</body>
				</html>
			`;
			
			const previewWindow = window.open('', '_blank', 'width=500,height=700');
			previewWindow.document.write(previewHtml);
			previewWindow.document.close();
		}

		// ========== 배너 관리 ==========
		function renderBanners() {
			const tbody = document.getElementById('bannersTableBody');
			if (!tbody) return;
			const banners = API.getBanners();
			if (!banners.length) {
				tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">등록된 배너가 없습니다.</td></tr>`;
				return;
			}
			tbody.innerHTML = banners.sort((a,b) => a.order - b.order).map(b => `
				<tr>
					<td>${b.order}</td>
					<td style="font-weight:500;">${b.title}<br><span style="font-size:.8rem;color:var(--light)">${b.subtitle || ''}</span></td>
					<td style="font-size:.85rem;">${b.link || '-'}</td>
					<td><span class="status-badge ${b.active ? 'answered' : 'waiting'}">${b.active ? '활성' : '비활성'}</span></td>
					<td>
						<button class="badge" style="cursor:pointer;font-size:.7rem;" onclick="editBanner(${b.id})">수정</button>
						<button class="badge" style="cursor:pointer;font-size:.7rem;color:var(--rose);" onclick="deleteBanner(${b.id})">삭제</button>
					</td>
				</tr>
			`).join('');
		}
		function openBannerForm() {
			document.getElementById('bannerFormWrap').style.display = 'block';
			document.getElementById('bannerFormTitle').textContent = '새 배너 등록';
			document.getElementById('bannerEditId').value = '';
			document.getElementById('bannerTitle').value = '';
			document.getElementById('bannerSubtitle').value = '';
			document.getElementById('bannerLink').value = 'pages/products.php';
			document.getElementById('bannerOrder').value = '1';
			document.getElementById('bannerImageUrl').value = '';
			document.getElementById('bannerActive').checked = true;
			document.getElementById('bannerImagePreview').style.display = 'none';
			document.getElementById('bannerImagePreview').src = '';
		}
		function closeBannerForm() { document.getElementById('bannerFormWrap').style.display = 'none'; }
		function editBanner(id) {
			const banners = API.getBanners();
			const b = banners.find(x => x.id === id);
			if (!b) return;
			document.getElementById('bannerFormWrap').style.display = 'block';
			document.getElementById('bannerFormTitle').textContent = '배너 수정';
			document.getElementById('bannerEditId').value = id;
			document.getElementById('bannerTitle').value = b.title || '';
			document.getElementById('bannerSubtitle').value = b.subtitle || '';
			document.getElementById('bannerLink').value = b.link || '';
			document.getElementById('bannerOrder').value = b.order || 1;
			document.getElementById('bannerImageUrl').value = b.imageUrl || '';
			document.getElementById('bannerActive').checked = !!b.active;
			// 이미지 미리보기
			const preview = document.getElementById('bannerImagePreview');
			if (b.imageUrl) {
				preview.src = b.imageUrl;
				preview.style.display = 'block';
			} else {
				preview.style.display = 'none';
				preview.src = '';
			}
		}
		function saveBanner() { const editId = document.getElementById('bannerEditId').value; const title = document.getElementById('bannerTitle').value.trim(); if (!title) { alert('제목을 입력해주세요.'); return; } const data = { title, subtitle: document.getElementById('bannerSubtitle').value.trim(), link: document.getElementById('bannerLink').value.trim(), order: parseInt(document.getElementById('bannerOrder').value) || 1, imageUrl: document.getElementById('bannerImageUrl').value.trim(), active: document.getElementById('bannerActive').checked }; let banners = API.getBanners(); if (editId) { const idx = banners.findIndex(b => b.id === parseInt(editId)); if (idx !== -1) banners[idx] = { ...banners[idx], ...data }; } else { data.id = Date.now(); banners.push(data); } API.setBanners(banners); closeBannerForm(); renderBanners(); alert('저장되었습니다.'); }
		function deleteBanner(id) { if (!confirm('정말 삭제하시겠습니까?')) return; let banners = API.getBanners().filter(b => b.id !== id); API.setBanners(banners); renderBanners(); }

		// ========== 팝업 관리 ==========
		function renderPopups() {
			const tbody = document.getElementById('popupsTableBody');
			if (!tbody) return;
			const popups = API.getPopups();
			if (!popups.length) {
				tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">등록된 팝업이 없습니다.</td></tr>`;
				return;
			}
			tbody.innerHTML = popups.sort((a,b) => a.order - b.order).map(p => `
				<tr>
					<td>${p.order}</td>
					<td style="font-weight:500;">${p.title}</td>
					<td style="font-size:.85rem;">${p.startDate || '상시'} ~ ${p.endDate || '상시'}</td>
					<td><span class="status-badge ${p.active ? 'answered' : 'waiting'}">${p.active ? '활성' : '비활성'}</span></td>
					<td>
						<button class="badge" style="cursor:pointer;font-size:.7rem;" onclick="editPopup(${p.id})">수정</button>
						<button class="badge" style="cursor:pointer;font-size:.7rem;color:var(--rose);" onclick="deletePopup(${p.id})">삭제</button>
					</td>
				</tr>
			`).join('');
		}
		function openPopupForm() {
			document.getElementById('popupFormWrap').style.display = 'block';
			document.getElementById('popupFormTitle').textContent = '새 팝업 등록';
			document.getElementById('popupEditId').value = '';
			document.getElementById('popupTitle').value = '';
			document.getElementById('popupLink').value = '';
			document.getElementById('popupStartDate').value = '';
			document.getElementById('popupEndDate').value = '';
			document.getElementById('popupOrder').value = '1';
			document.getElementById('popupContent').value = '';
			document.getElementById('popupImageUrl').value = '';
			document.getElementById('popupActive').checked = true;
			document.getElementById('popupImagePreview').style.display = 'none';
			document.getElementById('popupImagePreview').src = '';
		}
		function closePopupForm() { document.getElementById('popupFormWrap').style.display = 'none'; }
		function editPopup(id) {
			const popups = API.getPopups();
			const p = popups.find(x => x.id === id);
			if (!p) return;
			document.getElementById('popupFormWrap').style.display = 'block';
			document.getElementById('popupFormTitle').textContent = '팝업 수정';
			document.getElementById('popupEditId').value = id;
			document.getElementById('popupTitle').value = p.title || '';
			document.getElementById('popupLink').value = p.link || '';
			document.getElementById('popupStartDate').value = p.startDate || '';
			document.getElementById('popupEndDate').value = p.endDate || '';
			document.getElementById('popupOrder').value = p.order || 1;
			document.getElementById('popupContent').value = p.content || '';
			document.getElementById('popupImageUrl').value = p.imageUrl || '';
			document.getElementById('popupActive').checked = !!p.active;
			// 이미지 미리보기
			const preview = document.getElementById('popupImagePreview');
			if (p.imageUrl) {
				preview.src = p.imageUrl;
				preview.style.display = 'block';
			} else {
				preview.style.display = 'none';
				preview.src = '';
			}
		}
		function savePopup() { const editId = document.getElementById('popupEditId').value; const title = document.getElementById('popupTitle').value.trim(); if (!title) { alert('제목을 입력해주세요.'); return; } const data = { title, link: document.getElementById('popupLink').value.trim(), startDate: document.getElementById('popupStartDate').value, endDate: document.getElementById('popupEndDate').value, order: parseInt(document.getElementById('popupOrder').value) || 1, content: document.getElementById('popupContent').value.trim(), imageUrl: document.getElementById('popupImageUrl').value.trim(), active: document.getElementById('popupActive').checked }; let popups = API.getPopups(); if (editId) { const idx = popups.findIndex(p => p.id === parseInt(editId)); if (idx !== -1) popups[idx] = { ...popups[idx], ...data }; } else { data.id = Date.now(); popups.push(data); } API.setPopups(popups); closePopupForm(); renderPopups(); alert('저장되었습니다.'); }
		function deletePopup(id) { if (!confirm('정말 삭제하시겠습니까?')) return; let popups = API.getPopups().filter(p => p.id !== id); API.setPopups(popups); renderPopups(); }

		// ========== 감정 카드 관리 ==========
		function renderEmotions() {
			const tbody = document.getElementById('emotionsTableBody');
			if (!tbody) return;
			const emotions = API.getEmotions();
			if (!emotions.length) {
				tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--light)">등록된 감정 카드가 없습니다.</td></tr>';
				return;
			}
			tbody.innerHTML = emotions.sort((a,b) => a.order - b.order).map(e => `
				<tr>
					<td>${e.order || 1}</td>
					<td style="font-family:monospace;">${e.key || ''}</td>
					<td style="font-weight:500;">${e.title || ''}</td>
					<td>${e.desc || ''}</td>
					<td><span class="status-badge ${e.active ? 'answered' : 'waiting'}">${e.active ? '활성' : '비활성'}</span></td>
					<td>
						<button class="badge" style="cursor:pointer;" onclick="editEmotion(${e.id})">수정</button>
						<button class="badge" style="cursor:pointer;color:#d88;" onclick="deleteEmotion(${e.id})">삭제</button>
					</td>
				</tr>
			`).join('');
		}
		
		function openEmotionForm() {
			document.getElementById('emotionFormWrap').style.display = 'block';
			document.getElementById('emotionFormTitle').textContent = '새 감정 등록';
			document.getElementById('emotionEditId').value = '';
			document.getElementById('emotionKey').value = '';
			document.getElementById('emotionCardTitle').value = '';
			document.getElementById('emotionCardDesc').value = '';
			document.getElementById('emotionOrder').value = '1';
			document.getElementById('emotionActive').checked = true;
		}
		function closeEmotionForm() { document.getElementById('emotionFormWrap').style.display = 'none'; }
		
		function editEmotion(id) {
			const emotions = API.getEmotions();
			const e = emotions.find(x => x.id === id);
			if (!e) return;
			document.getElementById('emotionFormWrap').style.display = 'block';
			document.getElementById('emotionFormTitle').textContent = '감정 수정';
			document.getElementById('emotionEditId').value = id;
			document.getElementById('emotionKey').value = e.key || '';
			document.getElementById('emotionCardTitle').value = e.title || '';
			document.getElementById('emotionCardDesc').value = e.desc || '';
			document.getElementById('emotionOrder').value = e.order || 1;
			document.getElementById('emotionActive').checked = !!e.active;
		}
		
		function saveEmotion() {
			const editId = document.getElementById('emotionEditId').value;
			const title = document.getElementById('emotionCardTitle').value.trim();
			if (!title) { alert('제목을 입력해주세요.'); return; }
			const data = {
				key: document.getElementById('emotionKey').value.trim() || 'custom',
				title,
				desc: document.getElementById('emotionCardDesc').value.trim(),
				order: parseInt(document.getElementById('emotionOrder').value) || 1,
				active: document.getElementById('emotionActive').checked
			};
			let emotions = API.getEmotions();
			if (editId) {
				const idx = emotions.findIndex(e => e.id === parseInt(editId));
				if (idx !== -1) emotions[idx] = { ...emotions[idx], ...data };
			} else {
				data.id = Date.now();
				emotions.push(data);
			}
			API.setEmotions(emotions);
			closeEmotionForm();
			renderEmotions();
			alert('저장되었습니다.');
		}
		
		function deleteEmotion(id) {
			if (!confirm('정말 삭제하시겠습니까?')) return;
			let emotions = API.getEmotions().filter(e => e.id !== id);
			API.setEmotions(emotions);
			renderEmotions();
		}

		// ========== 미리보기 함수들 ==========
		function previewBannerSlider() {
			const banners = API.getActiveBanners();
			if (banners.length === 0) {
				alert('활성화된 배너가 없습니다.');
				return;
			}
			const previewHtml = `
				<div style="background:#222;padding:2rem;border-radius:16px;max-width:600px;margin:auto;">
					<h3 style="color:#fff;text-align:center;margin-bottom:1rem;">배너 슬라이더 미리보기</h3>
					<div style="display:flex;gap:1rem;overflow-x:auto;padding:1rem 0;">
						${banners.map((b, i) => `
							<div style="min-width:200px;background:linear-gradient(135deg,${i%2===0?'#d6e2cf':'#f8dde1'},${i%2===0?'#5f7161':'#c96473'});padding:1.5rem;border-radius:12px;text-align:center;">
								<div style="color:#fff;font-size:.8rem;opacity:.8;">이벤트 ${b.order || i+1}</div>
								<div style="color:#fff;font-weight:600;margin-top:.5rem;">${b.title}</div>
								<div style="color:#fff;font-size:.85rem;opacity:.9;margin-top:.25rem;">${b.subtitle || ''}</div>
							</div>
						`).join('')}
					</div>
					<p style="color:#aaa;text-align:center;font-size:.8rem;margin-top:1rem;">← 스크롤하여 모든 배너 확인 →</p>
				</div>
			`;
			showPreviewModal(previewHtml);
		}
		
		function previewPopup() {
			const popups = API.getActivePopups();
			if (popups.length === 0) {
				alert('활성화된 팝업이 없습니다.');
				return;
			}
			const p = popups[0];
			const previewHtml = `
				<div style="background:#fff;border-radius:16px;max-width:400px;margin:auto;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
					${p.imageUrl ? `<div style="height:150px;background:var(--sage-lighter);"><img src="${p.imageUrl}" style="width:100%;height:100%;object-fit:cover;"></div>` : ''}
					<div style="padding:1.5rem;">
						<h3 style="font-family:'Cormorant Garamond',serif;font-size:1.3rem;margin-bottom:.5rem;">${p.title}</h3>
						${p.content ? `<p style="color:var(--mid);font-size:.9rem;line-height:1.6;">${p.content}</p>` : ''}
						<div style="display:flex;gap:.5rem;margin-top:1rem;">
							<button class="form-btn secondary" style="flex:1;">닫기</button>
							<button class="form-btn" style="flex:1;background:transparent;color:var(--light);border:1px solid var(--border);">일주일간 안보기</button>
						</div>
					</div>
				</div>
			`;
			showPreviewModal(previewHtml);
		}
		
		function previewEmotions() {
			const emotions = API.getActiveEmotions();
			const colors = { calm: '#5f7161', warm: '#c96473', fresh: '#94b1c4', romantic: '#dfa0ab', focus: '#b6a273', refresh: '#d6e2cf' };
			const previewHtml = `
				<div style="background:var(--cream);padding:2rem;border-radius:16px;max-width:700px;margin:auto;">
					<h3 style="text-align:center;margin-bottom:1.5rem;">감정 카드 미리보기</h3>
					<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;">
						${emotions.map(e => `
							<div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:1.5rem;text-align:center;">
								<div style="width:40px;height:40px;border-radius:50%;background:${colors[e.key] || '#888'};margin:0 auto .75rem;"></div>
								<div style="font-weight:500;font-size:.95rem;">${e.title}</div>
								<div style="color:var(--light);font-size:.8rem;margin-top:.25rem;">${e.desc}</div>
							</div>
						`).join('')}
					</div>
				</div>
			`;
			showPreviewModal(previewHtml);
		}
		
		function showPreviewModal(content) {
			let modal = document.getElementById('adminPreviewModal');
			if (!modal) {
				modal = document.createElement('div');
				modal.id = 'adminPreviewModal';
				modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;';
				modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
				document.body.appendChild(modal);
			}
			modal.innerHTML = `
				<div style="position:relative;max-height:90vh;overflow-y:auto;">
					<button onclick="document.getElementById('adminPreviewModal').remove()" style="position:absolute;top:10px;right:10px;background:#fff;border:none;width:32px;height:32px;border-radius:50%;font-size:1.2rem;cursor:pointer;z-index:10;">×</button>
					${content}
				</div>
			`;
		}
		
		// ========== 향기 테스트 관리 ==========
		function checkWelcomeStatus() {
			const WELCOME_HIDE_KEY = 'dewscent_welcome_hidden';
			const hideUntil = localStorage.getItem(WELCOME_HIDE_KEY);
			const statusEl = document.getElementById('welcomeStatusText');
			if (!hideUntil) {
				statusEl.textContent = '향기 테스트 팝업이 정상적으로 표시됩니다.';
				statusEl.style.color = 'var(--sage)';
			} else {
				const until = new Date(parseInt(hideUntil));
				if (Date.now() < parseInt(hideUntil)) {
					statusEl.textContent = `${until.toLocaleDateString('ko-KR')} ${until.toLocaleTimeString('ko-KR')}까지 숨김 상태입니다.`;
					statusEl.style.color = 'var(--rose)';
				} else {
					statusEl.textContent = '숨김 기간이 만료되어 팝업이 표시됩니다.';
					statusEl.style.color = 'var(--sage)';
				}
			}
		}
		
		function resetWelcomeHidden() {
			const WELCOME_HIDE_KEY = 'dewscent_welcome_hidden';
			localStorage.removeItem(WELCOME_HIDE_KEY);
			alert('향기 테스트 팝업 숨김이 초기화되었습니다.\n이제 메인 페이지에서 다시 팝업이 표시됩니다.');
			checkWelcomeStatus();
		}
		
		// ========== 메인 상품 자동 선택 ==========
		function autoSelectBest() {
			API.getProducts().then(products => {
				const bestIds = products.filter(p => p.badge === 'BEST' && p.status === '판매중').map(p => p.id);
				if (bestIds.length === 0) {
					alert('BEST 태그가 있는 판매중인 상품이 없습니다.');
					return;
				}
				API.setMainProductIds(bestIds);
				renderMainProducts();
				alert(`BEST 상품 ${bestIds.length}개가 선택되었습니다.`);
			});
		}
		
		function autoSelectNew() {
			API.getProducts().then(products => {
				const newIds = products.filter(p => p.badge === 'NEW' && p.status === '판매중').map(p => p.id);
				if (newIds.length === 0) {
					alert('NEW 태그가 있는 판매중인 상품이 없습니다.');
					return;
				}
				API.setMainProductIds(newIds);
				renderMainProducts();
				alert(`NEW 상품 ${newIds.length}개가 선택되었습니다.`);
			});
		}
		
		function autoSelectBestAndNew() {
			API.getProducts().then(products => {
				const ids = products.filter(p => (p.badge === 'BEST' || p.badge === 'NEW') && p.status === '판매중').map(p => p.id);
				if (ids.length === 0) {
					alert('BEST 또는 NEW 태그가 있는 판매중인 상품이 없습니다.');
					return;
				}
				API.setMainProductIds(ids);
				renderMainProducts();
				alert(`BEST/NEW 상품 ${ids.length}개가 선택되었습니다.`);
			});
		}
		
		function clearMainProducts() {
			API.setMainProductIds([]);
			renderMainProducts();
			alert('선택이 해제되었습니다. (상위 4개 상품이 자동 표시됩니다)');
		}

		// ========== 섹션 타이틀 관리 ==========
		function renderSectionsForm() {
			const sections = API.getSections();
			document.getElementById('sectionEmotionLabel').value = sections.emotionLabel || '';
			document.getElementById('sectionEmotionTitle').value = sections.emotionTitle || '';
			document.getElementById('sectionEmotionSubtitle').value = sections.emotionSubtitle || '';
			document.getElementById('sectionBestLabel').value = sections.bestLabel || '';
			document.getElementById('sectionBestTitle').value = sections.bestTitle || '';
			document.getElementById('sectionBestSubtitle').value = sections.bestSubtitle || '';
			document.getElementById('sectionBestQuote').value = sections.bestQuote || '';
		}
		
		function saveSections() {
			const data = {
				emotionLabel: document.getElementById('sectionEmotionLabel').value.trim(),
				emotionTitle: document.getElementById('sectionEmotionTitle').value.trim(),
				emotionSubtitle: document.getElementById('sectionEmotionSubtitle').value.trim(),
				bestLabel: document.getElementById('sectionBestLabel').value.trim(),
				bestTitle: document.getElementById('sectionBestTitle').value.trim(),
				bestSubtitle: document.getElementById('sectionBestSubtitle').value.trim(),
				bestQuote: document.getElementById('sectionBestQuote').value.trim(),
			};
			API.setSections(data);
			alert('저장되었습니다.');
		}

		// ========== 메인 상품 배치 ==========
		function renderMainProducts() {
			const container = document.getElementById('mainProductsGrid');
			if (!container) return;
			API.getProducts().then(products => {
				const selectedIds = API.getMainProductIds();
				container.innerHTML = products.map(p => `
					<label style="display:flex;gap:.5rem;padding:.75rem;border:1px solid var(--border);border-radius:8px;cursor:pointer;background:${selectedIds.includes(p.id) ? 'var(--sage-bg)' : '#fff'};">
						<input type="checkbox" class="main-product-check" value="${p.id}" ${selectedIds.includes(p.id) ? 'checked' : ''}>
						<div>
							<strong style="font-size:.9rem;">${p.name}</strong><br>
							<span style="font-size:.8rem;color:var(--light);">₩${p.price.toLocaleString()}</span>
						</div>
					</label>
				`).join('');
			});
		}
		function saveMainProducts() {
			const checks = document.querySelectorAll('.main-product-check:checked');
			const ids = Array.from(checks).map(c => parseInt(c.value));
			API.setMainProductIds(ids);
			alert('저장되었습니다.');
			renderMainProducts();
		}

		// ========== 리뷰 관리 ==========
		function renderAdminReviews() {
			const container = document.getElementById('reviewsAdminBody');
			if (!container) return;
			const allReviews = JSON.parse(localStorage.getItem('dewscent_reviews') || '{}');
			let html = '';
			API.getProducts().then(products => {
				products.forEach(p => {
					const reviews = allReviews[p.id] || [];
					if (reviews.length > 0) {
						html += `<div style="margin-bottom:1.5rem;"><h4 style="font-size:.95rem;margin-bottom:.5rem;">${p.name} <span style="color:var(--light);font-weight:400;">(${reviews.length}개)</span></h4>`;
						reviews.forEach(r => {
							html += `<div style="background:var(--sage-bg);padding:.75rem;border-radius:8px;margin-bottom:.5rem;display:flex;justify-content:space-between;align-items:flex-start;">
								<div><strong>${r.user}</strong> <span style="color:var(--light);font-size:.8rem;">${r.date}</span><br><span style="color:#d4a574;">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</span><br><span style="font-size:.9rem;">${r.content}</span></div>
								<button class="badge" style="cursor:pointer;font-size:.7rem;color:var(--rose);" onclick="deleteReview(${p.id},${r.id})">삭제</button>
							</div>`;
						});
						html += '</div>';
					}
				});
				container.innerHTML = html || '<p style="text-align:center;color:var(--light);padding:2rem;">등록된 리뷰가 없습니다.</p>';
			});
		}
		function deleteReview(productId, reviewId) {
			if (!confirm('정말 삭제하시겠습니까?')) return;
			const allReviews = JSON.parse(localStorage.getItem('dewscent_reviews') || '{}');
			if (allReviews[productId]) {
				allReviews[productId] = allReviews[productId].filter(r => r.id !== reviewId);
				localStorage.setItem('dewscent_reviews', JSON.stringify(allReviews));
			}
			renderAdminReviews();
		}

		// ========== 사이트 설정 ==========
		function renderSiteSettings() {
			const s = API.getSiteSettings();
			document.getElementById('settingSiteName').value = s.siteName || '';
			document.getElementById('settingSlogan').value = s.siteSlogan || '';
			document.getElementById('settingEmail').value = s.contactEmail || '';
			document.getElementById('settingPhone').value = s.contactPhone || '';
			document.getElementById('settingAddress').value = s.address || '';
			document.getElementById('settingHours').value = s.businessHours || '';
			document.getElementById('settingKakao').value = s.kakaoChannel || '';
			document.getElementById('settingInstagram').value = s.instagramUrl || '';
		}
		function saveSiteSettings() {
			const settings = {
				siteName: document.getElementById('settingSiteName').value.trim(),
				siteSlogan: document.getElementById('settingSlogan').value.trim(),
				contactEmail: document.getElementById('settingEmail').value.trim(),
				contactPhone: document.getElementById('settingPhone').value.trim(),
				address: document.getElementById('settingAddress').value.trim(),
				businessHours: document.getElementById('settingHours').value.trim(),
				kakaoChannel: document.getElementById('settingKakao').value.trim(),
				instagramUrl: document.getElementById('settingInstagram').value.trim(),
			};
			API.setSiteSettings(settings);
			alert('저장되었습니다.');
		}

		// ========== 이미지 업로드 (Base64) ==========
		function uploadProductImage(input) {
			if (input.files && input.files[0]) {
				const file = input.files[0];
				if (file.size > 2 * 1024 * 1024) {
					alert('이미지 크기는 2MB 이하로 제한됩니다.');
					input.value = '';
					return;
				}
				const reader = new FileReader();
				reader.onload = function(e) {
					document.getElementById('prodImageUrl').value = e.target.result;
					const preview = document.getElementById('prodImagePreview');
					preview.src = e.target.result;
					preview.style.display = 'block';
				};
				reader.readAsDataURL(file);
			}
		}

		function uploadBannerImage(input) {
			if (input.files && input.files[0]) {
				const file = input.files[0];
				if (file.size > 2 * 1024 * 1024) {
					alert('이미지 크기는 2MB 이하로 제한됩니다.');
					input.value = '';
					return;
				}
				const reader = new FileReader();
				reader.onload = function(e) {
					document.getElementById('bannerImageUrl').value = e.target.result;
					const preview = document.getElementById('bannerImagePreview');
					preview.src = e.target.result;
					preview.style.display = 'block';
				};
				reader.readAsDataURL(file);
			}
		}

		function uploadPopupImage(input) {
			if (input.files && input.files[0]) {
				const file = input.files[0];
				if (file.size > 2 * 1024 * 1024) {
					alert('이미지 크기는 2MB 이하로 제한됩니다.');
					input.value = '';
					return;
				}
				const reader = new FileReader();
				reader.onload = function(e) {
					document.getElementById('popupImageUrl').value = e.target.result;
					const preview = document.getElementById('popupImagePreview');
					preview.src = e.target.result;
					preview.style.display = 'block';
				};
				reader.readAsDataURL(file);
			}
		}

		// 폼 열 때 미리보기 초기화
		const origOpenProductForm = openProductForm;
		openProductForm = function() {
			origOpenProductForm();
			document.getElementById('prodImagePreview').style.display = 'none';
			document.getElementById('prodImagePreview').src = '';
		};

		// ========== 탭 전환 ==========
		const allTabs = ['overview','banners','popups','emotions','sections','mainproducts','products','reviews','inquiries','users','orders','settings'];
		document.querySelectorAll('.admin-tab').forEach((btn) => {
			btn.addEventListener('click', () => {
				document.querySelectorAll('.admin-tab').forEach((b) => b.classList.remove('active'));
				btn.classList.add('active');
				const tab = btn.dataset.tab;
				allTabs.forEach((t)=>{
					const el = document.getElementById('tab-' + t);
					if (!el) return;
					el.style.display = (t === tab) ? '' : 'none';
				});
				// 첫 진입 시 데이터 로딩
				if (!loaded[tab]) {
					if (tab === 'users') renderUsers();
					if (tab === 'orders') renderAdminOrders();
					if (tab === 'products') renderProducts();
					if (tab === 'inquiries') renderAdminInquiries();
					if (tab === 'banners') renderBanners();
					if (tab === 'popups') renderPopups();
					if (tab === 'emotions') renderEmotions();
					if (tab === 'sections') renderSectionsForm();
					if (tab === 'mainproducts') renderMainProducts();
					if (tab === 'reviews') renderAdminReviews();
					if (tab === 'settings') renderSiteSettings();
					loaded[tab] = true;
				}
			});
		});

		// 기본 로드: 개요 표시 + KPI 업데이트
		function updateKPIs() {
			const inquiries = getInquiries();
			const waiting = inquiries.filter(inq => inq.status === 'waiting').length;
			document.getElementById('kpi-waiting-inquiries').textContent = waiting;
			document.getElementById('kpi-total-inquiries').textContent = inquiries.length;
		}
		updateKPIs();
	</script>
</body>
</html>


