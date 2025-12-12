<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "고객센터 | DewScent";
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
				<h1>DewScent 고객센터</h1>
				<p>당신의 향기 여정을 돕는 듀센트 헬프센터입니다.</p>
			</div>

			<div class="info-grid" style="margin-bottom:1rem;">
				<div class="info-card">
					<h3>운영시간 <span class="badge-soft">KST</span></h3>
					<ul style="line-height:1.9">
						<li>평일 10:00 ~ 17:00</li>
						<li>점심 12:30 ~ 13:30</li>
						<li>주말·공휴일 휴무</li>
					</ul>
				</div>
				<div class="info-card">
					<h3>문의처</h3>
					<ul style="line-height:1.9">
						<li>이메일: hello@dewscent.kr</li>
						<li>대표번호: 02-1234-5678</li>
						<li>카카오톡 채널: 듀센트 고객센터</li>
					</ul>
				</div>
			</div>

			<div class="info-grid">
				<div class="info-card">
					<h3>주문/결제 문의</h3>
					<p>주문번호와 함께 문의해 주시면 보다 빠르게 도와드릴 수 있어요.</p>
				</div>
				<div class="info-card">
					<h3>교환/반품 안내</h3>
					<p>수령 후 7일 이내 신청 가능하며, 제품 상태에 따라 제한될 수 있습니다.</p>
					<a href="support-returns.php" class="form-btn ivory btn-compact" style="margin-top:.5rem;display:inline-flex;width:auto">자세히 보기</a>
				</div>
			</div>

			<div class="info-card" style="margin-top:1.5rem;text-align:center;padding:2rem;">
				<h3 style="margin-bottom:0.5rem;">더 빠른 상담이 필요하신가요?</h3>
				<p style="margin-bottom:1rem;color:var(--mid);">1:1 문의를 남겨주시면 영업일 기준 1~2일 내 답변드립니다.</p>
				<div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
					<button class="form-btn primary btn-compact" onclick="openModal('inquiryModal')">1:1 문의하기</button>
					<button class="form-btn secondary btn-compact" onclick="openInquiryList()">내 문의내역</button>
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


