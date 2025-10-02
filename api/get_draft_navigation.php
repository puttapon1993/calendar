<?php
// File: get_draft_navigation.php
// Location: /api/
header('Content-Type: application/json');

// This check ensures only logged-in users (admin/staff) can access this internal API
session_start();
if (!isset($_SESSION['user_loggedin']) || $_SESSION['user_loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Access Denied']);
    exit;
}

require_once '../config.php';

try {
    // 1. Fetch publication settings
    $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('publish_start_date', 'publish_end_date')");
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $publish_start = !empty($settings['publish_start_date']) ? new DateTime($settings['publish_start_date'] . '-01') : null;
    $publish_end = !empty($settings['publish_end_date']) ? (new DateTime($settings['publish_end_date'] . '-01'))->modify('last day of this month') : null;


    // 2. Use UNION to get distinct months from both events and special holidays
    $stmt = $pdo->prepare("
        SELECT event_year, event_month FROM (
            SELECT DISTINCT YEAR(activity_date) as event_year, MONTH(activity_date) as event_month FROM event_dates
            UNION
            SELECT DISTINCT YEAR(holiday_date) as event_year, MONTH(holiday_date) as event_month FROM special_holidays
        ) as combined_dates
        ORDER BY event_year, event_month
    ");
    $stmt->execute();
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    $navigation = [];
    foreach ($months as $row) {
        $year_be = (int)$row['event_year'] + 543;
        $current_month_date = new DateTime($row['event_year'] . '-' . $row['event_month'] . '-01');
        
        $is_published = true;
        if ($publish_start && $current_month_date < $publish_start) {
            $is_published = false;
        }
        if ($publish_end && $current_month_date > $publish_end) {
            $is_published = false;
        }

        $navigation[] = [
            'year' => (int)$row['event_year'],
            'month' => (int)$row['event_month'],
            'label' => $thai_months[(int)$row['event_month']] . ' ' . $year_be,
            'is_published' => $is_published
        ];
    }
    
    echo json_encode([
        'navigation' => $navigation,
        'settings' => [ // Send settings for client-side checks
            'publish_start_date' => $settings['publish_start_date'] ?? null,
            'publish_end_date' => $settings['publish_end_date'] ?? null
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}

