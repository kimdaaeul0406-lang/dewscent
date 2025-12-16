-- users 테이블에 is_admin 컬럼 추가
USE dewscent;

-- is_admin 컬럼이 없으면 추가
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)' 
AFTER address;

