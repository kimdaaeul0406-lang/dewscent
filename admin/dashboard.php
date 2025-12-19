<?php
// 에러 리포팅 임시 활성화 (배포 서버 디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 0); // 화면에는 표시하지 않음
ini_set('log_errors', 1); // 로그 파일에만 기록

// config.php에서 세션 시작하므로 여기서는 시작하지 않음
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/guard.php';

// 디버깅: 세션 상태 로깅 (세션 시작 후)
error_log('[Admin Dashboard] ========== Dashboard 접근 ==========');
error_log('[Admin Dashboard] Request URI: ' . $_SERVER['REQUEST_URI']);
error_log('[Admin Dashboard] PHP_SELF: ' . $_SERVER['PHP_SELF']);
error_log('[Admin Dashboard] SCRIPT_NAME: ' . $_SERVER['SCRIPT_NAME']);
error_log('[Admin Dashboard] Session Status: ' . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE'));
error_log('[Admin Dashboard] Session ID: ' . session_id());
error_log('[Admin Dashboard] Session Save Path: ' . session_save_path());
error_log('[Admin Dashboard] Session Data: ' . json_encode($_SESSION, JSON_UNESCAPED_UNICODE));
error_log('[Admin Dashboard] admin_logged_in: ' . (isset($_SESSION['admin_logged_in']) ? ($_SESSION['admin_logged_in'] ? 'true' : 'false') : 'not set'));
error_log('[Admin Dashboard] role: ' . ($_SESSION['role'] ?? 'not set'));
error_log('[Admin Dashboard] user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
error_log('[Admin Dashboard] is_admin check before ensure_admin');

// 권한 체크 (리다이렉트되면 여기서 종료)
ensure_admin();

error_log('[Admin Dashboard] Access granted - continuing');

$pageTitle = "관리자 대시보드 | DewScent";
$adminEmail = $_SESSION['admin_email'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= htmlspecialchars($pageTitle) ?></title>
	<!-- DNS Prefetch -->
	<link rel="dns-prefetch" href="https://fonts.googleapis.com">
	<link rel="dns-prefetch" href="https://fonts.gstatic.com">
	<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
	
	<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Noto+Sans+KR:wght@200;300;400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../public/css/style.css?v=7">
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
	<script>
		// API 기본 URL 설정 (관리자 대시보드용)
		window.DS_BASE_URL = "<?php echo rtrim(SITE_URL, '/'); ?>";
	</script>
	<script src="../public/js/api.js?v=6"></script>
	<script>
		// API 로드 확인 및 에러 처리
		if (typeof window.API === 'undefined') {
			console.error('API 객체가 로드되지 않았습니다. api.js 파일을 확인해주세요.');
			// API가 없을 때 대체 처리
			window.API = {
				getUsers: () => Promise.reject(new Error('API is not defined')),
				getAdminOrders: () => Promise.reject(new Error('API is not defined')),
				getInquiries: () => Promise.reject(new Error('API is not defined')),
				getCoupons: () => Promise.reject(new Error('API is not defined')),
				getReviews: () => Promise.reject(new Error('API is not defined')),
				getBanners: () => Promise.reject(new Error('API is not defined')),
				getPopups: () => Promise.reject(new Error('API is not defined')),
				getEmotions: () => Promise.reject(new Error('API is not defined')),
			};
		}
	</script>
	<style>
		/* 관리 영역 간단 레이아웃 */
		.admin-wrap { max-width: 1100px; margin: 0 auto; }
		.admin-top { display:flex; justify-content: space-between; align-items:center; margin-bottom: 1rem; }
		.admin-tabs { display:flex; gap:.5rem; flex-wrap: wrap; justify-content: flex-start; align-items: center; }
		.admin-tab { padding:.5rem 1rem; border:1px solid var(--border); border-radius:999px; background:#fff; cursor:pointer; font-size:.85rem; white-space: nowrap; }
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
					<button class="admin-tab" data-tab="coupons">쿠폰</button>
					<button class="admin-tab" data-tab="notices">공지/이벤트</button>
					<button class="admin-tab" data-tab="settings">설정</button>
					</div>
				</div>

				<div class="admin-card" id="tab-overview">
					<div class="kpis">
						<div class="kpi">
							<h4>오늘 가입</h4>
							<strong id="kpi-today-signups">0</strong>
						</div>
						<div class="kpi">
							<h4>오늘 주문</h4>
							<strong id="kpi-today-orders">0</strong>
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
					
					<!-- 통계 그래프 섹션 -->
					<div style="margin-top:2rem;padding-top:2rem;border-top:2px solid var(--border);">
						<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
							<h3 style="margin:0;font-size:1.1rem;font-weight:600;color:var(--sage);">전체 통계</h3>
							<div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
								<input type="date" id="statFromDate" style="padding:0.5rem;border:1px solid var(--border);border-radius:6px;font-size:0.85rem;">
								<span style="color:var(--mid);">~</span>
								<input type="date" id="statToDate" style="padding:0.5rem;border:1px solid var(--border);border-radius:6px;font-size:0.85rem;">
								<button onclick="loadStatistics()" style="padding:0.5rem 1rem;background:var(--sage);color:#fff;border:none;border-radius:6px;font-size:0.85rem;cursor:pointer;font-weight:500;">조회</button>
								<button onclick="resetDateRange()" style="padding:0.5rem 1rem;background:var(--border);color:var(--mid);border:none;border-radius:6px;font-size:0.85rem;cursor:pointer;">초기화</button>
							</div>
						</div>
						
						<!-- 통계 요약 -->
						<div id="statSummary" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:2rem;">
							<div style="background:var(--sage-bg);padding:1rem;border-radius:8px;text-align:center;">
								<div style="font-size:0.85rem;color:var(--mid);margin-bottom:0.5rem;">총 주문</div>
								<div id="statTotalOrders" style="font-size:1.5rem;font-weight:700;color:var(--sage);">0</div>
							</div>
							<div style="background:var(--sage-bg);padding:1rem;border-radius:8px;text-align:center;">
								<div style="font-size:0.85rem;color:var(--mid);margin-bottom:0.5rem;">총 매출</div>
								<div id="statTotalRevenue" style="font-size:1.5rem;font-weight:700;color:var(--sage);">₩0</div>
							</div>
							<div style="background:var(--sage-bg);padding:1rem;border-radius:8px;text-align:center;">
								<div style="font-size:0.85rem;color:var(--mid);margin-bottom:0.5rem;">평균 주문금액</div>
								<div id="statAvgOrder" style="font-size:1.5rem;font-weight:700;color:var(--sage);">₩0</div>
							</div>
							<div style="background:var(--sage-bg);padding:1rem;border-radius:8px;text-align:center;">
								<div style="font-size:0.85rem;color:var(--mid);margin-bottom:0.5rem;">고유 고객</div>
								<div id="statCustomers" style="font-size:1.5rem;font-weight:700;color:var(--sage);">0</div>
							</div>
						</div>
						
						<!-- 그래프 컨테이너 -->
						<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(400px, 1fr));gap:1.5rem;margin-bottom:2rem;">
							<div style="background:var(--white);padding:1.5rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
								<h4 style="margin:0 0 1rem 0;font-size:1rem;font-weight:600;color:var(--sage);">일별 주문 수</h4>
								<canvas id="ordersChart"></canvas>
							</div>
							<div style="background:var(--white);padding:1.5rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
								<h4 style="margin:0 0 1rem 0;font-size:1rem;font-weight:600;color:var(--sage);">일별 매출</h4>
								<canvas id="revenueChart"></canvas>
							</div>
							<div style="background:var(--white);padding:1.5rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
								<h4 style="margin:0 0 1rem 0;font-size:1rem;font-weight:600;color:var(--sage);">일별 가입자</h4>
								<canvas id="signupsChart"></canvas>
							</div>
							<div style="background:var(--white);padding:1.5rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
								<h4 style="margin:0 0 1rem 0;font-size:1rem;font-weight:600;color:var(--sage);">주문 상태별 통계</h4>
								<canvas id="statusChart"></canvas>
							</div>
						</div>
					</div>
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
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
						<h3 style="margin:0;font-size:1rem;">주문 목록</h3>
						<button class="badge" style="cursor:pointer;font-size:.7rem;background:var(--sage);color:#fff;" onclick="renderAdminOrders()">새로고침</button>
					</div>
					<table class="table">
						<thead>
							<tr>
								<th>주문번호</th>
								<th>고객 (회원/비회원)</th>
								<th>금액</th>
								<th>상태</th>
								<th>주문일</th>
								<th>관리</th>
							</tr>
						</thead>
						<tbody id="ordersTableBody">
							<tr><td colspan="6" style="text-align:center;color:var(--light)">데이터 없음 (연동 예정)</td></tr>
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
								<label style="font-size:.8rem;color:var(--light);">카테고리</label>
								<select id="prodCategory" class="form-input" onchange="updateVolumeOptions()">
									<option value="향수">향수</option>
									<option value="바디미스트">바디미스트</option>
									<option value="헤어미스트">헤어미스트</option>
									<option value="디퓨저">디퓨저</option>
									<option value="섬유유연제">섬유유연제</option>
									<option value="룸스프레이">룸스프레이</option>
								</select>
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">향으로 찾기</label>
								<select id="prodFragranceType" class="form-input">
									<option value="">선택 안함</option>
									<option value="시트러스">시트러스</option>
									<option value="플로럴">플로럴</option>
									<option value="우디">우디</option>
									<option value="머스크">머스크</option>
									<option value="오리엔탈">오리엔탈</option>
									<option value="프레시">프레시</option>
								</select>
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
								<label style="font-size:.8rem;color:var(--light);">기분으로 찾기 (여러 개 선택 가능)</label>
								<div id="prodEmotionsContainer" style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.5rem;padding:.75rem;background:#f8f9fa;border-radius:4px;">
									<!-- JavaScript로 동적 생성 -->
								</div>
							</div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">상품 설명</label>
								<textarea id="prodDesc" class="form-input" rows="2" placeholder="상품에 대한 간단한 설명" style="resize:none;"></textarea>
							</div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">상품 카드 이미지 (메인페이지 표시용)</label>
								<div class="image-upload-wrap">
									<input type="text" id="prodImageUrl" class="form-input" placeholder="URL 입력 또는 파일 업로드">
									<input type="file" id="prodImageFile" class="image-upload-input" accept="image/*" onchange="uploadProductImage(this)">
									<button type="button" class="image-upload-btn" onclick="document.getElementById('prodImageFile').click()">파일 선택</button>
									<button type="button" class="image-upload-btn" style="background:var(--rose);" onclick="clearProductImage()" id="clearImageBtn" style="display:none;">이미지 삭제</button>
								</div>
								<img id="prodImagePreview" class="image-preview" style="display:none;">
							</div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">상품 상세 이미지 (클릭 시 모달 표시용)</label>
								<div class="image-upload-wrap">
									<input type="text" id="prodDetailImageUrl" class="form-input" placeholder="URL 입력 또는 파일 업로드 (없으면 카드 이미지 사용)">
									<input type="file" id="prodDetailImageFile" class="image-upload-input" accept="image/*" onchange="uploadProductDetailImage(this)">
									<button type="button" class="image-upload-btn" onclick="document.getElementById('prodDetailImageFile').click()">파일 선택</button>
									<button type="button" class="image-upload-btn" style="background:var(--rose);" onclick="clearProductDetailImage()" id="clearDetailImageBtn" style="display:none;">이미지 삭제</button>
								</div>
								<img id="prodDetailImagePreview" class="image-preview" style="display:none;">
							</div>
							<!-- 용량별 가격 설정 -->
							<div style="grid-column:1/-1;margin-top:1rem;padding:1rem;background:#f8f9fa;border-radius:4px;">
								<label style="font-size:.8rem;color:var(--light);display:block;margin-bottom:.5rem;">
									용량별 가격 설정
									<span style="font-size:.7rem;color:#888;">(카테고리별 용량 옵션이 자동으로 표시됩니다)</span>
								</label>
								<div id="variantsContainer" style="margin-top:.5rem;"></div>
								<button type="button" class="badge" style="cursor:pointer;background:#6b8cce;color:#fff;border:none;padding:.4rem .8rem;font-size:.75rem;margin-top:.5rem;display:none;" id="addVariantBtn" onclick="addVariantRow()">+ 용량 추가</button>
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
						<div style="width:80px;height:60px;background:var(--sage-lighter);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;"></div>
						<div>
							<strong style="color:var(--sage);">메인 슬라이더 배너</strong>
							<p style="font-size:.85rem;color:var(--mid);margin-top:.25rem;">메인 페이지 상단에 빙글빙글 돌아가는 이벤트 배너입니다.</p>
						</div>
						<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;margin-left:auto;" onclick="previewBannerSlider()">미리보기</button>
					</div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
						<div>
							<h3 style="font-size:1rem;margin-bottom:.25rem;">배너/캐러셀 관리 <span id="bannerCountText" style="font-size:.85rem;color:var(--light);font-weight:normal;"></span></h3>
							<p style="font-size:.8rem;color:var(--light);">* 최대 5개까지 등록 가능합니다. 메인 페이지 슬라이더에 표시됩니다.</p>
						</div>
						<div style="display:flex;gap:.5rem;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="openBannerForm()">+ 새 배너</button>
							<button class="badge" style="cursor:pointer;background:var(--ivory);color:#fff;border:none;padding:.5rem 1rem;" onclick="resetDefaultBanners()" title="기본 배너 5개로 초기화">기본 배너 초기화</button>
						</div>
					</div>
					<div id="bannerFormWrap" style="display:none;background:var(--sage-bg);padding:1rem;border-radius:10px;margin-bottom:1rem;">
						<h4 id="bannerFormTitle" style="margin-bottom:1rem;font-size:.95rem;">새 배너 등록</h4>
						<input type="hidden" id="bannerEditId">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
							<div><label style="font-size:.8rem;color:var(--light);">제목 *</label><input type="text" id="bannerTitle" class="form-input" placeholder="배너 제목"></div>
							<div><label style="font-size:.8rem;color:var(--light);">부제목</label><input type="text" id="bannerSubtitle" class="form-input" placeholder="부제목"></div>
							<div><label style="font-size:.8rem;color:var(--light);">링크</label><input type="text" id="bannerLink" class="form-input" placeholder="pages/products.php (클릭 시 이동할 페이지)"></div>
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
						<button class="badge" style="cursor:pointer;background:var(--rose);color:#fff;border:none;padding:.5rem 1rem;margin-left:auto;" onclick="previewPopup()">미리보기</button>
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
						<div style="width:80px;height:60px;background:var(--aqua);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;"></div>
						<div>
							<strong style="color:var(--sage);">감정 선택 카드</strong>
							<p style="font-size:.85rem;color:var(--mid);margin-top:.25rem;">메인 페이지 "오늘, 어떤 기분인가요?" 섹션의 감정 카드입니다.</p>
						</div>
						<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;margin-left:auto;" onclick="previewEmotions()">미리보기</button>
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
					
					<!-- 감정별 추천 상품 설정 모달 -->
					<div id="emotionRecommendationModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;">
						<div style="background:#fff;border-radius:16px;padding:2rem;max-width:800px;max-height:90vh;overflow-y:auto;position:relative;">
							<button onclick="closeEmotionRecommendationModal()" style="position:absolute;top:10px;right:10px;background:#fff;border:none;width:32px;height:32px;border-radius:50%;font-size:1.2rem;cursor:pointer;">×</button>
							<h3 id="emotionRecommendationTitle" style="margin-bottom:1rem;font-size:1.2rem;">추천 상품 설정</h3>
							<p style="font-size:.85rem;color:var(--light);margin-bottom:1.5rem;">이 감정 카드 클릭 시 추천될 상품을 선택하세요. (최대 10개 선택, 7일마다 4개씩 랜덤 표시)</p>
							<input type="hidden" id="emotionRecommendationKey">
							<div id="emotionRecommendationProducts" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem;"></div>
							<div style="display:flex;gap:.5rem;justify-content:flex-end;">
								<button class="badge" style="cursor:pointer;border:none;padding:.5rem 1rem;" onclick="closeEmotionRecommendationModal()">취소</button>
								<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveEmotionRecommendation()">저장</button>
							</div>
						</div>
					</div>
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
				<!-- 쿠폰 관리 -->
				<div class="admin-card" id="tab-coupons" style="display:none">
					<h3 style="margin-bottom:1rem;font-size:1rem;">쿠폰 관리</h3>
					<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;margin-bottom:1rem;" onclick="openCouponForm()">쿠폰 추가</button>
					
					<!-- 쿠폰 폼 -->
					<div id="couponForm" style="display:none;background:var(--sage-bg);padding:1.5rem;border-radius:12px;margin-bottom:1rem;">
						<input type="hidden" id="couponEditId" value="">
						<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;">
							<div>
								<label style="font-size:.8rem;color:var(--light);">쿠폰 코드</label>
								<input type="text" id="couponCode" class="form-input" placeholder="예: WELCOME10">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">쿠폰명</label>
								<input type="text" id="couponName" class="form-input" placeholder="예: 신규 회원 10% 할인">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">할인 타입</label>
								<select id="couponType" class="form-input">
									<option value="percent">퍼센트 할인</option>
									<option value="fixed">고정 금액 할인</option>
								</select>
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">할인 값</label>
								<input type="number" id="couponValue" class="form-input" placeholder="10 (퍼센트) 또는 5000 (고정)">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">최소 주문 금액</label>
								<input type="number" id="couponMinAmount" class="form-input" placeholder="0" value="0">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">최대 할인 금액 (퍼센트만)</label>
								<input type="number" id="couponMaxDiscount" class="form-input" placeholder="0 (무제한)" value="0">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">시작일</label>
								<input type="date" id="couponStartDate" class="form-input">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">종료일</label>
								<input type="date" id="couponEndDate" class="form-input">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">사용 횟수 제한</label>
								<input type="number" id="couponUsageLimit" class="form-input" placeholder="0 (무제한)" value="0">
							</div>
							<div style="display:flex;align-items:center;gap:.5rem;margin-top:1.5rem;">
								<input type="checkbox" id="couponActive" checked>
								<label style="font-size:.8rem;color:var(--light);">활성화</label>
							</div>
						</div>
						<div style="display:flex;gap:.5rem;margin-top:1rem;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveCoupon()">저장</button>
							<button class="badge" style="cursor:pointer;border:none;padding:.5rem 1rem;" onclick="closeCouponForm()">취소</button>
						</div>
					</div>
					
					<!-- 쿠폰 목록 -->
					<table class="table">
						<thead>
							<tr>
								<th>코드</th>
								<th>쿠폰명</th>
								<th>할인</th>
								<th>기간</th>
								<th>사용/제한</th>
								<th>상태</th>
								<th>관리</th>
							</tr>
						</thead>
						<tbody id="couponsTableBody">
							<tr><td colspan="7" style="text-align:center;color:var(--light)">불러오는 중...</td></tr>
						</tbody>
					</table>
				</div>

				<!-- 공지사항/이벤트 관리 -->
				<div class="admin-card" id="tab-notices" style="display:none">
					<h3 style="margin-bottom:1rem;font-size:1rem;">공지사항/이벤트 관리</h3>
					<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;margin-bottom:1rem;" onclick="openNoticeForm()">공지/이벤트 추가</button>
					
					<!-- 공지/이벤트 폼 -->
					<div id="noticeForm" style="display:none;background:var(--sage-bg);padding:1.5rem;border-radius:12px;margin-bottom:1rem;">
						<input type="hidden" id="noticeEditId" value="">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
							<div>
								<label style="font-size:.8rem;color:var(--light);">유형</label>
								<select id="noticeType" class="form-input">
									<option value="notice">공지사항</option>
									<option value="event">이벤트</option>
								</select>
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">제목</label>
								<input type="text" id="noticeTitle" class="form-input" placeholder="제목을 입력하세요">
							</div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">내용</label>
								<textarea id="noticeContent" class="form-input" rows="4" placeholder="내용을 입력하세요" style="resize:none;"></textarea>
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">시작일</label>
								<input type="date" id="noticeStartDate" class="form-input">
							</div>
							<div>
								<label style="font-size:.8rem;color:var(--light);">종료일</label>
								<input type="date" id="noticeEndDate" class="form-input">
							</div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">링크 (선택)</label>
								<input type="text" id="noticeLink" class="form-input" placeholder="클릭 시 이동할 링크 (선택사항)">
							</div>
							<div style="grid-column:1/-1;">
								<label style="font-size:.8rem;color:var(--light);">이미지</label>
								<div class="image-upload-wrap">
									<input type="text" id="noticeImageUrl" class="form-input" placeholder="URL 입력 또는 파일 업로드">
									<input type="file" id="noticeImageFile" class="image-upload-input" accept="image/*" onchange="uploadNoticeImage(this)">
									<button type="button" class="image-upload-btn" onclick="document.getElementById('noticeImageFile').click()">파일 선택</button>
								</div>
								<img id="noticeImagePreview" class="image-preview" style="display:none;">
							</div>
							<div style="display:flex;align-items:center;gap:.5rem;">
								<input type="checkbox" id="noticeActive" checked>
								<label style="font-size:.8rem;color:var(--light);">활성화</label>
							</div>
						</div>
						<div style="display:flex;gap:.5rem;margin-top:1rem;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveNotice()">저장</button>
							<button class="badge" style="cursor:pointer;border:none;padding:.5rem 1rem;" onclick="closeNoticeForm()">취소</button>
						</div>
					</div>
					
					<!-- 공지/이벤트 목록 -->
					<table class="table">
						<thead>
							<tr>
								<th>유형</th>
								<th>제목</th>
								<th>기간</th>
								<th>상태</th>
								<th>관리</th>
							</tr>
						</thead>
						<tbody id="noticesTableBody">
							<tr><td colspan="5" style="text-align:center;color:var(--light)">불러오는 중...</td></tr>
						</tbody>
					</table>
				</div>

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
					
					<!-- 향기 타입 관리 -->
					<div style="margin-top:2rem;padding-top:2rem;border-top:1px solid var(--border);">
						<h3 style="margin-bottom:1rem;font-size:1rem;">향으로 찾기 메뉴 관리</h3>
						<p style="font-size:.85rem;color:var(--light);margin-bottom:1rem;">햄버거 메뉴의 "향으로 찾기" 섹션에 표시될 향기 타입을 관리합니다.</p>
						<div id="fragranceTypesContainer" style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:1rem;">
							<!-- JavaScript로 동적 생성 -->
						</div>
						<div style="display:flex;gap:.5rem;">
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="addFragranceType()">+ 추가</button>
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.5rem 1rem;" onclick="saveFragranceTypes()">저장</button>
						</div>
					</div>
				</div>

			</div>
		</section>
	</main>

	<script>
		// 간단한 탭 전환 + 데이터 로딩
		const loaded = { overview: true, users: false, orders: false, products: false, inquiries: false, settings: true, coupons: false, notices: false };

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

		async function renderAdminInquiries() {
			const container = document.getElementById('inquiriesAdminBody');
			if (!container) return;

			if (typeof API === 'undefined') {
				container.innerHTML = `<p style="text-align:center;color:var(--rose);padding:2rem;">API를 불러오는 중입니다...</p>`;
				setTimeout(() => renderAdminInquiries(), 500);
				return;
			}

			container.innerHTML = `<p style="text-align:center;color:var(--light);padding:2rem;">불러오는 중...</p>`;

			try {
				let inquiries = await API.getInquiries();
				
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
							<span style="font-size:.8rem;color:var(--light)">${inq.user_email || inq.userId || ''}</span>
						</div>
						<div style="display:flex;align-items:center;gap:.5rem;">
							<span class="status-badge ${inq.status}">${inq.status === 'answered' ? '답변완료' : '답변대기'}</span>
							<span style="font-size:.75rem;color:var(--light)">${inq.createdAt || inq.created_at?.substring(0, 10) || ''}</span>
						</div>
					</div>
					<div class="inquiry-admin-body">
						<div class="inquiry-admin-content">
							${inq.orderNo || inq.order_no ? `<p style="font-size:.8rem;color:var(--light);margin-bottom:.5rem;">주문번호: ${inq.orderNo || inq.order_no}</p>` : ''}
							<p>${inq.content.replace(/\n/g, '<br>')}</p>
						</div>
						${inq.answer ? `
							<div style="background:var(--sage-bg);padding:.75rem;border-radius:8px;margin-bottom:1rem;">
								<p style="font-size:.75rem;font-weight:600;color:var(--sage);margin-bottom:.5rem;">관리자 답변 (${inq.answeredAt || inq.answered_at?.substring(0, 10) || ''})</p>
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
			} catch (err) {
				console.error('문의 로드 오류:', err);
				container.innerHTML = `<p style="text-align:center;color:var(--rose);padding:2rem;">문의를 불러오는 중 오류가 발생했습니다.</p>`;
			}
		}

		function toggleAdminInquiry(id) {
			const item = document.querySelector(`.inquiry-admin-item[data-id="${id}"]`);
			if (item) item.classList.toggle('open');
		}

		async function submitAdminAnswer(id) {
			const textarea = document.getElementById('answer-' + id);
			if (!textarea) return;
			const answer = textarea.value.trim();
			if (!answer) {
				alert('답변 내용을 입력해주세요.');
				return;
			}

			try {
				const result = await API.updateInquiryAnswer(id, answer);
				if (result.ok) {
					alert('답변이 등록되었습니다.');
					renderAdminInquiries();
				} else {
					alert(result.message || '답변 등록 중 오류가 발생했습니다.');
				}
			} catch (err) {
				console.error('답변 등록 오류:', err);
				alert('답변 등록 중 오류가 발생했습니다.');
			}
		}

		function filterInquiries(filter) {
			currentInquiryFilter = filter;
			document.querySelectorAll('#tab-inquiries .badge').forEach(b => b.style.background = '');
			document.getElementById('filter-' + filter).style.background = 'var(--sage-bg)';
			renderAdminInquiries();
		}

		async function renderUsers() {
			const tbody = document.getElementById('usersTableBody');
			if (!tbody) return;
			
			if (typeof API === 'undefined') {
				tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--rose)">API를 불러오는 중입니다...</td></tr>`;
				setTimeout(() => renderUsers(), 500);
				return;
			}
			
			tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">불러오는 중...</td></tr>`;
			
			try {
				const rows = await API.getUsers();
				if (!rows || !rows.length) {
					tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">데이터 없음</td></tr>`;
					return;
				}
				tbody.innerHTML = rows.map((u) => `
					<tr>
						<td>${u.id}</td>
						<td>${u.name}</td>
						<td>${u.email}</td>
						<td>${u.joinedAt || u.created_at?.substring(0, 10) || ""}</td>
						<td>${u.status || (u.is_admin ? '관리자' : '일반')}</td>
					</tr>
				`).join('');
			} catch (err) {
				console.error('회원 목록 로드 오류:', err);
				const errorMsg = err.message || '알 수 없는 오류';
				tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--rose);">회원 목록을 불러오는 중 오류가 발생했습니다.<br><small style="font-size:0.75rem;color:var(--light);">${errorMsg}</small></td></tr>`;
			}
		}

		async function renderAdminOrders() {
			const tbody = document.getElementById('ordersTableBody');
			if (!tbody) return;
			
			if (typeof API === 'undefined') {
				tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--rose)">API를 불러오는 중입니다...</td></tr>`;
				setTimeout(() => renderAdminOrders(), 500);
				return;
			}
			
			tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--light)">불러오는 중...</td></tr>`;
			
			try {
				const rows = await API.getAdminOrders();
				if (!rows || !rows.length) {
					tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--light)">데이터 없음</td></tr>`;
					return;
				}
				const statusOptions = ['결제대기', '결제완료', '배송준비중', '배송중', '배송완료', '취소요청', '취소'];
				tbody.innerHTML = rows.map((o) => {
					const statusBadgeClass = o.status === '배송완료' ? 'answered' : 
					                          o.status === '취소' ? 'waiting' : 'answered';
					// 취소 요청 확인 (상태가 취소요청이거나 cancelRequested가 true인 경우)
					const hasCancelRequest = o.status === '취소요청' || o.status === 'cancel_requested' || 
					                         (o.cancelRequested === true || o.cancelRequested === 1);
					// 회원/비회원 구분
					const isGuest = o.is_guest === true || o.is_guest === 1 || !o.email || (o.email && !o.customer_name);
					const memberBadge = isGuest 
						? '<span class="badge" style="background:#888;color:#fff;font-size:.7rem;padding:.2rem .4rem;border-radius:4px;">비회원</span>'
						: '<span class="badge" style="background:var(--sage);color:#fff;font-size:.7rem;padding:.2rem .4rem;border-radius:4px;">회원</span>';
					return `
					<tr>
						<td>${o.id}${hasCancelRequest ? '<br><span style="color:var(--rose);font-size:0.75rem;font-weight:600;">⚠ 취소요청</span>' : ''}</td>
						<td>
							${memberBadge}<br>
							<span style="font-size:.85rem;margin-top:.25rem;display:inline-block;">${o.customer || '비회원'}</span>
						</td>
						<td>₩${(o.total || 0).toLocaleString()}</td>
						<td>
							<select id="status-${o.id}" onchange="updateOrderStatus('${o.id}', this.value)" 
							        style="padding:0.25rem 0.5rem;border:1px solid var(--border);border-radius:4px;font-size:0.85rem;background:#fff;">
								${statusOptions.map(s => `<option value="${s}" ${s === o.status ? 'selected' : ''}>${s}</option>`).join('')}
							</select>
						</td>
						<td>${o.orderedAt || ""}</td>
						<td>
							${o.status === '결제대기' && !hasCancelRequest ? 
								`<button class="badge" style="cursor:pointer;font-size:.7rem;background:var(--sage);color:#fff;margin-bottom:0.25rem;" onclick="confirmOrderPayment('${o.id}')">결제확인</button><br>` : ''}
							${hasCancelRequest ? 
								`<button class="badge" style="cursor:pointer;font-size:.7rem;background:var(--sage);color:#fff;margin-bottom:0.25rem;" onclick="approveOrderCancel('${o.id}')">취소승인</button>
								 <button class="badge" style="cursor:pointer;font-size:.7rem;background:var(--rose);color:#fff;margin-bottom:0.25rem;" onclick="rejectOrderCancel('${o.id}')">취소거부</button><br>` : ''}
							<button class="badge" style="cursor:pointer;font-size:.7rem;" onclick="viewOrderDetail('${o.id}')">상세보기</button>
						</td>
					</tr>
				`;
				}).join('');
			} catch (err) {
				console.error('주문 목록 로드 오류:', err);
				tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--rose);">주문 목록을 불러오는 중 오류가 발생했습니다.<br><small style="font-size:0.75rem;color:var(--light);">${err.message || '알 수 없는 오류'}</small></td></tr>`;
			}
		}

		async function updateOrderStatus(orderNumber, newStatus) {
			if (!confirm(`주문 상태를 "${newStatus}"로 변경하시겠습니까?`)) {
				// 취소하면 원래 상태로 복원
				renderAdminOrders();
				return;
			}

			try {
				const result = await API.updateOrderStatus(orderNumber, newStatus);
				if (result.ok) {
					alert('주문 상태가 변경되었습니다.');
					renderAdminOrders();
				} else {
					alert('상태 변경 실패: ' + (result.message || '알 수 없는 오류'));
					renderAdminOrders();
				}
			} catch (err) {
				console.error('주문 상태 변경 오류:', err);
				alert('주문 상태 변경 중 오류가 발생했습니다: ' + err.message);
				renderAdminOrders();
			}
		}

		async function confirmOrderPayment(orderNumber) {
			if (!confirm(`주문번호 ${orderNumber}의 결제를 확인하시겠습니까?\n결제대기 → 결제완료로 변경됩니다.`)) return;

			try {
				const result = await API.confirmPayment(orderNumber);
				if (result.ok) {
					alert(result.message || '결제가 확인되었습니다.');
					// 주문 목록 새로고침
					await renderAdminOrders();
					// KPI도 업데이트
					updateKPIs();
				} else {
					alert('결제 확인 실패: ' + (result.message || '알 수 없는 오류'));
				}
			} catch (err) {
				console.error('결제 확인 오류:', err);
				alert('결제 확인 중 오류가 발생했습니다: ' + err.message);
			}
		}

		async function approveOrderCancel(orderNumber) {
			if (!confirm(`주문번호 ${orderNumber}의 취소를 승인하시겠습니까?`)) return;

			try {
				const result = await API.approveCancel(orderNumber);
				if (result.ok) {
					alert(result.message || '주문 취소가 승인되었습니다.');
					// 주문 목록 새로고침
					await renderAdminOrders();
					// KPI도 업데이트
					updateKPIs();
				} else {
					alert('취소 승인 실패: ' + (result.message || '알 수 없는 오류'));
				}
			} catch (err) {
				console.error('취소 승인 오류:', err);
				alert('취소 승인 중 오류가 발생했습니다: ' + err.message);
			}
		}

		async function rejectOrderCancel(orderNumber) {
			if (!confirm(`주문번호 ${orderNumber}의 취소 요청을 거부하시겠습니까?`)) return;

			try {
				const result = await API.rejectCancel(orderNumber);
				if (result.ok) {
					alert(result.message || '취소 요청이 거부되었습니다.');
					// 주문 목록 새로고침
					await renderAdminOrders();
				} else {
					alert('취소 거부 실패: ' + (result.message || '알 수 없는 오류'));
				}
			} catch (err) {
				console.error('취소 거부 오류:', err);
				alert('취소 거부 중 오류가 발생했습니다: ' + err.message);
			}
		}

		async function viewOrderDetail(orderNumber) {
			try {
				// 주문 상세 정보 가져오기
				const orders = await API.getAdminOrders();
				const order = orders.find(o => o.id === orderNumber);
				
				if (!order) {
					alert('주문을 찾을 수 없습니다.');
					return;
				}

				// 모달 생성
				const modal = document.createElement('div');
				modal.className = 'modal-overlay active';
				modal.id = 'orderDetailModal';
				modal.style.zIndex = '10000';
				
				// 상태 배지 스타일
				const statusColors = {
					'결제대기': 'var(--light)',
					'결제완료': 'var(--sage)',
					'배송준비중': '#c9b896',
					'배송중': '#6b8cce',
					'배송완료': 'var(--sage)',
					'취소요청': 'var(--rose)',
					'취소': 'var(--light)'
				};
				
				const statusColor = statusColors[order.status] || 'var(--light)';
				
				modal.innerHTML = `
					<div class="modal" style="max-width:800px;width:90%;max-height:90vh;overflow-y:auto;position:relative;background:#fff;border-radius:16px;padding:2rem;">
						<button onclick="document.getElementById('orderDetailModal').remove();document.body.style.overflow='';" 
						        style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:2rem;cursor:pointer;color:var(--light);line-height:1;">×</button>
						
						<h2 style="font-size:1.5rem;color:var(--sage);margin-bottom:1.5rem;padding-right:2rem;">주문 상세 정보</h2>
						
						<!-- 주문 기본 정보 -->
						<div style="background:var(--sage-bg);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
							<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
								<div>
									<strong style="color:var(--light);font-size:.85rem;display:block;margin-bottom:.25rem;">주문번호</strong>
									<span style="font-size:1.1rem;font-weight:600;color:var(--sage);">${order.id}</span>
								</div>
								<div>
									<strong style="color:var(--light);font-size:.85rem;display:block;margin-bottom:.25rem;">주문 상태</strong>
									<span class="badge" style="background:${statusColor};color:#fff;padding:.4rem .8rem;font-size:.9rem;">${order.status}</span>
								</div>
							</div>
							<div>
								<strong style="color:var(--light);font-size:.85rem;display:block;margin-bottom:.25rem;">주문일시</strong>
								<span style="font-size:.95rem;">${order.createdAt || order.orderedAt || '정보 없음'}</span>
							</div>
						</div>
						
						<!-- 고객 정보 -->
						<div style="background:#f9f9f9;padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
							<h3 style="font-size:1.1rem;color:var(--sage);margin-bottom:1rem;border-bottom:2px solid var(--sage);padding-bottom:.5rem;">고객 정보</h3>
							<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
								<div>
									<strong style="color:var(--light);font-size:.85rem;display:block;margin-bottom:.25rem;">이름</strong>
									<span style="font-size:.95rem;">${order.customer_name || order.customer || '정보 없음'}</span>
								</div>
								<div>
									<strong style="color:var(--light);font-size:.85rem;display:block;margin-bottom:.25rem;">이메일</strong>
									<span style="font-size:.95rem;">${order.email || '정보 없음'}</span>
								</div>
								<div>
									<strong style="color:var(--light);font-size:.85rem;display:block;margin-bottom:.25rem;">전화번호</strong>
									<span style="font-size:.95rem;">${order.customer_phone || '정보 없음'}</span>
								</div>
								<div>
									<strong style="color:var(--light);font-size:.85rem;display:block;margin-bottom:.25rem;">회원 여부</strong>
									<span style="font-size:.95rem;font-weight:500;">${order.is_guest === true || order.is_guest === 1 ? '비회원' : '회원'}</span>
								</div>
							</div>
						</div>
						
						<!-- 배송 정보 -->
						<div style="background:#f9f9f9;padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
							<h3 style="font-size:1.1rem;color:var(--sage);margin-bottom:1rem;border-bottom:2px solid var(--sage);padding-bottom:.5rem;">배송 정보</h3>
							<div>
								<strong style="color:var(--light);font-size:.85rem;display:block;margin-bottom:.25rem;">배송지 주소</strong>
								<span style="font-size:.95rem;line-height:1.6;white-space:pre-wrap;">${order.customer_address || '정보 없음'}</span>
							</div>
						</div>
						
						<!-- 주문 상품 목록 -->
						<div style="background:#f9f9f9;padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
							<h3 style="font-size:1.1rem;color:var(--sage);margin-bottom:1rem;border-bottom:2px solid var(--sage);padding-bottom:.5rem;">주문 상품</h3>
							${order.items && order.items.length > 0 ? `
								<table style="width:100%;border-collapse:collapse;">
									<thead>
										<tr style="border-bottom:2px solid var(--border);">
											<th style="padding:.75rem;text-align:left;font-size:.85rem;color:var(--light);">상품명</th>
											<th style="padding:.75rem;text-align:center;font-size:.85rem;color:var(--light);">수량</th>
											<th style="padding:.75rem;text-align:right;font-size:.85rem;color:var(--light);">가격</th>
										</tr>
									</thead>
									<tbody>
										${order.items.map(item => `
											<tr style="border-bottom:1px solid var(--border);">
												<td style="padding:.75rem;font-size:.9rem;">
													${item.name || '상품명 없음'}
													${item.variant_volume ? `<br><span style="color:var(--sage);font-size:.8rem;font-weight:500;">용량: ${item.variant_volume}</span>` : ''}
													${item.imageUrl ? `<br><img src="${item.imageUrl}" style="max-width:60px;max-height:60px;border-radius:8px;margin-top:.5rem;object-fit:cover;" onerror="this.style.display='none';">` : ''}
												</td>
												<td style="padding:.75rem;text-align:center;font-size:.9rem;">${item.quantity || item.qty || 1}개</td>
												<td style="padding:.75rem;text-align:right;font-size:.9rem;">₩${((item.price || 0) * (item.quantity || item.qty || 1)).toLocaleString()}</td>
											</tr>
										`).join('')}
									</tbody>
								</table>
							` : '<p style="color:var(--light);text-align:center;padding:1rem;">주문 상품 정보가 없습니다.</p>'}
						</div>
						
						<!-- 결제 정보 -->
						<div style="background:var(--sage-bg);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
							<h3 style="font-size:1.1rem;color:var(--sage);margin-bottom:1rem;border-bottom:2px solid var(--sage);padding-bottom:.5rem;">결제 정보</h3>
							<div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
								<span style="font-size:.9rem;color:var(--light);">상품 금액</span>
								<span style="font-size:.9rem;">₩${((order.payment?.subtotal || order.total || 0) - 3000).toLocaleString()}</span>
							</div>
							<div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
								<span style="font-size:.9rem;color:var(--light);">배송비</span>
								<span style="font-size:.9rem;">₩${(order.payment?.shipping || 3000).toLocaleString()}</span>
							</div>
							<div style="display:flex;justify-content:space-between;padding-top:1rem;border-top:2px solid var(--sage);margin-top:1rem;">
								<strong style="font-size:1.1rem;color:var(--sage);">총 결제 금액</strong>
								<strong style="font-size:1.1rem;color:var(--sage);">₩${(order.total || 0).toLocaleString()}</strong>
							</div>
						</div>
						
						<!-- 취소 사유 (있는 경우) -->
						${order.cancelRequested && order.cancelReason ? `
							<div style="background:#fff3cd;padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;border-left:4px solid var(--rose);">
								<h3 style="font-size:1.1rem;color:var(--rose);margin-bottom:1rem;">취소 요청 사유</h3>
								<p style="font-size:.9rem;line-height:1.6;white-space:pre-wrap;">${order.cancelReason}</p>
							</div>
						` : ''}
						
						<!-- 액션 버튼 -->
						<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.5rem;">
							${order.status === '결제대기' && !order.cancelRequested ? 
								`<button onclick="confirmOrderPayment('${order.id}');document.getElementById('orderDetailModal').remove();document.body.style.overflow='';" 
								        style="padding:.6rem 1.2rem;background:var(--sage);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:500;">결제 확인</button>` : ''}
							${order.cancelRequested ? 
								`<button onclick="approveOrderCancel('${order.id}');document.getElementById('orderDetailModal').remove();document.body.style.overflow='';" 
								        style="padding:.6rem 1.2rem;background:var(--sage);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:500;margin-right:.5rem;">취소 승인</button>
								 <button onclick="rejectOrderCancel('${order.id}');document.getElementById('orderDetailModal').remove();document.body.style.overflow='';" 
								        style="padding:.6rem 1.2rem;background:var(--rose);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:500;">취소 거부</button>` : ''}
							<button onclick="document.getElementById('orderDetailModal').remove();document.body.style.overflow='';" 
							        style="padding:.6rem 1.2rem;background:var(--border);color:var(--mid);border:none;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:500;">닫기</button>
						</div>
					</div>
				`;
				
				document.body.appendChild(modal);
				document.body.style.overflow = 'hidden';
			} catch (err) {
				console.error('주문 상세 정보 로드 오류:', err);
				alert('주문 상세 정보를 불러오는 중 오류가 발생했습니다: ' + (err.message || '알 수 없는 오류'));
			}
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
						<td>${p.category || p.type || '향수'}</td>
						<td>₩${(p.price || 0).toLocaleString()}</td>
						<td>-</td>
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


		// 카테고리별 용량 옵션 정의
		const volumeOptions = {
			'향수': ['30ml', '50ml', '100ml'],
			'헤어미스트': ['30ml', '50ml', '100ml'],
			'바디미스트': ['50ml', '100ml'],
			'디퓨저': ['80ml', '120ml'],
			'룸스프레이': ['80ml', '120ml'],
			'섬유유연제': ['800ml', '1L']
		};

		// 카테고리 변경 시 용량 옵션 업데이트
		function updateVolumeOptions() {
			const category = document.getElementById('prodCategory').value;
			const container = document.getElementById('variantsContainer');
			const addBtn = document.getElementById('addVariantBtn');
			
			// 기존 variants 초기화
			container.innerHTML = '';
			
			// 카테고리에 해당하는 용량 옵션이 있으면 버튼 표시
			if (volumeOptions[category]) {
				addBtn.style.display = 'inline-block';
				// 기본 용량 옵션 추가
				volumeOptions[category].forEach(volume => {
					addVariantRow({ volume, price: '', stock: 0 });
				});
			} else {
				addBtn.style.display = 'none';
			}
		}

		// 용량별 가격 행 추가
		function addVariantRow(variant = null) {
			const container = document.getElementById('variantsContainer');
			const category = document.getElementById('prodCategory').value;
			const availableVolumes = volumeOptions[category] || [];
			
			const row = document.createElement('div');
			row.className = 'variant-row';
			row.style.cssText = 'display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;flex-wrap:wrap;padding:.5rem;background:white;border-radius:4px;border:1px solid #ddd;';
			
			// 용량 선택 드롭다운
			let volumeSelect = '';
			if (availableVolumes.length > 0) {
				volumeSelect = `<select class="form-input variant-volume" style="width:100px;">
					<option value="">선택</option>
					${availableVolumes.map(v => `<option value="${v}" ${variant?.volume === v ? 'selected' : ''}>${v}</option>`).join('')}
				</select>`;
			} else {
				volumeSelect = `<input type="text" class="form-input variant-volume" placeholder="용량 (예: 30ml)" value="${variant?.volume || ''}" style="width:100px;">`;
			}
			
			row.innerHTML = `
				${volumeSelect}
				<input type="number" class="form-input variant-price" placeholder="가격" value="${variant?.price || ''}" style="width:120px;">
				<input type="number" class="form-input variant-stock" placeholder="재고" value="${variant?.stock || 0}" style="width:100px;">
				<label style="font-size:.75rem;display:flex;align-items:center;gap:.25rem;">
					<input type="radio" name="defaultVariant" class="variant-default" ${variant?.is_default == 1 ? 'checked' : ''}> 기본
				</label>
				<button type="button" onclick="this.parentElement.remove()" style="background:var(--rose);color:#fff;border:none;border-radius:4px;padding:.25rem .5rem;cursor:pointer;font-size:.7rem;">삭제</button>
			`;
			container.appendChild(row);
		}

		// 폼에서 variants 데이터 가져오기
		function getVariantsFromForm() {
			const rows = document.querySelectorAll('.variant-row');
			const variants = [];
			rows.forEach((row, index) => {
				const volume = row.querySelector('.variant-volume').value.trim();
				const price = row.querySelector('.variant-price').value;
				const stock = row.querySelector('.variant-stock').value;
				const isDefault = row.querySelector('.variant-default').checked;
				if (volume && price) {
					variants.push({
						volume,
						price: parseInt(price),
						stock: parseInt(stock) || 0,
						is_default: isDefault ? 1 : 0,
						sort_order: index
					});
				}
			});
			return variants;
		}

		// variants 로드
		function loadVariants(variants) {
			const container = document.getElementById('variantsContainer');
			const addBtn = document.getElementById('addVariantBtn');
			container.innerHTML = '';
			
			if (variants && variants.length > 0) {
				variants.forEach(v => addVariantRow(v));
				addBtn.style.display = 'inline-block';
			} else {
				// 카테고리에 맞는 기본 용량 옵션 표시
				updateVolumeOptions();
			}
		}

		// 감정 목록 로드 및 체크박스 생성
		function loadEmotionCheckboxes(selectedEmotions = []) {
			const container = document.getElementById('prodEmotionsContainer');
			if (!container) {
				console.warn('prodEmotionsContainer 요소를 찾을 수 없습니다');
				return;
			}
			
			let emotions = [];
			if (typeof API !== 'undefined' && API.getActiveEmotions) {
				try {
					emotions = API.getActiveEmotions();
				} catch (e) {
					console.error('감정 목록 로드 실패:', e);
					emotions = [];
				}
			}
			
			// API에서 가져온 감정이 없으면 기본값 사용
			if (!emotions || emotions.length === 0) {
				emotions = [
					{ key: 'calm', title: '차분해지고 싶은 날' },
					{ key: 'warm', title: '따뜻함이 필요한 순간' },
					{ key: 'focus', title: '집중하고 싶은 시간' },
					{ key: 'refresh', title: '상쾌하고 싶을 때' }
				];
			}
			
			container.innerHTML = emotions.map(emotion => {
				const isChecked = Array.isArray(selectedEmotions) && selectedEmotions.includes(emotion.key);
				return `
					<label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.5rem;background:#fff;border:1px solid var(--border);border-radius:4px;">
						<input type="checkbox" class="emotion-checkbox" value="${emotion.key}" ${isChecked ? 'checked' : ''}>
						<span style="font-size:.85rem;">${emotion.title}</span>
					</label>
				`;
			}).join('');
		}
		
		// 선택된 감정 키들 가져오기
		function getSelectedEmotions() {
			const checkboxes = document.querySelectorAll('.emotion-checkbox:checked');
			return Array.from(checkboxes).map(cb => cb.value);
		}

		// 상품 폼 열기 (등록)
		function openProductForm() {
			document.getElementById('productFormWrap').style.display = 'block';
			document.getElementById('productFormTitle').textContent = '새 상품 등록';
			document.getElementById('productEditId').value = '';
			// 폼 초기화
			document.getElementById('prodName').value = '';
			document.getElementById('prodCategory').value = '향수';
			document.getElementById('prodStatus').value = '판매중';
			document.getElementById('prodBadge').value = '';
			document.getElementById('prodDesc').value = '';
			document.getElementById('prodImageUrl').value = '';
			document.getElementById('prodDetailImageUrl').value = '';
			document.getElementById('prodImagePreview').style.display = 'none';
			document.getElementById('prodImagePreview').src = '';
			document.getElementById('prodDetailImagePreview').style.display = 'none';
			document.getElementById('prodDetailImagePreview').src = '';
			document.getElementById('clearImageBtn').style.display = 'none';
			document.getElementById('clearDetailImageBtn').style.display = 'none';
			// variants 초기화 및 카테고리별 용량 옵션 표시
			updateVolumeOptions();
			// 감정 체크박스 초기화
			loadEmotionCheckboxes([]);
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
			document.getElementById('prodCategory').value = product.category || product.type || '향수';
			document.getElementById('prodFragranceType').value = product.fragrance_type || product.fragranceType || '';
			document.getElementById('prodStatus').value = product.status || '판매중';
			document.getElementById('prodBadge').value = product.badge || '';
			document.getElementById('prodDesc').value = product.desc || '';
			document.getElementById('prodImageUrl').value = product.imageUrl || product.image || '';
			document.getElementById('prodDetailImageUrl').value = product.detailImageUrl || product.detail_image || '';
			// 이미지 미리보기
			const preview = document.getElementById('prodImagePreview');
			const imgUrl = product.imageUrl || product.image;
			if (imgUrl) {
				preview.src = imgUrl;
				preview.style.display = 'block';
				preview.onerror = function() {
					console.error('이미지 미리보기 로드 실패:', imgUrl.substring(0, 50));
					preview.style.display = 'none';
				};
				document.getElementById('clearImageBtn').style.display = 'inline-block';
			} else {
				preview.style.display = 'none';
				preview.src = '';
				document.getElementById('clearImageBtn').style.display = 'none';
			}
			const detailPreview = document.getElementById('prodDetailImagePreview');
			const detailImgUrl = product.detailImageUrl || product.detail_image;
			if (detailImgUrl) {
				detailPreview.src = detailImgUrl;
				detailPreview.style.display = 'block';
				detailPreview.onerror = function() {
					console.error('상세 이미지 미리보기 로드 실패:', detailImgUrl.substring(0, 50));
					detailPreview.style.display = 'none';
				};
				document.getElementById('clearDetailImageBtn').style.display = 'inline-block';
			} else {
				detailPreview.style.display = 'none';
				detailPreview.src = '';
				document.getElementById('clearDetailImageBtn').style.display = 'none';
			}
			// variants 로드
			loadVariants(product.variants || []);
			// 감정 체크박스 로드
			let emotionKeys = [];
			if (product.emotion_keys) {
				try {
					emotionKeys = JSON.parse(product.emotion_keys);
				} catch (e) {
					// 문자열이면 그대로 사용
					if (typeof product.emotion_keys === 'string') {
						emotionKeys = product.emotion_keys.split(',').map(k => k.trim()).filter(Boolean);
					}
				}
			} else if (product.emotionKeys) {
				emotionKeys = Array.isArray(product.emotionKeys) ? product.emotionKeys : [product.emotionKeys];
			}
			loadEmotionCheckboxes(emotionKeys);
		}

		// 상품 저장 (등록/수정)
		async function saveProduct() {
			const editId = document.getElementById('productEditId').value;
			const name = document.getElementById('prodName').value.trim();
			const variants = getVariantsFromForm();

			if (!name) {
				alert('상품명을 입력해주세요.');
				return;
			}
			// variants 필수 검증
			if (variants.length === 0) {
				alert('최소 하나의 용량별 가격을 설정해주세요.');
				return;
			}

			// category 값 검증 및 정리
			const categorySelect = document.getElementById('prodCategory');
			let category = categorySelect ? categorySelect.value : '향수';
			// category가 유효한 옵션 값인지 확인 (50자 제한)
			const validCategories = ['향수', '바디미스트', '헤어미스트', '디퓨저', '섬유유연제', '룸스프레이'];
			if (!validCategories.includes(category) || category.length > 50) {
				category = '향수'; // 기본값으로 설정
			}

			// variants의 기본 가격 또는 첫 번째 가격을 products 테이블의 price로 사용 (호환성)
			const defaultPrice = variants.find(v => v.is_default)?.price || variants[0].price;
			
			// 향기 타입 가져오기
			const fragranceType = document.getElementById('prodFragranceType').value.trim() || null;
			
			// 선택된 감정 키들 가져오기
			const emotionKeys = getSelectedEmotions();

			const data = {
				name: name,
				price: defaultPrice, // variants의 기본 가격 사용
				category: category,
				fragranceType: fragranceType,
				emotionKeys: emotionKeys.length > 0 ? emotionKeys : null,
				status: document.getElementById('prodStatus').value,
				badge: document.getElementById('prodBadge').value,
				desc: document.getElementById('prodDesc').value.trim(),
				imageUrl: document.getElementById('prodImageUrl').value.trim(),
				detailImageUrl: document.getElementById('prodDetailImageUrl').value.trim(),
			};

			try {
				let productId = editId;
				console.log('상품 저장 시작:', { editId, data });
				
				if (editId) {
					const result = await API.updateProduct(parseInt(editId), data);
					console.log('상품 수정 결과:', result);
				} else {
					const created = await API.createProduct(data);
					productId = created.id;
					console.log('상품 생성 결과:', created);
				}

				// variants 저장 (필수)
				if (productId && variants.length > 0) {
					console.log('variants 저장 시작:', { productId, variants });
					await saveVariants(productId, variants);
					console.log('variants 저장 완료');
				} else {
					throw new Error('상품 저장 후 variants 저장에 실패했습니다.');
				}

				alert(editId ? '상품이 수정되었습니다.' : '상품이 등록되었습니다.');
				closeProductForm();
				renderProducts();
			} catch (e) {
				console.error('상품 저장 오류:', e);
				console.error('오류 스택:', e.stack);
				alert('오류가 발생했습니다: ' + (e.message || '알 수 없는 오류'));
			}
		}

		// variants 저장 API 호출
		async function saveVariants(productId, variants) {
			try {
				// POST 메서드 사용 (PUT이 405 오류 발생 시 대안)
				// X-HTTP-Method-Override 헤더로 PUT 의미 전달
				const baseUrl = window.DS_BASE_URL || '';
				const response = await fetch(`${baseUrl}/api/variants.php?product_id=${productId}`, {
					method: 'POST',
					headers: { 
						'Content-Type': 'application/json',
						'X-HTTP-Method-Override': 'PUT'
					},
					credentials: 'include',
					body: JSON.stringify({ variants, _method: 'PUT' })
				});
				
				if (!response.ok) {
					let errorMessage = '용량별 가격 저장 실패';
					try {
						const errorData = await response.json();
						if (errorData.error) {
							errorMessage = errorData.error;
							if (errorData.details) {
								errorMessage += ' (' + errorData.details + ')';
							}
						}
					} catch (e) {
						// JSON 파싱 실패 시 기본 메시지 사용
					}
					throw new Error(errorMessage);
				}
				return await response.json();
			} catch (e) {
				console.error('Variants save error:', e);
				throw e;
			}
		}

		// 모든 variants 삭제
		async function deleteAllVariants(productId) {
			const baseUrl = window.DS_BASE_URL || '';
			const response = await fetch(`${baseUrl}/api/variants.php?product_id=${productId}`, {
				method: 'DELETE',
				credentials: 'include'
			});
			return response.ok;
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
			const variants = getVariantsFromForm();
			// variants의 기본 가격 또는 첫 번째 가격 사용
			const price = variants.length > 0 ? (variants.find(v => v.is_default)?.price || variants[0].price) : 0;
			const category = document.getElementById('prodCategory').value || '향수';
			const badge = document.getElementById('prodBadge').value || '';
			const desc = document.getElementById('prodDesc').value.trim() || '상품 설명';
			const rawImageUrl = document.getElementById('prodImageUrl').value.trim() || '';
			
			// Base64 이미지인 경우 따옴표 없이, 일반 URL은 따옴표로
			let imageStyle = '';
			if (rawImageUrl && rawImageUrl.length > 10) {
				const imageUrl = rawImageUrl.startsWith('data:') ? rawImageUrl : `'${rawImageUrl}'`;
				imageStyle = `background-image:url(${imageUrl}) !important;background-size:cover !important;background-position:center !important;background-color:transparent !important;`;
			}
			
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
						.preview-product-image { background: transparent !important; }
					</style>
				</head>
				<body>
					<h1 class="preview-title">상품 미리보기</h1>
					<div class="preview-container">
						<div class="product-card">
							<div class="product-image preview-product-image" style="position:relative;${imageStyle}">
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
		async function renderBanners() {
			if (typeof API === 'undefined') {
				const tbody = document.getElementById('bannersTableBody');
				if (tbody) {
					tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--rose);">API를 불러오는 중입니다...</td></tr>`;
					setTimeout(() => renderBanners(), 500);
				}
				return;
			}
			const tbody = document.getElementById('bannersTableBody');
			if (!tbody) return;
			const banners = await API.getBanners();
			if (!banners || !banners.length) {
				tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--light)">등록된 배너가 없습니다.</td></tr>`;
				// 개수 표시 업데이트
				const countText = document.getElementById('bannerCountText');
				if (countText) countText.textContent = '(0/5개)';
				return;
			}
			const sortedBanners = banners.sort((a,b) => (a.order || 0) - (b.order || 0));
			tbody.innerHTML = sortedBanners.map(b => `
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
			
			// 배너 개수 표시 업데이트
			const bannerCount = sortedBanners.length;
			const activeCount = sortedBanners.filter(b => b.active).length;
			const countText = document.getElementById('bannerCountText');
			if (countText) {
				countText.textContent = `(${bannerCount}/5개, 활성: ${activeCount}개)`;
				countText.style.color = bannerCount >= 5 ? 'var(--rose)' : 'var(--light)';
			}
		}
		async function openBannerForm() {
			const banners = await API.getBanners();
			if (banners && banners.length >= 5) {
				alert('배너는 최대 5개까지 등록할 수 있습니다. 기존 배너를 삭제하거나 수정해주세요.');
				return;
			}
			document.getElementById('bannerFormWrap').style.display = 'block';
			document.getElementById('bannerFormTitle').textContent = '새 배너 등록';
			document.getElementById('bannerEditId').value = '';
			document.getElementById('bannerTitle').value = '';
			document.getElementById('bannerSubtitle').value = '';
			document.getElementById('bannerLink').value = 'pages/products.php';
			document.getElementById('bannerLink').placeholder = 'pages/products.php (기본값)';
			document.getElementById('bannerOrder').value = String(banners.length + 1);
			document.getElementById('bannerImageUrl').value = '';
			document.getElementById('bannerActive').checked = true;
			document.getElementById('bannerImagePreview').style.display = 'none';
			document.getElementById('bannerImagePreview').src = '';
		}
		function closeBannerForm() { document.getElementById('bannerFormWrap').style.display = 'none'; }
		async function editBanner(id) {
			const banners = await API.getBanners();
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
		async function saveBanner() {
			const editId = document.getElementById('bannerEditId').value;
			const title = document.getElementById('bannerTitle').value.trim();
			if (!title) {
				alert('제목을 입력해주세요.');
				return;
			}
			const linkValue = document.getElementById('bannerLink').value.trim();
			const data = {
				title,
				subtitle: document.getElementById('bannerSubtitle').value.trim(),
				link: linkValue || 'pages/products.php', // 링크가 비어있으면 기본값 사용
				order: parseInt(document.getElementById('bannerOrder').value) || 1,
				imageUrl: document.getElementById('bannerImageUrl').value.trim(),
				active: document.getElementById('bannerActive').checked ? 1 : 0
			};
			
			if (editId) {
				data.id = parseInt(editId);
			}
			
			try {
				if (editId) {
					await API.putJSON(`/banners.php`, data);
				} else {
					// 새로 등록하는 경우 최대 5개 제한 확인
					const banners = await API.getBanners();
					if (banners && banners.length >= 5) {
						alert('배너는 최대 5개까지 등록할 수 있습니다.');
						return;
					}
					await API.postJSON(`/banners.php`, data);
				}
				closeBannerForm();
				await renderBanners();
				alert('저장되었습니다.');
			} catch (err) {
				console.error('배너 저장 오류:', err);
				alert('배너 저장 중 오류가 발생했습니다.');
			}
		}
		
		async function deleteBanner(id) {
			if (!confirm('정말 삭제하시겠습니까?')) return;
			try {
				const baseUrl = window.DS_BASE_URL || '';
				const response = await fetch(`${baseUrl}/api/banners.php?id=${id}`, {
					method: 'DELETE',
					credentials: 'include'
				});
				if (!response.ok) throw new Error('삭제 실패');
				await renderBanners();
				alert('삭제되었습니다.');
			} catch (err) {
				console.error('배너 삭제 오류:', err);
				alert('배너 삭제 중 오류가 발생했습니다.');
			}
		}
		
		// 기본 배너 5개로 초기화
		async function resetDefaultBanners() {
			if (!confirm('기본 배너 5개로 초기화하시겠습니까?\n현재 등록된 배너가 모두 삭제됩니다.')) return;
			
			try {
				const baseUrl = window.DS_BASE_URL || '';
				// 기존 배너 모두 삭제 (병렬 처리로 빠르게)
				const banners = await API.getBanners();
				await Promise.all(banners.map(banner => 
					fetch(`${baseUrl}/api/banners.php?id=${banner.id}`, {
						method: 'DELETE',
						credentials: 'include'
					})
				));
				
				// 기본 배너 생성
				const defaultBanners = [
					{
						title: "나에게 맞는 향기 찾기",
						subtitle: "3분 향기 테스트로 나만의 향을 발견하세요",
						link: "#fragrance-test",
						imageUrl: "",
						order: 1,
						active: 1,
					},
					{
						title: "새로운 향기의 시작",
						subtitle: "DewScent 2025 컬렉션",
						link: "pages/products.php",
						imageUrl: "",
						order: 2,
						active: 1,
					},
					{
						title: "봄의 향기를 담다",
						subtitle: "벚꽃 에디션 출시",
						link: "pages/products.php",
						imageUrl: "",
						order: 3,
						active: 1,
					},
					{
						title: "특별한 선물",
						subtitle: "기프트 세트 20% 할인",
						link: "pages/products.php",
						imageUrl: "",
						order: 4,
						active: 1,
					},
					{
						title: "시그니처 향기",
						subtitle: "베스트셀러 모음",
					link: "pages/products.php",
					imageUrl: "",
					order: 5,
					active: true,
				},
			];
			API.setBanners(defaultBanners);
			await renderBanners();
			alert('기본 배너 5개로 초기화되었습니다.');
			} catch (err) {
				console.error('기본 배너 초기화 오류:', err);
				alert('기본 배너 초기화 중 오류가 발생했습니다.');
			}
		}

		// ========== 팝업 관리 ==========
		async function renderPopups() {
			if (typeof API === 'undefined') {
				const tbody = document.getElementById('popupsTableBody');
				if (tbody) {
					tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--rose);">API를 불러오는 중입니다...</td></tr>`;
					setTimeout(() => renderPopups(), 500);
				}
				return;
			}
			const tbody = document.getElementById('popupsTableBody');
			if (!tbody) return;
			const popups = await API.getPopups();
			if (!popups || !popups.length) {
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
		async function renderEmotions() {
			if (typeof API === 'undefined') {
				const tbody = document.getElementById('emotionsTableBody');
				if (tbody) {
					tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--rose);">API를 불러오는 중입니다...</td></tr>`;
					setTimeout(() => renderEmotions(), 500);
				}
				return;
			}
			const tbody = document.getElementById('emotionsTableBody');
			if (!tbody) return;
			const emotions = await API.getEmotions();
			if (!emotions || !emotions.length) {
				tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--light)">등록된 감정 카드가 없습니다.</td></tr>';
				return;
			}
			const recommendations = await API.getAllEmotionRecommendations();
			tbody.innerHTML = emotions.sort((a,b) => (a.order || 0) - (b.order || 0)).map(e => {
				const recCount = recommendations[e.key]?.productIds?.length || 0;
				return `
				<tr>
					<td>${e.order || 1}</td>
					<td style="font-family:monospace;">${e.key || ''}</td>
					<td style="font-weight:500;">${e.title || ''}</td>
					<td>${e.desc || ''}</td>
					<td><span class="status-badge ${e.active ? 'answered' : 'waiting'}">${e.active ? '활성' : '비활성'}</span></td>
					<td>
						<button class="badge" style="cursor:pointer;background:var(--ivory);color:#fff;border:none;font-size:.7rem;" onclick="openEmotionRecommendationModal('${e.key}', '${e.title}')">추천 설정 ${recCount > 0 ? `(${recCount})` : ''}</button>
						<button class="badge" style="cursor:pointer;font-size:.7rem;" onclick="editEmotion(${e.id})">수정</button>
						<button class="badge" style="cursor:pointer;font-size:.7rem;color:var(--rose);" onclick="deleteEmotion(${e.id})">삭제</button>
					</td>
				</tr>
			`;
			}).join('');
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
		
		// 감정별 추천 상품 설정 모달 열기
		async function openEmotionRecommendationModal(emotionKey, emotionTitle) {
			document.getElementById('emotionRecommendationKey').value = emotionKey;
			document.getElementById('emotionRecommendationTitle').textContent = `"${emotionTitle}" 추천 상품 설정`;
			document.getElementById('emotionRecommendationModal').style.display = 'flex';
			
			// 현재 설정된 추천 상품 가져오기 (중복 제거)
			const recommendations = API.getAllEmotionRecommendations();
			const currentIds = recommendations[emotionKey]?.productIds || [];
			const uniqueCurrentIds = [...new Set(currentIds)]; // 중복 제거
			
			// 모든 상품 가져오기 (중복 제거)
			const products = await API.getProducts();
			const availableProducts = products
				.filter(p => p.status === '판매중')
				.filter((p, index, self) => index === self.findIndex(prod => prod.id === p.id)); // id 기준 중복 제거
			
			// 상품 선택 UI 생성
			const container = document.getElementById('emotionRecommendationProducts');
			container.innerHTML = availableProducts.map(p => {
				const isSelected = uniqueCurrentIds.includes(p.id);
				return `
					<div style="border:2px solid ${isSelected ? 'var(--sage)' : 'var(--border)'};border-radius:12px;padding:1rem;cursor:pointer;background:${isSelected ? 'var(--sage-bg)' : '#fff'};transition:all 0.2s;" 
						onclick="toggleEmotionProduct(${p.id})" 
						data-product-id="${p.id}">
						<div style="height:100px;background:${p.imageUrl ? `url(${p.imageUrl})` : 'linear-gradient(135deg,var(--sage-lighter),var(--sage))'};background-size:cover;background-position:center;border-radius:8px;margin-bottom:.5rem;"></div>
						<p style="font-size:.85rem;font-weight:500;margin-bottom:.25rem;">${p.name}</p>
						<p style="font-size:.75rem;color:var(--light);">₩${(p.price || 0).toLocaleString()}</p>
						${isSelected ? '<div style="margin-top:.5rem;text-align:center;"><span style="background:var(--sage);color:#fff;padding:.2rem .5rem;border-radius:999px;font-size:.7rem;">선택됨</span></div>' : ''}
					</div>
				`;
			}).join('');
		}
		
		// 추천 상품 선택 토글
		function toggleEmotionProduct(productId) {
			const container = document.getElementById('emotionRecommendationProducts');
			const productEl = container.querySelector(`[data-product-id="${productId}"]`);
			if (!productEl) return;
			
			const isSelected = productEl.style.borderColor === 'var(--sage)';
			const emotionKey = document.getElementById('emotionRecommendationKey').value;
			const recommendations = API.getAllEmotionRecommendations();
			const currentIds = recommendations[emotionKey]?.productIds || [];
			
			// 중복 제거: 현재 ID 목록에서 중복 제거
			const uniqueCurrentIds = [...new Set(currentIds)];
			
			let newIds;
			if (isSelected) {
				// 선택 해제
				newIds = uniqueCurrentIds.filter(id => id !== productId);
			} else {
				// 중복 체크: 이미 선택된 상품인지 확인
				if (uniqueCurrentIds.includes(productId)) {
					alert('이미 선택된 상품입니다.');
					return;
				}
				if (uniqueCurrentIds.length >= 10) {
					alert('최대 10개까지만 선택할 수 있습니다.');
					return;
				}
				newIds = [...uniqueCurrentIds, productId];
			}
			
			// 중복 제거 후 저장
			const finalIds = [...new Set(newIds)];
			
			// UI 업데이트
			API.setEmotionRecommendations(emotionKey, finalIds);
			openEmotionRecommendationModal(emotionKey, document.getElementById('emotionRecommendationTitle').textContent.replace('"', '').split('"')[0]);
		}
		
		// 추천 상품 저장
		function saveEmotionRecommendation() {
			const emotionKey = document.getElementById('emotionRecommendationKey').value;
			const recommendations = API.getAllEmotionRecommendations();
			const currentIds = recommendations[emotionKey]?.productIds || [];
			
			if (currentIds.length === 0) {
				if (!confirm('추천 상품이 선택되지 않았습니다. 자동 추천을 사용하시겠습니까?')) {
					return;
				}
			}
			
			alert('저장되었습니다. 7일마다 선택한 상품 중 4개가 랜덤으로 표시됩니다.');
			closeEmotionRecommendationModal();
			renderEmotions();
		}
		
		// 추천 상품 모달 닫기
		function closeEmotionRecommendationModal() {
			document.getElementById('emotionRecommendationModal').style.display = 'none';
		}

		// ========== 미리보기 함수들 ==========
		function previewBannerSlider() {
			let banners = API.getActiveBanners();
			if (banners.length === 0) {
				alert('활성화된 배너가 없습니다.');
				return;
			}
			
			// 최대 5개까지만 표시 (메인 페이지와 동일하게)
			if (banners.length > 5) {
				banners = banners.slice(0, 5);
			}
			
			// 5개 미만이면 반복해서 채움 (메인 페이지와 동일한 로직)
			const displayBanners = [];
			while (displayBanners.length < 5) {
				banners.forEach((b) => {
					if (displayBanners.length < 5) displayBanners.push(b);
				});
			}
			
			const positions = ['pos-far-left', 'pos-left', 'pos-center', 'pos-right', 'pos-far-right'];
			const previewHtml = `
				<div style="background:#f5f5f5;padding:2rem;border-radius:16px;max-width:900px;margin:auto;">
					<h3 style="color:var(--sage);text-align:center;margin-bottom:1.5rem;font-size:1.2rem;">배너 슬라이더 미리보기</h3>
					<div style="position:relative;height:400px;overflow:hidden;border-radius:12px;background:#fff;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
						<div style="position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
							${displayBanners.map((b, i) => {
								const pos = positions[i];
								let style = '';
								if (pos === 'pos-center') {
									style = 'position:absolute;left:50%;transform:translateX(-50%);z-index:10;width:280px;height:320px;';
								} else if (pos === 'pos-left') {
									style = 'position:absolute;left:20%;transform:translateX(-50%) scale(0.85);z-index:5;width:240px;height:280px;opacity:0.8;';
								} else if (pos === 'pos-right') {
									style = 'position:absolute;right:20%;transform:translateX(50%) scale(0.85);z-index:5;width:240px;height:280px;opacity:0.8;';
								} else if (pos === 'pos-far-left') {
									style = 'position:absolute;left:5%;transform:translateX(-50%) scale(0.7);z-index:1;width:200px;height:240px;opacity:0.6;';
								} else if (pos === 'pos-far-right') {
									style = 'position:absolute;right:5%;transform:translateX(50%) scale(0.7);z-index:1;width:200px;height:240px;opacity:0.6;';
								}
								return `
									<div style="${style}background:${b.imageUrl ? `url(${b.imageUrl});background-size:cover;background-position:center;` : 'linear-gradient(135deg,#d6e2cf,#5f7161)'};border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,0.15);display:flex;flex-direction:column;justify-content:flex-end;padding:1.5rem;cursor:pointer;transition:all 0.3s;">
										${!b.imageUrl ? `<div style="color:#fff;font-size:2rem;text-align:center;margin-bottom:auto;opacity:0.3;">이벤트 ${b.order || i+1}</div>` : ''}
										<div style="background:rgba(0,0,0,0.4);padding:1rem;border-radius:8px;backdrop-filter:blur(4px);">
											<div style="color:#fff;font-weight:600;font-size:1.1rem;margin-bottom:.25rem;">${b.title}</div>
											<div style="color:#fff;font-size:.9rem;opacity:0.95;">${b.subtitle || ''}</div>
										</div>
									</div>
								`;
							}).join('')}
						</div>
					</div>
					<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.5rem;">
						${displayBanners.map((b, i) => `
							<div style="width:8px;height:8px;border-radius:50%;background:${i === 2 ? 'var(--sage)' : '#ddd'};cursor:pointer;"></div>
						`).join('')}
					</div>
					<p style="color:var(--light);text-align:center;font-size:.85rem;margin-top:1rem;">
						총 ${banners.length}개의 활성 배너가 있습니다. (최대 5개 표시)
					</p>
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
		async function renderAdminReviews() {
			const container = document.getElementById('reviewsAdminBody');
			if (!container) return;
			
			if (typeof API === 'undefined') {
				container.innerHTML = '<p style="text-align:center;color:var(--rose);padding:2rem;">API를 불러오는 중입니다...</p>';
				setTimeout(() => renderAdminReviews(), 500);
				return;
			}
			
			container.innerHTML = '<p style="text-align:center;color:var(--light);padding:2rem;">리뷰 목록을 불러오는 중...</p>';
			
			try {
				// 모든 리뷰 가져오기 (productId 없이 호출하면 관리자용 전체 리뷰)
				const reviews = await API.getReviews(null);
				
				if (!reviews || reviews.length === 0) {
					container.innerHTML = '<p style="text-align:center;color:var(--light);padding:2rem;">등록된 리뷰가 없습니다.</p>';
					return;
				}
				
				// 상품 정보 가져오기 (상품명 표시용)
				const products = await API.getProducts();
				const productMap = {};
				products.forEach(p => { productMap[p.id] = p; });
				
				let html = '';
				reviews.forEach(r => {
					const product = productMap[r.product_id] || { name: '알 수 없음' };
					html += `<div style="background:var(--sage-bg);padding:.75rem;border-radius:8px;margin-bottom:.5rem;display:flex;justify-content:space-between;align-items:flex-start;">
						<div style="flex:1;">
							<div style="font-size:.75rem;color:var(--light);margin-bottom:.25rem;">${product.name}</div>
							<div><strong>${r.user || r.user_name || '익명'}</strong> <span style="color:var(--light);font-size:.8rem;">${r.date || r.created_at?.substring(0, 10) || ''}</span></div>
							<div style="color:#d4a574;margin:.25rem 0;">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</div>
							<div style="font-size:.9rem;">${r.content}</div>
						</div>
						<button class="badge" style="cursor:pointer;font-size:.7rem;color:var(--rose);margin-left:1rem;" onclick="deleteAdminReview(${r.product_id}, ${r.id})">삭제</button>
					</div>`;
				});
				
				container.innerHTML = html;
			} catch (err) {
				console.error('리뷰 로드 오류:', err);
				container.innerHTML = '<p style="text-align:center;color:var(--rose);padding:2rem;">리뷰를 불러오는 중 오류가 발생했습니다: ' + (err.message || '알 수 없는 오류') + '</p>';
			}
		}
		
		async function deleteAdminReview(productId, reviewId) {
			if (!confirm('정말 삭제하시겠습니까?')) return;
			try {
				const result = await API.deleteReview(productId, reviewId);
				if (result.ok) {
					alert('리뷰가 삭제되었습니다.');
					renderAdminReviews();
				} else {
					alert(result.message || '리뷰 삭제 중 오류가 발생했습니다.');
				}
			} catch (err) {
				console.error('리뷰 삭제 오류:', err);
				alert('리뷰 삭제 중 오류가 발생했습니다.');
			}
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
			
			// 향기 타입 렌더링
			renderFragranceTypes();
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
		
		// ========== 향기 타입 관리 ==========
		function getFragranceTypes() {
			try {
				const stored = localStorage.getItem('dewscent_fragrance_types');
				if (stored) {
					return JSON.parse(stored);
				}
			} catch (e) {
				console.error('향기 타입 로드 실패:', e);
			}
			return ['시트러스', '플로럴', '우디', '머스크', '오리엔탈', '프레시'];
		}
		
		function setFragranceTypes(types) {
			localStorage.setItem('dewscent_fragrance_types', JSON.stringify(types));
		}
		
		function renderFragranceTypes() {
			const container = document.getElementById('fragranceTypesContainer');
			if (!container) return;
			
			const types = getFragranceTypes();
			container.innerHTML = types.map((type, index) => `
				<div style="display:flex;gap:.5rem;align-items:center;padding:.5rem;background:var(--sage-bg);border-radius:8px;">
					<input type="text" class="form-input fragrance-type-input" value="${type}" data-index="${index}" style="flex:1;">
					<button class="badge" style="cursor:pointer;background:var(--rose);color:#fff;border:none;padding:.25rem .5rem;font-size:.7rem;" onclick="removeFragranceType(${index})">삭제</button>
				</div>
			`).join('');
		}
		
		function addFragranceType() {
			const types = getFragranceTypes();
			types.push('새 향기 타입');
			setFragranceTypes(types);
			renderFragranceTypes();
		}
		
		function removeFragranceType(index) {
			const types = getFragranceTypes();
			if (types.length <= 1) {
				alert('최소 하나의 향기 타입이 필요합니다.');
				return;
			}
			types.splice(index, 1);
			setFragranceTypes(types);
			renderFragranceTypes();
		}
		
		function saveFragranceTypes() {
			const inputs = document.querySelectorAll('.fragrance-type-input');
			const types = Array.from(inputs).map(input => input.value.trim()).filter(Boolean);
			
			if (types.length === 0) {
				alert('최소 하나의 향기 타입이 필요합니다.');
				return;
			}
			
			setFragranceTypes(types);
			alert('향기 타입이 저장되었습니다. 메인 페이지를 새로고침하면 변경사항이 적용됩니다.');
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
					const base64Data = e.target.result;
					document.getElementById('prodImageUrl').value = base64Data;
					const preview = document.getElementById('prodImagePreview');
					preview.src = base64Data;
					preview.style.display = 'block';
					preview.onerror = function() {
						console.error('이미지 미리보기 로드 실패');
						preview.style.display = 'none';
					};
					document.getElementById('clearImageBtn').style.display = 'inline-block';
				};
				reader.readAsDataURL(file);
			}
		}
		
		function uploadProductDetailImage(input) {
			if (input.files && input.files[0]) {
				const file = input.files[0];
				if (file.size > 2 * 1024 * 1024) {
					alert('이미지 크기는 2MB 이하로 제한됩니다.');
					input.value = '';
					return;
				}
				const reader = new FileReader();
				reader.onload = function(e) {
					const base64Data = e.target.result;
					document.getElementById('prodDetailImageUrl').value = base64Data;
					const preview = document.getElementById('prodDetailImagePreview');
					preview.src = base64Data;
					preview.style.display = 'block';
					preview.onerror = function() {
						console.error('상세 이미지 미리보기 로드 실패');
						preview.style.display = 'none';
					};
					document.getElementById('clearDetailImageBtn').style.display = 'inline-block';
				};
				reader.readAsDataURL(file);
			}
		}
		
		function clearProductImage() {
			if (confirm('이미지를 삭제하시겠습니까?')) {
				document.getElementById('prodImageUrl').value = '';
				document.getElementById('prodImageFile').value = '';
				document.getElementById('prodImagePreview').style.display = 'none';
				document.getElementById('prodImagePreview').src = '';
				document.getElementById('clearImageBtn').style.display = 'none';
			}
		}
		
		function clearProductDetailImage() {
			if (confirm('상세 이미지를 삭제하시겠습니까?')) {
				document.getElementById('prodDetailImageUrl').value = '';
				document.getElementById('prodDetailImageFile').value = '';
				document.getElementById('prodDetailImagePreview').style.display = 'none';
				document.getElementById('prodDetailImagePreview').src = '';
				document.getElementById('clearDetailImageBtn').style.display = 'none';
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

		// ========== 공지사항/이벤트 관리 ==========
		async function renderNotices() {
			const tbody = document.getElementById('noticesTableBody');
			if (!tbody) return;
			
			if (typeof API === 'undefined') {
				tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--rose)">API를 불러오는 중입니다...</td></tr>';
				setTimeout(() => renderNotices(), 500);
				return;
			}
			
			try {
				const notices = await API.getNotices();
				if (notices.length === 0) {
					tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--light)">등록된 공지/이벤트가 없습니다.</td></tr>';
					return;
				}
				tbody.innerHTML = notices.map(n => {
				const period = (n.startDate || '') + (n.endDate ? ' ~ ' + n.endDate : '');
				return `
					<tr>
						<td><span class="badge ${n.type === 'event' ? 'style="background:var(--rose);color:#fff;"' : ''}">${n.type === 'event' ? '이벤트' : '공지'}</span></td>
						<td>${n.title}</td>
						<td style="font-size:.85rem;color:var(--light);">${period || '제한없음'}</td>
						<td><span class="badge ${n.active ? '' : 'style="background:var(--border);"'}">${n.active ? '활성' : '비활성'}</span></td>
						<td>
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.3rem .6rem;font-size:.75rem;" onclick="editNotice(${n.id})">수정</button>
							<button class="badge" style="cursor:pointer;background:var(--rose);color:#fff;border:none;padding:.3rem .6rem;font-size:.75rem;" onclick="deleteNotice(${n.id})">삭제</button>
						</td>
					</tr>
				`;
				}).join('');
			} catch (err) {
				console.error('공지사항 로드 오류:', err);
				tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--rose);">공지사항을 불러오는 중 오류가 발생했습니다.</td></tr>`;
			}
		}
		function openNoticeForm() {
			document.getElementById('noticeForm').style.display = 'block';
			document.getElementById('noticeEditId').value = '';
			document.getElementById('noticeType').value = 'notice';
			document.getElementById('noticeTitle').value = '';
			document.getElementById('noticeContent').value = '';
			document.getElementById('noticeStartDate').value = '';
			document.getElementById('noticeEndDate').value = '';
			document.getElementById('noticeLink').value = '';
			document.getElementById('noticeImageUrl').value = '';
			document.getElementById('noticeActive').checked = true;
			document.getElementById('noticeImagePreview').style.display = 'none';
		}
		function closeNoticeForm() {
			document.getElementById('noticeForm').style.display = 'none';
		}
		async function saveNotice() {
			const editId = document.getElementById('noticeEditId').value;
			const type = document.getElementById('noticeType').value;
			const title = document.getElementById('noticeTitle').value.trim();
			const content = document.getElementById('noticeContent').value.trim();
			if (!title || !content) {
				alert('제목과 내용을 입력해주세요.');
				return;
			}
			
			if (typeof API === 'undefined') {
				alert('API를 불러오는 중입니다. 잠시 후 다시 시도해주세요.');
				return;
			}
			
			try {
				const notices = await API.getNotices();
				if (editId) {
					const idx = notices.findIndex(n => n.id === parseInt(editId));
					if (idx !== -1) {
						notices[idx] = {
							...notices[idx],
							type, title, content,
							startDate: document.getElementById('noticeStartDate').value || '',
							endDate: document.getElementById('noticeEndDate').value || '',
							link: document.getElementById('noticeLink').value.trim() || '',
							imageUrl: document.getElementById('noticeImageUrl').value.trim() || '',
							active: document.getElementById('noticeActive').checked
						};
					}
				} else {
					notices.push({
						id: Date.now(),
						type, title, content,
						startDate: document.getElementById('noticeStartDate').value || '',
						endDate: document.getElementById('noticeEndDate').value || '',
						link: document.getElementById('noticeLink').value.trim() || '',
						imageUrl: document.getElementById('noticeImageUrl').value.trim() || '',
						active: document.getElementById('noticeActive').checked,
						createdAt: new Date().toISOString().split('T')[0]
					});
				}
				await API.setNotices(notices);
				closeNoticeForm();
				await renderNotices();
				alert('저장되었습니다. 메인 페이지 상단에 표시됩니다.');
			} catch (err) {
				console.error('공지사항 저장 오류:', err);
				alert('공지사항 저장 중 오류가 발생했습니다.');
			}
		}
		async function editNotice(id) {
			if (typeof API === 'undefined') {
				alert('API를 불러오는 중입니다. 잠시 후 다시 시도해주세요.');
				return;
			}
			
			try {
				const notices = await API.getNotices();
				const notice = notices.find(n => n.id === id);
				if (!notice) {
					alert('공지사항을 찾을 수 없습니다.');
					return;
				}
				document.getElementById('noticeEditId').value = id;
				document.getElementById('noticeType').value = notice.type;
				document.getElementById('noticeTitle').value = notice.title;
				document.getElementById('noticeContent').value = notice.content;
				document.getElementById('noticeStartDate').value = notice.startDate || '';
				document.getElementById('noticeEndDate').value = notice.endDate || '';
				document.getElementById('noticeLink').value = notice.link || '';
				document.getElementById('noticeImageUrl').value = notice.imageUrl || '';
				document.getElementById('noticeActive').checked = notice.active !== false;
				if (notice.imageUrl) {
					const preview = document.getElementById('noticeImagePreview');
					preview.src = notice.imageUrl;
					preview.style.display = 'block';
				}
				document.getElementById('noticeForm').style.display = 'block';
			} catch (err) {
				console.error('공지사항 편집 오류:', err);
				alert('공지사항 정보를 불러오는 중 오류가 발생했습니다.');
			}
		}
		async function deleteNotice(id) {
			if (!confirm('정말 삭제하시겠습니까?')) return;
			
			if (typeof API === 'undefined') {
				alert('API를 불러오는 중입니다. 잠시 후 다시 시도해주세요.');
				return;
			}
			
			try {
				const notices = await API.getNotices();
				const filtered = notices.filter(n => n.id !== id);
				await API.setNotices(filtered);
				await renderNotices();
			} catch (err) {
				console.error('공지사항 삭제 오류:', err);
				alert('공지사항 삭제 중 오류가 발생했습니다.');
			}
		}
		function uploadNoticeImage(input) {
			if (input.files && input.files[0]) {
				const file = input.files[0];
				if (file.size > 2 * 1024 * 1024) {
					alert('이미지 크기는 2MB 이하로 제한됩니다.');
					input.value = '';
					return;
				}
				const reader = new FileReader();
				reader.onload = function(e) {
					document.getElementById('noticeImageUrl').value = e.target.result;
					const preview = document.getElementById('noticeImagePreview');
					preview.src = e.target.result;
					preview.style.display = 'block';
				};
				reader.readAsDataURL(file);
			}
		}

		// ========== 쿠폰 관리 ==========
		async function renderCoupons() {
			const tbody = document.getElementById('couponsTableBody');
			if (!tbody) return;

			if (typeof API === 'undefined') {
				tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--rose)">API를 불러오는 중입니다...</td></tr>';
				setTimeout(() => renderCoupons(), 500);
				return;
			}

			tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--light)">불러오는 중입니다...</td></tr>';

			try {
				const coupons = await API.getCoupons();
				if (coupons.length === 0) {
					tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--light)">등록된 쿠폰이 없습니다.</td></tr>';
					return;
				}
				tbody.innerHTML = coupons.map(c => {
				const discountText = c.type === 'percent' ? `${c.value}%` : `₩${c.value.toLocaleString()}`;
				const period = (c.startDate || '') + (c.endDate ? ' ~ ' + c.endDate : '');
				const usage = `${c.usedCount || 0}${c.usageLimit > 0 ? '/' + c.usageLimit : ''}`;
				return `
					<tr>
						<td><strong style="color:var(--sage);">${c.code}</strong></td>
						<td>${c.name}</td>
						<td>${discountText}</td>
						<td style="font-size:.85rem;color:var(--light);">${period || '제한없음'}</td>
						<td>${usage}</td>
						<td><span class="badge ${c.active ? '' : 'style="background:var(--border);"'}">${c.active ? '활성' : '비활성'}</span></td>
						<td>
							<button class="badge" style="cursor:pointer;background:var(--sage);color:#fff;border:none;padding:.3rem .6rem;font-size:.75rem;" onclick="editCoupon(${c.id})">수정</button>
							<button class="badge" style="cursor:pointer;background:var(--rose);color:#fff;border:none;padding:.3rem .6rem;font-size:.75rem;" onclick="deleteCoupon(${c.id})">삭제</button>
						</td>
					</tr>
				`;
				}).join('');
			} catch (error) {
				console.error('쿠폰 목록 로딩 실패:', error);
				tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--rose)">쿠폰을 불러오는 중 오류가 발생했습니다.</td></tr>';
			}
		}
		function openCouponForm() {
			document.getElementById('couponForm').style.display = 'block';
			document.getElementById('couponEditId').value = '';
			document.getElementById('couponCode').value = '';
			document.getElementById('couponName').value = '';
			document.getElementById('couponType').value = 'percent';
			document.getElementById('couponValue').value = '';
			document.getElementById('couponMinAmount').value = '0';
			document.getElementById('couponMaxDiscount').value = '0';
			document.getElementById('couponStartDate').value = '';
			document.getElementById('couponEndDate').value = '';
			document.getElementById('couponUsageLimit').value = '0';
			document.getElementById('couponActive').checked = true;
		}
		function closeCouponForm() {
			document.getElementById('couponForm').style.display = 'none';
		}
		async function saveCoupon() {
			const editId = document.getElementById('couponEditId').value;
			const code = document.getElementById('couponCode').value.trim().toUpperCase();
			const name = document.getElementById('couponName').value.trim();
			const type = document.getElementById('couponType').value;
			const value = parseInt(document.getElementById('couponValue').value) || 0;
			if (!code || !name || value <= 0) {
				alert('필수 항목을 모두 입력해주세요. (쿠폰 코드, 쿠폰명, 할인 값)');
				return;
			}
			
			try {
				const baseUrl = window.DS_BASE_URL || '';
				const response = await fetch(`${baseUrl}/api/coupons.php?action=save`, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					credentials: 'include',
					body: JSON.stringify({
						id: editId ? parseInt(editId) : 0,
						code, name, type, value,
						minAmount: parseInt(document.getElementById('couponMinAmount').value) || 0,
						maxDiscount: parseInt(document.getElementById('couponMaxDiscount').value) || 0,
						startDate: document.getElementById('couponStartDate').value || null,
						endDate: document.getElementById('couponEndDate').value || null,
						usageLimit: parseInt(document.getElementById('couponUsageLimit').value) || 0,
						active: document.getElementById('couponActive').checked ? 1 : 0
					})
				});
				
				const data = await response.json();
				
				if (data.success) {
					// 캐시 초기화
					if (typeof API.clearCouponsCache === 'function') {
						API.clearCouponsCache();
					}
					closeCouponForm();
					await renderCoupons();
					alert('쿠폰이 저장되었습니다. 고객이 결제 시 사용할 수 있습니다.');
				} else {
					alert(data.message || '쿠폰 저장에 실패했습니다.');
				}
			} catch (error) {
				console.error('쿠폰 저장 실패:', error);
				alert('쿠폰 저장 중 오류가 발생했습니다.');
			}
		}
		async function editCoupon(id) {
			try {
				const coupons = await API.getCoupons();
				const coupon = coupons.find(c => c.id === id);
				if (!coupon) {
					alert('쿠폰을 찾을 수 없습니다.');
					return;
				}
				document.getElementById('couponEditId').value = id;
				document.getElementById('couponCode').value = coupon.code;
				document.getElementById('couponName').value = coupon.name;
				document.getElementById('couponType').value = coupon.type;
				document.getElementById('couponValue').value = coupon.value;
				document.getElementById('couponMinAmount').value = coupon.minAmount || 0;
				document.getElementById('couponMaxDiscount').value = coupon.maxDiscount || 0;
				document.getElementById('couponStartDate').value = coupon.startDate || '';
				document.getElementById('couponEndDate').value = coupon.endDate || '';
				document.getElementById('couponUsageLimit').value = coupon.usageLimit || 0;
				document.getElementById('couponActive').checked = coupon.active !== false;
				document.getElementById('couponForm').style.display = 'block';
			} catch (error) {
				console.error('쿠폰 수정 폼 로딩 실패:', error);
				alert('쿠폰 정보를 불러오는 중 오류가 발생했습니다.');
			}
		}
		async function deleteCoupon(id) {
			if (!confirm('정말 삭제하시겠습니까?')) return;
			
			try {
				const baseUrl = window.DS_BASE_URL || '';
				const response = await fetch(`${baseUrl}/api/coupons.php?action=delete`, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					credentials: 'include',
					body: JSON.stringify({ id: id })
				});
				
				const data = await response.json();
				
				if (data.success) {
					// 캐시 초기화
					if (typeof API.clearCouponsCache === 'function') {
						API.clearCouponsCache();
					}
					await renderCoupons();
					alert('쿠폰이 삭제되었습니다.');
				} else {
					alert(data.message || '쿠폰 삭제에 실패했습니다.');
				}
			} catch (error) {
				console.error('쿠폰 삭제 실패:', error);
				alert('쿠폰 삭제 중 오류가 발생했습니다.');
			}
		}

		// ========== 탭 전환 ==========
		const allTabs = ['overview','banners','popups','emotions','sections','mainproducts','products','reviews','inquiries','users','orders','coupons','notices','settings'];
		document.querySelectorAll('.admin-tab').forEach((btn) => {
			btn.addEventListener('click', async () => {
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
					if (tab === 'coupons') renderCoupons();
					if (tab === 'notices') await renderNotices();
					if (tab === 'products') renderProducts();
					if (tab === 'inquiries') renderAdminInquiries();
					if (tab === 'banners') await renderBanners();
					if (tab === 'popups') await renderPopups();
					if (tab === 'emotions') await renderEmotions();
					if (tab === 'sections') renderSectionsForm();
					if (tab === 'mainproducts') renderMainProducts();
					if (tab === 'reviews') renderAdminReviews();
					if (tab === 'settings') renderSiteSettings();
					// 상품 탭이 열릴 때마다 감정 체크박스 초기화 (폼이 열려있을 수 있으므로)
					if (tab === 'products') {
						// 폼이 열려있으면 감정 체크박스 로드
						const formWrap = document.getElementById('productFormWrap');
						if (formWrap && formWrap.style.display !== 'none') {
							loadEmotionCheckboxes([]);
						}
					}
					loaded[tab] = true;
				}
			});
		});

		// 기본 로드: 개요 표시 + KPI 업데이트
		async function updateKPIs() {
			try {
				// 오늘 날짜 (YYYY-MM-DD 형식, 한국 시간대 기준)
				const now = new Date();
				const kstOffset = 9 * 60; // 한국 시간대 UTC+9
				const kstTime = new Date(now.getTime() + (kstOffset + now.getTimezoneOffset()) * 60000);
				const today = kstTime.toISOString().split('T')[0];
				
				// 3개의 API를 병렬로 동시 호출 (순차 호출보다 3배 빠름)
				const [inquiries, users, orders] = await Promise.all([
					API.getInquiries().catch(err => { console.error('문의 데이터 로드 오류:', err); return []; }),
					API.getUsers().catch(err => { console.error('회원 데이터 로드 오류:', err); return []; }),
					API.getAdminOrders().catch(err => { console.error('주문 데이터 로드 오류:', err); return []; })
				]);
				
				// 문의 데이터 업데이트
				if (inquiries && Array.isArray(inquiries)) {
					const waiting = inquiries.filter(inq => inq.status === 'waiting').length;
					const waitingEl = document.getElementById('kpi-waiting-inquiries');
					const totalEl = document.getElementById('kpi-total-inquiries');
					if (waitingEl) waitingEl.textContent = waiting;
					if (totalEl) totalEl.textContent = inquiries.length;
				}
				
				// 회원 데이터 업데이트 (오늘 가입)
				if (users && Array.isArray(users)) {
					const todaySignups = users.filter(u => {
						const joinedDate = u.joinedAt || u.created_at?.substring(0, 10) || '';
						return joinedDate === today;
					}).length;
					const signupsEl = document.getElementById('kpi-today-signups');
					if (signupsEl) signupsEl.textContent = todaySignups;
				}
				
				// 주문 데이터 업데이트 (오늘 주문)
				if (orders && Array.isArray(orders)) {
					const todayOrders = orders.filter(o => {
						const orderDate = o.orderedAt || '';
						return orderDate === today;
					}).length;
					const ordersEl = document.getElementById('kpi-today-orders');
					if (ordersEl) ordersEl.textContent = todayOrders;
				}
			} catch (err) {
				console.error('KPI 업데이트 오류:', err);
				// 오류 시 기본값 유지
			}
		}
		updateKPIs();
		
		// 통계 그래프 관련 변수
		let ordersChart = null;
		let revenueChart = null;
		let signupsChart = null;
		let statusChart = null;
		
		// 날짜 범위 초기화
		function resetDateRange() {
			const toDate = new Date();
			const fromDate = new Date();
			fromDate.setDate(fromDate.getDate() - 30);
			
			document.getElementById('statFromDate').value = fromDate.toISOString().split('T')[0];
			document.getElementById('statToDate').value = toDate.toISOString().split('T')[0];
			
			loadStatistics();
		}
		
		// 통계 데이터 로드
		async function loadStatistics() {
			const from = document.getElementById('statFromDate').value;
			const to = document.getElementById('statToDate').value;
			
			if (!from || !to) {
				alert('시작일과 종료일을 모두 선택해주세요.');
				return;
			}
			
			if (new Date(from) > new Date(to)) {
				alert('시작일이 종료일보다 늦을 수 없습니다.');
				return;
			}
			
			try {
				const baseUrl = window.DS_BASE_URL || '';
				const response = await fetch(`${baseUrl}/api/admin/statistics.php?from=${from}&to=${to}`, {
					credentials: 'include'
				});
				
				if (!response.ok) {
					throw new Error('통계 데이터를 불러올 수 없습니다.');
				}
				
				const data = await response.json();
				
				if (!data.ok) {
					throw new Error(data.message || '통계 데이터를 불러올 수 없습니다.');
				}
				
				// 요약 정보 업데이트
				updateSummary(data.summary);
				
				// 그래프 업데이트
				updateCharts(data);
				
			} catch (error) {
				console.error('통계 로드 오류:', error);
				alert('통계 데이터를 불러오는 중 오류가 발생했습니다.');
			}
		}
		
		// 요약 정보 업데이트
		function updateSummary(summary) {
			document.getElementById('statTotalOrders').textContent = (summary.total_orders || 0).toLocaleString();
			document.getElementById('statTotalRevenue').textContent = '₩' + (summary.total_revenue || 0).toLocaleString();
			document.getElementById('statAvgOrder').textContent = '₩' + Math.round(summary.avg_order_value || 0).toLocaleString();
			document.getElementById('statCustomers').textContent = (summary.unique_customers || 0).toLocaleString();
		}
		
		// 그래프 업데이트
		function updateCharts(data) {
			const daily = data.daily || [];
			const status = data.status || [];
			
			// 날짜 라벨
			const labels = daily.map(d => {
				const date = new Date(d.date);
				return `${date.getMonth() + 1}/${date.getDate()}`;
			});
			
			// 주문 수 그래프
			const ordersCtx = document.getElementById('ordersChart');
			if (ordersChart) {
				ordersChart.destroy();
			}
			ordersChart = new Chart(ordersCtx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: [{
						label: '주문 수',
						data: daily.map(d => d.orders),
						borderColor: 'rgb(95, 113, 97)',
						backgroundColor: 'rgba(95, 113, 97, 0.1)',
						tension: 0.4,
						fill: true
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					plugins: {
						legend: {
							display: false
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								stepSize: 1
							}
						}
					}
				}
			});
			
			// 매출 그래프
			const revenueCtx = document.getElementById('revenueChart');
			if (revenueChart) {
				revenueChart.destroy();
			}
			revenueChart = new Chart(revenueCtx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: [{
						label: '매출',
						data: daily.map(d => d.paid_revenue),
						borderColor: 'rgb(95, 113, 97)',
						backgroundColor: 'rgba(95, 113, 97, 0.1)',
						tension: 0.4,
						fill: true
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							callbacks: {
								label: function(context) {
									return '₩' + context.parsed.y.toLocaleString();
								}
							}
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								callback: function(value) {
									return '₩' + value.toLocaleString();
								}
							}
						}
					}
				}
			});
			
			// 가입자 그래프
			const signupsCtx = document.getElementById('signupsChart');
			if (signupsChart) {
				signupsChart.destroy();
			}
			signupsChart = new Chart(signupsCtx, {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [{
						label: '가입자',
						data: daily.map(d => d.signups),
						backgroundColor: 'rgba(95, 113, 97, 0.6)',
						borderColor: 'rgb(95, 113, 97)',
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					plugins: {
						legend: {
							display: false
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								stepSize: 1
							}
						}
					}
				}
			});
			
			// 상태별 통계 그래프
			const statusCtx = document.getElementById('statusChart');
			if (statusChart) {
				statusChart.destroy();
			}
			
			const statusLabels = status.map(s => {
				// 상태명 한글 변환
				const statusMap = {
					'결제대기': '결제대기',
					'pending': '결제대기',
					'결제완료': '결제완료',
					'paid': '결제완료',
					'배송준비중': '배송준비중',
					'preparing': '배송준비중',
					'배송중': '배송중',
					'shipping': '배송중',
					'배송완료': '배송완료',
					'delivered': '배송완료',
					'취소': '취소',
					'cancelled': '취소',
					'취소요청': '취소요청',
					'cancel_requested': '취소요청'
				};
				return statusMap[s.status] || s.status;
			});
			
			const statusColors = [
				'rgba(95, 113, 97, 0.8)',
				'rgba(201, 184, 150, 0.8)',
				'rgba(107, 140, 206, 0.8)',
				'rgba(200, 200, 200, 0.8)',
				'rgba(150, 150, 150, 0.8)',
				'rgba(180, 180, 180, 0.8)'
			];
			
			statusChart = new Chart(statusCtx, {
				type: 'doughnut',
				data: {
					labels: statusLabels,
					datasets: [{
						data: status.map(s => s.count),
						backgroundColor: statusColors.slice(0, status.length),
						borderWidth: 2,
						borderColor: '#fff'
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					plugins: {
						legend: {
							position: 'bottom'
						}
					}
				}
			});
		}
		
		// 페이지 로드 시 날짜 초기화 및 통계 로드 (약간의 지연을 두어 KPI 먼저 로드)
		// API가 로드될 때까지 대기하는 함수
		function waitForAPI(maxAttempts = 50) {
			return new Promise((resolve, reject) => {
				let attempts = 0;
				const checkAPI = () => {
					if (typeof window.API !== 'undefined' && window.API) {
						resolve(window.API);
					} else if (attempts < maxAttempts) {
						attempts++;
						setTimeout(checkAPI, 100);
					} else {
						reject(new Error('API 로드 시간 초과'));
					}
				};
				checkAPI();
			});
		}
		
		document.addEventListener('DOMContentLoaded', async function() {
			// API가 로드될 때까지 대기
			try {
				await waitForAPI();
				// 통계는 약간 지연시켜서 KPI 로딩과 겹치지 않도록
				setTimeout(() => {
					resetDateRange();
				}, 100);
			} catch (err) {
				console.error('API 로드 실패:', err);
				alert('관리자 도구를 초기화하는 중 오류가 발생했습니다. 페이지를 새로고침해주세요.');
			}
		});
	</script>
</body>
</html>


