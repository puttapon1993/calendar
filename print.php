<?php
// Suppress errors on the print page for a cleaner output
ini_set('display_errors', 0);
error_reporting(0);
require_once 'config.php';

// =============================================================================
// PHP HELPER FUNCTIONS (SERVER-SIDE RENDERING LOGIC)
// =============================================================================

function get_settings($pdo) {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        return [];
    }
}

function get_available_months($pdo, $settings) {
    $start_date_str = ($settings['publish_start_date'] ?? '1970-01') . '-01';
    $end_date_str = ($settings['publish_end_date'] ?? '2099-12') . '-01';
    $end_date_obj = new DateTime($end_date_str);
    $end_date_obj->modify('last day of this month');
    $end_date_formatted = $end_date_obj->format('Y-m-d');
    $stmt = $pdo->prepare("SELECT DISTINCT YEAR(activity_date) as year, MONTH(activity_date) as month FROM event_dates WHERE activity_date BETWEEN ? AND ? ORDER BY year, month");
    $stmt->execute([$start_date_str, $end_date_formatted]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_month_data($pdo, $year, $month) {
    $stmt_events = $pdo->prepare("SELECT e.event_name, e.responsible_unit, ed.activity_date FROM events e JOIN event_dates ed ON e.id = ed.event_id WHERE e.is_hidden = 0 AND YEAR(ed.activity_date) = ? AND MONTH(ed.activity_date) = ? ORDER BY ed.activity_date, e.event_name");
    $stmt_events->execute([$year, $month]);
    $all_events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    $events_by_day = [];
    foreach ($all_events as $row) {
        $events_by_day[$row['activity_date']][] = $row;
    }

    $stmt_holidays = $pdo->prepare("SELECT holiday_date, holiday_name FROM special_holidays WHERE YEAR(holiday_date) = ? AND MONTH(holiday_date) = ?");
    $stmt_holidays->execute([$year, $month]);
    $holidays_by_day = $stmt_holidays->fetchAll(PDO::FETCH_KEY_PAIR);

    return ['events' => $events_by_day, 'holidays' => $holidays_by_day];
}

function render_calendar_php($year, $month, $data, $settings) {
    $thai_months_full = [1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'];
    $week_start_day = ($settings['week_start_day'] ?? 'sunday') === 'monday' ? 1 : 0;
    $day_headers = $week_start_day === 1 ? ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'] : ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

    $html = "<h2>" . $thai_months_full[$month] . ' พ.ศ. ' . ($year + 543) . "</h2>";
    $html .= '<div class="calendar-grid">';
    foreach ($day_headers as $day) {
        $html .= '<div class="calendar-day day-header">' . $day . '</div>';
    }

    $first_day_of_month = (int)date('w', strtotime("$year-$month-01"));
    $days_in_month = (int)date('t', strtotime("$year-$month-01"));
    $start_offset = ($first_day_of_month - $week_start_day + 7) % 7;

    for ($i = 0; $i < $start_offset; $i++) {
        $html .= '<div class="calendar-day empty"></div>';
    }

    for ($day = 1; $day <= $days_in_month; $day++) {
        $date_key = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $day_of_week = (int)date('w', strtotime($date_key));
        
        $classes = 'calendar-day';
        if (isset($data['events'][$date_key])) $classes .= ' has-event';
        if (isset($data['holidays'][$date_key])) $classes .= ' is-holiday';
        if ($day_of_week === 6) $classes .= ' is-saturday';
        if ($day_of_week === 0) $classes .= ' is-sunday';
        
        $html .= '<div class="' . $classes . '"><span class="day-number">' . $day . '</span>';
        if (isset($data['holidays'][$date_key])) {
            $html .= '<div class="event-item holiday-item">' . htmlspecialchars($data['holidays'][$date_key]) . '</div>';
        }
        if (isset($data['events'][$date_key])) {
            foreach ($data['events'][$date_key] as $event) {
                $event_text = htmlspecialchars($event['event_name']);
                if(!empty($event['responsible_unit'])) {
                     $event_text .= ' (' . htmlspecialchars($event['responsible_unit']) . ')';
                }
                $html .= '<div class="event-item">' . $event_text . '</div>';
            }
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

function render_table_php($year, $month, $data, $settings) {
    $thai_months_full = [1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'];
    $thai_day_short = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
     
    $html = "<h2>กิจกรรมเดือน " . $thai_months_full[$month] . ' พ.ศ. ' . ($year + 543) . "</h2>";
    $html .= '<table class="event-table"><thead><tr><th style="width: 25%;">วันที่</th><th>ชื่อกิจกรรม</th><th style="width: 30%;">หน่วยงาน</th></tr></thead><tbody>';

    $all_events_flat = [];
    foreach($data['events'] as $date => $events_on_day) {
        foreach($events_on_day as $event) {
            $all_events_flat[] = $event;
        }
    }

    if(empty($all_events_flat)) {
        $html .= '<tr><td colspan="3" style="text-align:center;">ไม่มีกิจกรรมในเดือนนี้</td></tr>';
    } else {
        // Sort by date just in case
        usort($all_events_flat, fn($a, $b) => strcmp($a['activity_date'], $b['activity_date']));
        foreach ($all_events_flat as $event) {
           $date_obj = new DateTime($event['activity_date']);
           $formatted_date = "({$thai_day_short[$date_obj->format('w')]}) {$date_obj->format('j')} {$thai_months_full[(int)$date_obj->format('n')]} " . ($date_obj->format('Y') + 543);
           $html .= '<tr>';
           $html .= '<td>' . htmlspecialchars($formatted_date) . '</td>';
           $html .= '<td>' . htmlspecialchars($event['event_name']) . '</td>';
           $html .= '<td>' . htmlspecialchars($event['responsible_unit']) . '</td>';
           $html .= '</tr>';
        }
    }
    $html .= '</tbody></table>';
    return $html;
}

// --- Main Page Logic ---
$settings = get_settings($pdo);
$site_title = $settings['site_title'] ?? 'ปฏิทินกิจกรรม';
$view = $_GET['view'] ?? 'calendar';
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$month = isset($_GET['month']) ? (int)$_GET['month'] : null;

$page_orientation = 'portrait';
if ($view === 'calendar' || $view === 'all_calendars' || $view === 'mixed') {
    $page_orientation = 'landscape';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>พิมพ์ - <?php echo htmlspecialchars($site_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/print_style.css">
    <style> 
        @page { size: A4 <?php echo $page_orientation; ?>; margin: 0.5in; } 
        /* --- Dynamic Colors from Settings --- */
        .calendar-day.has-event { background-color: <?php echo htmlspecialchars($settings['event_date_bg_color'] ?? '#d9edf7'); ?> !important; }
        .calendar-day.is-holiday { background-color: <?php echo htmlspecialchars($settings['holiday_bg_color'] ?? '#f2dede'); ?> !important; }
        .calendar-day.is-saturday { background-color: <?php echo htmlspecialchars($settings['saturday_bg_color'] ?? '#f0f8ff'); ?> !important; }
        .calendar-day.is-sunday { background-color: <?php echo htmlspecialchars($settings['sunday_bg_color'] ?? '#fff0f0'); ?> !important; }
        .calendar-day:not(.has-event):not(.is-holiday):not(.is-saturday):not(.is-sunday) {
             background-color: <?php echo htmlspecialchars($settings['no_event_date_bg_color'] ?? '#fafafa'); ?> !important;
        }
    </style>
</head>
<body>
<?php
if ($view === 'all_calendars' || $view === 'all_tables' || $view === 'all_mixed') {
    $available_months = get_available_months($pdo, $settings);
    foreach($available_months as $month_info) {
        $m_year = $month_info['year'];
        $m_month = $month_info['month'];
        $month_data = get_month_data($pdo, $m_year, $m_month);
        echo '<div class="print-month-group">';
        if ($view === 'all_calendars') {
            echo render_calendar_php($m_year, $m_month, $month_data, $settings);
        } elseif ($view === 'all_tables') {
            echo render_table_php($m_year, $m_month, $month_data, $settings);
        } elseif ($view === 'all_mixed') {
            echo "<div class='page-landscape'>";
            echo render_calendar_php($m_year, $m_month, $month_data, $settings);
            echo "</div>";
            echo "<div class='page-portrait'>";
            echo render_table_php($m_year, $m_month, $month_data, $settings);
            echo "</div>";
        }
        echo '</div>';
    }
} else if (!is_null($year) && !is_null($month)) {
    $month_data = get_month_data($pdo, $year, $month);
    echo '<div class="print-month-group">';
    if ($view === 'calendar') {
        echo render_calendar_php($year, $month, $month_data, $settings);
    } elseif ($view === 'table') {
        echo render_table_php($year, $month, $month_data, $settings);
    } elseif ($view === 'mixed') {
        echo "<div class='page-landscape'>";
        echo render_calendar_php($year, $month, $month_data, $settings);
        echo "</div>";
        echo "<div class='page-portrait'>";
        echo render_table_php($year, $month, $month_data, $settings);
        echo "</div>";
    }
    echo '</div>';
}
?>
<script>
    window.onload = function() {
        window.print();
        setTimeout(function() { window.close(); }, 100);
    }
</script>
</body>
</html>

