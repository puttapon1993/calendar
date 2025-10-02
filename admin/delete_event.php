<?php
// File: delete_event.php
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

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $event_id = $_GET['id'];

    // --- Permission Check ---
    if (!$is_admin) {
        $stmt_owner_check = $pdo->prepare("SELECT COUNT(*) FROM event_owners WHERE event_id = ? AND user_id = ?");
        $stmt_owner_check->execute([$event_id, $current_user_id]);
        if ($stmt_owner_check->fetchColumn() == 0) {
            $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ลบกิจกรรมนี้";
            header('Location: events.php');
            exit;
        }
    }

    try {
        // The CASCADE constraint on `event_dates` and `event_owners` handles deletions there.
        $stmt_event = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt_event->execute([$event_id]);
        
        $_SESSION['success_message'] = "ลบกิจกรรมเรียบร้อยแล้ว";

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
}

header('Location: events.php');
exit;

