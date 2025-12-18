<?php
// api/variants.php
// 상품 용량별 가격 (variants) CRUD API

// 파일 실행 확인용 로그 (최상단)
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    error_log('Variants API - 파일 실행 시작, Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'null'));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// CORS 설정
$allowed_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (strpos($allowed_origin, 'localhost') !== false || $allowed_origin === '') {
    header('Access-Control-Allow-Origin: ' . ($allowed_origin ?: '*'));
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';
require_once __DIR__ . '/../admin/guard.php';

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];

// URL에서 product_id와 variant_id 추출
// 예: /api/variants.php/5 (product_id=5의 모든 variants)
// 예: /api/variants.php/5/10 (product_id=5의 variant_id=10)
$productId = null;
$variantId = null;

// 1. PATH_INFO에서 추출
if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
    $path = trim($_SERVER['PATH_INFO'], '/');
    $pathParts = explode('/', $path);
    if (isset($pathParts[0]) && is_numeric($pathParts[0])) {
        $productId = (int)$pathParts[0];
    }
    if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
        $variantId = (int)$pathParts[1];
    }
}

// 2. REQUEST_URI에서 추출 (PATH_INFO가 없는 경우)
if (!$productId && isset($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // /api/variants.php/5 형식에서 5 추출
    if (preg_match('#/variants\.php/(\d+)(?:/(\d+))?#', $uri, $matches)) {
        $productId = (int)$matches[1];
        if (isset($matches[2])) {
            $variantId = (int)$matches[2];
        }
    }
}

// 3. SCRIPT_NAME과 REQUEST_URI 비교하여 경로 추출
if (!$productId && isset($_SERVER['SCRIPT_NAME']) && isset($_SERVER['REQUEST_URI'])) {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($requestUri, $scriptName) !== false) {
        $path = substr($requestUri, strpos($requestUri, $scriptName) + strlen($scriptName));
        $path = trim($path, '/');
        if (!empty($path)) {
            $pathParts = explode('/', $path);
            if (isset($pathParts[0]) && is_numeric($pathParts[0])) {
                $productId = (int)$pathParts[0];
            }
            if (isset($pathParts[1]) && is_numeric($pathParts[1])) {
                $variantId = (int)$pathParts[1];
            }
        }
    }
}

// 4. GET 파라미터로도 product_id 받기
if (!$productId && isset($_GET['product_id'])) {
    $productId = (int)$_GET['product_id'];
}
if (!$variantId && isset($_GET['variant_id'])) {
    $variantId = (int)$_GET['variant_id'];
}

// 디버깅용 로그 (개발 환경에서만)
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    error_log('Variants API Debug - PATH_INFO: ' . ($_SERVER['PATH_INFO'] ?? 'null') . ', REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'null') . ', SCRIPT_NAME: ' . ($_SERVER['SCRIPT_NAME'] ?? 'null') . ', Method: ' . $method . ', productId: ' . ($productId ?? 'null') . ', variantId: ' . ($variantId ?? 'null'));
    
    // 파일이 실행되는지 확인
    if ($method === 'PUT') {
        error_log('Variants API - PUT 요청 수신됨, productId: ' . ($productId ?? 'null'));
    }
}

// switch 문 진입 확인
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    error_log('Variants API - switch 문 진입, Method: ' . $method);
}

