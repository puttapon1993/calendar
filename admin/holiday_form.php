<?php
// File: holiday_form.php
// Location: /admin/
$page_title = 'เพิ่ม/แก้ไข วันสำคัญ';
require_once 'partials/header.php';
require_once '../config.php';

$current_user_id = $_SESSION['user_id'];
$holiday_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit_mode = $holiday_id > 0;

$holiday = ['id' => '', 'holiday_name' => '', 'holiday_date' => '', 'is_hidden' => 0, 'created_by_user_id' => $current_user_id];
$owner_ids = [];
$all_staff = [];
$user_permissions = ['start' => null, 'end' => null];

$thai_month_full_static = [1=>'มกราคม', 2=>'กุมภาพันธ์', 3=>'มีนาคม', 4=>'เมษายน', 5=>'พฤษภาคม', 6=>'มิถุนายน', 7=>'กรกฎาคม', 8=>'สิงหาคม', 9=>'กันยายน', 10=>'ตุลาคม', 11=>'พฤศจิกายน', 12=>'ธันวาคม'];
$today = new DateTime();
$today_thai_full = "วันที่ " . (int)$today->format('j') . " " . $thai_month_full_static[(int)$today->format('n')] . " พ.ศ. " . ($today->format('Y') + 543);
$today_example_input = $today->format('dm') . ($today->format('Y') + 543);

if (!is_admin()) {
    $stmt_perm = $pdo->prepare("SELECT permission_start_date, permission_end_date FROM users WHERE id = ?");
    $stmt_perm->execute([$current_user_id]);
    $permissions = $stmt_perm->fetch(PDO::FETCH_ASSOC);
    if ($permissions) {
        $user_permissions['start'] = $permissions['permission_start_date'];
        $user_permissions['end'] = $permissions['permission_end_date'];
    }
}

if ($is_edit_mode) {
    $stmt_owner_check = $pdo->prepare("SELECT COUNT(*) FROM holiday_owners WHERE holiday_id = ? AND user_id = ?");
    $stmt_owner_check->execute([$holiday_id, $current_user_id]);
    $is_owner = $stmt_owner_check->fetchColumn() > 0;
    if (!is_admin() && !$is_owner) {
        $_SESSION['error_message'] = "คุณไม่มีสิทธิ์แก้ไขวันสำคัญนี้";
        header('Location: holidays.php');
        exit;
    }
}

if (is_admin()) {
    $stmt_staff = $pdo->query("SELECT id, real_name FROM users WHERE role = 'staff' AND is_active = 1 ORDER BY real_name");
    $all_staff = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
}

function convert_to_be_text($date_str) {
    if (empty($date_str)) return '';
    try {
        $date = new DateTime($date_str);
        return $date->format('d') . $date->format('m') . ($date->format('Y') + 543);
    } catch (Exception $e) { return ''; }
}

if ($is_edit_mode) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM special_holidays WHERE id = ?");
        $stmt->execute([$holiday_id]);
        $holiday = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$holiday) {
            $_SESSION['error_message'] = "ไม่พบวันสำคัญที่ระบุ";
            header('Location: holidays.php');
            exit;
        }

        $stmt_owners = $pdo->prepare("SELECT user_id FROM holiday_owners WHERE holiday_id = ?");
        $stmt_owners->execute([$holiday_id]);
        $owner_ids = $stmt_owners->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header('Location: holidays.php');
        exit;
    }
}

if (!$is_edit_mode) {
    $owner_ids[] = $current_user_id;
}
?>

<form action="save_holiday.php" method="POST" class="data-form" id="holiday-form">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($holiday['id']); ?>">
    <input type="hidden" name="owner_ids_str" id="owner_ids_input" value="<?php echo implode(',', $owner_ids); ?>">

    <div class="form-group">
        <label for="holiday_name">ชื่อวันสำคัญ</label>
        <input type="text" id="holiday_name" name="holiday_name" value="<?php echo htmlspecialchars($holiday['holiday_name']); ?>" required>
    </div>

    <div class="date-input-container-with-helper">
        <div id="date-inputs-container">
            <div class="form-group">
                <label for="holiday_date_text">วันที่</label>
                <div class="date-input-wrapper">
                    <input type="text" id="holiday_date_text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text(htmlspecialchars($holiday['holiday_date'])); ?>">
                    <input type="hidden" name="holiday_date" value="<?php echo htmlspecialchars($holiday['holiday_date']); ?>">
                    <span class="date-display"></span>
                </div>
            </div>
        </div>
        <div class="date-helper">
            <strong>คำแนะนำ</strong>
            <p>กรอกวันที่เป็น <strong>ตัวเลขติดกัน 8 หลัก</strong><br>(ววดดปปปป) ไม่ต้องมีเครื่องหมายใดๆ</p>
            <p>ตัวอย่าง: "<?php echo $today_thai_full; ?>"<br>ให้กรอกเป็น <strong><?php echo $today_example_input; ?></strong></p>
        </div>
    </div>

     <fieldset>
        <legend>สถานะ</legend>
        <div class="form-group">
            <div class="radio-group">
                <label><input type="radio" name="is_hidden" value="0" <?php echo ($holiday['is_hidden'] == 0) ? 'checked' : ''; ?>> แสดง</label>
                <label><input type="radio" name="is_hidden" value="1" <?php echo ($holiday['is_hidden'] == 1) ? 'checked' : ''; ?>> ซ่อน</label>
            </div>
        </div>
    </fieldset>

    <?php if (is_admin() && !empty($all_staff)): ?>
    <fieldset><legend>เจ้าของร่วม (Co-owners)</legend><p>คุณสามารถกำหนดให้ Staff คนอื่นมีสิทธิ์แก้ไขวันสำคัญนี้ร่วมกันได้</p><button type="button" id="open-co-owner-modal-btn" class="button button-secondary"><i class="fas fa-users"></i> กำหนดเจ้าของร่วม</button></fieldset>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="button">บันทึกข้อมูล</button>
        <a href="holidays.php" class="button button-secondary">ยกเลิก</a>
    </div>
