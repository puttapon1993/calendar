<?php
$page_title = 'จัดการกิจกรรม';
require_once 'partials/header.php';
require_once '../config.php';

// --- Sorting Logic ---
$sort_order = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';
$sort_param = $sort_order === 'ASC' ? 'asc' : 'desc';

// --- Helper Functions ---
function format_event_dates($dates_str) {
    if (empty($dates_str)) return 'N/A';
    
    $dates = explode(',', $dates_str);
    sort($dates); // Ensure dates are sorted chronologically
    
    $date_objects = array_map(fn($d) => new DateTime($d), $dates);
    
    $thai_day_short = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
    $thai_month_short = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];

    if (count($date_objects) == 1) {
        $date = $date_objects[0];
        $day_name = $thai_day_short[$date->format('w')];
        return "({$day_name}) " . $date->format('j') . ' ' . $thai_month_short[(int)$date->format('n')] . ' ' . ($date->format('Y') + 543);
    }

    $is_continuous = true;
    for ($i = 0; $i < count($date_objects) - 1; $i++) {
        if ($date_objects[$i+1]->diff($date_objects[$i])->days != 1) {
            $is_continuous = false;
            break;
        }
    }

    if ($is_continuous) {
        $start_date = $date_objects[0];
        $end_date = end($date_objects);
        $start_day_name = $thai_day_short[$start_date->format('w')];
        $end_day_name = $thai_day_short[$end_date->format('w')];
        return "({$start_day_name}) " . $start_date->format('j') . " - ({$end_day_name}) " . $end_date->format('j') . ' ' . $thai_month_short[(int)$end_date->format('n')] . ' ' . ($end_date->format('Y') + 543);
    } else {
        $output = [];
        foreach ($date_objects as $date) {
            $day_name = $thai_day_short[$date->format('w')];
            $output[] = "({$day_name}) " . $date->format('j');
        }
        $last_date = end($date_objects);
        return implode(', ', $output) . ' ' . $thai_month_short[(int)$last_date->format('n')] . ' ' . ($last_date->format('Y') + 543);
    }
}

try {
    // Fetch all events with their corresponding dates, ordered by the earliest date of each event
    $stmt = $pdo->prepare("
        SELECT 
            e.*, 
            GROUP_CONCAT(ed.activity_date ORDER BY ed.activity_date) as all_dates,
            MIN(ed.activity_date) as first_date
        FROM events e
        LEFT JOIN event_dates ed ON e.id = ed.event_id
        GROUP BY e.id
        HAVING first_date IS NOT NULL
        ORDER BY first_date {$sort_order}
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group events by month-year
    $grouped_events = [];
    $thai_month_full = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    foreach ($events as $event) {
        $date = new DateTime($event['first_date']);
        $month_key = $date->format('Y-m');
        $month_label = $thai_month_full[(int)$date->format('n')] . ' ' . ($date->format('Y') + 543);
        
        if (!isset($grouped_events[$month_key])) {
            $grouped_events[$month_key] = [
                'label' => $month_label,
                'events' => []
            ];
        }
        $grouped_events[$month_key]['events'][] = $event;
    }

} catch (PDOException $e) {
    $grouped_events = [];
    echo "<p class='error'>Could not fetch events from the database: " . $e->getMessage() . "</p>";
}
?>

<div class="toolbar-grid">
    <a href="event_form.php" class="button"> + เพิ่มกิจกรรมใหม่</a>
    
    <div class="search-sort-container">
        <div class="sort-controls">
            <span>เรียงลำดับ:</span>
            <a href="?sort=desc" class="<?php echo $sort_param === 'desc' ? 'active' : ''; ?>">ใหม่ไปเก่า</a>
            <a href="?sort=asc" class="<?php echo $sort_param === 'asc' ? 'active' : ''; ?>">เก่าไปใหม่</a>
        </div>
        <input type="text" id="searchInput" placeholder="ค้นหาชื่อกิจกรรม, หน่วยงาน...">
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
    <table class="data-table" id="eventsTable">
        <thead>
            <tr>
                <th>ชื่อกิจกรรม</th>
                <th>หน่วยงานที่รับผิดชอบ</th>
                <th>วันที่จัดกิจกรรม</th>
                <th>สถานะ</th>
                <th>เครื่องมือ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($grouped_events)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">ยังไม่มีข้อมูลกิจกรรม...</td>
                </tr>
            <?php else: ?>
                <?php foreach ($grouped_events as $group): ?>
                    <tr class="month-header">
                        <td colspan="5"><?php echo $group['label']; ?></td>
                    </tr>
                    <?php foreach ($group['events'] as $event): ?>
                        <tr class="event-row">
                            <td data-label="ชื่อกิจกรรม"><?php echo htmlspecialchars($event['event_name']); ?></td>
                            <td data-label="หน่วยงาน"><?php echo htmlspecialchars($event['responsible_unit']); ?></td>
                            <td data-label="วันที่"><?php echo format_event_dates($event['all_dates']); ?></td>
                            <td data-label="สถานะ">
                                <a href="toggle_event_status.php?id=<?php echo $event['id']; ?>" class="status-badge <?php echo $event['is_hidden'] ? 'status-hidden' : 'status-visible'; ?>">
                                    <?php echo $event['is_hidden'] ? 'ซ่อน' : 'แสดง'; ?>
                                </a>
                            </td>
                            <td data-label="เครื่องมือ">
                                <div class="action-buttons">
                                    <a href="event_form.php?id=<?php echo $event['id']; ?>" class="button button-edit">แก้ไข</a>
                                    <a href="delete_event.php?id=<?php echo $event['id']; ?>" class="button button-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะลบกิจกรรมนี้? การกระทำนี้ไม่สามารถย้อนกลับได้');">ลบ</a>
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
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('eventsTable');
    const rows = table.querySelectorAll('tbody tr.event-row');
    const monthHeaders = table.querySelectorAll('tbody tr.month-header');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = searchInput.value.toLowerCase();

        rows.forEach(row => {
            const eventName = row.querySelector('td[data-label="ชื่อกิจกรรม"]').textContent.toLowerCase();
            const responsibleUnit = row.querySelector('td[data-label="หน่วยงาน"]').textContent.toLowerCase();

            if (eventName.includes(searchTerm) || responsibleUnit.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update visibility of month headers
        monthHeaders.forEach(header => {
            let nextRow = header.nextElementSibling;
            let hasVisibleEvents = false;
            while (nextRow && nextRow.classList.contains('event-row')) {
                if (nextRow.style.display !== 'none') {
                    hasVisibleEvents = true;
                    break;
                }
                nextRow = nextRow.nextElementSibling;
            }
            header.style.display = hasVisibleEvents ? '' : 'none';
        });
    });
});
</script>

<?php
require_once 'partials/footer.php';
?>

