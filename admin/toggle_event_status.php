<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['admin_loggedin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $event_id = $_GET['id'];

    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT is_hidden FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $current_status = $stmt->fetchColumn();

        if ($current_status !== false) {
            // Toggle the status
            $new_status = $current_status ? 0 : 1;
            
            $update_stmt = $pdo->prepare("UPDATE events SET is_hidden = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $event_id]);
            $_SESSION['success_message'] = "เปลี่ยนสถานะกิจกรรมเรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "ไม่พบกิจกรรมที่ระบุ";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

header('Location: events.php');
exit;
