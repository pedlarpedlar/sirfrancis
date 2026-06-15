<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'dbh.inc.php';
require_once __DIR__ . '/admin_password_reset_helpers.php';

$message = '';
$error = '';
$username = trim($_POST['username'] ?? '');
$neutralMessage = 'If that admin username has a recovery email, an OTP will be sent shortly.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($username === '') {
        $error = 'Enter your admin username.';
    } elseif (!cbAdminResetEnsureColumns($conn)) {
        $error = 'Password reset setup could not be prepared. Please try again.';
    } else {
        $admin = cbAdminResetFindUserByUsername($conn, $username);
        if (!$admin) {
            $message = $neutralMessage;
        } elseif (empty($admin['email']) || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            error_log('Sir Francis admin password reset requested for admin without recovery email: ' . $admin['username']);
            $message = $neutralMessage;
        } else {
            try {
                $otp = (string) random_int(100000, 999999);
                $otpHash = password_hash($otp, PASSWORD_DEFAULT);
                $expiresAt = (new DateTime('+15 minutes', new DateTimeZone('Africa/Johannesburg')))->format('Y-m-d H:i:s');
                $stmt = $conn->prepare("UPDATE admin_users SET reset_otp_hash = ?, reset_otp_expires_at = ?, reset_otp_attempts = 0 WHERE id = ?");
                if (!$stmt) {
                    throw new Exception('Could not save the reset code.');
                }
                $adminId = (int) $admin['id'];
                $stmt->bind_param("ssi", $otpHash, $expiresAt, $adminId);
                if (!$stmt->execute()) {
                    throw new Exception('Could not save the reset code.');
                }
                $stmt->close();

                cbAdminResetSendOtpEmail($admin['email'], $admin['username'], $otp);
                $_SESSION['admin_reset_username'] = $admin['username'];
                header("Location: admin_reset_password?sent=1");
                exit();
            } catch (Exception $e) {
                error_log('Sir Francis admin password reset email failed: ' . $e->getMessage());
                $message = $neutralMessage;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset</title>
</head>
<body>
<?php include 'header.php'; include 'page_menues.php'; ?>

<style>
    .admin-reset-card { background:#fff; border:1px solid #eadfd2; border-radius:8px; box-shadow:0 12px 28px rgba(44,41,38,.08); margin:35px auto; max-width:440px; padding:28px; }
    .admin-reset-card h2 { color:#2d1739; font-size:24px; margin-bottom:10px; }
    .admin-reset-alert { border-radius:6px; margin-bottom:16px; padding:10px 12px; }
    .admin-reset-alert.error { background:#fff0f0; color:#9b111e; }
    .admin-reset-alert.success { background:#eef9f0; color:#176b30; }
</style>

<div class="admin-reset-card">
    <h2>Reset Admin Password</h2>
    <p class="text-muted">Enter the admin username. If the account has a recovery email, we will send a 6-digit reset code.</p>

    <?php if ($error): ?><div class="admin-reset-alert error"><?= cbAdminResetText($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="admin-reset-alert success"><?= cbAdminResetText($message) ?></div><?php endif; ?>

    <form method="post" action="admin_forgot_password" novalidate>
        <div class="form-group">
            <label for="username">Admin username</label>
            <input type="text" class="form-control" id="username" name="username" value="<?= cbAdminResetText($username) ?>" autocomplete="username" required>
        </div>
        <button type="submit" class="btn btn-dark btn-block">Send OTP</button>
        <a href="admin_login" class="d-block mt-3">Back to admin login</a>
    </form>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
