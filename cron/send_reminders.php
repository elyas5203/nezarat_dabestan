<?php
// This script is intended to be run by a cron job, e.g., every hour.
// It does not have session/UI.
require_once dirname(__DIR__) . "/includes/db.php";
require_once dirname(__DIR__) . "/includes/functions.php";

echo "--- Reminder Script Started: " . date('Y-m-d H:i:s') . " ---\n";

$link = get_db_connection();

// Find checklist items that need reminders
$sql = "
    SELECT
        mc.id as meeting_checklist_id,
        mc.meeting_id,
        ti.item_text,
        ti.reminder_frequency_hours,
        pm.created_by as user_to_notify,
        c.class_name
    FROM meeting_checklists mc
    JOIN checklist_template_items ti ON mc.template_item_id = ti.id
    JOIN parent_meetings pm ON mc.meeting_id = pm.id AND mc.meeting_type = 'parent'
    JOIN classes c ON pm.class_id = c.id
    WHERE
        mc.is_completed = 0
        AND ti.reminder_frequency_hours IS NOT NULL
        AND ti.reminder_frequency_hours > 0
        AND (
            mc.last_reminder_sent_at IS NULL
            OR mc.last_reminder_sent_at <= NOW() - INTERVAL ti.reminder_frequency_hours HOUR
        )
";
// NOTE: This query only supports parent_meetings. It should be expanded for service_meetings.

$result = mysqli_query($link, $sql);

if (!$result) {
    echo "Error executing query: " . mysqli_error($link) . "\n";
    exit;
}

$reminders_sent = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $user_id = $row['user_to_notify'];
    $message = "یادآوری: آیتم چک‌لیست '{$row['item_text']}' برای جلسه کلاس '{$row['class_name']}' هنوز تکمیل نشده است.";
    $link_to_page = "user/meeting_reports.php?id=" . $row['meeting_id'];

    // Insert notification
    $sql_notify = "INSERT INTO notifications (user_id, type, related_id, message, link) VALUES (?, 'checklist_reminder', ?, ?, ?)";
    $stmt_notify = mysqli_prepare($link, $sql_notify);
    mysqli_stmt_bind_param($stmt_notify, "iiss", $user_id, $row['meeting_checklist_id'], $message, $link_to_page);
    mysqli_stmt_execute($stmt_notify);

    // Update last_reminder_sent_at
    $sql_update = "UPDATE meeting_checklists SET last_reminder_sent_at = NOW() WHERE id = ?";
    $stmt_update = mysqli_prepare($link, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "i", $row['meeting_checklist_id']);
    mysqli_stmt_execute($stmt_update);

    echo "Sent reminder for item '{$row['item_text']}' to user ID {$user_id}.\n";
    $reminders_sent++;
}

echo "--- Reminder Script Finished. Sent {$reminders_sent} reminders. ---\n";
mysqli_close($link);
?>
