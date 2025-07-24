<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

// In a real application, you would fetch rental requests from a dedicated table.
// For now, we will simulate this by fetching notifications related to rentals.
$sql = "SELECT n.*, u.username
        FROM notifications n
        JOIN users u ON n.user_id = u.id
        WHERE n.message LIKE '%New rental request%'
        ORDER BY n.created_at DESC";

$rental_requests = mysqli_fetch_all(mysqli_query($link, $sql), MYSQLI_ASSOC);

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>مدیریت درخواست‌های کرایه</h2>
    <p>در این بخش می‌توانید درخواست‌های ثبت شده برای کرایه لوازم را مشاهده و مدیریت کنید.</p>

    <div class="table-container">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>کاربر درخواست دهنده</th>
                    <th>جزئیات درخواست</th>
                    <th>تاریخ ثبت</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rental_requests)): ?>
                    <tr><td colspan="4" class="text-center">هیچ درخواست کرایه‌ای ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($rental_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['username']); ?></td>
                            <td><?php echo htmlspecialchars($request['message']); ?></td>
                            <td><?php echo to_persian_date($request['created_at']); ?></td>
                            <td>
                                <span class="badge <?php echo $request['is_read'] ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $request['is_read'] ? 'خوانده شده' : 'جدید'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
