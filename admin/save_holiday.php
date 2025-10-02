<?php
// File: save_holiday.php
// Location: /admin/
session_start();
require_once 'session_check.php';
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = trim($_POST['holiday_name']);
    $date = trim($_POST['holiday_date']);
    $is_hidden = isset($_POST['is_hidden']) ? (int)$_POST['is_hidden'] : 0;
    $owner_ids = isset($_POST['owner_ids_str']) ? array_unique(explode(',', $_POST['owner_ids_str'])) : [];
    $current_user_id = $_SESSION['user_id'];
    
    if (empty($name) || empty($date)) {
        $_SESSION['error_message'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header('Location: holiday_form.php' . ($id ? '?id='.$id : ''));
        exit;
    }

    $pdo->beginTransaction();
    try {
        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO special_holidays (holiday_name, holiday_date, is_hidden, created_by_user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $date, $is_hidden, $current_user_id]);
            $holiday_id = $pdo->lastInsertId();
            $_SESSION['success_message'] = "เพิ่มวันสำคัญเรียบร้อยแล้ว";
        } else {
            $stmt = $pdo->prepare("UPDATE special_holidays SET holiday_name = ?, holiday_date = ?, is_hidden = ? WHERE id = ?");
            $stmt->execute([$name, $date, $is_hidden, $id]);
            $holiday_id = $id;
            $_SESSION['success_message'] = "แก้ไขข้อมูลวันสำคัญเรียบร้อยแล้ว";
        }
        
        $stmt_delete_owners = $pdo->prepare("DELETE FROM holiday_owners WHERE holiday_id = ?");
        $stmt_delete_owners->execute([$holiday_id]);
        
        if (!empty($owner_ids)) {
            $stmt_insert_owner = $pdo->prepare("INSERT INTO holiday_owners (holiday_id, user_id) VALUES (?, ?)");
            foreach ($owner_ids as $owner_id) {
                if (!empty($owner_id)) {
                    $stmt_insert_owner->execute([$holiday_id, (int)$owner_id]);
                }
            }
        }
        
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }
}

header('Location: holidays.php');
exit;

