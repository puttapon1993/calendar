<?php
// File: settings.php
// Location: /admin/
$page_title = 'ตั้งค่าเว็บไซต์';
require_once 'partials/header.php';

// Security Check: Only admins can access this page
if (!is_admin()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header('Location: dashboard.php');
    exit;
}

require_once '../config.php';

// Fetch all settings from the database
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("Could not load settings: " . $e->getMessage());
}

// Function to safely get a setting value
function get_setting($key, $default = '') {
    global $settings_raw;
    return isset($settings_raw[$key]) ? htmlspecialchars($settings_raw[$key]) : $default;
}

// Prepare date values for form
$start_date = get_setting('publish_start_date', date('Y-m'));
list($start_year, $start_month) = explode('-', $start_date);
$start_year_be = (int)$start_year + 543;

$end_date = get_setting('publish_end_date', date('Y-m', strtotime('+1 year')));
list($end_year, $end_month) = explode('-', $end_date);
$end_year_be = (int)$end_year + 543;

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

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

<form action="save_settings.php" method="POST" class="data-form">
    
    <fieldset>
        <legend>การตั้งค่าทั่วไป</legend>
        <div class="form-group">
            <label for="site_title">หัวข้อเว็บไซต์ (Title Name & Header)</label>
            <input type="text" id="site_title" name="settings[site_title]" value="<?php echo get_setting('site_title', 'ปฏิทินกิจกรรมโรงเรียน'); ?>">
        </div>
        <div class="form-group">
            <label for="footer_text">ข้อความท้ายเว็บ (Footer)</label>
            <textarea id="footer_text" name="settings[footer_text]" rows="4"><?php echo get_setting('footer_text', '© โรงเรียนรักวิทยาคม'); ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>สถานะและการเผยแพร่</legend>
        <div class="form-group">
            <label>สถานะการเผยแพร่เว็บไซต์</label>
            <div class="radio-group">
                <label><input type="radio" name="settings[site_publication_status]" value="published" <?php echo get_setting('site_publication_status', 'published') == 'published' ? 'checked' : ''; ?>> เผยแพร่</label>
                <label><input type="radio" name="settings[site_publication_status]" value="unpublished" <?php echo get_setting('site_publication_status') == 'unpublished' ? 'checked' : ''; ?>> ไม่เผยแพร่ (ซ่อนข้อมูลทั้งหมด)</label>
            </div>
        </div>
        <div class="form-group">
            <label>กำหนดช่วงเวลาเผยแพร่ข้อมูล</label>
            <div class="date-range-selector">
                <div>
                    <span>เดือน-ปี เริ่มต้น:</span>
                    <select name="start_month">
                        <?php foreach($thai_months as $num => $name): ?>
                        <option value="<?php echo $num; ?>" <?php echo $num == $start_month ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="start_year_be" class="year-input" value="<?php echo $start_year_be; ?>" placeholder="พ.ศ." min="2560" max="2600">
                </div>
                 <div>
                    <span>ถึง เดือน-ปี สิ้นสุด:</span>
                    <select name="end_month">
                        <?php foreach($thai_months as $num => $name): ?>
                        <option value="<?php echo $num; ?>" <?php echo $num == $end_month ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="end_year_be" class="year-input" value="<?php echo $end_year_be; ?>" placeholder="พ.ศ." min="2560" max="2600">
                </div>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>การตั้งค่าการแสดงผล</legend>
         <div class="form-group">
            <label>การแสดงวันที่ปัจจุบัน (ใต้ Header)</label>
             <div class="radio-group">
                <label><input type="radio" name="settings[show_current_date]" value="1" <?php echo get_setting('show_current_date', '1') == '1' ? 'checked' : ''; ?>> แสดง</label>
                <label><input type="radio" name="settings[show_current_date]" value="0" <?php echo get_setting('show_current_date') == '0' ? 'checked' : ''; ?>> ซ่อน</label>
            </div>
        </div>
        <div class="form-group">
            <label>วันแรกของสัปดาห์ในปฏิทิน</label>
             <div class="radio-group">
                <label><input type="radio" name="settings[week_start_day]" value="sunday" <?php echo get_setting('week_start_day', 'sunday') == 'sunday' ? 'checked' : ''; ?>> วันอาทิตย์</label>
                <label><input type="radio" name="settings[week_start_day]" value="monday" <?php echo get_setting('week_start_day') == 'monday' ? 'checked' : ''; ?>> วันจันทร์</label>
            </div>
        </div>
        <div class="form-group">
            <label>การแสดงผลชื่อกิจกรรมในปฏิทิน</label>
             <div class="radio-group">
                <label><input type="radio" name="settings[truncate_event_names]" value="1" <?php echo get_setting('truncate_event_names', '1') == '1' ? 'checked' : ''; ?>> แสดงผลแบบย่อ (เช่น "โครงการปลูกป่า...")</label>
                <label><input type="radio" name="settings[truncate_event_names]" value="0" <?php echo get_setting('truncate_event_names') == '0' ? 'checked' : ''; ?>> แสดงผลแบบเต็ม</label>
            </div>
        </div>
        <div class="form-group">
            <label>รูปแบบเมนูนำทาง "เดือน-ปี"</label>
             <div class="radio-group">
                <label><input type="radio" name="settings[nav_menu_style]" value="buttons" <?php echo get_setting('nav_menu_style', 'buttons') == 'buttons' ? 'checked' : ''; ?>> ปุ่มเรียงกัน</label>
                <label><input type="radio" name="settings[nav_menu_style]" value="dropdown" <?php echo get_setting('nav_menu_style') == 'dropdown' ? 'checked' : ''; ?>> Dropdown List</label>
            </div>
        </div>
         <div class="form-group">
            <label>รูปแบบการแสดงหน่วยงานที่รับผิดชอบ</label>
            <select name="settings[responsible_unit_format]">
                <option value="parenthesis" <?php echo get_setting('responsible_unit_format') == 'parenthesis' ? 'selected' : ''; ?>>ตรวจสุขภาพประจำปี (งานอนามัย)</option>
                <option value="dash" <?php echo get_setting('responsible_unit_format') == 'dash' ? 'selected' : ''; ?>>ตรวจสุขภาพประจำปี - งานอนามัย</option>
                <option value="slash" <?php echo get_setting('responsible_unit_format') == 'slash' ? 'selected' : ''; ?>>ตรวจสุขภาพประจำปี / งานอนามัย</option>
                <option value="colon" <?php echo get_setting('responsible_unit_format') == 'colon' ? 'selected' : ''; ?>>ตรวจสุขภาพประจำปี : งานอนามัย</option>
                <option value="hide" <?php echo get_setting('responsible_unit_format', 'parenthesis') == 'hide' ? 'selected' : ''; ?>>ซ่อนหน่วยงานที่รับผิดชอบ</option>
            </select>
        </div>
    </fieldset>
    
    <fieldset>
        <legend>การตั้งค่ารูปแบบวันที่ใน "มุมมองตาราง"</legend>
        <div class="settings-grid">
            <div class="form-group">
                <label>รูปแบบเดือน</label>
                <select name="settings[table_month_format]">
                    <option value="full" <?php echo get_setting('table_month_format', 'full') == 'full' ? 'selected' : ''; ?>>ชื่อเต็ม (มกราคม)</option>
                    <option value="short" <?php echo get_setting('table_month_format') == 'short' ? 'selected' : ''; ?>>ชื่อย่อ (ม.ค.)</option>
                </select>
            </div>
            <div class="form-group">
                <label>รูปแบบปี</label>
                <select name="settings[table_year_format]">
                    <option value="be_4" <?php echo get_setting('table_year_format', 'be_4') == 'be_4' ? 'selected' : ''; ?>>พ.ศ. 4 หลัก (2568)</option>
                    <option value="be_2" <?php echo get_setting('table_year_format') == 'be_2' ? 'selected' : ''; ?>>พ.ศ. 2 หลัก (68)</option>
                    <option value="ce_4" <?php echo get_setting('table_year_format') == 'ce_4' ? 'selected' : ''; ?>>ค.ศ. 4 หลัก (2025)</option>
                    <option value="ce_2" <?php echo get_setting('table_year_format') == 'ce_2' ? 'selected' : ''; ?>>ค.ศ. 2 หลัก (25)</option>
                </select>
            </div>
            <div class="form-group">
                <label>รูปแบบชื่อวัน</label>
                <select name="settings[table_day_name_format]">
                    <option value="full_prefix" <?php echo get_setting('table_day_name_format') == 'full_prefix' ? 'selected' : ''; ?>>มี "วัน" (วันจันทร์)</option>
                    <option value="full" <?php echo get_setting('table_day_name_format') == 'full' ? 'selected' : ''; ?>>ไม่มี "วัน" (จันทร์)</option>
                    <option value="short" <?php echo get_setting('table_day_name_format') == 'short' ? 'selected' : ''; ?>>แบบย่อ (จ)</option>
                    <option value="short_dot" <?php echo get_setting('table_day_name_format', 'short_dot') == 'short_dot' ? 'selected' : ''; ?>>แบบย่อมีจุด (จ.)</option>
                    <option value="none" <?php echo get_setting('table_day_name_format') == 'none' ? 'selected' : ''; ?>>ไม่ต้องแสดง</option>
                </select>
            </div>
            <div class="form-group">
                <label>เครื่องหมายกำกับชื่อวัน</label>
                <select name="settings[table_day_name_style]">
                    <option value="parenthesis" <?php echo get_setting('table_day_name_style', 'parenthesis') == 'parenthesis' ? 'selected' : ''; ?>>(ชื่อวัน)</option>
                    <option value="bracket" <?php echo get_setting('table_day_name_style') == 'bracket' ? 'selected' : ''; ?>>[ชื่อวัน]</option>
                    <option value="dash" <?php echo get_setting('table_day_name_style') == 'dash' ? 'selected' : ''; ?>>ชื่อวัน -</option>
                    <option value="slash" <?php echo get_setting('table_day_name_style') == 'slash' ? 'selected' : ''; ?>>ชื่อวัน /</option>
                    <option value="colon" <?php echo get_setting('table_day_name_style') == 'colon' ? 'selected' : ''; ?>>ชื่อวัน :</option>
                     <option value="none" <?php echo get_setting('table_day_name_style') == 'none' ? 'selected' : ''; ?>>ไม่ต้องมีเครื่องหมาย</option>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>แถบข้อความเลื่อน (Ticker)</legend>
        <div class="form-group">
            <label>การแสดงผลแถบข้อความเลื่อน</label>
            <div class="radio-group">
                <label><input type="radio" name="settings[show_event_ticker]" value="1" <?php echo get_setting('show_event_ticker', '1') == '1' ? 'checked' : ''; ?>> แสดง</label>
                <label><input type="radio" name="settings[show_event_ticker]" value="0" <?php echo get_setting('show_event_ticker') == '0' ? 'checked' : ''; ?>> ซ่อน</label>
            </div>
        </div>
         <div class="form-group">
            <label for="ticker_custom_message">ข้อความประชาสัมพันธ์เพิ่มเติม</label>
            <textarea id="ticker_custom_message" name="settings[ticker_custom_message]" rows="3"><?php echo get_setting('ticker_custom_message'); ?></textarea>
        </div>
        <div class="settings-grid">
            <div class="form-group">
                <label for="ticker_speed">ความเร็ว (วินาที)</label>
                <input type="number" id="ticker_speed" name="settings[ticker_speed]" value="<?php echo get_setting('ticker_speed', '20'); ?>" min="5" step="1">
            </div>
            <div class="form-group">
                <label for="ticker_text_color">สีตัวอักษร</label>
                <input type="color" id="ticker_text_color" name="settings[ticker_text_color]" value="<?php echo get_setting('ticker_text_color', '#000000'); ?>">
            </div>
        </div>
    </fieldset>


    <fieldset>
        <legend>การตั้งค่าสี</legend>
        <div class="color-picker-grid">
            <div class="form-group">
                <label for="header_bg_color">สีพื้นหลังหัวเว็บ</label>
                <input type="color" id="header_bg_color" name="settings[header_bg_color]" value="<?php echo get_setting('header_bg_color', '#2c3e50'); ?>">
            </div>
            <div class="form-group">
                <label for="header_text_color">สีตัวอักษรหัวเว็บ</label>
                <input type="color" id="header_text_color" name="settings[header_text_color]" value="<?php echo get_setting('header_text_color', '#ffffff'); ?>">
            </div>
             <div class="form-group">
                <label for="site_bg_color">สีพื้นหลังเว็บไซต์</label>
                <input type="color" id="site_bg_color" name="settings[site_bg_color]" value="<?php echo get_setting('site_bg_color', '#ecf0f1'); ?>">
            </div>
            <div class="form-group">
                <label for="saturday_bg_color">สีพื้นหลังวันเสาร์</label>
                <input type="color" id="saturday_bg_color" name="settings[saturday_bg_color]" value="<?php echo get_setting('saturday_bg_color', '#f0f8ff'); ?>">
            </div>
             <div class="form-group">
                <label for="sunday_bg_color">สีพื้นหลังวันอาทิตย์</label>
                <input type="color" id="sunday_bg_color" name="settings[sunday_bg_color]" value="<?php echo get_setting('sunday_bg_color', '#fff0f0'); ?>">
            </div>
            <div class="form-group">
                <label for="event_date_bg_color">สีพื้นหลังวันที่มีกิจกรรม</label>
                <input type="color" id="event_date_bg_color" name="settings[event_date_bg_color]" value="<?php echo get_setting('event_date_bg_color', '#d9edf7'); ?>">
            </div>
             <div class="form-group">
                <label for="no_event_date_bg_color">สีพื้นหลังวันที่ไม่มีกิจกรรม</label>
                <input type="color" id="no_event_date_bg_color" name="settings[no_event_date_bg_color]" value="<?php echo get_setting('no_event_date_bg_color', '#fafafa'); ?>">
            </div>
            <div class="form-group">
                <label for="holiday_bg_color">สีพื้นหลังวันสำคัญ</label>
                <input type="color" id="holiday_bg_color" name="settings[holiday_bg_color]" value="<?php echo get_setting('holiday_bg_color', '#f2dede'); ?>">
            </div>
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="button">บันทึกการตั้งค่า</button>
    </div>
</form>

<?php
require_once 'partials/footer.php';
?>

