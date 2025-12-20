<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "교환/반품 안내 | DewScent";
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
<main id="main" class="visible">
	<section class="best-section">
		<div class="page-container">
			<div class="page-hero">
				<h1>교환 / 반품 안내</h1>
				<p>안전한 쇼핑을 위한 듀센트의 교환/반품 정책을 확인하세요.</p>
			</div>

			<div class="info-grid" style="margin-bottom:1rem;">
				<div class="info-card">
					<h3>가능 기간</h3>
					<ul style="line-height:1.9">
						<li>상품 수령 후 7일 이내 신청</li>
						<li>제품 훼손/사용 흔적/구성품 누락 시 불가</li>
					</ul>
				</div>
				<div class="info-card">
					<h3>왕복 배송비</h3>
					<ul style="line-height:1.9">
						<li>단순 변심: 고객 부담</li>
						<li>상품 불량/오배송: 판매자 부담</li>
					</ul>
				</div>
			</div>

			<div class="info-card">
				<h3>신청 방법</h3>
				<ul style="line-height:1.9">
					<li>고객센터(이메일/전화/카카오톡)로 주문번호와 사유 전달</li>
					<li>안내받은 주소로 안전 포장 후 반송</li>
					<li>검수 완료 후 교환 또는 환불 진행</li>
				</ul>
			</div>
		</div>
	</section>
</main>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/modals.php'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="../public/js/api.js?v=4"></script>
<script src="../public/js/main.js?v=4"></script>
</body>
</html>


