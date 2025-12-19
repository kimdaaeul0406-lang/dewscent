# 성능 및 세션 문제 해결 요약

## 1. 관리자 대시보드 세션 문제 해결 ✅

### 문제
- 배포 서버에서 `/admin/dashboard.php` 접근 시 메인 페이지로 리다이렉트
- 세션 값이 서버에서 유지되지 않음

### 해결 방법
1. **세션 시작 위치 확인**: `dashboard.php` 최상단에 `session_start()` 명시적 호출
2. **디버깅 로그 추가**: 세션 ID, 세션 데이터, 권한 체크 결과를 error_log에 기록
3. **리다이렉트 경로 수정**: `index.php` → `admin/index.php` (상대 경로)
4. **에러 리포팅 활성화**: `error_reporting(E_ALL)`, `log_errors = 1` (화면에는 표시하지 않음)

### 확인 방법
서버의 PHP error_log 파일에서 다음 로그 확인:
```
[Admin Dashboard] Session ID: ...
[Admin Dashboard] Session Data: {...}
[Admin Dashboard] admin_logged_in: true/false
[ensure_admin] isAdmin: true/false
```

## 2. N+1 쿼리 문제 해결 ✅

### 문제
- 각 상품마다 별도의 variants 조회 쿼리 실행 (N+1 문제)
- 100개 상품이면 101번 쿼리 실행

### 해결 방법
1. **일괄 조회**: 모든 상품의 variants를 한 번에 조회
   ```sql
   SELECT * FROM product_variants WHERE product_id IN (1,2,3,...)
   ```
2. **메모리에서 그룹화**: 조회된 variants를 product_id로 그룹화하여 각 상품에 할당

### 성능 개선
- **이전**: 100개 상품 = 101번 쿼리 (약 20초)
- **이후**: 100개 상품 = 2번 쿼리 (약 2초 이하)

## 3. 쿼리 실행 시간 로깅 추가 ✅

### 추가된 로깅
- 메인 쿼리 실행 시간
- Variants 쿼리 실행 시간
- 총 처리 시간
- 처리된 상품 수, variants 수

### 확인 방법
PHP error_log에서:
```
[Products API] Main query time: 150.25ms, Products count: 100
[Products API] Variants query time: 50.12ms, Variants count: 250
[Products API] Total time: 200.37ms, Products: 100
```

## 4. 페이징 지원 추가 ✅

### API 변경
- `getPublicProducts(options)` 함수에 `limit`, `offset` 파라미터 추가
- SQL 쿼리에 `LIMIT ? OFFSET ?` 추가

### 사용 예시
```javascript
// 처음 20개만 로드
await API.getPublicProducts({ limit: 20, offset: 0 });

// 다음 20개 로드
await API.getPublicProducts({ limit: 20, offset: 20 });
```

## 5. 이미지 Lazy Loading 적용 ✅

### 적용 위치
- `pages/products.php`의 상품 목록 (grid, list view)
- 상품 카드 이미지에 `lazy-image` 클래스 추가
- `data-bg` 속성으로 이미지 URL 저장

### 동작 방식
- `IntersectionObserver`로 뷰포트 50px 전에 미리 로드
- 초기 로딩 시 이미지가 보이지 않는 영역은 로드하지 않음

## 6. 추가 최적화 권장 사항

### 즉시 적용 가능
1. **상품 목록 페이징 구현**: 처음 20개만 로드, 스크롤 시 추가 로드
2. **이미지 압축**: Base64 대신 실제 파일로 저장하고 WebP 포맷 사용
3. **CDN 사용**: 정적 파일(이미지, CSS, JS)을 CDN에서 제공

### 서버 설정
1. **PHP OPcache 활성화**: PHP 스크립트 캐싱
2. **MySQL 쿼리 캐시**: 자주 사용되는 쿼리 결과 캐싱
3. **Gzip 압축**: `.htaccess`에 이미 설정되어 있으나 서버에서 활성화 확인 필요

## 배포 후 확인 사항

1. **세션 확인**: 관리자 로그인 후 dashboard.php 접근 테스트
2. **성능 확인**: Network 탭에서 API 호출 시간 확인 (목표: 2초 이하)
3. **로그 확인**: error_log에서 쿼리 실행 시간 확인
4. **이미지 로딩**: 스크롤 시 이미지가 지연 로드되는지 확인
