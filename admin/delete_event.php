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

    $pdo->beginTransaction();
    try {
        // Delete associated dates first
        $stmt_dates = $pdo->prepare("DELETE FROM event_dates WHERE event_id = ?");
        $stmt_dates->execute([$event_id]);

        // Delete the event itself
        $stmt_event = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt_event->execute([$event_id]);
        
        $pdo->commit();
        $_SESSION['success_message'] = "ลบกิจกรรมเรียบร้อยแล้ว";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
}

header('Location: events.php');
exit;
