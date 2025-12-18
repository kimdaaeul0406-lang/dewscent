<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 자동 생성
ensure_tables_exist();

// 공통 함수: 관리자 여부 확인
function is_admin_user() {
    return (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           !empty($_SESSION['admin_logged_in']);
}

// 공통 함수: 주문 상태 매핑 (영어 → 한국어)
function map_order_status($status)
{
    $statusMap = [
        'paid' => '결제완료',
        'preparing' => '배송준비중',
        'shipping' => '배송중',
        'delivered' => '배송완료',
        'cancelled' => '취소',
        'cancel_requested' => '취소요청',
        'pending' => '결제대기'
    ];
    return $statusMap[$status] ?? '결제대기';
}

// 공통 함수: 주문 상태 역매핑 (한국어 → 영어)
function reverse_map_order_status($koreanStatus)
{
    $reverseMap = [
        '결제완료' => 'paid',
        '배송준비중' => 'preparing',
        '배송중' => 'shipping',
        '배송완료' => 'delivered',
        '취소' => 'cancelled',
        '취소요청' => 'cancel_requested',
        '결제대기' => 'pending'
    ];
    return $reverseMap[$koreanStatus] ?? 'pending';
}

// 관리자 여부 확인 (공통 함수 사용)
$isAdmin = is_admin_user();

// 주문 취소 시 재고 복구 함수
function restoreOrderStock($orderId) {
    try {
        // 주문 상품 목록 가져오기
        $orderItems = db()->fetchAll(
            "SELECT product_id, quantity, variant_id FROM order_items WHERE order_id = ?",
            [$orderId]
        );
        
        if (empty($orderItems)) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("[Orders API] 재고 복구: 주문 상품이 없음 (orderId={$orderId})");
        }
            return;
        }
        
        foreach ($orderItems as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            $variantId = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
            
            if ($quantity <= 0) continue;
            
            if ($variantId) {
                // variant 재고 복구
                db()->execute(
                    "UPDATE product_variants SET stock = stock + ? WHERE id = ?",
                    [$quantity, $variantId]
                );
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("[Orders API] 재고 복구: variantId={$variantId}, quantity={$quantity}");
                }
            } else if ($productId > 0) {
                // 상품 재고 복구 (variants가 없는 경우)
                db()->execute(
                    "UPDATE products SET stock = stock + ? WHERE id = ?",
                    [$quantity, $productId]
                );
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("[Orders API] 재고 복구: productId={$productId}, quantity={$quantity}");
                }
            }
        }
        
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("[Orders API] 재고 복구 완료: orderId={$orderId}, items=" . count($orderItems));
        }
    } catch (Exception $e) {
        error_log("[Orders API] 재고 복구 실패: " . $e->getMessage() . " | " . $e->getTraceAsString());
        throw $e;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// payment_orders에서 주문 정보 복구 및 저장 (결제 승인 후)
if ($action === 'saveFromPayment' && $method === 'POST') {
    $paymentOrderId = trim($_GET['orderId'] ?? '');
    
    if (empty($paymentOrderId)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => '주문번호가 필요합니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        // payment_orders에서 주문 정보 조회
        $paymentOrder = db()->fetchOne(
            "SELECT order_id, order_name, amount, customer_name, customer_email, order_data, status 
             FROM payment_orders 
             WHERE order_id = ?",
            [$paymentOrderId]
        );
        
        if (!$paymentOrder) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => '결제 정보를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 이미 주문이 저장되었는지 확인
        $existingOrder = db()->fetchOne(
            "SELECT id FROM orders WHERE order_number = ?",
            [$paymentOrderId]
        );
        
        if ($existingOrder) {
            echo json_encode(['ok' => true, 'message' => '이미 저장된 주문입니다.', 'orderId' => $existingOrder['id']], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // order_data가 있으면 사용, 없으면 payment_orders의 기본 정보로 주문 생성 (하위 호환성)
        $orderData = null;
        if (!empty($paymentOrder['order_data'])) {
            $orderData = json_decode($paymentOrder['order_data'], true);
        }
        
        // order_data가 없으면 기본 정보로 주문 생성 (v1 SDK 사용 시)
        if (!$orderData) {
            // payment_orders의 기본 정보로 최소한의 주문 생성
            // 단, 이 경우 상품 정보가 없으므로 주문 상품은 저장하지 않음
            // 실제로는 프론트엔드에서 sessionStorage를 통해 주문 정보를 전달해야 함
            http_response_code(400);
            echo json_encode([
                'ok' => false, 
                'message' => '주문 정보를 찾을 수 없습니다. 페이지를 새로고침하지 말고 주문을 다시 진행해주세요.',
                'requiresSessionStorage' => true
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 주문 저장 (기존 POST 로직 재사용)
        $orderNumber = $paymentOrderId;
        $items = $orderData['items'] ?? [];
        $customer = $orderData['customer'] ?? [];
        $payment = $orderData['payment'] ?? [];
        $total = (int)($payment['total'] ?? $paymentOrder['amount'] ?? 0);
        
        if (!$orderNumber || empty($items) || $total <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주문 정보가 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 비회원 주문인 경우 이메일 필수
        $userId = $_SESSION['user_id'] ?? null;
        $isGuest = empty($userId);
        $guestEmail = $customer['email'] ?? $paymentOrder['customer_email'] ?? '';
        $guestSessionId = session_id();
        
        if ($isGuest && empty($guestEmail)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '비회원 주문은 이메일이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $conn = db()->getConnection();
        $conn->beginTransaction();
        
        // 주문 저장 로직 (기존 POST 로직과 동일)
        $paymentMethod = $payment['method'] ?? 'card';
        $orderStatus = ($paymentMethod === 'card') ? 'paid' : 'pending';
        
        // 재고 확인 및 주문 저장 (기존 로직 재사용)
        // ... (기존 POST 로직의 재고 확인 및 주문 저장 부분)
        // 여기서는 간단히 기존 POST 로직을 호출하는 대신 직접 처리
        
        // 재고 확인
        $stockErrors = [];
        $validatedItems = [];
        
        foreach ($items as $item) {
            $productId = (int)($item['id'] ?? 0);
            $productName = $item['name'] ?? '상품명 없음';
            $quantity = (int)($item['qty'] ?? $item['quantity'] ?? 1);
            $price = (int)($item['price'] ?? 0);
            $variantId = isset($item['variantId']) ? (int)$item['variantId'] : null;
            
            if ($productId <= 0) {
                $product = db()->fetchOne("SELECT id FROM products WHERE name = ? LIMIT 1", [$productName]);
                $productId = $product ? $product['id'] : 0;
            }
            
            if ($productId <= 0) {
                $stockErrors[] = "{$productName}: 상품을 찾을 수 없습니다.";
                continue;
            }
            
            if ($variantId) {
                $variant = db()->fetchOne("SELECT stock, volume FROM product_variants WHERE id = ?", [$variantId]);
                if (!$variant || (int)$variant['stock'] < $quantity) {
                    $variantVolume = isset($variant['volume']) ? $variant['volume'] : '';
                    $stockErrors[] = "{$productName} ({$variantVolume}): 재고가 부족합니다.";
                    continue;
                }
            } else {
                $product = db()->fetchOne("SELECT stock FROM products WHERE id = ?", [$productId]);
                if (!$product || (int)$product['stock'] < $quantity) {
                    $stockErrors[] = "{$productName}: 재고가 부족합니다.";
                    continue;
                }
            }
            
            $validatedItems[] = [
                'productId' => $productId,
                'productName' => $productName,
                'quantity' => $quantity,
                'price' => $price,
                'variantId' => $variantId
            ];
        }
        
        if (!empty($stockErrors)) {
            $conn->rollBack();
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '재고가 부족한 상품이 있습니다.', 'errors' => $stockErrors], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 주문 저장
        $orderId = db()->insert(
            "INSERT INTO orders (user_id, order_number, total_price, status, shipping_name, shipping_phone, shipping_address, guest_email, guest_session_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $userId,
                $orderNumber,
                $total,
                $orderStatus,
                $customer['name'] ?? $paymentOrder['customer_name'] ?? '',
                $customer['phone'] ?? '',
                $customer['address'] ?? '',
                $isGuest ? $guestEmail : null,
                $isGuest ? $guestSessionId : null
            ]
        );
        
        // 주문 상품 저장 및 재고 감소
        foreach ($validatedItems as $item) {
            db()->insert(
                "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, variant_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$orderId, $item['productId'], $item['productName'], $item['quantity'], $item['price'], $item['variantId']]
            );
            
            if ($item['variantId']) {
                db()->execute("UPDATE product_variants SET stock = stock - ? WHERE id = ?", [$item['quantity'], $item['variantId']]);
            } else {
                db()->execute("UPDATE products SET stock = stock - ? WHERE id = ?", [$item['quantity'], $item['productId']]);
            }
        }
        
        // 쿠폰 사용 처리
        $couponCode = $payment['coupon'] ?? null;
        $discountAmount = (int)($payment['discount'] ?? 0);
        
        if ($couponCode && $discountAmount > 0 && $userId) {
            $coupon = db()->fetchOne("SELECT id FROM coupons WHERE code = ? AND active = 1", [strtoupper(trim($couponCode))]);
            if ($coupon) {
                $couponId = (int)$coupon['id'];
                $alreadyUsed = db()->fetchOne("SELECT id FROM coupon_usages WHERE user_id = ? AND coupon_id = ?", [$userId, $couponId]);
                
                if (!$alreadyUsed) {
                    $updateResult = db()->execute(
                        "UPDATE user_coupons SET used = 1, used_at = NOW(), order_id = ? WHERE user_id = ? AND coupon_id = ? AND used = 0",
                        [$orderId, $userId, $couponId]
                    );
                    
                    if ($updateResult > 0) {
                        db()->insert(
                            "INSERT INTO coupon_usages (user_id, coupon_id, order_id, order_number, discount_amount) VALUES (?, ?, ?, ?, ?)",
                            [$userId, $couponId, $orderId, $orderNumber, $discountAmount]
                        );
                        db()->execute("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$couponId]);
                    }
                }
            }
        }
        
        $conn->commit();
        echo json_encode(['ok' => true, 'orderId' => $orderId, 'orderNumber' => $orderNumber], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        http_response_code(500);
        $errorMsg = '주문 저장 중 오류가 발생했습니다.';
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $errorMsg .= ' ' . $e->getMessage();
        }
        echo json_encode(['ok' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

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
            // 비회원 조회: 주문번호 필수 + 이메일 또는 전화번호 검증 (보안 강화)
            if (!$orderNumber) {
                // 주문번호가 없으면 접근 거부
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => '주문 조회를 위해 주문번호가 필요합니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $isGuestLookup = !empty($guestEmail) || !empty($guestPhone);
            
            if (!$isGuestLookup) {
                // 주문번호만으로는 조회 불가 (보안)
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => '주문 조회를 위해 주문번호와 이메일 또는 전화번호가 모두 필요합니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 비회원 조회: 주문번호 + 이메일 또는 전화번호로 조회 (둘 다 일치해야 함)
            $where[] = 'o.order_number = ?';
            $params[] = $orderNumber;
            
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
        $sql = "SELECT o.id, o.order_number, o.total_price, o.status, o.cancel_requested, o.cancel_reason, o.created_at, 
                       o.shipping_name, o.shipping_phone, o.shipping_address, o.user_id, o.guest_email, o.guest_session_id, 
                       u.name as user_name, u.email as user_email
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        // 모든 상태의 주문 조회 (취소된 주문 포함)
        $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

        // 디버깅: 쿼리 로그 (디버그 모드에서만)
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("주문 조회 SQL: " . $sql);
            error_log("주문 조회 파라미터: " . json_encode($params));
        }
        
        try {
            $rows = db()->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log("주문 조회 SQL 실행 오류: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '주문 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // $rows가 없으면 빈 배열로 초기화
        if (!is_array($rows)) {
            $rows = [];
        }

        // 추가 보안: 로그인한 사용자의 경우 반환된 주문이 모두 자신의 것인지 확인
        $currentUserId = $_SESSION['user_id'] ?? null;
        if (!empty($currentUserId) && !$isAdmin && !empty($rows)) {
            foreach ($rows as $row) {
                $orderUserId = $row['user_id'] ?? null;
                if ($orderUserId != $currentUserId) {
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("보안 경고: 사용자 ID {$currentUserId}가 다른 사용자(ID: {$orderUserId})의 주문을 조회하려고 시도함. 주문번호: " . (isset($row['order_number']) ? $row['order_number'] : 'N/A'));
                    }
                    // 다른 사용자의 주문은 제거
                    $rows = array_filter($rows, function($r) use ($currentUserId) {
                        return (isset($r['user_id']) ? $r['user_id'] : null) == $currentUserId;
                    });
                    $rows = array_values($rows); // 인덱스 재정렬
                    break;
                }
            }
        }
        
        // N+1 쿼리 방지: 모든 주문의 상품을 한 번에 조회
        $allOrderItems = [];
        if (!empty($rows) && is_array($rows)) {
            $orderIds = array_column($rows, 'id');
            if (!empty($orderIds) && is_array($orderIds)) {
                $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
                try {
                    $allOrderItemsRaw = db()->fetchAll(
                        "SELECT oi.order_id, oi.product_id, oi.product_name, oi.quantity, oi.price, oi.variant_id, 
                                p.image as product_image,
                                pv.volume as variant_volume
                         FROM order_items oi
                         LEFT JOIN products p ON oi.product_id = p.id
                         LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                         WHERE oi.order_id IN ($placeholders)
                         ORDER BY oi.order_id, oi.id",
                        $orderIds
                    );
                    
                    // order_id별로 그룹화
                    if (is_array($allOrderItemsRaw)) {
                        foreach ($allOrderItemsRaw as $item) {
                            $orderId = (int)$item['order_id'];
                            if (!isset($allOrderItems[$orderId])) {
                                $allOrderItems[$orderId] = [];
                            }
                            $allOrderItems[$orderId][] = [
                                'product_id' => (int)$item['product_id'],
                                'product_name' => $item['product_name'],
                                'quantity' => (int)$item['quantity'],
                                'price' => (int)$item['price'],
                                'variant_id' => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                                'product_image' => isset($item['product_image']) ? $item['product_image'] : null,
                                'variant_volume' => isset($item['variant_volume']) ? $item['variant_volume'] : null
                            ];
                        }
                    }
                } catch (Exception $e) {
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("주문 상품 일괄 조회 오류: " . $e->getMessage());
                    }
                }
            }
        }
        
        $orders = array_map(function ($row) use ($currentUserId, $isAdmin, $allOrderItems) {
            $orderId = $row['id'];
            $orderUserId = $row['user_id'] ?? null;
            
            // 추가 보안 체크: 로그인한 사용자는 자신의 주문만 볼 수 있음
            if (!empty($currentUserId) && !$isAdmin && $orderUserId != $currentUserId) {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("보안: 사용자 ID {$currentUserId}가 다른 사용자(ID: {$orderUserId})의 주문 접근 시도 차단");
                }
                return null; // 이 주문은 반환하지 않음
            }
            
            // 주문 상품 정보 가져오기 (일괄 조회한 데이터 사용 - N+1 쿼리 방지)
            $items = isset($allOrderItems[$orderId]) ? $allOrderItems[$orderId] : [];
            
            return [
                'id' => $row['order_number'] ?: (string) $row['id'],
                'total' => (int) ($row['total_price'] ?? 0),
                'status' => map_order_status($row['status'] ?? ''),
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

        // 입력값 검증
        if (!$orderNumber || strlen($orderNumber) > 100) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주문번호가 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (empty($items) || !is_array($items)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주문 상품이 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($total <= 0 || $total > 100000000) { // 최대 1억원
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주문 금액이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 고객 정보 검증
        $customerName = trim($customer['name'] ?? '');
        $customerPhone = trim($customer['phone'] ?? '');
        $customerAddress = trim($customer['address'] ?? '');
        
        if (empty($customerName) || strlen($customerName) > 50) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '이름을 올바르게 입력해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if (!empty($customerPhone)) {
            // 전화번호 형식 검증 (숫자, 하이픈, 공백, 괄호만 허용)
            $phoneDigits = preg_replace('/[^0-9]/', '', $customerPhone);
            if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 11) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => '전화번호 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        
        if (empty($customerAddress) || strlen($customerAddress) > 500) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '주소를 올바르게 입력해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 비회원 주문인 경우 이메일 필수 및 검증
        $userId = $_SESSION['user_id'] ?? null;
        $isGuest = empty($userId);
        $guestEmail = trim($customer['email'] ?? '');
        $guestSessionId = session_id();

        if ($isGuest) {
            if (empty($guestEmail)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => '비회원 주문은 이메일이 필요합니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 이메일 형식 검증
            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL) || strlen($guestEmail) > 255) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => '올바른 이메일 주소를 입력해주세요.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        
        // 상품 정보 검증
        foreach ($items as $item) {
            $quantity = (int)($item['qty'] ?? $item['quantity'] ?? 1);
            $price = (int)($item['price'] ?? 0);
            
            if ($quantity <= 0 || $quantity > 100) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => '상품 수량이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if ($price < 0 || $price > 10000000) { // 최대 1천만원
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => '상품 가격이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        try {
            $conn = db()->getConnection();
            $conn->beginTransaction();

            // 1단계: 모든 상품의 재고 확인 (주문 저장 전)
            $stockErrors = [];
            $validatedItems = [];
            
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

                if ($productId <= 0) {
                    $stockErrors[] = "{$productName}: 상품을 찾을 수 없습니다.";
                    continue;
                }

                // 재고 확인 (variant가 있으면 variant 재고, 없으면 상품 재고)
                if ($variantId) {
                    $variant = db()->fetchOne(
                        "SELECT stock, volume FROM product_variants WHERE id = ?",
                        [$variantId]
                    );
                    
                    if (!$variant) {
                        $stockErrors[] = "{$productName}: 선택한 옵션을 찾을 수 없습니다.";
                        continue;
                    }
                    
                    $currentStock = (int)$variant['stock'];
                    if ($currentStock < $quantity) {
                        $stockErrors[] = "{$productName} ({$variant['volume']}): 재고가 부족합니다. (재고: {$currentStock}개, 주문: {$quantity}개)";
                        continue;
                    }
                } else {
                    $product = db()->fetchOne(
                        "SELECT stock FROM products WHERE id = ?",
                        [$productId]
                    );
                    
                    if (!$product) {
                        $stockErrors[] = "{$productName}: 상품을 찾을 수 없습니다.";
                        continue;
                    }
                    
                    $currentStock = (int)$product['stock'];
                    if ($currentStock < $quantity) {
                        $stockErrors[] = "{$productName}: 재고가 부족합니다. (재고: {$currentStock}개, 주문: {$quantity}개)";
                        continue;
                    }
                }
                
                // 재고가 충분한 경우 validatedItems에 추가
                $validatedItems[] = [
                    'productId' => $productId,
                    'productName' => $productName,
                    'quantity' => $quantity,
                    'price' => $price,
                    'variantId' => $variantId
                ];
            }
            
            // 재고 부족 상품이 있으면 주문 거부
            if (!empty($stockErrors)) {
                $conn->rollBack();
                http_response_code(400);
                echo json_encode([
                    'ok' => false, 
                    'message' => '재고가 부족한 상품이 있습니다.',
                    'errors' => $stockErrors
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 2단계: 주문 저장 (재고 확인 완료 후)
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

            // 3단계: 주문 상품 저장 및 재고 감소 (재고 확인 완료된 상품만)
            foreach ($validatedItems as $item) {
                $productId = $item['productId'];
                $productName = $item['productName'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $variantId = $item['variantId'];

                // 주문 상품 저장
                db()->insert(
                    "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, variant_id, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())",
                    [$orderId, $productId, $productName, $quantity, $price, $variantId]
                );

                // 재고 감소 (재고 확인 완료된 상품이므로 안전하게 감소)
                if ($variantId) {
                    $stockUpdateResult = db()->execute(
                        "UPDATE product_variants SET stock = stock - ? WHERE id = ?",
                        [$quantity, $variantId]
                    );
                    
                    if ($stockUpdateResult === 0) {
                        throw new Exception("재고 감소 실패: variantId={$variantId}");
                    }
                } else {
                    $stockUpdateResult = db()->execute(
                        "UPDATE products SET stock = stock - ? WHERE id = ?",
                        [$quantity, $productId]
                    );
                    
                    if ($stockUpdateResult === 0) {
                        throw new Exception("재고 감소 실패: productId={$productId}");
                    }
                }
            }

            // 쿠폰 사용 처리 (주문 저장과 함께 처리하여 확실하게)
            $couponCode = $payment['coupon'] ?? null;
            $discountAmount = (int)($payment['discount'] ?? 0);
            
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("[Orders API] 쿠폰 사용 처리 시작: couponCode=" . ($couponCode ?? 'NULL') . ", discountAmount={$discountAmount}, userId=" . ($userId ?? 'NULL') . ", orderId={$orderId}");
            }
            
            if ($couponCode && $discountAmount > 0 && $userId) {
                try {
                    // 쿠폰 코드로 쿠폰 ID 찾기
                    $coupon = db()->fetchOne(
                        "SELECT id FROM coupons WHERE code = ? AND active = 1",
                        [strtoupper(trim($couponCode))]
                    );
                    
                    if ($coupon) {
                        $couponId = (int)$coupon['id'];
                        
                        // 이미 사용된 쿠폰인지 확인
                        $alreadyUsed = db()->fetchOne(
                            "SELECT id FROM coupon_usages 
                             WHERE user_id = ? AND coupon_id = ?",
                            [$userId, $couponId]
                        );
                        
                        if ($alreadyUsed) {
                            if (defined('APP_DEBUG') && APP_DEBUG) {
                                error_log("[Orders API] 쿠폰 이미 사용됨: userId={$userId}, couponId={$couponId}");
                            }
                        } else {
                            // user_coupons 업데이트 (used = 0인 것만 업데이트하여 중복 방지)
                            $updateResult = db()->execute(
                                "UPDATE user_coupons SET used = 1, used_at = NOW(), order_id = ? 
                                 WHERE user_id = ? AND coupon_id = ? AND used = 0",
                                [$orderId, $userId, $couponId]
                            );
                            
                            if ($updateResult > 0) {
                                // coupon_usages에 기록 (중복 사용 방지)
                                try {
                                    $usageId = db()->insert(
                                        "INSERT INTO coupon_usages (user_id, coupon_id, order_id, order_number, discount_amount) 
                                         VALUES (?, ?, ?, ?, ?)",
                                        [$userId, $couponId, $orderId, $orderNumber, $discountAmount]
                                    );
                                    if (defined('APP_DEBUG') && APP_DEBUG) {
                                        error_log("[Orders API] 쿠폰 사용 처리 성공: usageId={$usageId}, userId={$userId}, couponId={$couponId}, orderId={$orderId}");
                                    }
                                } catch (Exception $e) {
                                    error_log("[Orders API] coupon_usages 기록 실패: " . $e->getMessage());
                                }
                                
                                // coupons의 used_count 증가
                                db()->execute(
                                    "UPDATE coupons SET used_count = used_count + 1 WHERE id = ?",
                                    [$couponId]
                                );
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("[Orders API] 쿠폰 사용 처리 오류: " . $e->getMessage());
                    // 쿠폰 사용 실패해도 주문은 계속 진행
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
        $statusEn = reverse_map_order_status($newStatus);
        $allowedStatuses = ['pending', 'paid', 'preparing', 'shipping', 'delivered', 'cancelled'];
        
        if (!in_array($statusEn, $allowedStatuses)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '올바르지 않은 상태입니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // order_number로 주문 찾기
            $order = db()->fetchOne(
                "SELECT id, status FROM orders WHERE order_number = ?",
                [$orderNumber]
            );

            if (!$order) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => '주문을 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $oldStatus = $order['status'];
            
            // 상태 업데이트
            db()->execute(
                "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?",
                [$statusEn, $order['id']]
            );
            
            // 취소 상태로 변경되는 경우 재고 복구
            if ($statusEn === 'cancelled' && $oldStatus !== 'cancelled') {
                restoreOrderStock($order['id']);
            }

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
                        // 결제대기 상태: 즉시 취소 및 재고 복구
                        try {
                            $conn = db()->getConnection();
                            $conn->beginTransaction();
                            
                            // 주문 상태 변경
                            db()->execute(
                                "UPDATE orders SET status = 'cancelled', cancel_reason = ?, updated_at = NOW() WHERE id = ?",
                                [$reason, $order['id']]
                            );
                            
                            // 재고 복구
                            restoreOrderStock($order['id']);
                            
                            $conn->commit();
                            echo json_encode(['ok' => true, 'message' => '주문이 취소되었습니다.'], JSON_UNESCAPED_UNICODE);
                        } catch (Exception $e) {
                            if (isset($conn)) {
                                $conn->rollBack();
                            }
                            error_log('[Orders API] 주문 취소 실패: ' . $e->getMessage());
                            http_response_code(500);
                            echo json_encode(['ok' => false, 'message' => '주문 취소 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
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

                    try {
                        $conn = db()->getConnection();
                        $conn->beginTransaction();
                        
                        // 주문 상태 변경
                        db()->execute(
                            "UPDATE orders SET status = 'cancelled', cancel_requested = 0, updated_at = NOW() WHERE id = ?",
                            [$order['id']]
                        );
                        
                        // 재고 복구
                        restoreOrderStock($order['id']);
                        
                        $conn->commit();
                        echo json_encode(['ok' => true, 'message' => '주문 취소가 승인되었습니다.'], JSON_UNESCAPED_UNICODE);
                    } catch (Exception $e) {
                        if (isset($conn)) {
                            $conn->rollBack();
                        }
                        error_log('[Orders API] 취소 승인 실패: ' . $e->getMessage());
                        http_response_code(500);
                        echo json_encode(['ok' => false, 'message' => '취소 승인 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
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
