<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'dbh.inc.php';
require_once __DIR__ . '/admin_password_reset_helpers.php';

$error = '';
$success = '';
$sent = isset($_GET['sent']);
$username = trim($_POST['username'] ?? ($_SESSION['admin_reset_username'] ?? ''));
$resetMode = $_SESSION['admin_reset_mode'] ?? (($_GET['mode'] ?? '') === 'first-time' ? 'first_time' : 'reset');
$isFirstTime = $resetMode === 'first_time';
$pageTitle = $isFirstTime ? 'Create Admin Password' : 'Enter Admin OTP';
$pageIntro = $isFirstTime
    ? 'Enter the 6-digit code from the email, then create the admin password.'
    : 'Enter the 6-digit code from the email, then choose a new password.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = preg_replace('/\D+/', '', (string) ($_POST['otp'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '') {
        $error = 'Enter your admin username.';
    } elseif ($otp === '' || strlen($otp) !== 6) {
        $error = 'Enter the 6-digit OTP code.';
    } elseif (strlen($password) < 8) {
        $error = 'Use a password with at least 8 characters.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'The two password fields do not match.';
    } elseif (!cbAdminResetEnsureColumns($conn)) {
        $error = 'Password reset setup could not be checked. Please try again.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, reset_otp_hash, reset_otp_expires_at, reset_otp_attempts FROM admin_users WHERE LOWER(username) = LOWER(?) LIMIT 1");
        if (!$stmt) {
            $error = 'Password reset is temporarily unavailable.';
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$admin || empty($admin['reset_otp_hash']) || empty($admin['reset_otp_expires_at'])) {
                $error = 'No active reset code was found. Please request a new OTP.';
            } elseif ((int) ($admin['reset_otp_attempts'] ?? 0) >= 5) {
                $error = 'Too many incorrect attempts. Please request a new OTP.';
            } elseif (new DateTime('now', new DateTimeZone('Africa/Johannesburg')) > new DateTime($admin['reset_otp_expires_at'], new DateTimeZone('Africa/Johannesburg'))) {
                $error = 'This OTP has expired. Please request a new one.';
            } elseif (!password_verify($otp, $admin['reset_otp_hash'])) {
                $adminId = (int) $admin['id'];
                $conn->query("UPDATE admin_users SET reset_otp_attempts = reset_otp_attempts + 1 WHERE id = $adminId");
                $error = 'That OTP is incorrect. Please check the code and try again.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ?, reset_otp_hash = NULL, reset_otp_expires_at = NULL, reset_otp_attempts = 0 WHERE id = ?");
                if (!$stmt) {
                    $error = 'Could not update the password. Please try again.';
                } else {
                    $adminId = (int) $admin['id'];
                    $stmt->bind_param("si", $passwordHash, $adminId);
                    if ($stmt->execute()) {
                        unset($_SESSION['admin_reset_username']);
                        unset($_SESSION['admin_reset_mode']);
                        $success = $isFirstTime
                            ? 'Admin password created. You can now log in with your username and password.'
                            : 'Admin password updated. You can now log in with your username and new password.';
                        $username = '';
                    } else {
                        $error = 'Could not update the password. Please try again.';
                    }
                    $stmt->close();
                }
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
    <title><?= cbAdminResetText($pageTitle) ?></title>
</head>
<body class="admin-sf">
<?php include 'header.php'; include 'page_menues.php'; ?>

<style>
    .admin-reset-card { background:#fff; border:1px solid var(--sf-border); border-radius:8px; box-shadow:0 12px 28px rgba(44,41,38,.08); margin:35px auto; max-width:460px; padding:28px; }
    .admin-reset-card h2 { color:var(--sf-navy); font-size:24px; margin-bottom:10px; }
    .admin-reset-alert { border-radius:6px; margin-bottom:16px; padding:10px 12px; }
    .admin-reset-alert.error { background:#fff0f0; color:#9b111e; }
    .admin-reset-alert.success { background:#eef9f0; color:#176b30; }
    .otp-input { font-size:22px; letter-spacing:8px; text-align:center; }
</style>

<div class="admin-reset-card">
    <h2><?= cbAdminResetText($pageTitle) ?></h2>
    <p class="text-muted"><?= cbAdminResetText($pageIntro) ?></p>

    <?php if ($sent): ?><div class="admin-reset-alert success"><?= $isFirstTime ? 'First-time OTP sent. Please check the admin recovery email.' : 'OTP sent. Please check the admin recovery email.' ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-reset-alert error"><?= cbAdminResetText($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="admin-reset-alert success"><?= cbAdminResetText($success) ?></div><?php endif; ?>

    <?php if ($success): ?>
        <a href="admin_login" class="btn btn-dark btn-block">Go to admin login</a>
    <?php else: ?>
        <form method="post" action="admin_reset_password" novalidate>
            <div class="form-group">
                <label for="username">Admin username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= cbAdminResetText($username) ?>" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="otp">OTP code</label>
                <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control otp-input" id="otp" name="otp" autocomplete="one-time-code" required>
            </div>
            <div class="form-group">
                <label for="password"><?= $isFirstTime ? 'Create password' : 'New password' ?></label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" minlength="8" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm new password</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-dark btn-block"><?= $isFirstTime ? 'Create password' : 'Reset password' ?></button>
            <a href="<?= $isFirstTime ? 'admin_first_time_access' : 'admin_forgot_password' ?>" class="d-block mt-3">Request a new OTP</a>
        </form>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
