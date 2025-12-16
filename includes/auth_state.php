<?php
// 브라우저 측 인증 상태 동기화를 위한 스크립트
// PHP 세션을 로컬 스토리지에 반영하여 프론트 UI가 최신 로그인 상태를 인식하도록 함

$userInfo = null;
if (isset($_SESSION['user_id'])) {
    $userInfo = [
        'id'    => (int) $_SESSION['user_id'],
        'name'  => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
    ];
}
?>
<script>
(function syncAuthState() {
  const USER_KEY = "ds_current_user";
  <?php if ($userInfo): ?>
  localStorage.setItem(USER_KEY, JSON.stringify(<?php echo json_encode($userInfo, JSON_UNESCAPED_UNICODE); ?>));
  <?php else: ?>
  localStorage.removeItem(USER_KEY);
  <?php endif; ?>

  <?php if (!empty($_SESSION['signup_success'])): ?>
  alert("회원가입이 완료되었습니다!\n자동으로 로그인되었습니다.");
  <?php unset($_SESSION['signup_success']); ?>
  <?php endif; ?>
})();
</script>
