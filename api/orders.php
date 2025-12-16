<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$from = $_GET['from'] ?? null;
$to   = $_GET['to'] ?? null;

$where = ['user_id = ?'];
$params = [$_SESSION['user_id']];

if ($from) {
    $where[] = 'DATE(created_at) >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'DATE(created_at) <= ?';
    $params[] = $to;
}

$sql = "SELECT id, order_number, total_price, status, created_at FROM orders";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC, id DESC';

$rows = db()->fetchAll($sql, $params);

function map_status($status)
{
    switch ($status) {
        case 'paid':
            return '결제완료';
        case 'shipping':
            return '배송중';
        case 'delivered':
            return '배송완료';
        case 'cancelled':
            return '취소';
        default:
            return '결제대기';
    }
}

$orders = array_map(function ($row) {
    return [
        'id' => $row['order_number'] ?: (string) $row['id'],
        'total' => (int) ($row['total_price'] ?? 0),
        'status' => map_status($row['status'] ?? ''),
        'orderedAt' => substr($row['created_at'] ?? '', 0, 10),
    ];
}, $rows ?: []);

echo json_encode($orders, JSON_UNESCAPED_UNICODE);
