<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/dbh.inc.php';
include_once __DIR__ . '/log_action_function.php';
require_once __DIR__ . '/product_sheet_helpers.php';
require_once __DIR__ . '/candybird_mail_helpers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/PHPMailer/PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer/src/SMTP.php';
}

$liveConfig = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: dirname(__DIR__)), '/') . '/configs_sirfrancis/sirfrancis_config.php';
if (file_exists($liveConfig)) {
    require_once $liveConfig;
}

header('Content-Type: application/json');

function cbSubscribeJson($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit;
}

function cbSubscribeEnsureTable($conn) {
    if ($conn instanceof mysqli) {
        $conn->query("CREATE TABLE IF NOT EXISTS subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            is_subscribed TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            subscribed_at TIMESTAMP NULL DEFAULT NULL,
            unsubscribed_at TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY unique_subscriber_email (email)
        )");
        foreach ([
            'subscribed_at' => "ALTER TABLE subscribers ADD COLUMN subscribed_at TIMESTAMP NULL DEFAULT NULL",
            'unsubscribed_at' => "ALTER TABLE subscribers ADD COLUMN unsubscribed_at TIMESTAMP NULL DEFAULT NULL",
        ] as $column => $alterSql) {
            $safeColumn = $conn->real_escape_string($column);
            $check = $conn->query("SHOW COLUMNS FROM subscribers LIKE '{$safeColumn}'");
            if ($check && $check->num_rows === 0) {
                $conn->query($alterSql);
            }
        }
    }
}

function cbSubscribeCouponValidityText($coupon) {
    if (!$coupon) {
        return '';
    }

    $from = trim((string) ($coupon['valid_from'] ?? ''));
    $until = trim((string) ($coupon['valid_until'] ?? ''));

    if ($from !== '' && $until !== '') {
        return 'Valid from ' . $from . ' until ' . $until . '.';
    }
    if ($until !== '') {
        return 'Valid until ' . $until . '.';
    }
    if ($from !== '') {
        return 'Valid from ' . $from . '.';
    }

    return 'Valid while the coupon remains active on the Sir Francis coupon sheet.';
}

function cbSubscribeCouponOfferText($coupon, $couponCode) {
    if (!$coupon) {
        return '';
    }

    $discountType = strtolower(trim((string) ($coupon['discount_type'] ?? 'percentage')));
    $discountValue = (float) ($coupon['discount_value'] ?? 0);
    $minOrder = (float) ($coupon['min_order_value'] ?? 0);

    if ($discountType === 'fixed' || $discountType === 'amount' || $discountType === 'currency') {
        $offer = 'Use code ' . $couponCode . ' for R' . number_format($discountValue, 2) . ' off';
    } else {
        $offer = 'Use code ' . $couponCode . ' for ' . rtrim(rtrim(number_format($discountValue, 2), '0'), '.') . '% off';
    }

    if ($minOrder > 0) {
        $offer .= ' orders over R' . number_format($minOrder, 2);
    }

    return $offer . '.';
}

function cbSubscribeTemplate($file, $fallback) {
    $path = __DIR__ . '/emails/' . $file;
    return is_file($path) ? (string) file_get_contents($path) : $fallback;
}

