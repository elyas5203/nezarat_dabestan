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

<h1>مدیریت رقبا</h1>
<a href="search_competitor.php" class="button">افزودن رقیب جدید (جستجوی هوشمند)</a>
<a href="add_competitor.php" class="button">افزودن رقیب جدید (دستی)</a>

<style>
    .button { display: inline-block; padding: 10px 15px; background: #5cb85c; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
    th { background-color: #f2f2f2; }
    .delete-button { background: #d9534f; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
</style>

<table>
    <thead>
        <tr>
            <th>نام</th>
            <th>وب سایت</th>
            <th>اینستاگرام</th>
            <th>عملیات</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($competitors as $competitor): ?>
            <tr>
                <td><?php echo htmlspecialchars($competitor['name']); ?></td>
                <td><a href="<?php echo htmlspecialchars($competitor['website']); ?>" target="_blank"><?php echo htmlspecialchars($competitor['website']); ?></a></td>
                <td><a href="<?php echo htmlspecialchars($competitor['instagram']); ?>" target="_blank"><?php echo htmlspecialchars($competitor['instagram']); ?></a></td>
                <td>
                    <form action="manage_competitors.php" method="post" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $competitor['id']; ?>">
                        <button type="submit" class="delete-button" onclick="return confirm('آیا از حذف این رقیب مطمئن هستید؟')">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'dashboard_footer.php'; ?>
