<?php
/**
 * 네이버 로그인 사용자 확인 스크립트
 * 어떤 계정이 네이버와 연동되어 있는지 확인
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>네이버 로그인 사용자 확인</h2>";

// 네이버 ID로 가입된 사용자 확인
$naverUsers = db()->fetchAll(
    "SELECT id, name, email, naver_id, created_at FROM users WHERE naver_id IS NOT NULL AND naver_id != '' ORDER BY id DESC"
);

echo "<h3>네이버로 가입된 사용자 (naver_id가 있는 사용자)</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>이름</th><th>이메일</th><th>네이버 ID</th><th>가입일</th></tr>";

foreach ($naverUsers as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['naver_id']}</td>";
    echo "<td>{$user['created_at']}</td>";
    echo "</tr>";
}

echo "</table>";

// kin3653@naver.com 이메일로 가입된 사용자 확인
$emailUsers = db()->fetchAll(
    "SELECT id, name, email, naver_id, kakao_id, created_at FROM users WHERE email = 'kin3653@naver.com'"
);

echo "<h3>kin3653@naver.com 이메일로 가입된 사용자</h3>";
if (empty($emailUsers)) {
    echo "<p>해당 이메일로 가입된 사용자가 없습니다.</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>이름</th><th>이메일</th><th>네이버 ID</th><th>카카오 ID</th><th>가입일</th></tr>";
    
    foreach ($emailUsers as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>" . ($user['naver_id'] ?: '-') . "</td>";
        echo "<td>" . ($user['kakao_id'] ?: '-') . "</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // 해당 사용자의 주문 내역 확인
    foreach ($emailUsers as $user) {
        $orders = db()->fetchAll(
            "SELECT id, order_number, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
            [$user['id']]
        );
        
        echo "<h4>사용자 ID {$user['id']} ({$user['email']})의 최근 주문 내역</h4>";
        if (empty($orders)) {
            echo "<p>주문 내역이 없습니다.</p>";
        } else {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>주문번호</th><th>금액</th><th>상태</th><th>주문일</th></tr>";
            
            foreach ($orders as $order) {
                echo "<tr>";
                echo "<td>{$order['order_number']}</td>";
                echo "<td>" . number_format($order['total_price']) . "원</td>";
                echo "<td>{$order['status']}</td>";
                echo "<td>{$order['created_at']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
}
