<?php
// File: event_form.php
// Location: /admin/
$page_title = 'เพิ่ม/แก้ไข กิจกรรม';
require_once 'partials/header.php';
require_once '../config.php';

$current_user_id = $_SESSION['user_id'];
$event_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit_mode = $event_id > 0;

$event = ['id' => '', 'event_name' => '', 'responsible_unit' => '', 'notes' => '', 'created_by_user_id' => $current_user_id];
$event_dates = [];
$date_type = 'single';
$owner_ids = [];
$all_staff = [];
$user_permissions = ['start' => null, 'end' => null];

// --- Prepare dynamic date example for helper text ---
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
    $stmt_owner_check = $pdo->prepare("SELECT COUNT(*) FROM event_owners WHERE event_id = ? AND user_id = ?");
    $stmt_owner_check->execute([$event_id, $current_user_id]);
    $is_owner = $stmt_owner_check->fetchColumn() > 0;
    if (!is_admin() && !$is_owner) {
        $_SESSION['error_message'] = "คุณไม่มีสิทธิ์แก้ไขกิจกรรมนี้";
        header('Location: events.php');
        exit;
    }
}

if (is_admin()) {
    $stmt_staff = $pdo->query("SELECT id, real_name FROM users WHERE role = 'staff' AND is_active = 1 ORDER BY real_name");
    $all_staff = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
}

$responsible_unit_default = '';
if (!$is_edit_mode && !is_admin()) { 
    $responsible_unit_default = $_SESSION['user_real_name'];
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
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            $_SESSION['error_message'] = "ไม่พบกิจกรรมที่ระบุ";
            header('Location: events.php');
            exit;
        }

        $stmt_dates = $pdo->prepare("SELECT activity_date FROM event_dates WHERE event_id = ? ORDER BY activity_date ASC");
        $stmt_dates->execute([$event_id]);
        $event_dates_raw = $stmt_dates->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt_owners = $pdo->prepare("SELECT user_id FROM event_owners WHERE event_id = ?");
        $stmt_owners->execute([$event_id]);
        $owner_ids = $stmt_owners->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($event_dates_raw) > 1) {
            $is_continuous = true;
            for ($i = 0; $i < count($event_dates_raw) - 1; $i++) {
                if ((new DateTime($event_dates_raw[$i+1]))->diff(new DateTime($event_dates_raw[$i]))->days != 1) {
                    $is_continuous = false;
                    break;
                }
            }
            $date_type = $is_continuous ? 'continuous' : 'non-continuous';
        } elseif (count($event_dates_raw) == 1) {
            $date_type = 'single';
        }
        $event_dates = $event_dates_raw;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header('Location: events.php');
        exit;
    }
}

if (!$is_edit_mode) {
    $owner_ids[] = $current_user_id;
}
?>

