<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/db_setup.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>관리자 계정 설정</title>";
echo "<style>body{font-family:sans-serif;margin:2rem;background:#f4f4f4;color:#333;}";
echo "div{background:#fff;padding:1.5rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);margin-bottom:1rem;}";
echo "h1{color:#5f7161;}h2{color:#c96473;margin-top:1.5rem;}";
echo "p{margin-bottom:0.5rem;}";
echo ".success{color:#28a745;font-weight:600;}";
echo ".error{color:#dc3545;font-weight:600;}";
echo ".info{color:#17a2b8;}";
echo ".warning{color:#ffc107;}";
echo "table{border-collapse:collapse;width:100%;margin-top:1rem;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#f8f9fa;}</style></head><body>";
echo "<div><h1>DewScent - 관리자 계정 설정</h1>";

try {
    // 테이블 자동 생성
    ensure_tables_exist();
    
    $conn = db()->getConnection();
    echo "<p class='success'>✅ 데이터베이스 연결 성공</p>";
    
    // 1. is_admin 컬럼 확인 및 추가
    echo "<h2>1. is_admin 컬럼 확인</h2>";
    $columns = db()->fetchAll("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if (empty($columns)) {
        echo "<p class='info'>is_admin 컬럼을 추가합니다...</p>";
        try {
            $conn->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 COMMENT '관리자 여부 (1=관리자)'");
            echo "<p class='success'>✅ is_admin 컬럼 추가 완료</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ is_admin 컬럼 추가 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='success'>✅ is_admin 컬럼이 이미 존재합니다</p>";
    }
    
    // 2. 현재 사용자 목록 확인
    echo "<h2>2. 현재 사용자 목록</h2>";
    $users = db()->fetchAll("SELECT id, email, name, is_admin FROM users ORDER BY id");
    if (empty($users)) {
        echo "<p class='warning'>⚠️ 등록된 사용자가 없습니다.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>이메일</th><th>이름</th><th>관리자 여부</th></tr>";
        foreach ($users as $user) {
            $isAdmin = !empty($user['is_admin']) ? '✅ 예' : '❌ 아니오';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>$isAdmin</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. admin 계정 찾기 및 관리자 권한 부여
    echo "<h2>3. 관리자 계정 설정</h2>";
    
    // 이메일이 admin인 계정 찾기 (여러 개일 수 있음)
    $adminUsers = db()->fetchAll("SELECT id, email, name, is_admin, password FROM users WHERE email LIKE '%admin%' OR name = 'admin' OR name = '관리자'");
    
    if (empty($adminUsers)) {
        echo "<p class='warning'>⚠️ 'admin' 계정을 찾을 수 없습니다.</p>";
        $adminUser = null;
    } else {
        echo "<p class='info'>관리자 계정 후보를 찾았습니다:</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>이메일</th><th>이름</th><th>현재 관리자</th><th>비밀번호 상태</th></tr>";
        foreach ($adminUsers as $user) {
            $isAdmin = !empty($user['is_admin']) ? '✅ 예' : '❌ 아니오';
            $passwordStatus = (strlen($user['password']) < 20) ? '⚠️ 평문' : '✅ 해시됨';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>$isAdmin</td>";
            echo "<td>$passwordStatus</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 첫 번째 admin 계정 사용
        $adminUser = $adminUsers[0];
    }
    
    if ($adminUser) {
        echo "<h3>선택된 관리자 계정</h3>";
        echo "<ul>";
        echo "<li>ID: {$adminUser['id']}</li>";
        echo "<li>이메일: <strong>{$adminUser['email']}</strong></li>";
        echo "<li>이름: {$adminUser['name']}</li>";
        echo "<li>현재 관리자 여부: " . (!empty($adminUser['is_admin']) ? '✅ 예' : '❌ 아니오') . "</li>";
        echo "</ul>";
        
        // 관리자 권한 부여
        if (empty($adminUser['is_admin'])) {
            echo "<p class='info'>관리자 권한을 부여합니다...</p>";
            try {
                db()->execute("UPDATE users SET is_admin = 1 WHERE id = ?", [$adminUser['id']]);
                echo "<p class='success'>✅ 관리자 권한이 부여되었습니다!</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ 관리자 권한 부여 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='success'>✅ 이미 관리자 권한이 설정되어 있습니다.</p>";
        }
        
        // 비밀번호가 평문이면 해시로 변경 제안
        if (strlen($adminUser['password']) < 20) {
            echo "<p class='warning'>⚠️ 비밀번호가 평문으로 저장되어 있습니다. 보안을 위해 해시로 변경하는 것을 권장합니다.</p>";
            echo "<p class='info'>비밀번호를 해시로 변경하시겠습니까? (현재 비밀번호: {$adminUser['password']})</p>";
            
            // 비밀번호 해시화 옵션
            if (isset($_GET['hash_password']) && $_GET['hash_password'] == $adminUser['id']) {
                try {
                    $hashedPassword = password_hash($adminUser['password'], PASSWORD_DEFAULT);
                    db()->execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $adminUser['id']]);
                    echo "<p class='success'>✅ 비밀번호가 해시로 변경되었습니다!</p>";
                    echo "<p class='info'>이제 로그인 시 원래 비밀번호({$adminUser['password']})를 사용하세요.</p>";
                } catch (Exception $e) {
                    echo "<p class='error'>❌ 비밀번호 해시화 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            } else {
                echo "<p><a href='?hash_password={$adminUser['id']}' style='color:#17a2b8;font-weight:600;'>비밀번호를 해시로 변경하기</a></p>";
            }
        }
        
        echo "<div style='background:#e7f3ff;padding:1rem;border-radius:8px;margin-top:1rem;'>";
        echo "<p class='info'><strong>📌 로그인 정보:</strong></p>";
        echo "<ul style='margin:0.5rem 0;'>";
        echo "<li><strong>이메일:</strong> <code style='background:#fff;padding:2px 6px;border-radius:4px;'>{$adminUser['email']}</code></li>";
        if (strlen($adminUser['password']) < 20) {
            echo "<li><strong>비밀번호:</strong> <code style='background:#fff;padding:2px 6px;border-radius:4px;'>{$adminUser['password']}</code> (평문)</li>";
        } else {
            echo "<li><strong>비밀번호:</strong> 설정하신 비밀번호</li>";
        }
        echo "</ul>";
        echo "<p style='margin-top:0.5rem;'><a href='../index.php' style='color:#17a2b8;font-weight:600;'>로그인 페이지로 이동</a></p>";
        echo "</div>";
    } else {
        echo "<p class='warning'>⚠️ 'admin' 계정을 찾을 수 없습니다.</p>";
        echo "<p class='info'>다음 중 하나를 선택하세요:</p>";
        echo "<ol>";
        echo "<li>위의 사용자 목록에서 관리자로 설정할 계정의 ID를 확인하세요.</li>";
        echo "<li>아래에서 직접 관리자 계정을 생성할 수 있습니다.</li>";
        echo "</ol>";
        
        // 관리자 계정 생성 옵션
        echo "<h3>관리자 계정 생성</h3>";
        echo "<p class='info'>이메일: admin@dewscent.com, 비밀번호: admin123으로 관리자 계정을 생성하시겠습니까?</p>";
        echo "<p><a href='insert_default_data.php' style='color:#17a2b8;font-weight:600;'>기본 데이터 삽입 페이지로 이동 (관리자 계정 생성 포함)</a></p>";
    }
    
    // 4. 모든 관리자 계정 확인
    echo "<h2>4. 모든 관리자 계정 확인</h2>";
    $admins = db()->fetchAll("SELECT id, email, name FROM users WHERE is_admin = 1");
    if (empty($admins)) {
        echo "<p class='warning'>⚠️ 관리자 계정이 없습니다.</p>";
    } else {
        echo "<p class='success'>✅ 관리자 계정 목록:</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>이메일</th><th>이름</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$admin['name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>5. 완료</h2>";
    echo "<p class='success'>🎉 관리자 계정 설정이 완료되었습니다!</p>";
    echo "<p>이제 관리자 대시보드에 로그인할 수 있습니다.</p>";
    echo "<p><a href='admin/dashboard.php' style='color:#17a2b8;font-weight:600;'>관리자 대시보드로 이동</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ 데이터베이스 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>DB 연결 정보 (config.php) 또는 테이블 권한을 확인해주세요.</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";

