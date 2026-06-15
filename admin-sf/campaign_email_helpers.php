<?php
date_default_timezone_set('Africa/Johannesburg');

require_once __DIR__ . '/../candybird_mail_helpers.php';

function cbCampaignText($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbCampaignEnsureTables($conn)
{
    if (!($conn instanceof mysqli)) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        is_subscribed TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_subscriber_email (email)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS scheduled_emails (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email_heading VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body LONGTEXT NOT NULL,
        scheduled_at DATETIME NOT NULL,
        sent TINYINT(1) DEFAULT 0
    )");

    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'created_at', "DATETIME NULL");
    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'updated_at', "DATETIME NULL");
    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'sent_at', "DATETIME NULL");
    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'scheduled_recipient_count', "INT DEFAULT 0");
    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'sent_success_count', "INT DEFAULT 0");
    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'sent_failed_count', "INT DEFAULT 0");
    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'recipient_stats_json', "LONGTEXT NULL");
    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'recipient_snapshot_json', "LONGTEXT NULL");
    cbCampaignEnsureColumn($conn, 'scheduled_emails', 'failure_summary', "LONGTEXT NULL");

    $conn->query("CREATE TABLE IF NOT EXISTS email_scheduler_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_url VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS email_recipient_lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        purpose VARCHAR(255) DEFAULT '',
        source_note VARCHAR(255) DEFAULT '',
        emails LONGTEXT NOT NULL,
        email_count INT DEFAULT 0,
        created_by_admin_id INT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    cbCampaignEnsureColumn($conn, 'email_recipient_lists', 'purpose', "VARCHAR(255) DEFAULT ''");
    cbCampaignEnsureColumn($conn, 'email_recipient_lists', 'source_note', "VARCHAR(255) DEFAULT ''");
    cbCampaignEnsureColumn($conn, 'email_recipient_lists', 'email_count', "INT DEFAULT 0");
    cbCampaignEnsureColumn($conn, 'email_recipient_lists', 'created_by_admin_id', "INT NULL");
    cbCampaignEnsureColumn($conn, 'email_recipient_lists', 'created_at', "DATETIME DEFAULT CURRENT_TIMESTAMP");
    cbCampaignEnsureColumn($conn, 'email_recipient_lists', 'updated_at', "DATETIME DEFAULT CURRENT_TIMESTAMP");
}

function cbCampaignEnsureColumn($conn, $table, $column, $definition)
{
    if (!($conn instanceof mysqli)) {
        return;
    }
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
    if ($safeTable === '' || $safeColumn === '') {
        return;
    }

    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    if ($result && $result->num_rows > 0) {
        return;
    }
    $conn->query("ALTER TABLE `{$safeTable}` ADD COLUMN `{$safeColumn}` {$definition}");
}

function cbCampaignCleanHtml($html)
{
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string) $html);
    $html = preg_replace('#<img\b[^>]*\bsrc\s*=\s*["\']?\s*data:[^>]*>#i', '', $html);
    $html = preg_replace('#\s(src|href)\s*=\s*["\']?\s*data:[^"\'\s>]+["\']?#i', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/javascript\s*:/i', '', $html);
    return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><img><h1><h2><h3><h4><blockquote><table><thead><tbody><tr><td><th><span><div>');
}

function cbCampaignCleanHeaderText($value)
{
    $value = preg_replace('/[\r\n]+/', ' ', (string) $value);
    $value = preg_replace('/[^\P{C}\t ]/u', '', $value);
    return trim($value);
}

