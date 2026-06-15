<?php
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

$liveConfigPath = '/home/candybirdco/configs_candybird/candybird_config.php';
if (file_exists($liveConfigPath)) {
    require_once($liveConfigPath);
}

include_once 'dbh.inc.php';
include_once 'log_action_function.php';

function resetPasswordJson($payload) {
    echo json_encode($payload);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resetPasswordJson([
        'success' => false,
        'errors' => ['token' => 'Invalid request. Please open the reset link from your email again.']
    ]);
}

if (!($conn instanceof mysqli)) {
    resetPasswordJson([
        'success' => false,
        'errors' => ['token' => 'Password reset is temporarily unavailable. Please try again shortly.']
    ]);
}

$token = trim((string) ($_POST['token'] ?? ''));
$password = (string) ($_POST['user-password'] ?? '');
$confirmPassword = (string) ($_POST['user-confirm-password'] ?? '');
$errors = [];

if ($token === '') {
    $errors['token'] = 'Invalid or expired token.';
}
if ($password === '') {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}
if ($confirmPassword === '') {
    $errors['confirm-password'] = 'Confirm password is required.';
} elseif ($password !== $confirmPassword) {
    $errors['confirm-password'] = 'Passwords do not match.';
}

if ($errors) {
    resetPasswordJson([
        'success' => false,
        'errors' => $errors
    ]);
}

$sql = "SELECT pr.id, pr.user_id, u.email, COALESCE(NULLIF(ua.billing_first_name, ''), u.username, 'Customer') AS first_name
        FROM password_resets pr
        INNER JOIN users u ON pr.user_id = u.id
        LEFT JOIN user_addresses ua ON u.id = ua.user_id
        WHERE pr.token = ? AND pr.expiration > UNIX_TIMESTAMP()
        ORDER BY pr.id DESC
        LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    resetPasswordJson([
        'success' => false,
        'errors' => ['token' => 'Password reset could not be prepared. Please try again.']
    ]);
}

mysqli_stmt_bind_param($stmt, 's', $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) <= 0) {
    resetPasswordJson([
        'success' => false,
        'errors' => ['token' => 'Invalid or expired token. Please request a new password reset link.']
    ]);
}

$row = mysqli_fetch_assoc($result);
$resetId = (int) $row['id'];
$userId = (int) $row['user_id'];
$userEmail = (string) $row['email'];
$firstName = (string) $row['first_name'];
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

mysqli_begin_transaction($conn);

try {
    $sqlUpdate = "UPDATE users SET password_hash = ? WHERE id = ?";
    $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
    if (!$stmtUpdate) {
        throw new Exception('Could not prepare password update.');
    }
    mysqli_stmt_bind_param($stmtUpdate, 'si', $passwordHash, $userId);
    if (!mysqli_stmt_execute($stmtUpdate)) {
        throw new Exception('Could not update password.');
    }

    $sqlDelete = "DELETE FROM password_resets WHERE id = ?";
    $stmtDelete = mysqli_prepare($conn, $sqlDelete);
    if ($stmtDelete) {
        mysqli_stmt_bind_param($stmtDelete, 'i', $resetId);
        mysqli_stmt_execute($stmtDelete);
    }

    mysqli_commit($conn);
    logAction('User Reset Password Successfully', 'used Reset Link', $userId, null);
} catch (Exception $e) {
    mysqli_rollback($conn);
    logAction('Reset Password Error', $e->getMessage(), $userId, null);
    resetPasswordJson([
        'success' => false,
        'errors' => ['password' => 'Password could not be updated. Please try again.']
    ]);
}

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
        $mail->Subject = 'Password Changed Successfully | Sir Francis';
        $emailBody = file_get_contents('emails/email_changed_password.php');
        $emailBody = str_replace('{recipient_name}', htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'), $emailBody);
        $emailBody = str_replace('{user_email_unsubscribe}', urlencode($userEmail), $emailBody);
        $emailBody = str_replace('{reset_link}', 'https://www.fishgelatine.co.za/v2/forgot-password', $emailBody);
        $mail->Body = $emailBody;
        $mail->isHTML(true);
        $mail->send();
    } catch (Exception $e) {
        $emailWarning = ' Password was changed, but the confirmation email could not be sent.';
        logAction('Reset Password Email Error', $e->getMessage(), $userId, null);
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
        $adminMail->Subject = 'User Successfully Changed Password | Sir Francis';
        $adminBody = file_get_contents('emails/email_changed_password_admin.php');
        $adminBody = str_replace('{recipient_name}', 'Admin', $adminBody);
        $adminBody = str_replace('{reset_link}', 'https://www.fishgelatine.co.za/v2/admin-sf/', $adminBody);
        $adminBody = str_replace('{user_id}', (string) $userId, $adminBody);
        $adminBody = str_replace('{special_code}', 'Password was reset by the user.', $adminBody);
        $adminMail->Body = $adminBody;
        $adminMail->isHTML(true);
        $adminMail->send();
    } catch (Exception $e) {
        logAction('Reset Password Admin Email Error', $e->getMessage(), $userId, null);
    }
}

resetPasswordJson([
    'success' => true,
    'message' => 'Password has been reset successfully. You can now log in.' . $emailWarning,
    'redirect_url' => 'login'
]);
