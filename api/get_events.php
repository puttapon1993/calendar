<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config.php';

// --- NEW ADVANCED HELPER FUNCTION FOR TABLE DATE FORMATTING ---
function format_table_date($date_str, $settings) {
    // Get all settings with defaults
    $month_format = $settings['table_month_format'] ?? 'full';
    $year_format = $settings['table_year_format'] ?? 'be_4';
    $day_name_format = $settings['table_day_name_format'] ?? 'short_dot';
    $day_name_style = $settings['table_day_name_style'] ?? 'parenthesis';
    
    try {
        $date = new DateTime($date_str);
    } catch (Exception $e) {
        return $date_str; // Return original if date is invalid
    }

    // 1. Format Day Name part
    $day_name_part = '';
    if ($day_name_format !== 'none') {
        $thai_day_full = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        $thai_day_short_dot = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
        $thai_day_short = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
        $day_index = (int)$date->format('w');

        $day_name = '';
        switch ($day_name_format) {
            case 'full_prefix': $day_name = 'วัน' . $thai_day_full[$day_index]; break;
            case 'full': $day_name = $thai_day_full[$day_index]; break;
            case 'short': $day_name = $thai_day_short[$day_index]; break;
            case 'short_dot': $day_name = $thai_day_short_dot[$day_index]; break;
        }

        switch ($day_name_style) {
            case 'parenthesis': $day_name_part = "({$day_name}) "; break;
            case 'bracket': $day_name_part = "[{$day_name}] "; break;
            case 'dash': $day_name_part = "{$day_name} - "; break;
            case 'slash': $day_name_part = "{$day_name} / "; break;
            case 'colon': $day_name_part = "{$day_name} : "; break;
            case 'none': $day_name_part = "{$day_name} "; break;
        }
    }

    // 2. Day Number part
    $day_number_part = (int)$date->format('j');

    // 3. Month part
    $thai_month_full = [1=>'มกราคม', 2=>'กุมภาพันธ์', 3=>'มีนาคม', 4=>'เมษายน', 5=>'พฤษภาคม', 6=>'มิถุนายน', 7=>'กรกฎาคม', 8=>'สิงหาคม', 9=>'กันยายน', 10=>'ตุลาคม', 11=>'พฤศจิกายน', 12=>'ธันวาคม'];
    $thai_month_short = [1=>'ม.ค.', 2=>'ก.พ.', 3=>'มี.ค.', 4=>'เม.ย.', 5=>'พ.ค.', 6=>'มิ.ย.', 7=>'ก.ค.', 8=>'ส.ค.', 9=>'ก.ย.', 10=>'ต.ค.', 11=>'พ.ย.', 12=>'ธ.ค.'];
    $month_index = (int)$date->format('n');
    $month_part = ($month_format === 'full') ? $thai_month_full[$month_index] : $thai_month_short[$month_index];

    // 4. Year part
    $year_part = '';
    $year_ce = (int)$date->format('Y');
    $year_be = $year_ce + 543;
    switch ($year_format) {
        case 'be_4': $year_part = $year_be; break;
        case 'be_2': $year_part = substr((string)$year_be, -2); break;
        case 'ce_4': $year_part = $year_ce; break;
        case 'ce_2': $year_part = substr((string)$year_ce, -2); break;
    }

    // 5. Combine everything
    return trim("{$day_name_part}{$day_number_part} {$month_part} {$year_part}");
}


