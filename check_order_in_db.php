<?php
/**
 * 특정 주문이 DB에 실제로 존재하는지 확인
 */

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

$orderNumber = 'ORD-20251216-161724';

echo "<h2>주문 DB 확인: {$orderNumber}</h2>";

// 주문이 DB에 존재하는지 확인
$order = db()->fetchOne(
    "SELECT id, order_number, total_price, status, created_at, user_id FROM orders WHERE order_number = ?",
    [$orderNumber]
);

if ($order) {
    echo "<div style='background:#ffeeee;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h3 style='color:red;'>⚠️ 주문이 DB에 존재합니다!</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>주문번호</th><th>금액</th><th>상태</th><th>주문일</th><th>user_id</th></tr>";
    echo "<tr>";
    echo "<td>{$order['id']}</td>";
    echo "<td>{$order['order_number']}</td>";
    echo "<td>" . number_format($order['total_price']) . "원</td>";
    echo "<td>{$order['status']}</td>";
    echo "<td>{$order['created_at']}</td>";
    echo "<td>{$order['user_id']}</td>";
    echo "</tr>";
    echo "</table>";
    
    // 해당 user_id의 사용자 정보
    if ($order['user_id']) {
        $user = db()->fetchOne(
            "SELECT id, name, email, kakao_id, naver_id, created_at FROM users WHERE id = ?",
            [$order['user_id']]
        );
        
        if ($user) {
            echo "<h4>주문 소유자 정보:</h4>";
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
        }
    }
    
    echo "<br>";
    echo "<form method='POST' onsubmit='return confirm(\"정말 이 주문을 삭제하시겠습니까?\");'>";
    echo "<button type='submit' name='delete' value='1' style='padding:10px 20px;background:#ff4444;color:white;border:none;border-radius:5px;cursor:pointer;'>이 주문 삭제</button>";
    echo "</form>";
    echo "</div>";
} else {
    echo "<div style='background:#d4edda;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h3 style='color:green;'>✅ 주문이 DB에 존재하지 않습니다.</h3>";
    echo "<p>이 경우 브라우저 캐시 문제일 수 있습니다.</p>";
    echo "<p>다음 방법을 시도해보세요:</p>";
    echo "<ol>";
    echo "<li>브라우저 개발자 도구(F12) → Application → Local Storage → 해당 사이트 → 'ds_order_adds', 'ds_order_removes', 'dewscent_order_details' 삭제</li>";
    echo "<li>브라우저 개발자 도구 → Application → Session Storage → 'pending_order' 삭제</li>";
    echo "<li>Ctrl+Shift+R (강제 새로고침)</li>";
    echo "</ol>";
    echo "</div>";
}

// 주문 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete'])) {
    try {
        $conn = db()->getConnection();
        $conn->beginTransaction();
        
        // 주문 상품 삭제
        $conn->exec("DELETE FROM order_items WHERE order_id = " . (int)$order['id']);
        // 주문 삭제
        $conn->exec("DELETE FROM orders WHERE id = " . (int)$order['id']);
        
        $conn->commit();
        
        echo "<div style='background:#d4edda;padding:15px;border-radius:5px;margin:20px 0;'>";
        echo "✅ 주문이 삭제되었습니다. 페이지를 새로고침하세요.";
        echo "</div>";
        echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin:20px 0;'>";
        echo "❌ 오류: " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
}
