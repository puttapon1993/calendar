<?php
// File: get_ticker_data.php
// Location: /api/
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config.php';

$response = [
    'show' => false,
    'text' => '',
    'speed' => '20s',
    'color' => '#000000',
    'bgColor' => '#ffffff'
];

try {
    $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    if (empty($settings['show_event_ticker']) || $settings['show_event_ticker'] === '0') {
        echo json_encode($response);
        exit;
    }

    $response['show'] = true;
    $response['speed'] = ($settings['ticker_speed'] ?? 20) . 's';
    $response['color'] = $settings['ticker_text_color'] ?? '#000000';
    $response['bgColor'] = $settings['ticker_bg_color'] ?? '#ffffff';
    
    $separator = $settings['ticker_separator'] ?? ' ••• ';
    $notes_prefix = $settings['ticker_notes_prefix'] ?? 'หมายเหตุ:';
    $show_holidays = ($settings['ticker_show_holidays'] ?? '1') === '1';

    $today = date('Y-m-d');
    $ticker_items = [];

    // 1. Fetch Today's Events
    $stmt_events = $pdo->prepare("
        SELECT e.event_name, e.responsible_unit, e.notes 
        FROM events e
        JOIN event_dates ed ON e.id = ed.event_id
        WHERE ed.activity_date = ? AND e.is_hidden = 0
        ORDER BY e.event_name
    ");
    $stmt_events->execute([$today]);
    $todays_events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    foreach ($todays_events as $event) {
        $text = htmlspecialchars($event['event_name']);
        if (!empty($event['responsible_unit'])) {
            $text .= ' (' . htmlspecialchars($event['responsible_unit']) . ')';
        }
        if (!empty($event['notes'])) {
            $text .= ' - ' . htmlspecialchars($notes_prefix) . ' ' . htmlspecialchars($event['notes']);
        }
        $ticker_items[] = $text;
    }
    
    // 2. Fetch Today's Holidays if enabled
    if ($show_holidays) {
        $stmt_holidays = $pdo->prepare("SELECT holiday_name FROM special_holidays WHERE holiday_date = ? AND is_hidden = 0");
        $stmt_holidays->execute([$today]);
        $todays_holidays = $stmt_holidays->fetchAll(PDO::FETCH_COLUMN);
        foreach ($todays_holidays as $holiday_name) {
            $ticker_items[] = htmlspecialchars($holiday_name);
        }
    }

    // 3. Combine with custom message
    $final_parts = [];
    if (!empty($ticker_items)) {
        $final_parts[] = "กิจกรรมวันนี้ : " . implode($separator, $ticker_items);
    }

    if (!empty($settings['ticker_custom_message'])) {
        $final_parts[] = htmlspecialchars($settings['ticker_custom_message']);
    }

    // Use a single separator between the activity block and the custom message block.
    $response['text'] = implode($separator, $final_parts);

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed']);
}

