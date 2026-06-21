<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: text/plain; charset=UTF-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Please submit the contact form.';
    exit;
}

$rootDir = __DIR__;
$liveConfigPath = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: dirname(__DIR__)), '/') . '/configs_sirfrancis/sirfrancis_config.php';
if (is_file($liveConfigPath)) {
    require_once $liveConfigPath;
}
if (is_file($rootDir . '/dbh.inc.php')) {
    require_once $rootDir . '/dbh.inc.php';
}

$mailerFiles = [
    $rootDir . '/PHPMailer/PHPMailer/src/PHPMailer.php',
    $rootDir . '/PHPMailer/PHPMailer/src/Exception.php',
    $rootDir . '/PHPMailer/PHPMailer/src/SMTP.php',
];

foreach ($mailerFiles as $mailerFile) {
    if (!is_file($mailerFile)) {
        http_response_code(500);
        echo 'Message could not be sent right now. Please email us directly.';
        exit;
    }
    require_once $mailerFile;
}

function cbContactCleanLine($value) {
    $value = trim((string) $value);
    $value = strip_tags($value);
    return preg_replace('/[\r\n\t]+/', ' ', $value);
}

function cbContactHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbContactFirstValidEmail(array $values) {
    foreach ($values as $value) {
        $email = trim((string) $value);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
    }
    return '';
}

function cbContactFindSmtpAccount() {
    foreach ([5, 1, 3, 4, 2] as $index) {
        $userVar = 'smtp_username' . $index;
        $passVar = 'smtp_password' . $index;
        $email = trim((string) ($GLOBALS[$userVar] ?? ''));
        $password = (string) ($GLOBALS[$passVar] ?? '');
        if ($email !== '' && $password !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $email, 'password' => $password];
        }
    }

    $legacyEmail = cbContactFirstValidEmail([
        $GLOBALS['smtp_username'] ?? '',
        $GLOBALS['smtp_username5'] ?? '',
        $GLOBALS['smtp_username1'] ?? '',
    ]);
    $legacyPassword = (string) ($GLOBALS['smtp_password'] ?? '');
    if ($legacyEmail !== '' && $legacyPassword !== '') {
        return ['email' => $legacyEmail, 'password' => $legacyPassword];
    }

    return null;
}

function cbContactEnsureAttemptsTable($conn) {
    if (!($conn instanceof mysqli) || $conn->connect_error) {
        return false;
    }
    return $conn->query("CREATE TABLE IF NOT EXISTS contact_form_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(64) NOT NULL,
        email VARCHAR(255) NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        reason VARCHAR(80) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_contact_attempt_ip_time (ip_address, created_at),
        KEY idx_contact_attempt_email_time (email, created_at)
    )") !== false;
}

function cbContactLogAttempt($conn, $ip, $email, $success, $reason) {
    if (!cbContactEnsureAttemptsTable($conn)) {
        return;
    }
    $stmt = $conn->prepare("INSERT INTO contact_form_attempts (ip_address, email, success, reason) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $successInt = $success ? 1 : 0;
        $stmt->bind_param("ssis", $ip, $email, $successInt, $reason);
        $stmt->execute();
        $stmt->close();
    }
}

function cbContactTooManyAttempts($conn, $ip, $email) {
    if (!cbContactEnsureAttemptsTable($conn)) {
        return false;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS attempts FROM contact_form_attempts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND (ip_address = ? OR (email <> '' AND email = ?))");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ss", $ip, $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['attempts'] ?? 0) >= 5;
}

function cbContactVerifyRecaptcha($secret, $response, $remoteIp, $type = 'v3', $expectedAction = 'contact_form') {
    $secret = trim((string) $secret);
    $response = trim((string) $response);
    if ($secret === '' || $response === '') {
        return false;
    }

    $payload = http_build_query([
        'secret' => $secret,
        'response' => $response,
        'remoteip' => $remoteIp,
    ]);

    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
            ],
        ]);
        $body = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    }

    $decoded = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($decoded) || empty($decoded['success'])) {
        return false;
    }

    if ($type === 'v3') {
        $score = isset($decoded['score']) ? (float) $decoded['score'] : 0;
        $action = (string) ($decoded['action'] ?? '');
        return $score >= 0.3 && ($action === '' || $action === $expectedAction);
    }

    return true;
}

