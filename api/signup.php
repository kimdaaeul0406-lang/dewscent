<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

// 테이블 자동 생성
ensure_tables_exist();

header('Content-Type: application/json; charset=utf-8');

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// AJAX 요청인지 확인 (X-Requested-With 헤더 또는 Accept 헤더 확인)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function sendJsonResponse(bool $ok, string $message, array $data = []): void {
  $response = [
    'ok' => $ok,
    'message' => $message
  ];
  // $data 배열의 키-값을 response에 병합
  foreach ($data as $key => $value) {
    $response[$key] = $value;
  }
  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  exit;
}

function redirect_to_index(string $error = null): void {
  if ($error) {
    $_SESSION['signup_error'] = $error;
  }
  $baseUrl = rtrim(SITE_URL, '/');
  header("Location: {$baseUrl}/index.php");
  exit;
}

if (!$username || !$email || !$password) {
  if ($isAjax) {
    sendJsonResponse(false, '필수 값을 모두 입력해주세요.');
  } else {
    redirect_to_index('필수 값을 모두 입력해주세요.');
  }
}

// 이메일 중복 체크
$exists = db()->fetchOne(
  "SELECT id FROM users WHERE email = ? LIMIT 1",
  [$email]
);
if ($exists) {
  if ($isAjax) {
    sendJsonResponse(false, '이미 가입된 이메일입니다.\n로그인 후 이용해주세요.');
  } else {
    redirect_to_index('이미 가입된 이메일입니다.\n로그인 후 이용해주세요.');
  }
}

// 비밀번호 해시 생성 (기존 평문 데이터와 호환을 위해 로그인 시에는 평문도 허용)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
  // 데이터베이스 트랜잭션 시작
  $conn = db()->getConnection();
  $conn->beginTransaction();

  // 회원 저장 (insert 함수가 lastInsertId를 반환)
  // 컬럼 순서: id, email, password, name, phone, address, is_admin, created_at, updated_at
  $userId = db()->insert(
    "INSERT INTO users (email, password, name, created_at)
     VALUES (?, ?, ?, NOW())",
    [$email, $hashedPassword, $username]
  );

  // 트랜잭션 커밋
  $conn->commit();

  // 자동 로그인 & 플래시 메시지
  session_regenerate_id(true); // 세션 고정 공격 방지
  $_SESSION['user_id'] = $userId;
  $_SESSION['username'] = $username;
  $_SESSION['email'] = $email;
  $_SESSION['role'] = 'user';
  $_SESSION['signup_success'] = true;

  if ($isAjax) {
    sendJsonResponse(true, '회원가입이 완료되었습니다!', [
      'user' => [
        'id' => $userId,
        'name' => $username,
        'email' => $email,
        'role' => 'user'
      ]
    ]);
  } else {
    redirect_to_index();
  }
} catch (Exception $e) {
  if (isset($conn)) {
    $conn->rollBack();
  }
  // 실제 운영 환경에서는 로그를 남기는 것이 좋습니다. 
  error_log('회원가입 오류: ' . $e->getMessage());
  $errorMessage = APP_DEBUG ? $e->getMessage() : '회원가입 중 오류가 발생했습니다. 다시 시도해주세요.';
  if ($isAjax) {
    sendJsonResponse(false, $errorMessage);
  } else {
    redirect_to_index($errorMessage);
  }
}