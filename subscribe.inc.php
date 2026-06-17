<?php
include_once __DIR__ . '/dbh.inc.php';
include_once __DIR__ . '/log_action_function.php';
require_once __DIR__ . '/product_sheet_helpers.php';

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
            UNIQUE KEY unique_subscriber_email (email)
        )");
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

function cbSubscribeSendEmails($email, $couponCode = '', $coupon = null) {
    global $smtp_server, $smtp_username1, $smtp_username5, $smtp_password5, $smtp_password, $smtp_type, $smtp_port;

    if (!class_exists(PHPMailer::class) || empty($smtp_server) || empty($smtp_username5) || (empty($smtp_password5) && empty($smtp_password))) {
        return;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username5;
        $mail->Password = $smtp_password5 ?? $smtp_password;
        if (!empty($smtp_type)) {
            $mail->SMTPSecure = $smtp_type;
        }
        $mail->Port = (int) ($smtp_port ?? 587);
        $mail->setFrom($smtp_username5, 'Sir Francis');
        $mail->addAddress($email);
        $mail->Subject = "Hooray! You've just subscribed to Sir Francis's mailing list";
        $body = is_file(__DIR__ . '/emails/email_subscribe.php') ? file_get_contents(__DIR__ . '/emails/email_subscribe.php') : 'Thank you for subscribing to Sir Francis.';
        $body = str_replace('{user_email_unsubscribe}', urlencode($email), $body);
        $body = str_replace('{coupon_code}', htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8'), $body);
        $body = str_replace('{coupon_offer}', htmlspecialchars(cbSubscribeCouponOfferText($coupon, $couponCode), ENT_QUOTES, 'UTF-8'), $body);
        $body = str_replace('{coupon_validity}', htmlspecialchars(cbSubscribeCouponValidityText($coupon), ENT_QUOTES, 'UTF-8'), $body);
        $mail->Body = $body;
        $mail->isHTML(true);
        $mail->send();
    } catch (Throwable $e) {
        error_log('Subscriber email failed: ' . $e->getMessage());
    }

    try {
        if (empty($smtp_username1)) {
            return;
        }
        $adminMail = new PHPMailer(true);
        $adminMail->isSMTP();
        $adminMail->Host = $smtp_server;
        $adminMail->SMTPAuth = true;
        $adminMail->Username = $smtp_username5;
        $adminMail->Password = $smtp_password5 ?? $smtp_password;
        if (!empty($smtp_type)) {
            $adminMail->SMTPSecure = $smtp_type;
        }
        $adminMail->Port = (int) ($smtp_port ?? 587);
        $adminMail->setFrom($smtp_username5, 'Sir Francis');
        $adminMail->addAddress($smtp_username1, 'Admin');
        $adminMail->Subject = 'New Sir Francis subscriber';
        $body = is_file(__DIR__ . '/emails/email_subscribe_admin.php') ? file_get_contents(__DIR__ . '/emails/email_subscribe_admin.php') : 'A user subscribed: {user_email}';
        $body = str_replace('{recipient_name}', 'Admin', $body);
        $body = str_replace('{user_email}', $email, $body);
        $adminMail->Body = $body;
        $adminMail->isHTML(true);
        $adminMail->send();
    } catch (Throwable $e) {
        error_log('Subscriber admin email failed: ' . $e->getMessage());
    }
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
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $existingId = (int) $row['id'];
        $existingSubscribed = (int) $row['is_subscribed'] === 1;
    }
    $stmt->close();
}

if ($existingSubscribed) {
    cbSubscribeJson(true, 'You are already subscribed. Thank you!', [
        'already_subscribed' => true,
        'coupon_available' => false,
    ]);
}

if ($existingId) {
    $stmt = $conn->prepare("UPDATE subscribers SET is_subscribed = 1 WHERE id = ?");
    $stmt->bind_param('i', $existingId);
} else {
    $stmt = $conn->prepare("INSERT INTO subscribers (email, is_subscribed) VALUES (?, 1)");
    $stmt->bind_param('s', $email);
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

cbSubscribeSendEmails($email, $couponIsValid ? $couponCode : '', $couponIsValid ? $coupon : null);

cbSubscribeJson(true, 'Thank you for subscribing!', [
    'already_subscribed' => false,
    'coupon_available' => $couponIsValid,
    'coupon_code' => $couponIsValid ? $couponCode : '',
    'coupon_message' => $couponIsValid ? cbSubscribeCouponOfferText($coupon, $couponCode) . ' ' . cbSubscribeCouponValidityText($coupon) : 'Your subscription is saved. The welcome coupon is not active yet.',
]);
?>
