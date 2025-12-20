<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "제휴 문의 | DewScent";
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
				<h1>제휴 문의</h1>
				<p>콜라보 · 유통 · 입점 · 기업 선물 등 다양한 제안을 기다립니다.</p>
			</div>

			<div class="info-grid" style="margin-bottom:1rem;">
				<div class="info-card">
					<h3>연락처</h3>
					<ul style="line-height:1.9">
						<li>이메일: partner@dewscent.kr</li>
						<li>담당자: 마케팅팀 제휴 담당</li>
					</ul>
				</div>
				<div class="info-card">
					<h3>제출 정보</h3>
					<ul style="line-height:1.9">
						<li>회사명 / 담당자 / 연락처</li>
						<li>제안 개요 / 예상 일정</li>
						<li>브랜드/제품 소개 자료(선택)</li>
					</ul>
				</div>
			</div>

			<div class="info-card">
				<h3>진행 절차</h3>
				<ul style="line-height:1.9">
					<li>제안 접수 → 내부 검토 → 미팅/샘플 협의 → 계약/일정 확정</li>
				</ul>
				<p style="color:var(--light);margin-top:.5rem">접수 후 영업일 기준 2~3일 내 1차 회신을 드립니다.</p>
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


