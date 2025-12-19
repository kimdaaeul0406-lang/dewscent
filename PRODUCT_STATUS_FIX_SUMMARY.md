# 상품 0개 문제 수정 요약

## 1. DB 확인 쿼리 (배포 서버에서 직접 실행)

배포 DB에서 다음 쿼리를 실행하여 결과를 확인해주세요:

### 쿼리 1: 전체 상품 수
```sql
SELECT COUNT(*) AS cnt FROM products;
```

### 쿼리 2: Status별 분포
```sql
SELECT status, COUNT(*) c FROM products GROUP BY status;
```

**또는 간단한 확인 스크립트 사용:**
- `check_products_db.php` 파일을 배포 서버에 업로드
- 브라우저에서 `/check_products_db.php` 접근
- 결과 확인 후 파일 삭제

---

## 2. admin 상품 등록 INSERT 코드 위치

### 위치
**파일:** `api/products.php`  
**라인:** 297-315 (POST 메서드, 상품 등록)

### 현재 코드 (수정 후)
```php
// status 값 처리: 기본값 '판매중' 보장
$status = !empty($data['status']) ? trim($data['status']) : '판매중';
// 유효한 status 값 검증
$validStatuses = ['판매중', '품절', '숨김'];
if (!in_array($status, $validStatuses)) {
    $status = '판매중'; // 기본값으로 강제 설정
}

$sql = "INSERT INTO products (..., status, ...) VALUES (?, ..., ?, ...)";
$params = [
    // ... 기타 필드들
    $status, // 검증된 status 값
    // ...
];

// INSERT 실행 및 로깅
$newId = db()->insert($sql, $params);
error_log('[Products API] 상품 INSERT 성공 - id: ' . $newId . ', name: ' . $data['name']);
error_log('[Products API] 등록된 상품 확인 - id: ' . $newProduct['id'] . ', status: ' . $newProduct['status']);
```

### 저장되는 status 값
- **기본값**: `'판매중'` (status가 제공되지 않거나 유효하지 않은 경우)
- **폼에서 전달된 값**: `document.getElementById('prodStatus').value` (admin/dashboard.php 라인 1583)
- **폼 기본값**: `'판매중'` (admin/dashboard.php 라인 1453)

---

## 3. SELECT 조건 통일

### 현재 상태

#### INSERT 시 (api/products.php 라인 310, 수정 후)
```php
$status = !empty($data['status']) ? trim($data['status']) : '판매중';
// 유효성 검증 후 기본값 '판매중' 강제
```

#### SELECT 시 (api/products.php 라인 139)
```php
$query .= " WHERE status = '판매중'";
```

**✅ 통일 완료:** INSERT 기본값과 SELECT 조건이 모두 `'판매중'`으로 일치합니다.

---

## 4. API 응답이 빈 배열일 때 원인 파악을 위한 로깅

### 추가된 로깅 (api/products.php GET 요청)

#### 수정 전
```php
$products = db()->fetchAll($query, $params);
error_log('[Products API] Main query time: Xms, Products count: X');
```

#### 수정 후
```php
// DB 연결 확인 및 전체 상품 수 확인
try {
    $totalCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM products");
    error_log('[Products API] DB 연결 성공 - 전체 상품 수: ' . $totalCount['cnt']);
    
    // status별 분포 확인
    $statusDistribution = db()->fetchAll("SELECT status, COUNT(*) as c FROM products GROUP BY status");
    error_log('[Products API] Status 분포: 판매중:X, 품절:Y, ...');
    
    // '판매중' 상품 수 확인
    $sellingCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM products WHERE status = '판매중'");
    error_log('[Products API] 판매중 상품 수: ' . $sellingCount['cnt']);
} catch (Exception $e) {
    error_log('[Products API] DB 연결/통계 확인 실패: ' . $e->getMessage());
}

// 쿼리 실행 및 상세 로깅
$products = db()->fetchAll($query, $params);
error_log('[Products API] Main query time: Xms, Products count: X');

// 결과가 빈 배열일 때 상세 로그
if (count($products) === 0) {
    error_log('[Products API] 경고: 조회 결과가 0개입니다.');
    error_log('[Products API] - 전체 상품 수: X');
    error_log('[Products API] - 판매중 상품 수: Y');
    error_log('[Products API] - 쿼리 조건: status = "판매중"');
}
```

### INSERT 시 로깅 (api/products.php POST 요청)

```php
error_log('[Products API] 상품 등록 시작 - name: X, status: Y');
// ... INSERT 실행
error_log('[Products API] 상품 INSERT 성공 - id: X, name: Y');
// ... 상품 조회 후
error_log('[Products API] 등록된 상품 확인 - id: X, name: Y, status: Z');
```

---

## 5. 해결 방법 요약

### 상황별 대응

#### 상황 A: DB에 상품이 없음 (totalCount = 0)
**원인:** 상품을 아직 등록하지 않음  
**해결:**
1. 관리자 대시보드에서 상품 등록
2. 등록 후 error_log 확인: `[Products API] 상품 INSERT 성공`
3. `/check_products_db.php` 또는 직접 SQL로 확인

#### 상황 B: 상품은 있지만 status가 '판매중'이 아님
**원인:** INSERT 시 다른 status 값이 저장됨  
**해결:**
- **옵션 1 (권장):** 기존 상품의 status를 '판매중'으로 변경
  ```sql
  UPDATE products SET status = '판매중' WHERE status IS NULL OR status = '';
  ```
- **옵션 2:** API의 SELECT 조건을 실제 status 값에 맞게 변경 (비권장)

#### 상황 C: INSERT는 성공했지만 SELECT에서 안 나옴
**원인:** 
- 세션/트랜잭션 문제
- 다른 DB에 INSERT되거나 조회되는 경우
- status 값이 예상과 다름

**해결:**
1. error_log에서 INSERT 성공 로그 확인
2. error_log에서 등록된 상품의 status 확인
3. `check_products_db.php`로 실제 DB 상태 확인

---

## 6. 확인 방법

### 1. 배포 서버 error_log 확인
다음 로그 메시지들을 확인:
```
[Products API] DB 연결 성공 - 전체 상품 수: X
[Products API] Status 분포: 판매중:X, 품절:Y, ...
[Products API] 판매중 상품 수: X
[Products API] Main query time: Xms, Products count: Y
```

상품 등록 시:
```
[Products API] 상품 등록 시작 - name: X, status: 판매중
[Products API] 상품 INSERT 성공 - id: X, name: Y
[Products API] 등록된 상품 확인 - id: X, status: 판매중
```

### 2. 브라우저 Network 탭 확인
- `/api/products.php` 요청의 상태 코드 (200이어야 함)
- 응답 본문: `[]` (빈 배열) 또는 상품 데이터

### 3. DB 직접 확인
- `check_products_db.php` 사용
- 또는 직접 SQL 실행

---

## 성공 기준

✅ **관리자에서 상품 등록 시:**
- error_log에 `[Products API] 상품 INSERT 성공` 로그 확인
- error_log에 `[Products API] 등록된 상품 확인 - status: 판매중` 로그 확인

✅ **상품 목록 조회 시:**
- error_log에 `[Products API] 판매중 상품 수: X` (X > 0) 로그 확인
- error_log에 `[Products API] Main query time: Xms, Products count: Y` (Y > 0) 로그 확인
- `/pages/products.php`에서 "총 n개 (n>0)" 표시

✅ **DB 확인:**
- `SELECT COUNT(*) FROM products WHERE status = '판매중'` 결과 > 0
