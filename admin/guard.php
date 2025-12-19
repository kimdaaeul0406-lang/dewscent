<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function ensure_admin(): void
{
	// 세션이 시작되지 않았다면 시작
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
	
	// 세션 값만으로 관리자 여부 판단 (DB 재조회 안 함)
	// DB의 is_admin 값을 그대로 세션에 저장하므로, 세션의 is_admin로 판단
	$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
	
	// 디버깅 로그
	error_log('[ensure_admin] ========== 권한 체크 시작 ==========');
	error_log('[ensure_admin] Session Status: ' . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE'));
	error_log('[ensure_admin] Session ID: ' . session_id());
	error_log('[ensure_admin] user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
	error_log('[ensure_admin] role: ' . ($_SESSION['role'] ?? 'not set'));
	error_log('[ensure_admin] is_admin: ' . ($_SESSION['is_admin'] ?? 'not set'));
	error_log('[ensure_admin] isAdmin 계산 결과: ' . ($isAdmin ? 'true' : 'false'));
	
	if (!$isAdmin) {
		error_log('[ensure_admin] Access denied - redirecting to admin/login.php');
		
		// 현재 요청한 URL을 return URL로 사용 (다시 돌아올 곳)
		$currentUrl = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php';
		$returnUrl = urlencode($currentUrl);
		
		// admin/login.php로 리다이렉트 (return URL 포함)
		// SITE_URL이 정의되어 있으면 절대 경로, 없으면 상대 경로 사용
		if (defined('SITE_URL')) {
			$loginUrl = rtrim(SITE_URL, '/') . '/admin/login.php';
		} else {
			$loginUrl = dirname($_SERVER['SCRIPT_NAME']) . '/login.php';
		}
		
		// return URL이 있으면 추가
		$redirectUrl = $loginUrl . '?next=' . $returnUrl;
		
		error_log('[ensure_admin] Redirect URL: ' . $redirectUrl);
		error_log('[ensure_admin] Return URL: ' . $currentUrl);
		header('Location: ' . $redirectUrl);
		exit;
	}
	
	// 관리자 확인 완료 - 로그 기록
	error_log('[ensure_admin] 관리자 권한 확인 완료');
}

// API용 관리자 인증 체크 (JSON 응답)
function ensure_admin_api(): bool
{
	// 세션 값만으로 관리자 여부 판단 (DB 재조회 안 함)
	$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
	
	if (!$isAdmin) {
		http_response_code(401);
		echo json_encode(['ok' => false, 'message' => '관리자 로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
		return false;
	}
	
	return true;
}

// 관리자 로그인 여부 확인 (리다이렉트 없이)
function is_admin(): bool
{
	// 세션 값만으로 관리자 여부 판단
	return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}


