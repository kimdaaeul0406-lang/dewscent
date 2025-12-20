<?php
/**
 * 데이터베이스 연결 및 데이터 확인 스크립트
 * 닷홈 배포 후 문제 진단용
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB 연결 확인 | DewScent</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #5f7161; margin-bottom: 1.5rem; }
        h2 { color: #5f7161; margin: 1.5rem 0 1rem; font-size: 1.2rem; }
        .success { color: #28a745; font-weight: 600; }
        .error { color: #dc3545; font-weight: 600; }
        .warning { color: #ffc107; font-weight: 600; }
        .info { color: #17a2b8; }
        .box {
            background: #f8f9fa;
            border-left: 4px solid #5f7161;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
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
        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #5f7161 0%, #c96473 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            margin-top: 1rem;
        }
        pre {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 DewScent 데이터베이스 진단</h1>

        <?php
        try {
            // 1. DB 연결 확인
            echo '<div class="box">';
            echo '<h2>1. 데이터베이스 연결 확인</h2>';
            $conn = db()->getConnection();
            echo '<p class="success">✅ 데이터베이스 연결 성공!</p>';
            echo '<p class="info">호스트: ' . DB_HOST . '</p>';
            echo '<p class="info">데이터베이스: ' . DB_NAME . '</p>';
            echo '<p class="info">사용자: ' . DB_USER . '</p>';
            echo '</div>';

            // 2. 환경 변수 확인
            echo '<div class="box">';
            echo '<h2>2. 환경 설정 확인</h2>';
            echo '<table>';
            echo '<tr><th>설정</th><th>값</th></tr>';
            echo '<tr><td>APP_ENV</td><td>' . (defined('APP_ENV') ? APP_ENV : '미설정') . '</td></tr>';
            echo '<tr><td>SITE_URL</td><td>' . (defined('SITE_URL') ? SITE_URL : '미설정') . '</td></tr>';
            echo '<tr><td>KAKAO_CLIENT_ID</td><td>' . (KAKAO_CLIENT_ID !== 'NOT_SET' ? '✅ 설정됨' : '❌ 미설정') . '</td></tr>';
            echo '<tr><td>NAVER_CLIENT_ID</td><td>' . (NAVER_CLIENT_ID !== 'NOT_SET' ? '✅ 설정됨' : '❌ 미설정') . '</td></tr>';
            echo '</table>';
            echo '</div>';

            // 3. 테이블 존재 확인
            echo '<div class="box">';
            echo '<h2>3. 테이블 확인</h2>';
            $tables = ['users', 'products', 'banners', 'emotions', 'sections', 'orders', 'inquiries', 'reviews'];
            echo '<table>';
            echo '<tr><th>테이블</th><th>상태</th><th>레코드 수</th></tr>';

            foreach ($tables as $table) {
                try {
                    $count = db()->fetchOne("SELECT COUNT(*) as cnt FROM {$table}");
                    $cnt = (int)$count['cnt'];
                    if ($cnt > 0) {
                        echo "<tr><td><strong>{$table}</strong></td><td class='success'>✅ 존재</td><td>{$cnt}</td></tr>";
                    } else {
                        echo "<tr><td><strong>{$table}</strong></td><td class='warning'>⚠️ 비어있음</td><td>0</td></tr>";
                    }
                } catch (Exception $e) {
                    echo "<tr><td><strong>{$table}</strong></td><td class='error'>❌ 없음</td><td>-</td></tr>";
                }
            }
            echo '</table>';
            echo '</div>';

            // 4. 상품 데이터 샘플 확인
            echo '<div class="box">';
            echo '<h2>4. 상품 데이터 확인</h2>';
            try {
                $products = db()->fetchAll("SELECT id, name, type, price, stock, status FROM products LIMIT 5");
                if (!empty($products)) {
                    echo '<p class="success">✅ 상품 데이터가 있습니다!</p>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>상품명</th><th>타입</th><th>가격</th><th>재고</th><th>상태</th></tr>';
                    foreach ($products as $p) {
                        echo "<tr>";
                        echo "<td>{$p['id']}</td>";
                        echo "<td>{$p['name']}</td>";
                        echo "<td>{$p['type']}</td>";
                        echo "<td>" . number_format($p['price']) . "원</td>";
                        echo "<td>{$p['stock']}</td>";
                        echo "<td>{$p['status']}</td>";
                        echo "</tr>";
                    }
                    echo '</table>';
                } else {
                    echo '<p class="error">❌ 상품 데이터가 없습니다!</p>';
                    echo '<p class="warning">⚠️ init_deploy.php를 실행해야 합니다.</p>';
                    echo '<a href="init_deploy.php" class="btn">초기화 스크립트 실행</a>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ products 테이블이 존재하지 않습니다!</p>';
                echo '<p class="error">오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<a href="init_deploy.php" class="btn">초기화 스크립트 실행</a>';
            }
            echo '</div>';

            // 5. 배너 데이터 확인
            echo '<div class="box">';
            echo '<h2>5. 배너 데이터 확인</h2>';
            try {
                $banners = db()->fetchAll("SELECT id, title, type FROM banners LIMIT 3");
                if (!empty($banners)) {
                    echo '<p class="success">✅ 배너 데이터: ' . count($banners) . '개</p>';
                } else {
                    echo '<p class="warning">⚠️ 배너 데이터가 없습니다.</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ banners 테이블이 존재하지 않습니다!</p>';
            }
            echo '</div>';

            // 6. API 테스트
            echo '<div class="box">';
            echo '<h2>6. API 엔드포인트 테스트</h2>';
            echo '<p>다음 URL들이 정상 작동하는지 확인하세요:</p>';
            echo '<ul style="margin-left: 2rem;">';
            echo '<li><a href="api/products.php" target="_blank">api/products.php</a> - 상품 목록 API</li>';
            echo '<li><a href="api/banners.php" target="_blank">api/banners.php</a> - 배너 API</li>';
            echo '<li><a href="api/main-products.php" target="_blank">api/main-products.php</a> - 메인 상품 API</li>';
            echo '</ul>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="box">';
            echo '<h2 class="error">❌ 오류 발생</h2>';
            echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<h3 style="margin-top: 1.5rem;">문제 해결 방법:</h3>';
            echo '<ol style="margin-left: 2rem; line-height: 1.8;">';
            echo '<li>.env 파일이 올바르게 설정되었는지 확인</li>';
            echo '<li>닷홈 MySQL 데이터베이스가 생성되었는지 확인</li>';
            echo '<li>DB_NAME이 정확한지 확인 (닷홈: 사용자ID_데이터베이스명)</li>';
            echo '<li>DB_PASS(MySQL 비밀번호)가 정확한지 확인</li>';
            echo '</ol>';
            echo '</div>';
        }
        ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php" class="btn">메인 페이지로 이동</a>
            <a href="init_deploy.php" class="btn">초기화 스크립트 실행</a>
        </div>
    </div>
</body>
</html>
