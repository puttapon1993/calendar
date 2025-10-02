<?php
// Ensure session is started at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login page if user is not logged in
if (!isset($_SESSION['admin_loggedin'])) {
    header('Location: login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Admin Panel'; ?> - ปฏิทินกิจกรรม</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin_style.css?v=<?php echo filemtime('assets/admin_style.css'); ?>">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <h2><i class="fas fa-calendar-check"></i> <span>ปฏิทินกิจกรรม</span></h2>
            <button id="sidebar-toggle" class="sidebar-toggle" title="ย่อ/ขยายเมนู"><i class="fas fa-bars"></i></button>
            <nav class="admin-nav">
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="events.php" class="<?php echo ($current_page == 'events.php' || $current_page == 'event_form.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> <span>จัดการกิจกรรม</span></a>
                <a href="holidays.php" class="<?php echo ($current_page == 'holidays.php' || $current_page == 'holiday_form.php') ? 'active' : ''; ?>"><i class="fas fa-star"></i> <span>จัดการวันหยุดพิเศษ</span></a>
                <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-flag"></i> <span>ข้อความแจ้งปัญหา</span></a>
                <a href="backup.php" class="<?php echo ($current_page == 'backup.php') ? 'active' : ''; ?>"><i class="fas fa-database"></i> <span>สำรองข้อมูล</span></a>
                <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> <span>ตั้งค่าเว็บไซต์</span></a>
            </nav>
            <div class="user-info">
                <p><i class="fas fa-user"></i> <span><strong>ผู้ใช้:</strong> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span></p>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>ออกจากระบบ</span></a>
            </div>
        </aside>
        <main class="main-content">
            <header class="main-header">
                <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Admin Panel'; ?></h1>
            </header>
            <div class="content-area">

