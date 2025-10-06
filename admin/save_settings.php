<?php
// File: save_settings.php
// Location: /admin/
require_once 'session_check.php';

// Security Check: Only admins can save settings.
if (!is_admin()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ดำเนินการนี้";
    header("Location: dashboard.php");
    exit;
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Handle regular settings from the 'settings' array
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($_POST['settings'] as $key => $value) {
                // Trim whitespace from value, EXCEPT for the ticker separator
                $final_value = ($key === 'ticker_separator') ? $value : trim($value);
                $stmt->execute([$key, $final_value]);
            }
        }
        
        // Handle concatenated date settings for publication range
        if (isset($_POST['start_year_be'], $_POST['start_month'], $_POST['end_year_be'], $_POST['end_month'])) {
            $start_year_ad = (int)$_POST['start_year_be'] - 543;
            $start_month = str_pad((int)$_POST['start_month'], 2, '0', STR_PAD_LEFT);
            $publish_start_date = "{$start_year_ad}-{$start_month}";
            
            $end_year_ad = (int)$_POST['end_year_be'] - 543;
            $end_month = str_pad((int)$_POST['end_month'], 2, '0', STR_PAD_LEFT);
            $publish_end_date = "{$end_year_ad}-{$end_month}";

            $stmt_date = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt_date->execute(['publish_start_date', $publish_start_date]);
            $stmt_date->execute(['publish_end_date', $publish_end_date]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = "บันทึกการตั้งค่าเรียบร้อยแล้ว";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }
} else {
    // Handle cases where the request method is not POST
    $_SESSION['error_message'] = "Invalid request method.";
}

// Always redirect back to the settings page
header('Location: settings.php');
exit;
?>

