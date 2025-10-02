<?php
session_start();
if (!isset($_SESSION['admin_loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = trim($_POST['holiday_name']);
    $date = trim($_POST['holiday_date']);

    if (empty($name) || empty($date)) {
        $_SESSION['error_message'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header('Location: holiday_form.php' . ($id ? '?id='.$id : ''));
        exit;
    }

    try {
        if (empty($id)) {
            // Insert new holiday
            $stmt = $pdo->prepare("INSERT INTO special_holidays (holiday_name, holiday_date) VALUES (?, ?)");
            $stmt->execute([$name, $date]);
            $_SESSION['success_message'] = "เพิ่มวันหยุดพิเศษเรียบร้อยแล้ว";
        } else {
            // Update existing holiday
            $stmt = $pdo->prepare("UPDATE special_holidays SET holiday_name = ?, holiday_date = ? WHERE id = ?");
            $stmt->execute([$name, $date, $id]);
            $_SESSION['success_message'] = "แก้ไขข้อมูลวันหยุดเรียบร้อยแล้ว";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }
}

header('Location: holidays.php');
exit;
