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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">تولید محتوا با هوش مصنوعی</h1>
</div>
<p>از دستیار هوشمند خود بخواهید تا بر اساس تحلیل های انجام شده و نیاز شما، محتوای خلاقانه تولید کند.</p>

<div class="card">
    <div class="card-body">
        <form action="content_generation.php" method="post">
            <div class="mb-3">
                <label for="prompt" class="form-label">چه نوع محتوایی نیاز دارید؟</label>
                <textarea class="form-control" id="prompt" name="prompt" rows="3" placeholder="مثال: یک کپشن برای اینستاگرام در مورد دفترهای جدیدمان بنویس" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">تولید کن</button>
        </form>
    </div>
</div>

<?php if (!empty($generated_content)): ?>
<div class="card mt-4">
    <div class="card-header">
        محتوای تولید شده
    </div>
    <div class="card-body">
        <pre style="white-space: pre-wrap; font-family: inherit;"><?php echo htmlspecialchars($generated_content); ?></pre>
    </div>
</div>
<?php endif; ?>

<?php require_once 'dashboard_footer.php'; ?>
