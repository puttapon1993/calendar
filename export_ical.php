<?php
// Set internal encoding to UTF-8 for proper string handling.
mb_internal_encoding("UTF-8");
require_once 'config.php';

// --- NEW ROBUST iCAL GENERATOR FUNCTION ---
function generate_ical_event($event, $dates) {
    // --- Data Preparation ---
    $summary = $event['event_name'];
    $description_parts = [];
    if (!empty($event['responsible_unit'])) {
        $description_parts[] = "หน่วยงานที่รับผิดชอบ: " . $event['responsible_unit'];
    }
    if (!empty($event['notes'])) {
        $description_parts[] = "หมายเหตุ: " . $event['notes'];
    }
    $description = implode('\n', $description_parts);

    // Escape special iCal characters
    $summary = str_replace(['\\', ',', ';'], ['\\\\', '\,', '\;'], $summary);
    $description = str_replace(['\\', ',', ';'], ['\\\\', '\,', '\;'], $description);
    $description = str_replace(["\r\n", "\r", "\n"], "\\n", $description);

    // Folds a long string to 75 bytes per line, as required by the standard.
    function fold_line($key, $value) {
        $line = $key . ':' . $value;
        $crlf = "\r\n";
        $output = '';
        while (strlen($line) > 75) {
            $break_pos = 75;
            while ($break_pos > 0 && (ord($line[$break_pos]) & 0xC0) === 0x80) { $break_pos--; }
            $space_pos = strrpos(substr($line, 0, $break_pos), ' ');
            $break_at = ($space_pos !== false) ? $space_pos : $break_pos;
            $output .= substr($line, 0, $break_at) . $crlf;
            $line = ' ' . substr($line, $break_at + ($space_pos !== false ? 1 : 0));
        }
        $output .= $line . $crlf;
        return $output;
    }
    
    // --- Building the iCal content ---
    $crlf = "\r\n";
    $ical = "BEGIN:VCALENDAR" . $crlf;
    $ical .= "VERSION:2.0" . $crlf;
    $ical .= "PRODID:-//[ชื่อย่อโรงเรียน]//ปฏิทินกิจกรรมโรงเรียน//TH" . $crlf;
    $ical .= "BEGIN:VEVENT" . $crlf;
    $ical .= "UID:" . uniqid() . "-event-" . $event['id'] . "@yourschool.com" . $crlf;
    $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . $crlf;
    $ical .= fold_line("SUMMARY", $summary);
    if (!empty($description)) {
        $ical .= fold_line("DESCRIPTION", $description);
    }

    // **CRITICAL FIX: Check for date continuity**
    $date_objects = array_map(fn($d) => new DateTime($d), $dates);
    
    if (count($date_objects) == 1) {
        // --- SINGLE DAY EVENT (CORRECTED) ---
        $start_date = $date_objects[0];
        // For a single all-day event, DTEND is the next day.
        $end_date = (clone $start_date)->modify('+1 day');
        $ical .= "DTSTART;VALUE=DATE:" . $start_date->format('Ymd') . $crlf;
        $ical .= "DTEND;VALUE=DATE:" . $end_date->format('Ymd') . $crlf;
    } else {
        $is_continuous = true;
        for ($i = 0; $i < count($date_objects) - 1; $i++) {
            if ($date_objects[$i+1]->diff($date_objects[$i])->days != 1) {
                $is_continuous = false;
                break;
            }
        }

        if ($is_continuous) {
            // --- CONTINUOUS MULTI-DAY EVENT ---
            $start_date = $date_objects[0];
            // DTEND is the day AFTER the last day of the event (standard practice).
            $end_date = (clone end($date_objects))->modify('+1 day');
            $ical .= "DTSTART;VALUE=DATE:" . $start_date->format('Ymd') . $crlf;
            $ical .= "DTEND;VALUE=DATE:" . $end_date->format('Ymd') . $crlf;
        } else {
            // --- NON-CONTINUOUS EVENT ---
            $first_date = $date_objects[0];
            $ical .= "DTSTART;VALUE=DATE:" . $first_date->format('Ymd') . $crlf;
            $ical .= "DURATION:P1D" . $crlf; 
            $rdate_values = [];
            foreach ($date_objects as $date) {
                $rdate_values[] = $date->format('Ymd');
            }
            $ical .= fold_line("RDATE;VALUE=DATE", implode(',', $rdate_values));
        }
    }

    $ical .= "END:VEVENT" . $crlf;
    $ical .= "END:VCALENDAR" . $crlf;
    
    return $ical;
}

// --- Main Logic ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Invalid Event ID');
}
$event_id = (int)$_GET['id'];

try {
    // 1. Fetch event details
    $stmt_event = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt_event->execute([$event_id]);
    $event = $stmt_event->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        die('Event not found.');
    }

    // 2. Fetch all dates for this event
    $stmt_dates = $pdo->prepare("SELECT activity_date FROM event_dates WHERE event_id = ? ORDER BY activity_date ASC");
    $stmt_dates->execute([$event_id]);
    $dates = $stmt_dates->fetchAll(PDO::FETCH_COLUMN);

    if (empty($dates)) {
         http_response_code(404);
         die('No dates found for this event.');
    }

    // 3. Generate the iCal content using the robust function
    $ical_content = generate_ical_event($event, $dates);

    // Create a UTF-8 compatible filename for modern browsers.
    $fallback_filename = preg_replace('/[^a-z0-9_.-]/i', '_', $event['event_name']) . '.ics';
    $utf8_filename = $event['event_name'] . '.ics';

    // 4. Set HTTP headers for download
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fallback_filename . '"; filename*=UTF-8\'\'' . rawurlencode($utf8_filename));
    header('Content-Length: ' . strlen($ical_content));

    // 5. Output the content
    echo $ical_content;
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    die("Database Error: " . $e->getMessage());
}
?>

