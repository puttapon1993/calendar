<?php
// File: toggle_event_status.php
// Location: /admin/
session_start();
// Redirect if not logged in
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
            $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เปลี่ยนสถานะกิจกรรมนี้";
            header('Location: events.php');
            exit;
        }
    }

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