$name = cbContactCleanLine($_POST['name'] ?? '');
$emailInput = trim((string) ($_POST['email'] ?? ''));
$email = filter_var($emailInput, FILTER_VALIDATE_EMAIL);
$subjectInput = cbContactCleanLine($_POST['subject'] ?? '');
$message = trim((string) ($_POST['contactMessage'] ?? ''));
$messagePlain = trim(strip_tags($message));
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
$emailForRateLimit = filter_var($emailInput, FILTER_VALIDATE_EMAIL) ? strtolower(trim($emailInput)) : '';

if (trim((string) ($_POST['website_url'] ?? '')) !== '') {
    cbContactLogAttempt($conn ?? null, $remoteIp, $emailForRateLimit, false, 'honeypot');
    http_response_code(400);
    echo 'Message could not be sent. Please email us directly.';
    exit;
}

$postedStartedAt = (int) ($_POST['contact_started_at'] ?? 0);
$sessionStartedAt = (int) ($_SESSION['contact_form_started_at'] ?? 0);
$startedAt = $sessionStartedAt > 0 ? $sessionStartedAt : $postedStartedAt;
if ($startedAt <= 0 || time() - $startedAt < 3 || time() - $startedAt > 7200) {
    cbContactLogAttempt($conn ?? null, $remoteIp, $emailForRateLimit, false, 'timing');
    http_response_code(400);
    echo 'Please refresh the contact page and try again.';
    exit;
}

if (cbContactTooManyAttempts($conn ?? null, $remoteIp, $emailForRateLimit)) {
    cbContactLogAttempt($conn ?? null, $remoteIp, $emailForRateLimit, false, 'rate_limit');
    http_response_code(429);
    echo 'Too many messages were sent recently. Please try again later or email us directly.';
    exit;
}

if ($name === '' || !$email || $messagePlain === '') {
    cbContactLogAttempt($conn ?? null, $remoteIp, $emailForRateLimit, false, 'required_fields');
    http_response_code(400);
    echo 'Please add your name, email address, and message.';
    exit;
}

$websiteCompanyName = 'Sir Francis';
$primaryRecipient = '';
$supportRecipient = '';
$secondaryRecipient = '';
$recaptchaEnabled = false;
$recaptchaSecretKey = '';
$recaptchaType = 'v3';

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $recaptchaColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'contact_recaptcha_secret_key'");
    $recaptchaTypeColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'contact_recaptcha_type'");
    $settingsColumns = $recaptchaColumnCheck && $recaptchaColumnCheck->num_rows > 0
        ? ($recaptchaTypeColumnCheck && $recaptchaTypeColumnCheck->num_rows > 0
            ? "website_company_name, email_1, email_2, support_email, contact_recaptcha_enabled, contact_recaptcha_type, contact_recaptcha_secret_key"
            : "website_company_name, email_1, email_2, support_email, contact_recaptcha_enabled, contact_recaptcha_secret_key")
        : "website_company_name, email_1, email_2, support_email";
    $settingsResult = $conn->query("SELECT {$settingsColumns} FROM admin_website_settings LIMIT 1");
    if ($settingsResult && ($settings = $settingsResult->fetch_assoc())) {
        $websiteCompanyName = trim((string) ($settings['website_company_name'] ?? '')) ?: $websiteCompanyName;
        $primaryRecipient = trim((string) ($settings['email_1'] ?? ''));
        $secondaryRecipient = trim((string) ($settings['email_2'] ?? ''));
        $supportRecipient = trim((string) ($settings['support_email'] ?? ''));
        $recaptchaEnabled = !empty($settings['contact_recaptcha_enabled']);
        $recaptchaType = in_array($settings['contact_recaptcha_type'] ?? 'v3', ['v3', 'v2_checkbox'], true) ? $settings['contact_recaptcha_type'] : 'v3';
        $recaptchaSecretKey = trim((string) ($settings['contact_recaptcha_secret_key'] ?? ''));
    }
}

if ($recaptchaEnabled && $recaptchaSecretKey !== '') {
    $recaptchaResponse = (string) ($_POST['g-recaptcha-response'] ?? '');
    $recaptchaAction = (string) ($_POST['contact_recaptcha_action'] ?? 'contact_form');
    if (!cbContactVerifyRecaptcha($recaptchaSecretKey, $recaptchaResponse, $remoteIp, $recaptchaType, $recaptchaAction)) {
        cbContactLogAttempt($conn ?? null, $remoteIp, $emailForRateLimit, false, 'recaptcha');
        http_response_code(400);
        echo $recaptchaType === 'v2_checkbox'
            ? 'Please complete the security checkbox and try again.'
            : 'The automatic security check could not verify this message. Please refresh the page and try again, or email us directly.';
        exit;
    }
}

