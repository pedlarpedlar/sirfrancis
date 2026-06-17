<?php
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

$liveConfigPath = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: dirname(__DIR__)), '/') . '/configs_sirfrancis/sirfrancis_config.php';
if (file_exists($liveConfigPath)) {
    require_once($liveConfigPath);
}

include_once 'dbh.inc.php';
include_once 'log_action_function.php';

function forgotPasswordJson($payload) {
    echo json_encode($payload);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    forgotPasswordJson([
        'success' => false,
        'message' => 'Form not submitted.'
    ]);
}

if (!($conn instanceof mysqli)) {
    forgotPasswordJson([
        'success' => false,
        'errors' => ['email' => 'Password reset is temporarily unavailable. Please try again shortly.']
    ]);
}

$userEmail = trim((string) ($_POST['user-email'] ?? ''));

if ($userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    forgotPasswordJson([
        'success' => false,
        'errors' => ['email' => 'A valid email is required.']
    ]);
}

$sql = "SELECT u.id, u.email, COALESCE(NULLIF(ua.billing_first_name, ''), u.username, 'Customer') AS first_name
        FROM users u
        LEFT JOIN user_addresses ua ON u.id = ua.user_id
        WHERE u.email = ?
        LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    forgotPasswordJson([
        'success' => false,
        'errors' => ['email' => 'Password reset could not be prepared. Please try again.']
    ]);
}

mysqli_stmt_bind_param($stmt, 's', $userEmail);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) <= 0) {
    logAction('Password Reset Request Failed Attempt', 'Error: Email address ' . $userEmail . ' not found.', null, null);
    forgotPasswordJson([
        'success' => false,
        'errors' => ['email' => 'Email address not found. Please check your spelling.']
    ]);
}

$user = mysqli_fetch_assoc($result);
$userId = (int) $user['id'];
$firstName = (string) $user['first_name'];
$resetToken = bin2hex(random_bytes(32));
$expiration = time() + (24 * 60 * 60);

$sqlToken = "INSERT INTO password_resets (user_id, token, expiration) VALUES (?, ?, ?)";
$stmtToken = mysqli_prepare($conn, $sqlToken);
if (!$stmtToken) {
    forgotPasswordJson([
        'success' => false,
        'errors' => ['email' => 'Password reset could not be prepared. Please try again.']
    ]);
}

mysqli_stmt_bind_param($stmtToken, 'isi', $userId, $resetToken, $expiration);
if (!mysqli_stmt_execute($stmtToken)) {
    forgotPasswordJson([
        'success' => false,
        'errors' => ['email' => 'Password reset could not be created. Please try again.']
    ]);
}

$resetLink = 'https://www.fishgelatine.co.za/v2/reset-password?token=' . urlencode($resetToken);
logAction('User requested Password Reset email', 'Link: ' . $resetLink, $userId, null);

$emailWarning = '';
if (isset($smtp_server, $smtp_username5, $smtp_password5, $smtp_type, $smtp_port, $smtp_username1)) {
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
        $mail->addAddress($userEmail);
        $mail->Subject = 'Password Reset Request | Sir Francis';
        $emailBody = file_get_contents('emails/email_forgot_password.php');
        $emailBody = str_replace('{recipient_name}', htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'), $emailBody);
        $emailBody = str_replace('{user_email_unsubscribe}', urlencode($userEmail), $emailBody);
        $emailBody = str_replace('{reset_link}', $resetLink, $emailBody);
        $mail->Body = $emailBody;
        $mail->isHTML(true);
        $mail->send();
    } catch (Exception $e) {
        $emailWarning = ' We created the reset link, but the email could not be sent.';
        logAction('Forgot Password Email Error', $e->getMessage(), $userId, null);
    }

    try {
        $adminMail = new PHPMailer(true);
        $adminMail->isSMTP();
        $adminMail->Host = $smtp_server;
        $adminMail->SMTPAuth = true;
        $adminMail->Username = $smtp_username5;
        $adminMail->Password = $smtp_password5;
        $adminMail->SMTPSecure = $smtp_type;
        $adminMail->Port = $smtp_port;
        $adminMail->setFrom($smtp_username5, 'Sir Francis');
        $adminMail->addAddress($smtp_username1, 'Admin');
        $adminMail->Subject = 'Password Reset Request | Sir Francis';
        $adminBody = file_get_contents('emails/email_forgot_password_admin.php');
        $adminBody = str_replace('{recipient_name}', 'Admin', $adminBody);
        $adminBody = str_replace('{reset_link}', $resetLink, $adminBody);
        $adminBody = str_replace('{user_id}', (string) $userId, $adminBody);
        $adminMail->Body = $adminBody;
        $adminMail->isHTML(true);
        $adminMail->send();
    } catch (Exception $e) {
        logAction('Forgot Password Admin Email Error', $e->getMessage(), $userId, null);
    }
}

forgotPasswordJson([
    'success' => true,
    'message' => 'Password reset email sent. Please check your email for instructions.' . $emailWarning,
    'reset_link' => $resetLink
]);
