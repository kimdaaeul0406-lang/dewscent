# 수정 사항 요약

## A. /admin/dashboard.php 리다이렉트 문제 수정

### 문제

- 관리자가 아닌 사용자가 `/admin/dashboard.php`에 접근하면 `guard.php`에서 `/admin/index.php`로 리다이렉트
- `admin/index.php`가 GET 요청으로 접근하면 무조건 `../index.php` (메인 페이지)로 리다이렉트하여 로그인 폼이 표시되지 않음

### 해결 방법

#### 1. `admin/index.php` 수정

- GET 요청 시에도 로그인 폼을 표시하도록 변경
- 이전: GET 요청 시 `../index.php`로 리다이렉트
- 이후: GET 요청 시에도 로그인 폼 표시 (POST만 처리)

#### 2. `admin/guard.php` 수정

- `SITE_URL`이 정의되어 있으면 절대 경로 사용하도록 개선
- 리다이렉트 URL이 정확하게 `/admin/index.php`를 가리키도록 보장

### 결과

- 관리자가 아닌 사용자: `/admin/index.php` (로그인 페이지)에 머무름
- 관리자 로그인 상태: `/admin/dashboard.php` 정상 표시

---

## B. /pages/products.php 상품 로딩 문제 수정

### 문제

- 상품 목록이 "총 0개"로 표시됨
- DB 연결 실패나 쿼리 실패 시 조용히 빈 배열 반환하여 원인 파악 어려움

### 해결 방법

#### 1. `api/products.php` 에러 처리 강화

- **메인 쿼리 에러 처리**: `db()->fetchAll()` 실행 시 try-catch 추가
  - 에러 발생 시 HTTP 500과 상세 에러 메시지 반환
  - error_log에 쿼리, 파라미터, 에러 메시지, 스택 트레이스 기록
- **Variants 쿼리 에러 처리**: variants 조회 실패 시에도 상품 목록은 반환 (variants는 빈 배열)
  - 에러 로그 기록
- **전체 로깅 강화**: 쿼리, 파라미터, 실행 시간, 결과 개수 모두 로깅

#### 2. `public/js/api.js` 에러 처리 개선

- **getJSON 함수**: HTTP 에러 상태 코드(4xx, 5xx) 처리 강화
  - 응답 본문의 에러 메시지 추출
  - 에러 객체에 status와 data 포함
  - 명확한 에러 메시지 throw

#### 3. `public/js/main.js` loadProducts 함수 개선

- **에러 처리 강화**:
  - API 응답이 에러 객체인지 확인 (`ok === false`)
  - 배열이 아닌 응답 처리
  - 상품이 0개인 경우 경고 로그 출력
  - 에러 발생 시 `window.productLoadError`에 저장하여 화면에 표시 가능하도록

#### 4. `pages/products.php` 에러 표시 추가

- 상품 로드 실패 시 화면에 에러 메시지 표시
- 에러 발생 시 "상품을 불러오는 중 오류가 발생했습니다" 메시지와 함께 에러 상세 정보 표시
- 사용자 친화적인 안내 메시지 제공

### 결과

- DB 연결/쿼리 실패 시 error_log에 상세 정보 기록
- 프론트엔드에서 에러 발생 시 화면에 명확한 에러 메시지 표시
- 상품이 0개인 경우와 에러가 발생한 경우를 구분하여 처리

---

## 확인 방법

### 1. 관리자 대시보드 접근

1. 비로그인 상태에서 `/admin/dashboard.php` 접근 → `/admin/index.php` (로그인 페이지)로 리다이렉트
2. 로그인 후 `/admin/dashboard.php` 접근 → 대시보드 정상 표시

### 2. 상품 목록 페이지

1. `/pages/products.php` 접근
2. 브라우저 개발자 도구 Console 탭에서 로그 확인:
   - `[loadProducts] X개 상품 로드 완료` 메시지 확인
   - 에러 발생 시 상세 에러 메시지 확인
3. Network 탭에서 `/api/products.php` 요청 확인:
   - 상태 코드: 200 (성공) 또는 500 (서버 에러)
   - 응답 본문 확인
4. 서버 error_log 확인:
   - `[Products API] Main query time: Xms, Products count: X`
   - 에러 발생 시 `[Products API] DB query error: ...` 확인

---

## 성공 기준

- ✅ `/admin/dashboard.php`에서 관리자 로그인 시 대시보드가 정상 표시됨
- ✅ `/pages/products.php`에서 DB에 있는 상품이 목록으로 표시되고 "총 n개"가 n>0으로 나옴
- ✅ 에러 발생 시 화면 또는 error_log에 명확한 오류 메시지 출력
