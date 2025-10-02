<?php
// File: delete_holiday.php
// Location: /admin/
session_start();
require_once 'session_check.php';
require_once '../config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $current_user_id = $_SESSION['user_id'];

    try {
        if (!is_admin()) {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM holiday_owners WHERE holiday_id = ? AND user_id = ?");
            $stmt_check->execute([$id, $current_user_id]);
            if ($stmt_check->fetchColumn() == 0) {
                $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ลบวันสำคัญนี้";
                header('Location: holidays.php');
                exit;
            }
        }
        
        // No need to delete owners separately due to CASCADE DELETE constraint
        $stmt = $pdo->prepare("DELETE FROM special_holidays WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "ลบวันสำคัญเรียบร้อยแล้ว";

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
}
header('Location: holidays.php');
exit;

