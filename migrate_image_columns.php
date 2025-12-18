<?php
// 이미지 컬럼을 TEXT로 변경하는 마이그레이션 스크립트
// 이 파일을 브라우저에서 한 번 실행하면 됩니다
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_setup.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>이미지 컬럼 마이그레이션</title></head><body>";
echo "<h2>이미지 컬럼 타입 변경 (VARCHAR → TEXT)</h2>";

try {
    $conn = db()->getConnection();
    
    // image 컬럼 확인 및 변경
    $imageColumn = db()->fetchOne("SHOW COLUMNS FROM products WHERE Field = 'image'");
    if ($imageColumn) {
        echo "<p><strong>image 컬럼:</strong> 현재 타입 = " . htmlspecialchars($imageColumn['Type']) . "</p>";
        if (stripos($imageColumn['Type'], 'varchar') !== false) {
            $conn->exec("ALTER TABLE products MODIFY COLUMN image TEXT DEFAULT NULL COMMENT '상품 카드 이미지 경로 (메인페이지 표시용, Base64 지원)'");
            echo "<p style='color:green;font-weight:bold;'>✓ image 컬럼을 TEXT로 변경했습니다!</p>";
        } else {
            echo "<p style='color:blue;'>image 컬럼은 이미 TEXT 타입입니다.</p>";
        }
    }
    
    // detail_image 컬럼 확인 및 변경
    $detailImageColumn = db()->fetchOne("SHOW COLUMNS FROM products WHERE Field = 'detail_image'");
    if ($detailImageColumn) {
        echo "<p><strong>detail_image 컬럼:</strong> 현재 타입 = " . htmlspecialchars($detailImageColumn['Type']) . "</p>";
        if (stripos($detailImageColumn['Type'], 'varchar') !== false) {
            $conn->exec("ALTER TABLE products MODIFY COLUMN detail_image TEXT DEFAULT NULL COMMENT '상품 상세 이미지 경로 (클릭 시 모달 표시용, Base64 지원)'");
            echo "<p style='color:green;font-weight:bold;'>✓ detail_image 컬럼을 TEXT로 변경했습니다!</p>";
        } else {
            echo "<p style='color:blue;'>detail_image 컬럼은 이미 TEXT 타입입니다.</p>";
        }
    }
    
    // 변경 후 최종 확인
    $imageColumnAfter = db()->fetchOne("SHOW COLUMNS FROM products WHERE Field = 'image'");
    $detailImageColumnAfter = db()->fetchOne("SHOW COLUMNS FROM products WHERE Field = 'detail_image'");
    
    echo "<hr><h3>✅ 최종 결과:</h3>";
    echo "<p><strong>image:</strong> " . htmlspecialchars($imageColumnAfter['Type']) . "</p>";
    echo "<p><strong>detail_image:</strong> " . htmlspecialchars($detailImageColumnAfter['Type']) . "</p>";
    
    if (stripos($imageColumnAfter['Type'], 'text') !== false && stripos($detailImageColumnAfter['Type'], 'text') !== false) {
        echo "<div style='background:#d4edda;border:1px solid #c3e6cb;padding:1rem;border-radius:4px;margin-top:1rem;'>";
        echo "<h3 style='color:#155724;margin-top:0;'>✅ 마이그레이션 완료!</h3>";
        echo "<p style='color:#155724;'>이제 Base64 이미지를 전체 저장할 수 있습니다.</p>";
        echo "<p style='color:#856404;'><strong>⚠️ 중요:</strong> 기존에 저장된 이미지 데이터가 잘려있을 수 있으니, 관리자 페이지에서 이미지를 다시 업로드해주세요.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;padding:1rem;border-radius:4px;'>";
    echo "<h3 style='color:#721c24;margin-top:0;'>❌ 오류 발생</h3>";
    echo "<p style='color:#721c24;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
