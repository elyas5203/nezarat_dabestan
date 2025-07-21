<?php
require_once 'dashboard_header.php';
require_once '../app/controllers/AnalysisController.php';

$controller = new AnalysisController();
$analyses = $controller->getAllAnalyses();
?>

<h1>مشاهده تحلیل ها</h1>
<p>در این بخش می توانید آخرین تحلیل های انجام شده روی محتوای رقبای خود را مشاهده کنید.</p>

<style>
    .analysis-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .analysis-card h3 { margin: 0 0 10px; }
    .analysis-card .meta { font-size: 0.9em; color: #666; margin-bottom: 10px; }
    .badge { background-color: #eee; padding: 3px 8px; border-radius: 10px; font-size: 0.8em; margin-left: 5px; }
</style>

<?php if (empty($analyses)): ?>
    <p>هنوز هیچ تحلیلی انجام نشده است. برای شروع، از بخش مدیریت رقبا، رقیب اضافه کرده و سپس اسکریپت <a href="cron_fetch.php" target="_blank">جمع آوری و تحلیل داده</a> را اجرا کنید.</p>
<?php else: ?>
    <?php foreach ($analyses as $analysis): ?>
        <div class="analysis-card">
            <h3>تحلیل برای: <?php echo htmlspecialchars($analysis['competitor_name']); ?></h3>
            <div class="meta">
                <span>نوع محتوا: <?php echo htmlspecialchars($analysis['content_type']); ?></span> |
                <span>تاریخ: <?php echo $analysis['created_at']; ?></span>
            </div>
            <h4>خلاصه تحلیل:</h4>
            <p><?php echo htmlspecialchars($analysis['analysis_summary']); ?></p>

            <h4>کلمات کلیدی شناسایی شده:</h4>
            <p>
                <?php
                $keywords = json_decode($analysis['keywords']);
                if ($keywords) {
                    foreach ($keywords as $keyword) {
                        echo '<span class="badge">' . htmlspecialchars($keyword) . '</span>';
                    }
                }
                ?>
            </p>

            <h4>محصولات تبلیغ شده:</h4>
            <p>
                <?php
                $products = json_decode($analysis['products_promoted']);
                if ($products) {
                    foreach ($products as $product) {
                        echo '<span class="badge">' . htmlspecialchars($product) . '</span>';
                    }
                }
                ?>
            </p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>


<?php require_once 'dashboard_footer.php'; ?>