</form>

<?php if (is_admin() && !empty($all_staff)): ?>
<div id="co-owner-modal" class="modal-overlay hidden" style="z-index: 1060;">
    <div class="modal-content"><button type="button" class="modal-close-btn">&times;</button><h3>เลือกเจ้าของร่วม</h3>
        <div class="co-owner-list">
            <?php foreach ($all_staff as $staff): ?>
                <label><input type="checkbox" name="modal_owner_ids" value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['real_name']); ?></label>
            <?php endforeach; ?>
        </div>
        <div class="form-actions" style="text-align: right;"><button type="button" id="save-co-owners-btn" class="button">บันทึก</button></div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const holidayForm = document.getElementById('holiday-form');
    const isUserAdmin = <?php echo json_encode(is_admin()); ?>;
    const userPermissions = <?php echo json_encode($user_permissions); ?>;
    const thaiDayShort = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
    const thaiMonthFull = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

    function convertAndValidateBE(textInput) {
        const wrapper = textInput.closest('.date-input-wrapper');
        const hiddenInput = wrapper.querySelector('input[type="hidden"]');
        const displaySpan = wrapper.querySelector('.date-display');
        let value = textInput.value.replace(/\D/g, '');
        
        textInput.classList.remove('is-invalid', 'is-valid');
        if (value.length < 8) { hiddenInput.value = ''; displaySpan.textContent = ''; return; }
        if (value.length > 8) { value = value.substring(0, 8); textInput.value = value; }

        const day = parseInt(value.substring(0, 2), 10), month = parseInt(value.substring(2, 4), 10), yearBE = parseInt(value.substring(4, 8), 10);
        if (isNaN(day) || isNaN(month) || isNaN(yearBE) || month < 1 || month > 12 || day < 1 || day > 31 || yearBE < 2500) { hiddenInput.value = ''; textInput.classList.add('is-invalid'); displaySpan.textContent = ''; return; }
        
        const yearCE = yearBE - 543, formattedDate = `${yearCE}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const testDate = new Date(formattedDate + 'T00:00:00');
        
        if (testDate.getFullYear() === yearCE && (testDate.getMonth() + 1) === month && testDate.getDate() === day) {
            hiddenInput.value = formattedDate;
            textInput.classList.add('is-valid');
            const dayName = thaiDayShort[testDate.getDay()], dayNum = testDate.getDate(), monthName = thaiMonthFull[testDate.getMonth()], yearNumBE = testDate.getFullYear() + 543;
            displaySpan.textContent = `${dayName}. ${dayNum} ${monthName} ${yearNumBE}`;
        } else {
            hiddenInput.value = ''; textInput.classList.add('is-invalid'); displaySpan.textContent = '';
        }
    }

    const dateInput = document.getElementById('holiday_date_text');
    if (dateInput) {
        dateInput.addEventListener('input', function() { convertAndValidateBE(this); });
        if (dateInput.value) { convertAndValidateBE(dateInput); }
    }

    holidayForm.addEventListener('submit', function(e) {
        if (!isUserAdmin && (userPermissions.start || userPermissions.end)) {
            const date = document.querySelector('input[name="holiday_date"]').value;
            if (date && ((userPermissions.start && date < userPermissions.start) || (userPermissions.end && date > userPermissions.end))) {
                e.preventDefault();
                showAlert(`ไม่สามารถบันทึกได้ เนื่องจากวันที่ที่ระบุ (${date}) อยู่นอกช่วงเวลาที่คุณได้รับอนุญาต`);
            }
        }
    });

    const coOwnerModal = document.getElementById('co-owner-modal');
    if (coOwnerModal) {
        const openBtn = document.getElementById('open-co-owner-modal-btn'), closeBtn = coOwnerModal.querySelector('.modal-close-btn'), saveBtn = document.getElementById('save-co-owners-btn');
        const ownerIdsInput = document.getElementById('owner_ids_input'), checkboxes = coOwnerModal.querySelectorAll('input[name="modal_owner_ids"]');
        openBtn.addEventListener('click', () => { const currentIds = ownerIdsInput.value.split(',').filter(id => id); checkboxes.forEach(cb => { cb.checked = currentIds.includes(cb.value); }); coOwnerModal.classList.remove('hidden'); });
        closeBtn.addEventListener('click', () => coOwnerModal.classList.add('hidden'));
        saveBtn.addEventListener('click', () => {
            const selectedIds = [];
            checkboxes.forEach(cb => { if (cb.checked) selectedIds.push(cb.value); });
            const creatorId = "<?php echo $holiday['created_by_user_id'] ?: $current_user_id; ?>";
            if (!selectedIds.includes(creatorId)) selectedIds.push(creatorId);
            ownerIdsInput.value = [...new Set(selectedIds)].join(',');
            coOwnerModal.classList.add('hidden');
        });
    }
});
</script>

<?php require_once 'partials/footer.php'; ?>

