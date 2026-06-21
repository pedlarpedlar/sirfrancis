<?php
// Start or resume the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require_once 'candybird_mail_helpers.php';

function sfRegisterRespond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit();
}

$liveConfigPath = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: dirname(__DIR__)), '/') . '/configs_sirfrancis/sirfrancis_config.php';
if (file_exists($liveConfigPath)) {
    require_once $liveConfigPath;
}

// Include action logger and database connection
include 'log_action_function.php';
include 'dbh.inc.php';

// Check if the form is submitted
$requestMethod = $_SERVER["REQUEST_METHOD"] ?? '';
if ($requestMethod === "POST") {
    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        $dbError = isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'Connection unavailable.';
        logAction('Register Error', 'Database connection unavailable: ' . $dbError, null, null);
        sfRegisterRespond([
            "success" => false,
            "message" => "Registration is temporarily unavailable. Please try again shortly."
        ], 500);
    }

    // Get input values
    $username = trim($_POST['user-name'] ?? '');
    $password_raw = $_POST['user-password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $email = trim($_POST['user-email'] ?? '');

    $errors = [];

    // Validate inputs
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
        logAction('Register Error', 'Username is required.', null, $email);
    }
    if (empty($password_raw)) {
        $errors['password'] = 'Password is required.';
        logAction('Register Error', 'Password is required.', null, $email);
    }
    if (empty($confirmPassword)) {
        $errors['confirm-password'] = 'Confirm password is required.';
        logAction('Register Error', 'Confirm password is required.', null, $email);
    }
    if ($password_raw !== $confirmPassword) {
        $errors['confirm-password'] = 'Passwords do not match.';
        logAction('Register Error', 'Passwords do not match.', null, $email);
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email is required.';
        logAction('Register Error', 'Invalid email.', null, $username);
    }

    if (!empty($errors)) {
        sfRegisterRespond(["success" => false, "errors" => $errors]);
    }

    // Hash the password AFTER confirming match
    $password = password_hash($password_raw, PASSWORD_DEFAULT);

    // Check if username or email exists
    $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        logAction('Register Error', 'Database prepare failed.', null, $email);
        sfRegisterRespond(["success" => false, "message" => "Database error."], 500);
    }
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors['username'] = "Username or email is already taken.";
        logAction('Register Error', 'Username or email is taken.', null, $email);
        sfRegisterRespond(["success" => false, "errors" => $errors]);
    }
    mysqli_stmt_close($stmt);

    // Insert user
    $sql = "INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        logAction('Register Error', 'Database prepare failed.', null, $email);
        sfRegisterRespond(["success" => false, "message" => "Database error."], 500);
    }
    mysqli_stmt_bind_param($stmt, "sss", $username, $password, $email);
    if (!mysqli_stmt_execute($stmt)) {
        $insertError = mysqli_stmt_error($stmt);
        logAction('Register Error', 'User insert failed: ' . $insertError, null, $email);
        mysqli_stmt_close($stmt);
        sfRegisterRespond([
            "success" => false,
            "message" => "Registration could not be completed. Please try again."
        ], 500);
    }
    $user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    logAction('Registered User', 'New user registered.', $user_id, $email);

    try {
        if (function_exists('cbCandybirdLoadMailConfig')) {
            cbCandybirdLoadMailConfig();
        }

        $email_body = @file_get_contents(__DIR__ . '/emails/email_register.php');
        if (is_string($email_body) && $email_body !== '' && function_exists('cbCandybirdSendMail')) {
            $email_body = str_replace('{recipient_name}', $username, $email_body);
            $email_body = str_replace('{user_email_unsubscribe}', $email, $email_body);
            $mailResult = cbCandybirdSendMail(
                $email,
                $username,
                "Welcome to Sir Francis - Registration Confirmation",
                $email_body,
                [
                    'from_name' => 'Sir Francis',
                    'reply_to_email' => $GLOBALS['smtp_username1'] ?? '',
                    'reply_to_name' => 'Sir Francis',
                ]
            );
            if (empty($mailResult['success'])) {
                logAction('Register Error', 'Welcome email failed: ' . ($mailResult['error'] ?? 'Unknown mail error.'), $user_id, $email);
            }
        } else {
            logAction('Register Error', 'Welcome email template or mail helper unavailable.', $user_id, $email);
        }

        $adminRecipient = trim((string) ($GLOBALS['smtp_username1'] ?? ''));
        $admin_body = @file_get_contents(__DIR__ . '/emails/email_register_admin.php');
        if ($adminRecipient !== '' && filter_var($adminRecipient, FILTER_VALIDATE_EMAIL) && is_string($admin_body) && $admin_body !== '' && function_exists('cbCandybirdSendMail')) {
            $admin_body = str_replace('{recipient_name}', 'Admin', $admin_body);
            $admin_body = str_replace('{user_id}', (string) $user_id, $admin_body);
            $admin_body = str_replace('{user_name}', $username, $admin_body);
            $admin_body = str_replace('{user_email}', $email, $admin_body);
            $admin_body = str_replace('{special_code}', '', $admin_body);
            $adminResult = cbCandybirdSendMail(
                $adminRecipient,
                'Admin',
                "New User Registration",
                $admin_body,
                ['from_name' => 'Sir Francis']
            );
            if (empty($adminResult['success'])) {
                logAction('Register Error', 'Admin registration email failed: ' . ($adminResult['error'] ?? 'Unknown mail error.'), $user_id, $email);
            }
        }
    } catch (Throwable $e) {
        logAction('Register Error', 'Registration email handling failed: ' . $e->getMessage(), $user_id, $email);
    }

    $conn->close();
    sfRegisterRespond([
        "success" => true,
        "message" => "Registration successful. You can now log in.",
        "redirect_url" => "https://sirfrancis.co.za/login"
    ]);

} else {
    logAction('Register Error', 'Form not submitted.', null, null);
    sfRegisterRespond([
        "success" => false,
        "message" => "Form not submitted."
    ], 405);
}
?>
