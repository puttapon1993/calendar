<?php
// File: staff_form.php
// Location: /admin/
$page_title = 'เพิ่ม/แก้ไข Staff';
require_once 'partials/header.php';
require_once '../config.php';

if (!is_admin()) {
    echo "<div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>";
    require_once 'partials/footer.php';
    exit;
}

$staff = [
    'id' => '', 'username' => '', 'real_name' => '', 'is_active' => 1,
    'permission_start_date' => '', 'permission_end_date' => ''
];
$is_edit_mode = false;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_edit_mode = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$_GET['id']]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$staff) {
            $_SESSION['error_message'] = "ไม่พบข้อมูล Staff ที่ระบุ";
            header('Location: manage_staff.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header('Location: manage_staff.php');
        exit;
    }
}
?>

<form action="save_staff.php" method="POST" class="data-form">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($staff['id']); ?>">

    <fieldset>
        <legend>ข้อมูลบัญชี</legend>
        <div class="form-group">
            <label for="real_name">ชื่อ-สกุล จริง</label>
            <input type="text" id="real_name" name="real_name" value="<?php echo htmlspecialchars($staff['real_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($staff['username']); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="text" id="password" name="password" placeholder="<?php echo $is_edit_mode ? 'เว้นว่างไว้หากไม่ต้องการเปลี่ยน' : ''; ?>">
        </div>
        <div class="form-group">
            <label>สถานะบัญชี</label>
            <div class="radio-group">
                <label><input type="radio" name="is_active" value="1" <?php echo ($staff['is_active'] == 1) ? 'checked' : ''; ?>> ใช้งาน</label>
                <label><input type="radio" name="is_active" value="0" <?php echo ($staff['is_active'] == 0) ? 'checked' : ''; ?>> ระงับ</label>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>สิทธิ์การสร้าง/แก้ไขข้อมูล</legend>
        <p>กำหนดช่วงวันที่ที่ Staff สามารถสร้างหรือแก้ไขกิจกรรมและวันหยุดได้ (เว้นว่างไว้เพื่อไม่จำกัด)</p>
        <div class="form-group">
            <label for="permission_start_date">วันที่เริ่มต้น</label>
            <input type="date" id="permission_start_date" name="permission_start_date" value="<?php echo htmlspecialchars($staff['permission_start_date']); ?>">
        </div>
        <div class="form-group">
            <label for="permission_end_date">วันที่สิ้นสุด</label>
            <input type="date" id="permission_end_date" name="permission_end_date" value="<?php echo htmlspecialchars($staff['permission_end_date']); ?>">
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="button">บันทึกข้อมูล</button>
        <a href="manage_staff.php" class="button button-secondary">ยกเลิก</a>
    </div>
</form>

<?php
require_once 'partials/footer.php';
?>
