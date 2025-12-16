-- 기존 products 테이블에 stock, status 컬럼 추가
-- phpMyAdmin에서 실행하세요

USE dewscent;

-- stock 컬럼 추가 (이미 있으면 무시)
ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0 COMMENT '재고 수량';

-- status 컬럼 추가 (이미 있으면 무시)
ALTER TABLE products ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT '판매중' COMMENT '판매중, 품절, 숨김';
