<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    $order_id = isset($_GET['order_id']) ? preg_replace('/\D/', '', (string) $_GET['order_id']) : '';
    $redirect_url = "order_receipt" . ($order_id !== '' ? "?order_id=" . urlencode($order_id) : "");
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';
require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/admin_order_totals.php';

$order_id = isset($_GET['order_id']) ? (int) preg_replace('/\D/', '', (string) $_GET['order_id']) : 0;
if ($order_id <= 0) {
    echo "Order ID is missing.";
    exit();
}

$deliveryCopy = isset($_GET['delivery_copy']) ? max(0, (int) preg_replace('/\D/', '', (string) $_GET['delivery_copy'])) : 0;
$deliveryTotal = isset($_GET['delivery_total']) ? max(0, (int) preg_replace('/\D/', '', (string) $_GET['delivery_total'])) : 0;
if ($deliveryCopy > 0 && $deliveryTotal < $deliveryCopy) {
    $deliveryTotal = $deliveryCopy;
}

function cbReceiptText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbReceiptMoney($value) {
    return 'R' . number_format((float) $value, 2);
}

$settings = [
    'website_company_name' => 'Sir Francis',
    'email_1' => 'info@fishgelatine.co.za',
    'tel' => '+27 41 001 1786',
    'address' => '18 Babiana Rd, Malabar, Port Elizabeth',
    'banking_details' => '',
];

$settingsResult = $conn instanceof mysqli ? $conn->query("SELECT website_company_name, email_1, tel, address, banking_details FROM admin_website_settings LIMIT 1") : false;
if ($settingsResult && ($settingsRow = $settingsResult->fetch_assoc())) {
    $settings = array_merge($settings, array_filter($settingsRow, static function ($value) {
        return trim((string) $value) !== '';
    }));
}

$receiptLogoSrc = '../assets/img/logo/logo.png';
foreach (['pos_header.png', 'pos_header.PNG', 'pos_header/logo.png', 'pos_header/png'] as $logoCandidate) {
    if (is_file(__DIR__ . '/' . $logoCandidate)) {
        $receiptLogoSrc = $logoCandidate;
        break;
    }
}

