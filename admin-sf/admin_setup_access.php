<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin_password_reset_helpers.php';

$setupKeyHash = '089d0df0857cc3f6fd7c1df769bcc1e5c2a63912fbec0700c4164f2779cc5efc';
$setupExpiresAt = strtotime('2026-06-18 23:59:59 Africa/Johannesburg');
$message = '';
$error = '';
$email = trim((string) ($_POST['email'] ?? ($_GET['email'] ?? '')));
$setupKey = trim((string) ($_POST['setup_key'] ?? ($_GET['key'] ?? '')));

function sfSetupText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sfSetupKeyIsValid($setupKey, $setupKeyHash, $setupExpiresAt) {
    if ($setupKey === '' || time() > $setupExpiresAt) {
        return false;
    }
    return hash_equals($setupKeyHash, hash('sha256', $setupKey));
}

function sfSetupCreateOrRepairAdmin($conn, $email, $password) {
    if (!cbAdminResetEnsureColumns($conn)) {
        throw new RuntimeException('Admin table could not be prepared.');
    }

    if (cbAdminResetRecoverableAdminCount($conn) > 0) {
        throw new RuntimeException('A recoverable admin account already exists. Use the normal admin login or password reset.');
    }

    $email = strtolower(trim((string) $email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid admin email address.');
    }
    if (strlen($password) < 8) {
        throw new RuntimeException('Use a password with at least 8 characters.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $result = $conn->query("SELECT id FROM admin_users ORDER BY id ASC LIMIT 1");
    $existingId = 0;
    if ($result) {
        $row = $result->fetch_assoc();
        $existingId = (int) ($row['id'] ?? 0);
    }

    if ($existingId > 0) {
        $stmt = $conn->prepare("UPDATE admin_users SET username = ?, email = ?, password_hash = ?, reset_otp_hash = NULL, reset_otp_expires_at = NULL, reset_otp_attempts = 0 WHERE id = ?");
        if (!$stmt) {
            throw new RuntimeException('Could not update the admin account.');
        }
        $stmt->bind_param("sssi", $email, $email, $passwordHash, $existingId);
    } else {
        $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new RuntimeException('Could not create the admin account.');
        }
        $stmt->bind_param("sss", $email, $email, $passwordHash);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Admin account could not be saved. The email may already exist.');
    }
    $stmt->close();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (!sfSetupKeyIsValid($setupKey, $setupKeyHash, $setupExpiresAt)) {
        $error = 'Setup key is missing, invalid, or expired.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'The two password fields do not match.';
    } else {
        try {
            include __DIR__ . '/db_connect.php';
            sfSetupCreateOrRepairAdmin($conn, $email, $password);
            $message = 'Admin account is ready. Log in using this email address and password.';
        } catch (Exception $e) {
            error_log('Sir Francis direct admin setup failed: ' . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

$keyValid = sfSetupKeyIsValid($setupKey, $setupKeyHash, $setupExpiresAt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sir Francis Admin Setup</title>
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png" />
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="admin-theme.css" />
</head>
<body class="admin-sf">
<style>
    body.admin-sf { align-items:center; background:var(--sf-cream); display:flex; justify-content:center; min-height:100vh; padding:24px; }
    .setup-card { background:#fff; border:1px solid var(--sf-border); border-radius:0; box-shadow:0 16px 40px rgba(23,34,53,.12); max-width:460px; padding:30px; width:100%; }
    .setup-card h1 { color:var(--sf-navy); font-size:26px; margin-bottom:10px; text-align:center; }
    .setup-logo { display:block; height:auto; margin:0 auto 12px; max-width:210px; width:100%; }
    .setup-alert { border-radius:0; margin-bottom:16px; padding:10px 12px; }
    .setup-alert.error { background:#fff0f0; color:#9b111e; }
    .setup-alert.success { background:#eef9f0; color:#176b30; }
</style>

<div class="setup-card">
    <img src="../assets/img/logo/logo.png" alt="Sir Francis" class="setup-logo">
    <h1>Admin Setup</h1>
    <p class="text-muted text-center">Use this once to create or repair the first admin login.</p>

    <?php if ($error): ?><div class="setup-alert error"><?= sfSetupText($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="setup-alert success"><?= sfSetupText($message) ?></div><?php endif; ?>

    <?php if ($message): ?>
        <a href="admin_login" class="btn btn-dark btn-block">Go to admin login</a>
    <?php else: ?>
        <form method="post" action="admin_setup_access" novalidate>
            <input type="hidden" name="setup_key" value="<?= sfSetupText($setupKey) ?>">
            <?php if (!$keyValid): ?>
                <div class="form-group">
                    <label for="setup_key">Setup key</label>
                    <input type="text" class="form-control" id="setup_key" name="setup_key" value="<?= sfSetupText($setupKey) ?>" required>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="email">Admin email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= sfSetupText($email) ?>" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Admin password</label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" minlength="8" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm password</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Create admin login</button>
            <a href="admin_login" class="d-block mt-3">Back to admin login</a>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
