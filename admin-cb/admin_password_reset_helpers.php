<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function cbAdminResetEnsureColumns($conn) {
    if (!($conn instanceof mysqli)) {
        return false;
    }

    $columns = [
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

function cbAdminResetText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbAdminResetLoadMailer() {
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/SMTP.php';
}

function cbAdminResetSendOtpEmail($email, $username, $otp) {
    global $smtp_server, $smtp_port, $smtp_type, $smtp_username1, $smtp_username5, $smtp_password5, $website_company_name;

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('This admin user does not have a valid recovery email address.');
    }

    if (empty($smtp_server) || empty($smtp_port) || empty($smtp_username5) || empty($smtp_password5)) {
        throw new Exception('SMTP settings are missing. Please check the CandyBird mail configuration.');
    }

    cbAdminResetLoadMailer();

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtp_server;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username5;
    $mail->Password = $smtp_password5;
    $mail->SMTPSecure = $smtp_type ?: PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int) $smtp_port;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($smtp_username5, $website_company_name ?: 'CandyBird Admin');
    if (!empty($smtp_username1)) {
        $mail->addReplyTo($smtp_username1, 'CandyBird');
    }
    $mail->addAddress($email, $username);
    $mail->isHTML(true);
    $mail->Subject = 'CandyBird admin password reset OTP';
    $mail->Body = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#2c2926;">'
        . '<h2 style="color:#5b1178;">Admin password reset</h2>'
        . '<p>Use this one-time code to reset the CandyBird admin password for <strong>' . cbAdminResetText($username) . '</strong>.</p>'
        . '<div style="font-size:30px;letter-spacing:8px;font-weight:700;background:#f6f1e8;border:1px solid #eadfd2;padding:18px;text-align:center;">' . cbAdminResetText($otp) . '</div>'
        . '<p>This code expires in 15 minutes. If you did not request this, please ignore this email and check admin access.</p>'
        . '</div>';
    $mail->AltBody = "CandyBird admin password reset OTP for {$username}: {$otp}. This code expires in 15 minutes.";
    $mail->send();
}

function cbAdminResetFindUserByUsername($conn, $username) {
    $stmt = $conn->prepare("SELECT id, username, email FROM admin_users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}
?>
