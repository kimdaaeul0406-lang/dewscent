<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/db_setup.php';
require_once __DIR__ . '/../../admin/guard.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 자동 생성
ensure_tables_exist();

// 관리자 권한 체크
if (!ensure_admin_api()) {
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        
        // 날짜 범위 설정 (기본값: 최근 30일)
        if (!$from) {
            $from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$to) {
            $to = date('Y-m-d');
        }
        
        // 1. 일별 주문 통계
        $orderStats = db()->fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(total_price) as total_revenue,
                SUM(CASE WHEN status = '결제완료' OR status = 'paid' OR status = '배송준비중' OR status = 'preparing' OR status = '배송중' OR status = 'shipping' OR status = '배송완료' OR status = 'delivered' THEN total_price ELSE 0 END) as paid_revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            [$from, $to]
        );
        
        // 2. 일별 가입자 통계
        $signupStats = db()->fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as signup_count
            FROM users
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND is_admin = 0
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            [$from, $to]
        );
        
        // 3. 주문 상태별 통계
        $statusStats = db()->fetchAll(
            "SELECT 
                status,
                COUNT(*) as count,
                SUM(total_price) as total_revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY status
            ORDER BY count DESC",
            [$from, $to]
        );
        
        // 4. 전체 통계 요약
        $summary = db()->fetchOne(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(total_price) as total_revenue,
                AVG(total_price) as avg_order_value,
                COUNT(DISTINCT user_id) as unique_customers
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?",
            [$from, $to]
        );
        
        // 날짜별 데이터를 배열로 변환 (빈 날짜도 포함)
        $dateRange = [];
        $current = strtotime($from);
        $end = strtotime($to);
        
        while ($current <= $end) {
            $dateStr = date('Y-m-d', $current);
            $dateRange[$dateStr] = [
                'date' => $dateStr,
                'orders' => 0,
                'revenue' => 0,
                'paid_revenue' => 0,
                'signups' => 0
            ];
            $current = strtotime('+1 day', $current);
        }
        
        // 주문 통계 병합
        foreach ($orderStats as $stat) {
            $date = $stat['date'];
            if (isset($dateRange[$date])) {
                $dateRange[$date]['orders'] = (int)$stat['order_count'];
                $dateRange[$date]['revenue'] = (float)($stat['total_revenue'] ?? 0);
                $dateRange[$date]['paid_revenue'] = (float)($stat['paid_revenue'] ?? 0);
            }
        }
        
        // 가입자 통계 병합
        foreach ($signupStats as $stat) {
            $date = $stat['date'];
            if (isset($dateRange[$date])) {
                $dateRange[$date]['signups'] = (int)$stat['signup_count'];
            }
        }
        
        // 배열로 변환
        $dailyStats = array_values($dateRange);
        
        // 상태별 통계 정리
        $statusData = [];
        foreach ($statusStats as $stat) {
            $statusData[] = [
                'status' => $stat['status'],
                'count' => (int)$stat['count'],
                'revenue' => (float)($stat['total_revenue'] ?? 0)
            ];
        }
        
        echo json_encode([
            'ok' => true,
            'from' => $from,
            'to' => $to,
            'daily' => $dailyStats,
            'status' => $statusData,
            'summary' => [
                'total_orders' => (int)($summary['total_orders'] ?? 0),
                'total_revenue' => (float)($summary['total_revenue'] ?? 0),
                'avg_order_value' => (float)($summary['avg_order_value'] ?? 0),
                'unique_customers' => (int)($summary['unique_customers'] ?? 0)
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log('통계 조회 오류: ' . $e->getMessage());
        error_log('통계 조회 오류 스택: ' . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'ok' => false, 
            'message' => '통계 조회 중 오류가 발생했습니다.',
            'error' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : null
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => '지원하지 않는 메서드입니다.'], JSON_UNESCAPED_UNICODE);
}
