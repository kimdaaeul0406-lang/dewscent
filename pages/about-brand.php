<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "브랜드 소개 | DewScent";
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
				<h1>DewScent</h1>
				<p>당신의 향기, 당신의 무드. 듀센트와 함께하는 감성 향기 여정.</p>
			</div>

			<div class="info-grid" style="margin-bottom:1rem;">
				<div class="info-card">
					<h3>Our Philosophy</h3>
					<p>맑고 차분한 무드를 담아 일상에서 오래도록 사랑받는 향을 제안합니다.</p>
				</div>
				<div class="info-card">
					<h3>Design Language</h3>
					<p>세이지와 로즈, 아이보리 포인트로 미니멀하고 따뜻한 UI/패키지를 지향합니다.</p>
				</div>
			</div>

			<div class="info-card">
				<h3>Sustainability</h3>
				<ul style="line-height:1.9">
					<li>불필요한 포장 절감 및 재활용 소재 사용</li>
					<li>재사용 가능한 보틀/리필 중심의 제품 라인</li>
					<li>동물 실험 반대, 클린 포뮬러 지향</li>
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


