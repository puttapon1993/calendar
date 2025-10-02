<?php
$page_title = 'เพิ่ม/แก้ไข กิจกรรม';
require_once 'partials/header.php';
require_once '../config.php';

// Initialize variables
$event = [
    'id' => '',
    'event_name' => '',
    'responsible_unit' => '',
    'notes' => ''
];
$event_dates = [];
$date_type = 'single'; // Default type for new events

// Helper to convert Y-m-d to d-m-Y(BE)
function convert_to_be_text($date_str) {
    if (empty($date_str)) return '';
    try {
        $date = new DateTime($date_str);
        return $date->format('d') . $date->format('m') . ($date->format('Y') + 543);
    } catch (Exception $e) {
        return '';
    }
}


// Check if we are in edit mode
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $event_id = $_GET['id'];
    try {
        // Fetch event details
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            $_SESSION['error_message'] = "ไม่พบกิจกรรมที่ระบุ";
            header('Location: events.php');
            exit;
        }

        // Fetch all dates for this event
        $stmt_dates = $pdo->prepare("SELECT activity_date FROM event_dates WHERE event_id = ? ORDER BY activity_date ASC");
        $stmt_dates->execute([$event_id]);
        $event_dates_raw = $stmt_dates->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine the date type based on the fetched dates
        if (count($event_dates_raw) > 1) {
             // Check if dates are continuous
            $is_continuous = true;
            for ($i = 0; $i < count($event_dates_raw) - 1; $i++) {
                $date1 = new DateTime($event_dates_raw[$i]);
                $date2 = new DateTime($event_dates_raw[$i+1]);
                if ($date1->diff($date2)->days != 1) {
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
?>

<form action="save_event.php" method="POST" class="data-form">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id']); ?>">

    <div class="form-group">
        <label for="event_name">ชื่อกิจกรรม</label>
        <input type="text" id="event_name" name="event_name" value="<?php echo htmlspecialchars($event['event_name']); ?>" required>
    </div>

    <div class="form-group">
        <label for="responsible_unit">หน่วยงานที่รับผิดชอบ (ไม่บังคับ)</label>
        <input type="text" id="responsible_unit" name="responsible_unit" value="<?php echo htmlspecialchars($event['responsible_unit']); ?>">
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
            <!-- Date Inputs Container -->
            <div id="date-inputs-container">
                <!-- Single Date -->
                <div id="single-date-input" class="date-input-group <?php echo $date_type !== 'single' ? 'hidden' : ''; ?>">
                    <div class="form-group">
                        <label for="single_date_text">เลือกวันที่</label>
                        <input type="text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text(($date_type === 'single' && !empty($event_dates)) ? $event_dates[0] : ''); ?>">
                        <input type="hidden" name="single_date" value="<?php echo ($date_type === 'single' && !empty($event_dates)) ? $event_dates[0] : ''; ?>">
                    </div>
                </div>

                <!-- Continuous Dates -->
                <div id="continuous-date-input" class="date-input-group <?php echo $date_type !== 'continuous' ? 'hidden' : ''; ?>">
                     <div class="form-group">
                        <label for="start_date_text">วันเริ่มต้น</label>
                        <input type="text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text(($date_type === 'continuous' && !empty($event_dates)) ? $event_dates[0] : ''); ?>">
                        <input type="hidden" name="start_date" value="<?php echo ($date_type === 'continuous' && !empty($event_dates)) ? $event_dates[0] : ''; ?>">
                    </div>
                     <div class="form-group">
                        <label for="end_date_text">วันสิ้นสุด</label>
                        <input type="text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text(($date_type === 'continuous' && !empty($event_dates)) ? end($event_dates) : ''); ?>">
                        <input type="hidden" name="end_date" value="<?php echo ($date_type === 'continuous' && !empty($event_dates)) ? end($event_dates) : ''; ?>">
                    </div>
                </div>

                <!-- Non-Continuous Dates -->
                <div id="non-continuous-date-input" class="date-input-group <?php echo $date_type !== 'non-continuous' ? 'hidden' : ''; ?>">
                    <div id="non-continuous-dates-list">
                        <?php if ($date_type === 'non-continuous' && !empty($event_dates)): ?>
                            <?php foreach($event_dates as $date): ?>
                            <div class="date-entry">
                                <input type="text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text($date); ?>">
                                <input type="hidden" name="non_continuous_dates[]" value="<?php echo $date; ?>">
                                <button type="button" class="button button-danger remove-date-btn">ลบ</button>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="date-entry">
                                <input type="text" class="date-input-be" placeholder="ววดดปปปป" value="">
                                <input type="hidden" name="non_continuous_dates[]" value="">
                                <button type="button" class="button button-danger remove-date-btn" style="display:none;">ลบ</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-date-btn" class="button button-secondary"> + เพิ่มวัน</button>
                </div>
            </div>

            <!-- Helper Box -->
            <div class="date-helper">
                <strong>คำแนะนำ</strong>
                <p>กรอกวันที่ในรูปแบบ <strong>ววดดปปปป</strong><br>ตัวอย่าง: <strong>28092568</strong></p>
                <ul>
                    <li>01 = ม.ค.</li>
                    <li>02 = ก.พ.</li>
                    <li>03 = มี.ค.</li>
                    <li>04 = เม.ย.</li>
                    <li>05 = พ.ค.</li>
                    <li>06 = มิ.ย.</li>
                    <li>07 = ก.ค.</li>
                    <li>08 = ส.ค.</li>
                    <li>09 = ก.ย.</li>
                    <li>10 = ต.ค.</li>
                    <li>11 = พ.ย.</li>
                    <li>12 = ธ.ค.</li>
                </ul>
            </div>
        </div>

    </fieldset>

    <div class="form-actions">
        <button type="submit" class="button">บันทึกข้อมูล</button>
        <a href="events.php" class="button button-secondary">ยกเลิก</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function convertAndValidateBE(textInput) {
        const hiddenInput = textInput.nextElementSibling;
        let value = textInput.value.replace(/\D/g, ''); // Remove non-digits
        
        textInput.classList.remove('is-invalid', 'is-valid');

        if (value.length < 8) {
            hiddenInput.value = '';
            return;
        }

        if (value.length > 8) {
            value = value.substring(0, 8);
            textInput.value = value;
        }

        const day = parseInt(value.substring(0, 2), 10);
        const month = parseInt(value.substring(2, 4), 10);
        const yearBE = parseInt(value.substring(4, 8), 10);
        
        if (isNaN(day) || isNaN(month) || isNaN(yearBE) || 
            month < 1 || month > 12 || day < 1 || day > 31 || yearBE < 2500) {
            hiddenInput.value = '';
            textInput.classList.add('is-invalid');
            return;
        }

        const yearCE = yearBE - 543;
        const formattedDate = `${yearCE}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        
        // Final check if the generated date is a real date
        const testDate = new Date(formattedDate);
        if (testDate.getFullYear() === yearCE && (testDate.getMonth() + 1) === month && testDate.getDate() === day) {
            hiddenInput.value = formattedDate;
            textInput.classList.add('is-valid');
        } else {
            hiddenInput.value = '';
            textInput.classList.add('is-invalid');
        }
    }

    function initializeDateInput(element) {
        element.addEventListener('input', function() {
            convertAndValidateBE(this);
        });
        // Initial validation for pre-filled values
        if(element.value) {
            convertAndValidateBE(element);
        }
    }

    // Attach listener to all existing and future date inputs
    const container = document.getElementById('date-inputs-container');
    container.querySelectorAll('.date-input-be').forEach(initializeDateInput);
    
    // --- Date Type Toggling Logic ---
    const dateTypeRadios = document.querySelectorAll('input[name="date_type"]');
    const singleDateInput = document.getElementById('single-date-input');
    const continuousDateInput = document.getElementById('continuous-date-input');
    const nonContinuousDateInput = document.getElementById('non-continuous-date-input');

    function updateDateInputVisibility() {
        const selectedValue = document.querySelector('input[name="date_type"]:checked').value;
        singleDateInput.classList.add('hidden');
        continuousDateInput.classList.add('hidden');
        nonContinuousDateInput.classList.add('hidden');

        if (selectedValue === 'single') singleDateInput.classList.remove('hidden');
        else if (selectedValue === 'continuous') continuousDateInput.classList.remove('hidden');
        else if (selectedValue === 'non-continuous') nonContinuousDateInput.classList.remove('hidden');
    }

    dateTypeRadios.forEach(radio => {
        radio.addEventListener('change', updateDateInputVisibility);
    });
    
    // Initial call to set the correct view
    updateDateInputVisibility();

    // --- Non-Continuous Date Adding/Removing Logic ---
    const nonContinuousList = document.getElementById('non-continuous-dates-list');
    const addDateBtn = document.getElementById('add-date-btn');

    if(addDateBtn) {
        addDateBtn.addEventListener('click', function() {
            const newEntry = document.createElement('div');
            newEntry.className = 'date-entry';
            newEntry.innerHTML = `
                <input type="text" class="date-input-be" placeholder="ววดดปปปป" value="">
                <input type="hidden" name="non_continuous_dates[]" value="">
                <button type="button" class="button button-danger remove-date-btn">ลบ</button>
            `;
            const newTextInput = newEntry.querySelector('.date-input-be');
            initializeDateInput(newTextInput); // IMPORTANT: Initialize the new input
            nonContinuousList.appendChild(newEntry);
        });
    }

    if(nonContinuousList) {
        nonContinuousList.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-date-btn')) {
                if (nonContinuousList.querySelectorAll('.date-entry').length > 1) {
                    e.target.parentElement.remove();
                }
            }
        });
    }
});
</script>

<?php
require_once 'partials/footer.php';
?>

