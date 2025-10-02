<?php
$page_title = 'Dashboard';
require_once 'partials/header.php';
require_once '../config.php';

// Fetch some stats
try {
    $event_count = $pdo->query("SELECT count(*) FROM events")->fetchColumn();
    $holiday_count = $pdo->query("SELECT count(*) FROM special_holidays")->fetchColumn();
    $report_count = $pdo->query("SELECT count(*) FROM problem_reports")->fetchColumn();
} catch (PDOException $e) {
    $event_count = 'N/A';
    $holiday_count = 'N/A';
    $report_count = 'N/A';
    echo '<div class="alert alert-danger">ไม่สามารถดึงข้อมูลสรุปได้: ' . $e->getMessage() . '</div>';
}
?>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3>กิจกรรมทั้งหมด</h3>
        <p class="stat-number"><?php echo $event_count; ?></p>
        <a href="events.php">จัดการกิจกรรม &rarr;</a>
    </div>
    <div class="dashboard-card">
        <h3>วันหยุดพิเศษ</h3>
        <p class="stat-number"><?php echo $holiday_count; ?></p>
        <a href="holidays.php">จัดการวันหยุด &rarr;</a>
    </div>
    <div class="dashboard-card">
        <h3>ข้อความแจ้งปัญหา</h3>
        <p class="stat-number"><?php echo $report_count; ?></p>
        <a href="reports.php">ดูข้อความ &rarr;</a>
    </div>
     <div class="dashboard-card">
        <h3>ตั้งค่าเว็บไซต์</h3>
        <p class="stat-number" style="font-size: 2.5rem;">⚙️</p>
        <a href="settings.php">ไปที่การตั้งค่า &rarr;</a>
    </div>
</div>

<?php
require_once 'partials/footer.php';
?>

