<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

$baseUrl = rtrim(SITE_URL, '/');

function redirect_with_error(string $message, string $baseUrl): void {
  $_SESSION['signup_error'] = $message;
  header("Location: {$baseUrl}/index.php");
  exit;
}

if (!$username || !$email || !$password) {
  redirect_with_error('필수 값을 모두 입력해주세요.', $baseUrl);
}

// 이메일 중복 체크
$exists = db()->fetchOne(
  "SELECT id FROM users WHERE email = ? LIMIT 1",
  [$email]
);
if ($exists) {
  redirect_with_error('이미 가입된 이메일입니다.\n로그인 후 이용해주세요.', $baseUrl);
}

// 비밀번호 해시 생성 (기존 평문 데이터와 호환을 위해 로그인 시에는 평문도 허용)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 회원 저장
db()->insert(
  "INSERT INTO users (name, email, password, created_at)
   VALUES (?, ?, ?, NOW())",
  [$username, $email, $hashedPassword]
);

// 자동 로그인 & 플래시 메시지
regenerate_session();
$_SESSION['user_id'] = db()->getConnection()->lastInsertId();
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;
$_SESSION['role'] = 'user';
$_SESSION['signup_success'] = true;

// 메인으로 이동 (서브 디렉토리 배포 환경에서도 동작하도록 SITE_URL 사용)
header("Location: {$baseUrl}/index.php");
exit;
