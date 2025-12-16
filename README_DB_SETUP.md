# DewScent 데이터베이스 설정 가이드

## 현재 상태 확인

### ✅ DB에 저장되는 기능
1. **회원가입** - `api/signup.php` → `users` 테이블
2. **상품 등록/수정/삭제** - `api/products.php` → `products` 테이블
3. **로그인** - `api/login.php` → 세션 사용
4. **주문** - `api/orders.php` → `orders`, `order_items` 테이블 (구현 필요)

### ✅ 새로 추가된 DB 기능
1. **1:1 문의** - `api/inquiries.php` → `inquiries` 테이블
2. **리뷰** - `api/reviews.php` → `reviews` 테이블
3. **관리자 회원 목록** - `api/admin/users.php` → `users` 테이블 조회

## 데이터베이스 설정

### 1. 테이블 생성
phpMyAdmin에서 다음 SQL 파일을 실행하세요:
```
database/add_tables.sql
```

또는 직접 실행:
```sql
USE dewscent;

-- 문의 테이블
CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    order_no VARCHAR(50) DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'waiting',
    answer TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    answered_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 리뷰 테이블
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 인덱스
CREATE INDEX idx_inquiries_user ON inquiries(user_id);
CREATE INDEX idx_inquiries_status ON inquiries(status);
CREATE INDEX idx_reviews_product ON reviews(product_id);
CREATE INDEX idx_reviews_user ON reviews(user_id);
```

## 기능별 확인

### 1. 1:1 문의
- ✅ DB 저장: `api/inquiries.php` (POST)
- ✅ DB 조회: `api/inquiries.php` (GET)
- ✅ 관리자 답변: `api/inquiries.php` (PUT)

### 2. 리뷰
- ✅ DB 저장: `api/reviews.php` (POST)
- ✅ DB 조회: `api/reviews.php` (GET)
- ✅ DB 삭제: `api/reviews.php` (DELETE)
- ✅ 상품 평점 자동 업데이트

### 3. 관리자 대시보드
- ✅ 회원 목록: `api/admin/users.php` (GET)
- ✅ 문의 관리: DB 연동 완료
- ✅ 상품 관리: DB 연동 완료

## 테스트 방법

1. **회원가입 테스트**
   - 회원가입 후 phpMyAdmin에서 `users` 테이블 확인

2. **상품 등록 테스트**
   - 관리자 대시보드에서 상품 등록 후 `products` 테이블 확인

3. **1:1 문의 테스트**
   - 로그인 후 문의 작성 → `inquiries` 테이블 확인

4. **리뷰 테스트**
   - 로그인 후 리뷰 작성 → `reviews` 테이블 확인
   - 상품의 `rating`, `reviews` 컬럼 자동 업데이트 확인

5. **관리자 대시보드**
   - 회원 목록 탭에서 가입한 회원 확인
   - 문의 관리 탭에서 문의 확인 및 답변

