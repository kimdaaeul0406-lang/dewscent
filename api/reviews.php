<?php
// 에러 리포팅 (배포 서버 디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

error_log('[Reviews API] ========== 요청 시작 ==========');
error_log('[Reviews API] Request URI: ' . $_SERVER['REQUEST_URI']);
error_log('[Reviews API] Request Method: ' . $_SERVER['REQUEST_METHOD']);

error_log('[Reviews API] Session ID: ' . session_id());

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 자동 생성
try {
    ensure_tables_exist();
    error_log('[Reviews API] Tables ensured');
} catch (Exception $e) {
    error_log('[Reviews API] Table creation error: ' . $e->getMessage());
}

$method = $_SERVER['REQUEST_METHOD'];
// URL에서 product_id 추출: ?product_id=123
$productId = !empty($_GET['product_id']) ? (int)$_GET['product_id'] : null;
error_log('[Reviews API] product_id: ' . ($productId ?? 'null (관리자 모드)'));

switch ($method) {
    case 'GET':
        // 리뷰 목록 조회
        if ($productId) {
            // 특정 상품의 리뷰 목록
            $reviews = db()->fetchAll(
                "SELECT r.*, u.name as user_name, u.email as user_email
                 FROM reviews r
                 LEFT JOIN users u ON r.user_id = u.id
                 WHERE r.product_id = ?
                 ORDER BY r.created_at DESC",
                [$productId]
            );
        } else {
            // 모든 리뷰 (관리자용)
            // 두 가지 방식 모두 지원: admin_logged_in 또는 role = 'admin'
            $isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
                       !empty($_SESSION['admin_logged_in']);
            
            error_log('[Reviews API] 관리자 모드 체크: isAdmin=' . ($isAdmin ? 'true' : 'false'));
            error_log('[Reviews API] role: ' . ($_SESSION['role'] ?? 'not set'));
            error_log('[Reviews API] admin_logged_in: ' . (isset($_SESSION['admin_logged_in']) ? ($_SESSION['admin_logged_in'] ? 'true' : 'false') : 'not set'));
            
            if (!$isAdmin) {
                http_response_code(401);
                error_log('[Reviews API] Access denied - not admin');
                echo json_encode(['ok' => false, 'message' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            try {
                error_log('[Reviews API] DB query 시작 (전체 리뷰)');
                $startTime = microtime(true);
                
                $reviews = db()->fetchAll(
                    "SELECT r.*, u.name as user_name, u.email as user_email, p.name as product_name
                     FROM reviews r
                     LEFT JOIN users u ON r.user_id = u.id
                     LEFT JOIN products p ON r.product_id = p.id
                     ORDER BY r.created_at DESC"
                );
                
                $queryTime = microtime(true) - $startTime;
                error_log('[Reviews API] DB query 완료: ' . round($queryTime * 1000, 2) . 'ms, Reviews count: ' . count($reviews));
            } catch (Exception $e) {
                http_response_code(500);
                error_log('[Reviews API] DB query error: ' . $e->getMessage());
                error_log('[Reviews API] Stack trace: ' . $e->getTraceAsString());
                echo json_encode(['ok' => false, 'message' => '리뷰 목록 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // 날짜 포맷팅 및 사용자 정보 추가
        foreach ($reviews as &$review) {
            if (!empty($review['created_at'])) {
                $review['date'] = substr($review['created_at'], 0, 10);
            }
            $review['user'] = $review['user_name'] ?? '익명';
            $review['userId'] = $review['user_email'] ?? '';
        }

        error_log('[Reviews API] 응답 전송: ' . count($reviews) . '개 리뷰');
        echo json_encode($reviews, JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // 리뷰 작성
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!$productId) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '상품 ID가 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            $data = $_POST;
        }

        $rating = (int)($data['rating'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '별점은 1-5 사이여야 합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (strlen($content) < 10) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '리뷰 내용은 10자 이상 입력해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 이미 리뷰를 작성했는지 확인
        $existing = db()->fetchOne(
            "SELECT id FROM reviews WHERE user_id = ? AND product_id = ?",
            [$_SESSION['user_id'], $productId]
        );

        try {
            if ($existing) {
                // 기존 리뷰 수정
                db()->execute(
                    "UPDATE reviews SET rating = ?, content = ?, updated_at = NOW() WHERE id = ?",
                    [$rating, $content, $existing['id']]
                );
                $reviewId = $existing['id'];
            } else {
                // 새 리뷰 작성
                $reviewId = db()->insert(
                    "INSERT INTO reviews (user_id, product_id, rating, content, created_at)
                     VALUES (?, ?, ?, ?, NOW())",
                    [$_SESSION['user_id'], $productId, $rating, $content]
                );
            }

            // 상품의 평균 평점 및 리뷰 수 업데이트
            $avgRating = db()->fetchOne(
                "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = ?",
                [$productId]
            );

            if ($avgRating) {
                db()->execute(
                    "UPDATE products SET rating = ?, reviews = ? WHERE id = ?",
                    [
                        round($avgRating['avg_rating'], 1),
                        (int)$avgRating['count'],
                        $productId
                    ]
                );
            }

            $newReview = db()->fetchOne(
                "SELECT r.*, u.name as user_name, u.email as user_email
                 FROM reviews r
                 LEFT JOIN users u ON r.user_id = u.id
                 WHERE r.id = ?",
                [$reviewId]
            );

            if ($newReview) {
                $newReview['date'] = substr($newReview['created_at'], 0, 10);
                $newReview['user'] = $newReview['user_name'] ?? '익명';
                $newReview['userId'] = $newReview['user_email'] ?? '';
            }

            echo json_encode(['ok' => true, 'review' => $newReview], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '리뷰 등록 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        // 리뷰 삭제
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!$productId) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => '상품 ID가 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $reviewId = !empty($_GET['review_id']) ? (int)$_GET['review_id'] : null;
        $isAdmin = !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';

        try {
            // 관리자는 모든 리뷰 삭제 가능, 일반 사용자는 자신의 리뷰만 삭제 가능
            if ($reviewId) {
                // 특정 리뷰 삭제
                if ($isAdmin) {
                    db()->execute("DELETE FROM reviews WHERE id = ? AND product_id = ?", [$reviewId, $productId]);
                } else {
                    db()->execute(
                        "DELETE FROM reviews WHERE id = ? AND user_id = ? AND product_id = ?",
                        [$reviewId, $_SESSION['user_id'], $productId]
                    );
                }
            } else {
                // 해당 상품의 모든 리뷰 삭제 (관리자만)
                if ($isAdmin) {
                    db()->execute("DELETE FROM reviews WHERE product_id = ?", [$productId]);
                } else {
                    db()->execute(
                        "DELETE FROM reviews WHERE user_id = ? AND product_id = ?",
                        [$_SESSION['user_id'], $productId]
                    );
                }
            }

            // 상품의 평균 평점 및 리뷰 수 업데이트
            $avgRating = db()->fetchOne(
                "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = ?",
                [$productId]
            );

            if ($avgRating) {
                db()->execute(
                    "UPDATE products SET rating = ?, reviews = ? WHERE id = ?",
                    [
                        $avgRating['count'] > 0 ? round($avgRating['avg_rating'], 1) : 0,
                        (int)$avgRating['count'],
                        $productId
                    ]
                );
            }

            echo json_encode(['ok' => true, 'message' => '리뷰가 삭제되었습니다.'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => '리뷰 삭제 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}

