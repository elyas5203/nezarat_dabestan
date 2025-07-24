<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/functions.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: ../index.php");
    exit;
}

// Fetch all users (teachers)
$sql_users = "SELECT id, first_name, last_name FROM users WHERE is_admin = 0 ORDER BY last_name, first_name";
$users_result = mysqli_query($link, $sql_users);

$financial_summary = [];

while ($user = mysqli_fetch_assoc($users_result)) {
    $user_id = $user['id'];
    $total_debit = 0;
    $total_credit = 0;

    // Calculate total debit
    $sql_debit = "SELECT SUM(amount) as total_debit FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'debit'";
    if ($stmt_debit = mysqli_prepare($link, $sql_debit)) {
        mysqli_stmt_bind_param($stmt_debit, "i", $user_id);
        mysqli_stmt_execute($stmt_debit);
        $result_debit = mysqli_stmt_get_result($stmt_debit);
        if ($row_debit = mysqli_fetch_assoc($result_debit)) {
            $total_debit = $row_debit['total_debit'] ?? 0;
        }
        mysqli_stmt_close($stmt_debit);
    }

    // Calculate total credit
    $sql_credit = "SELECT SUM(amount) as total_credit FROM booklet_transactions WHERE user_id = ? AND transaction_type = 'credit'";
    if ($stmt_credit = mysqli_prepare($link, $sql_credit)) {
        mysqli_stmt_bind_param($stmt_credit, "i", $user_id);
        mysqli_stmt_execute($stmt_credit);
        $result_credit = mysqli_stmt_get_result($stmt_credit);
        if ($row_credit = mysqli_fetch_assoc($result_credit)) {
            $total_credit = $row_credit['total_credit'] ?? 0;
        }
        mysqli_stmt_close($stmt_credit);
    }

    $balance = $total_credit - $total_debit;

    $financial_summary[] = [
        'user_id' => $user_id,
        'full_name' => $user['first_name'] . ' ' . $user['last_name'],
        'total_debit' => $total_debit,
        'total_credit' => $total_credit,
        'balance' => $balance,
    ];
}

require_once "../includes/header.php";
?>

<div class="page-content">
    <h2>گزارش جامع مالی مدرسین</h2>
    <p>این گزارش وضعیت بدهی و بستانکاری هر مدرس را نمایش می‌دهد.</p>

    <div class="table-container">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>نام مدرس</th>
                    <th>مجموع بدهی (تومان)</th>
                    <th>مجموع پرداختی (تومان)</th>
                    <th>مانده حساب (تومان)</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($financial_summary)): ?>
                    <tr><td colspan="5" class="text-center">هیچ اطلاعات مالی برای نمایش وجود ندارد.</td></tr>
                <?php else: ?>
                    <?php foreach ($financial_summary as $summary): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($summary['full_name']); ?></td>
                            <td><?php echo number_format($summary['total_debit']); ?></td>
                            <td><?php echo number_format($summary['total_credit']); ?></td>
                            <td>
                                <strong class="<?php echo $summary['balance'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo number_format(abs($summary['balance'])); ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ($summary['balance'] > 0): ?>
                                    <span class="badge bg-success">بستانکار</span>
                                <?php elseif ($summary['balance'] < 0): ?>
                                    <span class="badge bg-danger">بدهکار</span>
                                <?php else: ?>
                                     <span class="badge bg-secondary">تسویه</span>
                                <?php endif; ?>
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
