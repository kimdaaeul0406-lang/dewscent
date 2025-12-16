<?php
// 에러 표시 켜기
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';

echo "<h2>DB 연결 테스트</h2>";

try {
    $pdo = db()->getConnection();
    echo "<p style='color:green;'>DB 연결 성공!</p>";

    // 테이블 확인
    $tables = db()->fetchAll("SHOW TABLES");
    echo "<h3>테이블 목록:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . array_values($table)[0] . "</li>";
    }
    echo "</ul>";

    // 상품 데이터 확인
    $products = db()->fetchAll("SELECT * FROM products");
    echo "<h3>상품 데이터 (" . count($products) . "개):</h3>";

    if (count($products) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>이름</th><th>타입</th><th>가격</th><th>뱃지</th></tr>";
        foreach ($products as $p) {
            echo "<tr>";
            echo "<td>" . $p['id'] . "</td>";
            echo "<td>" . $p['name'] . "</td>";
            echo "<td>" . $p['type'] . "</td>";
            echo "<td>" . number_format($p['price']) . "원</td>";
            echo "<td>" . ($p['badge'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>상품 데이터가 없습니다!</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
}
