<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to resend order emails.']);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/dbh.inc.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../candybird_mail_helpers.php';
require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/admin_order_totals.php';

function cbResendOrderEmailText($value) {
    return nl2br(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
}

function cbResendOrderEmailMoney($amount) {
    return 'R' . number_format((float) $amount, 2);
}

function cbResendOrderEmailFetch($conn, $orderId) {
    $stmt = $conn->prepare("
        SELECT
            o.*,
            pm.label AS payment_method_label,
            COALESCE(NULLIF(TRIM(CONCAT(ua.billing_first_name, ' ', ua.billing_last_name)), ''), u.username, 'Customer') AS customer_name,
            COALESCE(NULLIF(ua.billing_first_name, ''), u.username, 'Customer') AS first_name,
            COALESCE(NULLIF(u.email, ''), NULLIF(ua.billing_email_address, '')) AS customer_email,
            ua.billing_phone_number,
            ua.billing_email_address
        FROM orders o
        LEFT JOIN payment_methods pm ON o.payment_method = pm.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN user_addresses ua ON (
            (o.user_id IS NOT NULL AND ua.user_id = o.user_id)
            OR (o.guest_identifier <> '' AND ua.guest_identifier = o.guest_identifier)
        )
        WHERE o.id = ?
        GROUP BY o.id
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $order ?: null;
}

function cbResendOrderEmailItemsHtml($conn, $orderId, $orderDate = null) {
    ensureCandybirdOrderItemSnapshotColumns($conn);
    $stmt = $conn->prepare("
        SELECT product_id, clearance_id, product_title, product_image_url, product_weight, clearance_note, quantity, price, discount_amount, tax_amount
        FROM order_items
        WHERE order_id = ?
        ORDER BY id ASC
    ");
    if (!$stmt) {
        return ['html' => '', 'product_discount' => 0];
    }

    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $html = '';
    $productDiscount = 0;

    while ($item = $result->fetch_assoc()) {
        $productId = (int) ($item['product_id'] ?? 0);
        $clearanceId = strtoupper(trim((string) ($item['clearance_id'] ?? '')));
        $sheetProduct = null;
        if ($clearanceId !== '') {
            $clearanceRow = getSheetClearanceRowById($clearanceId);
            $sheetProduct = $clearanceRow ? buildCandybirdClearanceProduct($clearanceRow) : null;
        }
        if (!$sheetProduct && $productId > 0) {
            $sheetProduct = getSheetProductById($productId);
        }

        $displaySnapshot = getCandybirdOrderItemDisplaySnapshot($conn, $item, $orderDate, ['allow_sheet_fallback' => false]);
        $title = $displaySnapshot['title'];
        $imageUrl = trim((string) $displaySnapshot['image_url']);
        if ($imageUrl === SIRFRANCIS_PRODUCT_PLACEHOLDER_IMAGE && $sheetProduct) {
            $imageUrl = getSheetProductEmailImage($sheetProduct);
        }
        $imageUrl = getCandybirdAbsoluteImageUrl($imageUrl);

        $productUrl = $sheetProduct ? getSheetProductUrl($sheetProduct, true) : sirFrancisSiteUrl('product?id=' . rawurlencode($clearanceId !== '' ? 'CLR:' . $clearanceId : (string) $productId));
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $price = (float) $displaySnapshot['price'];
        $discount = (float) $displaySnapshot['discount_amount'];
        $tax = (float) ($item['tax_amount'] ?? 0);
        $unitPayable = max(0, $price - $discount + $tax);
        $fullLineTotal = $price * $quantity;
        $discountedLineTotal = $unitPayable * $quantity;
        $productDiscount += max(0, $discount * $quantity);

        if ($discount > 0) {
            $priceHtml = '<span style="color:#777777;text-decoration:line-through;font-weight:400;">' . cbResendOrderEmailMoney($fullLineTotal) . '</span><br>'
                . '<strong style="color:#1d7d38;">' . cbResendOrderEmailMoney($discountedLineTotal) . '</strong>'
                . '<div style="color:#777777;font-size:12px;font-weight:400;">Unit: ' . cbResendOrderEmailMoney($unitPayable) . ' was ' . cbResendOrderEmailMoney($price) . '</div>';
        } else {
            $priceHtml = '<strong>' . cbResendOrderEmailMoney($discountedLineTotal) . '</strong>'
                . '<div style="color:#777777;font-size:12px;font-weight:400;">Unit: ' . cbResendOrderEmailMoney($unitPayable) . '</div>';
        }

        $clearanceNote = trim((string) ($item['clearance_note'] ?? ''));
        $titleHtml = cbResendOrderEmailText($title);
        if ($clearanceNote !== '') {
            $titleHtml .= '<br><span style="display:inline-block;margin-top:4px;background:#fff0c7;color:#7b4b00;font-size:12px;font-weight:700;padding:3px 6px;">' . cbResendOrderEmailText($clearanceNote) . '</span>';
        }

        $html .= '<tr>
            <td style="width:64px;padding:12px 10px;border-bottom:1px solid #e8e1d7;vertical-align:top;"><img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" width="54" height="54" alt="" style="display:block;width:54px;height:54px;object-fit:cover;border:1px solid #eee;"></td>
            <td style="padding:12px 10px;border-bottom:1px solid #e8e1d7;vertical-align:top;"><a href="' . htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#28364B;text-decoration:none;font-weight:700;">' . $titleHtml . '</a></td>
            <td align="center" style="width:48px;padding:12px 10px;border-bottom:1px solid #e8e1d7;vertical-align:top;color:#555555;">x' . $quantity . '</td>
            <td align="right" style="width:120px;padding:12px 0 12px 10px;border-bottom:1px solid #e8e1d7;vertical-align:top;font-weight:700;">' . $priceHtml . '</td>
        </tr>';
    }

    $stmt->close();
    return ['html' => $html, 'product_discount' => $productDiscount];
}

$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$includeEditNote = isset($_POST['include_edit_note']) && (string) $_POST['include_edit_note'] === '1';

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Choose a valid order first.']);
    exit;
}

$order = cbResendOrderEmailFetch($conn, $orderId);
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order could not be found.']);
    exit;
}

$customerEmail = trim((string) ($order['customer_email'] ?? ''));
if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'This order does not have a valid customer email address.']);
    exit;
}