$recipient = cbContactFirstValidEmail([
    $supportRecipient,
    $primaryRecipient,
    $support_email ?? '',
    $website_email ?? '',
    $smtp_username1 ?? '',
    'info@sirfrancis.co.za',
]);
$smtpAccount = cbContactFindSmtpAccount();
$smtpHost = trim((string) ($smtp_server ?? ''));
$smtpPort = (int) ($smtp_port ?? 0);

if ($smtpHost === '' || $smtpPort <= 0 || empty($smtpAccount) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    error_log('Sir Francis contact form email settings missing. host=' . ($smtpHost !== '' ? 'yes' : 'no') . '; port=' . ($smtpPort > 0 ? 'yes' : 'no') . '; account=' . (!empty($smtpAccount) ? 'yes' : 'no') . '; recipient=' . ($recipient !== '' ? $recipient : 'none'));
    http_response_code(500);
    echo 'Message could not be sent because email settings are not available. Please email us directly.';
    exit;
}

$safeSubject = $subjectInput !== '' ? $subjectInput : 'Website contact message';
$pageUrl = $_SERVER['HTTP_REFERER'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$submittedAt = date('Y-m-d H:i:s');

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpAccount['email'];
    $mail->Password = $smtpAccount['password'];
    if (!empty($smtp_type)) {
        $mail->SMTPSecure = $smtp_type;
    }
    $mail->Port = $smtpPort;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($smtpAccount['email'], $websiteCompanyName . ' Website');
    $mail->addAddress($recipient, $websiteCompanyName . ' Support');
    if (filter_var($secondaryRecipient, FILTER_VALIDATE_EMAIL) && strcasecmp($secondaryRecipient, $recipient) !== 0) {
        $mail->addCC($secondaryRecipient);
    }
    $mail->addReplyTo($email, $name);
    $mail->isHTML(true);
    $mail->Subject = $websiteCompanyName . ' contact form: ' . $safeSubject;
    $mail->Body = '<div style="font-family:Arial,sans-serif;background:#f7f2ea;padding:24px;color:#2a211c;">'
        . '<div style="max-width:640px;margin:auto;background:#ffffff;border:1px solid #eadfd2;border-radius:10px;overflow:hidden;">'
        . '<div style="background:#2a1b1b;color:#ffffff;padding:18px 22px;">'
        . '<h2 style="margin:0;font-size:22px;">New website message</h2>'
        . '<p style="margin:6px 0 0;color:#f6d9b0;">' . cbContactHtml($websiteCompanyName) . '</p>'
        . '</div>'
        . '<div style="padding:22px;">'
        . '<p><strong>Name:</strong> ' . cbContactHtml($name) . '</p>'
        . '<p><strong>Email:</strong> <a href="mailto:' . cbContactHtml($email) . '">' . cbContactHtml($email) . '</a></p>'
        . '<p><strong>Subject:</strong> ' . cbContactHtml($safeSubject) . '</p>'
        . '<div style="margin:18px 0;padding:16px;background:#fbfaf7;border:1px solid #eee1d4;border-radius:8px;line-height:1.7;">'
        . nl2br(cbContactHtml($messagePlain))
        . '</div>'
        . '<p style="font-size:12px;color:#6f625a;"><strong>Submitted:</strong> ' . cbContactHtml($submittedAt) . '</p>'
        . '<p style="font-size:12px;color:#6f625a;"><strong>Page:</strong> ' . cbContactHtml($pageUrl) . '</p>'
        . '<p style="font-size:12px;color:#6f625a;"><strong>IP:</strong> ' . cbContactHtml($remoteIp) . '</p>'
        . '<p style="font-size:12px;color:#6f625a;"><strong>Browser:</strong> ' . cbContactHtml($userAgent) . '</p>'
        . '</div></div></div>';
    $mail->AltBody = "New website message\n\nName: {$name}\nEmail: {$email}\nSubject: {$safeSubject}\n\n{$messagePlain}\n\nSubmitted: {$submittedAt}\nPage: {$pageUrl}\nIP: {$remoteIp}";
    $mail->send();

    cbContactLogAttempt($conn ?? null, $remoteIp, $emailForRateLimit, true, 'sent');
    unset($_SESSION['contact_form_started_at']);
    echo 'Thank you. Your message has been sent.';
} catch (Exception $e) {
    error_log('Sir Francis contact form email failed: ' . $e->getMessage());
    cbContactLogAttempt($conn ?? null, $remoteIp, $emailForRateLimit, false, 'mailer');
    http_response_code(500);
    echo 'Message could not be sent right now. Please email us directly.';
}
