<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function ensure_admin(): void
{
	if (empty($_SESSION['admin_logged_in'])) {
		header('Location: index.php');
		exit;
	}
}

// API용 관리자 인증 체크 (JSON 응답)
function ensure_admin_api(): bool
{
	if (empty($_SESSION['admin_logged_in'])) {
		http_response_code(401);
		echo json_encode(['error' => '관리자 로그인이 필요합니다.']);
		return false;
	}
	return true;
}

// 관리자 로그인 여부 확인 (리다이렉트 없이)
function is_admin(): bool
{
	return !empty($_SESSION['admin_logged_in']);
}


