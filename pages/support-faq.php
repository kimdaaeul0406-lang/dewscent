<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "FAQ | DewScent";
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
				<h1>자주 묻는 질문</h1>
				<p>주문/결제, 배송, 교환/반품 관련 핵심 정보를 모았습니다.</p>
			</div>

			<div class="faq-list">
				<div class="faq-item">
					<h4>Q. 배송은 얼마나 걸리나요?</h4>
					<p>결제 확인 후 1~2영업일 내 출고되며, 출고 후 평균 1~3영업일 소요됩니다.</p>
				</div>
				<div class="faq-item">
					<h4>Q. 교환/반품은 어떻게 하나요?</h4>
					<p>상품 수령 후 7일 이내 고객센터로 문의해 주시면 절차를 안내해 드립니다.</p>
				</div>
				<div class="faq-item">
					<h4>Q. 사용 흔적이 있으면 반품 가능한가요?</h4>
					<p>향수·디퓨저 등 개봉/사용 흔적이 있는 경우 반품이 제한될 수 있습니다.</p>
				</div>
				<div class="faq-item">
					<h4>Q. 사은품/구성품 누락 시?</h4>
					<p>수령 후 2일 이내 고객센터로 문의해 주시면 확인 후 재발송 도와드립니다.</p>
				</div>
				<div class="faq-item">
					<h4>Q. 무료배송 기준은?</h4>
					<p>50,000원 이상 구매 시 무료배송이 적용됩니다. (일부 지역 추가비용 별도)</p>
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


