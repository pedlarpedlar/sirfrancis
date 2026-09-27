<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("email_test"));
    exit();
}

include 'header.php';
include 'dbh.inc.php';

require_once __DIR__ . '/../PHPMailer/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../candybird_mail_helpers.php';

function sfAdminEmailTestText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sfAdminEmailTestDefaultRecipient($conn) {
    cbCandybirdLoadMailConfig();
    $emails = [];
    if ($conn instanceof mysqli) {
        $result = $conn->query("SELECT support_email, email_1, email_2 FROM admin_website_settings ORDER BY id ASC LIMIT 1");
        if ($result) {
            $row = $result->fetch_assoc() ?: [];
            foreach (['support_email', 'email_1', 'email_2'] as $field) {
                $email = trim((string) ($row[$field] ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $email;
                }
            }
            $result->free();
        }
    }

    foreach (['smtp_username1', 'smtp_username5', 'smtp_username3'] as $globalName) {
        $email = trim((string) ($GLOBALS[$globalName] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $email;
        }
    }

    return $emails[0] ?? '';
}

$testResult = null;
$recipientEmail = trim((string) ($_POST['recipient_email'] ?? sfAdminEmailTestDefaultRecipient($conn)));
$recipientName = trim((string) ($_POST['recipient_name'] ?? 'Sir Francis Admin'));
$accounts = cbCandybirdMailAccounts();
$smtpHost = trim((string) ($GLOBALS['smtp_server'] ?? ''));
$smtpPort = trim((string) ($GLOBALS['smtp_port'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $safeHost = sfAdminEmailTestText($_SERVER['HTTP_HOST'] ?? 'sirfrancis.co.za');
    $sentAt = date('Y-m-d H:i:s');
    $subject = 'Sir Francis email test - ' . $sentAt;
    $body = '
        <div style="margin:0;background:#f7f4ec;padding:24px;font-family:Arial,sans-serif;color:#28364B;">
            <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #d8c895;">
                <div style="background:#172235;padding:22px;text-align:center;">
                    <img src="https://www.sirfrancis.co.za/assets/img/logo/logo-gold.png" alt="Sir Francis" style="max-width:180px;height:auto;">
                </div>
                <div style="padding:24px;">
                    <h1 style="margin:0 0 12px;font-size:24px;color:#172235;">Email delivery test</h1>
                    <p style="font-size:15px;line-height:1.6;">This message was sent from the Sir Francis admin email tester.</p>
                    <table style="width:100%;border-collapse:collapse;margin-top:18px;font-size:14px;">
                        <tr><td style="padding:8px;border-top:1px solid #eee;font-weight:bold;">Website</td><td style="padding:8px;border-top:1px solid #eee;">' . $safeHost . '</td></tr>
                        <tr><td style="padding:8px;border-top:1px solid #eee;font-weight:bold;">Sent at</td><td style="padding:8px;border-top:1px solid #eee;">' . sfAdminEmailTestText($sentAt) . '</td></tr>
                        <tr><td style="padding:8px;border-top:1px solid #eee;font-weight:bold;">Purpose</td><td style="padding:8px;border-top:1px solid #eee;">Checking the same mail helper used by order, payment and status emails.</td></tr>
                    </table>
                    <p style="font-size:13px;color:#75675d;margin-top:22px;">If this arrives but order notifications do not, the SMTP setup works and the next check is the order/payment notification flow.</p>
                </div>
            </div>
        </div>';

    $testResult = cbCandybirdSendMail(
        $recipientEmail,
        $recipientName !== '' ? $recipientName : $recipientEmail,
        $subject,
        $body,
        [
            'from_name' => 'Sir Francis',
            'alt_body' => "Sir Francis email delivery test\nSent at: {$sentAt}\nWebsite: " . ($_SERVER['HTTP_HOST'] ?? 'sirfrancis.co.za'),
        ]
    );
}

include 'page_menues.php';
?>

<title>Email Test - Sir Francis</title>

<style>
    .email-test-shell { padding: 30px 0 70px; }
    .email-test-hero { background:#172235; color:#fff; padding:24px; margin-bottom:18px; }
    .email-test-hero h1 { color:#d6c27a; margin:0 0 8px; }
    .email-test-card { background:#fff; border:1px solid #e8ded2; padding:22px; margin-bottom:18px; }
    .email-test-card label { color:#172235; font-weight:800; }
    .email-test-status { display:grid; gap:12px; grid-template-columns:repeat(3,minmax(0,1fr)); }
    .email-test-status div { background:#f8f5ee; border:1px solid #e8ded2; padding:14px; }
    .email-test-status strong { color:#172235; display:block; }
    @media (max-width: 767px) { .email-test-status { grid-template-columns:1fr; } }
</style>

<div class="container email-test-shell">
    <div class="email-test-hero">
        <h1>Email Test</h1>
        <p class="mb-0">Send a real test email through the same Sir Francis mail helper used by order confirmations, payment notices and status updates.</p>
    </div>

    <?php if (is_array($testResult)): ?>
        <?php if (!empty($testResult['success'])): ?>
            <div class="alert alert-success">
                Test email sent to <?= sfAdminEmailTestText($recipientEmail) ?> using <?= sfAdminEmailTestText($testResult['sender'] ?? 'configured mail account') ?><?= !empty($testResult['transport']) ? ' via ' . sfAdminEmailTestText($testResult['transport']) : '' ?>.
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                Test email failed: <?= sfAdminEmailTestText($testResult['error'] ?? 'Unknown mail error') ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="email-test-card">
        <form method="post">
            <div class="form-group">
                <label for="recipient_email">Send test to</label>
                <input type="email" class="form-control" id="recipient_email" name="recipient_email" value="<?= sfAdminEmailTestText($recipientEmail) ?>" required>
                <small class="form-text text-muted">Use the address that should receive order and paid-order notifications.</small>
            </div>
            <div class="form-group">
                <label for="recipient_name">Recipient name</label>
                <input type="text" class="form-control" id="recipient_name" name="recipient_name" value="<?= sfAdminEmailTestText($recipientName) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Send Test Email</button>
        </form>
    </div>

    <div class="email-test-card">
        <h4>Current Mail Setup</h4>
        <div class="email-test-status">
            <div>
                <strong>SMTP host</strong>
                <?= $smtpHost !== '' ? sfAdminEmailTestText($smtpHost) : 'Missing' ?>
            </div>
            <div>
                <strong>SMTP port</strong>
                <?= $smtpPort !== '' ? sfAdminEmailTestText($smtpPort) : 'Missing' ?>
            </div>
            <div>
                <strong>Configured senders</strong>
                <?= count($accounts) ?>
            </div>
        </div>
        <p class="text-muted mt-3 mb-0">A successful test proves the mail credentials can send. If paid orders still do not notify after this succeeds, the problem is likely inside the checkout or PayFast notification path.</p>
    </div>
</div>

<?php include 'footer.php'; ?>
