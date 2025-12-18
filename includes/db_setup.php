<?php
// 데이터베이스 테이블 자동 생성 헬퍼 함수

/**
 * 필요한 모든 테이블이 존재하는지 확인하고 없으면 생성
 */
function ensure_tables_exist() {
    try {
        $conn = db()->getConnection();
        
        // users 테이블
        $conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(50) NOT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                address TEXT DEFAULT NULL,
                is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // name 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'name'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN name VARCHAR(50) NOT NULL DEFAULT '' AFTER id");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // phone 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'phone'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER name");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // address 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'address'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL AFTER phone");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // is_admin 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'is_admin'");
        if (empty($columns)) {
            try {
                // address 컬럼이 있으면 AFTER address, 없으면 마지막에 추가
                $hasAddress = !empty(db()->fetchAll("SHOW COLUMNS FROM users LIKE 'address'"));
                if ($hasAddress) {
                    $conn->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)' AFTER address");
                } else {
                    $conn->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)'");
                }
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }

        // kakao_id 컬럼이 없으면 추가 (소셜 로그인용)
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'kakao_id'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN kakao_id VARCHAR(100) DEFAULT NULL COMMENT '카카오 사용자 ID' AFTER is_admin");
                $conn->exec("CREATE INDEX idx_users_kakao_id ON users(kakao_id)");
            } catch (PDOException $e) {
                // 컬럼이나 인덱스가 이미 존재하거나 다른 오류 (무시)
            }
        }

        // profile_image 컬럼이 없으면 추가 (소셜 로그인 프로필 이미지용)
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'profile_image'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(500) DEFAULT NULL COMMENT '프로필 이미지 URL' AFTER kakao_id");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }

        // naver_id 컬럼이 없으면 추가 (네이버 소셜 로그인용)
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'naver_id'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN naver_id VARCHAR(100) DEFAULT NULL COMMENT '네이버 사용자 ID' AFTER profile_image");
                $conn->exec("CREATE INDEX idx_users_naver_id ON users(naver_id)");
            } catch (PDOException $e) {
                // 컬럼이나 인덱스가 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // updated_at 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'updated_at'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // products 테이블
        $conn->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                type VARCHAR(50) NOT NULL COMMENT '향수, 바디미스트, 디퓨저, 섬유유연제',
                price INT NOT NULL,
                originalPrice INT DEFAULT NULL COMMENT '할인 전 원가 (없으면 NULL)',
                rating DECIMAL(2,1) DEFAULT 0.0,
                reviews INT DEFAULT 0,
                badge VARCHAR(20) DEFAULT NULL COMMENT 'BEST, NEW, SALE 등',
                `desc` TEXT COMMENT '상품 설명',
                image TEXT DEFAULT NULL COMMENT '상품 카드 이미지 경로 (메인페이지 표시용, Base64 지원)',
                detail_image TEXT DEFAULT NULL COMMENT '상품 상세 이미지 경로 (클릭 시 모달 표시용, Base64 지원)',
                stock INT DEFAULT 0 COMMENT '재고 수량',
                status VARCHAR(20) DEFAULT '판매중' COMMENT '판매중, 품절, 숨김',
                fragrance_type VARCHAR(50) DEFAULT NULL COMMENT '향기 타입 (시트러스, 플로럴, 우디 등)',
                emotion_keys TEXT DEFAULT NULL COMMENT '감정 키들 (JSON 배열 형식)',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // fragrance_type 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM products LIKE 'fragrance_type'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE products ADD COLUMN fragrance_type VARCHAR(50) DEFAULT NULL COMMENT '향기 타입 (시트러스, 플로럴, 우디 등)' AFTER status");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // emotion_keys 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM products LIKE 'emotion_keys'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE products ADD COLUMN emotion_keys TEXT DEFAULT NULL COMMENT '감정 키들 (JSON 배열 형식)' AFTER fragrance_type");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // detail_image 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM products LIKE 'detail_image'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE products ADD COLUMN detail_image TEXT DEFAULT NULL COMMENT '상품 상세 이미지 경로 (클릭 시 모달 표시용, Base64 지원)' AFTER image");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // image와 detail_image 컬럼이 VARCHAR(255)이면 TEXT로 변경 (Base64 이미지 지원 - 필수!)
        try {
            $imageColumn = db()->fetchOne("SHOW COLUMNS FROM products WHERE Field = 'image'");
            if ($imageColumn && stripos($imageColumn['Type'], 'varchar') !== false) {
                $conn->exec("ALTER TABLE products MODIFY COLUMN image TEXT DEFAULT NULL COMMENT '상품 카드 이미지 경로 (메인페이지 표시용, Base64 지원)'");
                error_log("DB Migration: image 컬럼을 VARCHAR에서 TEXT로 변경했습니다.");
            }
        } catch (PDOException $e) {
            error_log("DB Migration 오류 (image): " . $e->getMessage());
        }
        
        try {
            $detailImageColumn = db()->fetchOne("SHOW COLUMNS FROM products WHERE Field = 'detail_image'");
            if ($detailImageColumn && stripos($detailImageColumn['Type'], 'varchar') !== false) {
                $conn->exec("ALTER TABLE products MODIFY COLUMN detail_image TEXT DEFAULT NULL COMMENT '상품 상세 이미지 경로 (클릭 시 모달 표시용, Base64 지원)'");
                error_log("DB Migration: detail_image 컬럼을 VARCHAR에서 TEXT로 변경했습니다.");
            }
        } catch (PDOException $e) {
            error_log("DB Migration 오류 (detail_image): " . $e->getMessage());
        }

        // product_variants 테이블 (용량별 가격)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS product_variants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                volume VARCHAR(20) NOT NULL COMMENT '용량 (예: 30ml, 50ml, 100ml)',
                price INT NOT NULL COMMENT '해당 용량 가격',
                stock INT DEFAULT 0 COMMENT '해당 용량 재고',
                is_default TINYINT(1) DEFAULT 0 COMMENT '기본 선택 여부',
                sort_order INT DEFAULT 0 COMMENT '정렬 순서',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                INDEX idx_product_id (product_id),
                UNIQUE KEY unique_product_volume (product_id, volume)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // inquiries 테이블
        $conn->exec("
            CREATE TABLE IF NOT EXISTS inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL COMMENT 'shipping, exchange, product, order, other',
                order_no VARCHAR(50) DEFAULT NULL COMMENT '주문번호 (선택)',
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'waiting' COMMENT 'waiting, answered',
                answer TEXT DEFAULT NULL COMMENT '관리자 답변',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                answered_at TIMESTAMP NULL DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // inquiries 인덱스
        try {
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_inquiries_user ON inquiries(user_id)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_inquiries_status ON inquiries(status)");
        } catch (PDOException $e) {
            // 인덱스가 이미 존재할 수 있음
        }
        
        // reviews 테이블
        $conn->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                rating INT NOT NULL COMMENT '1-5',
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_product (user_id, product_id) COMMENT '한 사용자는 한 상품당 하나의 리뷰만 작성 가능'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // reviews 인덱스
        try {
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_reviews_product ON reviews(product_id)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_reviews_user ON reviews(user_id)");
        } catch (PDOException $e) {
            // 인덱스가 이미 존재할 수 있음
        }
        
        // orders 테이블 생성
        $conn->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT DEFAULT NULL,
                order_number VARCHAR(50) NOT NULL UNIQUE,
                total_price INT NOT NULL,
                status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, paid, preparing, shipping, delivered, cancelled',
                cancel_requested TINYINT(1) DEFAULT 0 COMMENT '취소 요청 여부 (1=요청됨)',
                cancel_reason TEXT DEFAULT NULL COMMENT '취소 사유',
                shipping_name VARCHAR(50) DEFAULT NULL,
                shipping_phone VARCHAR(20) DEFAULT NULL,
                shipping_address TEXT DEFAULT NULL,
                guest_email VARCHAR(255) DEFAULT NULL COMMENT '비회원 주문 이메일',
                guest_session_id VARCHAR(100) DEFAULT NULL COMMENT '비회원 세션 ID',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 비회원 주문 컬럼 추가 (기존 테이블 마이그레이션)
        try {
            $columns = db()->fetchAll("SHOW COLUMNS FROM orders LIKE 'guest_email'");
            if (empty($columns)) {
                $conn->exec("ALTER TABLE orders ADD COLUMN guest_email VARCHAR(255) DEFAULT NULL COMMENT '비회원 주문 이메일' AFTER shipping_address");
            }
        } catch (PDOException $e) {
            // 컬럼이 이미 존재하거나 다른 오류 (무시)
        }
        
        try {
            $columns = db()->fetchAll("SHOW COLUMNS FROM orders LIKE 'guest_session_id'");
            if (empty($columns)) {
                $conn->exec("ALTER TABLE orders ADD COLUMN guest_session_id VARCHAR(100) DEFAULT NULL COMMENT '비회원 세션 ID' AFTER guest_email");
            }
        } catch (PDOException $e) {
            // 컬럼이 이미 존재하거나 다른 오류 (무시)
        }
        
        // 비회원 주문 조회를 위한 인덱스 추가
        try {
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_orders_guest_email ON orders(guest_email)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_orders_guest_session ON orders(guest_session_id)");
        } catch (PDOException $e) {
            // 인덱스가 이미 존재하거나 다른 오류 (무시)
        }
        
        // order_items 테이블 생성
        $conn->exec("
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                quantity INT NOT NULL,
                price INT NOT NULL COMMENT '주문 시점 가격',
                variant_id INT DEFAULT NULL COMMENT '선택된 variant ID (용량별 가격)',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // variant_id 컬럼이 없으면 추가
        $columns = db()->fetchAll("SHOW COLUMNS FROM order_items LIKE 'variant_id'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE order_items ADD COLUMN variant_id INT DEFAULT NULL COMMENT '선택된 variant ID (용량별 가격)' AFTER price");
                $conn->exec("ALTER TABLE order_items ADD FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL");
            } catch (PDOException $e) {
                // 컬럼이나 외래키가 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // orders 인덱스
        try {
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_order_items_product ON order_items(product_id)");
        } catch (PDOException $e) {
            // 인덱스가 이미 존재할 수 있음
        }
        
        // orders 테이블에 cancel_requested 컬럼 추가 (없으면)
        $columns = db()->fetchAll("SHOW COLUMNS FROM orders LIKE 'cancel_requested'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE orders ADD COLUMN cancel_requested TINYINT(1) DEFAULT 0 COMMENT '취소 요청 여부 (1=요청됨)' AFTER status");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // orders 테이블에 cancel_reason 컬럼 추가 (없으면)
        $columns = db()->fetchAll("SHOW COLUMNS FROM orders LIKE 'cancel_reason'");
        if (empty($columns)) {
            try {
                $conn->exec("ALTER TABLE orders ADD COLUMN cancel_reason TEXT DEFAULT NULL COMMENT '취소 사유' AFTER cancel_requested");
            } catch (PDOException $e) {
                // 컬럼이 이미 존재하거나 다른 오류 (무시)
            }
        }
        
        // payment_sessions 테이블 생성 (결제 임시 데이터 저장용)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS payment_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id VARCHAR(100) NOT NULL UNIQUE COMMENT '주문번호',
                payment_key VARCHAR(255) NOT NULL COMMENT '토스페이먼츠 paymentKey',
                amount INT NOT NULL COMMENT '결제 금액',
                order_name VARCHAR(255) NOT NULL COMMENT '주문명',
                customer_name VARCHAR(100) NOT NULL COMMENT '구매자 이름',
                customer_email VARCHAR(255) NOT NULL COMMENT '구매자 이메일',
                session_id VARCHAR(255) DEFAULT NULL COMMENT 'PHP 세션 ID',
                user_id INT DEFAULT NULL COMMENT '사용자 ID (로그인한 경우)',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
                expires_at TIMESTAMP NULL DEFAULT NULL COMMENT '만료 시간',
                status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, completed, expired',
                INDEX idx_order_id (order_id),
                INDEX idx_session_id (session_id),
                INDEX idx_expires_at (expires_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // payment_orders 테이블 생성 (임시 주문 저장용)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS payment_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id VARCHAR(100) NOT NULL UNIQUE COMMENT '주문번호 (PK)',
                order_name VARCHAR(255) NOT NULL COMMENT '주문명',
                amount INT NOT NULL COMMENT '결제 금액',
                customer_name VARCHAR(100) NOT NULL COMMENT '구매자 이름',
                customer_email VARCHAR(255) NOT NULL COMMENT '구매자 이메일',
                status VARCHAR(20) DEFAULT 'READY' COMMENT 'READY, DONE, FAIL',
                payment_key VARCHAR(255) DEFAULT NULL COMMENT '토스페이먼츠 paymentKey',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
                INDEX idx_order_id (order_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // coupons 테이블 생성 (쿠폰 정보)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS coupons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE COMMENT '쿠폰 코드',
                name VARCHAR(255) NOT NULL COMMENT '쿠폰명',
                type VARCHAR(20) NOT NULL COMMENT 'percent 또는 fixed',
                value INT NOT NULL COMMENT '할인율(%) 또는 할인금액(원)',
                min_amount INT DEFAULT 0 COMMENT '최소 주문 금액',
                max_discount INT DEFAULT 0 COMMENT '최대 할인 금액 (percent 타입일 때, 0이면 제한없음)',
                start_date DATE DEFAULT NULL COMMENT '시작일',
                end_date DATE DEFAULT NULL COMMENT '종료일',
                usage_limit INT DEFAULT 0 COMMENT '사용 제한 횟수 (0이면 무제한)',
                used_count INT DEFAULT 0 COMMENT '사용된 횟수',
                active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_code (code),
                INDEX idx_active (active),
                INDEX idx_dates (start_date, end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // user_coupons 테이블 생성 (사용자가 받은 쿠폰)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS user_coupons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL COMMENT '사용자 ID',
                coupon_id INT NOT NULL COMMENT '쿠폰 ID',
                received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '받은 시간',
                used TINYINT(1) DEFAULT 0 COMMENT '사용 여부',
                used_at TIMESTAMP NULL DEFAULT NULL COMMENT '사용한 시간',
                order_id INT DEFAULT NULL COMMENT '사용한 주문 ID',
                UNIQUE KEY unique_user_coupon (user_id, coupon_id),
                INDEX idx_user_id (user_id),
                INDEX idx_coupon_id (coupon_id),
                INDEX idx_used (used),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // coupon_usages 테이블 생성 (쿠폰 사용 내역 - 중복 사용 방지)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS coupon_usages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL COMMENT '사용자 ID',
                coupon_id INT NOT NULL COMMENT '쿠폰 ID',
                order_id INT NOT NULL COMMENT '주문 ID',
                order_number VARCHAR(50) NOT NULL COMMENT '주문번호',
                discount_amount INT NOT NULL COMMENT '할인 금액',
                used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_coupon (user_id, coupon_id),
                INDEX idx_order_id (order_id),
                INDEX idx_order_number (order_number),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 기본 쿠폰 데이터 삽입 (없는 경우만)
        $existingCoupon = db()->fetchOne("SELECT id FROM coupons WHERE code = 'WELCOME10'");
        if (!$existingCoupon) {
            $conn->exec("
                INSERT INTO coupons (code, name, type, value, min_amount, max_discount, active, created_at)
                VALUES ('WELCOME10', '신규 회원 10% 할인', 'percent', 10, 0, 10000, 1, NOW())
            ");
        }
        
        // 기본 관리자 계정 생성 (없는 경우만)
        try {
            $admin = db()->fetchOne("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
            if (!$admin) {
                // 관리자 계정이 없으면 생성
                $adminEmail = 'admin@dewscent.com';
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                db()->insert(
                    "INSERT INTO users (email, password, name, is_admin, created_at)
                     VALUES (?, ?, '관리자', 1, NOW())",
                    [$adminEmail, $adminPassword]
                );
                error_log('기본 관리자 계정이 자동 생성되었습니다. 이메일: ' . $adminEmail . ', 비밀번호: admin123');
            }
        } catch (PDOException $e) {
            // 중복된 이메일이거나 다른 오류 (무시)
            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                error_log('관리자 계정 자동 생성 중 오류 (무시됨): ' . $e->getMessage());
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log('테이블 생성 오류: ' . $e->getMessage());
        return false;
    }
}

