<?php
require_once 'dashboard_header.php';
require_once '../app/controllers/CompetitorController.php';

$controller = new CompetitorController();

// Handle Delete Request
if (isset($_POST['delete_id'])) {
    $controller->deleteCompetitor($_POST['delete_id']);
    // Redirect to avoid form resubmission
    header("Location: manage_competitors.php");
    exit;
}

$competitors = $controller->getAllCompetitors();

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">مدیریت رقبا</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="search_competitor.php" class="btn btn-sm btn-outline-primary me-2">افزودن با جستجوی هوشمند</a>
        <a href="add_competitor.php" class="btn btn-sm btn-outline-secondary">افزودن دستی</a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">نام</th>
                <th scope="col">وب سایت</th>
                <th scope="col">اینستاگرام</th>
                <th scope="col">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($competitors as $index => $competitor): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($competitor['name']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($competitor['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($competitor['website']); ?></a></td>
                    <td><a href="<?php echo htmlspecialchars($competitor['instagram']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($competitor['instagram']); ?></a></td>
                    <td>
                        <form action="manage_competitors.php" method="post" class="d-inline">
                            <input type="hidden" name="delete_id" value="<?php echo $competitor['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این رقیب مطمئن هستید؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'dashboard_footer.php'; ?>
