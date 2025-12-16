<?php
// data/products.php
// DewScent 상품 데이터 (DB 연결 시 DB에서 가져옴)

require_once __DIR__ . '/../includes/db.php';

// DB에서 모든 상품 가져오기
function get_all_products()
{
    try {
        $sql = "SELECT * FROM products ORDER BY id ASC";
        return db()->fetchAll($sql);
    } catch (Exception $e) {
        // DB 오류 시 빈 배열 반환
        return [];
    }
}

// id로 상품 하나 찾기
function find_product_by_id($id)
{
    try {
        $sql = "SELECT * FROM products WHERE id = ?";
        return db()->fetchOne($sql, [(int)$id]);
    } catch (Exception $e) {
        return null;
    }
}

// 카테고리(type)로 상품 필터링
function get_products_by_type($type)
{
    try {
        $sql = "SELECT * FROM products WHERE type = ? ORDER BY id ASC";
        return db()->fetchAll($sql, [$type]);
    } catch (Exception $e) {
        return [];
    }
}

// 상품 검색
function search_products($keyword)
{
    try {
        $sql = "SELECT * FROM products WHERE name LIKE ? OR `desc` LIKE ? ORDER BY id ASC";
        $param = "%{$keyword}%";
        return db()->fetchAll($sql, [$param, $param]);
    } catch (Exception $e) {
        return [];
    }
}

// 베스트 상품 가져오기
function get_best_products()
{
    try {
        $sql = "SELECT * FROM products WHERE badge = 'BEST' ORDER BY rating DESC";
        return db()->fetchAll($sql);
    } catch (Exception $e) {
        return [];
    }
}

// 신상품 가져오기
function get_new_products()
{
    try {
        $sql = "SELECT * FROM products WHERE badge = 'NEW' ORDER BY id DESC";
        return db()->fetchAll($sql);
    } catch (Exception $e) {
        return [];
    }
}

// 세일 상품 가져오기
function get_sale_products()
{
    try {
        $sql = "SELECT * FROM products WHERE badge = 'SALE' OR originalPrice IS NOT NULL ORDER BY id ASC";
        return db()->fetchAll($sql);
    } catch (Exception $e) {
        return [];
    }
}

// 기존 코드 호환성을 위해 $products 변수도 유지
$products = get_all_products();
