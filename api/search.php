<?php
header('Content-Type: application/json');
require_once '../config.php';

// Helper function to format date into short Thai day format
function format_thai_date_short_day($date_str, $include_month_year = true) {
    if (empty($date_str)) return '';
    $thai_day_short = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
    $thai_month_full = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    try {
        $date = new DateTime($date_str);
        $day_name = $thai_day_short[$date->format('w')];
        $day_number = (int)$date->format('j');
        
        if (!$include_month_year) {
             return "{$day_name}. {$day_number}";
        }
        
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
    // 1. Get publication settings
    $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    $start_date_str = isset($settings['publish_start_date']) ? $settings['publish_start_date'] . '-01' : '1970-01-01';
    $end_date_str = isset($settings['publish_end_date']) ? $settings['publish_end_date'] . '-01' : '2099-12-01';
    
    $end_date_obj = new DateTime($end_date_str);
    $end_date_obj->modify('last day of this month');
    $end_date_formatted = $end_date_obj->format('Y-m-d');

    // 2. Perform search query with date range filter
    $search_term = "%{$query}%";
    $stmt = $pdo->prepare("
        SELECT e.id, e.event_name, e.responsible_unit, e.notes, GROUP_CONCAT(ed.activity_date ORDER BY ed.activity_date ASC) as dates
        FROM events e
        LEFT JOIN event_dates ed ON e.id = ed.event_id
        WHERE 
            (e.event_name LIKE ? OR e.responsible_unit LIKE ? OR e.notes LIKE ?) 
            AND e.is_hidden = 0
            AND ed.activity_date BETWEEN ? AND ?
        GROUP BY e.id
        HAVING dates IS NOT NULL
        ORDER BY MIN(ed.activity_date) ASC
    ");
    $stmt->execute([$search_term, $search_term, $search_term, $start_date_str, $end_date_formatted]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Format the dates before sending the response
    foreach ($results as &$event) { // Use reference to modify the array directly
        if (!empty($event['dates'])) {
            $date_array_str = explode(',', $event['dates']);
            
            if (count($date_array_str) == 1) {
                // Case 1: Single date
                $event['dates'] = format_thai_date_short_day($date_array_str[0]);
            } else {
                // Case 2: Multi-day, check for continuity
                $date_objects = [];
                foreach ($date_array_str as $date_str) {
                    $date_objects[] = new DateTime($date_str);
                }

                $is_continuous = true;
                for ($i = 0; $i < count($date_objects) - 1; $i++) {
                    if ($date_objects[$i+1]->diff($date_objects[$i])->days != 1) {
                        $is_continuous = false;
                        break;
                    }
                }

                if ($is_continuous) {
                    // It's a continuous range
                    $start_date = $date_objects[0];
                    $end_date = end($date_objects);

                    if ($start_date->format('Y-m') === $end_date->format('Y-m')) {
                        // Same month and year, format compactly
                        $start_part = format_thai_date_short_day($start_date->format('Y-m-d'), false); // e.g., "ส. 27"
                        $end_part = format_thai_date_short_day($end_date->format('Y-m-d'), true);     // e.g., "อา. 28 กันยายน 2568"
                        $event['dates'] = "{$start_part} - {$end_part}";
                    } else {
                        // Different months or years, show full info for both
                        $start_part = format_thai_date_short_day($start_date->format('Y-m-d'), true);
                        $end_part = format_thai_date_short_day($end_date->format('Y-m-d'), true);
                        $event['dates'] = "{$start_part} - {$end_part}";
                    }
                } else {
                    // Not continuous, format each date individually
                    $formatted_date_array = [];
                    foreach ($date_array_str as $date_str) {
                        $formatted_date_array[] = format_thai_date_short_day($date_str);
                    }
                    $event['dates'] = implode(', ', $formatted_date_array);
                }
            }
        }
    }
    
    echo json_encode($results);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}

