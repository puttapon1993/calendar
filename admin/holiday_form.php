<?php
$page_title = 'เพิ่ม/แก้ไข วันหยุดพิเศษ';
require_once 'partials/header.php';
require_once '../config.php';

$holiday = ['id' => '', 'holiday_name' => '', 'holiday_date' => ''];

// Helper to convert Y-m-d to ddmmyyyy(BE)
function convert_to_be_text($date_str) {
    if (empty($date_str)) return '';
    try {
        $date = new DateTime($date_str);
        return $date->format('d') . $date->format('m') . ($date->format('Y') + 543);
    } catch (Exception $e) {
        return '';
    }
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    // Edit mode
    try {
        $stmt = $pdo->prepare("SELECT * FROM special_holidays WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $holiday = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$holiday) {
            // Holiday not found
            $_SESSION['error_message'] = "ไม่พบวันหยุดที่ระบุ";
            header('Location: holidays.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header('Location: holidays.php');
        exit;
    }
}
?>

<form action="save_holiday.php" method="POST" class="data-form">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($holiday['id']); ?>">

    <div class="form-group">
        <label for="holiday_name">ชื่อวันหยุดพิเศษ</label>
        <input type="text" id="holiday_name" name="holiday_name" value="<?php echo htmlspecialchars($holiday['holiday_name']); ?>" required>
    </div>

    <div class="date-input-container-with-helper">
        <div id="date-inputs-container">
            <div class="form-group">
                <label for="holiday_date_text">วันที่</label>
                <input type="text" id="holiday_date_text" class="date-input-be" placeholder="ววดดปปปป" value="<?php echo convert_to_be_text(htmlspecialchars($holiday['holiday_date'])); ?>">
                <input type="hidden" name="holiday_date" value="<?php echo htmlspecialchars($holiday['holiday_date']); ?>">
            </div>
        </div>
         <!-- Helper Box -->
        <div class="date-helper">
            <strong>คำแนะนำ</strong>
            <p>กรอกวันที่ในรูปแบบ <strong>ววดดปปปป</strong><br>ตัวอย่าง: <strong>28092568</strong></p>
            <ul>
                <li>01 = ม.ค.</li><li>02 = ก.พ.</li>
                <li>03 = มี.ค.</li><li>04 = เม.ย.</li>
                <li>05 = พ.ค.</li><li>06 = มิ.ย.</li>
                <li>07 = ก.ค.</li><li>08 = ส.ค.</li>
                <li>09 = ก.ย.</li><li>10 = ต.ค.</li>
                <li>11 = พ.ย.</li><li>12 = ธ.ค.</li>
            </ul>
        </div>
    </div>


    <div class="form-actions">
        <button type="submit" class="button">บันทึกข้อมูล</button>
        <a href="holidays.php" class="button button-secondary">ยกเลิก</a>
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
        
        const testDate = new Date(formattedDate);
        if (testDate.getFullYear() === yearCE && (testDate.getMonth() + 1) === month && testDate.getDate() === day) {
            hiddenInput.value = formattedDate;
            textInput.classList.add('is-valid');
        } else {
            hiddenInput.value = '';
            textInput.classList.add('is-invalid');
        }
    }

    const dateInput = document.getElementById('holiday_date_text');
    if (dateInput) {
        dateInput.addEventListener('input', function() {
            convertAndValidateBE(this);
        });
        // Initial validation for pre-filled values
        if(dateInput.value) {
            convertAndValidateBE(dateInput);
        }
    }
});
</script>

<?php
require_once 'partials/footer.php';
?>

