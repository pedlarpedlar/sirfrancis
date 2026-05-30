<?php
include '../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login?redirect=' . urlencode('customer_email'));
    exit();
}

include 'dbh.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer/src/SMTP.php';

$liveConfigPath = '/home/candybirdco/configs_candybird/candybird_config.php';
if (file_exists($liveConfigPath)) {
    require_once $liveConfigPath;
} elseif (file_exists(__DIR__ . '/../configs/email_config.php')) {
    require_once __DIR__ . '/../configs/email_config.php';
}

require_once 'campaign_email_helpers.php';

function cbCustomerEmailText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbCustomerEmailAdminCopyAddress($conn) {
    global $smtp_username1;

    $fallback = trim((string) ($smtp_username1 ?? ''));
    if (!($conn instanceof mysqli)) {
        return $fallback;
    }

    $result = $conn->query("SELECT support_email, email_1 FROM admin_website_settings LIMIT 1");
    if ($result && ($row = $result->fetch_assoc())) {
        foreach (['support_email', 'email_1'] as $field) {
            $email = trim((string) ($row[$field] ?? ''));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }
    }

    return $fallback;
}

$recipientEmail = trim((string) ($_POST['recipient_email'] ?? $_GET['email'] ?? ''));
$recipientName = trim((string) ($_POST['recipient_name'] ?? $_GET['name'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? 'A message from CandyBird'));
$heading = trim((string) ($_POST['email_heading'] ?? 'A message from CandyBird'));
$body = (string) ($_POST['body'] ?? '<p>Hi {customer_name},</p><p>We hope you are well.</p>');
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please add a valid customer email address.';
    } elseif ($subject === '' || trim(strip_tags($body)) === '') {
        $message = 'Please add a subject and message.';
    } else {
        try {
            $personalBody = str_replace('{customer_name}', cbCampaignText($recipientName ?: 'there'), $body);
            $payload = [
                'email_heading' => $heading ?: $subject,
                'subject' => $subject,
                'coupon_code' => '',
                'hero_image_url' => '',
                'manual_recipients' => '',
                'body_html' => cbCampaignCleanHtml($personalBody),
                'cta_label' => trim((string) ($_POST['cta_label'] ?? '')),
                'cta_url' => trim((string) ($_POST['cta_url'] ?? '')),
                'send_admin_copy' => true,
                'admin_copy_email' => cbCustomerEmailAdminCopyAddress($conn),
                'created_by_admin_id' => (int) ($_SESSION['admin_id'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            cbCampaignSendEmail($recipientEmail, $recipientName ?: $recipientEmail, $payload);
            $success = true;
            $message = 'Email sent to ' . $recipientEmail . '.';
        } catch (Exception $e) {
            $message = 'Email could not be sent: ' . $e->getMessage();
        }
    }
}

include 'header.php';
include 'page_menues.php';
?>

<title>Send Customer Email</title>

<style>
    .customer-email-shell { padding: 28px 0 60px; }
    .customer-email-panel { background:#fff; border:1px solid #eadfd2; border-radius:8px; padding:20px; }
    .customer-email-panel h1 { color:#5b1178; }
    .customer-email-help { color:#6d6270; font-size:13px; }
    .preview-frame { background:#f5f2ea; border:1px solid #e0d7cc; min-height:420px; overflow:auto; padding:14px; }
</style>

<div class="container customer-email-shell">
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="customer-email-panel">
                <h1>Send Email To Customer</h1>
                <p class="customer-email-help">Use this for one customer only. Their name and email are pre-filled from the customer profile where available. A copy is sent to admin automatically.</p>
                <?php if ($message): ?>
                    <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>"><?= cbCustomerEmailText($message) ?></div>
                <?php endif; ?>
                <form method="post" id="customerEmailForm">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Customer name</label>
                            <input type="text" class="form-control" name="recipient_name" id="recipientName" value="<?= cbCustomerEmailText($recipientName) ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Recipient email</label>
                            <input type="email" class="form-control" name="recipient_email" id="recipientEmail" value="<?= cbCustomerEmailText($recipientEmail) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email heading</label>
                        <input type="text" class="form-control" name="email_heading" id="emailHeading" value="<?= cbCustomerEmailText($heading) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" class="form-control" name="subject" id="emailSubject" value="<?= cbCustomerEmailText($subject) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Body</label>
                        <textarea class="form-control" name="body" id="body" rows="10"><?= cbCustomerEmailText($body) ?></textarea>
                        <div class="customer-email-help">Use {customer_name} where the customer name should appear.</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Optional button text</label>
                            <input type="text" class="form-control" name="cta_label" id="ctaLabel" value="<?= cbCustomerEmailText($_POST['cta_label'] ?? '') ?>">
                        </div>
                        <div class="form-group col-md-7">
                            <label>Optional button link</label>
                            <input type="url" class="form-control" name="cta_url" id="ctaUrl" value="<?= cbCustomerEmailText($_POST['cta_url'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap" style="gap:10px;">
                        <button class="btn btn-primary" type="submit">Send email</button>
                        <a class="btn btn-outline-secondary" href="manage_users">Back to customers</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="customer-email-panel">
                <h2 class="h4">Preview</h2>
                <div class="preview-frame" id="emailPreview"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.tiny.cloud/1/krc3t31hewwxmxp9ymcfecueza73p98zly4l51k8zm5ngjy8/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function(match) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[match];
    });
}
function updateCustomerEmailPreview() {
    var name = $('#recipientName').val() || 'there';
    var heading = $('#emailHeading').val() || 'A message from CandyBird';
    var subject = $('#emailSubject').val() || 'Subject preview';
    var body = tinymce.get('body') ? tinymce.get('body').getContent() : $('#body').val();
    var ctaLabel = $('#ctaLabel').val();
    var ctaUrl = $('#ctaUrl').val();
    body = body.split('{customer_name}').join(escapeHtml(name));
    var ctaHtml = ctaLabel && ctaUrl ? '<table style="margin-top:22px;"><tr><td style="background:#5b1178;"><a href="' + escapeHtml(ctaUrl) + '" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">' + escapeHtml(ctaLabel) + '</a></td></tr></table>' : '';
    $('#emailPreview').html('<table width="100%" style="max-width:680px;background:#fff;border-collapse:collapse;margin:auto;"><tr><td style="background:#5b1178;padding:22px 28px;color:#fcb42f;font-size:21px;font-weight:700;">' + escapeHtml(heading) + '</td></tr><tr><td style="padding:28px;color:#51475a;font-size:15px;line-height:1.7;">' + body + ctaHtml + '</td></tr><tr><td style="background:#f5f2ea;padding:18px;text-align:center;color:#5b1178;font-size:12px;">' + escapeHtml(subject) + '</td></tr></table>');
}
tinymce.init({
    selector: '#body',
    plugins: 'image link lists table',
    toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image table',
    images_upload_handler: function(blobInfo, success, failure) {
        var formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());

        $.ajax({
            type: 'POST',
            url: 'rich_editor_upload_image.php',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.location) {
                    success(response.location);
                    updateCustomerEmailPreview();
                } else {
                    failure(response.error || 'Invalid upload response');
                }
            },
            error: function() {
                failure('Upload error');
            }
        });
    },
    image_dimensions: false,
    paste_data_images: true,
    setup: function(editor) {
        editor.on('init change keyup nodechange', updateCustomerEmailPreview);
    }
});
$('#recipientName, #emailHeading, #emailSubject, #ctaLabel, #ctaUrl').on('input', updateCustomerEmailPreview);
$('#customerEmailForm').on('submit', function() {
    if (tinymce.get('body')) {
        tinymce.triggerSave();
    }
});
$(document).ready(updateCustomerEmailPreview);
</script>

<?php include '../footer.php'; ?>
