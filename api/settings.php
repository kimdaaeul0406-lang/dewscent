<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/db_setup.php';

header('Content-Type: application/json; charset=utf-8');

ensure_tables_exist();

$method = $_SERVER['REQUEST_METHOD'];

// 사이트 설정 조회
if ($method === 'GET') {
    try {
        $settings = db()->fetchOne("SELECT * FROM site_settings WHERE id = 1");
        if (!$settings) {
            // 기본 설정 생성
            db()->insert("INSERT INTO site_settings (id) VALUES (1)");
            $settings = db()->fetchOne("SELECT * FROM site_settings WHERE id = 1");
        }
        
        // 필드명 변환
        $result = [
            'siteName' => $settings['site_name'] ?? 'DewScent',
            'siteSlogan' => $settings['site_slogan'] ?? '당신의 향기를 찾아서',
            'contactEmail' => $settings['contact_email'] ?? '',
            'contactPhone' => $settings['contact_phone'] ?? '',
            'address' => $settings['address'] ?? '',
            'businessHours' => $settings['business_hours'] ?? '',
            'kakaoChannel' => $settings['kakao_channel'] ?? '',
            'instagramUrl' => $settings['instagram_url'] ?? ''
        ];
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '설정 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} 
// 사이트 설정 저장
elseif ($method === 'PUT' || $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        db()->execute(
            "UPDATE site_settings SET 
                site_name = ?, site_slogan = ?, contact_email = ?, contact_phone = ?,
                address = ?, business_hours = ?, kakao_channel = ?, instagram_url = ?
             WHERE id = 1",
            [
                $data['siteName'] ?? 'DewScent',
                $data['siteSlogan'] ?? '당신의 향기를 찾아서',
                $data['contactEmail'] ?? null,
                $data['contactPhone'] ?? null,
                $data['address'] ?? null,
                $data['businessHours'] ?? null,
                $data['kakaoChannel'] ?? null,
                $data['instagramUrl'] ?? null
            ]
        );
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => '설정 저장 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
