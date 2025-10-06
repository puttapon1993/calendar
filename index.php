<?php
// File: index.php
// Location: /
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดร้ายแรง: ไม่สามารถเชื่อมต่อฐานข้อมูลการตั้งค่าได้. " . $e->getMessage());
}

function get_setting($key, $default = '') {
    global $settings_raw;
    return isset($settings_raw[$key]) ? $settings_raw[$key] : $default;
}

// Check publication status first
if (get_setting('site_publication_status', 'published') === 'unpublished') {
    // A simple page to show when the site is offline
    echo <<<HTML
<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><title>ปิดปรับปรุง</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500&display=swap" rel="stylesheet">
<style>body{font-family:'Sarabun',sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background-color:#f0f0f0;}.message{text-align:center;padding:2rem;background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);}</style></head>
<body><div class="message"><h1>ปิดให้บริการชั่วคราว</h1><p>ขณะนี้ไม่มีข้อมูลกิจกรรมที่เผยแพร่</p></div></body></html>
HTML;
    exit;
}

$thai_day_full = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];
$thai_month_full = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
$today = new DateTime();
$current_date_thai = $thai_day_full[$today->format('w')] . 'ที่ ' . (int)$today->format('j') . ' ' . $thai_month_full[(int)$today->format('n')] . ' พ.ศ. ' . ($today->format('Y') + 543);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(get_setting('site_title', 'ปฏิทินกิจกรรม')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <style>
        /* Dynamic Styles from Admin Settings */
        body {
            background-color: <?php echo htmlspecialchars(get_setting('site_bg_color', '#ecf0f1')); ?>;
        }
        .site-header {
            background-color: <?php echo htmlspecialchars(get_setting('header_bg_color', '#2c3e50')); ?>;
            color: <?php echo htmlspecialchars(get_setting('header_text_color', '#ffffff')); ?>;
        }
        .calendar-day.has-event { background-color: <?php echo htmlspecialchars(get_setting('event_date_bg_color', '#d9edf7')); ?>; }
        .calendar-day.is-holiday { background-color: <?php echo htmlspecialchars(get_setting('holiday_bg_color', '#f2dede')); ?>; }
        .calendar-day.is-saturday { background-color: <?php echo htmlspecialchars(get_setting('saturday_bg_color', '#f0f8ff')); ?>; }
        .calendar-day.is-sunday { background-color: <?php echo htmlspecialchars(get_setting('sunday_bg_color', '#fff0f0')); ?>; }
        .calendar-day:not(.has-event):not(.is-holiday):not(.is-saturday):not(.is-sunday) {
             background-color: <?php echo htmlspecialchars(get_setting('no_event_date_bg_color', '#fafafa')); ?>;
        }
        .calendar-day.is-today { 
            box-shadow: inset 0 0 0 2px #3498db; 
        }
    </style>
</head>
<body>
    <div class="site-container">
        <header class="site-header">
            <h1><?php echo nl2br(htmlspecialchars(get_setting('site_title', 'ปฏิทินกิจกรรมโรงเรียน'))); ?></h1>
            <?php if (get_setting('show_current_date', '1') === '1'): ?>
            <p><?php echo $current_date_thai; ?></p>
            <?php endif; ?>
        </header>

        <div id="event-ticker-container" class="hidden">
            <!-- Ticker content will be loaded here by JavaScript -->
        </div>

        <div id="navigation-container">
            <!-- Navigation will be loaded here by JavaScript -->
        </div>

        <main class="main-content">
            <div class="controls-toolbar">
                <div class="view-switcher">
                    <button id="calendar-view-btn" class="active">📅 มุมมองปฏิทิน</button>
                    <button id="table-view-btn">📄 มุมมองตาราง</button>
                </div>
                 <div class="search-container">
                    <input type="search" id="search-box" placeholder="ค้นหากิจกรรมและวันสำคัญ...">
                </div>
                <div class="actions-group">
                    <button id="print-btn">🖨️ พิมพ์</button>
                    <button id="report-problem-btn">⚠️ แจ้งปัญหา</button>
                </div>
            </div>

            <div id="search-results-container" class="hidden"></div>
            <div id="all-events-container" class="hidden"></div>
            
            <div id="calendar-container">
                <div class="calendar-header">
                    <button id="prev-month-btn">&lt;</button>
                    <h2 id="month-year-header"></h2>
                    <button id="next-month-btn">&gt;</button>
                </div>
                <div id="calendar-grid" class="calendar-grid"></div>
            </div>

            <div id="table-view-container" class="hidden">
                 <h2 id="table-month-year-header"></h2>
                 <table id="event-table">
                     <thead>
                         <tr>
                             <th>วันที่</th>
                             <th>ชื่อกิจกรรม</th>
                             <th>หน่วยงาน</th>
                             <th><i class="fas fa-cog"></i></th>
                         </tr>
                     </thead>
                     <tbody></tbody>
                 </table>
            </div>

            <div id="loading-spinner" class="hidden">
                <div class="spinner"></div>
            </div>
        </main>

        <footer class="site-footer">
            <p><?php echo nl2br(htmlspecialchars(get_setting('footer_text', '© โรงเรียนรักวิทยาคม'))); ?></p>
            <div class="footer-login-link">
                <a href="admin/">เข้าสู่ระบบ</a>
            </div>
        </footer>
    </div>
    
    <div id="event-details-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <button class="modal-close-btn">&times;</button>
            <h3 id="event-modal-title">รายละเอียดกิจกรรม</h3>
            <div id="event-modal-body"></div>
            <div id="event-modal-actions" class="modal-actions"></div>
        </div>
    </div>

    <div id="report-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <button class="modal-close-btn">&times;</button>
            <h3>แจ้งปัญหา / ข้อมูลผิดพลาด</h3>
            <textarea id="report-message" placeholder="กรุณาอธิบายปัญหาที่พบ..." rows="5"></textarea>
            <button id="submit-report-btn" class="button">ส่งข้อความ</button>
            <p id="report-status-msg"></p>
        </div>
    </div>
    
    <div id="print-modal" class="modal-overlay hidden">
         <div class="modal-content">
            <button class="modal-close-btn">&times;</button>
            <h3>เลือกรูปแบบการพิมพ์</h3>
            <div class="print-options">
                <button class="print-option-btn" data-print-type="calendar">พิมพ์มุมมองปฏิทิน</button>
                <button class="print-option-btn" data-print-type="table">พิมพ์มุมมองตาราง</button>
                <button class="print-option-btn" data-print-type="mixed">พิมพ์มุมมองผสม</button>
            </div>
        </div>
    </div>

    <script>
        const siteSettings = <?php echo json_encode($settings_raw); ?>;
    </script>
    <script src="assets/js/script.js?v=<?php echo filemtime('assets/js/script.js'); ?>"></script>
</body>
</html>
