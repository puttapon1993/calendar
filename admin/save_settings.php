<?php
session_start();
if (!isset($_SESSION['admin_loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Handle regular settings
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($_POST['settings'] as $key => $value) {
                $stmt->execute([$key, trim($value)]);
            }
        }
        
        // Handle concatenated date settings
        $start_year_ad = (int)$_POST['start_year_be'] - 543;
        $start_month = str_pad((int)$_POST['start_month'], 2, '0', STR_PAD_LEFT);
        $publish_start_date = "{$start_year_ad}-{$start_month}";
        
        $end_year_ad = (int)$_POST['end_year_be'] - 543;
        $end_month = str_pad((int)$_POST['end_month'], 2, '0', STR_PAD_LEFT);
        $publish_end_date = "{$end_year_ad}-{$end_month}";

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['publish_start_date', $publish_start_date]);
        $stmt->execute(['publish_end_date', $publish_end_date]);

        $pdo->commit();
        $_SESSION['success_message'] = "บันทึกการตั้งค่าเรียบร้อยแล้ว";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }
}

header('Location: settings.php');
exit;
