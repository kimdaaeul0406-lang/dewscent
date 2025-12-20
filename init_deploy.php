<?php
/**
 * 배포 후 초기화 스크립트
 * 이 파일을 한 번만 실행하면 모든 테이블과 기본 데이터가 자동으로 생성됩니다.
 *
 * 실행 방법: 브라우저에서 http://your-domain.com/init_deploy.php 접속
 *
 * 주의: 배포 후 딱 한 번만 실행하세요!
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_setup.php';

header('Content-Type: text/html; charset=utf-8');

// 보안을 위한 실행 횟수 제한 (이미 초기화되었는지 확인)
$alreadyInitialized = false;
try {
    $productCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM products");
    $bannerCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM banners");
    if ($productCount && $bannerCount && $productCount['cnt'] > 0 && $bannerCount['cnt'] > 0) {
        $alreadyInitialized = true;
    }
} catch (Exception $e) {
    // 테이블이 없으면 초기화 진행
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DewScent 배포 초기화</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Noto Sans KR', -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #5f7161 0%, #c96473 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .header p {
            opacity: 0.9;
        }
        .content {
            padding: 2rem;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #5f7161;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        .step h2 {
            color: #5f7161;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .success {
            color: #28a745;
            font-weight: 600;
        }
        .error {
            color: #dc3545;
            font-weight: 600;
        }
        .warning {
            color: #ffc107;
            font-weight: 600;
        }
        .info {
            color: #17a2b8;
        }
        .admin-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .admin-box h3 {
            color: #856404;
            margin-bottom: 1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #5f7161 0%, #c96473 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            margin-top: 1rem;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .footer {
            background: #f8f9fa;
            padding: 1rem 2rem;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .already-init {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: center;
        }
        .already-init h2 {
            color: #155724;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌸 DewScent 배포 초기화</h1>
            <p>데이터베이스 자동 설정 및 기본 데이터 삽입</p>
        </div>

        <div class="content">
            <?php if ($alreadyInitialized): ?>
                <div class="already-init">
                    <h2>✅ 이미 초기화되었습니다!</h2>
                    <p>데이터베이스에 데이터가 이미 존재합니다.<br>중복 실행을 방지하기 위해 초기화를 건너뜁니다.</p>
                    <a href="index.php" class="btn">메인 페이지로 이동</a>
                    <a href="admin/login.php" class="btn">관리자 로그인</a>
                </div>
            <?php else: ?>
                <?php
                try {
                    // 1. DB 연결 확인
                    echo '<div class="step">';
                    echo '<h2>1. 데이터베이스 연결 확인</h2>';
                    $conn = db()->getConnection();
                    echo '<p class="success">✅ 데이터베이스 연결 성공</p>';
                    echo '<p class="info">호스트: ' . DB_HOST . ' | 데이터베이스: ' . DB_NAME . '</p>';
                    echo '</div>';

                    // 2. 테이블 생성 및 기본 데이터 삽입
                    echo '<div class="step">';
                    echo '<h2>2. 테이블 생성 및 기본 데이터 삽입</h2>';

                    $result = ensure_tables_exist();
                    if ($result) {
                        echo '<p class="success">✅ 모든 테이블이 성공적으로 생성되었습니다.</p>';
                    } else {
                        echo '<p class="warning">⚠️ 일부 테이블 생성 중 경고가 있었습니다. (무시 가능)</p>';
                    }
                    echo '</div>';

                    // 3. 기본 상품 데이터 추가
                    echo '<div class="step">';
                    echo '<h2>3. 기본 상품 데이터 추가</h2>';
                    $productCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM products");
                    $productCount = (int)$productCount['cnt'];

                    if ($productCount === 0) {
                        $defaultProducts = [
                            ['name' => 'Morning Dew', 'type' => '향수', 'price' => 89000, 'originalPrice' => 110000, 'rating' => 4.8, 'reviews' => 128, 'badge' => 'BEST', 'desc' => '아침 이슬처럼 맑고 청량한 향기입니다.', 'stock' => 50],
                            ['name' => 'Rose Garden', 'type' => '바디미스트', 'price' => 65000, 'originalPrice' => null, 'rating' => 4.9, 'reviews' => 89, 'badge' => 'NEW', 'desc' => '로맨틱한 장미 정원을 거니는 듯한 우아한 향기입니다.', 'stock' => 60],
                            ['name' => 'Golden Hour', 'type' => '향수', 'price' => 105000, 'originalPrice' => null, 'rating' => 4.7, 'reviews' => 156, 'badge' => null, 'desc' => '황금빛 노을처럼 따스하고 포근한 향기입니다.', 'stock' => 40],
                            ['name' => 'Forest Mist', 'type' => '디퓨저', 'price' => 78000, 'originalPrice' => 98000, 'rating' => 4.6, 'reviews' => 72, 'badge' => 'SALE', 'desc' => '숲속의 신선한 공기를 담은 청량한 향기입니다.', 'stock' => 35],
                            ['name' => 'Ocean Breeze', 'type' => '섬유유연제', 'price' => 32000, 'originalPrice' => null, 'rating' => 4.5, 'reviews' => 203, 'badge' => null, 'desc' => '바다 바람처럼 시원하고 깨끗한 향기입니다.', 'stock' => 100],
                            ['name' => 'Velvet Night', 'type' => '향수', 'price' => 125000, 'originalPrice' => null, 'rating' => 4.9, 'reviews' => 67, 'badge' => 'NEW', 'desc' => '밤의 신비로움을 담은 관능적인 향기입니다.', 'stock' => 25],
                            ['name' => 'Citrus Burst', 'type' => '바디미스트', 'price' => 55000, 'originalPrice' => 68000, 'rating' => 4.4, 'reviews' => 145, 'badge' => 'SALE', 'desc' => '상큼한 시트러스가 톡톡 터지는 활기찬 향기입니다.', 'stock' => 70],
                            ['name' => 'Soft Cotton', 'type' => '섬유유연제', 'price' => 28000, 'originalPrice' => null, 'rating' => 4.7, 'reviews' => 312, 'badge' => 'BEST', 'desc' => '갓 세탁한 면처럼 포근하고 깨끗한 향기입니다.', 'stock' => 150],
                        ];

                        $inserted = 0;
                        foreach ($defaultProducts as $p) {
                            try {
                                db()->insert(
                                    "INSERT INTO products (name, type, price, originalPrice, rating, reviews, badge, `desc`, stock, status, created_at)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '판매중', NOW())",
                                    [
                                        $p['name'],
                                        $p['type'],
                                        $p['price'],
                                        $p['originalPrice'],
                                        $p['rating'],
                                        $p['reviews'],
                                        $p['badge'],
                                        $p['desc'],
                                        $p['stock']
                                    ]
                                );
                                $inserted++;
                            } catch (Exception $e) {
                                // 중복 등 무시
                            }
                        }
                        echo '<p class="success">✅ ' . $inserted . '개의 기본 상품이 추가되었습니다.</p>';
                    } else {
                        echo '<p class="info">ℹ️ 이미 ' . $productCount . '개의 상품이 있습니다.</p>';
                    }
                    echo '</div>';

                    // 4. 테이블 상태 확인
                    echo '<div class="step">';
                    echo '<h2>4. 데이터베이스 상태 확인</h2>';
                    $tables = [
                        'users' => '회원',
                        'products' => '상품',
                        'banners' => '배너',
                        'emotions' => '감정',
                        'sections' => '섹션 설정',
                        'inquiries' => '문의',
                        'reviews' => '리뷰',
                        'orders' => '주문'
                    ];

                    echo '<table>';
                    echo '<tr><th>테이블</th><th>레코드 수</th><th>상태</th></tr>';
                    foreach ($tables as $table => $label) {
                        try {
                            $count = db()->fetchOne("SELECT COUNT(*) as cnt FROM {$table}");
                            $countNum = (int)($count['cnt'] ?? 0);
                            $status = $countNum > 0 ? 'success' : 'info';
                            echo "<tr><td><strong>{$label}</strong></td><td>{$countNum}</td><td class='{$status}'>✅ 정상</td></tr>";
                        } catch (Exception $e) {
                            echo "<tr><td><strong>{$label}</strong></td><td>-</td><td class='error'>❌ 오류</td></tr>";
                        }
                    }
                    echo '</table>';
                    echo '</div>';

                    // 5. 관리자 계정 정보
                    echo '<div class="admin-box">';
                    echo '<h3>🔑 관리자 계정 정보</h3>';
                    $admin = db()->fetchOne("SELECT email, name FROM users WHERE is_admin = 1 LIMIT 1");
                    if ($admin) {
                        echo '<p><strong>이메일:</strong> ' . htmlspecialchars($admin['email']) . '</p>';
                        echo '<p><strong>이름:</strong> ' . htmlspecialchars($admin['name']) . '</p>';
                        echo '<p><strong>비밀번호:</strong> admin123</p>';
                        echo '<p style="color:#856404;margin-top:1rem;font-size:0.9rem;">⚠️ 보안을 위해 로그인 후 비밀번호를 꼭 변경하세요!</p>';
                    } else {
                        echo '<p class="error">❌ 관리자 계정이 생성되지 않았습니다.</p>';
                    }
                    echo '</div>';

                    echo '<div style="text-align:center;margin-top:2rem;">';
                    echo '<h2 class="success">🎉 초기화 완료!</h2>';
                    echo '<p style="margin:1rem 0;">DewScent를 사용할 준비가 되었습니다.</p>';
                    echo '<div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">';
                    echo '<a href="index.php" class="btn">메인 페이지로 이동</a>';
                    echo '<a href="admin/login.php" class="btn">관리자 로그인</a>';
                    echo '</div>';
                    echo '</div>';

                } catch (Exception $e) {
                    echo '<div class="step">';
                    echo '<h2 class="error">❌ 오류 발생</h2>';
                    echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p>DB 연결 정보를 확인하거나 .env 파일을 점검하세요.</p>';
                    echo '</div>';
                }
                ?>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p style="color:#6c757d;font-size:0.9rem;">
                이 파일은 배포 후 한 번만 실행하면 됩니다.<br>
                초기화가 완료되면 보안을 위해 이 파일을 삭제하는 것을 권장합니다.
            </p>
        </div>
    </div>
</body>
</html>
