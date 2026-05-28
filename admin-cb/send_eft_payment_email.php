<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please sign in as admin first.']);
    exit;
}

include 'dbh.inc.php';

use PHPMailer\PHPMailer\PHPMailer;

require '../PHPMailer/PHPMailer/src/PHPMailer.php';
require '../PHPMailer/PHPMailer/src/Exception.php';
require '../PHPMailer/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../candybird_mail_helpers.php';

$liveConfigPath = '/home/candybirdco/configs_candybird/candybird_config.php';
if (file_exists($liveConfigPath)) {
    require_once $liveConfigPath;
} elseif (file_exists(__DIR__ . '/../configs/email_config.php')) {
    require_once __DIR__ . '/../configs/email_config.php';
}

function cbEftEmailText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$orderId = (int) ($_POST['order_id'] ?? 0);
if ($orderId <= 0 || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Order could not be loaded.']);
    exit;
}

$settings = [];
$settingsResult = $conn->query("SELECT * FROM admin_website_settings ORDER BY id ASC LIMIT 1");
if ($settingsResult) {
    $settings = $settingsResult->fetch_assoc() ?: [];
}

$stmt = $conn->prepare("
    SELECT
        o.id,
        o.grand_total_amount,
        COALESCE(u.email, ua.billing_email_address) AS customer_email,
        COALESCE(NULLIF(ua.billing_first_name, ''), u.username, 'Customer') AS customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN user_addresses ua ON o.guest_identifier = ua.guest_identifier OR o.user_id = ua.user_id
    WHERE o.id = ?
    GROUP BY o.id
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Order lookup could not be prepared.']);
    exit;
}

$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || empty($order['customer_email'])) {
    echo json_encode(['success' => false, 'message' => 'This order does not have a customer email address.']);
    exit;
}

$bankingDetails = trim(strip_tags((string) ($settings['banking_details'] ?? '')));
if ($bankingDetails === '') {
    echo json_encode(['success' => false, 'message' => 'Banking details are not set in Website Settings.']);
    exit;
}

$orderNumber = str_pad((string) $orderId, 7, '0', STR_PAD_LEFT);
$total = 'R' . number_format((float) $order['grand_total_amount'], 2);
$customerName = trim((string) $order['customer_name']) ?: 'Customer';

$messageHtml = nl2br(cbEftEmailText(
    "Thank you for placing an order with us.\n\n" .
    "Kindly use the banking details below for EFT payments, or if you prefer a secure payment link let me know.\n\n" .
    $bankingDetails . "\n\n" .
    "Total: " . $total . "\n\n" .
    "Once payment is received we will dispatch your order.\n\n" .
    "Warm Regards\nCandyBird Team."
));

$body = file_get_contents('../emails/email_order_update.php');
$body = str_replace('{recipient_name}', cbEftEmailText($customerName), $body);
$body = str_replace('{user_email_unsubscribe}', cbEftEmailText($order['customer_email']), $body);
$body = str_replace('{order_id}', cbEftEmailText($orderNumber), $body);
$body = str_replace('{order_status}', 'Awaiting EFT payment', $body);
$body = str_replace('{custom_message}', $messageHtml, $body);

try {
    $mailResult = cbCandybirdSendMail(
        $order['customer_email'],
        $customerName,
        'EFT payment details for Order ' . $orderNumber . ' | CandyBird',
        $body
    );
    if (empty($mailResult['success'])) {
        error_log('CandyBird EFT email failed for order ' . $orderNumber . ': ' . ($mailResult['error'] ?? 'unknown error'));
        echo json_encode(['success' => false, 'message' => 'EFT email could not be sent right now. The exact SMTP error has been logged.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'EFT payment email sent to the customer.']);
} catch (Throwable $e) {
    error_log('CandyBird EFT email exception for order ' . $orderNumber . ': ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'EFT email could not be sent right now. The exact SMTP error has been logged.']);
}
?>
