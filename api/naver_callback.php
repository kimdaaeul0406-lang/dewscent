<?php
/**
 * 네이버 소셜 로그인 콜백 처리
 *
 * 흐름:
 * 1. 네이버에서 인가 코드(code) 받기
 * 2. 인가 코드로 액세스 토큰 발급
 * 3. 액세스 토큰으로 사용자 정보 조회
 * 4. 기존 회원 확인 또는 신규 가입 처리
 * 5. 세션 설정 후 메인 페이지로 리다이렉트
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

// 테이블 자동 생성 (naver_id 컬럼 포함)
ensure_tables_exist();

// 1. 인가 코드 받기
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($code)) {
    // 에러 처리
    $error = $_GET['error'] ?? 'unknown';
    $errorDescription = $_GET['error_description'] ?? '네이버 로그인에 실패했습니다.';
    error_log('네이버 로그인 에러: ' . $error . ' - ' . $errorDescription);
    header('Location: ' . SITE_URL . '?error=naver_auth_failed');
    exit;
}

// 2. 액세스 토큰 발급
$tokenUrl = "https://nid.naver.com/oauth2.0/token";

$ch = curl_init();

$tokenParams = http_build_query([
    'grant_type' => 'authorization_code',
    'client_id' => NAVER_CLIENT_ID,
    'client_secret' => NAVER_CLIENT_SECRET,
    'code' => $code,
    'state' => $state
]);

curl_setopt_array($ch, [
    CURLOPT_URL => $tokenUrl . '?' . $tokenParams,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

$tokenResponse = curl_exec($ch);
$tokenError = curl_error($ch);

if ($tokenError) {
    error_log('네이버 토큰 요청 실패: ' . $tokenError);
    header('Location: ' . SITE_URL . '?error=naver_token_failed');
    exit;
}

$tokenResult = json_decode($tokenResponse, true);

if (!isset($tokenResult['access_token'])) {
    error_log('네이버 토큰 응답 오류: ' . $tokenResponse);
    header('Location: ' . SITE_URL . '?error=naver_token_invalid');
    exit;
}

$accessToken = $tokenResult['access_token'];

// 3. 사용자 정보 조회
$profileUrl = "https://openapi.naver.com/v1/nid/me";

curl_setopt_array($ch, [
    CURLOPT_URL => $profileUrl,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
    CURLOPT_RETURNTRANSFER => true
]);

$profileResponse = curl_exec($ch);
$profileError = curl_error($ch);
curl_close($ch);

if ($profileError) {
    error_log('네이버 프로필 요청 실패: ' . $profileError);
    header('Location: ' . SITE_URL . '?error=naver_profile_failed');
    exit;
}

$profileResult = json_decode($profileResponse, true);

if ($profileResult['resultcode'] !== '00' || !isset($profileResult['response']['id'])) {
    error_log('네이버 프로필 응답 오류: ' . $profileResponse);
    header('Location: ' . SITE_URL . '?error=naver_profile_invalid');
    exit;
}

// 사용자 정보 추출
$naverUser = $profileResult['response'];
$naverId = $naverUser['id'];
$nickname = $naverUser['nickname'] ?? $naverUser['name'] ?? '네이버사용자';
$email = $naverUser['email'] ?? null;
$profileImage = $naverUser['profile_image'] ?? null;

// 4. 기존 회원 확인 (naver_id로 검색)
$user = db()->fetchOne(
    "SELECT id, name, email, is_admin, naver_id, profile_image FROM users WHERE naver_id = ? LIMIT 1",
    [$naverId]
);

if ($user) {
    // 기존 회원 - 프로필 이미지 업데이트
    if ($profileImage && $profileImage !== $user['profile_image']) {
        db()->query(
            "UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?",
            [$profileImage, $user['id']]
        );
    }
} else {
    // 신규 회원 - 이메일로 기존 계정 확인
    if ($email) {
        $existingUser = db()->fetchOne(
            "SELECT id, name, email, is_admin, naver_id FROM users WHERE email = ? LIMIT 1",
            [$email]
        );

        if ($existingUser) {
            // 기존 이메일 계정에 네이버 연동
            db()->query(
                "UPDATE users SET naver_id = ?, profile_image = ?, updated_at = NOW() WHERE id = ?",
                [$naverId, $profileImage, $existingUser['id']]
            );
            $user = $existingUser;
            $user['naver_id'] = $naverId;
        }
    }

    // 완전 신규 회원 가입
    if (!$user) {
        // 이메일이 없으면 네이버ID 기반 임시 이메일 생성
        $userEmail = $email ?: 'naver_' . $naverId . '@dewscent.local';

        db()->query(
            "INSERT INTO users (name, email, password, naver_id, profile_image, created_at) VALUES (?, ?, '', ?, ?, NOW())",
            [$nickname, $userEmail, $naverId, $profileImage]
        );

        $newUserId = db()->getConnection()->lastInsertId();

        $user = [
            'id' => (int) $newUserId,
            'name' => $nickname,
            'email' => $userEmail,
            'is_admin' => 0,
            'naver_id' => $naverId,
            'profile_image' => $profileImage
        ];
    }
}

// 5. 세션 설정
regenerate_session();

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['name'] ?? $nickname;
$_SESSION['email'] = $user['email'] ?? '';
$_SESSION['role'] = !empty($user['is_admin']) ? 'admin' : 'user';
$_SESSION['profile_image'] = $profileImage;
$_SESSION['login_type'] = 'naver';

// 관리자인 경우 admin 세션도 설정
if (!empty($user['is_admin'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = $user['email'] ?? '';
    $_SESSION['admin_name'] = $user['name'] ?? $nickname;
    $_SESSION['admin_id'] = (int) $user['id'];
}

// 6. 메인 페이지로 리다이렉트 (로그인 성공)
header('Location: ' . SITE_URL . '?login=success');
exit;
