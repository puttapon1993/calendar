<?php
// File: draft_search.php
// Location: /api/
header('Content-Type: application/json');

// Security check for logged-in users
session_start();
if (!isset($_SESSION['user_loggedin']) || $_SESSION['user_loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Access Denied']);
    exit;
}

require_once '../config.php';

// Helper function to format date into short Thai day format
function format_thai_date_short_day($date_str) {
    if (empty($date_str)) return '';
    $thai_day_short = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
    $thai_month_full = [
        1=>'มกราคม', 2=>'กุมภาพันธ์', 3=>'มีนาคม', 4=>'เมษายน', 5=>'พฤษภาคม', 6=>'มิถุนายน',
        7=>'กรกฎาคม', 8=>'สิงหาคม', 9=>'กันยายน', 10=>'ตุลาคม', 11=>'พฤศจิกายน', 12=>'ธันวาคม'
    ];
    try {
        $date = new DateTime($date_str);
        $day_name = $thai_day_short[$date->format('w')];
        $day_number = (int)$date->format('j');
        $month_name = $thai_month_full[(int)$date->format('n')];
        $year_be = (int)$date->format('Y') + 543;
        return "{$day_name}. {$day_number} {$month_name} {$year_be}";
    } catch (Exception $e) {
        return $date_str;
    }
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $search_term = "%{$query}%";
    
    // Query for events
    $stmt_events = $pdo->prepare("
        SELECT 'event' as type, e.id, e.event_name as name, e.responsible_unit, e.notes, GROUP_CONCAT(ed.activity_date ORDER BY ed.activity_date ASC) as dates
        FROM events e
        LEFT JOIN event_dates ed ON e.id = ed.event_id
        WHERE (e.event_name LIKE ? OR e.responsible_unit LIKE ?)
        GROUP BY e.id
        HAVING dates IS NOT NULL
    ");
    $stmt_events->execute([$search_term, $search_term]);
    $events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    // Query for holidays
    $stmt_holidays = $pdo->prepare("
        SELECT 'holiday' as type, id, holiday_name as name, '' as responsible_unit, '' as notes, holiday_date as dates
        FROM special_holidays
        WHERE holiday_name LIKE ?
    ");
    $stmt_holidays->execute([$search_term]);
    $holidays = $stmt_holidays->fetchAll(PDO::FETCH_ASSOC);

    $results = array_merge($events, $holidays);
    
    // Sort combined results by the earliest date
    usort($results, function($a, $b) {
        $dateA = substr($a['dates'], 0, 10);
        $dateB = substr($b['dates'], 0, 10);
        return strcmp($dateA, $dateB);
    });

    // Format the dates for display
    foreach ($results as &$item) {
        if (!empty($item['dates'])) {
            $date_array = explode(',', $item['dates']);
            $formatted_dates = array_map('format_thai_date_short_day', $date_array);
            $item['dates'] = implode(', ', $formatted_dates);
        }
    }
    
    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
