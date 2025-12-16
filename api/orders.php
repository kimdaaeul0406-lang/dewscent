<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 자동 생성
ensure_tables_exist();

// 관리자 여부 확인 (두 가지 방식 모두 지원)
$isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           !empty($_SESSION['admin_logged_in']);

function map_status($status)
{
    switch ($status) {
        case 'paid':
            return '결제완료';
        case 'preparing':
            return '배송준비중';
        case 'shipping':
            return '배송중';
        case 'delivered':
            return '배송완료';
        case 'cancelled':
            return '취소';
        case 'cancel_requested':
            return '취소요청';
        default:
            return '결제대기';
    }
}

function reverse_map_status($koreanStatus)
{
    switch ($koreanStatus) {
        case '결제완료':
            return 'paid';
        case '배송준비중':
            return 'preparing';
        case '배송중':
            return 'shipping';
        case '배송완료':
            return 'delivered';
        case '취소':
            return 'cancelled';
        default:
            return 'pending';
    }
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // 주문 목록 조회
        if (empty($_SESSION['user_id']) && !$isAdmin) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $from = $_GET['from'] ?? null;
        $to   = $_GET['to'] ?? null;

        // 관리자는 모든 주문, 일반 사용자는 자신의 주문만
        $where = [];
        $params = [];

        if (!$isAdmin) {
            $where[] = 'o.user_id = ?';
            $params[] = $_SESSION['user_id'];
        }

        if ($from) {
            $where[] = 'DATE(o.created_at) >= ?';
            $params[] = $from;
        }
        if ($to) {
            $where[] = 'DATE(o.created_at) <= ?';
            $params[] = $to;
        }

        $sql = "SELECT o.id, o.order_number, o.total_price, o.status, o.cancel_requested, o.cancel_reason, o.created_at, o.shipping_name, o.shipping_phone, o.shipping_address, u.name as user_name, u.email as user_email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

        $rows = db()->fetchAll($sql, $params);

        $orders = array_map(function ($row) {
            $orderId = $row['id'];
            // 주문 상품 정보 가져오기
            $items = db()->fetchAll(
                "SELECT oi.product_id, oi.product_name, oi.quantity, oi.price, p.image as product_image
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?",
                [$orderId]
            );
            
            return [
                'id' => $row['order_number'] ?: (string) $row['id'],
                'total' => (int) ($row['total_price'] ?? 0),
                'status' => map_status($row['status'] ?? ''),
                'orderedAt' => substr($row['created_at'] ?? '', 0, 10),
                'createdAt' => $row['created_at'] ?? '',
                'customer' => $row['user_name'] ?? $row['shipping_name'] ?? '비회원',
                'customer_name' => $row['user_name'] ?? $row['shipping_name'] ?? '',
                'customer_phone' => $row['shipping_phone'] ?? '',
                'customer_address' => $row['shipping_address'] ?? '',
                'email' => $row['user_email'] ?? null,
                'cancelRequested' => (bool) ($row['cancel_requested'] ?? 0),
                'cancelReason' => $row['cancel_reason'] ?? null,
                'items' => array_map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'name' => $item['product_name'],
                        'quantity' => (int) $item['quantity'],
                        'qty' => (int) $item['quantity'],
                        'price' => (int) $item['price'],
                        'image' => $item['product_image'] ?? null,
                        'imageUrl' => $item['product_image'] ?? null,
                    ];
                }, $items),
                'payment' => [
                    'subtotal' => (int) ($row['total_price'] ?? 0) - 3000,
                    'shipping' => 3000,
                    'total' => (int) ($row['total_price'] ?? 0),
                ],
            ];
        }, $rows ?: []);

        echo json_encode($orders, JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // 주문 생성
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            $data = $_POST;
        }

        $orderNumber = trim($data['orderNumber'] ?? $data['id'] ?? '');
        $items = $data['items'] ?? [];
        $customer = $data['customer'] ?? [];
        $payment = $data['payment'] ?? [];
        $total = (int)($payment['total'] ?? $data['total'] ?? 0);

        if (!$orderNumber || empty($items) || $total <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주문 정보가 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $conn = db()->getConnection();
            $conn->beginTransaction();

            // 주문 저장
            $orderId = db()->insert(
                "INSERT INTO orders (user_id, order_number, total_price, status, shipping_name, shipping_phone, shipping_address, created_at)
                 VALUES (?, ?, ?, 'pending', ?, ?, ?, NOW())",
                [
                    $_SESSION['user_id'],
                    $orderNumber,
                    $total,
                    $customer['name'] ?? '',
                    $customer['phone'] ?? '',
                    $customer['address'] ?? ''
                ]
            );

            // 주문 상품 저장
            foreach ($items as $item) {
                $productId = (int)($item['id'] ?? 0);
                $productName = $item['name'] ?? '상품명 없음';
                $quantity = (int)($item['qty'] ?? $item['quantity'] ?? 1);
                $price = (int)($item['price'] ?? 0);

                // product_id가 없으면 products 테이블에서 찾기
                if ($productId <= 0) {
                    $product = db()->fetchOne("SELECT id FROM products WHERE name = ? LIMIT 1", [$productName]);
                    $productId = $product ? $product['id'] : 0;
                }

                db()->insert(
                    "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())",
                    [$orderId, $productId, $productName, $quantity, $price]
                );
            }

            $conn->commit();

            echo json_encode(['ok' => true, 'orderId' => $orderId, 'orderNumber' => $orderNumber], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            http_response_code(500);
            $errorMsg = '주문 저장 중 오류가 발생했습니다.';
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $errorMsg .= ' ' . $e->getMessage();
                error_log('Order creation error: ' . $e->getMessage());
            }
            echo json_encode(['ok' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        // 주문 상태 변경 (관리자만)
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            $data = $_POST;
        }

        $orderNumber = trim($data['orderNumber'] ?? $data['id'] ?? '');
        $newStatus = trim($data['status'] ?? '');

        if (!$orderNumber || !$newStatus) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주문번호와 상태를 입력해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 한국어 상태를 영어로 변환
        $statusEn = reverse_map_status($newStatus);
        $allowedStatuses = ['pending', 'paid', 'preparing', 'shipping', 'delivered', 'cancelled'];
        
        if (!in_array($statusEn, $allowedStatuses)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '올바르지 않은 상태입니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // order_number로 주문 찾기
            $order = db()->fetchOne(
                "SELECT id FROM orders WHERE order_number = ?",
                [$orderNumber]
            );

            if (!$order) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => '주문을 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 상태 업데이트
            db()->execute(
                "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?",
                [$statusEn, $order['id']]
            );

            echo json_encode(['ok' => true, 'message' => '주문 상태가 변경되었습니다.'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            $errorMsg = '주문 상태 변경 중 오류가 발생했습니다.';
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $errorMsg .= ' ' . $e->getMessage();
                error_log('Order status update error: ' . $e->getMessage());
            }
            echo json_encode(['ok' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PATCH':
        // 주문 취소 요청 또는 결제 확인
        if (empty($_SESSION['user_id']) && !$isAdmin) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            $data = $_POST;
        }

        $orderNumber = trim($data['orderNumber'] ?? $data['id'] ?? '');
        $action = trim($data['action'] ?? ''); // 'cancel_request', 'confirm_payment', 'approve_cancel', 'reject_cancel'

        if (!$orderNumber || !$action) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주문번호와 액션을 입력해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $order = db()->fetchOne(
                "SELECT id, status, user_id FROM orders WHERE order_number = ?",
                [$orderNumber]
            );

            if (!$order) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => '주문을 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            switch ($action) {
                case 'cancel_request':
                    // 사용자가 취소 요청
                    if ($order['user_id'] != $_SESSION['user_id']) {
                        http_response_code(403);
                        echo json_encode(['ok' => false, 'message' => '본인의 주문만 취소할 수 있습니다.'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    // 결제대기 상태는 즉시 취소, 결제완료 상태는 취소 요청
                    if ($order['status'] === 'pending') {
                        // 결제대기 상태: 즉시 취소
                        db()->execute(
                            "UPDATE orders SET status = 'cancelled', cancel_reason = ?, updated_at = NOW() WHERE id = ?",
                            [$reason, $order['id']]
                        );
                        echo json_encode(['ok' => true, 'message' => '주문이 취소되었습니다.'], JSON_UNESCAPED_UNICODE);
                    } else if ($order['status'] === 'paid' || $order['status'] === 'preparing') {
                        // 결제완료 또는 배송준비중 상태: 취소 요청
                        db()->execute(
                            "UPDATE orders SET status = 'cancel_requested', cancel_requested = 1, cancel_reason = ?, updated_at = NOW() WHERE id = ?",
                            [$reason, $order['id']]
                        );
                        echo json_encode(['ok' => true, 'message' => '취소 요청이 접수되었습니다. 관리자 승인 후 처리됩니다.'], JSON_UNESCAPED_UNICODE);
                    } else {
                        http_response_code(400);
                        echo json_encode(['ok' => false, 'message' => '현재 상태에서는 취소할 수 없습니다.'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    break;

                    $cancelReason = trim($data['reason'] ?? '');
                    db()->execute(
                        "UPDATE orders SET cancel_requested = 1, cancel_reason = ? WHERE id = ?",
                        [$cancelReason, $order['id']]
                    );

                    echo json_encode(['ok' => true, 'message' => '취소 요청이 접수되었습니다. 관리자 승인 후 취소됩니다.'], JSON_UNESCAPED_UNICODE);
                    break;

                case 'confirm_payment':
                    // 관리자가 결제 확인 (결제대기 → 결제완료)
                    if (!$isAdmin) {
                        http_response_code(403);
                        echo json_encode(['ok' => false, 'message' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    if ($order['status'] !== 'pending') {
                        http_response_code(400);
                        echo json_encode(['ok' => false, 'message' => '결제대기 상태의 주문만 결제 확인할 수 있습니다.'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    db()->execute(
                        "UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = ?",
                        [$order['id']]
                    );

                    echo json_encode(['ok' => true, 'message' => '결제가 확인되었습니다.'], JSON_UNESCAPED_UNICODE);
                    break;

                case 'approve_cancel':
                    // 관리자가 취소 승인
                    if (!$isAdmin) {
                        http_response_code(403);
                        echo json_encode(['ok' => false, 'message' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    db()->execute(
                        "UPDATE orders SET status = 'cancelled', cancel_requested = 0, updated_at = NOW() WHERE id = ?",
                        [$order['id']]
                    );

                    echo json_encode(['ok' => true, 'message' => '주문 취소가 승인되었습니다.'], JSON_UNESCAPED_UNICODE);
                    break;

                case 'reject_cancel':
                    // 관리자가 취소 거부
                    if (!$isAdmin) {
                        http_response_code(403);
                        echo json_encode(['ok' => false, 'message' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }

                    db()->execute(
                        "UPDATE orders SET cancel_requested = 0, cancel_reason = NULL WHERE id = ?",
                        [$order['id']]
                    );

                    echo json_encode(['ok' => true, 'message' => '취소 요청이 거부되었습니다.'], JSON_UNESCAPED_UNICODE);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['ok' => false, 'message' => '올바르지 않은 액션입니다.'], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            http_response_code(500);
            $errorMsg = '처리 중 오류가 발생했습니다.';
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $errorMsg .= ' ' . $e->getMessage();
                error_log('Order action error: ' . $e->getMessage());
            }
            echo json_encode(['ok' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
