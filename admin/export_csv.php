<?php
session_start();
if (!isset($_SESSION['admin_loggedin'])) {
    exit('Access Denied');
}
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_ids']) && is_array($_POST['event_ids'])) {
    $event_ids = $_POST['event_ids'];

    if (empty($event_ids)) {
        $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมอย่างน้อย 1 รายการเพื่อ Export";
        header('Location: backup.php');
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($event_ids), '?'));

    try {
        $stmt = $pdo->prepare("
            SELECT e.event_name, e.responsible_unit, e.notes, GROUP_CONCAT(ed.activity_date ORDER BY ed.activity_date ASC) as dates
            FROM events e
            LEFT JOIN event_dates ed ON e.id = ed.event_id
            WHERE e.id IN ($placeholders)
            GROUP BY e.id
        ");
        $stmt->execute($event_ids);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "events_export_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM to make Excel happy
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Add headers
        fputcsv($output, ['event_name', 'responsible_unit', 'notes', 'dates']);

        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
        header('Location: backup.php');
        exit;
    }
} else {
    $_SESSION['error_message'] = "ไม่มีกิจกรรมที่ถูกเลือก";
    header('Location: backup.php');
    exit;
}
