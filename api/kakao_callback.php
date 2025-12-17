<?php
/**
 * 카카오 소셜 로그인 콜백 처리
 *
 * 흐름:
 * 1. 카카오에서 인가 코드(code) 받기
 * 2. 인가 코드로 액세스 토큰 발급
 * 3. 액세스 토큰으로 사용자 정보 조회
 * 4. 기존 회원 확인 또는 신규 가입 처리
 * 5. 세션 설정 후 메인 페이지로 리다이렉트
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

// 테이블 자동 생성 (kakao_id 컬럼 포함)
ensure_tables_exist();

// 1. 인가 코드 받기
$code = $_GET['code'] ?? '';

if (empty($code)) {
    // 인가 코드가 없으면 에러
    header('Location: ' . SITE_URL . '?error=kakao_auth_failed');
    exit;
}

// 2. 액세스 토큰 발급
$tokenUrl = "https://kauth.kakao.com/oauth/token";

$ch = curl_init();

$tokenData = [
    'grant_type' => 'authorization_code',
    'client_id' => KAKAO_CLIENT_ID,
    'redirect_uri' => KAKAO_REDIRECT_URI,
    'code' => $code
];

curl_setopt_array($ch, [
    CURLOPT_URL => $tokenUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded;charset=utf-8'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

$tokenResponse = curl_exec($ch);
$tokenError = curl_error($ch);

if ($tokenError) {
    error_log('카카오 토큰 요청 실패: ' . $tokenError);
    header('Location: ' . SITE_URL . '?error=kakao_token_failed');
    exit;
}

$tokenResult = json_decode($tokenResponse, true);

if (!isset($tokenResult['access_token'])) {
    error_log('카카오 토큰 응답 오류: ' . $tokenResponse);
    header('Location: ' . SITE_URL . '?error=kakao_token_invalid');
    exit;
}

$accessToken = $tokenResult['access_token'];

// 3. 사용자 정보 조회
$profileUrl = "https://kapi.kakao.com/v2/user/me";

curl_setopt_array($ch, [
    CURLOPT_URL => $profileUrl,
    CURLOPT_POST => false,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
    CURLOPT_RETURNTRANSFER => true
]);

$profileResponse = curl_exec($ch);
$profileError = curl_error($ch);
curl_close($ch);

if ($profileError) {
    error_log('카카오 프로필 요청 실패: ' . $profileError);
    header('Location: ' . SITE_URL . '?error=kakao_profile_failed');
    exit;
}

$profileResult = json_decode($profileResponse, true);

if (!isset($profileResult['id'])) {
    error_log('카카오 프로필 응답 오류: ' . $profileResponse);
    header('Location: ' . SITE_URL . '?error=kakao_profile_invalid');
    exit;
}

// 사용자 정보 추출
$kakaoId = (string) $profileResult['id'];
$nickname = $profileResult['properties']['nickname'] ?? '카카오사용자';
$profileImage = $profileResult['properties']['profile_image'] ?? null;
$kakaoEmail = $profileResult['kakao_account']['email'] ?? null;

// 4. 기존 회원 확인 (kakao_id로 검색)
$user = db()->fetchOne(
    "SELECT id, name, email, is_admin, kakao_id, profile_image FROM users WHERE kakao_id = ? LIMIT 1",
    [$kakaoId]
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
    if ($kakaoEmail) {
        $existingUser = db()->fetchOne(
            "SELECT id, name, email, is_admin, kakao_id FROM users WHERE email = ? LIMIT 1",
            [$kakaoEmail]
        );

        if ($existingUser) {
            // 기존 이메일 계정에 카카오 연동
            db()->query(
                "UPDATE users SET kakao_id = ?, profile_image = ?, updated_at = NOW() WHERE id = ?",
                [$kakaoId, $profileImage, $existingUser['id']]
            );
            $user = $existingUser;
            $user['kakao_id'] = $kakaoId;
        }
    }

    // 완전 신규 회원 가입
    if (!$user) {
        // 이메일이 없으면 카카오ID 기반 임시 이메일 생성
        $email = $kakaoEmail ?: 'kakao_' . $kakaoId . '@dewscent.local';

        db()->query(
            "INSERT INTO users (name, email, password, kakao_id, profile_image, created_at) VALUES (?, ?, '', ?, ?, NOW())",
            [$nickname, $email, $kakaoId, $profileImage]
        );

        $newUserId = db()->getConnection()->lastInsertId();

        $user = [
            'id' => (int) $newUserId,
            'name' => $nickname,
            'email' => $email,
            'is_admin' => 0,
            'kakao_id' => $kakaoId,
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
$_SESSION['login_type'] = 'kakao';

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
