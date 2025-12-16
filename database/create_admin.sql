-- 관리자 계정 생성
-- phpMyAdmin에서 실행하세요

USE dewscent;

-- is_admin 컬럼 추가 (이미 있으면 무시)
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부';

-- 기본 관리자 계정 생성
-- 이메일: admin@dewscent.kr
-- 비밀번호: admin123
INSERT INTO users (email, password, name, is_admin) VALUES (
    'admin@dewscent.kr',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '관리자',
    1
) ON DUPLICATE KEY UPDATE is_admin = 1;

-- 비밀번호 변경하고 싶으면 아래 쿼리 사용 (password123 예시)
-- UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@dewscent.kr';
