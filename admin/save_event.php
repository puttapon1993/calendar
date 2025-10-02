<?php
// File: save_event.php
// Location: /admin/
session_start();
if (!isset($_SESSION['user_loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

$current_user_id = $_SESSION['user_id'];
$is_admin = is_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $event_name = trim($_POST['event_name']);
    $responsible_unit = trim($_POST['responsible_unit']); 
    $notes = trim($_POST['notes']);
    $date_type = $_POST['date_type'];
    $owner_ids_str = $_POST['owner_ids_str'] ?? '';
    $is_edit_mode = !empty($id);

    if ($is_edit_mode && !$is_admin) {
        $stmt_owner_check = $pdo->prepare("SELECT COUNT(*) FROM event_owners WHERE event_id = ? AND user_id = ?");
        $stmt_owner_check->execute([$id, $current_user_id]);
        if ($stmt_owner_check->fetchColumn() == 0) {
            $_SESSION['error_message'] = "คุณไม่มีสิทธิ์แก้ไขกิจกรรมนี้";
            header('Location: events.php');
            exit;
        }
    }

    $dates = [];
    $is_date_valid = false;
    if ($date_type === 'single' && !empty($_POST['single_date'])) {
        $dates[] = $_POST['single_date'];
        $is_date_valid = true;
    } elseif ($date_type === 'continuous' && !empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        try {
            $start = new DateTime($_POST['start_date']);
            $end = new DateTime($_POST['end_date']);
            if ($start > $end) {
                 $_SESSION['error_message'] = "วันที่เริ่มต้นต้องมาก่อนวันที่สิ้นสุด";
                 header('Location: event_form.php' . ($is_edit_mode ? '?id=' . $id : ''));
                 exit;
            }
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
            foreach ($period as $date) { $dates[] = $date->format('Y-m-d'); }
            $is_date_valid = true;
        } catch (Exception $e) { $is_date_valid = false; }
    } elseif ($date_type === 'non-continuous' && isset($_POST['non_continuous_dates'])) {
        foreach ($_POST['non_continuous_dates'] as $date) {
            if (!empty($date)) { $dates[] = $date; }
        }
        if (!empty($dates)) { $is_date_valid = true; }
    }

    if (empty($event_name) || !$is_date_valid) {
        $_SESSION['error_message'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (ชื่อกิจกรรม และ วันที่)";
        header('Location: event_form.php' . ($is_edit_mode ? '?id=' . $id : ''));
        exit;
    }
    
    $dates = array_unique($dates);
    
    $pdo->beginTransaction();
    try {
        if (!$is_edit_mode) {
            $stmt = $pdo->prepare("INSERT INTO events (event_name, responsible_unit, notes, created_by_user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$event_name, $responsible_unit, $notes, $current_user_id]);
            $event_id = $pdo->lastInsertId();
            $_SESSION['success_message'] = "เพิ่มกิจกรรมเรียบร้อยแล้ว";
        } else {
            $stmt = $pdo->prepare("UPDATE events SET event_name = ?, responsible_unit = ?, notes = ? WHERE id = ?");
            $stmt->execute([$event_name, $responsible_unit, $notes, $id]);
            $event_id = $id;
            $stmt_delete = $pdo->prepare("DELETE FROM event_dates WHERE event_id = ?");
            $stmt_delete->execute([$event_id]);
            $_SESSION['success_message'] = "แก้ไขข้อมูลกิจกรรมเรียบร้อยแล้ว";
        }

        if (!empty($dates)) {
            $stmt_date = $pdo->prepare("INSERT INTO event_dates (event_id, activity_date) VALUES (?, ?)");
            foreach ($dates as $date) { $stmt_date->execute([$event_id, $date]); }
        }
        
        $final_owner_ids = [];
        if (!empty($owner_ids_str)) {
            $final_owner_ids = explode(',', $owner_ids_str);
        } else {
             $final_owner_ids = [$current_user_id];
        }

        $stmt_delete_owners = $pdo->prepare("DELETE FROM event_owners WHERE event_id = ?");
        $stmt_delete_owners->execute([$event_id]);
        $stmt_insert_owner = $pdo->prepare("INSERT INTO event_owners (event_id, user_id) VALUES (?, ?)");
        foreach (array_unique($final_owner_ids) as $uid) {
            $stmt_insert_owner->execute([$event_id, $uid]);
        }
        
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }
}
header('Location: events.php');
exit;

