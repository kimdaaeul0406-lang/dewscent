<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$username || !$email || !$password) {
  die('필수 값이 누락되었습니다.');
}

// 이메일 중복 체크
$exists = db()->fetchOne(
  "SELECT id FROM users WHERE email = ? LIMIT 1",
  [$email]
);
if ($exists) {
  die('이미 가입된 이메일입니다.');
}

// 회원 저장
db()->insert(
  "INSERT INTO users (username, email, password, role, created_at)
   VALUES (?, ?, ?, 'user', NOW())",
  [$username, $email, $password]
);

// 자동 로그인
$_SESSION['user_id'] = db()->getConnection()->lastInsertId();
$_SESSION['username'] = $username;
$_SESSION['role'] = 'user';

// 메인으로 이동
// 메인으로 이동 (서브 디렉토리 배포 환경에서도 동작하도록 SITE_URL 사용)
$baseUrl = rtrim(SITE_URL, '/');
header("Location: {$baseUrl}/index.php");
exit;