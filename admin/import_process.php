<?php
session_start();
if (!isset($_SESSION['admin_loggedin'])) {
    exit('Access Denied');
}
require_once '../config.php';

$summary = [
    'logs' => [],
    'errors' => []
];

// Retrieve the preview data stored securely in the session
$import_preview_data = $_SESSION['import_preview_data'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_indices']) && is_array($_POST['selected_indices']) && !empty($import_preview_data)) {
    
    $pdo->beginTransaction();
    try {
        // Prepare statements outside the loop for efficiency
        $stmt_insert_event = $pdo->prepare("INSERT INTO events (event_name, responsible_unit, notes) VALUES (?, ?, ?)");
        $stmt_insert_date = $pdo->prepare("INSERT INTO event_dates (event_id, activity_date) VALUES (?, ?)");

        // Loop through the INDEXES of the selected events
        foreach ($_POST['selected_indices'] as $index) {
            // Get the full event data from our session array using the index
            if (!isset($import_preview_data[$index])) {
                $summary['errors'][] = "ไม่พบข้อมูลสำหรับ index {$index} ใน session";
                continue;
            }
            $event_data = $import_preview_data[$index];

            // Step 1: Insert into `events` table
            $stmt_insert_event->execute([
                $event_data['event_name'],
                $event_data['responsible_unit'],
                $event_data['notes']
            ]);
            
            // Step 2: Get the ID of the new event
            $event_id = $pdo->lastInsertId();
            if (!$event_id) {
                throw new Exception("ไม่สามารถสร้าง ID สำหรับ '" . htmlspecialchars($event_data['event_name']) . "' ได้");
            }
            $summary['logs'][] = "เพิ่มกิจกรรม: '" . htmlspecialchars($event_data['event_name']) . "' (ID: {$event_id})";

            // Step 3: Insert into `event_dates` table using the new ID
            if (!empty($event_data['dates'])) {
                $dates = explode(',', $event_data['dates']);
                $date_added_count = 0;
                foreach ($dates as $date) {
                    $clean_date = trim($date);
                    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $clean_date)) {
                        $stmt_insert_date->execute([$event_id, $clean_date]);
                        $date_added_count++;
                    }
                }
                $summary['logs'][] = " -> เพิ่มข้อมูลวันที่ {$date_added_count} วัน";
            }
        }
        
        $pdo->commit();
        $_SESSION['import_summary'] = $summary;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['import_error'] = "เกิดข้อผิดพลาดร้ายแรงระหว่าง Import: " . $e->getMessage();
    }
} else {
    if (empty($import_preview_data)) {
         $_SESSION['import_error'] = "ไม่พบข้อมูลสำหรับ Import หรือ Session หมดอายุ กรุณาลองอัปโหลดไฟล์อีกครั้ง";
    } else {
        $_SESSION['import_error'] = "คุณไม่ได้เลือกกิจกรรมใดๆ เพื่อนำเข้า";
    }
}

// Clean up the session variable after processing is complete
unset($_SESSION['import_preview_data']);

header('Location: backup.php');
exit;

