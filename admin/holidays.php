<?php
// File: holidays.php
// Location: /admin/
$page_title = 'จัดการวันสำคัญ';
require_once 'partials/header.php';
require_once '../config.php';

$sort_order = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';
$sort_param = $sort_order === 'ASC' ? 'asc' : 'desc';
$current_user_id = $_SESSION['user_id'];

function format_holiday_date($date_str) {
    if (empty($date_str)) return 'N/A';
    $date = new DateTime($date_str);
    $thai_day_short = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
    $thai_month_short = [1=>'ม.ค.', 2=>'ก.พ.', 3=>'มี.ค.', 4=>'เม.ย.', 5=>'พ.ค.', 6=>'มิ.ย.', 7=>'ก.ค.', 8=>'ส.ค.', 9=>'ก.ย.', 10=>'ต.ค.', 11=>'พ.ย.', 12=>'ธ.ค.'];
    $day_name = $thai_day_short[$date->format('w')];
    return "({$day_name}) " . $date->format('j') . ' ' . $thai_month_short[(int)$date->format('n')] . ' ' . ($date->format('Y') + 543);
}

try {
    $sql = "
        SELECT 
            sh.*, 
            GROUP_CONCAT(DISTINCT u.real_name SEPARATOR ', ') as owner_names,
            GROUP_CONCAT(DISTINCT u.role SEPARATOR ', ') as owner_roles,
            creator.real_name as creator_name,
            creator.role as creator_role,
            (SELECT COUNT(*) FROM holiday_owners WHERE holiday_id = sh.id AND user_id != sh.created_by_user_id) > 0 as has_coowners
        FROM special_holidays sh
        LEFT JOIN holiday_owners ho ON sh.id = ho.holiday_id
        LEFT JOIN users u ON ho.user_id = u.id
        LEFT JOIN users creator ON sh.created_by_user_id = creator.id
    ";
    if (!is_admin()) {
        $sql .= " WHERE sh.id IN (SELECT holiday_id FROM holiday_owners WHERE user_id = :user_id)";
    }
    $sql .= " GROUP BY sh.id ORDER BY sh.holiday_date {$sort_order}";
    
    $stmt = $pdo->prepare($sql);
    if (!is_admin()) {
        $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped_holidays = [];
    $thai_month_full = [1=>'มกราคม', 2=>'กุมภาพันธ์', 3=>'มีนาคม', 4=>'เมษายน', 5=>'พฤษภาคม', 6=>'มิถุนายน', 7=>'กรกฎาคม', 8=>'สิงหาคม', 9=>'กันยายน', 10=>'ตุลาคม', 11=>'พฤศจิกายน', 12=>'ธันวาคม'];
    foreach ($holidays as $holiday) {
        $date = new DateTime($holiday['holiday_date']);
        $month_key = $date->format('Y-m');
        $month_label = $thai_month_full[(int)$date->format('n')] . ' ' . ($date->format('Y') + 543);
        
        if (!isset($grouped_holidays[$month_key])) {
            $grouped_holidays[$month_key] = ['label' => $month_label, 'holidays' => []];
        }
        $grouped_holidays[$month_key]['holidays'][] = $holiday;
    }
} catch (PDOException $e) {
    $grouped_holidays = [];
    echo "<p class='error'>Could not fetch holidays: " . $e->getMessage() . "</p>";
}
?>

<div class="toolbar-grid">
    <a href="holiday_form.php" class="button"> + เพิ่มวันสำคัญ</a>
    <div class="search-sort-container">
        <div class="sort-controls">
            <span>เรียงลำดับ:</span>
            <a href="?sort=desc" class="<?php echo $sort_param === 'desc' ? 'active' : ''; ?>">ใหม่ไปเก่า</a>
            <a href="?sort=asc" class="<?php echo $sort_param === 'asc' ? 'active' : ''; ?>">เก่าไปใหม่</a>
        </div>
        <input type="text" id="holidaySearchInput" placeholder="ค้นหาชื่อวันสำคัญ...">
    </div>
</div>

<?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); } if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>'; unset($_SESSION['error_message']); } ?>

<div class="table-container">
    <table class="data-table" id="holidaysTable">
        <thead>
            <tr>
                <th>ชื่อวันสำคัญ</th>
                <th>วันที่</th>
                <?php if(is_admin()): ?><th>ผู้สร้าง/เจ้าของ</th><?php endif; ?>
                <th>สถานะ</th>
                <th>เครื่องมือ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($grouped_holidays)): ?>
                <tr><td colspan="<?php echo is_admin() ? '5' : '4'; ?>" style="text-align:center;">ยังไม่มีข้อมูลวันสำคัญ...</td></tr>
            <?php else: ?>
                <?php foreach ($grouped_holidays as $group): ?>
                    <tr class="month-header"><td colspan="<?php echo is_admin() ? '5' : '4'; ?>"><?php echo $group['label']; ?></td></tr>
                    <?php foreach ($group['holidays'] as $holiday): 
                        $creator_display = $holiday['creator_name'] ?: '<i>ผู้ใช้ถูกลบ</i>';
                        $tooltip_text = 'สร้างโดย: ' . $creator_display . ($holiday['has_coowners'] ? ' | เจ้าของร่วม: ' . htmlspecialchars($holiday['owner_names']) : '');
                        $icon_class = $holiday['creator_role'] === 'admin' ? 'fas fa-user-shield creator-admin' : 'fas fa-user creator-staff';
                        if ($holiday['has_coowners']) $icon_class .= ' creator-coowned';
                    ?>
                        <tr class="holiday-row">
                            <td data-label="ชื่อวันสำคัญ"><?php echo htmlspecialchars($holiday['holiday_name']); ?></td>
                            <td data-label="วันที่"><?php echo format_holiday_date($holiday['holiday_date']); ?></td>
                            <?php if (is_admin()): ?>
                            <td data-label="ผู้สร้าง/เจ้าของ">
                                <span class="creator-tooltip" data-tooltip="<?php echo htmlspecialchars($tooltip_text); ?>">
                                    <i class="<?php echo $icon_class; ?>"></i>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td data-label="สถานะ">
                                <a href="toggle_holiday_status.php?id=<?php echo $holiday['id']; ?>" class="status-badge <?php echo $holiday['is_hidden'] ? 'status-hidden' : 'status-visible'; ?>">
                                    <?php echo $holiday['is_hidden'] ? 'ซ่อน' : 'แสดง'; ?>
                                </a>
                            </td>
                            <td data-label="เครื่องมือ">
                                <div class="action-buttons">
                                    <a href="holiday_form.php?id=<?php echo $holiday['id']; ?>" class="button button-edit">แก้ไข</a>
                                    <a href="delete_holiday.php?id=<?php echo $holiday['id']; ?>" class="button button-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะลบวันสำคัญนี้?');">ลบ</a>
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
            const holidayName = row.querySelector('td[data-label="ชื่อวันสำคัญ"]').textContent.toLowerCase();
            row.style.display = holidayName.includes(searchTerm) ? '' : 'none';
        });
        monthHeaders.forEach(header => {
            let nextRow = header.nextElementSibling, hasVisibleHolidays = false;
            while (nextRow && nextRow.classList.contains('holiday-row')) { if (nextRow.style.display !== 'none') { hasVisibleHolidays = true; break; } nextRow = nextRow.nextElementSibling; }
            header.style.display = hasVisibleHolidays ? '' : 'none';
        });
    });
});
</script>

<?php require_once 'partials/footer.php'; ?>

