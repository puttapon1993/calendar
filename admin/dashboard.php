<?php
// File: dashboard.php
// Location: /admin/
$page_title = 'Dashboard';
require_once 'partials/header.php';
require_once '../config.php';

$current_user_id = $_SESSION['user_id'];

// Initialize variables
$event_count = 'N/A';
$holiday_count = 'N/A';
$report_count = 'N/A'; // Only for admin
$event_card_title = 'กิจกรรมทั้งหมด';
$holiday_card_title = 'วันสำคัญทั้งหมด';

try {
    if (is_admin()) {
        // Admin: Fetch system-wide statistics
        $event_card_title = 'กิจกรรมทั้งหมด';
        $holiday_card_title = 'วันสำคัญทั้งหมด';
        $event_count = $pdo->query("SELECT count(*) FROM events")->fetchColumn();
        $holiday_count = $pdo->query("SELECT count(*) FROM special_holidays")->fetchColumn();
        $report_count = $pdo->query("SELECT count(*) FROM problem_reports")->fetchColumn();
    } else {
        // Staff: Fetch statistics specific to this user
        $event_card_title = 'กิจกรรมของคุณ';
        $holiday_card_title = 'วันสำคัญของคุณ';

        // Count events owned by the staff
        $stmt_events = $pdo->prepare("SELECT COUNT(DISTINCT event_id) FROM event_owners WHERE user_id = ?");
        $stmt_events->execute([$current_user_id]);
        $event_count = $stmt_events->fetchColumn();

        // Count holidays owned by the staff
        $stmt_holidays = $pdo->prepare("SELECT COUNT(DISTINCT holiday_id) FROM holiday_owners WHERE user_id = ?");
        $stmt_holidays->execute([$current_user_id]);
        $holiday_count = $stmt_holidays->fetchColumn();
    }

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">ไม่สามารถดึงข้อมูลสรุปได้: ' . $e->getMessage() . '</div>';
}
?>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3><?php echo $event_card_title; ?></h3>
        <p class="stat-number"><?php echo $event_count; ?></p>
        <a href="events.php">จัดการกิจกรรม &rarr;</a>
    </div>
    <div class="dashboard-card">
        <h3><?php echo $holiday_card_title; ?></h3>
        <p class="stat-number"><?php echo $holiday_count; ?></p>
        <a href="holidays.php">จัดการวันสำคัญ &rarr;</a>
    </div>

    <?php if (is_admin()): ?>
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
    <?php endif; ?>
</div>

<?php
require_once 'partials/footer.php';
?>

