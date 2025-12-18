<?php
// product_variants 테이블 확인 및 생성 스크립트

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_setup.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>product_variants 테이블 확인</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #004085; padding: 10px; background: #d1ecf1; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>product_variants 테이블 확인 및 생성</h1>
        
        <?php
        try {
            $conn = db()->getConnection();
            
            // 1. 테이블 존재 여부 확인
            echo "<h2>1. 테이블 존재 여부 확인</h2>";
            $tables = db()->fetchAll("SHOW TABLES LIKE 'product_variants'");
            
            if (empty($tables)) {
                echo "<div class='error'>❌ product_variants 테이블이 존재하지 않습니다.</div>";
                $tableExists = false;
            } else {
                echo "<div class='success'>✅ product_variants 테이블이 존재합니다.</div>";
                $tableExists = true;
            }
            
            // 2. 테이블이 없으면 생성
            if (!$tableExists) {
                echo "<h2>2. 테이블 생성 시도</h2>";
                try {
                    // 먼저 products 테이블이 있는지 확인
                    $productsTable = db()->fetchAll("SHOW TABLES LIKE 'products'");
                    if (empty($productsTable)) {
                        echo "<div class='error'>❌ products 테이블이 없습니다. 먼저 products 테이블을 생성해야 합니다.</div>";
                    } else {
                        echo "<div class='info'>ℹ️ products 테이블이 존재합니다. product_variants 테이블을 생성합니다...</div>";
                        
                        // ensure_tables_exist() 함수 실행
                        $result = ensure_tables_exist();
                        
                        if ($result) {
                            echo "<div class='success'>✅ 테이블 생성 함수가 성공적으로 실행되었습니다.</div>";
                            
                            // 다시 확인
                            $tables = db()->fetchAll("SHOW TABLES LIKE 'product_variants'");
                            if (!empty($tables)) {
                                echo "<div class='success'>✅ product_variants 테이블이 생성되었습니다!</div>";
                                $tableExists = true;
                            } else {
                                echo "<div class='error'>❌ 테이블 생성 함수는 실행되었지만 테이블이 여전히 없습니다.</div>";
                            }
                        } else {
                            echo "<div class='error'>❌ 테이블 생성 함수 실행 중 오류가 발생했습니다.</div>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>❌ 테이블 생성 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
                    echo "<div class='info'>상세 오류: " . htmlspecialchars($e->getTraceAsString()) . "</div>";
                }
            }
            
            // 3. 테이블 구조 확인
            if ($tableExists) {
                echo "<h2>3. 테이블 구조 확인</h2>";
                try {
                    $columns = db()->fetchAll("SHOW COLUMNS FROM product_variants");
                    echo "<table>";
                    echo "<tr><th>컬럼명</th><th>타입</th><th>NULL</th><th>키</th><th>기본값</th><th>추가</th></tr>";
                    foreach ($columns as $col) {
                        $field = $col['Field'] ?? $col['field'];
                        $type = $col['Type'] ?? $col['type'];
                        $null = $col['Null'] ?? $col['null'];
                        $key = $col['Key'] ?? $col['key'];
                        $default = $col['Default'] ?? $col['default'];
                        $extra = $col['Extra'] ?? $col['extra'];
                        echo "<tr>";
                        echo "<td><strong>{$field}</strong></td>";
                        echo "<td>{$type}</td>";
                        echo "<td>{$null}</td>";
                        echo "<td>{$key}</td>";
                        echo "<td>" . ($default !== null ? $default : 'NULL') . "</td>";
                        echo "<td>{$extra}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "<div class='success'>✅ 테이블 구조가 정상입니다.</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ 테이블 구조 확인 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
                
                // 4. 인덱스 확인
                echo "<h2>4. 인덱스 확인</h2>";
                try {
                    $indexes = db()->fetchAll("SHOW INDEXES FROM product_variants");
                    if (!empty($indexes)) {
                        echo "<table>";
                        echo "<tr><th>인덱스명</th><th>컬럼명</th><th>순서</th><th>고유성</th></tr>";
                        foreach ($indexes as $idx) {
                            $keyName = $idx['Key_name'] ?? $idx['key_name'];
                            $columnName = $idx['Column_name'] ?? $idx['column_name'];
                            $seq = $idx['Seq_in_index'] ?? $idx['seq_in_index'];
                            $nonUnique = $idx['Non_unique'] ?? $idx['non_unique'];
                            echo "<tr>";
                            echo "<td><strong>{$keyName}</strong></td>";
                            echo "<td>{$columnName}</td>";
                            echo "<td>{$seq}</td>";
                            echo "<td>" . ($nonUnique == 0 ? 'YES' : 'NO') . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>❌ 인덱스 확인 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
                
                // 5. 데이터 확인
                echo "<h2>5. 저장된 데이터 확인</h2>";
                try {
                    $count = db()->fetchOne("SELECT COUNT(*) as cnt FROM product_variants");
                    $countValue = $count['cnt'] ?? $count['CNT'] ?? 0;
                    echo "<div class='info'>현재 저장된 variants 수: <strong>{$countValue}</strong>개</div>";
                    
                    if ($countValue > 0) {
                        $variants = db()->fetchAll("SELECT * FROM product_variants ORDER BY product_id, sort_order LIMIT 10");
                        echo "<table>";
                        echo "<tr><th>ID</th><th>상품ID</th><th>용량</th><th>가격</th><th>재고</th><th>기본</th><th>정렬</th></tr>";
                        foreach ($variants as $v) {
                            echo "<tr>";
                            echo "<td>{$v['id']}</td>";
                            echo "<td>{$v['product_id']}</td>";
                            echo "<td>{$v['volume']}</td>";
                            echo "<td>" . number_format($v['price']) . "원</td>";
                            echo "<td>{$v['stock']}</td>";
                            echo "<td>" . ($v['is_default'] ? '✓' : '') . "</td>";
                            echo "<td>{$v['sort_order']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>❌ 데이터 확인 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            
            // 6. 수동 생성 버튼
            if (!$tableExists) {
                echo "<h2>6. 수동 테이블 생성</h2>";
                echo "<div class='info'>위의 자동 생성이 실패한 경우, 아래 버튼을 클릭하여 수동으로 테이블을 생성할 수 있습니다.</div>";
                echo "<form method='POST' style='margin-top: 20px;'>";
                echo "<input type='hidden' name='create_table' value='1'>";
                echo "<button type='submit'>테이블 수동 생성</button>";
                echo "</form>";
            }
            
            // 수동 생성 처리
            if (isset($_POST['create_table']) && $_POST['create_table'] == '1') {
                echo "<h2>수동 테이블 생성 실행</h2>";
                try {
                    $conn->exec("
                        CREATE TABLE IF NOT EXISTS product_variants (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            product_id INT NOT NULL,
                            volume VARCHAR(20) NOT NULL COMMENT '용량 (예: 30ml, 50ml, 100ml)',
                            price INT NOT NULL COMMENT '해당 용량 가격',
                            stock INT DEFAULT 0 COMMENT '해당 용량 재고',
                            is_default TINYINT(1) DEFAULT 0 COMMENT '기본 선택 여부',
                            sort_order INT DEFAULT 0 COMMENT '정렬 순서',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                            INDEX idx_product_id (product_id),
                            UNIQUE KEY unique_product_volume (product_id, volume)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    echo "<div class='success'>✅ 테이블이 수동으로 생성되었습니다! 페이지를 새로고침하세요.</div>";
                    echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ 수동 생성 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ 전체 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='info'>상세 오류: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="admin/dashboard.php" style="color: #007bff; text-decoration: none;">← 관리자 대시보드로 돌아가기</a>
        </div>
    </div>
</body>
</html>