$items = cbResendOrderEmailItemsHtml($conn, $orderId, $order['order_date'] ?? null);
if (trim($items['html']) === '') {
    echo json_encode(['success' => false, 'message' => 'This order has no items to email.']);
    exit;
}

$orderPadded = str_pad((string) $orderId, 7, '0', STR_PAD_LEFT);
$shippingAmount = (float) ($order['shipping_amount'] ?? 0);
$shippingDiscount = (float) ($order['shipping_discount_amount'] ?? 0);
$shippingPayable = max(0, $shippingAmount - $shippingDiscount);
$productDiscount = max((float) ($order['discount_amount'] ?? 0), (float) $items['product_discount']);
$couponAmount = (float) ($order['coupon_amount'] ?? 0);
$totalSavings = $productDiscount + $couponAmount + $shippingDiscount;
$deliveryMethod = cbAdminInferDeliveryMethod($order);
$deliveryLabels = [
    'locker' => 'Pudo locker',
    'door' => 'Door-to-door',
    'digital' => 'Digital delivery',
    'collect' => 'Collection',
];
$deliveryLabel = $deliveryLabels[$deliveryMethod] ?? 'Pudo locker';
$weightLabel = formatCandybirdWeightKg(cbAdminOrderWeightKg($conn, $orderId));
$couponCode = '';
if ($couponAmount > 0) {
    $couponStmt = $conn->prepare("SELECT coupon_code FROM coupon_email_usage WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    if ($couponStmt) {
        $couponStmt->bind_param('i', $orderId);
        $couponStmt->execute();
        $couponRow = $couponStmt->get_result()->fetch_assoc();
        $couponStmt->close();
        $couponCode = trim((string) ($couponRow['coupon_code'] ?? ''));
    }
}

$shippingContent = cbResendOrderEmailMoney($shippingPayable);
if ($shippingDiscount > 0) {
    $shippingContent = '<span style="color:#777777;text-decoration:line-through;">' . cbResendOrderEmailMoney($shippingAmount) . '</span> <strong style="color:#1d7d38;">' . ($shippingPayable > 0 ? cbResendOrderEmailMoney($shippingPayable) : 'Free') . '</strong>';
}

$productDiscountRow = $productDiscount > 0
    ? '<tr><td style="padding:8px 0;color:#555555;">Product savings</td><td align="right" style="padding:8px 0;color:#1d7d38;font-weight:700;">-' . cbResendOrderEmailMoney($productDiscount) . '</td></tr>'
    : '';
$couponRow = $couponAmount > 0
    ? '<tr><td style="padding:8px 0;color:#555555;">Coupon' . ($couponCode !== '' ? ' (' . cbResendOrderEmailText($couponCode) . ')' : '') . '</td><td align="right" style="padding:8px 0;color:#1d7d38;font-weight:700;">-' . cbResendOrderEmailMoney($couponAmount) . '</td></tr>'
    : '';
$shippingDiscountRow = $shippingDiscount > 0
    ? '<tr><td style="padding:8px 0;color:#555555;">Delivery discount</td><td align="right" style="padding:8px 0;color:#1d7d38;font-weight:700;">-' . cbResendOrderEmailMoney($shippingDiscount) . '</td></tr>'
    : '';
$resendNotice = $includeEditNote
    ? '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:18px 0 0;background:#fff8e6;border:1px solid #f1d38a;"><tr><td style="padding:12px 14px;color:#5d4300;font-size:13px;line-height:1.6;"><strong>Updated order notice:</strong> Sir Francis has updated this order. The products, delivery, discounts, and total shown below are the latest saved version of your order.</td></tr></table>'
    : '';

