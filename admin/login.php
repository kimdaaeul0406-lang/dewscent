<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$pageTitle = "관리자 로그인 | DewScent";

// 이미 로그인되어 있으면 대시보드로 (또는 return URL로)
if (!empty($_SESSION['admin_logged_in']) || (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin')) {
	$returnUrl = $_GET['next'] ?? 'dashboard.php';
	// return URL이 외부 사이트를 가리키지 않도록 검증
	if (strpos($returnUrl, '://') === false && strpos($returnUrl, '../') === false) {
		header('Location: ' . $returnUrl);
	} else {
		header('Location: dashboard.php');
	}
	exit;
}

$error = null;
$next = $_GET['next'] ?? 'dashboard.php';

// DB 연동 로그인 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['username'] ?? ''); // 이메일 또는 이름으로 로그인
	$password = trim($_POST['password'] ?? '');
	$next = trim($_POST['next'] ?? $_GET['next'] ?? 'dashboard.php');

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

				// 로그인 성공 - DB 값을 그대로 세션에 매핑 (필수)
				$_SESSION['user_id'] = (int)$user['id'];  // DB의 id
				$_SESSION['role'] = !empty($user['is_admin']) ? 'admin' : 'user';  // DB의 is_admin을 기반으로 role 설정
				$_SESSION['is_admin'] = !empty($user['is_admin']) ? 1 : 0;  // DB의 is_admin 값 그대로
				
				// 호환성을 위한 추가 세션키
				$_SESSION['admin_logged_in'] = true;
				$_SESSION['admin_email'] = $user['email'];
				$_SESSION['admin_name'] = $user['name'] ?? 'Admin';
				$_SESSION['admin_id'] = (int)$user['id'];
				$_SESSION['email'] = $user['email'];
				$_SESSION['username'] = $user['name'] ?? 'Admin';
				$_SESSION['name'] = $user['name'] ?? 'Admin';
				
				// 디버깅 로그
				error_log('[Admin Login] 로그인 성공 - user_id: ' . $user['id'] . ', email: ' . $user['email']);
				error_log('[Admin Login] DB 값 - is_admin: ' . ($user['is_admin'] ?? 'null'));
				error_log('[Admin Login] 세션 설정 - user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
				error_log('[Admin Login] 세션 설정 - role: ' . ($_SESSION['role'] ?? 'not set'));
				error_log('[Admin Login] 세션 설정 - is_admin: ' . ($_SESSION['is_admin'] ?? 'not set'));
				
				// 임시 디버깅 출력 (확인 후 제거)
				if (defined('APP_DEBUG') && APP_DEBUG) {
					echo "<pre>DEBUG - 세션 내용:\n";
					var_dump($_SESSION);
					echo "</pre>";
					exit; // 임시로 여기서 종료하여 확인
				}
				
				// return URL로 리다이렉트 (보안 검증)
				if (strpos($next, '://') === false && strpos($next, '../') === false) {
					header('Location: ' . $next);
				} else {
					header('Location: dashboard.php');
				}
				exit;
			} else {
				$error = '이메일 또는 비밀번호가 올바르지 않습니다.';
				error_log('[Admin Login] 로그인 실패 - email: ' . $email);
			}
		} catch (Exception $e) {
			$error = 'DB 연결 오류가 발생했습니다.';
			error_log('[Admin Login] DB 오류: ' . $e->getMessage());
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
				<form method="post" action="login.php">
					<input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
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
