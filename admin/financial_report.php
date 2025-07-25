<?php
session_start();
require_once "../includes/db_singleton.php";
require_once "../includes/functions.php";
require_once "../includes/jdf.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !is_admin()) {
    header("location: ../index.php");
    exit;
}

$link = get_db_connection();

// --- Overall Financial Stats ---
$sql_stats = "
    SELECT
        (SELECT SUM(amount) FROM booklet_transactions WHERE transaction_type = 'debit') as total_debit,
        (SELECT SUM(amount) FROM booklet_transactions WHERE transaction_type = 'credit') as total_credit,
        (SELECT COUNT(*) FROM booklet_transactions) as total_transactions
";
$stats_result = mysqli_query($link, $sql_stats);
$stats = mysqli_fetch_assoc($stats_result);
$total_balance = ($stats['total_credit'] ?? 0) - ($stats['total_debit'] ?? 0);


// --- User Balances ---
$sql_user_balances = "
    SELECT
        u.id,
        u.username,
        u.first_name,
        u.last_name,
        SUM(CASE WHEN bt.transaction_type = 'debit' THEN bt.amount ELSE 0 END) as total_debit,
        SUM(CASE WHEN bt.transaction_type = 'credit' THEN bt.amount ELSE 0 END) as total_credit,
        (SUM(CASE WHEN bt.transaction_type = 'credit' THEN bt.amount ELSE 0 END) - SUM(CASE WHEN bt.transaction_type = 'debit' THEN bt.amount ELSE 0 END)) as balance
    FROM users u
    LEFT JOIN booklet_transactions bt ON u.id = bt.user_id
    GROUP BY u.id
    HAVING balance != 0
    ORDER BY balance ASC
";
$user_balances_result = mysqli_query($link, $sql_user_balances);


require_once "../includes/header.php";
?>
<style>
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: var(--shadow-sm); text-align: center; }
    .stat-box h4 { margin: 0 0 10px 0; color: #6c757d; font-size: 1rem; }
    .stat-box .amount { font-size: 1.8rem; font-weight: bold; }
    .positive { color: #28a745; }
    .negative { color: #dc3545; }
</style>

<div class="page-content">
    <h2>گزارش کلی مالی</h2>
    <p>این صفحه یک نمای کلی از وضعیت مالی سیستم ارائه می‌دهد.</p>

    <div class="stat-grid">
        <div class="stat-box">
            <h4>کل بدهی‌ها (فروش جزوه)</h4>
            <div class="amount negative"><?php echo number_format($stats['total_debit'] ?? 0); ?> تومان</div>
        </div>
        <div class="stat-box">
            <h4>کل واریزی‌ها</h4>
            <div class="amount positive"><?php echo number_format($stats['total_credit'] ?? 0); ?> تومان</div>
        </div>
        <div class="stat-box">
            <h4>مانده کل</h4>
            <div class="amount <?php echo $total_balance >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo number_format($total_balance); ?> تومان
            </div>
        </div>
        <div class="stat-box">
            <h4>تعداد کل تراکنش‌ها</h4>
            <div class="amount"><?php echo number_format($stats['total_transactions'] ?? 0); ?></div>
        </div>
    </div>

    <div class="table-container card">
        <div class="card-header">
            <h3>مانده حساب کاربران</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>جمع بدهی</th>
                        <th>جمع واریزی</th>
                        <th>مانده نهایی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($user_balances_result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($user_balances_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td class="negative"><?php echo number_format($row['total_debit']); ?></td>
                                <td class="positive"><?php echo number_format($row['total_credit']); ?></td>
                                <td class="<?php echo $row['balance'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo number_format($row['balance']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">هیچ کاربری مانده حساب غیر صفر ندارد.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
     <div class="alert alert-info mt-4">
        <strong>در انتظار بازخورد شما:</strong>
        <p>این یک نسخه اولیه از گزارش مالی است. لطفاً اعلام کنید چه موارد دیگری (مانند گزارش کمک‌های مالی، فیلتر تاریخ و...) باید به این صفحه اضافه شود.</p>
    </div>
</div>

<?php
mysqli_close($link);
require_once "../includes/footer.php";
?>
