<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /dabestan/index.php");
    exit;
}
if (!function_exists('has_permission')) {
    require_once __DIR__ . "/access_control.php";
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سامانه دبستان</title>
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <link rel="stylesheet" href="/dabestan/assets/css/style.css">
    <?php
    if (strpos($_SERVER['REQUEST_URI'], '/user/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        echo '<link rel="stylesheet" href="/dabestan/assets/css/dashboard.css">';
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>دبستان</h3>
        </div>
        <ul class="nav-links">
            <li><a href="/dabestan/user/index.php"><i data-feather="home"></i><span>داشبورد</span></a></li>

            <li class="nav-section-title"><span>ویژه مدرس</span></li>
            <li><a href="/dabestan/user/my_classes.php"><i data-feather="book-open"></i><span>کلاس‌های من</span></a></li>
            <li><a href="/dabestan/user/list_forms.php"><i data-feather="file-text"></i><span>فرم‌های خوداظهاری</span></a></li>

            <li class="nav-section-title"><span>ارتباطات</span></li>
             <li class="has-submenu">
                <a href="#"><i data-feather="message-square"></i><span>تیکت‌ها</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/user/new_ticket.php"><span>ایجاد تیکت</span></a></li>
                    <li><a href="/dabestan/user/my_tickets.php"><span>تیکت‌های من</span></a></li>
                </ul>
            </li>
            <li class="has-submenu">
                <a href="#"><i data-feather="briefcase"></i><span>وظایف</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                     <li><a href="/dabestan/user/my_tasks.php"><span>وظایف من</span></a></li>
                </ul>
            </li>


            <?php if(has_permission('view_department_menu')): ?>
            <li class="nav-section-title"><span>بخش‌های سازمانی</span></li>
            <li class="has-submenu">
                <a href="#"><i data-feather="eye"></i><span>نظارت</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/admin/view_all_assessments.php"><span>مشاهده خوداظهاری‌ها</span></a></li>
                </ul>
            </li>
            <li class="has-submenu">
                <a href="#"><i data-feather="gift"></i><span>پرورشی</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/user/class_event_reports.php"><span>گزارش خدمت‌گزاری</span></a></li>
                    <li><a href="/dabestan/user/rental_items.php"><span>کرایه‌چی</span></a></li>
                </ul>
            </li>
            <li class="has-submenu">
                <a href="#"><i data-feather="users"></i><span>اولیا</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                     <li><a href="/dabestan/user/manage_parent_meetings.php"><span>جلسات اولیا</span></a></li>
                </ul>
            </li>
             <li class="has-submenu">
                <a href="#"><i data-feather="award"></i><span>امید تدریس</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                     <li><a href="#"><span>گزارش جلسات</span></a></li>
                     <li><a href="#"><span>فرم حضور و غیاب</span></a></li>
                </ul>
            </li>
             <li class="has-submenu">
                <a href="#"><i data-feather="dollar-sign"></i><span>مالی</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/user/my_financial_status.php"><span>وضعیت حساب من</span></a></li>
                </ul>
            </li>
            <?php endif; ?>


            <?php if(has_permission('manage_users')): ?>
            <li class="nav-section-title"><span>مدیریت سیستم</span></li>
            <li class="has-submenu">
                <a href="#"><i data-feather="settings"></i><span>پیکربندی</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/admin/manage_users.php"><span>کاربران</span></a></li>
                    <li><a href="/dabestan/admin/manage_roles.php"><span>نقش‌ها</span></a></li>
                    <li><a href="/dabestan/admin/manage_departments.php"><span>بخش‌ها</span></a></li>
                    <li><a href="/dabestan/admin/manage_classes.php"><span>کلاس‌ها</span></a></li>
                </ul>
            </li>
             <li class="has-submenu">
                <a href="#"><i data-feather="database"></i><span>محتوا</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/admin/manage_forms.php"><span>فرم‌ها</span></a></li>
                    <li><a href="/dabestan/admin/manage_general_events.php"><span>رویدادهای عمومی</span></a></li>
                    <li><a href="/dabestan/admin/manage_regions.php"><span>مناطق جذب</span></a></li>
                </ul>
            </li>
             <li class="has-submenu">
                <a href="#"><i data-feather="tool"></i><span>ابزارها</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/admin/manage_tasks.php"><span>مدیریت وظایف</span></a></li>
                </ul>
            </li>
             <li class="has-submenu">
                <a href="#"><i data-feather="archive"></i><span>انبار و مالی</span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/admin/manage_categories.php"><span>دسته‌بندی انبار</span></a></li>
                    <li><a href="/dabestan/admin/manage_inventory.php"><span>موجودی انبار</span></a></li>
                    <li><a href="/dabestan/admin/manage_booklets.php"><span>جزوات</span></a></li>
                     <li><a href="/dabestan/user/financial_transactions.php"><span>تراکنش‌های مالی</span></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <li class="nav-section-title"><span>حساب کاربری</span></li>
            <li class="has-submenu">
                <a href="#"><i data-feather="user"></i><span><?php echo htmlspecialchars($_SESSION["username"]); ?></span><i class="submenu-arrow" data-feather="chevron-left"></i></a>
                <ul class="submenu">
                    <li><a href="/dabestan/user/my_settings.php"><span>تنظیمات پروفایل</span></a></li>
                    <li><a href="/dabestan/logout.php"><span>خروج</span></a></li>
                </ul>
            </li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <button class="menu-toggle" id="menu-toggle"><i data-feather="menu"></i></button>
            <div id="datetime">
                <span id="date"></span>
                <span id="time"></span>
            </div>
            <div class="header-right">
                <div class="header-notifications">
                    <div class="notification-icon" id="notification-icon">
                        <i data-feather="bell"></i>
                        <span class="notification-count" id="notification-count"></span>
                    </div>
                    <div class="notification-dropdown" id="notification-dropdown">
                        <div class="notification-header">اعلان‌ها</div>
                        <div id="notification-list"></div>
                        <div class="notification-footer">
                            <a href="/dabestan/user/view_all_notifications.php">مشاهده همه</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <main>
            <!-- Page content will be loaded here -->