function cbCampaignPayloadFromPost()
{
    $ctaUrl = trim($_POST['cta_url'] ?? '');
    if ($ctaUrl === '') {
        $ctaUrl = 'https://www.fishgelatine.co.za/v2/products';
    }

    $attachments = cbCampaignExistingAttachmentsFromPost();

    return array(
        'email_heading' => cbCampaignCleanHeaderText($_POST['email_heading'] ?? ''),
        'subject' => cbCampaignCleanHeaderText($_POST['subject'] ?? ''),
        'coupon_code' => strtoupper(cbCampaignCleanHeaderText($_POST['coupon_code'] ?? '')),
        'hero_image_url' => trim($_POST['hero_image_url'] ?? ''),
        'attachments' => $attachments,
        'recipient_mode' => ($_POST['recipient_mode'] ?? 'subscribers_plus_custom') === 'custom_only' ? 'custom_only' : 'subscribers_plus_custom',
        'manual_recipients' => trim($_POST['manual_recipients'] ?? ''),
        'exclude_unsubscribed_manual' => !empty($_POST['exclude_unsubscribed_manual']) ? 1 : 0,
        'body_html' => cbCampaignCleanHtml($_POST['body'] ?? ''),
        'cta_label' => cbCampaignCleanHeaderText($_POST['cta_label'] ?? 'Shop now'),
        'cta_url' => $ctaUrl,
        'created_by_admin_id' => isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null,
        'created_at' => date('Y-m-d H:i:s')
    );
}

function cbCampaignPayloadFromScheduledEmail($row)
{
    $payload = json_decode($row['body'] ?? '', true);
    if (!is_array($payload)) {
        $payload = array(
            'email_heading' => $row['email_heading'] ?? '',
            'subject' => $row['subject'] ?? '',
            'coupon_code' => '',
            'hero_image_url' => '',
            'manual_recipients' => '',
            'body_html' => cbCampaignCleanHtml($row['body'] ?? ''),
            'cta_label' => 'Shop now',
            'cta_url' => 'https://www.fishgelatine.co.za/v2/products'
        );
    }

    $payload['email_heading'] = $payload['email_heading'] ?? ($row['email_heading'] ?? '');
    $payload['subject'] = $payload['subject'] ?? ($row['subject'] ?? '');
    $payload['manual_recipients'] = $payload['manual_recipients'] ?? '';
    $payload['attachments'] = cbCampaignNormalizeAttachments($payload['attachments'] ?? []);
    $payload['recipient_mode'] = ($payload['recipient_mode'] ?? 'subscribers_plus_custom') === 'custom_only' ? 'custom_only' : 'subscribers_plus_custom';
    $payload['exclude_unsubscribed_manual'] = array_key_exists('exclude_unsubscribed_manual', $payload) ? (int) $payload['exclude_unsubscribed_manual'] : 1;
    return $payload;
}

function cbCampaignPostFromPayload($payload, $scheduledAt = '')
{
    $timestamp = $scheduledAt !== '' ? strtotime((string) $scheduledAt) : false;
    return [
        'email_heading' => $payload['email_heading'] ?? '',
        'subject' => $payload['subject'] ?? '',
        'coupon_code' => $payload['coupon_code'] ?? '',
        'hero_image_url' => $payload['hero_image_url'] ?? '',
        'attachments' => cbCampaignNormalizeAttachments($payload['attachments'] ?? []),
        'recipient_mode' => ($payload['recipient_mode'] ?? 'subscribers_plus_custom') === 'custom_only' ? 'custom_only' : 'subscribers_plus_custom',
        'body' => $payload['body_html'] ?? '',
        'cta_label' => $payload['cta_label'] ?? 'Shop now',
        'cta_url' => $payload['cta_url'] ?? 'https://www.fishgelatine.co.za/v2/products',
        'manual_recipients' => $payload['manual_recipients'] ?? '',
        'exclude_unsubscribed_manual' => array_key_exists('exclude_unsubscribed_manual', $payload) ? (int) $payload['exclude_unsubscribed_manual'] : 1,
        'scheduled_at' => $timestamp ? date('Y-m-d\TH:i', $timestamp) : '',
        'scheduled_date' => $timestamp ? date('Y-m-d', $timestamp) : '',
        'scheduled_time' => $timestamp ? date('H:i', $timestamp) : '',
    ];
}

function cbCampaignAttachmentDirectory()
{
    return __DIR__ . '/uploads/broadcast_attachments';
}

