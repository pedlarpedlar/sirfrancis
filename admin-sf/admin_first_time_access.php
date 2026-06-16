<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'dbh.inc.php';
require_once __DIR__ . '/admin_password_reset_helpers.php';

$message = '';
$error = '';
$username = trim($_POST['username'] ?? '');
$neutralMessage = 'If that admin username has a recovery email, a first-time OTP will be sent shortly.';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if ($username === '') {
        $error = 'Enter your admin username.';
    } else {
        try {
            $admin = cbAdminResetIssueOtp($conn, $username, 'first_time');
            if ($admin) {
                $_SESSION['admin_reset_username'] = $admin['username'];
                $_SESSION['admin_reset_mode'] = 'first_time';
                header("Location: admin_reset_password?sent=1&mode=first-time");
                exit();
            }
            $message = $neutralMessage;
        } catch (Exception $e) {
            error_log('Sir Francis first-time admin OTP failed: ' . $e->getMessage());
            $message = $neutralMessage;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First-Time Admin Access</title>
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png" />
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="admin-theme.css" />
</head>
<body class="admin-sf">
<style>
    body.admin-sf {
        align-items: center;
        background: var(--sf-cream);
        display: flex;
        justify-content: center;
        min-height: 100vh;
        padding: 24px;
    }
    .admin-access-card {
        background: #fff;
        border: 1px solid var(--sf-border);
        border-radius: 0;
        box-shadow: 0 16px 40px rgba(23, 34, 53, .12);
        max-width: 440px;
        padding: 30px;
        width: 100%;
    }
    .admin-access-card h2 {
        color: var(--sf-navy);
        font-size: 24px;
        margin-bottom: 10px;
        text-align: center;
    }
    .admin-access-logo {
        display: block;
        height: auto;
        margin: 0 auto 12px;
        max-width: 210px;
        width: 100%;
    }
    .admin-access-alert {
        border-radius: 0;
        margin-bottom: 16px;
        padding: 10px 12px;
    }
    .admin-access-alert.error { background:#fff0f0; color:#9b111e; }
    .admin-access-alert.success { background:#eef9f0; color:#176b30; }
</style>

<div class="admin-access-card">
    <img src="../assets/img/logo/logo.png" alt="Sir Francis" class="admin-access-logo">
    <h2>First-Time Admin Access</h2>
    <p class="text-muted text-center">Enter the admin username. If the account has a recovery email, we will send a one-time code so you can create the password.</p>

    <?php if ($error): ?><div class="admin-access-alert error"><?= cbAdminResetText($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="admin-access-alert success"><?= cbAdminResetText($message) ?></div><?php endif; ?>

    <form method="post" action="admin_first_time_access" novalidate>
        <div class="form-group">
            <label for="username">Admin username</label>
            <input type="text" class="form-control" id="username" name="username" value="<?= cbAdminResetText($username) ?>" autocomplete="username" required>
        </div>
        <button type="submit" class="btn btn-dark btn-block">Send first-time OTP</button>
        <a href="admin_login" class="d-block mt-3">Back to admin login</a>
    </form>
</div>
</body>
</html>
