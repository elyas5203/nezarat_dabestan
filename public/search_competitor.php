<?php
require_once '../app/controllers/CompetitorController.php';

$results = null;
$competitorName = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['competitor_name'])) {
    $competitorName = $_POST['competitor_name'];
    $controller = new CompetitorController();
    $results = $controller->searchCompetitor($competitorName);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جستجوی هوشمند رقیب</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>جستجوی هوشمند رقیب</h1>
    <p>نام رقیب مورد نظر را وارد کنید تا دستیار هوشمند به دنبال وب سایت و صفحه اینستاگرام او بگردد.</p>

    <form action="search_competitor.php" method="post">
        <label for="competitor_name">نام رقیب:</label><br>
        <input type="text" id="competitor_name" name="competitor_name" value="<?php echo htmlspecialchars($competitorName); ?>" required><br><br>
        <input type="submit" value="جستجو">
    </form>

    <?php if ($results): ?>
        <h2>نتایج جستجو برای "<?php echo htmlspecialchars($competitorName); ?>"</h2>
        <p><strong>وب سایت:</strong> <?php echo htmlspecialchars($results['website'] ?? 'پیدا نشد'); ?></p>
        <p><strong>اینستاگرام:</strong> <?php echo htmlspecialchars($results['instagram'] ?? 'پیدا نشد'); ?></p>

        <hr>
        <h3>آیا این اطلاعات صحیح است؟</h3>
        <p>در صورت تایید، این رقیب به لیست شما اضافه خواهد شد.</p>
        <form action="add_competitor.php" method="post">
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($competitorName); ?>">
            <input type="hidden" name="website" value="<?php echo htmlspecialchars($results['website'] ?? ''); ?>">
            <input type="hidden" name="instagram" value="<?php echo htmlspecialchars($results['instagram'] ?? ''); ?>">
            <input type="submit" value="بله، اضافه کن">
        </form>
    <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <p>متاسفانه نتیجه ای برای "<?php echo htmlspecialchars($competitorName); ?>" پیدا نشد. لطفا نام دیگری را امتحان کنید یا اطلاعات را به صورت دستی در <a href="add_competitor.php">صفحه افزودن رقیب</a> وارد کنید.</p>
    <?php endif; ?>

</body>
</html>
