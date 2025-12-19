# 상품 stock 필터링 문제 수정 요약

## 문제 원인
products 테이블에 데이터가 있고 `status='판매중'`인 상품도 존재하지만, 모든 상품의 `stock` 값이 0이어서 만약 쿼리에 `AND stock > 0` 같은 조건이 있다면 모든 행이 필터링되어 결과가 0개가 됨.

## 1. api/products.php의 실제 SQL WHERE 조건 확인

### 현재 WHERE 조건 (GET 요청, 라인 127-142)

```sql
SELECT ... FROM products
WHERE status = '판매중'
ORDER BY id DESC
```

**확인 결과:**
- ✅ **status 조건만 존재**: `WHERE status = '판매중'`
- ✅ **stock 조건 없음**: `AND stock > 0` 같은 조건이 없음
- ✅ **type/category 조건 없음**: 추가 필터링 없음

**결론:** 현재 코드에서는 stock 조건이 없으므로 stock=0인 상품도 조회되어야 함.

---

## 2. stock 조건 제거/완화

### 현재 상태
- WHERE 조건에 stock 관련 조건이 **없음**
- 따라서 stock=0인 상품도 정상적으로 조회됨

### 수정 내용 (예방적 조치)
- 주석 추가: stock 조건이 없음을 명시
- 로깅 강화: stock 분포 확인 추가

---

## 3. 관리자 등록 시 기본 stock 값 설정

### 수정 내용

#### A. 상품 등록 시 (POST, 라인 297-353)

**수정 전:**
```php
$sql = "INSERT INTO products (..., status, ...) VALUES (?, ..., ?, ...)";
// stock 컬럼이 없음
```

**수정 후:**
```php
// stock 값 처리: 기본값 10 (테스트/전시 목적)
$stock = isset($data['stock']) ? (int)$data['stock'] : 10;
if ($stock < 0) {
    $stock = 10; // 음수면 기본값으로 설정
}

$sql = "INSERT INTO products (..., stock, status, ...) VALUES (?, ..., ?, ?, ...)";
$params = [
    // ...
    $stock, // stock 값 추가 (기본값 10)
    $status,
    // ...
];
```

#### B. 상품 수정 시 (PUT, 라인 401-490)

**수정 전:**
```php
$sql = "UPDATE products SET ... status = ?, ... WHERE id = ?";
// stock 컬럼이 없음
```

**수정 후:**
```php
// stock 값 처리 (수정 시에는 기존 값 유지, 명시적으로 전달되면 업데이트)
$stock = isset($data['stock']) ? (int)$data['stock'] : ($existing['stock'] ?? 10);
if ($stock < 0) {
    $stock = $existing['stock'] ?? 10; // 음수면 기존 값 또는 기본값 사용
}

$sql = "UPDATE products SET ... stock = ?, status = ?, ... WHERE id = ?";
$params = [
    // ...
    $stock, // stock 값 추가
    $status,
    // ...
];
```

---

## 4. 로깅 강화

### 추가된 로깅

#### 상품 등록 시
```php
error_log('[Products API] 상품 등록 시작 - name: ' . $data['name'] . ', status: ' . $status . ', stock: ' . $stock);
```

#### 조회 결과가 0개일 때 stock 분포 확인
```php
// stock 분포도 확인 (디버깅)
$stockDistribution = db()->fetchAll("SELECT CASE WHEN stock = 0 THEN 'stock=0' WHEN stock > 0 THEN 'stock>0' ELSE 'stock<0' END as stock_group, COUNT(*) as c FROM products WHERE status = '판매중' GROUP BY stock_group");
error_log('[Products API] 판매중 상품 stock 분포: stock=0:X, stock>0:Y');
```

---

## 성공 기준

✅ **/api/products.php 직접 접근:**
- 응답이 `[]`가 아닌 배열
- 상품 데이터가 포함된 JSON 배열 반환

✅ **/pages/products.php 접근:**
- "총 n개"가 n>0으로 표시됨
- 상품 목록이 정상적으로 렌더링됨

✅ **관리자에서 상품 등록:**
- 새로 등록된 상품의 stock 값이 10 이상 (기본값)
- error_log에 `[Products API] 상품 등록 시작 - stock: 10` 확인

---

## 확인 방법

### 1. /api/products.php 직접 접근
1. 브라우저에서 `/api/products.php` 접근
2. 응답 확인:
   - `[]` (빈 배열) → 문제 있음
   - `[{...}, {...}]` (상품 배열) → 정상

### 2. 서버 error_log 확인
```
[Products API] DB 연결 성공 - 전체 상품 수: X
[Products API] Status 분포: 판매중:X, ...
[Products API] 판매중 상품 수: X
[Products API] Main query time: Xms, Products count: Y
```

조회 결과가 0개일 때:
```
[Products API] 판매중 상품 stock 분포: stock=0:X, stock>0:Y
```

### 3. 관리자에서 상품 등록 테스트
1. 관리자 대시보드에서 상품 등록
2. error_log 확인:
   ```
   [Products API] 상품 등록 시작 - name: X, status: 판매중, stock: 10
   [Products API] 상품 INSERT 성공 - id: X
   [Products API] 등록된 상품 확인 - id: X, stock: 10
   ```

---

## 주의사항

1. **기존 상품의 stock 값**: 기존에 등록된 상품의 stock이 0인 경우, 수정할 때까지 stock=0으로 유지됨
2. **stock 조건 없음**: 현재 쿼리에는 stock 조건이 없으므로 stock=0이어도 조회됨
3. **기본값 10**: 새로 등록하는 상품은 stock=10으로 저장됨 (테스트/전시 목적)

---

## 다음 단계 (선택사항)

### 기존 상품의 stock 값을 일괄 업데이트
필요시 다음 SQL을 실행하여 기존 상품의 stock을 업데이트:

```sql
-- stock이 0이거나 NULL인 판매중 상품의 stock을 10으로 업데이트
UPDATE products 
SET stock = 10 
WHERE status = '판매중' AND (stock IS NULL OR stock = 0);
```