try {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month_num = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

    if (!isset($pdo)) {
         throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }

    // Get all settings to pass to formatter
    $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    // --- 1. Fetch data for CALENDAR view ---
    $stmt_events_cal = $pdo->prepare("SELECT e.id, e.event_name, e.responsible_unit, e.notes, ed.activity_date FROM events e JOIN event_dates ed ON e.id = ed.event_id WHERE e.is_hidden = 0 AND YEAR(ed.activity_date) = ? AND MONTH(ed.activity_date) = ? ORDER BY ed.activity_date, e.event_name");
    $stmt_events_cal->execute([$year, $month_num]);
    $all_event_occurrences = $stmt_events_cal->fetchAll(PDO::FETCH_ASSOC);

    $events_by_day = [];
    foreach ($all_event_occurrences as $row) {
        $events_by_day[$row['activity_date']][] = [
            'id' => $row['id'], // Pass ID for iCal link
            'event_name' => $row['event_name'],
            'responsible_unit' => $row['responsible_unit'],
            'notes' => $row['notes']
        ];
    }
    
    // --- 2. LOGIC FOR TABLE view ---
    $stmt_ids = $pdo->prepare("SELECT DISTINCT event_id FROM event_dates WHERE YEAR(activity_date) = ? AND MONTH(activity_date) = ?");
    $stmt_ids->execute([$year, $month_num]);
    $event_ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);

    $event_list_for_table = [];
    if (!empty($event_ids)) {
        $id_placeholders = implode(',', array_fill(0, count($event_ids), '?'));
        $stmt_full_events = $pdo->prepare("SELECT e.id, e.event_name, e.responsible_unit, e.notes, GROUP_CONCAT(ed.activity_date ORDER BY ed.activity_date ASC) as all_dates FROM events e JOIN event_dates ed ON e.id = ed.event_id WHERE e.id IN ($id_placeholders) AND e.is_hidden = 0 GROUP BY e.id");
        $stmt_full_events->execute($event_ids);
        $full_events_data = $stmt_full_events->fetchAll(PDO::FETCH_ASSOC);

        $current_month_start = new DateTime("$year-$month_num-01");

        foreach ($full_events_data as $event) {
            $dates = explode(',', $event['all_dates']);
            $date_objects = array_map(fn($d) => new DateTime($d), $dates);

            $is_continuous = (count($date_objects) > 1) && ($date_objects[0]->diff(end($date_objects))->days == count($date_objects) - 1);
            
            if ($is_continuous) {
                $start_date = $date_objects[0];
                $end_date = end($date_objects);
                $is_carry_over = $start_date < $current_month_start;
                
                $start_part = format_table_date($start_date->format('Y-m-d'), $settings);
                $end_part = format_table_date($end_date->format('Y-m-d'), $settings);

                $event_list_for_table[] = [
                    'id' => $event['id'], // ADDED ID
                    'sort_key' => $is_carry_over ? 0 : 1,
                    'sort_date' => $start_date->format('Y-m-d'),
                    'event_name' => $event['event_name'],
                    'responsible_unit' => $event['responsible_unit'],
                    'formatted_dates' => "{$start_part} - {$end_part}"
                ];
            } else {
                foreach ($date_objects as $date) {
                    if ($date->format('Y-m') == $current_month_start->format('Y-m')) {
                        $event_list_for_table[] = [
                            'id' => $event['id'], // ADDED ID
                            'sort_key' => 1,
                            'sort_date' => $date->format('Y-m-d'),
                            'event_name' => $event['event_name'],
                            'responsible_unit' => $event['responsible_unit'],
                            'formatted_dates' => format_table_date($date->format('Y-m-d'), $settings)
                        ];
                    }
                }
            }
        }
    }
    // Sort the final list
    usort($event_list_for_table, function($a, $b) {
        if ($a['sort_key'] != $b['sort_key']) return $a['sort_key'] <=> $b['sort_key'];
        return strcmp($a['sort_date'], $b['sort_date']);
    });


    // --- Fetch Holidays ---
    $stmt_holidays = $pdo->prepare("SELECT holiday_date, holiday_name FROM special_holidays WHERE YEAR(holiday_date) = ? AND MONTH(holiday_date) = ?");
    $stmt_holidays->execute([$year, $month_num]);
    $holidays_by_day = $stmt_holidays->fetchAll(PDO::FETCH_KEY_PAIR);

    // --- Combine and send response ---
    echo json_encode([
        'events_for_calendar' => $events_by_day,
        'event_list_for_table' => $event_list_for_table,
        'holidays' => $holidays_by_day
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'General Error: ' . $e->getMessage()]);
}

