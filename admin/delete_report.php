<?php
// File: delete_report.php
// Location: /admin/

require_once 'session_check.php';

// Security Check: Only admins can delete reports.
if (!is_admin()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ดำเนินการนี้";
    header("Location: dashboard.php");
    exit;
}

require_once '../config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM problem_reports WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "ลบข้อความแจ้งปัญหาเรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "ไม่ได้ระบุ ID ของข้อความที่จะลบ";
}

// Always redirect back to the reports page after the operation.
header('Location: reports.php');
exit;
?>
