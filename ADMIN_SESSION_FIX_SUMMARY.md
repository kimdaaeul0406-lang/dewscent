# 관리자 세션 저장 문제 수정 요약

## 문제 원인

DB(users 테이블)에는 관리자 계정이 존재하지만, 로그인 성공 시 PHP 세션에 관리자 판정용 값(`is_admin`, `role`, `user_id`)이 저장되지 않아 `ensure_admin()`이 항상 false로 판단.

## 수정 내용

### 1. 로그인 성공 시 세션 저장 코드 수정

#### 수정 전

```php
$_SESSION['admin_logged_in'] = true;
$_SESSION['role'] = 'admin';  // 하드코딩
$_SESSION['is_admin'] = 1;  // 하드코딩
```

#### 수정 후

```php
// DB 값을 그대로 세션에 매핑 (필수)
$_SESSION['user_id'] = (int)$user['id'];  // DB의 id
$_SESSION['role'] = !empty($user['is_admin']) ? 'admin' : 'user';  // DB의 is_admin을 기반으로 role 설정
$_SESSION['is_admin'] = !empty($user['is_admin']) ? 1 : 0;  // DB의 is_admin 값 그대로
```

**수정된 파일:**

- `admin/index.php` (라인 45-66)
- `admin/login.php` (라인 47-68)
- `api/login.php` (라인 46-61)

---

### 2. ensure_admin() 수정 - 세션만으로 판단

#### 수정 전

```php
// 두 가지 방식 모두 지원
$isAdmin = !empty($_SESSION['admin_logged_in']) ||
           (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
```

#### 수정 후

```php
// 세션 값만으로 관리자 여부 판단 (DB 재조회 안 함)
// DB의 is_admin 값을 그대로 세션에 저장하므로, 세션의 is_admin로 판단
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
```

**수정된 파일:**

- `admin/guard.php` (라인 6-31)

**함수별 수정:**

- `ensure_admin()`: 세션의 `is_admin`만 체크
- `ensure_admin_api()`: 세션의 `is_admin`만 체크
- `is_admin()`: 세션의 `is_admin`만 체크

---

### 3. 임시 디버깅 출력 추가

로그인 성공 직후 세션 내용을 확인할 수 있도록 임시 출력 추가:

```php
// 임시 디버깅 출력 (확인 후 제거)
if (defined('APP_DEBUG') && APP_DEBUG) {
    echo "<pre>DEBUG - 세션 내용:\n";
    var_dump($_SESSION);
    echo "</pre>";
    exit; // 임시로 여기서 종료하여 확인
}
```

**위치:**

- `admin/index.php` (라인 58-63)
- `admin/login.php` (라인 60-65)

**확인 방법:**

1. `.env` 파일에 `APP_ENV=development` 설정 (또는 `config.php`에서 `APP_DEBUG = true`)
2. 관리자 로그인 시도
3. `var_dump($_SESSION)` 출력 확인
4. `is_admin=1`이 들어가는지 확인
5. 확인 후 해당 코드 블록 제거

---

## 저장되는 세션 키

### 필수 세션키 (DB 값 매핑)

- `$_SESSION['user_id']` = DB의 `id`
- `$_SESSION['role']` = DB의 `is_admin` 기반 (`'admin'` 또는 `'user'`)
- `$_SESSION['is_admin']` = DB의 `is_admin` (1 또는 0)

### 호환성을 위한 추가 세션키

- `$_SESSION['admin_logged_in']` = true
- `$_SESSION['admin_email']` = DB의 `email`
- `$_SESSION['admin_name']` = DB의 `name`
- `$_SESSION['admin_id']` = DB의 `id`
- `$_SESSION['email']` = DB의 `email`
- `$_SESSION['username']` = DB의 `name`
- `$_SESSION['name']` = DB의 `name`

---

## 성공 기준

✅ **관리자 로그인 후:**

- `var_dump($_SESSION)`에서 `is_admin=1` 확인
- error_log에 `[Admin Login] 세션 설정 - is_admin: 1` 확인

✅ **/admin/dashboard.php 접속 시:**

- 로그인 페이지로 튕기지 않고 대시보드가 표시됨
- error_log에 `[ensure_admin] 관리자 권한 확인 완료` 확인

---

## 확인 방법

### 1. 로그인 시 세션 확인

- `.env` 파일에 `APP_ENV=development` 설정
- 관리자 로그인 시도
- 화면에 출력된 `var_dump($_SESSION)`에서 확인:
  ```
  ["user_id"] => int(1)
  ["role"] => string(5) "admin"
  ["is_admin"] => int(1)
  ```

### 2. 서버 error_log 확인

```
[Admin Login] 로그인 성공 - user_id: 1, email: admin@example.com
[Admin Login] DB 값 - is_admin: 1
[Admin Login] 세션 설정 - user_id: 1
[Admin Login] 세션 설정 - role: admin
[Admin Login] 세션 설정 - is_admin: 1
```

### 3. 대시보드 접근 확인

- 로그인 후 `/admin/dashboard.php` 접속
- error_log에서:
  ```
  [ensure_admin] user_id: 1
  [ensure_admin] role: admin
  [ensure_admin] is_admin: 1
  [ensure_admin] isAdmin 계산 결과: true
  [ensure_admin] 관리자 권한 확인 완료
  ```

---

## 주의사항

1. **임시 디버깅 코드 제거**: 확인 완료 후 `var_dump($_SESSION)` 블록 제거
2. **APP_DEBUG 설정**: 개발 환경에서만 디버깅 출력이 표시되도록 설정
3. **세션 유지**: 세션이 서버에서 제대로 유지되는지 확인 (쿠키 설정, 세션 경로 등)

---

## 테스트 시나리오

1. ✅ 관리자 계정으로 로그인
2. ✅ 로그인 성공 시 `var_dump($_SESSION)` 확인 (is_admin=1)
3. ✅ `/admin/dashboard.php` 접속
4. ✅ 대시보드가 정상 표시되는지 확인
5. ✅ error_log에서 권한 체크 성공 확인
6. ✅ 임시 디버깅 코드 제거
