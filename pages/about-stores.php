<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "매장 안내 | DewScent";
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
				<h1>매장 안내</h1>
				<p>가까운 듀센트 스토어에서 향기를 직접 경험하세요.</p>
			</div>

			<div class="info-grid">
				<div class="info-card">
					<h3>서울 플래그십</h3>
					<p>서울시 강남구 테헤란로 123</p>
					<p class="badge-soft">10:30 ~ 20:00</p>
				</div>
				<div class="info-card">
					<h3>홍대 스토어</h3>
					<p>서울시 마포구 양화로 45</p>
					<p class="badge-soft">11:00 ~ 21:00</p>
				</div>
				<div class="info-card">
					<h3>부산 스토어</h3>
					<p>부산시 해운대구 센텀서로 12</p>
					<p class="badge-soft">10:30 ~ 20:00</p>
				</div>
				<div class="info-card">
					<h3>재고/방문 문의</h3>
					<p>방문 전 제품 재고는 고객센터로 확인해 주세요.</p>
				</div>
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


