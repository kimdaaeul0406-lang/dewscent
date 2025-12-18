<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$pageTitle = "관리자 로그인 | DewScent";

// 이미 로그인되어 있으면 대시보드로
if (!empty($_SESSION['admin_logged_in'])) {
	header('Location: dashboard.php');
	exit;
}

$error = null;

// GET으로 직접 접근하면 메인으로 돌려보내기 (로그인은 팝업에서 POST로만)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../index.php');
	exit;
}

// DB 연동 로그인 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['username'] ?? ''); // 이메일 또는 이름으로 로그인
	$password = trim($_POST['password'] ?? '');

	if ($email === '' || $password === '') {
		$error = '이메일과 비밀번호를 입력해주세요.';
	} else {
		try {
			// DB에서 관리자 계정 조회 (is_admin = 1)
			// 이메일 또는 이름으로 로그인 가능
			$user = db()->fetchOne(
				"SELECT * FROM users WHERE (email = ? OR name = ?) AND is_admin = 1",
				[$email, $email]
			);

			// 비밀번호 확인 (해시 또는 평문 둘 다 지원)
			$passwordMatch = false;
			if ($user) {
				if (password_verify($password, $user['password'])) {
					$passwordMatch = true;
				} elseif ($user['password'] === $password) {
					// 평문 비밀번호 지원 (나중에 해시로 변경 권장)
					$passwordMatch = true;
				}
			}

			if ($user && $passwordMatch) {
				// 세션 고정 공격 방지 - 세션 ID 재생성
				session_regenerate_id(true);

				// 로그인 성공
				$_SESSION['admin_logged_in'] = true;
				$_SESSION['admin_email'] = $user['email'];
				$_SESSION['admin_name'] = $user['name'] ?? 'Admin';
				$_SESSION['admin_id'] = $user['id'];
				$_SESSION['role'] = 'admin';
				$_SESSION['user_id'] = $user['id'];
				$_SESSION['is_admin'] = 1;
				header('Location: dashboard.php');
				exit;
			} else {
				$error = '이메일 또는 비밀번호가 올바르지 않습니다.';
			}
		} catch (Exception $e) {
			$error = 'DB 연결 오류가 발생했습니다.';
		}
	}
}
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
<body class="cart-page">

	<header>
		<div class="header-center">
			<a href="../index.php" class="logo">DewScent Admin</a>
		</div>
	</header>

	<main id="main" class="visible">
		<section class="best-section">
			<div class="best-header">
				<h2 class="section-title">관리자 로그인</h2>
			</div>

			<div style="max-width:420px;margin:0 auto;background:#fff;border:1px solid var(--border);border-radius:16px;padding:2rem;">
				<?php if ($error): ?>
					<div style="background:rgba(196,164,160,0.15);border:1px solid var(--rose);border-radius:8px;padding:0.75rem 1rem;margin-bottom:1.5rem;text-align:center;">
						<p style="color:var(--rose);font-size:0.9rem;margin:0;"><?= htmlspecialchars($error) ?></p>
					</div>
				<?php endif; ?>
				<form method="post" action="index.php">
					<div class="form-group">
						<label class="form-label">이메일</label>
						<input type="text" name="username" class="form-input" placeholder="admin@example.com" required>
					</div>
					<div class="form-group">
						<label class="form-label">비밀번호</label>
						<input type="password" name="password" class="form-input" placeholder="비밀번호를 입력하세요" required>
					</div>
					<div style="display:flex;flex-direction:column;gap:0.5rem;margin-top:1.5rem;">
						<button type="submit" class="form-btn primary" style="margin:0;">로그인</button>
						<a href="../index.php" class="form-btn secondary" style="margin:0;text-align:center;">메인으로 돌아가기</a>
					</div>
				</form>
			</div>
		</section>
	</main>

	<script src="../public/js/main.js?v=4"></script>
</body>
</html>


