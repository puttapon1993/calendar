<?php
// File: reports.php
// Location: /admin/
$page_title = 'ข้อความแจ้งปัญหา';
require_once 'partials/header.php';

// Security Check: Only admins can access this page
if (!is_admin()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header('Location: dashboard.php');
    exit;
}

require_once '../config.php';

try {
    $stmt = $pdo->query("SELECT * FROM problem_reports ORDER BY submitted_at DESC");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reports = [];
    echo "<p class='error'>Could not fetch reports: " . $e->getMessage() . "</p>";
}
?>

<?php 
// Display success or error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ข้อความ</th>
                <th style="width: 200px;">วันที่ส่ง</th>
                <th style="width: 100px;">เครื่องมือ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="3" style="text-align:center;">ยังไม่มีข้อความแจ้งปัญหา...</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?php echo nl2br(htmlspecialchars($report['message'])); ?></td>
                        <td><?php 
                            $date = new DateTime($report['submitted_at']);
                            echo $date->format('j M Y, H:i');
                         ?></td>
                        <td class="action-links">
                            <a href="delete_report.php?id=<?php echo $report['id']; ?>" class="button button-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะลบข้อความนี้?');">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require_once 'partials/footer.php';
?>
