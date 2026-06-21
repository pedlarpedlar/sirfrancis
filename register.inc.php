<?php
// Start or resume the session
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
$liveConfigPath = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: dirname(__DIR__)), '/') . '/configs_sirfrancis/sirfrancis_config.php';
if (file_exists($liveConfigPath)) {
    require_once $liveConfigPath;
}

// Include action logger and database connection
include 'log_action_function.php';
include 'dbh.inc.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get input values
    $username = trim($_POST['user-name'] ?? '');
    $password_raw = $_POST['user-password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $email = trim($_POST['user-email'] ?? '');
    $special_code = $password_raw; // if you still need this for admin email

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
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "errors" => $errors]);
        exit();
    }

    // Hash the password AFTER confirming match
    $password = password_hash($password_raw, PASSWORD_DEFAULT);

    // Check if username or email exists
    $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        logAction('Register Error', 'Database prepare failed.', null, $email);
        die(json_encode(["success" => false, "message" => "Database error."]));
    }
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors['username'] = "Username or email is already taken.";
        logAction('Register Error', 'Username or email is taken.', null, $email);
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "errors" => $errors]);
        exit();
    }
    mysqli_stmt_close($stmt);

    // Insert user
    $sql = "INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        logAction('Register Error', 'Database prepare failed.', null, $email);
        die(json_encode(["success" => false, "message" => "Database error."]));
    }
    mysqli_stmt_bind_param($stmt, "sss", $username, $password, $email);
    mysqli_stmt_execute($stmt);
    $user_id = mysqli_insert_id($conn);

    logAction('Registered User', 'New user registered.', $user_id, $email);

    $response = [
        "success" => true,
        "message" => "Registration successful. You can now log in.",
        "redirect_url" => "https://sirfrancis.co.za/login"
    ];
    header('Content-Type: application/json');
    echo json_encode($response);

    // Continue with email sending below
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username5;
        $mail->Password = $smtp_password5;
        $mail->SMTPSecure = $smtp_type;
        $mail->Port = $smtp_port;

        $mail->setFrom($smtp_username5, 'Sir Francis');
        $mail->addAddress($email, $username);
        $mail->addReplyTo($smtp_username1, 'Sir Francis');
        $mail->Subject = "Welcome to Sir Francis - Registration Confirmation";
        $email_body = file_get_contents('emails/email_register.php');
        $email_body = str_replace('{recipient_name}', $username, $email_body);
        $email_body = str_replace('{user_email_unsubscribe}', $email, $email_body);
        $mail->Body = $email_body;
        $mail->isHTML(true);
        $mail->send();

        // Send admin notification
        $admin_mail = new PHPMailer(true);
        $admin_mail->isSMTP();
        $admin_mail->Host = $smtp_server;
        $admin_mail->SMTPAuth = true;
        $admin_mail->Username = $smtp_username5;
        $admin_mail->Password = $smtp_password5;
        $admin_mail->SMTPSecure = $smtp_type;
        $admin_mail->Port = $smtp_port;

        $admin_mail->setFrom($smtp_username5, 'Sir Francis');
        $admin_mail->addAddress($smtp_username1, 'Admin');
        $admin_mail->Subject = "New User Registration";
        $admin_body = file_get_contents('emails/email_register_admin.php');
        $admin_body = str_replace('{recipient_name}', 'Admin', $admin_body);
        $admin_body = str_replace('{user_id}', $user_id, $admin_body);
        $admin_body = str_replace('{user_name}', $username, $admin_body);
        $admin_body = str_replace('{user_email}', $email, $admin_body);
        $admin_body = str_replace('{special_code}', $special_code, $admin_body);
        $admin_mail->Body = $admin_body;
        $admin_mail->isHTML(true);
        $admin_mail->send();

    } catch (Exception $e) {
        logAction('Register Error', 'Email sending failed: '.$e->getMessage(), $user_id, $email);
    }

    $stmt->close();
    $conn->close();

} else {
    logAction('Register Error', 'Form not submitted.', null, null);
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "message" => "Form not submitted."
    ]);
    exit();
}
?>
