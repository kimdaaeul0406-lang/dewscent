<?php
/**
 * 사용자와 주문 내역 디버깅 스크립트
 * 현재 로그인한 사용자와 주문 내역을 확인
 */

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>사용자 및 주문 내역 디버깅</h2>";

// 현재 세션 정보
echo "<h3>1. 현재 세션 정보</h3>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? '없음') . "\n";
echo "username: " . ($_SESSION['username'] ?? '없음') . "\n";
echo "email: " . ($_SESSION['email'] ?? '없음') . "\n";
echo "login_type: " . ($_SESSION['login_type'] ?? '없음') . "\n";
echo "</pre>";

if (empty($_SESSION['user_id'])) {
    echo "<p style='color:red;'>⚠️ 로그인이 필요합니다.</p>";
    exit;
}

$userId = $_SESSION['user_id'];

// 현재 사용자 정보
echo "<h3>2. 현재 사용자 정보 (DB)</h3>";
$user = db()->fetchOne(
    "SELECT id, name, email, kakao_id, naver_id, created_at FROM users WHERE id = ?",
    [$userId]
);

if ($user) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>이름</th><th>이메일</th><th>카카오 ID</th><th>네이버 ID</th><th>가입일</th></tr>";
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>" . ($user['kakao_id'] ?: '-') . "</td>";
    echo "<td>" . ($user['naver_id'] ?: '-') . "</td>";
    echo "<td>{$user['created_at']}</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "<p style='color:red;'>⚠️ 사용자를 찾을 수 없습니다.</p>";
    exit;
}

// 해당 사용자의 주문 내역
echo "<h3>3. 현재 사용자 (user_id={$userId})의 주문 내역</h3>";
$userOrders = db()->fetchAll(
    "SELECT id, order_number, total_price, status, created_at, user_id FROM orders WHERE user_id = ? ORDER BY created_at DESC",
    [$userId]
);

if (empty($userOrders)) {
    echo "<p>✅ 이 사용자에게는 주문 내역이 없습니다.</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>주문번호</th><th>금액</th><th>상태</th><th>주문일</th><th>user_id</th></tr>";
    foreach ($userOrders as $order) {
        echo "<tr>";
        echo "<td>{$order['order_number']}</td>";
        echo "<td>" . number_format($order['total_price']) . "원</td>";
        echo "<td>{$order['status']}</td>";
        echo "<td>{$order['created_at']}</td>";
        echo "<td>{$order['user_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// ORD-20251216-161724 주문 상세 정보
echo "<h3>4. ORD-20251216-161724 주문 상세 정보</h3>";
$problemOrder = db()->fetchOne(
    "SELECT id, order_number, total_price, status, created_at, user_id FROM orders WHERE order_number = ?",
    ['ORD-20251216-161724']
);

if ($problemOrder) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>주문번호</th><th>금액</th><th>상태</th><th>주문일</th><th>user_id</th></tr>";
    echo "<tr>";
    echo "<td>{$problemOrder['order_number']}</td>";
    echo "<td>" . number_format($problemOrder['total_price']) . "원</td>";
    echo "<td>{$problemOrder['status']}</td>";
    echo "<td>{$problemOrder['created_at']}</td>";
    echo "<td style='color:" . ($problemOrder['user_id'] == $userId ? 'red' : 'green') . ";font-weight:bold;'>{$problemOrder['user_id']}</td>";
    echo "</tr>";
    echo "</table>";
    
    if ($problemOrder['user_id'] == $userId) {
        echo "<p style='color:red;font-weight:bold;'>⚠️ 이 주문이 현재 사용자(user_id={$userId})에게 연결되어 있습니다!</p>";
        
        // 해당 user_id의 사용자 정보
        $orderOwner = db()->fetchOne(
            "SELECT id, name, email, kakao_id, naver_id, created_at FROM users WHERE id = ?",
            [$problemOrder['user_id']]
        );
        
        if ($orderOwner) {
            echo "<h4>이 주문의 소유자 정보:</h4>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>이름</th><th>이메일</th><th>카카오 ID</th><th>네이버 ID</th><th>가입일</th></tr>";
            echo "<tr>";
            echo "<td>{$orderOwner['id']}</td>";
            echo "<td>{$orderOwner['name']}</td>";
            echo "<td>{$orderOwner['email']}</td>";
            echo "<td>" . ($orderOwner['kakao_id'] ?: '-') . "</td>";
            echo "<td>" . ($orderOwner['naver_id'] ?: '-') . "</td>";
            echo "<td>{$orderOwner['created_at']}</td>";
            echo "</tr>";
            echo "</table>";
        }
    } else {
        echo "<p style='color:green;'>✅ 이 주문은 다른 사용자(user_id={$problemOrder['user_id']})에게 연결되어 있습니다.</p>";
    }
} else {
    echo "<p>해당 주문을 찾을 수 없습니다.</p>";
}

// 모든 사용자 목록 (최근 10명)
echo "<h3>5. 최근 가입한 사용자 목록 (최근 10명)</h3>";
$allUsers = db()->fetchAll(
    "SELECT id, name, email, kakao_id, naver_id, created_at FROM users ORDER BY created_at DESC LIMIT 10"
);

if (!empty($allUsers)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>이름</th><th>이메일</th><th>카카오 ID</th><th>네이버 ID</th><th>가입일</th></tr>";
    foreach ($allUsers as $u) {
        $highlight = $u['id'] == $userId ? "style='background-color:#ffffcc;'" : "";
        echo "<tr {$highlight}>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['name']}</td>";
        echo "<td>{$u['email']}</td>";
        echo "<td>" . ($u['kakao_id'] ?: '-') . "</td>";
        echo "<td>" . ($u['naver_id'] ?: '-') . "</td>";
        echo "<td>{$u['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color:#666;font-size:0.9em;'>노란색 배경 = 현재 로그인한 사용자</p>";
}