$sessionParam = urlencode($_SESSION['session_id'] ?? session_id());
$orderDetailsUrl = 'https://www.fishgelatine.co.za/v2/order_details?order_id=' . urlencode((string) $orderId) . '&session=' . $sessionParam;
$adminOrderUrl = 'https://www.fishgelatine.co.za/v2/admin-sf/order_details?order_id=' . urlencode((string) $orderId);
$replacements = [
    '{year}' => date('Y'),
    '{order_id}' => $orderPadded,
    '{order_id_raw}' => (string) $orderId,
    '{order_details_url}' => $orderDetailsUrl,
    '{admin_order_url}' => $adminOrderUrl,
    '{order_items}' => $items['html'],
    '{delivery_address}' => cbResendOrderEmailText($order['shipping_address'] ?? ''),
    '{delivery_method}' => cbResendOrderEmailText($deliveryLabel),
    '{delivery_tier}' => '',
    '{delivery_summary}' => cbResendOrderEmailText($deliveryLabel),
    '{order_weight_estimate}' => cbResendOrderEmailText($weightLabel),
    '{coupon_code}' => cbResendOrderEmailText($couponCode !== '' ? $couponCode : 'None'),
    '{coupon_amount}' => '-' . cbResendOrderEmailMoney($couponAmount),
    '{coupon_row}' => $couponRow,
    '{coupon_section}' => '',
    '{product_discount}' => '-' . cbResendOrderEmailMoney($productDiscount),
    '{product_discount_row}' => $productDiscountRow,
    '{shipping_before_discount}' => cbResendOrderEmailMoney($shippingAmount),
    '{shipping_discount}' => '-' . cbResendOrderEmailMoney($shippingDiscount),
    '{shipping_discount_row}' => $shippingDiscountRow,
    '{shipping_payable}' => $shippingPayable > 0 ? cbResendOrderEmailMoney($shippingPayable) : 'Free',
    '{order_subtotal}' => cbResendOrderEmailMoney($order['subtotal_amount'] ?? 0),
    '{order_shipping}' => $shippingContent,
    '{order_discount}' => '-' . cbResendOrderEmailMoney($totalSavings),
    '{order_total}' => cbResendOrderEmailMoney($order['grand_total_amount'] ?? 0),
    '{order_status}' => cbResendOrderEmailText($order['order_status'] ?? 'Pending'),
    '{payment_method}' => cbResendOrderEmailText($order['payment_method_label'] ?? 'Payment'),
    '{order_notes}' => trim((string) ($order['order_notes'] ?? '')) !== '' ? cbResendOrderEmailText($order['order_notes']) : 'No order notes.',
    '{user_email_unsubscribe}' => urlencode($customerEmail),
    '{resend_notice}' => $resendNotice,
];

$customerTemplate = file_get_contents(__DIR__ . '/../emails/email_order_confirmation.php');
$adminTemplate = file_get_contents(__DIR__ . '/../emails/email_order_confirmation_admin.php');
if ($customerTemplate === false || $adminTemplate === false) {
    echo json_encode(['success' => false, 'message' => 'Email template could not be loaded.']);
    exit;
}

$customerName = trim((string) ($order['first_name'] ?? 'Customer'));
$fullCustomerName = trim((string) ($order['customer_name'] ?? $customerName));
$customerBody = strtr($customerTemplate, array_merge($replacements, [
    '{recipient_name}' => cbResendOrderEmailText($customerName),
]));
$adminBody = strtr($adminTemplate, array_merge($replacements, [
    '{recipient_name}' => 'Admin',
    '{user_name}' => cbResendOrderEmailText($fullCustomerName),
    '{user_email}' => cbResendOrderEmailText($customerEmail),
]));

$customerResult = cbCandybirdSendMail(
    $customerEmail,
    $fullCustomerName,
    'Sir Francis | Order Confirmation | #' . $orderPadded,
    $customerBody,
    ['prefer_mail_transport' => true]
);

$adminRecipient = $GLOBALS['smtp_username1'] ?? '';
$adminResult = ['success' => false, 'error' => 'Admin email address is not configured.'];
if (filter_var((string) $adminRecipient, FILTER_VALIDATE_EMAIL)) {
    $adminResult = cbCandybirdSendMail(
        $adminRecipient,
        'Admin',
        'Sir Francis | Order Received | #' . $orderPadded,
        $adminBody,
        [
            'reply_to_email' => $customerEmail,
            'reply_to_name' => $fullCustomerName ?: 'Sir Francis customer',
            'prefer_mail_transport' => true,
        ]
    );
}

if (!empty($customerResult['success']) && !empty($adminResult['success'])) {
    echo json_encode(['success' => true, 'message' => 'Order confirmation resent to the client and admin.']);
    exit;
}

error_log('Sir Francis resend order email failed for order ' . $orderPadded . ': customer=' . ($customerResult['error'] ?? 'ok') . ' admin=' . ($adminResult['error'] ?? 'ok'));
echo json_encode([
    'success' => false,
    'message' => !empty($customerResult['success'])
        ? 'Client email sent, but the admin copy could not be sent.'
        : 'The order confirmation email could not be sent right now. The exact mail error has been logged.',
]);
exit;
