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

    $conn->query("CREATE TABLE IF NOT EXISTS email_scheduler_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_url VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

function cbCampaignCleanHtml($html)
{
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string) $html);
    $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/javascript\s*:/i', '', $html);
    return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><img><h1><h2><h3><h4><blockquote><table><thead><tbody><tr><td><th><span><div>');
}

function cbCampaignPayloadFromPost()
{
    $ctaUrl = trim($_POST['cta_url'] ?? '');
    if ($ctaUrl === '') {
        $ctaUrl = 'https://www.candybird.co.za/products';
    }

    return array(
        'email_heading' => trim($_POST['email_heading'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'coupon_code' => strtoupper(trim($_POST['coupon_code'] ?? '')),
        'hero_image_url' => trim($_POST['hero_image_url'] ?? ''),
        'manual_recipients' => trim($_POST['manual_recipients'] ?? ''),
        'body_html' => cbCampaignCleanHtml($_POST['body'] ?? ''),
        'cta_label' => trim($_POST['cta_label'] ?? 'Shop now'),
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
            'cta_url' => 'https://www.candybird.co.za/products'
        );
    }

    $payload['email_heading'] = $payload['email_heading'] ?? ($row['email_heading'] ?? '');
    $payload['subject'] = $payload['subject'] ?? ($row['subject'] ?? '');
    $payload['manual_recipients'] = $payload['manual_recipients'] ?? '';
    return $payload;
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

function cbCampaignNormalizeEmailKey($email)
{
    return strtolower(trim((string) $email));
}

function cbCampaignBuildRecipientList($subscriberRows, $manualRecipients)
{
    $recipients = [];
    $stats = [
        'subscriber_count' => 0,
        'manual_count' => 0,
        'unique_count' => 0,
        'duplicate_count' => 0,
        'invalid_count' => 0,
        'duplicate_emails' => [],
        'invalid_emails' => [],
    ];

    foreach ($subscriberRows as $row) {
        $email = trim((string) ($row['email'] ?? $row));
        if ($email === '') {
            continue;
        }
        $stats['subscriber_count']++;
        $key = cbCampaignNormalizeEmailKey($email);
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

    return [
        'recipients' => $recipients,
        'stats' => $stats,
    ];
}

function cbCampaignRecipientStatsForSchedule($conn, $manualRecipients)
{
    $subscriberRows = [];
    if ($conn instanceof mysqli) {
        $result = $conn->query("SELECT email FROM subscribers WHERE is_subscribed = 1 AND email <> '' ORDER BY id ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $subscriberRows[] = $row;
            }
        }
    }

    return cbCampaignBuildRecipientList($subscriberRows, $manualRecipients);
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
        $couponBox = '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:22px 0;border:2px dashed #5b1178;background:#fbfaf7;"><tr><td align="center" style="padding:18px;"><div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#6b6070;">Coupon code</div><div style="font-size:28px;font-weight:700;color:#5b1178;margin-top:6px;">' . cbCampaignText($couponCode) . '</div></td></tr></table>';
    }

    $ctaButton = '';
    if (!empty($payload['cta_label']) && !empty($payload['cta_url'])) {
        $trackedCtaUrl = cbCampaignTrackedUrl($payload['cta_url'], $payload);
        $ctaButton = '<table cellpadding="0" cellspacing="0" role="presentation" style="margin-top:22px;"><tr><td style="background:#5b1178;"><a href="' . cbCampaignText($trackedCtaUrl) . '" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">' . cbCampaignText($payload['cta_label']) . '</a></td></tr></table>';
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
        $bcc[$adminCopyEmail] = 'CandyBird Admin';
    }

    $html = cbCampaignRenderEmail($payload, $recipientEmail);
    $alt = trim(strip_tags(str_replace('{coupon_code}', $payload['coupon_code'] ?? '', $payload['body_html'] ?? '')));
    if ($alt === '' && !empty($payload['coupon_code'])) {
        $alt = 'CandyBird coupon code: ' . $payload['coupon_code'];
    }

    $result = cbCandybirdSendMail(
        $recipientEmail,
        $recipientName ?: $recipientEmail,
        $payload['subject'],
        $html,
        [
            'from_name' => 'CandyBird',
            'prefer_mail_transport' => true,
            'reply_to_email' => $smtp_username1 ?? '',
            'reply_to_name' => 'CandyBird',
            'bcc' => $bcc,
            'alt_body' => $alt,
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
        . '<h2 style="color:#5b1178;">' . cbCampaignText($subject) . '</h2>'
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
        'CandyBird Admin',
        $subject,
        $body,
        [
            'from_name' => 'CandyBird',
            'prefer_mail_transport' => true,
            'reply_to_email' => $smtp_username1,
            'reply_to_name' => 'CandyBird',
        ]
    );

    if (empty($result['success'])) {
        throw new Exception($result['error'] ?? 'Campaign admin summary could not be sent.');
    }
}