function cbSubscribeReplaceTokens($body, $email, $couponCode = '', $coupon = null) {
    $couponOffer = cbSubscribeCouponOfferText($coupon, $couponCode);
    $couponValidity = cbSubscribeCouponValidityText($coupon);
    if ($couponCode === '') {
        $couponOffer = 'Your subscription is confirmed. We will send procurement updates, product news and wholesale announcements as they become available.';
        $couponValidity = '';
    }

    return str_replace(
        ['{user_email_unsubscribe}', '{coupon_code}', '{coupon_offer}', '{coupon_validity}', '{recipient_name}', '{user_email}'],
        [urlencode($email), htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8'), htmlspecialchars($couponOffer, ENT_QUOTES, 'UTF-8'), htmlspecialchars($couponValidity, ENT_QUOTES, 'UTF-8'), 'Admin', htmlspecialchars($email, ENT_QUOTES, 'UTF-8')],
        $body
    );
}

function cbSubscribeAdminRecipient() {
    global $conn, $support_email, $smtp_username1, $website_email;

    $recipient = $support_email ?: ($smtp_username1 ?? ($website_email ?? ''));
    if ($conn instanceof mysqli) {
        $result = $conn->query("SELECT support_email, email_1 FROM admin_website_settings LIMIT 1");
        if ($result && ($row = $result->fetch_assoc())) {
            $recipient = trim((string) ($row['support_email'] ?: $row['email_1'] ?: $recipient));
        }
    }

    return filter_var($recipient, FILTER_VALIDATE_EMAIL) ? $recipient : '';
}

function cbSubscribeSendEmails($email, $couponCode = '', $coupon = null, $alreadySubscribed = false) {
    $customerBody = cbSubscribeReplaceTokens(
        cbSubscribeTemplate('email_subscribe.php', '<p>Thank you for subscribing to Sir Francis.</p>'),
        $email,
        $couponCode,
        $coupon
    );
    $adminBody = cbSubscribeReplaceTokens(
        cbSubscribeTemplate('email_subscribe_admin.php', '<p>A user subscribed: {user_email}</p>'),
        $email,
        $couponCode,
        $coupon
    );

    $customerResult = cbCandybirdSendMail(
        $email,
        $email,
        $alreadySubscribed ? 'Your Sir Francis subscription is active' : 'Welcome to Sir Francis updates',
        $customerBody,
        ['from_name' => 'Sir Francis', 'prefer_mail_transport' => true]
    );

    if (empty($customerResult['success'])) {
        error_log('Sir Francis subscriber customer email failed for ' . $email . ': ' . ($customerResult['error'] ?? 'unknown error'));
    }

    $adminRecipient = cbSubscribeAdminRecipient();
    $adminResult = ['success' => false, 'error' => 'No admin recipient configured.'];
    if ($adminRecipient !== '') {
        $adminResult = cbCandybirdSendMail(
            $adminRecipient,
            'Sir Francis Admin',
            $alreadySubscribed ? 'Existing Sir Francis subscriber requested confirmation' : 'New Sir Francis subscriber',
            $adminBody,
            ['from_name' => 'Sir Francis Website', 'reply_to_email' => $email, 'reply_to_name' => $email, 'prefer_mail_transport' => true]
        );
        if (empty($adminResult['success'])) {
            error_log('Sir Francis subscriber admin email failed for ' . $email . ': ' . ($adminResult['error'] ?? 'unknown error'));
        }
    } else {
        error_log('Sir Francis subscriber admin email skipped: no admin/support recipient configured.');
    }

    return [
        'customer' => $customerResult,
        'admin' => $adminResult,
    ];
}

function cbSubscribeAdminMailDebug($mailResults) {
    if (empty($_SESSION['admin_id'])) {
        return [];
    }

    return [
        'mail_debug' => [
            'customer_sent' => !empty($mailResults['customer']['success']),
            'customer_error' => $mailResults['customer']['error'] ?? '',
            'customer_sender' => $mailResults['customer']['sender'] ?? '',
            'admin_sent' => !empty($mailResults['admin']['success']),
            'admin_error' => $mailResults['admin']['error'] ?? '',
            'admin_sender' => $mailResults['admin']['sender'] ?? '',
        ],
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cbSubscribeJson(false, 'Invalid request.');
}

if (!($conn instanceof mysqli)) {
    cbSubscribeJson(false, 'Subscription is temporarily unavailable. Please try again shortly.');
}

cbSubscribeEnsureTable($conn);

$email = strtolower(trim((string) ($_POST['email'] ?? $_POST['subscribe_email'] ?? '')));
$source = trim((string) ($_POST['source'] ?? 'footer'));
$couponCode = strtoupper(trim((string) ($_POST['coupon_code'] ?? 'SUBSCRIBENOW')));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    cbSubscribeJson(false, 'Please enter a valid email address.');
}

$existingSubscribed = false;
$existingId = null;
$stmt = $conn->prepare("SELECT id, is_subscribed FROM subscribers WHERE email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($subscriberId, $subscriberIsSubscribed);
    if ($stmt->fetch()) {
        $existingId = (int) $subscriberId;
        $existingSubscribed = (int) $subscriberIsSubscribed === 1;
    }
    $stmt->close();
}

if ($existingSubscribed) {
    $coupon = getSheetCouponByCode($couponCode);
    $couponValid = $coupon ? validateSheetCoupon($coupon, true) : ['valid' => false];
    $couponIsValid = !empty($couponValid['valid']);
    $mailResults = cbSubscribeSendEmails($email, $couponIsValid ? $couponCode : '', $couponIsValid ? $coupon : null, true);
    cbSubscribeJson(true, 'You are already subscribed. Thank you!', [
        'already_subscribed' => true,
        'coupon_available' => false,
    ] + cbSubscribeAdminMailDebug($mailResults));
}

if ($existingId) {
    $stmt = $conn->prepare("UPDATE subscribers SET is_subscribed = 1, subscribed_at = NOW(), unsubscribed_at = NULL WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $existingId);
    }
} else {
    $stmt = $conn->prepare("INSERT INTO subscribers (email, is_subscribed, subscribed_at) VALUES (?, 1, NOW())");
    if ($stmt) {
        $stmt->bind_param('s', $email);
    }
}

if (!$stmt || !$stmt->execute()) {
    cbSubscribeJson(false, 'Subscription could not be saved. Please try again.');
}
$stmt->close();

if (function_exists('logAction')) {
    logAction('Subscriber Added', 'Email: ' . $email . ' Source: ' . $source, null, $_SESSION['guest_identifier'] ?? null);
}

$coupon = getSheetCouponByCode($couponCode);
$couponValid = $coupon ? validateSheetCoupon($coupon, true) : ['valid' => false];
$couponIsValid = !empty($couponValid['valid']);

$mailResults = cbSubscribeSendEmails($email, $couponIsValid ? $couponCode : '', $couponIsValid ? $coupon : null);

cbSubscribeJson(true, 'Thank you for subscribing!', [
    'already_subscribed' => false,
    'coupon_available' => $couponIsValid,
    'coupon_code' => $couponIsValid ? $couponCode : '',
    'coupon_message' => $couponIsValid ? cbSubscribeCouponOfferText($coupon, $couponCode) . ' ' . cbSubscribeCouponValidityText($coupon) : 'Your subscription is saved. The welcome coupon is not active yet.',
] + cbSubscribeAdminMailDebug($mailResults));
?>
