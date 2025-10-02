<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config.php';

$response = [
    'show' => false,
    'text' => '',
    'speed' => '20s',
    'color' => '#000000'
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

    $today = date('Y-m-d');
    $stmt_events = $pdo->prepare("
        SELECT e.event_name, e.responsible_unit, e.notes 
        FROM events e
        JOIN event_dates ed ON e.id = ed.event_id
        WHERE ed.activity_date = ? AND e.is_hidden = 0
        ORDER BY e.event_name
    ");
    $stmt_events->execute([$today]);
    $todays_events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    $ticker_parts = [];
    if (!empty($todays_events)) {
        $event_texts = [];
        foreach ($todays_events as $event) {
            $text = htmlspecialchars($event['event_name']);
            if (!empty($event['responsible_unit'])) {
                $text .= ' (' . htmlspecialchars($event['responsible_unit']) . ')';
            }
            // CORRECTED: Append the actual note text instead of '[*]'
            if (!empty($event['notes'])) {
                $text .= ' - หมายเหตุ: ' . htmlspecialchars($event['notes']);
            }
            $event_texts[] = $text;
        }
        $ticker_parts[] = "กิจกรรมวันนี้: " . implode(' ••• ', $event_texts);
    }

    if (!empty($settings['ticker_custom_message'])) {
        $ticker_parts[] = htmlspecialchars($settings['ticker_custom_message']);
    }

    $response['text'] = implode(' •••• ', $ticker_parts);

    echo json_encode($response);

} catch (PDOException $e) {
    // Return a generic error to prevent exposing database details
    echo json_encode(['error' => 'Database query failed']);
}