function cbCampaignNormalizeAttachments($attachments)
{
    $clean = [];
    foreach ((array) $attachments as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }
        $path = trim((string) ($attachment['path'] ?? ''));
        $name = cbCampaignCleanHeaderText($attachment['name'] ?? basename($path));
        if ($path === '' || $name === '') {
            continue;
        }
        $clean[] = [
            'path' => $path,
            'name' => $name,
            'size' => isset($attachment['size']) ? (int) $attachment['size'] : 0,
            'type' => cbCampaignCleanHeaderText($attachment['type'] ?? ''),
        ];
    }
    return $clean;
}

function cbCampaignExistingAttachmentsFromPost()
{
    $attachments = json_decode((string) ($_POST['existing_attachments_json'] ?? '[]'), true);
    $attachments = cbCampaignNormalizeAttachments(is_array($attachments) ? $attachments : []);
    $keep = array_flip((array) ($_POST['keep_attachment'] ?? []));
    if (empty($attachments)) {
        return [];
    }

    $kept = [];
    foreach ($attachments as $attachment) {
        $key = sha1($attachment['path'] . '|' . $attachment['name']);
        if (isset($keep[$key])) {
            $kept[] = $attachment;
        }
    }
    return $kept;
}

function cbCampaignHandleAttachmentUploads($attachments = [])
{
    $attachments = cbCampaignNormalizeAttachments($attachments);
    if (empty($_FILES['attachments']) || empty($_FILES['attachments']['name'])) {
        return $attachments;
    }

    $allowedExtensions = ['pdf', 'xls', 'xlsx', 'csv'];
    $maxFileSize = 10 * 1024 * 1024;
    $maxTotalSize = 20 * 1024 * 1024;
    $dir = cbCampaignAttachmentDirectory();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new Exception('Could not create the broadcast attachment folder.');
    }

    $totalSize = 0;
    foreach ($attachments as $attachment) {
        $totalSize += (int) ($attachment['size'] ?? 0);
    }

    $names = (array) ($_FILES['attachments']['name'] ?? []);
    $tmpNames = (array) ($_FILES['attachments']['tmp_name'] ?? []);
    $errors = (array) ($_FILES['attachments']['error'] ?? []);
    $sizes = (array) ($_FILES['attachments']['size'] ?? []);
    $types = (array) ($_FILES['attachments']['type'] ?? []);

    foreach ($names as $index => $originalName) {
        $originalName = (string) $originalName;
        if ($originalName === '') {
            continue;
        }
        $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new Exception('Attachment upload failed for ' . cbCampaignCleanHeaderText($originalName) . '.');
        }

        $size = (int) ($sizes[$index] ?? 0);
        if ($size <= 0 || $size > $maxFileSize) {
            throw new Exception('Each attachment must be 10MB or smaller.');
        }
        $totalSize += $size;
        if ($totalSize > $maxTotalSize) {
            throw new Exception('All broadcast attachments together must be 20MB or smaller.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception('Only PDF, XLS, XLSX and CSV attachments are allowed.');
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9._-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
        $safeBase = trim((string) $safeBase, '-._');
        if ($safeBase === '') {
            $safeBase = 'broadcast-file';
        }
        $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '-' . $safeBase . '.' . $extension;
        $target = $dir . '/' . $fileName;
        if (!move_uploaded_file((string) ($tmpNames[$index] ?? ''), $target)) {
            throw new Exception('Could not save attachment ' . cbCampaignCleanHeaderText($originalName) . '.');
        }

        $attachments[] = [
            'path' => $target,
            'name' => cbCampaignCleanHeaderText($originalName),
            'size' => $size,
            'type' => cbCampaignCleanHeaderText($types[$index] ?? ''),
        ];
    }

    return cbCampaignNormalizeAttachments($attachments);
}

