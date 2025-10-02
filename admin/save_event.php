<?php
session_start();
if (!isset($_SESSION['admin_loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $event_name = trim($_POST['event_name']);
    // responsible_unit is now optional, so we just trim it.
    $responsible_unit = trim($_POST['responsible_unit']); 
    $notes = trim($_POST['notes']);
    $date_type = $_POST['date_type'];

    // --- Validation ---
    $dates = [];
    $is_date_valid = false;

    if ($date_type === 'single' && !empty($_POST['single_date'])) {
        $dates[] = $_POST['single_date'];
        $is_date_valid = true;
    } elseif ($date_type === 'continuous' && !empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $start = new DateTime($_POST['start_date']);
        $end = new DateTime($_POST['end_date']);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        $is_date_valid = true;
    } elseif ($date_type === 'non-continuous' && isset($_POST['non_continuous_dates'])) {
        foreach ($_POST['non_continuous_dates'] as $date) {
            if (!empty($date)) {
                $dates[] = $date;
            }
        }
        if (!empty($dates)) {
            $is_date_valid = true;
        }
    }

    // Check if event name or dates are empty
    if (empty($event_name) || !$is_date_valid) {
        $_SESSION['error_message'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (ชื่อกิจกรรม และ วันที่)";
        // Redirect back to the form with the ID if it exists
        $redirect_url = 'event_form.php' . (!empty($id) ? '?id=' . $id : '');
        header('Location: ' . $redirect_url);
        exit;
    }

    // --- Database Operation ---
    $pdo->beginTransaction();
    try {
        if (empty($id)) {
            // Insert new event
            $stmt = $pdo->prepare("INSERT INTO events (event_name, responsible_unit, notes) VALUES (?, ?, ?)");
            $stmt->execute([$event_name, $responsible_unit, $notes]);
            $event_id = $pdo->lastInsertId();
            $_SESSION['success_message'] = "เพิ่มกิจกรรมเรียบร้อยแล้ว";
        } else {
            // Update existing event
            $stmt = $pdo->prepare("UPDATE events SET event_name = ?, responsible_unit = ?, notes = ? WHERE id = ?");
            $stmt->execute([$event_name, $responsible_unit, $notes, $id]);
            $event_id = $id;

            // Delete old dates before inserting new ones
            $stmt_delete = $pdo->prepare("DELETE FROM event_dates WHERE event_id = ?");
            $stmt_delete->execute([$event_id]);
            $_SESSION['success_message'] = "แก้ไขข้อมูลกิจกรรมเรียบร้อยแล้ว";
        }

        // Insert event dates
        if (!empty($dates)) {
            $stmt_date = $pdo->prepare("INSERT INTO event_dates (event_id, activity_date) VALUES (?, ?)");
            foreach ($dates as $date) {
                $stmt_date->execute([$event_id, $date]);
            }
        }
        
        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }
}

header('Location: events.php');
exit;

