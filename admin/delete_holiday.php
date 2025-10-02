<?php
session_start();
if (!isset($_SESSION['admin_loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM special_holidays WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "ลบวันหยุดพิเศษเรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
}
header('Location: holidays.php');
exit;