<form action="save_event.php" method="POST" class="data-form" id="event-form">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id']); ?>">
    <input type="hidden" name="confirm_conflict" id="confirm-conflict-input" value="0">
    <input type="hidden" name="owner_ids_str" id="owner_ids_input" value="<?php echo implode(',', $owner_ids); ?>">

    <div class="form-group">
        <label for="event_name">ชื่อกิจกรรม</label>
        <input type="text" id="event_name" name="event_name" value="<?php echo htmlspecialchars($event['event_name']); ?>" required>
    </div>

    <div class="form-group">
        <label for="responsible_unit">หน่วยงานที่รับผิดชอบ (ไม่บังคับ)</label>
        <input type="text" id="responsible_unit" name="responsible_unit" value="<?php echo htmlspecialchars($event['responsible_unit'] ?: $responsible_unit_default); ?>">
    </div>

    <div class="form-group">
        <label for="notes">หมายเหตุ (ไม่บังคับ)</label>
        <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($event['notes']); ?></textarea>
    </div>

    <fieldset>
        <legend>กำหนดวันที่จัดกิจกรรม</legend>
        <div class="form-group">
            <div class="radio-group">
                <label><input type="radio" name="date_type" value="single" <?php echo $date_type === 'single' ? 'checked' : ''; ?>> จัดวันเดียว</label>
                <label><input type="radio" name="date_type" value="continuous" <?php echo $date_type === 'continuous' ? 'checked' : ''; ?>> จัดหลายวัน (ต่อเนื่อง)</label>
                <label><input type="radio" name="date_type" value="non-continuous" <?php echo $date_type === 'non-continuous' ? 'checked' : ''; ?>> จัดหลายวัน (ไม่ต่อเนื่อง)</label>
            </div>
        </div>
        <div class="date-input-container-with-helper">
            <div id="date-inputs-container">
                <div id="single-date-input" class="date-input-group <?php echo $date_type !== 'single' ? 'hidden' : ''; ?>">
                    <div class="form-group"><label>เลือกวันที่</label><div class="date-input-wrapper"><input type="text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text(($date_type === 'single' && !empty($event_dates)) ? $event_dates[0] : ''); ?>"><input type="hidden" name="single_date" value="<?php echo ($date_type === 'single' && !empty($event_dates)) ? $event_dates[0] : ''; ?>"><span class="date-display"></span></div></div>
                </div>
                <div id="continuous-date-input" class="date-input-group <?php echo $date_type !== 'continuous' ? 'hidden' : ''; ?>">
                     <div class="form-group"><label>วันเริ่มต้น</label><div class="date-input-wrapper"><input type="text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text(($date_type === 'continuous' && !empty($event_dates)) ? $event_dates[0] : ''); ?>"><input type="hidden" name="start_date" value="<?php echo ($date_type === 'continuous' && !empty($event_dates)) ? $event_dates[0] : ''; ?>"><span class="date-display"></span></div></div>
                     <div class="form-group"><label>วันสิ้นสุด</label><div class="date-input-wrapper"><input type="text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text(($date_type === 'continuous' && !empty($event_dates)) ? end($event_dates) : ''); ?>"><input type="hidden" name="end_date" value="<?php echo ($date_type === 'continuous' && !empty($event_dates)) ? end($event_dates) : ''; ?>"><span class="date-display"></span></div></div>
                </div>
                <div id="non-continuous-date-input" class="date-input-group <?php echo $date_type !== 'non-continuous' ? 'hidden' : ''; ?>">
                    <div id="non-continuous-dates-list">
                        <?php if ($date_type === 'non-continuous' && !empty($event_dates)): foreach($event_dates as $date): ?>
                            <div class="date-entry"><div class="date-input-wrapper"><input type="text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text($date); ?>"><input type="hidden" name="non_continuous_dates[]" value="<?php echo $date; ?>"><span class="date-display"></span></div><button type="button" class="button button-danger remove-date-btn">ลบ</button></div>
                        <?php endforeach; else: ?>
                            <div class="date-entry"><div class="date-input-wrapper"><input type="text" class="date-input-be" placeholder="ววดดปปปป" value=""><input type="hidden" name="non_continuous_dates[]" value=""><span class="date-display"></span></div><button type="button" class="button button-danger remove-date-btn" style="display:none;">ลบ</button></div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-date-btn" class="button button-secondary"> + เพิ่มวัน</button>
                </div>
            </div>
            <div class="date-helper">
                <strong>คำแนะนำ</strong>
                <p>กรอกวันที่เป็น <strong>ตัวเลขติดกัน 8 หลัก</strong><br>(ววดดปปปป) ไม่ต้องมีเครื่องหมายใดๆ</p>
                <p>ตัวอย่าง: "<?php echo $today_thai_full; ?>"<br>ให้กรอกเป็น <strong><?php echo $today_example_input; ?></strong></p>
            </div>
        </div>
    </fieldset>

    <?php if (is_admin() && !empty($all_staff)): ?>
    <fieldset><legend>เจ้าของร่วม (Co-owners)</legend><p>คุณสามารถกำหนดให้ Staff คนอื่นมีสิทธิ์แก้ไขกิจกรรมนี้ร่วมกันได้</p><button type="button" id="open-co-owner-modal-btn" class="button button-secondary"><i class="fas fa-users"></i> กำหนดเจ้าของร่วม</button></fieldset>
    <?php endif; ?>

    <div class="form-actions"><button type="submit" class="button">บันทึกข้อมูล</button><a href="events.php" class="button button-secondary">ยกเลิก</a></div>
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
    const eventForm = document.getElementById('event-form');
    const confirmationModal = document.getElementById('confirmation-modal');
    const confirmSaveBtn = document.getElementById('confirm-save-btn');
    const cancelSaveBtn = document.getElementById('cancel-save-btn');
    const confirmConflictInput = document.getElementById('confirm-conflict-input');
    const modalBody = document.getElementById('confirmation-modal-body');
    let hasConflict = false;
    let conflictDetails = [];

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
        
        if (value.length < 8) { hiddenInput.value = ''; displaySpan.textContent = ''; checkForConflicts(); return; }
        if (value.length > 8) { value = value.substring(0, 8); textInput.value = value; }
        
        const day = parseInt(value.substring(0, 2), 10), month = parseInt(value.substring(2, 4), 10), yearBE = parseInt(value.substring(4, 8), 10);
        if (isNaN(day) || isNaN(month) || isNaN(yearBE) || month < 1 || month > 12 || day < 1 || day > 31 || yearBE < 2500) { 
            hiddenInput.value = ''; textInput.classList.add('is-invalid'); displaySpan.textContent = ''; checkForConflicts(); return; 
        }
        
        const yearCE = yearBE - 543, formattedDate = `${yearCE}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const testDate = new Date(yearCE, month - 1, day);
        
        if (testDate.getFullYear() === yearCE && testDate.getMonth() === (month - 1) && testDate.getDate() === day) { 
            hiddenInput.value = formattedDate; 
            textInput.classList.add('is-valid'); 
            const dayName = thaiDayShort[testDate.getDay()], dayNum = testDate.getDate(), monthName = thaiMonthFull[testDate.getMonth()], yearNumBE = testDate.getFullYear() + 543;
            displaySpan.textContent = `${dayName}. ${dayNum} ${monthName} ${yearNumBE}`;
        } else { 
            hiddenInput.value = ''; textInput.classList.add('is-invalid'); displaySpan.textContent = ''; 
        }
        checkForConflicts();
    }

    function initializeDateInput(element) {
        element.addEventListener('input', function() { convertAndValidateBE(this); });
        if (element.value) { convertAndValidateBE(element); }
    }

    const container = document.getElementById('date-inputs-container');
    container.querySelectorAll('.date-input-be').forEach(initializeDateInput);

    /**
     * Gathers all valid YYYY-MM-DD date strings from the form based on the selected date type.
     * This function now correctly handles date ranges to avoid timezone issues.
     * @returns {string[]} An array of date strings.
     */
    function getDatesFromForm() {
        let dates = [];
        const dateType = document.querySelector('input[name="date_type"]:checked').value;

        if (dateType === 'single') {
            const singleDate = document.querySelector('#single-date-input input[type="hidden"]').value;
            if (singleDate) dates.push(singleDate);
        } else if (dateType === 'continuous') {
            const startStr = document.querySelector('#continuous-date-input input[name="start_date"]').value;
            const endStr = document.querySelector('#continuous-date-input input[name="end_date"]').value;

            if (startStr && endStr && startStr <= endStr) {
                let currentDate = new Date(startStr);
                let endDate = new Date(endStr);
                
                // Use UTC methods to iterate through dates and avoid timezone shifts
                while (currentDate <= endDate) {
                    const year = currentDate.getUTCFullYear();
                    const month = String(currentDate.getUTCMonth() + 1).padStart(2, '0');
                    const day = String(currentDate.getUTCDate()).padStart(2, '0');
                    dates.push(`${year}-${month}-${day}`);
                    currentDate.setUTCDate(currentDate.getUTCDate() + 1);
                }
            }
        } else if (dateType === 'non-continuous') {
            document.querySelectorAll('#non-continuous-date-input input[type="hidden"]').forEach(input => {
                if (input.value) dates.push(input.value);
            });
        }
        return dates;
    }
    
    let conflictCheckTimeout;
    async function checkForConflicts() {
        clearTimeout(conflictCheckTimeout);
        conflictCheckTimeout = setTimeout(async () => {
            const dates = getDatesFromForm();
            if (dates.length === 0) { hasConflict = false; return; }
            
            const formData = new FormData();
            dates.forEach(date => formData.append('dates[]', date));
            if ("<?php echo $event_id; ?>" > 0) { formData.append('exclude_event_id', "<?php echo $event_id; ?>"); }

            try {
                const response = await fetch('../api/check_date_conflict.php', { method: 'POST', body: formData });
                const result = await response.json();
                hasConflict = result.conflict; conflictDetails = result.details || [];
            } catch (error) { console.error("Conflict check failed:", error); hasConflict = false; }
        }, 500);
    }

    eventForm.addEventListener('submit', function(e) {
        if (!isUserAdmin && (userPermissions.start || userPermissions.end)) {
            const datesToCheck = getDatesFromForm();
            for (const date of datesToCheck) {
                if ((userPermissions.start && date < userPermissions.start) || (userPermissions.end && date > userPermissions.end)) {
                    e.preventDefault();
                    showAlert(`ไม่สามารถบันทึกได้ เนื่องจากวันที่ที่ระบุ (${date}) อยู่นอกช่วงเวลาที่คุณได้รับอนุญาต`);
                    return;
                }
            }
        }
        if (hasConflict && confirmConflictInput.value !== '1') {
            e.preventDefault();
            modalBody.innerHTML = '<p><strong>คำเตือน:</strong></p><ul>' + conflictDetails.map(d => `<li>${d}</li>`).join('') + '</ul><p>คุณยังคงยืนยันที่จะเพิ่มกิจกรรมของคุณในวันนี้ใช่ไหม?</p>';
            confirmationModal.classList.remove('hidden');
        }
    });

    confirmSaveBtn.addEventListener('click', () => { confirmConflictInput.value = '1'; confirmationModal.classList.add('hidden'); eventForm.submit(); });
    cancelSaveBtn.addEventListener('click', () => { confirmationModal.classList.add('hidden'); confirmConflictInput.value = '0'; });
    
    const dateTypeRadios = document.querySelectorAll('input[name="date_type"]');
    const singleDateInput = document.getElementById('single-date-input'), continuousDateInput = document.getElementById('continuous-date-input'), nonContinuousDateInput = document.getElementById('non-continuous-date-input');
    function updateDateInputVisibility() {
        const selectedValue = document.querySelector('input[name="date_type"]:checked').value;
        singleDateInput.classList.add('hidden'); continuousDateInput.classList.add('hidden'); nonContinuousDateInput.classList.add('hidden');
        if (selectedValue === 'single') singleDateInput.classList.remove('hidden');
        else if (selectedValue === 'continuous') continuousDateInput.classList.remove('hidden');
        else if (selectedValue === 'non-continuous') nonContinuousDateInput.classList.remove('hidden');
        checkForConflicts();
    }
    dateTypeRadios.forEach(radio => radio.addEventListener('change', updateDateInputVisibility));
    updateDateInputVisibility();

    const nonContinuousList = document.getElementById('non-continuous-dates-list'), addDateBtn = document.getElementById('add-date-btn');
    if (addDateBtn) { addDateBtn.addEventListener('click', () => { const newEntry = document.createElement('div'); newEntry.className = 'date-entry'; newEntry.innerHTML = `<div class="date-input-wrapper"><input type="text" class="date-input-be" placeholder="ววดดปปปป" value=""><input type="hidden" name="non_continuous_dates[]" value=""><span class="date-display"></span></div><button type="button" class="button button-danger remove-date-btn">ลบ</button>`; initializeDateInput(newEntry.querySelector('.date-input-be')); nonContinuousList.appendChild(newEntry); }); }
    if (nonContinuousList) { nonContinuousList.addEventListener('click', e => { if (e.target.classList.contains('remove-date-btn')) { const parentEntry = e.target.parentElement; if (nonContinuousList.querySelectorAll('.date-entry').length > 1) { parentEntry.remove(); } else { parentEntry.querySelector('.date-input-be').value = ''; parentEntry.querySelector('input[type="hidden"]').value = ''; parentEntry.querySelector('.date-display').textContent = ''; } checkForConflicts(); } }); }

    const coOwnerModal = document.getElementById('co-owner-modal');
    if (coOwnerModal) {
        const openBtn = document.getElementById('open-co-owner-modal-btn'), closeBtn = coOwnerModal.querySelector('.modal-close-btn'), saveBtn = document.getElementById('save-co-owners-btn');
        const ownerIdsInput = document.getElementById('owner_ids_input'), checkboxes = coOwnerModal.querySelectorAll('input[name="modal_owner_ids"]');
        openBtn.addEventListener('click', () => { const currentIds = ownerIdsInput.value.split(',').filter(id => id); checkboxes.forEach(cb => { cb.checked = currentIds.includes(cb.value); }); coOwnerModal.classList.remove('hidden'); });
        closeBtn.addEventListener('click', () => coOwnerModal.classList.add('hidden'));
        saveBtn.addEventListener('click', () => {
            const selectedIds = [];
            checkboxes.forEach(cb => { if (cb.checked) selectedIds.push(cb.value); });
            const creatorId = "<?php echo $event['created_by_user_id'] ?: $current_user_id; ?>";
            if (!selectedIds.includes(creatorId)) selectedIds.push(creatorId);
            ownerIdsInput.value = [...new Set(selectedIds)].join(',');
            coOwnerModal.classList.add('hidden');
        });
    }
});
</script>

<?php require_once 'partials/footer.php'; ?>

