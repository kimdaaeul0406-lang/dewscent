# 관리자 인증 수정 요약

## 1. ensure_admin()이 체크하는 세션키/조건

### 정확한 체크 조건
```php
$isAdmin = !empty($_SESSION['admin_logged_in']) || 
           (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
```

**필수 세션키 (둘 중 하나라도 설정되면 됨):**
1. `$_SESSION['admin_logged_in']` - truthy 값 (true, 1 등)
2. `$_SESSION['role'] === 'admin'` - 정확히 문자열 'admin'

**두 조건 중 하나만 만족하면 관리자로 인식됩니다.**

---

## 2. 로그인 성공 시 세션 설정 코드

### 수정 전 (admin/index.php 라인 50-56)
```php
// 로그인 성공
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_email'] = $user['email'];
$_SESSION['admin_name'] = $user['name'] ?? 'Admin';
$_SESSION['admin_id'] = $user['id'];
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = $user['id'];
$_SESSION['is_admin'] = 1;
```

### 수정 후 (admin/index.php 라인 49-61, admin/login.php 라인 59-75)
```php
// 로그인 성공 - ensure_admin()이 체크하는 세션키 모두 설정
$_SESSION['admin_logged_in'] = true;  // ✅ ensure_admin()이 체크하는 키
$_SESSION['admin_email'] = $user['email'];
$_SESSION['admin_name'] = $user['name'] ?? 'Admin';
$_SESSION['admin_id'] = $user['id'];
$_SESSION['role'] = 'admin';  // ✅ ensure_admin()이 체크하는 키
$_SESSION['user_id'] = $user['id'];
$_SESSION['is_admin'] = 1;

// 추가 정보 (호환성)
$_SESSION['email'] = $user['email'];
$_SESSION['username'] = $user['name'] ?? 'Admin';
$_SESSION['name'] = $user['name'] ?? 'Admin';

// 디버깅 로그 추가
error_log('[Admin Login] 로그인 성공 - user_id: ' . $user['id'] . ', email: ' . $user['email']);
error_log('[Admin Login] 세션 설정 - admin_logged_in: ' . ($_SESSION['admin_logged_in'] ? 'true' : 'false'));
error_log('[Admin Login] 세션 설정 - role: ' . ($_SESSION['role'] ?? 'not set'));
```

### 변경 사항
- **기존 코드는 이미 올바르게 설정하고 있었음**
- **추가된 것**: 호환성을 위한 추가 세션키 (`email`, `username`, `name`)
- **추가된 것**: 디버깅을 위한 로그

---

## 3. ensure_admin() 리다이렉트 수정 코드

### 수정 전 (admin/guard.php 라인 30-41)
```php
if (!$isAdmin) {
	error_log('[ensure_admin] Access denied - redirecting to admin/index.php');
	// admin/index.php로 리다이렉트
	if (defined('SITE_URL')) {
		$redirectUrl = rtrim(SITE_URL, '/') . '/admin/index.php';
	} else {
		$redirectUrl = dirname($_SERVER['SCRIPT_NAME']) . '/index.php';
	}
	error_log('[ensure_admin] Redirect URL: ' . $redirectUrl);
	header('Location: ' . $redirectUrl);
	exit;
}
```

### 수정 후 (admin/guard.php 라인 30-48)
```php
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
```

### 변경 사항
1. **리다이렉트 목적지 변경**: `/admin/index.php` → `/admin/login.php`
2. **Return URL 지원 추가**: `?next=/admin/dashboard.php` 형태로 원래 요청한 페이지로 돌아갈 수 있음
3. **로깅 강화**: Return URL도 로그에 기록

---

## 4. 새로운 파일 생성

### admin/login.php
- `admin/index.php`를 기반으로 생성
- Return URL 지원 (`?next=` 파라미터)
- 로그인 성공 시 return URL로 리다이렉트 (보안 검증 포함)
- `ensure_admin()`이 체크하는 모든 세션키 설정

---

## 성공 기준

✅ **관리자 로그인 상태**: `/admin/dashboard.php` 접근 시 대시보드 정상 렌더링
✅ **비로그인/비관리자**: `/admin/dashboard.php` 접근 시 `/admin/login.php?next=/admin/dashboard.php`로 리다이렉트
✅ **로그인 후**: return URL(`next`)이 있으면 해당 페이지로, 없으면 `dashboard.php`로 이동

---

## 테스트 방법

1. **비로그인 상태에서 `/admin/dashboard.php` 접근**
   - 예상: `/admin/login.php?next=/admin/dashboard.php`로 리다이렉트

2. **로그인 성공 후**
   - 예상: `/admin/dashboard.php`로 자동 리다이렉트 (return URL 사용)

3. **로그인 상태에서 `/admin/dashboard.php` 접근**
   - 예상: 대시보드 정상 표시

4. **서버 error_log 확인**
   - `[Admin Login] 로그인 성공` 메시지
   - `[Admin Login] 세션 설정 - admin_logged_in: true`
   - `[Admin Login] 세션 설정 - role: admin`