$orderStmt = $conn->prepare("
    SELECT
        o.*,
        pm.label AS payment_method_label,
        ua.billing_first_name,
        ua.billing_last_name,
        ua.billing_email_address,
        ua.billing_phone_number
    FROM orders o
    LEFT JOIN payment_methods pm ON o.payment_method = pm.id
    LEFT JOIN user_addresses ua ON (
        (o.user_id IS NOT NULL AND ua.user_id = o.user_id)
        OR (o.guest_identifier <> '' AND ua.guest_identifier = o.guest_identifier)
    )
    WHERE o.id = ?
    GROUP BY o.id
    LIMIT 1
");
$orderStmt->bind_param("i", $order_id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
$orderStmt->close();

if (!$order) {
    echo "Order not found.";
    exit();
}

ensureCandybirdOrderItemSnapshotColumns($conn);

$itemsStmt = $conn->prepare("
    SELECT product_id, product_title, product_weight, quantity, price, discount_amount, tax_amount
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");
$itemsStmt->bind_param("i", $order_id);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}
$itemsStmt->close();

$displayOrderId = str_pad((string) $order_id, 7, '0', STR_PAD_LEFT);
$paymentStatus = (int) ($order['payment_status'] ?? 0);
$paymentStatusText = $paymentStatus === 0 ? 'UNPAID' : ($paymentStatus === 2 ? 'PAID - EFT CONFIRMED' : 'PAID');
$deliveryMethod = cbAdminInferDeliveryMethod($order);
$deliveryLabels = [
    'locker' => 'Pudo locker',
    'door' => 'Door-to-door',
    'digital' => 'Digital delivery',
    'collect' => 'Collection',
];
$deliveryLabel = $deliveryLabels[$deliveryMethod] ?? 'Delivery';

$customerName = trim((string) ($order['billing_first_name'] ?? '') . ' ' . (string) ($order['billing_last_name'] ?? ''));
if ($customerName === '') {
    $customerName = 'Customer';
}

$itemCount = 0;
$totalWeightKg = cbAdminOrderWeightKg($conn, $order_id);
$totalWeightText = formatCandybirdWeightKg($totalWeightKg);
$deliveryAddress = trim((string) ($order['shipping_address'] ?? ''));
$receiptNotes = trim((string) ($order['order_notes'] ?? ''));
$receiptNotes = preg_replace('/\n?Dispatch note:.*?(?=\n(?:Delivery|Collection address|Shipping quote|$)|$)/is', '', $receiptNotes);
$receiptNotes = trim((string) $receiptNotes);
$receiptRows = '';
foreach ($items as $item) {
    $qty = (float) ($item['quantity'] ?? 0);
    $unitPrice = (float) ($item['price'] ?? 0);
    $discount = (float) ($item['discount_amount'] ?? 0);
    $tax = (float) ($item['tax_amount'] ?? 0);
    $lineTotal = ($qty * $unitPrice) - ($qty * $discount) + $tax;
    $itemCount += $qty;

    $sheetProduct = getSheetProductById($item['product_id']);
    $title = trim((string) ($item['product_title'] ?? ''));
    $weight = trim((string) ($item['product_weight'] ?? ''));
    if ($title !== '' && $weight !== '' && stripos($title, $weight) === false) {
        $title = trim($title . ' ' . $weight);
    }
    if ($title === '' && $sheetProduct) {
        $title = getSheetProductDisplayTitle($sheetProduct);
    }
    if ($title === '') {
        $title = 'Product #' . $item['product_id'];
    }

    $receiptRows .= '<tr><td>' . cbReceiptText($title) . '</td><td class="right">' . cbReceiptText(number_format($qty, floor($qty) != $qty ? 2 : 0)) . ' @ ' . cbReceiptMoney($unitPrice) . '</td></tr>';
    if ($discount > 0) {
        $receiptRows .= '<tr><td class="muted">DISCOUNT</td><td class="right muted">-' . cbReceiptMoney($qty * $discount) . '</td></tr>';
    }
    $receiptRows .= '<tr><td class="muted">Line total</td><td class="right">' . cbReceiptMoney($lineTotal) . '</td></tr>';
    $receiptRows .= '<tr class="spacer"><td colspan="2"></td></tr>';
}

$subtotal = (float) ($order['subtotal_amount'] ?? 0);
$productSavings = (float) ($order['discount_amount'] ?? 0);
$couponSavings = (float) ($order['coupon_amount'] ?? 0);
$shipping = max(0, (float) ($order['shipping_amount'] ?? 0) - (float) ($order['shipping_discount_amount'] ?? 0));
$shippingBeforeDiscount = (float) ($order['shipping_amount'] ?? 0);
$shippingSavings = (float) ($order['shipping_discount_amount'] ?? 0);
$totalSavings = $productSavings + $couponSavings + $shippingSavings;
$grandTotal = (float) ($order['grand_total_amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt <?= cbReceiptText($displayOrderId) ?> - Sir Francis</title>
    <style>
        * { box-sizing: border-box; }
        html {
            width: 300px;
            display: block;
            margin: 0 auto;
            background: #f5f5f5;
            font-family: Calibri, Arial, sans-serif;
        }
        body {
            margin: 0;
            color: #000;
            font-family: Calibri, Arial, sans-serif;
            font-weight: 700;
        }
        .screen-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            padding: 12px 0;
        }
        .screen-actions form {
            display: flex;
            gap: 5px;
            margin: 0;
        }
        .screen-actions input {
            border: 1px solid #bbb;
            font-size: 12px;
            padding: 7px 6px;
            width: 58px;
        }
        .screen-actions a,
        .screen-actions button {
            background: #111;
            border: 0;
            color: #fff;
            cursor: pointer;
            font-size: 12px;
            padding: 8px 10px;
            text-decoration: none;
        }
        .receipt {
            background: #fff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
            padding: 18px;
            width: 300px;
            color: #000;
            font-weight: 700;
        }
        .logo {
            display: block;
            max-width: 210px;
            margin: 0 auto 12px;
        }
        h1, h2, p { margin: 0; }
        .center { text-align: center; }
        .muted { color: #000; font-weight: 700; }
        .company { font-size: 13px; line-height: 1.25; margin-bottom: 10px; }
        .bill-to { font-size: 13px; line-height: 1.25; margin: 8px 0; text-align: center; }
        .delivery-copy {
            border: 2px solid #000;
            font-size: 18px;
            font-weight: 900;
            margin: 8px 0;
            padding: 6px;
            text-align: center;
        }
        .customer-name {
            display: block;
            font-size: 24px;
            font-weight: 900;
            line-height: 1.05;
            margin: 4px 0 6px;
            text-transform: uppercase;
        }
        .big-total-label { font-size: 13px; margin-top: 8px; text-align: center; }
        .big-total { font-size: 30px; font-weight: 900; margin: 0 0 8px; text-align: center; }
        strong {
            font-weight: 900;
        }
        .status {
            border: 2px solid #000;
            display: inline-block;
            font-size: 12px;
            font-weight: 900;
            margin: 5px auto 0;
            padding: 3px 7px;
        }
        hr {
            border: 0;
            border-top: 2px solid #000;
            margin: 9px 0;
        }
        table {
            border-collapse: collapse;
            font-size: 13px;
            font-weight: 700;
            width: 100%;
        }
        td {
            padding: 2px 0;
            vertical-align: top;
        }
        .right { text-align: right; white-space: nowrap; }
        .spacer td { height: 10px; }
        .footer { color: #000; font-size: 12px; font-weight: 700; line-height: 1.25; margin-top: 8px; text-align: center; }
        @media print {
            @page {
                size: 80mm auto;
                margin: 4mm;
            }
            html, body {
                width: 72mm;
                background: #fff;
                color: #000;
                font-weight: 700;
                margin: 0;
                padding: 0;
            }
            * {
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .screen-actions {
                display: none !important;
            }
            .receipt {
                box-shadow: none;
                padding: 0;
                width: 72mm;
                color: #000;
                font-weight: 700;
            }
            .logo {
                max-width: 58mm;
            }
        }
    </style>
</head>
<body>
    <div class="screen-actions">
        <button type="button" onclick="window.print()">Print Receipt</button>
        <form method="get" action="order_receipt">
            <input type="hidden" name="order_id" value="<?= cbReceiptText((string) $order_id) ?>">
            <input type="number" name="delivery_copy" min="1" max="99" placeholder="Copy" value="<?= $deliveryCopy > 0 ? cbReceiptText((string) $deliveryCopy) : '' ?>">
            <input type="number" name="delivery_total" min="1" max="99" placeholder="Total" value="<?= $deliveryTotal > 0 ? cbReceiptText((string) $deliveryTotal) : '' ?>">
            <button type="submit">Delivery Copy</button>
        </form>
        <a href="order_details?order_id=<?= cbReceiptText((string) $order_id) ?>">Back</a>
    </div>

    <main class="receipt">
        <img class="logo" src="<?= cbReceiptText($receiptLogoSrc) ?>" alt="Sir Francis">
        <p class="company center">
            <strong><?= cbReceiptText($settings['website_company_name']) ?></strong><br>
            <?= nl2br(cbReceiptText($settings['address'])) ?><br>
            <?= cbReceiptText($settings['email_1']) ?><br>
            <?= cbReceiptText($settings['tel']) ?>
        </p>

        <hr>

        <p class="center" style="font-size:15px;">CUSTOMER RECEIPT</p>
        <?php if ($deliveryCopy > 0 && $deliveryTotal > 0): ?>
            <div class="delivery-copy">DELIVERY <?= cbReceiptText((string) $deliveryCopy) ?> OF <?= cbReceiptText((string) $deliveryTotal) ?></div>
        <?php endif; ?>
        <p class="bill-to">
            <strong class="customer-name"><?= cbReceiptText($customerName) ?></strong>
            <?= cbReceiptText($order['billing_phone_number'] ?? '') ?><br>
            <?= cbReceiptText($order['billing_email_address'] ?? '') ?>
        </p>

        <table>
            <tr><td>ORDER</td><td class="right">#<?= cbReceiptText($displayOrderId) ?></td></tr>
            <tr><td>DATE</td><td class="right"><?= cbReceiptText(date('Y-m-d H:i', strtotime((string) ($order['order_date'] ?? 'now')))) ?></td></tr>
            <tr><td>ITEMS</td><td class="right"><?= cbReceiptText(number_format($itemCount, floor($itemCount) != $itemCount ? 2 : 0)) ?></td></tr>
            <tr><td>WEIGHT EST.</td><td class="right"><?= cbReceiptText($totalWeightText) ?></td></tr>
            <tr><td>DELIVERY</td><td class="right"><?= cbReceiptText($deliveryLabel) ?></td></tr>
            <tr><td>PAYMENT</td><td class="right"><?= cbReceiptText($order['payment_method_label'] ?? '') ?></td></tr>
        </table>

        <?php if ($deliveryAddress !== ''): ?>
            <hr>
            <p class="footer"><strong>Delivery address</strong><br><?= nl2br(cbReceiptText($deliveryAddress)) ?></p>
        <?php endif; ?>

        <hr>

        <table>
            <?= $receiptRows ?>
        </table>

        <hr>

        <table>
            <tr><td>SUBTOTAL</td><td class="right"><?= cbReceiptMoney($subtotal) ?></td></tr>
            <?php if ($productSavings > 0): ?><tr><td>PRODUCT SAVINGS</td><td class="right">-<?= cbReceiptMoney($productSavings) ?></td></tr><?php endif; ?>
            <?php if ($couponSavings > 0): ?><tr><td>COUPON</td><td class="right">-<?= cbReceiptMoney($couponSavings) ?></td></tr><?php endif; ?>
            <?php if ($shippingSavings > 0): ?><tr><td>DELIVERY SAVINGS</td><td class="right">-<?= cbReceiptMoney($shippingSavings) ?></td></tr><?php endif; ?>
            <tr><td>DELIVERY</td><td class="right"><?= cbReceiptMoney($shipping) ?></td></tr>
            <?php if ($shippingSavings > 0): ?><tr><td class="muted">Delivery before discount</td><td class="right muted"><?= cbReceiptMoney($shippingBeforeDiscount) ?></td></tr><?php endif; ?>
            <?php if ($totalSavings > 0): ?><tr><td>TOTAL SAVINGS</td><td class="right">-<?= cbReceiptMoney($totalSavings) ?></td></tr><?php endif; ?>
        </table>

        <p class="big-total-label">TOTAL</p>
        <p class="big-total"><?= cbReceiptMoney($grandTotal) ?></p>
        <p class="center"><span class="status"><?= cbReceiptText($paymentStatusText) ?></span></p>

        <?php if ($receiptNotes !== ''): ?>
            <hr>
            <p class="footer"><strong>Notes</strong><br><?= nl2br(cbReceiptText($receiptNotes)) ?></p>
        <?php endif; ?>

        <hr>
        <p class="footer">
            Thank you for shopping with Sir Francis.<br>
            www.fishgelatine.co.za
        </p>
    </main>

    <script>
    window.addEventListener('load', function() {
        window.print();
    });
    </script>
</body>
</html>
