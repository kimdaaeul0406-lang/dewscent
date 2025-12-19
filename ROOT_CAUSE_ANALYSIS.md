# 원인 분석 보고서 (수정 없음)

## 1. /admin/dashboard.php 실행 흐름 분석

### 실제 실행 흐름

```
사용자 요청: GET /admin/dashboard.php
  ↓
admin/dashboard.php (라인 1-9)
  ├─ require_once '../includes/config.php' (라인 8)
  │   └─ config.php에서 session_start() 실행 (라인 37)
  │   └─ 에러 리포팅 설정
  ├─ require_once 'guard.php' (라인 9)
  │   └─ guard.php에서 함수만 정의 (실행 안 됨)
  └─ ensure_admin() 호출 (라인 26)
      └─ guard.php의 ensure_admin() 함수 실행
          ├─ 세션 상태 확인
          ├─ $isAdmin 계산
          └─ if (!$isAdmin) → header('Location: ...') + exit (라인 40-41)
              └─ **여기서 종료되면 dashboard.php의 나머지 코드는 실행 안 됨**
```

### 문제 원인 추정

#### 가능성 1: guard.php에서 리다이렉트되어 종료

- **위치**: `admin/guard.php` 라인 30-41
- **조건**: `$isAdmin === false`일 때
- **동작**: `header('Location: ...')` + `exit` 실행
- **결과**: dashboard.php의 라인 28 이후 코드는 실행되지 않음
- **확인 방법**: dashboard.php 상단에 `echo "DASHBOARD FILE LOADED"; exit;`를 추가했을 때
  - ✅ 출력되면: dashboard.php는 실행되지만, ensure_admin()에서 종료됨
  - ❌ 출력 안 되면: 다른 파일이 먼저 리다이렉트하고 있음

#### 가능성 2: 다른 파일에서 먼저 리다이렉트

- `index.php`에서 admin 경로 감지 시 리다이렉트하는 로직이 있는지 확인 필요
- 하지만 현재 코드에서는 `index.php`가 직접 `/admin/dashboard.php`를 처리하지 않음

#### 가능성 3: 서버 설정 문제

- `.htaccess`에서 `/admin/*` 경로를 `index.php`로 리다이렉트하는 규칙이 있을 수 있음
- Apache mod_rewrite 규칙 확인 필요

### 확인해야 할 파일

1. **admin/dashboard.php** (라인 26): `ensure_admin()` 호출 전후에 echo 추가하여 실행 흐름 확인
2. **admin/guard.php** (라인 30-41): 리다이렉트 조건 및 URL 확인
3. **.htaccess**: mod_rewrite 규칙 확인
4. **includes/config.php**: 세션 시작 및 리다이렉트 로직 확인

---

## 2. /pages/products.php 상품 데이터 소스 분석

### 실제 데이터 흐름

```
브라우저 요청: GET /pages/products.php
  ↓
서버: pages/products.php (PHP 파일)
  ├─ HTML 렌더링 (상품 목록은 빈 상태)
  ├─ <script src="../public/js/api.js?v=4"></script> 로드
  ├─ <script src="../public/js/main.js?v=4"></script> 로드
  └─ <script> ... </script> (인라인 스크립트, 라인 76-456)
      └─ DOMContentLoaded 이벤트 리스너 (라인 443)
          └─ await loadProducts() (라인 450)
              └─ main.js의 loadProducts() 함수 (라인 47-103)
                  ├─ if (typeof API !== "undefined" && API.getPublicProducts) (라인 49)
                  │   └─ API.getPublicProducts(loadOptions) 호출 (라인 60)
                  │       └─ api.js의 getPublicProducts() (라인 742)
                  │           ├─ if (USE_MOCK_API) → localStorage/mock 사용 (라인 743-747)
                  │           └─ else → getJSON('/products.php') 호출 (라인 752)
                  │               └─ 실제 API 요청: GET /api/products.php
                  │                   └─ api/products.php (서버)
                  │                       ├─ require_once '../includes/db.php' (라인 27)
                  │                       ├─ db()->fetchAll() 실행 (라인 156)
                  │                       │   └─ SQL: SELECT ... FROM products WHERE status = '판매중'
                  │                       └─ echo json_encode($products) (라인 220)
                  └─ else → getDefaultProducts() (fallback, 라인 65)
                      └─ 하드코딩된 기본 상품 데이터 반환
```

### 데이터 소스 설계

#### 설계 의도 (코드 기준)

1. **우선순위 1**: API 엔드포인트 (`/api/products.php`)

   - `USE_MOCK_API = false` (api.js 라인 2)
   - DB에서 SELECT 쿼리 실행
   - `status = '판매중'` 필터링

2. **Fallback**: 하드코딩된 기본 데이터

   - API 호출 실패 시 `getDefaultProducts()` 사용
   - `main.js` 라인 108-173에 정의된 기본 상품 배열

3. **localStorage 사용 안 함** (현재 설정)
   - `USE_MOCK_API = false`이므로 localStorage는 사용되지 않음
   - `getStoredProducts()` 함수는 정의되어 있지만 호출되지 않음

### 배포 서버에서 실제 발생하는 상황

#### 시나리오 A: API 호출 성공, DB에 데이터 없음

