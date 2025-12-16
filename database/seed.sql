-- DewScent 초기 상품 데이터
-- schema.sql 실행 후 이 파일을 실행하세요

USE dewscent;

-- 기존 데이터 삭제 (필요한 경우)
-- TRUNCATE TABLE products;

-- 상품 데이터 삽입
INSERT INTO products (id, name, type, price, originalPrice, rating, reviews, badge, `desc`) VALUES
(1, 'Morning Dew', '향수', 89000, 110000, 4.8, 128, 'BEST', '아침 이슬처럼 맑고 청량한 향기입니다.'),
(2, 'Rose Garden', '바디미스트', 65000, NULL, 4.9, 89, 'NEW', '로맨틱한 장미 정원을 거니는 듯한 우아한 향기입니다.'),
(3, 'Golden Hour', '향수', 105000, NULL, 4.7, 156, NULL, '황금빛 노을처럼 따스하고 포근한 향기입니다.'),
(4, 'Forest Mist', '디퓨저', 78000, 98000, 4.6, 72, 'SALE', '숲속의 신선한 공기를 담은 청량한 향기입니다.'),
(5, 'Ocean Breeze', '섬유유연제', 32000, NULL, 4.5, 203, NULL, '바다 바람처럼 시원하고 깨끗한 향기입니다.'),
(6, 'Velvet Night', '향수', 125000, NULL, 4.9, 67, 'NEW', '밤의 신비로움을 담은 관능적인 향기입니다.'),
(7, 'Citrus Burst', '바디미스트', 55000, 68000, 4.4, 145, 'SALE', '상큼한 시트러스가 톡톡 터지는 활기찬 향기입니다.'),
(8, 'Soft Cotton', '섬유유연제', 28000, NULL, 4.7, 312, 'BEST', '갓 세탁한 면처럼 포근하고 깨끗한 향기입니다.');
