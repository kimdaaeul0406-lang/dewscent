<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "배송안내 | DewScent";
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
				<h1>배송안내</h1>
				<p>안전하고 빠르게 향기를 전해드리기 위한 배송 정책입니다.</p>
			</div>

			<div class="info-grid" style="margin-bottom:1rem;">
				<div class="info-card">
					<h3>방법 · 기간</h3>
					<ul style="line-height:1.9">
						<li>택배사: CJ대한통운 (변경될 수 있음)</li>
						<li>출고: 결제 확인 후 1~2영업일</li>
						<li>배송: 출고 후 평균 1~3영업일</li>
					</ul>
				</div>
				<div class="info-card">
					<h3>배송비</h3>
					<ul style="line-height:1.9">
						<li>기본: 3,000원</li>
						<li>50,000원 이상: 무료배송</li>
						<li>제주/도서산간: 추가 배송비</li>
					</ul>
				</div>
			</div>

			<div class="info-card">
				<h3>유의사항</h3>
				<ul style="line-height:1.9">
					<li>주소/연락처 오기재, 수취 거부 시 왕복 배송비가 청구될 수 있습니다.</li>
					<li>천재지변·택배사 물량 증가 등으로 지연될 수 있습니다.</li>
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


