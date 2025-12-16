<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function ensure_admin(): void
{
	// 두 가지 방식 모두 지원: admin_logged_in 또는 role = 'admin'
	$isAdmin = !empty($_SESSION['admin_logged_in']) || 
	           (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
	
	if (!$isAdmin) {
		header('Location: index.php');
		exit;
	}
	
	// role = 'admin'으로 로그인한 경우 admin_logged_in도 설정 (호환성)
	if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin' && empty($_SESSION['admin_logged_in'])) {
		$_SESSION['admin_logged_in'] = true;
		$_SESSION['admin_email'] = $_SESSION['email'] ?? '';
		$_SESSION['admin_name'] = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
		$_SESSION['admin_id'] = $_SESSION['user_id'] ?? 0;
	}
}

// API용 관리자 인증 체크 (JSON 응답)
function ensure_admin_api(): bool
{
	// 두 가지 방식 모두 지원: admin_logged_in 또는 role = 'admin'
	$isAdmin = !empty($_SESSION['admin_logged_in']) || 
	           (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
	
	if (!$isAdmin) {
		http_response_code(401);
		echo json_encode(['ok' => false, 'message' => '관리자 로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
		return false;
	}
	
	// role = 'admin'으로 로그인한 경우 admin_logged_in도 설정 (호환성)
	if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin' && empty($_SESSION['admin_logged_in'])) {
		$_SESSION['admin_logged_in'] = true;
		$_SESSION['admin_email'] = $_SESSION['email'] ?? '';
		$_SESSION['admin_name'] = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
		$_SESSION['admin_id'] = $_SESSION['user_id'] ?? 0;
	}
	
	return true;
}

// 관리자 로그인 여부 확인 (리다이렉트 없이)
function is_admin(): bool
{
	return !empty($_SESSION['admin_logged_in']) || 
	       (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
}


