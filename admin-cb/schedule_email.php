<?php
include '../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "schedule_email";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';
require_once 'campaign_email_helpers.php';

cbCampaignEnsureTables($conn);

$subscriberCount = 0;
$countResult = $conn->query("SELECT COUNT(*) AS total FROM subscribers WHERE is_subscribed = 1");
if ($countResult && $row = $countResult->fetch_assoc()) {
    $subscriberCount = (int) $row['total'];
}

$broadcastFlash = $_SESSION['broadcast_flash'] ?? null;
unset($_SESSION['broadcast_flash']);

$oldForm = $broadcastFlash['old'] ?? [];
$formValue = function ($name, $default = '') use ($oldForm) {
    return htmlspecialchars((string) ($oldForm[$name] ?? $default), ENT_QUOTES, 'UTF-8');
};
$scheduleParts = function () use ($oldForm) {
    $date = trim((string) ($oldForm['scheduled_date'] ?? ''));
    $time = trim((string) ($oldForm['scheduled_time'] ?? ''));
    if ($date !== '' && $time !== '') {
        return [
            htmlspecialchars($date, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(substr($time, 0, 5), ENT_QUOTES, 'UTF-8')
        ];
    }

    $value = trim((string) ($oldForm['scheduled_at'] ?? ''));
    if ($value === '') {
        return ['', ''];
    }
    $timestamp = strtotime(str_replace('T', ' ', $value));
    if (!$timestamp) {
        return ['', ''];
    }

    return [
        htmlspecialchars(date('Y-m-d', $timestamp), ENT_QUOTES, 'UTF-8'),
        htmlspecialchars(date('H:i', $timestamp), ENT_QUOTES, 'UTF-8')
    ];
};
[$scheduledDateValue, $scheduledTimeValue] = $scheduleParts();
$bodyValue = htmlspecialchars((string) ($oldForm['body'] ?? ''), ENT_QUOTES, 'UTF-8');
$excludeUnsubscribedChecked = !array_key_exists('exclude_unsubscribed_manual', $oldForm) || !empty($oldForm['exclude_unsubscribed_manual']);

include 'header.php';
?>

<title>Broadcast Emails</title>

<style>
    .broadcast-shell { padding: 28px 0; }
    .broadcast-panel { background: #fff; border: 1px solid #e9e2d8; padding: 20px; height: 100%; }
    .broadcast-panel h2, .broadcast-panel h3 { color: #5b1178; }
    .field-help { color: #6d6570; font-size: 13px; margin-top: 4px; }
    .preview-frame { background: #f5f2ea; border: 1px solid #e0d7cc; min-height: 520px; max-height: 760px; overflow: auto; padding: 14px; }
    .status-pill { display: inline-block; background: #f5f2ea; border: 1px solid #e0d7cc; color: #5b1178; padding: 6px 10px; font-size: 13px; font-weight: 700; }
    .button-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .broadcast-alert { border-radius: 6px; margin: 14px 0 18px; }
</style>

<?php include 'page_menues.php'; ?>

<div class="container broadcast-shell">
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="broadcast-panel">
                <h2>Subscriber Broadcast</h2>
                <p class="lead py-2">Create a coupon email, send yourself a test, then schedule it for all subscribed customers. Staff only see this campaign form and the subscriber count.</p>
                <p><span class="status-pill"><?= number_format($subscriberCount) ?> active subscribers</span></p>

                <?php if ($broadcastFlash): ?>
                    <div class="alert broadcast-alert <?= !empty($broadcastFlash['success']) ? 'alert-success' : 'alert-danger' ?>">
                        <?= htmlspecialchars($broadcastFlash['message'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form action="send_email.php" method="post" id="email-form">
                    <div class="form-group">
                        <label for="email_heading">Email heading</label>
                        <input type="text" class="form-control" id="email_heading" name="email_heading" value="<?= $formValue('email_heading') ?>" required>
                        <div class="field-help">Short title shown inside the email, for example "Payday treats are here".</div>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject line</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="<?= $formValue('subject') ?>" required>
                        <div class="field-help">This is what customers see in their inbox. Keep it clear and not too long.</div>
                    </div>

                    <div class="form-group">
                        <label for="coupon_code">Coupon code</label>
                        <input type="text" class="form-control" id="coupon_code" name="coupon_code" value="<?= $formValue('coupon_code') ?>">
                        <div class="field-help">This is shown in a dedicated coupon box. The code must already exist in your coupon sheet before customers can use it.</div>
                    </div>

                    <div class="form-group">
                        <label for="hero_image_url">Picture URL</label>
                        <input type="url" class="form-control" id="hero_image_url" name="hero_image_url" value="<?= $formValue('hero_image_url') ?>">
                        <div class="field-help">Use the image button in the editor to upload, then paste the uploaded image URL here if you want it as the main picture.</div>
                    </div>

                    <div class="form-group">
                        <label for="body">Body text</label>
                        <textarea class="form-control" id="body" name="body" rows="10"><?= $bodyValue ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="cta_label">Button text</label>
                            <input type="text" class="form-control" id="cta_label" name="cta_label" value="<?= $formValue('cta_label', 'Shop now') ?>">
                            <div class="field-help">Change this if you want different button wording.</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="cta_url">Button link</label>
                            <input type="url" class="form-control" id="cta_url" name="cta_url" value="<?= $formValue('cta_url', 'https://www.candybird.co.za/products') ?>">
                            <div class="field-help">Use a full link starting with https://. The default takes customers to the shop.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="scheduled_at">Send date and time</label>
                        <input type="hidden" id="scheduled_at" name="scheduled_at" value="">
                        <div class="form-row">
                            <div class="form-group col-md-6 mb-md-0">
                                <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" value="<?= $scheduledDateValue ?>" autocomplete="off">
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" value="<?= $scheduledTimeValue ?>" autocomplete="off">
                            </div>
                        </div>
                        <div class="field-help">Choose the send date and time in South Africa time. The sender cron will send it when this time is due.</div>
                    </div>

                    <div class="form-group">
                        <label for="manual_recipients">Extra recipient emails</label>
                        <textarea class="form-control" id="manual_recipients" name="manual_recipients" rows="4"><?= htmlspecialchars((string) ($oldForm['manual_recipients'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="field-help">Optional. Add internal client-base emails here, one per line or separated by commas. They will be included with scheduled broadcasts but will not be added to the subscriber database.</div>
                    </div>

                    <div class="form-group">
                        <input type="hidden" name="exclude_unsubscribed_manual" value="0">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="exclude_unsubscribed_manual" name="exclude_unsubscribed_manual" value="1" <?= $excludeUnsubscribedChecked ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="exclude_unsubscribed_manual">Exclude emails that previously unsubscribed</label>
                        </div>
                        <div class="field-help">Recommended. If a custom email is pasted here but that person previously unsubscribed, they will be skipped unless you untick this for an urgent service update.</div>
                    </div>

                    <div class="form-group">
                        <label for="test_email">Test email address</label>
                        <input type="email" class="form-control" id="test_email" name="test_email" value="<?= $formValue('test_email') ?>">
                        <div class="field-help">Use this only for "Send test email". It does not schedule the broadcast.</div>
                    </div>
                    <div class="button-row">
                        <button type="submit" name="action" value="test" class="btn btn-outline-primary">Send test email</button>
                        <button type="submit" name="action" value="schedule" class="btn btn-primary">Schedule broadcast</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="broadcast-panel">
                <h3>Email Preview</h3>
                <div class="preview-frame" id="email-preview"></div>
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

    function updatePreview() {
        var heading = $('#email_heading').val() || 'Campaign heading';
        var subject = $('#subject').val() || 'Subject preview';
        var coupon = ($('#coupon_code').val() || '').toUpperCase();
        var hero = $('#hero_image_url').val();
        var body = tinymce.get('body') ? tinymce.get('body').getContent() : $('#body').val();
        var ctaLabel = $('#cta_label').val() || 'Shop now';
        var ctaUrl = $('#cta_url').val() || 'https://www.candybird.co.za/products';

        body = body.split('{coupon_code}').join(escapeHtml(coupon));

        var heroHtml = hero ? '<tr><td><img src="' + escapeHtml(hero) + '" style="display:block;width:100%;max-width:680px;height:auto;border:0;" alt=""></td></tr>' : '';
        var couponHtml = coupon ? '<table width="100%" style="margin:22px 0;border:2px dashed #5b1178;background:#fbfaf7;"><tr><td align="center" style="padding:18px;"><div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#6b6070;">Coupon code</div><div style="font-size:28px;font-weight:700;color:#5b1178;margin-top:6px;">' + escapeHtml(coupon) + '</div></td></tr></table>' : '';
        var ctaHtml = ctaLabel && ctaUrl ? '<table style="margin-top:22px;"><tr><td style="background:#5b1178;"><a href="' + escapeHtml(ctaUrl) + '" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">' + escapeHtml(ctaLabel) + '</a></td></tr></table>' : '';

        $('#email-preview').html('<table width="100%" style="max-width:680px;background:#fff;border-collapse:collapse;margin:auto;"><tr><td style="background:#5b1178;padding:22px 28px;color:#fcb42f;font-size:21px;font-weight:700;">' + escapeHtml(heading) + '</td></tr>' + heroHtml + '<tr><td style="padding:28px;color:#51475a;font-size:15px;line-height:1.7;">' + body + couponHtml + ctaHtml + '</td></tr><tr><td style="background:#f5f2ea;padding:18px;text-align:center;color:#5b1178;font-size:12px;">' + escapeHtml(subject) + '</td></tr></table>');
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
                        if (!$('#hero_image_url').val()) {
                            $('#hero_image_url').val(response.location);
                        }
                        updatePreview();
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
        paste_data_images: false,
        setup: function(editor) {
            editor.on('init change keyup nodechange', updatePreview);
        }
    });

    function syncScheduledAt() {
        var date = $('#scheduled_date').val();
        var time = $('#scheduled_time').val();
        $('#scheduled_at').val(date && time ? date + 'T' + time : '');
    }

    $('#email_heading, #subject, #coupon_code, #hero_image_url, #cta_label, #cta_url').on('input', updatePreview);
    $('#scheduled_date, #scheduled_time').on('input change blur', syncScheduledAt);
    $('#email-form').on('submit', function() {
        syncScheduledAt();
        if (tinymce.get('body')) {
            tinymce.triggerSave();
        }
    });
    $(document).ready(function() {
        syncScheduledAt();
        updatePreview();
    });
</script>

<?php include '../footer.php'; ?>
