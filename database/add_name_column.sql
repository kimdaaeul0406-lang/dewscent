-- users 테이블에 name 컬럼 추가
USE dewscent;

-- name 컬럼 추가 (id 다음에)
ALTER TABLE users 
ADD COLUMN name VARCHAR(50) NOT NULL DEFAULT '' AFTER id;

-- 기존 데이터가 있다면 email의 @ 앞부분을 name으로 설정 (임시)
UPDATE users SET name = SUBSTRING_INDEX(email, '@', 1) WHERE name = '';

