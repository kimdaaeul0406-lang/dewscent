<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 자동 생성
ensure_tables_exist();

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '이메일과 비밀번호를 모두 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = db()->fetchOne(
    "SELECT id, name, email, password, is_admin FROM users WHERE email = ? LIMIT 1",
    [$email]
);

if (!$user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => '등록되지 않은 이메일입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$storedPassword = $user['password'] ?? '';
$verified = password_verify($password, $storedPassword);

// 기존에 평문으로 저장된 데이터 호환 (해시가 아니거나 비어있는 경우)
if (!$verified && $storedPassword !== '') {
    $verified = hash_equals($storedPassword, $password);
}

if (!$verified) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => '비밀번호가 일치하지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

regenerate_session();
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['name'] ?? '';
$_SESSION['email'] = $user['email'] ?? '';
$_SESSION['role'] = !empty($user['is_admin']) ? 'admin' : 'user';

// 관리자인 경우 admin_logged_in도 설정 (관리자 대시보드 호환성)
if (!empty($user['is_admin'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = $user['email'] ?? '';
    $_SESSION['admin_name'] = $user['name'] ?? 'Admin';
    $_SESSION['admin_id'] = (int) $user['id'];
}

$responseUser = [
    'id'    => (int) $user['id'],
    'name'  => $user['name'] ?? '',
    'email' => $user['email'] ?? '',
    'role'  => !empty($user['is_admin']) ? 'admin' : 'user',
];

echo json_encode(['ok' => true, 'user' => $responseUser], JSON_UNESCAPED_UNICODE);
