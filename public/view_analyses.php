<?php
require_once 'dashboard_header.php';
require_once '../app/controllers/AnalysisController.php';

$controller = new AnalysisController();
$analyses = $controller->getAllAnalyses();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">مشاهده تحلیل ها</h1>
    <a href="cron_fetch.php" target="_blank" class="btn btn-sm btn-outline-success">شروع تحلیل جدید</a>
</div>
<p>در این بخش می توانید آخرین تحلیل های انجام شده روی محتوay رقبای خود را مشاهده کنید.</p>

<?php if (empty($analyses)): ?>
    <div class="alert alert-info" role="alert">
        هنوز هیچ تحلیلی انجام نشده است. برای شروع، از بخش <a href="manage_competitors.php" class="alert-link">مدیریت رقبا</a>، رقیب اضافه کرده و سپس دکمه "شروع تحلیل جدید" را بزنید.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($analyses as $analysis): ?>
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        تحلیل برای: <strong><?php echo htmlspecialchars($analysis['competitor_name']); ?></strong>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">خلاصه تحلیل</h5>
                        <p class="card-text"><?php echo htmlspecialchars($analysis['analysis_summary']); ?></p>

                        <h6>کلمات کلیدی شناسایی شده:</h6>
                        <p>
                            <?php
                            $keywords = json_decode($analysis['keywords']);
                            if ($keywords) {
                                foreach ($keywords as $keyword) {
                                    echo '<span class="badge bg-primary me-1">' . htmlspecialchars($keyword) . '</span>';
                                }
                            } else {
                                echo '<span class="badge bg-secondary">موردی یافت نشد</span>';
                            }
                            ?>
                        </p>

                        <h6>محصولات تبلیغ شده:</h6>
                        <p>
                            <?php
                            $products = json_decode($analysis['products_promoted']);
                            if ($products) {
                                foreach ($products as $product) {
                                    echo '<span class="badge bg-info me-1">' . htmlspecialchars($product) . '</span>';
                                }
                            } else {
                                echo '<span class="badge bg-secondary">موردی یافت نشد</span>';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="card-footer text-muted">
                        نوع محتوا: <?php echo htmlspecialchars($analysis['content_type']); ?> | تاریخ: <?php echo $analysis['created_at']; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php require_once 'dashboard_footer.php'; ?>
