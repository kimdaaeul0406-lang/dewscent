<?php
// 브라우저 측 인증 상태 동기화를 위한 스크립트
// PHP 세션을 로컬 스토리지에 반영하여 프론트 UI가 최신 로그인 상태를 인식하도록 함

$userInfo = null;
if (isset($_SESSION['user_id'])) {
    $userInfo = [
        'id'    => (int) $_SESSION['user_id'],
        'name'  => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role'  => $_SESSION['role'] ?? 'user',
    ];
}
?>
<script>
(function syncAuthState() {
  const USER_KEY = "ds_current_user";
  // 주문 조회 페이지에서 온 경우 localStorage를 정리하지 않음 (비회원 조회이므로)
  const fromOrderLookup = sessionStorage.getItem('from_order_lookup');
  if (fromOrderLookup === 'true') {
    // 주문 조회 페이지에서 온 경우 localStorage 정리 및 플래그 제거
    localStorage.removeItem(USER_KEY);
    sessionStorage.removeItem('from_order_lookup');
    return; // 세션 정보를 localStorage에 설정하지 않음
  }
  
  <?php if ($userInfo): ?>
  localStorage.setItem(USER_KEY, JSON.stringify(<?php echo json_encode($userInfo, JSON_UNESCAPED_UNICODE); ?>));
  <?php else: ?>
  localStorage.removeItem(USER_KEY);
  <?php endif; ?>

  <?php if (!empty($_SESSION['signup_success'])): ?>
  alert("회원가입이 완료되었습니다!\n자동으로 로그인되었습니다.");
  <?php unset($_SESSION['signup_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['signup_error'])): ?>
  // 회원가입 모달 열기 및 에러 표시
  setTimeout(() => {
    const modal = document.getElementById('signupModal');
    if (modal) {
      openModal('signupModal');
      const errorEl = document.getElementById('signupError');
      if (errorEl) {
        errorEl.textContent = <?php echo json_encode($_SESSION['signup_error'], JSON_UNESCAPED_UNICODE); ?>;
        errorEl.style.display = 'block';
      }
    }
  }, 100);
  <?php unset($_SESSION['signup_error']); ?>
  <?php endif; ?>

})();
</script>
