<?php
// File: search.php
// Location: /api/
header('Content-Type: application/json');
require_once '../config.php';

// Helper function to format date into short Thai day format
function format_thai_date_short_day($date_str, $include_month_year = true) {
    if (empty($date_str)) return '';
    $thai_day_short = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
    $thai_month_full = [1=>'มกราคม', 2=>'กุมภาพันธ์', 3=>'มีนาคม', 4=>'เมษายน', 5=>'พฤษภาคม', 6=>'มิถุนายน', 7=>'กรกฎาคม', 8=>'สิงหาคม', 9=>'กันยายน', 10=>'ตุลาคม', 11=>'พฤศจิกายน', 12=>'ธันวาคม'];
    try {
        $date = new DateTime($date_str);
        $day_name = $thai_day_short[$date->format('w')];
        $day_number = (int)$date->format('j');
        
        if (!$include_month_year) { return "{$day_name}. {$day_number}"; }
        
        $month_name = $thai_month_full[(int)$date->format('n')];
        $year_be = (int)$date->format('Y') + 543;
        return "{$day_name}. {$day_number} {$month_name} {$year_be}";
    } catch (Exception $e) { return $date_str; }
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    $start_date_str = isset($settings['publish_start_date']) ? $settings['publish_start_date'] . '-01' : '1970-01-01';
    $end_date_str = isset($settings['publish_end_date']) ? $settings['publish_end_date'] . '-01' : '2099-12-01';
    $end_date_obj = new DateTime($end_date_str);
    $end_date_obj->modify('last day of this month');
    $end_date_formatted = $end_date_obj->format('Y-m-d');

    $search_term = "%{$query}%";
    $results = [];

    // 1. Search Events
    $stmt_events = $pdo->prepare("
        SELECT e.id, e.event_name as name, e.responsible_unit, e.notes, MIN(ed.activity_date) as first_date, GROUP_CONCAT(ed.activity_date ORDER BY ed.activity_date ASC) as dates
        FROM events e
        JOIN event_dates ed ON e.id = ed.event_id
        WHERE (e.event_name LIKE ? OR e.responsible_unit LIKE ? OR e.notes LIKE ?) AND e.is_hidden = 0
        GROUP BY e.id
        HAVING MAX(ed.activity_date) >= ? AND MIN(ed.activity_date) <= ?
    ");
    $stmt_events->execute([$search_term, $search_term, $search_term, $start_date_str, $end_date_formatted]);
    $event_results = $stmt_events->fetchAll(PDO::FETCH_ASSOC);
    foreach ($event_results as $row) {
        $row['type'] = 'event';
        $results[] = $row;
    }

    // 2. Search Holidays
    $stmt_holidays = $pdo->prepare("
        SELECT id, holiday_name as name, holiday_date as first_date, holiday_date as dates
        FROM special_holidays
        WHERE holiday_name LIKE ? AND is_hidden = 0 AND holiday_date BETWEEN ? AND ?
    ");
    $stmt_holidays->execute([$search_term, $start_date_str, $end_date_formatted]);
    $holiday_results = $stmt_holidays->fetchAll(PDO::FETCH_ASSOC);
    foreach ($holiday_results as $row) {
        $row['type'] = 'holiday';
        $row['responsible_unit'] = null;
        $row['notes'] = null;
        $results[] = $row;
    }

    // 3. Sort all results by date
    usort($results, fn($a, $b) => strcmp($a['first_date'], $b['first_date']));

    // 4. Format dates for display
    foreach ($results as &$item) {
        if (empty($item['dates'])) continue;
        $date_array_str = explode(',', $item['dates']);
        
        if (count($date_array_str) == 1) {
            $item['dates'] = format_thai_date_short_day($date_array_str[0]);
        } else {
            $date_objects = array_map(fn($d) => new DateTime($d), $date_array_str);
            $is_continuous = true;
            for ($i = 0; $i < count($date_objects) - 1; $i++) {
                if ($date_objects[$i+1]->diff($date_objects[$i])->days != 1) {
                    $is_continuous = false;
                    break;
                }
            }
            if ($is_continuous) {
                $start_date = $date_objects[0];
                $end_date = end($date_objects);
                if ($start_date->format('Y-m') === $end_date->format('Y-m')) {
                    $start_part = format_thai_date_short_day($start_date->format('Y-m-d'), false);
                    $end_part = format_thai_date_short_day($end_date->format('Y-m-d'), true);
                    $item['dates'] = "{$start_part} - {$end_part}";
                } else {
                    $start_part = format_thai_date_short_day($start_date->format('Y-m-d'), true);
                    $end_part = format_thai_date_short_day($end_date->format('Y-m-d'), true);
                    $item['dates'] = "{$start_part} - {$end_part}";
                }
            } else {
                $item['dates'] = implode(', ', array_map(fn($d) => format_thai_date_short_day($d), $date_array_str));
            }
        }
    }
    
    echo json_encode($results);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
