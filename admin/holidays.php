<?php
$page_title = 'จัดการวันหยุดพิเศษ';
require_once 'partials/header.php';
require_once '../config.php';

// --- Sorting Logic ---
$sort_order = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';
$sort_param = $sort_order === 'ASC' ? 'asc' : 'desc';

// --- Helper Functions ---
function format_holiday_date($date_str) {
    if (empty($date_str)) return 'N/A';
    
    $date = new DateTime($date_str);
    $thai_day_short = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
    $thai_month_short = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $day_name = $thai_day_short[$date->format('w')];
    return "({$day_name}) " . $date->format('j') . ' ' . $thai_month_short[(int)$date->format('n')] . ' ' . ($date->format('Y') + 543);
}

try {
    // Fetch all holidays, ordered by date
    $stmt = $pdo->prepare("SELECT * FROM special_holidays ORDER BY holiday_date {$sort_order}");
    $stmt->execute();
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group holidays by month-year
    $grouped_holidays = [];
    $thai_month_full = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    foreach ($holidays as $holiday) {
        $date = new DateTime($holiday['holiday_date']);
        $month_key = $date->format('Y-m');
        $month_label = $thai_month_full[(int)$date->format('n')] . ' ' . ($date->format('Y') + 543);
        
        if (!isset($grouped_holidays[$month_key])) {
            $grouped_holidays[$month_key] = [
                'label' => $month_label,
                'holidays' => []
            ];
        }
        $grouped_holidays[$month_key]['holidays'][] = $holiday;
    }

} catch (PDOException $e) {
    $grouped_holidays = [];
    echo "<p class='error'>Could not fetch holidays: " . $e->getMessage() . "</p>";
}
?>

<div class="toolbar-grid">
    <a href="holiday_form.php" class="button"> + เพิ่มวันหยุดพิเศษ</a>
    
    <div class="search-sort-container">
        <div class="sort-controls">
            <span>เรียงลำดับ:</span>
            <a href="?sort=desc" class="<?php echo $sort_param === 'desc' ? 'active' : ''; ?>">ใหม่ไปเก่า</a>
            <a href="?sort=asc" class="<?php echo $sort_param === 'asc' ? 'active' : ''; ?>">เก่าไปใหม่</a>
        </div>
        <input type="text" id="holidaySearchInput" placeholder="ค้นหาชื่อวันหยุด...">
    </div>
</div>


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
    <table class="data-table" id="holidaysTable">
        <thead>
            <tr>
                <th>ชื่อวันหยุดพิเศษ</th>
                <th>วันที่</th>
                <th>เครื่องมือ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($grouped_holidays)): ?>
                <tr>
                    <td colspan="3" style="text-align:center;">ยังไม่มีข้อมูลวันหยุดพิเศษ...</td>
                </tr>
            <?php else: ?>
                <?php foreach ($grouped_holidays as $group): ?>
                    <tr class="month-header">
                        <td colspan="3"><?php echo $group['label']; ?></td>
                    </tr>
                    <?php foreach ($group['holidays'] as $holiday): ?>
                        <tr class="holiday-row">
                            <td data-label="ชื่อวันหยุด"><?php echo htmlspecialchars($holiday['holiday_name']); ?></td>
                            <td data-label="วันที่"><?php echo format_holiday_date($holiday['holiday_date']); ?></td>
                            <td data-label="เครื่องมือ">
                                <div class="action-buttons">
                                    <a href="holiday_form.php?id=<?php echo $holiday['id']; ?>" class="button button-edit">แก้ไข</a>
                                    <a href="delete_holiday.php?id=<?php echo $holiday['id']; ?>" class="button button-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะลบวันหยุดนี้?');">ลบ</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('holidaySearchInput');
    const table = document.getElementById('holidaysTable');
    const rows = table.querySelectorAll('tbody tr.holiday-row');
    const monthHeaders = table.querySelectorAll('tbody tr.month-header');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = searchInput.value.toLowerCase();

        rows.forEach(row => {
            const holidayName = row.querySelector('td[data-label="ชื่อวันหยุด"]').textContent.toLowerCase();
            if (holidayName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Update visibility of month headers
        monthHeaders.forEach(header => {
            let nextRow = header.nextElementSibling;
            let hasVisibleHolidays = false;
            while (nextRow && nextRow.classList.contains('holiday-row')) {
                if (nextRow.style.display !== 'none') {
                    hasVisibleHolidays = true;
                    break;
                }
                nextRow = nextRow.nextElementSibling;
            }
            header.style.display = hasVisibleHolidays ? '' : 'none';
        });
    });
});
</script>

<?php
require_once 'partials/footer.php';
?>