```
1. GET /api/products.php 요청
2. SQL 실행: SELECT ... WHERE status = '판매중'
3. 결과: 빈 배열 [] 반환
4. 프론트엔드: products = []
5. 화면 표시: "총 0개"
```

**원인**: DB의 `products` 테이블에 `status = '판매중'`인 레코드가 없음

#### 시나리오 B: API 호출 실패

```
1. GET /api/products.php 요청
2. 네트워크 에러 또는 서버 에러 (500, timeout 등)
3. catch 블록 실행: products = getDefaultProducts()
4. 화면 표시: "총 N개" (기본 데이터)
```

**원인**: API 엔드포인트 접근 불가 또는 서버 에러

#### 시나리오 C: API 호출 성공, 응답이 배열이 아님

```
1. GET /api/products.php 요청
2. 응답: {"error": "..."} 또는 {"ok": false, ...}
3. main.js 라인 70-73에서 에러 처리
4. catch 블록: products = getDefaultProducts()
```

**원인**: API가 에러 응답 반환

### 문제 원인 추정

#### 가장 가능성 높은 원인: **DB에 데이터가 없음**

- `api/products.php`는 정상적으로 실행되고 있음
- SQL 쿼리는 실행되지만 결과가 빈 배열
- `WHERE status = '판매중'` 조건에 맞는 레코드가 없음

#### 확인해야 할 것

1. **DB 확인**: `products` 테이블에 데이터가 있는지

   ```sql
   SELECT COUNT(*) FROM products WHERE status = '판매중';
   ```

2. **status 값 확인**: 실제 DB의 status 컬럼 값이 '판매중'인지

   ```sql
   SELECT DISTINCT status FROM products;
   ```

3. **API 응답 확인**: 브라우저 Network 탭에서 `/api/products.php` 응답 확인

   - 상태 코드: 200 (성공) 또는 500 (에러)
   - 응답 본문: `[]` (빈 배열) 또는 에러 메시지

4. **error_log 확인**: 서버 로그에서 다음 메시지 확인
   ```
   [Products API] Main query time: Xms, Products count: 0
   ```

### 확인해야 할 파일

1. **api/products.php** (라인 127-140): SQL 쿼리 및 WHERE 조건
2. **public/js/main.js** (라인 47-103): loadProducts() 함수 및 에러 처리
3. **public/js/api.js** (라인 2, 742-752): USE_MOCK_API 설정 및 getPublicProducts()
4. **DB 테이블**: `products` 테이블의 데이터 및 status 값

---

## 수정이 필요한 파일 목록

### 문제 1: /admin/dashboard.php 리다이렉트

#### 확인 필요 (수정 전)

1. **admin/dashboard.php** (라인 1, 26):

   - 상단에 `echo "DASHBOARD FILE LOADED"; exit;` 추가하여 파일 실행 여부 확인
   - `ensure_admin()` 호출 전후에 로그 추가

2. **admin/guard.php** (라인 30-41):

   - 리다이렉트 URL이 올바른지 확인
   - `$isAdmin` 계산 로직 점검

3. **.htaccess**:
   - `/admin/*` 경로에 대한 rewrite 규칙 확인

#### 수정 필요 (확인 후)

- **admin/guard.php**: 리다이렉트 로직 수정 (관리자가 아닐 때만 리다이렉트)
- **admin/index.php**: GET 요청 시에도 로그인 폼 표시 (이미 수정됨)

### 문제 2: /pages/products.php 상품 0개

#### 확인 필요 (수정 전)

1. **DB 확인**:

   - `products` 테이블에 데이터 존재 여부
   - `status` 컬럼 값 확인

2. **브라우저 Network 탭**:

   - `/api/products.php` 요청 상태 코드 및 응답 본문

3. **서버 error_log**:
   - `[Products API]` 로그 메시지 확인

#### 수정 필요 (확인 후)

- **api/products.php**:
  - 에러 처리 강화 (이미 수정됨)
  - DB 연결 실패 시 명확한 에러 메시지 반환
- **public/js/main.js**:
  - 에러 처리 개선 (이미 수정됨)
  - 빈 배열과 에러를 구분하여 처리
- **pages/products.php**:
  - 에러 메시지 표시 (이미 수정됨)

---

## 요약

### 문제 1 원인

**dashboard.php가 실행되지만, `guard.php`의 `ensure_admin()` 함수에서 관리자 권한 체크 실패 시 리다이렉트되어 `exit`로 종료됨.**

- dashboard.php의 라인 1-26까지는 실행됨
- 라인 26의 `ensure_admin()` 호출 시 권한이 없으면 라인 40-41에서 `header() + exit`로 종료
- 따라서 라인 28 이후의 HTML 렌더링 코드는 실행되지 않음

### 문제 2 원인

**배포 서버에서 `/api/products.php`는 정상 실행되지만, DB의 `products` 테이블에 `status = '판매중'`인 레코드가 없어서 빈 배열이 반환됨.**

- 데이터 소스는 **DB (api/products.php의 SELECT 쿼리)**가 맞음
- localStorage/mock 데이터는 `USE_MOCK_API = false`이므로 사용되지 않음
- API 호출은 성공하지만 결과가 빈 배열 `[]`이므로 화면에 "총 0개"로 표시됨
