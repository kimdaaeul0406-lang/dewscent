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
        // 주문 목록 조회 (회원/비회원 모두 허용)
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to'] ?? null;
        $orderNumber = $_GET['orderNumber'] ?? null;
        $guestEmail = trim($_GET['guestEmail'] ?? '');
        $guestPhone = trim($_GET['guestPhone'] ?? '');

        // 관리자는 모든 주문, 일반 사용자는 자신의 주문만, 비회원은 이메일/전화번호로 조회 (주문번호는 선택사항)
        $where = [];
        $params = [];
        $currentUserId = $_SESSION['user_id'] ?? null;
        
        // 로그인한 사용자가 있으면 무조건 자신의 주문만 조회 (비회원 주문 제외)
        if ($isAdmin) {
            // 관리자는 모든 주문 조회
            // 관리자도 이메일/전화번호로 필터링 가능
            if ($guestEmail) {
                $where[] = 'LOWER(TRIM(o.guest_email)) = LOWER(TRIM(?))';
                $params[] = $guestEmail;
            } else if ($guestPhone) {
                $phoneDigits = preg_replace('/[^0-9]/', '', $guestPhone);
                $where[] = 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(o.shipping_phone, "-", ""), " ", ""), "(", ""), ")", ""), ".", "") = ?';
                $params[] = $phoneDigits;
            }
        } else if (!empty($currentUserId)) {
            // 회원: 자신의 주문만 조회 (user_id가 정확히 일치하는 주문만)
            // 로그인한 사용자는 비회원 주문을 절대 볼 수 없음
            // guestEmail이나 guestPhone 파라미터는 완전히 무시
            $where[] = 'o.user_id = ?';
            $params[] = (int)$currentUserId; // 정수형으로 명시적 변환
            if ($orderNumber) {
                $where[] = 'o.order_number = ?';
                $params[] = $orderNumber;
            }
            // 추가 보안: user_id가 NULL이거나 다른 사용자의 주문은 절대 조회 불가
            // SQL 쿼리에서 이미 필터링되지만, 명시적으로 확인
        } else {
            // 비회원 조회: 이메일 또는 전화번호로 조회 (주문번호는 선택사항)
            $isGuestLookup = !empty($guestEmail) || !empty($guestPhone);
            
            if (!$isGuestLookup) {
                // 비회원이지만 조회 조건이 없으면 접근 거부
                http_response_code(401);
                echo json_encode(['ok' => false, 'message' => '주문 조회를 위해 이메일 또는 전화번호가 필요합니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 비회원 조회: 이메일 또는 전화번호로 조회
            if ($orderNumber) {
                $where[] = 'o.order_number = ?';
                $params[] = $orderNumber;
            }
            if ($guestEmail) {
                // 이메일은 대소문자 구분 없이, 공백 제거하여 비교
                $where[] = 'LOWER(TRIM(o.guest_email)) = LOWER(TRIM(?))';
                $params[] = $guestEmail;
            } else if ($guestPhone) {
                // 전화번호는 숫자만 추출하여 비교 (하이픈, 공백, 괄호 제거)
                $phoneDigits = preg_replace('/[^0-9]/', '', $guestPhone);
                $where[] = 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(o.shipping_phone, "-", ""), " ", ""), "(", ""), ")", ""), ".", "") = ?';
                $params[] = $phoneDigits;
            }
            // 비회원 조회는 user_id가 NULL이어야 함
            $where[] = 'o.user_id IS NULL';
        }

        if ($from) {
            $where[] = 'DATE(o.created_at) >= ?';
            $params[] = $from;
        }
        if ($to) {
            $where[] = 'DATE(o.created_at) <= ?';
            $params[] = $to;
        }

        // 취소된 주문도 포함하여 조회 (상태 필터링 없음)
        $sql = "SELECT o.id, o.order_number, o.total_price, o.status, o.cancel_requested, o.cancel_reason, o.created_at, o.shipping_name, o.shipping_phone, o.shipping_address, o.user_id, o.guest_email, o.guest_session_id, u.name as user_name, u.email as user_email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        // 모든 상태의 주문 조회 (취소된 주문 포함)
        $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

        // 디버깅: 쿼리 로그
        error_log("주문 조회 SQL: " . $sql);
        error_log("주문 조회 파라미터: " . json_encode($params));
        $currentUserId = $_SESSION['user_id'] ?? null;
        error_log("주문 조회 조건 - isAdmin: " . ($isAdmin ? 'Y' : 'N') . ", user_id: " . ($currentUserId ?? 'NULL') . ", orderNumber: " . ($orderNumber ?? 'NULL') . ", guestEmail: " . ($guestEmail ?? 'NULL') . ", guestPhone: " . ($guestPhone ?? 'NULL') . ", WHERE 조건: " . (count($where) > 0 ? implode(' AND ', $where) : '없음'));
        
        try {
            $rows = db()->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log("주문 조회 SQL 실행 오류: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '주문 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 디버깅: 주문 조회 로그
        error_log("주문 조회 결과: " . count($rows) . "건");
        if (!empty($rows)) {
            foreach ($rows as $row) {
                error_log("  - 주문번호: {$row['order_number']}, user_id: " . ($row['user_id'] ?? 'NULL') . ", guest_email: " . ($row['guest_email'] ?? 'NULL') . ", shipping_phone: " . ($row['shipping_phone'] ?? 'NULL') . ", 상태: {$row['status']}");
            }
        } else {
            error_log("주문 조회 결과 없음 - SQL: " . $sql . ", 파라미터: " . json_encode($params));
            // 추가 디버깅: 실제 DB에 있는 데이터 확인
            try {
                $debugSql = "SELECT COUNT(*) as cnt FROM orders WHERE user_id IS NULL";
                $debugResult = db()->fetchOne($debugSql);
                error_log("  - DB에 저장된 비회원 주문 총 개수: " . ($debugResult['cnt'] ?? 0));
                
                // 실제 DB에 있는 비회원 주문 샘플 확인
                $sampleSql = "SELECT id, order_number, guest_email, shipping_phone, user_id FROM orders WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 5";
                $samples = db()->fetchAll($sampleSql);
                error_log("  - 비회원 주문 샘플 (최근 5개):");
                foreach ($samples as $sample) {
                    error_log("    * 주문번호: {$sample['order_number']}, guest_email: " . ($sample['guest_email'] ?? 'NULL') . ", shipping_phone: " . ($sample['shipping_phone'] ?? 'NULL'));
                }
                
                if ($guestEmail) {
                    $debugEmailSql = "SELECT COUNT(*) as cnt FROM orders WHERE LOWER(TRIM(guest_email)) = LOWER(TRIM(?)) AND user_id IS NULL";
                    $debugEmailResult = db()->fetchOne($debugEmailSql, [$guestEmail]);
                    error_log("  - 이메일 '{$guestEmail}'로 조회 가능한 비회원 주문 개수: " . ($debugEmailResult['cnt'] ?? 0));
                    
                    // 이메일로 실제 주문 확인
                    $emailOrdersSql = "SELECT id, order_number, guest_email, shipping_phone FROM orders WHERE LOWER(TRIM(guest_email)) = LOWER(TRIM(?)) AND user_id IS NULL LIMIT 3";
                    $emailOrders = db()->fetchAll($emailOrdersSql, [$guestEmail]);
                    error_log("  - 이메일 '{$guestEmail}'로 찾은 주문:");
                    foreach ($emailOrders as $eo) {
                        error_log("    * 주문번호: {$eo['order_number']}, guest_email: " . ($eo['guest_email'] ?? 'NULL'));
                    }
                }
                if ($guestPhone) {
                    $phoneDigits = preg_replace('/[^0-9]/', '', $guestPhone);
                    $debugPhoneSql = "SELECT COUNT(*) as cnt FROM orders WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(shipping_phone, '-', ''), ' ', ''), '(', ''), ')', ''), '.', '') = ? AND user_id IS NULL";
                    $debugPhoneResult = db()->fetchOne($debugPhoneSql, [$phoneDigits]);
                    error_log("  - 전화번호 '{$phoneDigits}'로 조회 가능한 비회원 주문 개수: " . ($debugPhoneResult['cnt'] ?? 0));
                    
                    // 전화번호로 실제 주문 확인
                    $phoneOrdersSql = "SELECT id, order_number, guest_email, shipping_phone FROM orders WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(shipping_phone, '-', ''), ' ', ''), '(', ''), ')', ''), '.', '') = ? AND user_id IS NULL LIMIT 3";
                    $phoneOrders = db()->fetchAll($phoneOrdersSql, [$phoneDigits]);
                    error_log("  - 전화번호 '{$phoneDigits}'로 찾은 주문:");
                    foreach ($phoneOrders as $po) {
                        error_log("    * 주문번호: {$po['order_number']}, shipping_phone: " . ($po['shipping_phone'] ?? 'NULL'));
                    }
                }
            } catch (Exception $debugE) {
                error_log("  - 디버깅 쿼리 실행 오류: " . $debugE->getMessage());
            }
        }

        // 추가 보안: 로그인한 사용자의 경우 반환된 주문이 모두 자신의 것인지 확인
        $currentUserId = $_SESSION['user_id'] ?? null;
        if (!empty($currentUserId) && !$isAdmin) {
            foreach ($rows as $row) {
                $orderUserId = $row['user_id'] ?? null;
                if ($orderUserId != $currentUserId) {
                    error_log("보안 경고: 사용자 ID {$currentUserId}가 다른 사용자(ID: {$orderUserId})의 주문을 조회하려고 시도함. 주문번호: " . ($row['order_number'] ?? 'N/A'));
                    // 다른 사용자의 주문은 제거
                    $rows = array_filter($rows, function($r) use ($currentUserId) {
                        return ($r['user_id'] ?? null) == $currentUserId;
                    });
                    $rows = array_values($rows); // 인덱스 재정렬
                    break;
                }
            }
        }
        
        $orders = array_map(function ($row) use ($currentUserId, $isAdmin) {
            $orderId = $row['id'];
            $orderUserId = $row['user_id'] ?? null;
            
            // 추가 보안 체크: 로그인한 사용자는 자신의 주문만 볼 수 있음
            if (!empty($currentUserId) && !$isAdmin && $orderUserId != $currentUserId) {
                error_log("보안: 사용자 ID {$currentUserId}가 다른 사용자(ID: {$orderUserId})의 주문 접근 시도 차단");
                return null; // 이 주문은 반환하지 않음
            }
            
            // 주문 상품 정보 가져오기 (variant 정보 포함)
            try {
                $items = db()->fetchAll(
                    "SELECT oi.product_id, oi.product_name, oi.quantity, oi.price, oi.variant_id, 
                            p.image as product_image,
                            pv.volume as variant_volume
                     FROM order_items oi
                     LEFT JOIN products p ON oi.product_id = p.id
                     LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                     WHERE oi.order_id = ?",
                    [$orderId]
                );
                error_log("주문 ID {$orderId}의 상품 개수: " . count($items));
                if (!empty($items)) {
                    foreach ($items as $idx => $item) {
                        error_log("  - 상품 {$idx}: " . ($item['product_name'] ?? '이름 없음') . ", 수량: " . ($item['quantity'] ?? 0));
                    }
                } else {
                    error_log("  - 주문 ID {$orderId}에 상품이 없습니다.");
                }
            } catch (Exception $e) {
                error_log("주문 상품 조회 오류 (주문 ID: {$orderId}): " . $e->getMessage());
                $items = [];
            }
            
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
                'email' => $row['user_email'] ?? $row['guest_email'] ?? null,
                'is_guest' => empty($row['user_id']),
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
                        'variant_id' => $item['variant_id'] ?? null,
                        'variant_volume' => $item['variant_volume'] ?? null,
                    ];
                }, $items),
                'payment' => [
                    'subtotal' => (int) ($row['total_price'] ?? 0) - 3000,
                    'shipping' => 3000,
                    'total' => (int) ($row['total_price'] ?? 0),
                ],
            ];
        }, $rows ?: []);
        
        // null 값 제거 (보안 체크에서 필터링된 주문)
        $orders = array_filter($orders, function($order) {
            return $order !== null;
        });
        $orders = array_values($orders); // 인덱스 재정렬

        echo json_encode($orders, JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // 주문 생성 (회원/비회원 모두 허용)
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

        // 비회원 주문인 경우 이메일 필수
        $userId = $_SESSION['user_id'] ?? null;
        $isGuest = empty($userId);
        $guestEmail = $customer['email'] ?? '';
        $guestSessionId = session_id();

        if ($isGuest && empty($guestEmail)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '비회원 주문은 이메일이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $conn = db()->getConnection();
            $conn->beginTransaction();

            // 주문 저장
            // 결제 수단이 카드인 경우 paid, 무통장 입금인 경우 pending
            $paymentMethod = $payment['method'] ?? '';
            $orderStatus = ($paymentMethod === 'card') ? 'paid' : 'pending';
            
            $orderId = db()->insert(
                "INSERT INTO orders (user_id, order_number, total_price, status, shipping_name, shipping_phone, shipping_address, guest_email, guest_session_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userId, // NULL이면 비회원
                    $orderNumber,
                    $total,
                    $orderStatus,
                    $customer['name'] ?? '',
                    $customer['phone'] ?? '',
                    $customer['address'] ?? '',
                    $isGuest ? $guestEmail : null,
                    $isGuest ? $guestSessionId : null
                ]
            );

            // 주문 상품 저장 및 재고 감소
            foreach ($items as $item) {
                $productId = (int)($item['id'] ?? 0);
                $productName = $item['name'] ?? '상품명 없음';
                $quantity = (int)($item['qty'] ?? $item['quantity'] ?? 1);
                $price = (int)($item['price'] ?? 0);
                $variantId = isset($item['variantId']) ? (int)$item['variantId'] : null;

                // product_id가 없으면 products 테이블에서 찾기
                if ($productId <= 0) {
                    $product = db()->fetchOne("SELECT id FROM products WHERE name = ? LIMIT 1", [$productName]);
                    $productId = $product ? $product['id'] : 0;
                }

                // 주문 상품 저장
                db()->insert(
                    "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, variant_id, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())",
                    [$orderId, $productId, $productName, $quantity, $price, $variantId]
                );

                // 재고 감소 (variant가 있으면 variant 재고, 없으면 상품 재고)
                if ($variantId) {
                    // variant 재고 감소
                    db()->execute(
                        "UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?",
                        [$quantity, $variantId, $quantity]
                    );
                } else {
                    // 상품 재고 감소 (variants가 없는 경우)
                    db()->execute(
                        "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?",
                        [$quantity, $productId, $quantity]
                    );
                }
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
        // 주문 취소 요청 또는 결제 확인 (회원/비회원 모두 지원)
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            $data = $_POST;
        }

        $orderNumber = trim($data['orderNumber'] ?? $data['id'] ?? '');
        $action = trim($data['action'] ?? ''); // 'cancel_request', 'confirm_payment', 'approve_cancel', 'reject_cancel'
        $guestEmail = trim($data['guestEmail'] ?? '');
        $guestPhone = trim($data['guestPhone'] ?? '');

        if (!$orderNumber || !$action) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주문번호와 액션을 입력해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $order = db()->fetchOne(
                "SELECT id, status, user_id, guest_email, shipping_phone FROM orders WHERE order_number = ?",
                [$orderNumber]
            );

            if (!$order) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => '주문을 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            switch ($action) {
                case 'cancel_request':
                    // 사용자가 취소 요청 (회원/비회원 모두)
                    $currentUserId = $_SESSION['user_id'] ?? null;
                    $isGuestOrder = empty($order['user_id']);
                    
                    // 회원 주문인 경우: 본인 확인
                    if (!$isGuestOrder) {
                        if (empty($currentUserId) || $order['user_id'] != $currentUserId) {
                            http_response_code(403);
                            echo json_encode(['ok' => false, 'message' => '본인의 주문만 취소할 수 있습니다.'], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    } else {
                        // 비회원 주문인 경우: 이메일 또는 전화번호로 인증
                        $isAuthorized = false;
                        if ($guestEmail) {
                            $isAuthorized = (strtolower(trim($order['guest_email'] ?? '')) === strtolower(trim($guestEmail)));
                        } else if ($guestPhone) {
                            $phoneDigits = preg_replace('/[^0-9]/', '', $guestPhone);
                            $orderPhoneDigits = preg_replace('/[^0-9]/', '', $order['shipping_phone'] ?? '');
                            $isAuthorized = ($orderPhoneDigits === $phoneDigits);
                        }
                        
                        if (!$isAuthorized) {
                            http_response_code(403);
                            echo json_encode(['ok' => false, 'message' => '주문 시 입력한 이메일 또는 전화번호를 확인해주세요.'], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    }

                    // 취소 사유 가져오기
                    $reason = trim($data['reason'] ?? '');

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
