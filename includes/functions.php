<?php
// This file can be used for general purpose functions

require_once 'jdf.php';

/**
 * Converts a MySQL DATETIME string to a Persian date format.
 *
 * @param string $datetime_str The MySQL DATETIME string (e.g., "2024-07-13 10:00:00").
 * @param string $format The format for the output date (uses jdf formatting).
 * @return string The formatted Persian date.
 */
function to_persian_date($datetime_str, $format = 'Y/m/d H:i') {
    if (empty($datetime_str)) {
        return '';
    }
    $timestamp = strtotime($datetime_str);
    return jdf($format, $timestamp);
}

/**
 * Sends a message to a specific Telegram user.
 *
 * @param string $chat_id The recipient's Telegram Chat ID.
 * @param string $message The message text.
 * @return bool True on success, false on failure.
 */
function send_telegram_message($chat_id, $message) {
    $token_file = __DIR__ . '/../config/telegram_token.php';
    if (!file_exists($token_file)) {
        return false;
    }
    require_once $token_file;
    if (!defined('TELEGRAM_BOT_TOKEN') || TELEGRAM_BOT_TOKEN === '') {
        return false;
    }
    $bot_token = TELEGRAM_BOT_TOKEN;

    if (empty($chat_id)) {
        return false;
    }

    $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result !== false;
}

/**
 * Translates class status from English to Persian.
 *
 * @param string $status The status in English (e.g., "active").
 * @return string The translated status in Persian.
 */
function translate_class_status($status) {
    $translation = [
        'active'    => 'فعال',
        'inactive'  => 'غیرفعال',
        'archived'  => 'آرشیو شده',
        'disbanded' => 'منحل شده',
        'setup'     => 'تحویل مقدمات'
    ];
    return $translation[$status] ?? $status;
}

/**
 * Sends a notification to all users who have a specific permission.
 *
 * @param mysqli $link The database connection.
 * @param string $permission_name The name of the permission.
 * @param string $message The notification message.
 * @param string|null $url The URL for the notification link.
 */
function notify_permission($permission_name, $message, $url = null) {
    $link = get_db_connection();

    // Find all users with the given permission (either directly or through a role)
    $sql = "SELECT DISTINCT u.id
            FROM users u
            LEFT JOIN user_permissions up ON u.id = up.user_id
            LEFT JOIN permissions p_up ON up.permission_id = p_up.id
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN role_permissions rp ON ur.role_id = rp.role_id
            LEFT JOIN permissions p_rp ON rp.permission_id = p_rp.id
            WHERE p_up.permission_name = ? OR p_rp.permission_name = ? OR u.is_admin = 1";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $permission_name, $permission_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $user_ids = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $user_ids[] = $row['id'];
        }
        mysqli_stmt_close($stmt);

        if (!empty($user_ids)) {
            // Insert notification for each user
            $sql_notify = "INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, 'system')";
            if ($stmt_notify = mysqli_prepare($link, $sql_notify)) {
                foreach ($user_ids as $user_id) {
                    mysqli_stmt_bind_param($stmt_notify, "iss", $user_id, $message, $url);
                    mysqli_stmt_execute($stmt_notify);
                }
                mysqli_stmt_close($stmt_notify);
            }
        }
    }
}

/**
 * Checks if a user is a teacher of a specific class.
 *
 * @param mysqli $link The database connection.
 * @param int $user_id The ID of the user.
 * @param int $class_id The ID of the class.
 * @return bool True if the user is a teacher of the class, false otherwise.
 */
function is_teacher_of_class($link, $user_id, $class_id) {
    $sql = "SELECT 1 FROM class_teachers WHERE teacher_id = ? AND class_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $class_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $is_teacher = (mysqli_stmt_num_rows($stmt) > 0);
        mysqli_stmt_close($stmt);
        return $is_teacher;
    }
    return false;
}

/**
 * Sends a notification to all teachers of a specific class.
 *
 * @param int $class_id The ID of the class.
 * @param string $message The notification message.
 * @param string|null $url The URL for the notification link.
 */
function notify_class_teachers($class_id, $message, $url = null) {
    $link = get_db_connection();

    $sql = "SELECT teacher_id FROM class_teachers WHERE class_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $class_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $teacher_ids = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $teacher_ids[] = $row['teacher_id'];
        }
        mysqli_stmt_close($stmt);

        if (!empty($teacher_ids)) {
            $sql_notify = "INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, 'system')";
            if ($stmt_notify = mysqli_prepare($link, $sql_notify)) {
                foreach ($teacher_ids as $teacher_id) {
                    mysqli_stmt_bind_param($stmt_notify, "iss", $teacher_id, $message, $url);
                    mysqli_stmt_execute($stmt_notify);
                }
                mysqli_stmt_close($stmt_notify);
            }
        }
    }
}

function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'سال',
        'm' => 'ماه',
        'w' => 'هفته',
        'd' => 'روز',
        'h' => 'ساعت',
        'i' => 'دقیقه',
        's' => 'ثانیه',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' پیش' : 'همین الان';
}

/**
 * Checks if the current user is an admin or has a specific permission.
 * Redirects to index.php if the user does not have permission.
 *
 * @param string $permission_name The name of the permission to check.
 */
function is_admin_or_has_permission($permission_name) {
    // Session must be started before calling this function.
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        return false;
    }

    // Admin has all permissions.
    if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
        return true;
    }

    // Check for specific permission in the session variable.
    if (isset($_SESSION["permissions"]) && in_array($permission_name, $_SESSION["permissions"])) {
        return true;
    }

    return false;
}

/**
 * Logs an event to the events table.
 *
 * @param int|null $user_id The ID of the user who performed the action. Can be null for system actions.
 * @param string $action A short description of the action (e.g., 'user_login', 'create_booklet').
 * @param string|null $details More details about the event (e.g., the name of the created booklet).
 */
function log_event($user_id, $action, $details = null) {
    $link = get_db_connection();
    $sql = "INSERT INTO events (user_id, action, details) VALUES (?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $action, $details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
