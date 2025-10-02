<?php
// File: backup.php
// Location: /admin/
$page_title = 'สำรองข้อมูลกิจกรรม';
require_once 'partials/header.php';
require_once '../config.php';

// Security Check: Only admins can access this page
if (!is_admin()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: dashboard.php");
    exit;
}

$events_for_export = [];
try {
    // Modified query to sort correctly for Thai language
    $stmt = $pdo->query("SELECT id, event_name FROM events ORDER BY event_name COLLATE utf8mb4_thai_520_w2 ASC");
    $events_for_export = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='alert alert-danger'>Could not fetch events from the database: " . $e->getMessage() . "</p>";
}

$new_events_to_import = [];
$skipped_events = [];
$import_summary = $_SESSION['import_summary'] ?? null;
$import_error = $_SESSION['import_error'] ?? null;
unset($_SESSION['import_summary'], $_SESSION['import_error']);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file_preview']) && $_FILES['csv_file_preview']['error'] == 0) {
    unset($_SESSION['import_preview_data']);
    
    $filename = $_FILES['csv_file_preview']['tmp_name'];
    if (($handle = fopen($filename, 'r')) !== FALSE) {
        fgetcsv($handle); 
        while (($data = fgetcsv($handle)) !== FALSE) {
            $event_name = trim($data[0] ?? '');
            if(empty($event_name)) continue;

            $event_data = [
                'event_name' => $event_name,
                'responsible_unit' => trim($data[1] ?? ''),
                'notes' => trim($data[2] ?? ''),
                'dates' => trim($data[3] ?? '')
            ];

            $stmt_check = $pdo->prepare("SELECT id FROM events WHERE event_name = ?");
            $stmt_check->execute([$event_name]);
            if ($stmt_check->fetch()) {
                $skipped_events[] = $event_data;
            } else {
                $new_events_to_import[] = $event_data;
            }
        }
        fclose($handle);
        
        if (!empty($new_events_to_import)) {
            $_SESSION['import_preview_data'] = $new_events_to_import;
        }
    }
}
?>

<div class="backup-container">
    <!-- EXPORT SECTION -->
    <div class="backup-card">
        <h3><i class="fas fa-file-export"></i> Export กิจกรรมเป็นไฟล์ CSV</h3>
        <p>เลือกกิจกรรมที่ต้องการ Export หรือเลือกทั้งหมดเพื่อสำรองข้อมูล</p>
        <form action="export_csv.php" method="POST" id="exportForm">
            <div class="event-checklist-container">
                <div class="checklist-controls">
                    <button type="button" id="selectAllBtn" class="button button-secondary">เลือกทั้งหมด</button>
                    <button type="button" id="deselectAllBtn" class="button button-secondary">ไม่เลือกทั้งหมด</button>
                </div>
                <div class="event-checklist">
                    <?php if (empty($events_for_export)): ?>
                        <p>ไม่มีกิจกรรมให้เลือก</p>
                    <?php else: ?>
                        <?php foreach ($events_for_export as $event): ?>
                            <label class="checklist-item"><input type="checkbox" name="event_ids[]" value="<?php echo $event['id']; ?>"> <?php echo htmlspecialchars($event['event_name']); ?></label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-actions"><button type="submit" class="button" <?php echo empty($events_for_export) ? 'disabled' : ''; ?>>Export กิจกรรมที่เลือก</button></div>
        </form>
    </div>

    <!-- IMPORT SECTION -->
    <div class="backup-card">
        <h3><i class="fas fa-file-import"></i> Import กิจกรรมจากไฟล์ CSV</h3>
        <p><b>ขั้นตอนที่ 1:</b> เลือกไฟล์ CSV (UTF-8) เพื่อแสดงตัวอย่างข้อมูลก่อนนำเข้า</p>
        <?php 
        if ($import_summary) {
            echo "<div class='alert alert-success'><strong>สรุปผลการ Import ล่าสุด:</strong><br><ul>";
            foreach($import_summary['logs'] as $log) { echo "<li>" . htmlspecialchars($log) . "</li>"; }
            echo "</ul>";
            if(!empty($import_summary['errors'])) {
                echo "<strong>ข้อผิดพลาด:</strong><br><ul>";
                 foreach($import_summary['errors'] as $error) { echo "<li class='log-error'>" . htmlspecialchars($error) . "</li>"; }
                echo "</ul>";
            }
            echo "</div>";
        }
        if ($import_error) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($import_error) . '</div>';
        }
        ?>
        <form action="backup.php" method="POST" enctype="multipart/form-data">
            <div class="form-group"><label for="csv_file_preview">เลือกไฟล์ CSV</label><input type="file" id="csv_file_preview" name="csv_file_preview" accept=".csv" required></div>
            <div class="form-actions"><button type="submit" class="button">แสดงตัวอย่างข้อมูล</button></div>
        </form>
    </div>
</div>

<!-- IMPORT PREVIEW SECTION -->
<?php if (!empty($new_events_to_import) || !empty($skipped_events)): ?>
<div class="backup-container" style="margin-top: 2rem;">
    <div class="backup-card import-preview-card">
        <h3><i class="fas fa-tasks"></i> ขั้นตอนที่ 2: ยืนยันการนำเข้าข้อมูล</h3>
        <p>ตรวจสอบและเลือกกิจกรรมที่ต้องการนำเข้าสู่ระบบ</p>
        <form action="import_process.php" method="POST">
            <div class="event-checklist-container">
                <h4><i class="fas fa-plus-circle success-icon"></i> กิจกรรมใหม่ (ที่พร้อมนำเข้า)</h4>
                <div class="event-checklist">
                    <?php if (empty($new_events_to_import)): ?>
                        <p>ไม่พบกิจกรรมใหม่ในไฟล์นี้</p>
                    <?php else: ?>
                        <?php foreach ($new_events_to_import as $index => $event): ?>
                            <label class="checklist-item">
                                <input type="checkbox" name="selected_indices[]" value="<?php echo $index; ?>" checked>
                                <span class="import-preview-name"><?php echo htmlspecialchars($event['event_name']); ?></span>
                                <span class="import-preview-dates">วันที่: <?php echo htmlspecialchars($event['dates']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($skipped_events)): ?>
                    <h4 style="margin-top: 1.5rem;"><i class="fas fa-exclamation-triangle warning-icon"></i> กิจกรรมที่พบซ้ำ (จะถูกข้ามอัตโนมัติ)</h4>
                    <div class="event-checklist skipped-list">
                        <?php foreach ($skipped_events as $event): ?>
                            <div class="checklist-item-skipped"><?php echo htmlspecialchars($event['event_name']); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="button" <?php echo empty($new_events_to_import) ? 'disabled' : ''; ?>><i class="fas fa-check"></i> Import กิจกรรมที่เลือก</button>
                <a href="backup.php" class="button button-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="assets/backup_script.js"></script>
<?php require_once 'partials/footer.php'; ?>

