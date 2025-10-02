<?php
// File: save_staff.php
// Location: /admin/
session_start();
require_once '../config.php';

// We need to check the role from the session
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ดำเนินการ";
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $real_name = trim($_POST['real_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Don't trim password
    $is_active = $_POST['is_active'];
    $start_date = !empty($_POST['permission_start_date']) ? $_POST['permission_start_date'] : null;
    $end_date = !empty($_POST['permission_end_date']) ? $_POST['permission_end_date'] : null;

    // Basic validation
    if (empty($real_name) || empty($username)) {
        $_SESSION['error_message'] = "กรุณากรอกชื่อ-สกุล และ Username";
        header('Location: staff_form.php' . ($id ? '?id=' . $id : ''));
        exit;
    }

    try {
        if (empty($id)) {
            // Insert new staff
            if (empty($password)) {
                $_SESSION['error_message'] = "กรุณากำหนดรหัสผ่านสำหรับ Staff ใหม่";
                header('Location: staff_form.php');
                exit;
            }
            $stmt = $pdo->prepare("INSERT INTO users (username, password, real_name, role, is_active, permission_start_date, permission_end_date) VALUES (?, ?, ?, 'staff', ?, ?, ?)");
            $stmt->execute([$username, $password, $real_name, $is_active, $start_date, $end_date]);
            $_SESSION['success_message'] = "เพิ่ม Staff ใหม่เรียบร้อยแล้ว";
        } else {
            // Update existing staff
            if (!empty($password)) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, real_name = ?, is_active = ?, permission_start_date = ?, permission_end_date = ? WHERE id = ? AND role = 'staff'");
                $stmt->execute([$username, $password, $real_name, $is_active, $start_date, $end_date, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, real_name = ?, is_active = ?, permission_start_date = ?, permission_end_date = ? WHERE id = ? AND role = 'staff'");
                $stmt->execute([$username, $real_name, $is_active, $start_date, $end_date, $id]);
            }
            $_SESSION['success_message'] = "อัปเดตข้อมูล Staff เรียบร้อยแล้ว";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate username)
            $_SESSION['error_message'] = "Username นี้มีผู้ใช้งานแล้ว กรุณาใช้ชื่ออื่น";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        }
        header('Location: staff_form.php' . ($id ? '?id=' . $id : ''));
        exit;
    }
}

header('Location: manage_staff.php');
exit;
?>

