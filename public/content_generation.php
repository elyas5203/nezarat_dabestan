<?php
require_once 'dashboard_header.php';
require_once '../app/controllers/AnalysisController.php';

$generated_content = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['prompt'])) {
    $controller = new AnalysisController();
    $prompt = $_POST['prompt'];
    // This is a placeholder for a more sophisticated content generation method
    $generated_content = $controller->generateGenericContent($prompt);
}
?>

<h1>تولید محتوا با هوش مصنوعی</h1>
<p>از دستیار هوشمند خود بخواهید تا بر اساس تحلیل های انجام شده و نیاز شما، محتوای خلاقانه تولید کند.</p>

<form action="content_generation.php" method="post">
    <label for="prompt">چه نوع محتوایی نیاز دارید؟ (مثال: یک کپشن برای اینستاگرام در مورد دفترهای جدیدمان بنویس)</label><br>
    <textarea id="prompt" name="prompt" rows="4" style="width: 100%;" required></textarea><br><br>
    <input type="submit" value="تولید کن">
</form>

<?php if (!empty($generated_content)): ?>
    <h2>محتوای تولید شده:</h2>
    <div class="generated-content-box">
        <pre><?php echo htmlspecialchars($generated_content); ?></pre>
    </div>
<?php endif; ?>

<style>
    .generated-content-box { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px; white-space: pre-wrap; font-family: inherit; }
    textarea { padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
</style>

<?php require_once 'dashboard_footer.php'; ?>
