<?php
// File: manage_staff.php
// Location: /admin/
$page_title = 'จัดการ Staff';
require_once 'partials/header.php';
require_once '../config.php';

// Admin-only page
if (!is_admin()) {
    echo "<div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>";
    require_once 'partials/footer.php';
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'staff' ORDER BY real_name ASC");
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $staff_members = [];
    echo "<p class='error'>Could not fetch staff members: " . $e->getMessage() . "</p>";
}
?>

<div class="toolbar-grid">
    <a href="staff_form.php" class="button"> + เพิ่ม Staff ใหม่</a>
</div>

<?php 
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
                <th>ชื่อ-สกุล</th>
                <th>Username</th>
                <th>สิทธิ์การสร้าง (เริ่ม)</th>
                <th>สิทธิ์การสร้าง (สิ้นสุด)</th>
                <th>สถานะ</th>
                <th>เครื่องมือ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($staff_members)): ?>
                <tr><td colspan="6" style="text-align:center;">ยังไม่มีข้อมูล Staff...</td></tr>
            <?php else: ?>
                <?php foreach ($staff_members as $staff): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($staff['real_name']); ?></td>
                        <td><?php echo htmlspecialchars($staff['username']); ?></td>
                        <td><?php echo $staff['permission_start_date'] ? $staff['permission_start_date'] : 'ไม่จำกัด'; ?></td>
                        <td><?php echo $staff['permission_end_date'] ? $staff['permission_end_date'] : 'ไม่จำกัด'; ?></td>
                        <td>
                            <a href="toggle_staff_status.php?id=<?php echo $staff['id']; ?>" class="status-badge <?php echo $staff['is_active'] ? 'status-visible' : 'status-hidden'; ?>">
                                <?php echo $staff['is_active'] ? 'ใช้งาน' : 'ระงับ'; ?>
                            </a>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="staff_form.php?id=<?php echo $staff['id']; ?>" class="button button-edit">แก้ไข</a>
                                <a href="delete_staff.php?id=<?php echo $staff['id']; ?>" class="button button-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะลบ Staff คนนี้?');">ลบ</a>
                            </div>
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