try {
    switch ($method) {
        case 'GET':
            // 특정 상품의 variants 조회
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => '상품 ID가 필요합니다.']);
                exit;
            }

            $variants = db()->fetchAll(
                "SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, price ASC",
                [$productId]
            );
            echo json_encode($variants ?: []);
            break;

        case 'POST':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // product_id는 URL 또는 body에서 가져오기
            $productId = $productId ?: ($data['product_id'] ?? null);
            
            // PUT 요청을 POST로 처리 (X-HTTP-Method-Override 헤더 지원)
            $overrideMethod = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
            if ($overrideMethod === 'PUT' || (isset($data['_method']) && $data['_method'] === 'PUT')) {
                // PUT 로직으로 처리 (variants 교체)
                // PUT 로직으로 처리
                if (!$variantId && !$productId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'variant ID 또는 product ID가 필요합니다.']);
                    exit;
                }

                if ($variantId) {
                    // 단일 variant 수정
                    $existing = db()->fetchOne("SELECT * FROM product_variants WHERE id = ?", [$variantId]);
                    if (!$existing) {
                        http_response_code(404);
                        echo json_encode(['error' => 'variant를 찾을 수 없습니다.']);
                        exit;
                    }

                    db()->execute(
                        "UPDATE product_variants SET volume = ?, price = ?, stock = ?, is_default = ?, sort_order = ? WHERE id = ?",
                        [
                            $data['volume'] ?? $existing['volume'],
                            (int)($data['price'] ?? $existing['price']),
                            (int)($data['stock'] ?? $existing['stock']),
                            (int)($data['is_default'] ?? $existing['is_default']),
                            (int)($data['sort_order'] ?? $existing['sort_order']),
                            $variantId
                        ]
                    );

                    $updated = db()->fetchOne("SELECT * FROM product_variants WHERE id = ?", [$variantId]);
                    echo json_encode($updated);
                    break;
                } else {
                    // 전체 variants 교체 (product_id로)
                    if (!$productId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'product ID가 필요합니다.']);
                        exit;
                    }

                    // 상품 존재 여부 확인
                    try {
                        $product = db()->fetchOne("SELECT id FROM products WHERE id = ?", [$productId]);
                        if (!$product) {
                            http_response_code(404);
                            echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                            exit;
                        }
                    } catch (Exception $e) {
                        error_log('Product check error: ' . $e->getMessage());
                        http_response_code(500);
                        echo json_encode(['error' => '상품 확인 중 오류가 발생했습니다.', 'details' => $e->getMessage()]);
                        exit;
                    }

                    $variants = $data['variants'] ?? [];

                    // 기존 variants 삭제
                    try {
                        db()->execute("DELETE FROM product_variants WHERE product_id = ?", [$productId]);
                    } catch (Exception $e) {
                        error_log('Variant delete error: ' . $e->getMessage());
                    }

                    // 새로 추가
                    $insertedCount = 0;
                    foreach ($variants as $index => $variant) {
                        if (empty($variant['volume']) || !isset($variant['price'])) {
                            continue;
                        }
                        try {
                            db()->insert(
                                "INSERT INTO product_variants (product_id, volume, price, stock, is_default, sort_order)
                                 VALUES (?, ?, ?, ?, ?, ?)",
                                [
                                    $productId,
                                    trim($variant['volume']),
                                    (int)$variant['price'],
                                    (int)($variant['stock'] ?? 0),
                                    (int)($variant['is_default'] ?? 0),
                                    (int)($variant['sort_order'] ?? $index)
                                ]
                            );
                            $insertedCount++;
                        } catch (Exception $e) {
                            error_log('Variant insert error: ' . $e->getMessage() . ' - Variant: ' . json_encode($variant));
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                // 중복이면 업데이트
                                $existing = db()->fetchOne(
                                    "SELECT id FROM product_variants WHERE product_id = ? AND volume = ?",
                                    [$productId, trim($variant['volume'])]
                                );
                                if ($existing) {
                                    db()->execute(
                                        "UPDATE product_variants SET price = ?, stock = ?, is_default = ?, sort_order = ? WHERE id = ?",
                                        [
                                            (int)$variant['price'],
                                            (int)($variant['stock'] ?? 0),
                                            (int)($variant['is_default'] ?? 0),
                                            (int)($variant['sort_order'] ?? $index),
                                            $existing['id']
                                        ]
                                    );
                                    $insertedCount++;
                                }
                            } else {
                                http_response_code(500);
                                echo json_encode([
                                    'error' => '용량별 가격 저장 중 오류가 발생했습니다.',
                                    'details' => $e->getMessage(),
                                    'variant' => $variant
                                ]);
                                exit;
                            }
                        }
                    }

                    // 결과 조회
                    try {
                        $result = db()->fetchAll(
                            "SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC",
                            [$productId]
                        );
                        echo json_encode(['success' => true, 'variants' => $result, 'inserted' => $insertedCount]);
                    } catch (Exception $e) {
                        error_log('Variant fetch error: ' . $e->getMessage());
                        echo json_encode(['success' => true, 'variants' => [], 'inserted' => $insertedCount]);
                    }
                    break;
                }
            }
            
            // 일반 POST 로직 (variant 추가)
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => '상품 ID가 필요합니다.']);
                exit;
            }

            // 상품 존재 여부 확인
            $product = db()->fetchOne("SELECT id FROM products WHERE id = ?", [$productId]);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                exit;
            }

            // 단일 variant 추가 또는 여러 개 한번에 추가
            $variants = isset($data['variants']) ? $data['variants'] : [$data];
            $insertedIds = [];

            foreach ($variants as $variant) {
                if (empty($variant['volume']) || !isset($variant['price'])) {
                    continue;
                }

                // 중복 체크
                $existing = db()->fetchOne(
                    "SELECT id FROM product_variants WHERE product_id = ? AND volume = ?",
                    [$productId, $variant['volume']]
                );

                if ($existing) {
                    // 업데이트
                    db()->execute(
                        "UPDATE product_variants SET price = ?, stock = ?, is_default = ?, sort_order = ? WHERE id = ?",
                        [
                            (int)$variant['price'],
                            (int)($variant['stock'] ?? 0),
                            (int)($variant['is_default'] ?? 0),
                            (int)($variant['sort_order'] ?? 0),
                            $existing['id']
                        ]
                    );
                    $insertedIds[] = $existing['id'];
                } else {
                    // 새로 추가
                    $newId = db()->insert(
                        "INSERT INTO product_variants (product_id, volume, price, stock, is_default, sort_order)
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [
                            $productId,
                            $variant['volume'],
                            (int)$variant['price'],
                            (int)($variant['stock'] ?? 0),
                            (int)($variant['is_default'] ?? 0),
                            (int)($variant['sort_order'] ?? 0)
                        ]
                    );
                    $insertedIds[] = $newId;
                }
            }

            // 결과 반환
            $result = db()->fetchAll(
                "SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, price ASC",
                [$productId]
            );

            http_response_code(201);
            echo json_encode(['success' => true, 'variants' => $result]);
            break;

        case 'PUT':
            // PUT 케이스 진입 확인
            if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                error_log('Variants API - PUT 케이스 진입, productId: ' . ($productId ?? 'null'));
            }
            
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                    error_log('Variants API - 관리자 인증 실패');
                }
                exit;
            }
            
            if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                error_log('Variants API - 관리자 인증 성공, productId: ' . ($productId ?? 'null'));
            }

            if (!$variantId && !$productId) {
                http_response_code(400);
                echo json_encode(['error' => 'variant ID 또는 product ID가 필요합니다.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if ($variantId) {
                // 단일 variant 수정
                $existing = db()->fetchOne("SELECT * FROM product_variants WHERE id = ?", [$variantId]);
                if (!$existing) {
                    http_response_code(404);
                    echo json_encode(['error' => 'variant를 찾을 수 없습니다.']);
                    exit;
                }

                db()->execute(
                    "UPDATE product_variants SET volume = ?, price = ?, stock = ?, is_default = ?, sort_order = ? WHERE id = ?",
                    [
                        $data['volume'] ?? $existing['volume'],
                        (int)($data['price'] ?? $existing['price']),
                        (int)($data['stock'] ?? $existing['stock']),
                        (int)($data['is_default'] ?? $existing['is_default']),
                        (int)($data['sort_order'] ?? $existing['sort_order']),
                        $variantId
                    ]
                );

                $updated = db()->fetchOne("SELECT * FROM product_variants WHERE id = ?", [$variantId]);
                echo json_encode($updated);
            } else {
                // 전체 variants 교체 (product_id로)
                if (!$productId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'product ID가 필요합니다.']);
                    exit;
                }

                // 상품 존재 여부 확인
                try {
                    $product = db()->fetchOne("SELECT id FROM products WHERE id = ?", [$productId]);
                    if (!$product) {
                        http_response_code(404);
                        echo json_encode(['error' => '상품을 찾을 수 없습니다.']);
                        exit;
                    }
                } catch (Exception $e) {
                    error_log('Product check error: ' . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => '상품 확인 중 오류가 발생했습니다.', 'details' => $e->getMessage()]);
                    exit;
                }

                $variants = $data['variants'] ?? [];

                // 기존 variants 삭제
                try {
                    db()->execute("DELETE FROM product_variants WHERE product_id = ?", [$productId]);
                } catch (Exception $e) {
                    error_log('Variant delete error: ' . $e->getMessage());
                    // 삭제 실패해도 계속 진행 (테이블이 없을 수 있음)
                }

                // 새로 추가
                $insertedCount = 0;
                foreach ($variants as $index => $variant) {
                    if (empty($variant['volume']) || !isset($variant['price'])) {
                        continue;
                    }
                    try {
                        db()->insert(
                            "INSERT INTO product_variants (product_id, volume, price, stock, is_default, sort_order)
                             VALUES (?, ?, ?, ?, ?, ?)",
                            [
                                $productId,
                                trim($variant['volume']),
                                (int)$variant['price'],
                                (int)($variant['stock'] ?? 0),
                                (int)($variant['is_default'] ?? 0),
                                (int)($variant['sort_order'] ?? $index)
                            ]
                        );
                        $insertedCount++;
                    } catch (Exception $e) {
                        error_log('Variant insert error: ' . $e->getMessage() . ' - Data: ' . json_encode($variant));
                        // 첫 번째 오류는 상세 정보 포함
                        if ($insertedCount === 0) {
                            http_response_code(500);
                            echo json_encode([
                                'error' => '용량별 가격 저장 중 오류가 발생했습니다.',
                                'details' => $e->getMessage(),
                                'variant' => $variant
                            ]);
                            exit;
                        }
                        throw $e;
                    }
                }

                // 결과 조회
                try {
                    $result = db()->fetchAll(
                        "SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC",
                        [$productId]
                    );
                    echo json_encode(['success' => true, 'variants' => $result, 'inserted' => $insertedCount]);
                } catch (Exception $e) {
                    error_log('Variant fetch error: ' . $e->getMessage());
                    // 조회 실패해도 성공으로 처리 (데이터는 저장되었을 수 있음)
                    echo json_encode(['success' => true, 'variants' => [], 'inserted' => $insertedCount, 'warning' => '조회 중 오류 발생']);
                }
            }
            break;

        case 'DELETE':
            // 관리자 인증 필요
            if (!ensure_admin_api()) {
                exit;
            }

            if ($variantId) {
                // 단일 variant 삭제
                $existing = db()->fetchOne("SELECT id FROM product_variants WHERE id = ?", [$variantId]);
                if (!$existing) {
                    http_response_code(404);
                    echo json_encode(['error' => 'variant를 찾을 수 없습니다.']);
                    exit;
                }

                db()->execute("DELETE FROM product_variants WHERE id = ?", [$variantId]);
                echo json_encode(['success' => true, 'message' => 'variant가 삭제되었습니다.']);
            } else if ($productId) {
                // 상품의 모든 variants 삭제
                db()->execute("DELETE FROM product_variants WHERE product_id = ?", [$productId]);
                echo json_encode(['success' => true, 'message' => '모든 variants가 삭제되었습니다.']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'variant ID 또는 product ID가 필요합니다.']);
            }
            break;

        default:
            // 디버깅 정보 포함
            $debugInfo = '';
            if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                $debugInfo = ' (Method: ' . $method . ', PATH_INFO: ' . ($_SERVER['PATH_INFO'] ?? 'null') . ', productId: ' . ($productId ?? 'null') . ', REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'null') . ')';
                error_log('Variants API - default 케이스 진입, Method: ' . $method);
            }
            http_response_code(405);
            echo json_encode(['error' => '허용되지 않는 메소드입니다.' . $debugInfo, 'method' => $method]);
    }
} catch (Exception $e) {
    error_log('Variants API Error: ' . $e->getMessage());
    error_log('Variants API Stack Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    // 개발 환경에서는 상세 오류 메시지 표시
    $errorMessage = '서버 오류가 발생했습니다.';
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        $errorMessage = '서버 오류: ' . $e->getMessage();
    }
    echo json_encode(['error' => $errorMessage, 'details' => $e->getMessage()]);
}
