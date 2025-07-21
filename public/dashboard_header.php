<?php
// Session start, authentication checks, etc. would go here in a real app.
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { padding: 0; margin: 0; }
        .dashboard-container { display: flex; }
        .sidebar { width: 250px; background: #333; color: white; min-height: 100vh; padding: 20px; }
        .sidebar h2 { text-align: center; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li a { display: block; padding: 10px; color: white; text-decoration: none; border-bottom: 1px solid #444; }
        .sidebar ul li a:hover { background: #555; }
        .main-content { flex-grow: 1; padding: 20px; background: #f4f4f4; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>AresAI</h2>
            <ul>
                <li><a href="dashboard.php">داشبورد</a></li>
                <li><a href="manage_competitors.php">مدیریت رقبا</a></li>
                <li><a href="view_analyses.php">مشاهده تحلیل ها</a></li>
                <li><a href="content_generation.php">تولید محتوا</a></li>
                <li><a href="consultant_chat.php">مشاوره</a></li>
            </ul>
        </div>
        <div class="main-content">
