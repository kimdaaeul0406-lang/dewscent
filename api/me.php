<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = db()->fetchOne(
    "SELECT id, name, email, phone, address, created_at FROM users WHERE id = ? LIMIT 1",
    [$_SESSION['user_id']]
);

if (!$user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => '사용자 정보를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$joinedAt = '';
if (!empty($user['created_at'])) {
    $joinedAt = substr($user['created_at'], 0, 10);
}

$addresses = [];
if (!empty($user['address'])) {
    $addresses[] = [
        'id' => 1,
        'label' => '기본',
        'recipient' => $user['name'] ?? '',
        'address' => $user['address'],
        'phone' => $user['phone'] ?? '',
    ];
}

$response = [
    'id' => (int) ($user['id'] ?? 0),
    'name' => $user['name'] ?? '',
    'email' => $user['email'] ?? '',
    'phone' => $user['phone'] ?? '',
    'addresses' => $addresses,
    'joinedAt' => $joinedAt,
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
