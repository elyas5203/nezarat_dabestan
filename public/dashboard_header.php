<?php
// Session start, authentication checks, etc. would go here in a real app.
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet" integrity="sha384-dpuaG1suU0eT09BIGj,vPGKPAIe2ABeR6voUVEAcoBd/l2CUEmmInyTfAkKAGAD" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap');
        body {
            font-family: 'Vazirmatn', sans-serif;
        }
        .sidebar {
            width: 280px;
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: #c7c7c7;
            font-size: 1.1rem;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: #4a4a4a;
        }
        .sidebar .nav-link.active {
            color: #fff;
            font-weight: 500;
        }
        .content {
            min-width: 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="d-flex">
        <div class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white bg-dark">
            <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <span class="fs-4">AresAI Dashboard</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">داشبورد</a>
                </li>
                <li>
                    <a href="manage_competitors.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_competitors.php' ? 'active' : ''; ?>">مدیریت رقبا</a>
                </li>
                <li>
                    <a href="view_analyses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_analyses.php' ? 'active' : ''; ?>">مشاهده تحلیل ها</a>
                </li>
                <li>
                    <a href="content_generation.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'content_generation.php' ? 'active' : ''; ?>">تولید محتوا</a>
                </li>
                <li>
                    <a href="consultant_chat.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'consultant_chat.php' ? 'active' : ''; ?>">مشاوره</a>
                </li>
            </ul>
        </div>
        <div class="content flex-grow-1 p-4">
