<?php
// File: toggle_holiday_status.php
// Location: /admin/
session_start();
require_once 'session_check.php';
require_once '../config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $holiday_id = $_GET['id'];
    $current_user_id = $_SESSION['user_id'];

    try {
        if (!is_admin()) {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM holiday_owners WHERE holiday_id = ? AND user_id = ?");
            $stmt_check->execute([$holiday_id, $current_user_id]);
            if ($stmt_check->fetchColumn() == 0) {
                $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เปลี่ยนสถานะวันสำคัญนี้";
                header('Location: holidays.php');
                exit;
            }
        }

        $stmt = $pdo->prepare("SELECT is_hidden FROM special_holidays WHERE id = ?");
        $stmt->execute([$holiday_id]);
        $current_status = $stmt->fetchColumn();

        if ($current_status !== false) {
            $new_status = $current_status ? 0 : 1;
            $update_stmt = $pdo->prepare("UPDATE special_holidays SET is_hidden = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $holiday_id]);
            $_SESSION['success_message'] = "เปลี่ยนสถานะวันสำคัญเรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "ไม่พบวันสำคัญที่ระบุ";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

header('Location: holidays.php');
exit;

