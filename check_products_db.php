<?php
/**
 * 배포 DB의 products 테이블 상태 확인 스크립트
 * 
 * 사용법:
 * 1. 배포 서버에 이 파일을 업로드
 * 2. 브라우저에서 /check_products_db.php 접근
 * 3. 결과 확인 후 보안을 위해 파일 삭제
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
    <title>Products DB 확인</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        .result { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; }
        .warning { border-left-color: #ffc107; }
        .error { border-left-color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>📊 Products DB 상태 확인</h1>

<?php
try {
    // 1. 전체 상품 수
    echo "<h2>1. 전체 상품 수</h2>";
    $totalResult = db()->fetchOne("SELECT COUNT(*) AS cnt FROM products");
    $totalCount = (int)($totalResult['cnt'] ?? 0);
    echo "<div class='result " . ($totalCount > 0 ? 'success' : 'warning') . "'>";
    echo "<strong>총 상품 수:</strong> <code>{$totalCount}개</code>";
    echo "</div>";

    // 2. Status별 분포
    echo "<h2>2. Status별 분포</h2>";
    $statusResults = db()->fetchAll("SELECT status, COUNT(*) as c FROM products GROUP BY status ORDER BY c DESC");
    
    if (empty($statusResults)) {
        echo "<div class='result warning'>";
        echo "<strong>Status 데이터 없음:</strong> products 테이블에 데이터가 없거나 status 컬럼 값이 없습니다.";
        echo "</div>";
    } else {
        echo "<table>";
        echo "<tr><th>Status</th><th>개수</th></tr>";
        foreach ($statusResults as $row) {
            $status = htmlspecialchars($row['status'] ?? '(NULL)');
            $count = (int)($row['c'] ?? 0);
            $isSelling = ($status === '판매중');
            echo "<tr style='" . ($isSelling ? "background: #d4edda;" : "") . "'>";
            echo "<td><code>{$status}</code></td>";
            echo "<td><strong>{$count}개</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // '판매중' 상품 확인
        $sellingCount = 0;
        foreach ($statusResults as $row) {
            if (($row['status'] ?? '') === '판매중') {
                $sellingCount = (int)($row['c'] ?? 0);
                break;
            }
        }
        
        echo "<div class='result " . ($sellingCount > 0 ? 'success' : 'warning') . "'>";
        echo "<strong>'판매중' 상품:</strong> <code>{$sellingCount}개</code>";
        if ($sellingCount === 0) {
            echo "<br><strong>⚠️ 경고:</strong> '판매중' 상태인 상품이 없습니다. API의 WHERE 조건과 일치하지 않아 상품 목록이 비어보일 수 있습니다.";
        }
        echo "</div>";
    }

    // 3. 최근 등록된 상품 5개
    echo "<h2>3. 최근 등록된 상품 5개</h2>";
    $recentProducts = db()->fetchAll("SELECT id, name, status, created_at FROM products ORDER BY id DESC LIMIT 5");
    
    if (empty($recentProducts)) {
        echo "<div class='result warning'>등록된 상품이 없습니다.</div>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>상품명</th><th>Status</th><th>등록일</th></tr>";
        foreach ($recentProducts as $product) {
            $id = htmlspecialchars($product['id'] ?? '');
            $name = htmlspecialchars($product['name'] ?? '');
            $status = htmlspecialchars($product['status'] ?? '(NULL)');
            $created = htmlspecialchars($product['created_at'] ?? '');
            $isSelling = ($status === '판매중');
            echo "<tr style='" . ($isSelling ? "background: #d4edda;" : "") . "'>";
            echo "<td>{$id}</td>";
            echo "<td>{$name}</td>";
            echo "<td><code>{$status}</code></td>";
            echo "<td>{$created}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 4. DB 연결 정보 (마스킹)
    echo "<h2>4. DB 연결 정보</h2>";
    echo "<div class='result'>";
    echo "<strong>DB_HOST:</strong> " . htmlspecialchars(defined('DB_HOST') ? DB_HOST : 'not defined') . "<br>";
    echo "<strong>DB_NAME:</strong> " . htmlspecialchars(defined('DB_NAME') ? DB_NAME : 'not defined') . "<br>";
    echo "<strong>연결 상태:</strong> <span style='color:green;'>✓ 정상</span>";
    echo "</div>";

    // 5. 권장 사항
    echo "<h2>5. 권장 사항</h2>";
    echo "<div class='result'>";
    
    if ($totalCount === 0) {
        echo "<strong>⚠️ 상품이 없습니다:</strong><br>";
        echo "- 관리자 대시보드에서 상품을 등록해주세요.<br>";
        echo "- 등록 후 이 페이지를 새로고침하여 확인하세요.";
    } else if ($sellingCount === 0) {
        echo "<strong>⚠️ '판매중' 상품이 없습니다:</strong><br>";
        echo "- 상품은 있지만 status가 '판매중'이 아닌 것 같습니다.<br>";
        echo "- 옵션 A: 관리자에서 상품의 status를 '판매중'으로 변경<br>";
        echo "- 옵션 B: API의 SELECT 조건을 실제 status 값에 맞게 수정<br>";
        echo "<br><strong>현재 API 조건:</strong> <code>WHERE status = '판매중'</code><br>";
        echo "<strong>실제 DB status 값들:</strong> ";
        $statusList = array_map(function($row) { return $row['status'] ?? '(NULL)'; }, $statusResults);
        echo "<code>" . implode('</code>, <code>', array_map('htmlspecialchars', $statusList)) . "</code>";
    } else {
        echo "<strong>✓ 정상:</strong> '판매중' 상품이 {$sellingCount}개 있습니다. API가 정상적으로 조회할 수 있습니다.";
    }
    
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='result error'>";
    echo "<strong>❌ 오류 발생:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br>";
    echo "<small>" . htmlspecialchars($e->getTraceAsString()) . "</small>";
    echo "</div>";
}
?>

    <hr>
    <p><small><strong>보안:</strong> 확인 후 이 파일을 삭제하거나 접근을 차단하세요.</small></p>
</body>
</html>
