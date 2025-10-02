<?php
// File: delete_staff.php
// Location: /admin/
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ดำเนินการ";
    header('Location: dashboard.php');
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    try {
        // The foreign key constraint `ON DELETE SET NULL` will handle `created_by_user_id`
        // in events and special_holidays tables.
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "ลบข้อมูล Staff เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
}
header('Location: manage_staff.php');
exit;
?>