function cbCampaignFetchScheduledEmail($conn, $id)
{
    if (!($conn instanceof mysqli)) {
        return null;
    }
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM scheduled_emails WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function cbCampaignParseManualRecipients($value)
{
    $parts = preg_split('/[\s,;]+/', (string) $value);
    $emails = [];

    foreach ($parts as $part) {
        $email = trim($part);
        if ($email === '') {
            continue;
        }
        $emails[] = strtolower($email);
    }

    return array_values(array_unique($emails));
}

function cbCampaignValidEmailList($value)
{
    $valid = [];
    $invalid = [];
    foreach (cbCampaignParseManualRecipients($value) as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid[$email] = $email;
        } else {
            $invalid[$email] = $email;
        }
    }

    return [
        'valid' => array_values($valid),
        'invalid' => array_values($invalid),
    ];
}

function cbCampaignNormalizeEmailKey($email)
{
    return strtolower(trim((string) $email));
}

function cbCampaignGetUnsubscribedEmailKeys($conn)
{
    $keys = [];
    if (!($conn instanceof mysqli)) {
        return $keys;
    }

    $result = $conn->query("SELECT email FROM subscribers WHERE is_subscribed = 0 AND email <> ''");
    if (!$result) {
        return $keys;
    }
    while ($row = $result->fetch_assoc()) {
        $key = cbCampaignNormalizeEmailKey($row['email'] ?? '');
        if ($key !== '') {
            $keys[$key] = true;
        }
    }
    return $keys;
}

function cbCampaignBuildRecipientList($subscriberRows, $manualRecipients, $unsubscribedEmailKeys = [])
{
    $recipients = [];
    $stats = [
        'subscriber_count' => 0,
        'manual_count' => 0,
        'unique_count' => 0,
        'duplicate_count' => 0,
        'invalid_count' => 0,
        'unsubscribed_count' => 0,
        'duplicate_emails' => [],
        'invalid_emails' => [],
        'unsubscribed_emails' => [],
    ];

    foreach ($subscriberRows as $row) {
        $email = trim((string) ($row['email'] ?? $row));
        if ($email === '') {
            continue;
        }
        $stats['subscriber_count']++;
        $key = cbCampaignNormalizeEmailKey($email);
        if (!empty($unsubscribedEmailKeys[$key])) {
            $stats['unsubscribed_count']++;
            $stats['unsubscribed_emails'][] = $email;
            continue;
        }
        if (isset($recipients[$key])) {
            $stats['duplicate_count']++;
            $stats['duplicate_emails'][] = $email;
            continue;
        }
        $recipients[$key] = $email;
    }

    foreach ($manualRecipients as $email) {
        $email = trim((string) $email);
        if ($email === '') {
            continue;
        }
        $stats['manual_count']++;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stats['invalid_count']++;
            $stats['invalid_emails'][] = $email;
            continue;
        }
        $key = cbCampaignNormalizeEmailKey($email);
        if (isset($recipients[$key])) {
            $stats['duplicate_count']++;
            $stats['duplicate_emails'][] = $email;
            continue;
        }
        $recipients[$key] = $email;
    }

    $stats['unique_count'] = count($recipients);
    $stats['duplicate_emails'] = array_values(array_unique(array_map('strtolower', $stats['duplicate_emails'])));
    $stats['invalid_emails'] = array_values(array_unique($stats['invalid_emails']));
    $stats['unsubscribed_emails'] = array_values(array_unique(array_map('strtolower', $stats['unsubscribed_emails'])));

    return [
        'recipients' => $recipients,
        'stats' => $stats,
    ];
}

function cbCampaignRecipientStatsForSchedule($conn, $manualRecipients, $excludeUnsubscribedManual = true, $recipientMode = 'subscribers_plus_custom')
{
    $subscriberRows = [];
    if ($recipientMode !== 'custom_only' && $conn instanceof mysqli) {
        $result = $conn->query("SELECT email FROM subscribers WHERE is_subscribed = 1 AND email <> '' ORDER BY id ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $subscriberRows[] = $row;
            }
        }
    }

    $unsubscribedKeys = $excludeUnsubscribedManual ? cbCampaignGetUnsubscribedEmailKeys($conn) : [];
    return cbCampaignBuildRecipientList($subscriberRows, $manualRecipients, $unsubscribedKeys);
}

