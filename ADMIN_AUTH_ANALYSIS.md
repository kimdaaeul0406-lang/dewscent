# 관리자 인증 분석 및 수정 계획

## 1. ensure_admin()이 체크하는 세션키/조건

### 현재 체크 조건 (admin/guard.php 라인 14-15)
```php
$isAdmin = !empty($_SESSION['admin_logged_in']) || 
           (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
```

**정확히 체크하는 세션키:**
1. `$_SESSION['admin_logged_in']` - boolean 또는 truthy 값이어야 함
2. 또는 `$_SESSION['role'] === 'admin'` - 문자열 'admin'이어야 함

**두 조건 중 하나만 만족하면 관리자로 인식됩니다.**

---

## 2. 로그인 성공 시 세션 설정 코드

### 현재 로그인 처리 파일

#### A. admin/index.php (관리자 전용 로그인, 라인 45-58)
```php
// 로그인 성공 시
$_SESSION['admin_logged_in'] = true;        // ✅ 설정됨
$_SESSION['admin_email'] = $user['email'];  // ✅ 설정됨
$_SESSION['admin_name'] = $user['name'];    // ✅ 설정됨
$_SESSION['admin_id'] = $user['id'];        // ✅ 설정됨
$_SESSION['role'] = 'admin';                // ✅ 설정됨
$_SESSION['user_id'] = $user['id'];         // ✅ 설정됨
$_SESSION['is_admin'] = 1;                  // ✅ 설정됨 (추가)
```

#### B. api/login.php (일반 로그인, 관리자도 포함, 라인 46-58)
```php
// 일반 로그인 시
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['name'] ?? '';
$_SESSION['email'] = $user['email'] ?? '';
$_SESSION['role'] = !empty($user['is_admin']) ? 'admin' : 'user';  // ✅ 설정됨

// 관리자인 경우 추가 설정
if (!empty($user['is_admin'])) {
    $_SESSION['admin_logged_in'] = true;    // ✅ 설정됨
    $_SESSION['admin_email'] = $user['email'] ?? '';
    $_SESSION['admin_name'] = $user['name'] ?? 'Admin';
    $_SESSION['admin_id'] = (int) $user['id'];
}
```

### 문제점
- `admin/index.php`는 이미 올바르게 세션을 설정하고 있음
- 하지만 세션이 서버에서 유지되지 않을 수 있음 (쿠키 문제, 세션 경로 문제 등)
- `ensure_admin()`에서 리다이렉트 시 return URL을 지원하지 않음

---

## 3. 수정 계획

### 수정 1: ensure_admin() 리다이렉트 개선
- 리다이렉트 목적지: `/admin/login.php`로 변경
- return URL 지원: `?next=/admin/dashboard.php` 형태로 지원

### 수정 2: admin/login.php 생성
- `admin/index.php`를 복사하여 `admin/login.php` 생성
- 로그인 성공 시 `next` 파라미터가 있으면 해당 URL로, 없으면 `dashboard.php`로 이동

### 수정 3: 로그인 처리 확인 및 강화
- 세션 설정이 확실히 이루어지도록 확인
- 에러 로깅 강화
