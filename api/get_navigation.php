<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    // 1. Get publication settings from the database
    $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Define the date range based on settings
    $start_date_str = isset($settings['publish_start_date']) ? $settings['publish_start_date'] . '-01' : '1970-01-01';
    $end_date_str = isset($settings['publish_end_date']) ? $settings['publish_end_date'] . '-01' : '2099-12-01';

    $end_date_obj = new DateTime($end_date_str);
    $end_date_obj->modify('last day of this month');
    $end_date_formatted = $end_date_obj->format('Y-m-d');
    
    // 3. Fetch distinct months from event_dates that fall WITHIN the specified date range
    $stmt = $pdo->prepare("
        SELECT DISTINCT YEAR(activity_date) as event_year, MONTH(activity_date) as event_month
        FROM event_dates
        WHERE activity_date BETWEEN ? AND ?
        ORDER BY event_year, event_month
    ");
    $stmt->execute([$start_date_str, $end_date_formatted]);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    $navigation = [];
    foreach ($months as $row) {
        $year_be = (int)$row['event_year'] + 543;
        $navigation[] = [
            'year' => (int)$row['event_year'],
            'month' => (int)$row['event_month'],
            'label' => $thai_months[(int)$row['event_month']] . ' ' . $year_be
        ];
    }
    
    // Package response with navigation and settings for JS
    echo json_encode([
        'navigation' => $navigation,
        'settings' => [
            'publish_start' => isset($settings['publish_start_date']) ? $settings['publish_start_date'] : null,
            'publish_end' => isset($settings['publish_end_date']) ? $settings['publish_end_date'] : null
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}