function cbCampaignValidatePayload($payload)
{
    $errors = array();
    if ($payload['email_heading'] === '') {
        $errors[] = 'Add an email heading.';
    }
    if ($payload['subject'] === '') {
        $errors[] = 'Add a subject line.';
    }
    if (trim(strip_tags($payload['body_html'])) === '' && $payload['coupon_code'] === '') {
        $errors[] = 'Add body text or a coupon code.';
    }
    if ($payload['hero_image_url'] !== '' && !filter_var($payload['hero_image_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Use a full image URL, starting with https://.';
    }
    if ($payload['cta_url'] !== '' && !filter_var($payload['cta_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Use a full button URL, starting with https://.';
    }
    if (stripos((string) ($payload['body_html'] ?? ''), 'data:') !== false) {
        $errors[] = 'Please upload pictures with the editor image button instead of pasting images directly into the body.';
    }
    if (strlen((string) ($payload['body_html'] ?? '')) > 250000) {
        $errors[] = 'The email body is too large. Use image links or uploaded pictures instead of embedded image data.';
    }
    foreach (cbCampaignNormalizeAttachments($payload['attachments'] ?? []) as $attachment) {
        if (!is_file($attachment['path'])) {
            $errors[] = 'One of the selected attachments is missing. Please re-upload it.';
            break;
        }
    }
    if (($payload['recipient_mode'] ?? 'subscribers_plus_custom') === 'custom_only' && empty(cbCampaignParseManualRecipients($payload['manual_recipients'] ?? ''))) {
        $errors[] = 'Add at least one custom email address, or switch the audience back to subscribers plus custom emails.';
    }
    foreach (cbCampaignParseManualRecipients($payload['manual_recipients'] ?? '') as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Check this extra recipient email: ' . $email;
            break;
        }
    }
    return $errors;
}

function cbCampaignRenderEmail($payload, $unsubscribeEmail = '')
{
    $template = file_get_contents(__DIR__ . '/../emails/email_campaign_broadcast.php');
    $couponCode = trim($payload['coupon_code'] ?? '');
    $body = str_replace('{coupon_code}', cbCampaignText($couponCode), $payload['body_html'] ?? '');

    $heroImage = '';
    if (!empty($payload['hero_image_url'])) {
        $heroImage = '<tr><td><img src="' . cbCampaignText($payload['hero_image_url']) . '" alt="" width="680" style="display:block;width:100%;max-width:680px;height:auto;border:0;"></td></tr>';
    }

    $couponBox = '';
    if ($couponCode !== '') {
        $couponBox = '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:22px 0;border:2px dashed #28364B;background:#fbfaf7;"><tr><td align="center" style="padding:18px;"><div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#6b6070;">Coupon code</div><div style="font-size:28px;font-weight:700;color:#28364B;margin-top:6px;">' . cbCampaignText($couponCode) . '</div></td></tr></table>';
    }

    $ctaButton = '';
    if (!empty($payload['cta_label']) && !empty($payload['cta_url'])) {
        $trackedCtaUrl = cbCampaignTrackedUrl($payload['cta_url'], $payload);
        $ctaButton = '<table cellpadding="0" cellspacing="0" role="presentation" style="margin-top:22px;"><tr><td style="background:#28364B;"><a href="' . cbCampaignText($trackedCtaUrl) . '" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">' . cbCampaignText($payload['cta_label']) . '</a></td></tr></table>';
    }

    return strtr($template, array(
        '{subject}' => cbCampaignText($payload['subject'] ?? ''),
        '{email_heading}' => cbCampaignText($payload['email_heading'] ?? ''),
        '{hero_image}' => $heroImage,
        '{body}' => $body,
        '{coupon_box}' => $couponBox,
        '{cta_button}' => $ctaButton,
        '{year}' => date('Y'),
        '{user_email_unsubscribe}' => urlencode((string) $unsubscribeEmail)
    ));
}

function cbCampaignTrackedUrl($url, $payload)
{
    $url = trim((string) $url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }

    $params = [
        'utm_source' => 'email',
        'utm_medium' => 'broadcast',
        'utm_campaign' => preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($payload['coupon_code'] ?? $payload['subject'] ?? 'campaign')),
    ];

    if (!empty($payload['campaign_id'])) {
        $params['cb_campaign'] = (int) $payload['campaign_id'];
    }

    if (!empty($payload['coupon_code'])) {
        $params['cb_coupon'] = strtoupper((string) $payload['coupon_code']);
    }

    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . http_build_query($params);
}

function cbCampaignSendEmail($recipientEmail, $recipientName, $payload)
{
    global $smtp_username1;

    $adminCopyEmail = trim((string) ($payload['admin_copy_email'] ?? ''));
    if ($adminCopyEmail === '' && !empty($payload['send_admin_copy'])) {
        $adminCopyEmail = (string) ($smtp_username1 ?? '');
    }

    $bcc = [];
    if ($adminCopyEmail !== '' && filter_var($adminCopyEmail, FILTER_VALIDATE_EMAIL) && strcasecmp($adminCopyEmail, $recipientEmail) !== 0) {
        $bcc[$adminCopyEmail] = 'Sir Francis Admin';
    }

    $html = cbCampaignRenderEmail($payload, $recipientEmail);
    $alt = trim(strip_tags(str_replace('{coupon_code}', $payload['coupon_code'] ?? '', $payload['body_html'] ?? '')));
    if ($alt === '' && !empty($payload['coupon_code'])) {
        $alt = 'Sir Francis coupon code: ' . $payload['coupon_code'];
    }

    $result = cbCandybirdSendMail(
        $recipientEmail,
        $recipientName ?: $recipientEmail,
        $payload['subject'],
        $html,
        [
            'from_name' => 'Sir Francis',
            'prefer_mail_transport' => true,
            'reply_to_email' => $smtp_username1 ?? '',
            'reply_to_name' => 'Sir Francis',
            'bcc' => $bcc,
            'alt_body' => $alt,
            'attachments' => cbCampaignNormalizeAttachments($payload['attachments'] ?? []),
        ]
    );

    if (empty($result['success'])) {
        throw new Exception($result['error'] ?? 'Campaign email could not be sent.');
    }
}

function cbCampaignSendAdminSummary($subject, $message, $payload, $stats = array())
{
    global $smtp_username1;

    if (empty($smtp_username1)) {
        return;
    }

    $statsHtml = '';
    foreach ($stats as $label => $value) {
        $statsHtml .= '<tr><td style="padding:6px 0;color:#555555;">' . cbCampaignText($label) . '</td><td align="right" style="padding:6px 0;font-weight:700;">' . cbCampaignText($value) . '</td></tr>';
    }

    $body = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:680px;margin:0 auto;color:#2b2230;">'
        . '<h2 style="color:#28364B;">' . cbCampaignText($subject) . '</h2>'
        . '<p>' . cbCampaignText($message) . '</p>'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e8e1d7;border-bottom:1px solid #e8e1d7;margin:16px 0;">' . $statsHtml . '</table>'
        . '<h3>Subject</h3><p>' . cbCampaignText($payload['subject'] ?? '') . '</p>'
        . '<h3>Heading</h3><p>' . cbCampaignText($payload['email_heading'] ?? '') . '</p>'
        . '<h3>Coupon</h3><p>' . cbCampaignText($payload['coupon_code'] ?? 'None') . '</p>'
        . '<h3>Email preview</h3>'
        . cbCampaignRenderEmail($payload, $smtp_username1)
        . '</div>';

    $result = cbCandybirdSendMail(
        $smtp_username1,
        'Sir Francis Admin',
        $subject,
        $body,
        [
            'from_name' => 'Sir Francis',
            'prefer_mail_transport' => true,
            'reply_to_email' => $smtp_username1,
            'reply_to_name' => 'Sir Francis',
        ]
    );

    if (empty($result['success'])) {
        throw new Exception($result['error'] ?? 'Campaign admin summary could not be sent.');
    }
}
