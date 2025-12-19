<?php
// api/products.php
// 상품 CRUD API

// 세션 시작 (인증 체크용)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// CORS 설정 - 같은 도메인만 허용 (프로덕션용)
$allowed_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (strpos($allowed_origin, 'localhost') !== false || $allowed_origin === '') {
    header('Access-Control-Allow-Origin: ' . ($allowed_origin ?: '*'));
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

// 테이블 자동 생성
ensure_tables_exist();

// 에러 리포팅 (배포 서버 디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$pathParts = $path ? explode('/', $path) : [];

// ID 추출 (있으면)
$id = isset($pathParts[0]) && is_numeric($pathParts[0]) ? (int)$pathParts[0] : null;

// 상품에 variants 추가하는 헬퍼 함수
function addVariantsToProduct($product) {
    if (!$product) return $product;
    try {
        $variants = db()->fetchAll(
            "SELECT id, volume, price, stock, is_default, sort_order
             FROM product_variants
             WHERE product_id = ?
             ORDER BY sort_order ASC, price ASC",
            [$product['id']]
        );
        $product['variants'] = $variants ?: [];
    } catch (Exception $e) {
        // variants 테이블이 없으면 빈 배열 반환
        $product['variants'] = [];
    }
    // type 필드를 category로도 매핑 (프론트엔드 호환성)
    if (isset($product['type']) && !isset($product['category'])) {
        $product['category'] = $product['type'];
    }
    // emotion_keys를 배열로 변환 (JSON 문자열인 경우)
    if (isset($product['emotion_keys']) && is_string($product['emotion_keys']) && !empty($product['emotion_keys'])) {
        try {
            $product['emotionKeys'] = json_decode($product['emotion_keys'], true);
            if (!is_array($product['emotionKeys'])) {
                $product['emotionKeys'] = [];
            }
        } catch (Exception $e) {
            $product['emotionKeys'] = [];
        }
    } else {
        $product['emotionKeys'] = [];
    }
    // fragrance_type을 fragranceType으로도 매핑
    if (isset($product['fragrance_type']) && !isset($product['fragranceType'])) {
        $product['fragranceType'] = $product['fragrance_type'];
    }
    // image를 imageUrl로도 매핑 (프론트엔드 호환성) - NULL이나 빈 문자열이 아닐 때만
    if (isset($product['image']) && $product['image'] !== null && $product['image'] !== '') {
        $trimmedImage = trim($product['image']);
        if ($trimmedImage !== '' && $trimmedImage !== 'null' && $trimmedImage !== 'NULL' && strlen($trimmedImage) > 10 && !isset($product['imageUrl'])) {
            $product['imageUrl'] = $trimmedImage;
        }
    }
    // detail_image를 detailImageUrl로도 매핑 (프론트엔드 호환성) - NULL이나 빈 문자열이 아닐 때만
    if (isset($product['detail_image']) && $product['detail_image'] !== null && $product['detail_image'] !== '') {
        $trimmedDetailImage = trim($product['detail_image']);
        if ($trimmedDetailImage !== '' && $trimmedDetailImage !== 'null' && $trimmedDetailImage !== 'NULL' && strlen($trimmedDetailImage) > 10 && !isset($product['detailImageUrl'])) {
            $product['detailImageUrl'] = $trimmedDetailImage;
        }
    }
    return $product;
}

// 관리자 API 체크 (GET 요청은 public, 나머지는 관리자만)
if ($method !== 'GET') {
    require_once __DIR__ . '/../admin/guard.php';
}

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // 단일 상품 조회 (variants 포함) - 인덱스 활용
                $product = db()->fetchOne("SELECT * FROM products WHERE id = ? LIMIT 1", [$id]);
                if ($product) {
                    $product = addVariantsToProduct($product);
                    echo json_encode($product);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                }
            } else {
                // 쿼리 실행 시간 측정 시작
                $startTime = microtime(true);
                
                // 전체 상품 목록 조회 (판매중 상품만, 상태 인덱스 활용)
                // 페이징 지원
                $status = $_GET['status'] ?? null;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $query = "SELECT id, name, type, price, originalPrice, rating, reviews, badge, `desc`, 
                         image, detail_image, stock, status, fragrance_type, emotion_keys, created_at, updated_at
                         FROM products";
                $params = [];
                
                // 판매중 상품만 필터링 (status 파라미터가 없으면 기본적으로 판매중만)
                // 인덱스 활용을 위해 status 조건 먼저
                // 주의: stock 조건은 없음 (테스트/전시 목적상 stock=0이어도 표시)
                if ($status) {
                    $query .= " WHERE status = ?";
                    $params[] = $status;
                } else {
                    // 프론트엔드에서는 기본적으로 판매중 상품만 (인덱스 활용)
                    $query .= " WHERE status = '판매중'";
                }
                
                // stock 조건은 제거 (테스트/전시 목적상 stock=0이어도 표시)
                // 이전에 stock > 0 조건이 있었다면 제거됨
                
                $query .= " ORDER BY id DESC";
                
                // LIMIT 적용 (N+1 방지 및 성능 최적화)
                if ($limit !== null && $limit > 0) {
                    $query .= " LIMIT ? OFFSET ?";
                    $params[] = $limit;
                    $params[] = $offset;
                }
                
                // DB 연결 확인 및 전체 상품 수 확인 (디버깅)
                try {
                    $totalCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM products");
                    $totalProducts = (int)($totalCount['cnt'] ?? 0);
                    error_log('[Products API] DB 연결 성공 - 전체 상품 수: ' . $totalProducts);
                    
                    // status별 분포 확인 (디버깅)
                    $statusDistribution = db()->fetchAll("SELECT status, COUNT(*) as c FROM products GROUP BY status");
                    $statusInfo = [];
                    foreach ($statusDistribution as $row) {
                        $statusInfo[] = $row['status'] . ':' . $row['c'];
                    }
                    error_log('[Products API] Status 분포: ' . implode(', ', $statusInfo));
                    
                    // '판매중' 상품 수 확인
                    $sellingCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM products WHERE status = '판매중'");
                    $sellingProducts = (int)($sellingCount['cnt'] ?? 0);
                    error_log('[Products API] 판매중 상품 수: ' . $sellingProducts);
                } catch (Exception $e) {
                    error_log('[Products API] DB 연결/통계 확인 실패: ' . $e->getMessage());
                }
                
                error_log('[Products API] Query: ' . $query);
                error_log('[Products API] Params: ' . json_encode($params));
                
                $queryStart = microtime(true);
                try {
                    $products = db()->fetchAll($query, $params);
                    $queryTime = microtime(true) - $queryStart;
                    $rowCount = count($products);
                    error_log('[Products API] Main query time: ' . round($queryTime * 1000, 2) . 'ms, Products count: ' . $rowCount);
                    
                    // 결과가 빈 배열일 때 상세 로그
                    if ($rowCount === 0) {
                        error_log('[Products API] 경고: 조회 결과가 0개입니다.');
                        error_log('[Products API] - 전체 상품 수: ' . ($totalProducts ?? 'unknown'));
                        error_log('[Products API] - 판매중 상품 수: ' . ($sellingProducts ?? 'unknown'));
                        error_log('[Products API] - 쿼리 조건: status = "판매중" (stock 조건 없음)');
                        
                        // stock 분포도 확인 (디버깅)
                        try {
                            $stockDistribution = db()->fetchAll("SELECT CASE WHEN stock = 0 THEN 'stock=0' WHEN stock > 0 THEN 'stock>0' ELSE 'stock<0' END as stock_group, COUNT(*) as c FROM products WHERE status = '판매중' GROUP BY stock_group");
                            $stockInfo = [];
                            foreach ($stockDistribution as $row) {
                                $stockInfo[] = $row['stock_group'] . ':' . $row['c'];
                            }
                            error_log('[Products API] 판매중 상품 stock 분포: ' . implode(', ', $stockInfo));
                        } catch (Exception $e) {
                            error_log('[Products API] Stock 분포 확인 실패: ' . $e->getMessage());
                        }
                    }
                } catch (Exception $e) {
                    $queryTime = microtime(true) - $queryStart;
                    error_log('[Products API] DB query error: ' . $e->getMessage());
                    error_log('[Products API] Stack trace: ' . $e->getTraceAsString());
                    http_response_code(500);
                    echo json_encode(['ok' => false, 'error' => '상품 목록 조회 중 오류가 발생했습니다.', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // N+1 문제 해결: 모든 variants를 한 번에 조회
                $variantStart = microtime(true);
                $productIds = array_column($products, 'id');
                $allVariants = [];
                if (!empty($productIds)) {
                    try {
                        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                        $variantQuery = "SELECT id, product_id, volume, price, stock, is_default, sort_order
                                         FROM product_variants
                                         WHERE product_id IN ($placeholders)
                                         ORDER BY product_id ASC, sort_order ASC, price ASC";
                        error_log('[Products API] Variants query: ' . $variantQuery);
                        error_log('[Products API] Variants params: ' . json_encode($productIds));
                        $allVariants = db()->fetchAll($variantQuery, $productIds);
                    } catch (Exception $e) {
                        error_log('[Products API] Variants query error: ' . $e->getMessage());
                        error_log('[Products API] Variants stack trace: ' . $e->getTraceAsString());
                        // variants 조회 실패해도 상품 목록은 반환 (variants는 빈 배열로)
                        $allVariants = [];
                    }
                }
                
                // variants를 product_id로 그룹화
                $variantsByProduct = [];
                foreach ($allVariants as $variant) {
                    $pid = $variant['product_id'];
                    if (!isset($variantsByProduct[$pid])) {
                        $variantsByProduct[$pid] = [];
                    }
                    $variantsByProduct[$pid][] = $variant;
                }
                $variantTime = microtime(true) - $variantStart;
                error_log('[Products API] Variants query time: ' . round($variantTime * 1000, 2) . 'ms, Variants count: ' . count($allVariants));
                
                // variants를 각 상품에 추가 (DB 쿼리 없이)
                foreach ($products as &$p) {
                    $p['variants'] = $variantsByProduct[$p['id']] ?? [];
                    // 다른 필드 매핑 (기존 addVariantsToProduct 로직)
                    if (isset($p['type']) && !isset($p['category'])) {
                        $p['category'] = $p['type'];
                    }
                    if (isset($p['emotion_keys']) && is_string($p['emotion_keys']) && !empty($p['emotion_keys'])) {
                        try {
                            $p['emotionKeys'] = json_decode($p['emotion_keys'], true);
                            if (!is_array($p['emotionKeys'])) {
                                $p['emotionKeys'] = [];
                            }
                        } catch (Exception $e) {
                            $p['emotionKeys'] = [];
                        }
                    } else {
                        $p['emotionKeys'] = [];
                    }
                    if (isset($p['fragrance_type']) && !isset($p['fragranceType'])) {
                        $p['fragranceType'] = $p['fragrance_type'];
                    }
                    if (isset($p['image']) && $p['image'] !== null && $p['image'] !== '') {
                        $trimmedImage = trim($p['image']);
                        if ($trimmedImage !== '' && $trimmedImage !== 'null' && $trimmedImage !== 'NULL' && strlen($trimmedImage) > 10 && !isset($p['imageUrl'])) {
                            $p['imageUrl'] = $trimmedImage;
                        }
                    }
                    if (isset($p['detail_image']) && $p['detail_image'] !== null && $p['detail_image'] !== '') {
                        $trimmedDetailImage = trim($p['detail_image']);
                        if ($trimmedDetailImage !== '' && $trimmedDetailImage !== 'null' && $trimmedDetailImage !== 'NULL' && strlen($trimmedDetailImage) > 10 && !isset($p['detailImageUrl'])) {
                            $p['detailImageUrl'] = $trimmedDetailImage;
                        }
                    }
                }
                
                $totalTime = microtime(true) - $startTime;
                error_log('[Products API] Total time: ' . round($totalTime * 1000, 2) . 'ms, Products: ' . count($products));
                
                echo json_encode($products);
            }
            break;

        case 'POST':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            // 상품 등록
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => '상품명은 필수입니다.']);
                exit;
            }

            // category/type 값 검증 및 정리
            $category = $data['category'] ?? $data['type'] ?? '향수';
            $validCategories = ['향수', '바디미스트', '헤어미스트', '디퓨저', '섬유유연제', '룸스프레이'];
            // category가 너무 길거나 유효하지 않으면 기본값 사용
            if (strlen($category) > 50 || !in_array($category, $validCategories)) {
                $category = '향수';
            }

            // price는 variants의 기본 가격이 있으면 사용, 없으면 0 (호환성 유지)
            $price = isset($data['price']) && $data['price'] > 0 ? (int)$data['price'] : 0;

            // 향기 타입 및 감정 키 처리
            $fragranceType = !empty($data['fragranceType']) ? trim($data['fragranceType']) : null;
            $emotionKeys = null;
            if (!empty($data['emotionKeys']) && is_array($data['emotionKeys'])) {
                $emotionKeys = json_encode($data['emotionKeys'], JSON_UNESCAPED_UNICODE);
            } elseif (!empty($data['emotionKeys']) && is_string($data['emotionKeys'])) {
                $emotionKeys = $data['emotionKeys'];
            }

            // 이미지 데이터 처리: 빈 문자열이면 NULL로
            $imageData = $data['imageUrl'] ?? $data['image'] ?? null;
            if ($imageData !== null) {
                $imageData = trim($imageData);
                if ($imageData === '' || $imageData === 'null' || $imageData === 'NULL') {
                    $imageData = null;
                }
            }
            
            $detailImageData = $data['detailImageUrl'] ?? $data['detail_image'] ?? null;
            if ($detailImageData !== null) {
                $detailImageData = trim($detailImageData);
                if ($detailImageData === '' || $detailImageData === 'null' || $detailImageData === 'NULL') {
                    $detailImageData = null;
                }
            }

            // status 값 처리: 기본값 '판매중' 보장
            $status = !empty($data['status']) ? trim($data['status']) : '판매중';
            // 유효한 status 값 검증
            $validStatuses = ['판매중', '품절', '숨김'];
            if (!in_array($status, $validStatuses)) {
                $status = '판매중'; // 기본값으로 강제 설정
            }
            
            // stock 값 처리: 기본값 10 (테스트/전시 목적)
            $stock = isset($data['stock']) ? (int)$data['stock'] : 10;
            if ($stock < 0) {
                $stock = 10; // 음수면 기본값으로 설정
            }
            
            error_log('[Products API] 상품 등록 시작 - name: ' . $data['name'] . ', status: ' . $status . ', stock: ' . $stock);
            
            $sql = "INSERT INTO products (name, type, price, originalPrice, rating, reviews, badge, `desc`, image, detail_image, stock, status, fragrance_type, emotion_keys)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $data['name'],
                $category,
                $price,
                isset($data['originalPrice']) ? (int)$data['originalPrice'] : null,
                $data['rating'] ?? 0,
                $data['reviews'] ?? 0,
                $data['badge'] ?? null,
                $data['desc'] ?? '',
                $imageData,
                $detailImageData,
                $stock, // stock 값 추가 (기본값 10)
                $status, // 검증된 status 값 사용
                $fragranceType,
                $emotionKeys
            ];

            try {
                $newId = db()->insert($sql, $params);
                error_log('[Products API] 상품 INSERT 성공 - id: ' . $newId . ', name: ' . $data['name']);
                
                $newProduct = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$newId]);
                if ($newProduct) {
                    error_log('[Products API] 등록된 상품 확인 - id: ' . $newProduct['id'] . ', name: ' . $newProduct['name'] . ', status: ' . ($newProduct['status'] ?? 'not set'));
                    $newProduct = addVariantsToProduct($newProduct);
                } else {
                    error_log('[Products API] 경고: INSERT 후 상품 조회 실패 - id: ' . $newId);
                }
                
                http_response_code(201);
                echo json_encode($newProduct);
            } catch (Exception $e) {
                error_log('[Products API] 상품 INSERT 실패 - name: ' . $data['name'] . ', error: ' . $e->getMessage());
                error_log('[Products API] SQL: ' . $sql);
                error_log('[Products API] Params: ' . json_encode($params));
                throw $e;
            }
            break;

        case 'PUT':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            // 상품 수정
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => '상품 ID가 필요합니다.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // 기존 상품 확인
            $existing = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
            if (!$existing) {
                http_response_code(404);
                echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                exit;
            }

            // category/type 값 검증 및 정리
            $category = $data['category'] ?? $data['type'] ?? $existing['type'];
            // 유효한 카테고리 목록
            $validCategories = ['향수', '바디미스트', '헤어미스트', '디퓨저', '섬유유연제', '룸스프레이'];
            // category가 너무 길거나 유효하지 않으면 기본값 사용
            if (strlen($category) > 50 || !in_array($category, $validCategories)) {
                $category = $existing['type'] ?? '향수';
            }

            // stock 값 처리 (수정 시에는 기존 값 유지, 명시적으로 전달되면 업데이트)
            $stock = isset($data['stock']) ? (int)$data['stock'] : ($existing['stock'] ?? 10);
            if ($stock < 0) {
                $stock = $existing['stock'] ?? 10; // 음수면 기존 값 또는 기본값 사용
            }

            // 향기 타입 및 감정 키 처리
            $fragranceType = isset($data['fragranceType']) ? (!empty($data['fragranceType']) ? trim($data['fragranceType']) : null) : $existing['fragrance_type'];
            $emotionKeys = null;
            if (isset($data['emotionKeys'])) {
                if (is_array($data['emotionKeys']) && count($data['emotionKeys']) > 0) {
                    $emotionKeys = json_encode($data['emotionKeys'], JSON_UNESCAPED_UNICODE);
                } elseif (is_string($data['emotionKeys']) && !empty($data['emotionKeys'])) {
                    $emotionKeys = $data['emotionKeys'];
                } else {
                    $emotionKeys = null;
                }
            } else {
                $emotionKeys = $existing['emotion_keys'] ?? null;
            }

            // stock 값 처리 (수정 시에는 기존 값 유지, 명시적으로 전달되면 업데이트)
            $stock = isset($data['stock']) ? (int)$data['stock'] : ($existing['stock'] ?? 10);
            if ($stock < 0) {
                $stock = $existing['stock'] ?? 10; // 음수면 기존 값 또는 기본값 사용
            }
            
            $sql = "UPDATE products SET 
                    name = ?,
                    type = ?,
                    price = ?,
                    originalPrice = ?,
                    rating = ?,
                    reviews = ?,
                    badge = ?,
                    `desc` = ?,
                    image = ?,
                    detail_image = ?,
                    stock = ?,
                    status = ?,
                    fragrance_type = ?,
                    emotion_keys = ?
                    WHERE id = ?";
            // price는 variants의 기본 가격이 있으면 사용, 없으면 기존 값 유지 (호환성)
            $price = isset($data['price']) && $data['price'] > 0 ? (int)$data['price'] : ($existing['price'] ?? 0);

            // 이미지 데이터 처리: 빈 문자열이면 NULL로, 아니면 trim
            $imageData = $data['imageUrl'] ?? $data['image'] ?? null;
            if ($imageData !== null) {
                $imageData = trim($imageData);
                if ($imageData === '' || $imageData === 'null' || $imageData === 'NULL') {
                    $imageData = $existing['image'] ?? null;
                }
            } else {
                $imageData = $existing['image'] ?? null;
            }
            
            $detailImageData = $data['detailImageUrl'] ?? $data['detail_image'] ?? null;
            if ($detailImageData !== null) {
                $detailImageData = trim($detailImageData);
                if ($detailImageData === '' || $detailImageData === 'null' || $detailImageData === 'NULL') {
                    $detailImageData = $existing['detail_image'] ?? null;
                }
            } else {
                $detailImageData = $existing['detail_image'] ?? null;
            }

            $params = [
                trim($data['name'] ?? $existing['name']),
                $category,
                $price,
                isset($data['originalPrice']) ? (int)$data['originalPrice'] : $existing['originalPrice'],
                $data['rating'] ?? $existing['rating'],
                $data['reviews'] ?? $existing['reviews'],
                $data['badge'] ?? $existing['badge'],
                $data['desc'] ?? $existing['desc'],
                $imageData,
                $detailImageData,
                $stock, // stock 값 추가
                $data['status'] ?? $existing['status'] ?? '판매중',
                $fragranceType,
                $emotionKeys,
                $id
            ];

            try {
                db()->execute($sql, $params);
                $updated = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
                $updated = addVariantsToProduct($updated);
                echo json_encode($updated);
            } catch (Exception $e) {
                error_log('Product update error: ' . $e->getMessage() . ' - SQL: ' . $sql . ' - Params: ' . json_encode($params));
                throw $e;
            }
            break;

        case 'DELETE':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            // 상품 삭제
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => '상품 ID가 필요합니다.']);
                exit;
            }

            $existing = db()->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
            if (!$existing) {
                http_response_code(404);
                echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                exit;
            }

            db()->execute("DELETE FROM products WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => '상품이 삭제되었습니다.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => '허용되지 않는 메소드입니다.']);
    }
} catch (Exception $e) {
    // 프로덕션에서는 상세 에러 메시지 숨김
    error_log('Products API Error: ' . $e->getMessage());
    error_log('Products API Stack Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    // 개발 환경에서는 상세 오류 메시지 표시
    $errorMessage = '서버 오류가 발생했습니다.';
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        $errorMessage = '서버 오류: ' . $e->getMessage();
    }
    echo json_encode(['error' => $errorMessage, 'details' => $e->getMessage()]);
}
