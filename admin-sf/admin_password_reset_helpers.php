<?php
use PHPMailer\PHPMailer\PHPMailer;

function cbAdminResetEnsureColumns($conn) {
    if (!($conn instanceof mysqli)) {
        return false;
    }

    if (!$conn->query("CREATE TABLE IF NOT EXISTS admin_users (
        id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(255) UNIQUE,
        password_hash VARCHAR(255),
        email VARCHAR(255) UNIQUE,
        reset_otp_hash VARCHAR(255) NULL,
        reset_otp_expires_at DATETIME NULL,
        reset_otp_attempts INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )")) {
        return false;
    }

    $columns = [
        'email' => "ALTER TABLE admin_users ADD COLUMN email VARCHAR(255) UNIQUE NULL",
        'reset_otp_hash' => "ALTER TABLE admin_users ADD COLUMN reset_otp_hash VARCHAR(255) NULL",
        'reset_otp_expires_at' => "ALTER TABLE admin_users ADD COLUMN reset_otp_expires_at DATETIME NULL",
        'reset_otp_attempts' => "ALTER TABLE admin_users ADD COLUMN reset_otp_attempts INT NOT NULL DEFAULT 0",
    ];

    foreach ($columns as $column => $alterSql) {
        $safeColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM admin_users LIKE '$safeColumn'");
        if ($result && $result->num_rows === 0) {
            if (!$conn->query($alterSql)) {
                return false;
            }
        }
    }

    return true;
}

function cbAdminResetAdminCount($conn) {
    if (!cbAdminResetEnsureColumns($conn)) {
        throw new RuntimeException('Admin users table could not be prepared.');
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM admin_users");
    if (!$result) {
        throw new RuntimeException('Admin users could not be checked.');
    }

    $row = $result->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function cbAdminResetRecoverableAdminCount($conn) {
    if (!cbAdminResetEnsureColumns($conn)) {
        throw new RuntimeException('Admin users table could not be prepared.');
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM admin_users WHERE email IS NOT NULL AND email <> ''");
    if (!$result) {
        throw new RuntimeException('Admin recovery emails could not be checked.');
    }

    $row = $result->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function cbAdminResetText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbAdminResetLoadMailer() {
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/SMTP.php';
}

function cbAdminResetSendOtpEmail($email, $username, $otp, $mode = 'reset') {
    global $smtp_server, $smtp_port, $smtp_type, $smtp_username1, $smtp_username5, $smtp_password5, $website_company_name;

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('This admin user does not have a valid recovery email address.');
    }

    if (empty($smtp_server) || empty($smtp_port) || empty($smtp_username5) || empty($smtp_password5)) {
        throw new RuntimeException('SMTP settings are missing. Please check the Sir Francis mail configuration.');
    }

    cbAdminResetLoadMailer();
    $isFirstTime = in_array($mode, ['first_time', 'bootstrap'], true);
    $subject = $isFirstTime ? 'Sir Francis first-time admin access OTP' : 'Sir Francis admin password reset OTP';
    $heading = $mode === 'bootstrap' ? 'Create first admin account' : ($isFirstTime ? 'First-time admin access' : 'Admin password reset');
    $intro = $mode === 'bootstrap'
        ? 'Use this one-time code to create the first Sir Francis admin account for'
        : ($isFirstTime
        ? 'Use this one-time code to create the Sir Francis admin password for'
        : 'Use this one-time code to reset the Sir Francis admin password for');

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtp_server;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username5;
    $mail->Password = $smtp_password5;
    $mail->SMTPSecure = $smtp_type ?: PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int) $smtp_port;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($smtp_username5, $website_company_name ?: 'Sir Francis Admin');
    if (!empty($smtp_username1)) {
        $mail->addReplyTo($smtp_username1, 'Sir Francis');
    }
    $mail->addAddress($email, $username);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#2c2926;">'
        . '<h2 style="color:#28364B;">' . cbAdminResetText($heading) . '</h2>'
        . '<p>' . cbAdminResetText($intro) . ' <strong>' . cbAdminResetText($username) . '</strong>.</p>'
        . '<div style="font-size:30px;letter-spacing:8px;font-weight:700;background:#f6f1e8;border:1px solid #d8d2c4;padding:18px;text-align:center;">' . cbAdminResetText($otp) . '</div>'
        . '<p>This code expires in 15 minutes. If you did not request this, please ignore this email and check admin access.</p>'
        . '</div>';
    $mail->AltBody = "{$subject} for {$username}: {$otp}. This code expires in 15 minutes.";
    $mail->send();
}

function cbAdminResetFindUserByUsername($conn, $username) {
    $stmt = $conn->prepare("SELECT id, username, email FROM admin_users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $dbUsername, $email);
    $row = null;
    if ($stmt->fetch()) {
        $row = [
            'id' => $id,
            'username' => $dbUsername,
            'email' => $email,
        ];
    }
    $stmt->close();
    return $row ?: null;
}

function cbAdminResetIssueOtp($conn, $username, $mode = 'reset') {
    if (!cbAdminResetEnsureColumns($conn)) {
        throw new RuntimeException('Password access setup could not be prepared.');
    }

    $admin = cbAdminResetFindUserByUsername($conn, $username);
    if (!$admin) {
        return null;
    }

    if (empty($admin['email']) || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
        error_log('Sir Francis admin OTP requested for admin without recovery email: ' . $admin['username']);
        return null;
    }

    $otp = (string) random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiresAt = (new DateTime('+15 minutes', new DateTimeZone('Africa/Johannesburg')))->format('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE admin_users SET reset_otp_hash = ?, reset_otp_expires_at = ?, reset_otp_attempts = 0 WHERE id = ?");
    if (!$stmt) {
        throw new RuntimeException('Could not save the reset code.');
    }

    $adminId = (int) $admin['id'];
    $stmt->bind_param("ssi", $otpHash, $expiresAt, $adminId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Could not save the reset code.');
    }
    $stmt->close();

    cbAdminResetSendOtpEmail($admin['email'], $admin['username'], $otp, $mode);
    return $admin;
}

function cbAdminResetIssueBootstrapOtp($conn, $email) {
    $email = strtolower(trim((string) $email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid email address.');
    }

    if (cbAdminResetAdminCount($conn) > 0 && cbAdminResetRecoverableAdminCount($conn) > 0) {
        return false;
    }

    $otp = (string) random_int(100000, 999999);
    $_SESSION['admin_bootstrap_email'] = $email;
    $_SESSION['admin_bootstrap_otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
    $_SESSION['admin_bootstrap_otp_expires_at'] = time() + (15 * 60);
    $_SESSION['admin_bootstrap_otp_attempts'] = 0;

    cbAdminResetSendOtpEmail($email, $email, $otp, 'bootstrap');
    return true;
}

function cbAdminResetCreateBootstrapAdmin($conn, $email, $password) {
    $email = strtolower(trim((string) $email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('The bootstrap email is invalid.');
    }

    if (cbAdminResetAdminCount($conn) > 0 && cbAdminResetRecoverableAdminCount($conn) > 0) {
        throw new RuntimeException('An admin account with a recovery email already exists.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new RuntimeException('Could not create the first admin account.');
    }

    $stmt->bind_param("sss", $email, $email, $passwordHash);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Could not create the first admin account.');
    }

    $adminId = $stmt->insert_id;
    $stmt->close();
    return $adminId;
}
?>
