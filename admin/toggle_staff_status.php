<?php
// File: toggle_staff_status.php
// Location: /admin/
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ดำเนินการ";
    header('Location: dashboard.php');
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $staff_id = $_GET['id'];
    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$staff_id]);
        $current_status = $stmt->fetchColumn();

        if ($current_status !== false) {
            $new_status = $current_status ? 0 : 1;
            $update_stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $staff_id]);
            $_SESSION['success_message'] = "เปลี่ยนสถานะ Staff เรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "ไม่พบ Staff ที่ระบุ";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

header('Location: manage_staff.php');
exit;
?>

