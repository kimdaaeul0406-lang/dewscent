<?php
/**
 * 토스페이먼츠 클라이언트 키 반환 API
 * 
 * 프론트엔드에서 결제위젯 초기화에 필요한 클라이언트 키를 반환합니다.
 */

// .env 파일 로드
require_once __DIR__ . '/../../config/env.php';

session_start();
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

// GET 또는 POST 요청 허용
// 결제위젯 연동 키 우선, 없으면 일반 클라이언트 키 사용
$tossWidgetClientKey = getenv('TOSS_WIDGET_CLIENT_KEY') ?: ($_ENV['TOSS_WIDGET_CLIENT_KEY'] ?? '');
$tossClientKey = getenv('TOSS_CLIENT_KEY') ?: ($_ENV['TOSS_CLIENT_KEY'] ?? '');

// 결제위젯 연동 키가 있으면 우선 사용, 없으면 일반 클라이언트 키 사용
$clientKey = !empty($tossWidgetClientKey) ? $tossWidgetClientKey : $tossClientKey;

if (empty($clientKey)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '결제 설정이 올바르지 않습니다. .env 파일에 TOSS_WIDGET_CLIENT_KEY 또는 TOSS_CLIENT_KEY를 설정해주세요.',
        'hint' => '결제위젯을 사용하려면 토스페이먼츠 개발자센터 > API 키 메뉴에서 "결제위젯 연동 키"를 확인하세요.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 클라이언트 키 반환 (시크릿 키는 절대 반환하지 않음)
echo json_encode([
    'success' => true,
    'clientKey' => $clientKey,
    'keyType' => !empty($tossWidgetClientKey) ? 'widget' : 'api'
], JSON_UNESCAPED_UNICODE);
