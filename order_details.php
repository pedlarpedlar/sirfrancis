<?php
include 'session_logins.php';

// Fetch order details
$order_id = isset($_GET['order_id']) ? preg_replace('/\D/', '', (string) $_GET['order_id']) : null;
$order_id_raw = $order_id;
$guest_session_ok = isset($_GET['session'], $_SESSION['session_id']) && hash_equals($_SESSION['session_id'], $_GET['session']);
$user_id = $_SESSION['user_id'] ?? null;
$guest_identifier = $_SESSION['guest_identifier'] ?? '';
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;

$no_order_found = false;
$payment_status = null;
$payNowButton = '';
$statusText = '';
$order_items = '';
$autoSubmitPayfast = isset($_GET['payfast']) && $_GET['payfast'] === '1';

if (!function_exists('cbOrderHasSuccessfulPayfastPayment')) {
    function cbOrderHasSuccessfulPayfastPayment($conn, $orderId) {
        if (!($conn instanceof mysqli) || (int) $orderId <= 0) {
            return false;
        }

        $columnCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'payfast_payment_id'");
        if ($columnCheck && $columnCheck->num_rows > 0) {
            $stmt = $conn->prepare("SELECT payfast_payment_id FROM orders WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!empty($row['payfast_payment_id'])) {
                    return true;
                }
            }
        }

        $tableCheck = $conn->query("SHOW TABLES LIKE 'payment_checks'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $stmt = $conn->prepare("SELECT id FROM payment_checks WHERE order_id = ? AND check_result = 1 LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $hasPaidCheck = $stmt->get_result()->num_rows > 0;
                $stmt->close();
                if ($hasPaidCheck) {
                    return true;
                }
            }

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS failed_checks,
                       SUM(CASE WHEN check_name <> 'pfValidIP' THEN 1 ELSE 0 END) AS other_failed_checks
                FROM payment_checks
                WHERE order_id = ? AND check_result = 0
            ");
            if ($stmt) {
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ((int) ($row['failed_checks'] ?? 0) > 0 && (int) ($row['other_failed_checks'] ?? 0) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!$order_id) {
    echo "Order ID is missing.";
    header("Location: profile?notfound");
    exit();
}

if (!($conn instanceof mysqli)) {
    include 'header.php';
    include 'page_menues.php';
    echo '<div class="container py-5"><h2>Order details unavailable</h2><p>We could not connect to order records right now. Please try again shortly or contact us with your order number.</p></div>';
    include 'footer.php';
    exit();
}

ensureCandybirdOrderItemSnapshotColumns($conn);

$sql = "SELECT 
    o.id AS order_id,
    o.order_date,
    o.grand_total_amount,
    o.shipping_address,
    o.order_status,
    o.payment_status,
    o.order_notes,
    o.coupon_id,
    o.coupon_amount,
    o.subtotal_amount,
    o.shipping_amount,
    o.shipping_discount_amount,
    o.discount_amount AS order_discount_amount,
    pm.label AS payment_method_label,
    oi.id AS order_item_id,
    oi.product_id,
    oi.quantity AS quantity,
    oi.price AS product_price,
    oi.discount_amount AS product_discount_amount,
    oi.tax_amount AS product_tax_amount,
    oi.product_title AS product_title,
    oi.product_image_url AS product_image_url,
    oi.product_weight AS product_weight,
    ua.billing_first_name AS billing_first_name,
    ua.billing_last_name AS billing_last_name,
    ua.billing_email_address AS billing_email_address
FROM 
    orders o
LEFT JOIN 
    payment_methods pm ON o.payment_method = pm.id
LEFT JOIN 
    order_items oi ON o.id = oi.order_id
LEFT JOIN 
    user_addresses ua ON (
        (o.user_id IS NOT NULL AND ua.user_id = o.user_id)
        OR (o.guest_identifier <> '' AND ua.guest_identifier = o.guest_identifier)
    )
WHERE 
    o.id = ?
    AND (? IS NOT NULL OR o.user_id = ? OR o.guest_identifier = ? OR ? = 1)";

$stmt = mysqli_prepare($conn, $sql);
$guestAccess = 1;
$user_id_for_query = $user_id ?? 0;
mysqli_stmt_bind_param($stmt, "iiisi", $order_id, $admin_id, $user_id_for_query, $guest_identifier, $guestAccess);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$display_order_id = str_pad($order_id_raw, 7, '0', STR_PAD_LEFT);

if (mysqli_num_rows($result) > 0) {
    $order = mysqli_fetch_all($result, MYSQLI_ASSOC);

     // Store necessary data before freeing the result
    $cartTotal = $order[0]['grand_total_amount'];
    $payment_status = $order[0]['payment_status'];
    if ((int) $payment_status === 0 && cbOrderHasSuccessfulPayfastPayment($conn, (int) $order[0]['order_id'])) {
        $payment_status = 1;
        $conn->query("UPDATE orders SET payment_status = 1 WHERE id = " . (int) $order[0]['order_id']);
    }

    $fetched_id = $order[0]['order_id'];
    $fetched_payment_status = $payment_status;
    $fetched_billing_first_name = $order[0]['billing_first_name'];
    $fetched_billing_last_name = $order[0]['billing_last_name'];
    $fetched_billing_email_address = $order[0]['billing_email_address'];
    $fetched_shipping_address = $order[0]['shipping_address'];
    $fetched_order_status = $order[0]['order_status'];
    $fetched_payment_method_label = $order[0]['payment_method_label'];
    $fetched_order_notes = $order[0]['order_notes'];
    $fetched_coupon_id = $order[0]['coupon_id'];
    $fetched_coupon_amount = $order[0]['coupon_amount'];
    // $fetched_coupon_code = $order[0]['coupon_code'];
    $fetched_subtotal_amount = $order[0]['subtotal_amount'];
    $fetched_shipping_amount = $order[0]['shipping_amount'];
    $fetched_shipping_discount_amount = $order[0]['shipping_discount_amount'];
    $fetched_order_discount_amount = $order[0]['order_discount_amount'];
    $fetched_grand_total_amount = $order[0]['grand_total_amount'];
    $fetched_weight_kg = 0;

    if (empty($fetched_billing_email_address) && preg_match('/Email:\s*([^,\n]+)/i', (string) $fetched_shipping_address, $emailMatch)) {
        $fetched_billing_email_address = trim($emailMatch[1]);
    }
    if (empty($fetched_billing_first_name) || empty($fetched_billing_last_name)) {
        $addressFirstLine = trim(strtok((string) $fetched_shipping_address, ",\n"));
        $nameParts = preg_split('/\s+/', $addressFirstLine, 2);
        if (empty($fetched_billing_first_name)) {
            $fetched_billing_first_name = $nameParts[0] ?? 'Customer';
        }
        if (empty($fetched_billing_last_name)) {
            $fetched_billing_last_name = $nameParts[1] ?? '';
        }
    }

    
    $order_items = '';
    foreach ($order as $item) {
        $productId = htmlspecialchars($item['product_id']);
        $savedTitle = trim((string) ($item['product_title'] ?? ''));
        $savedImage = trim((string) ($item['product_image_url'] ?? ''));
        $sheetProduct = getSheetProductById($item['product_id']);
        if ($sheetProduct) {
            $fetched_weight_kg += getSheetProductWeightKg($sheetProduct) * (float) ($item['quantity'] ?? 0);
        }
        $image_url = htmlspecialchars($savedImage !== '' ? $savedImage : ($sheetProduct ? getSheetProductImage($sheetProduct) : 'assets/img/product/1.png'));
        $product_title = htmlspecialchars($savedTitle !== '' ? $savedTitle : ($sheetProduct ? getSheetProductDisplayTitle($sheetProduct) : 'Product #' . $item['product_id']));
        $quantity = (float)$item['quantity'];
        $product_price = (float)$item['product_price'];
        $discount_amount = (float)$item['product_discount_amount'];
        $tax_amount = (float)$item['product_tax_amount'];

        $discounted_unit_price = max(0, $product_price - $discount_amount);
        $subtotal = ($quantity * $discounted_unit_price) + $tax_amount;
        $priceDisplay = $discount_amount > 0
            ? '<span style="text-decoration: line-through; color: #777;">R' . number_format($product_price, 2) . '</span><br><strong>R' . number_format($discounted_unit_price, 2) . '</strong>'
            : 'R' . number_format($product_price, 2);

        $order_items .=
            '<tr>
                <td>
                    ' . (!empty($image_url) ? '<img class="no-print" src="' . $image_url . '" alt="' . $product_title . '" style="max-width: 50px; max-height: 50px; margin-right: 10px;">' : '<img class="no-print" src="assets/img/product/1.png" alt="Placeholder Image" style="max-width: 50px; max-height: 50px; margin-right: 10px;">') . '
                    <a href="product?id=' . $productId . '">' . $product_title .'</a>
                </td>
                <td>' . $quantity . '</td>
                <td>' . $priceDisplay . '</td>
                <td>' . number_format($discount_amount * $quantity, 2) . '</td>
                <td>' . number_format($tax_amount, 2) . '</td>
                <td>' . number_format($subtotal, 2) . '</td>
            </tr>';
    }
} else {
    $no_order_found = true;
    // exit();
}

if ($no_order_found === false && (int) $payment_status === 0) {
    $statusText = "Unpaid";
    $payNowButton = '<input type="submit" form="payNowForm" class="btn btn-success" value="Pay Now" />';
} elseif ($no_order_found === false && (int) $payment_status === 1) {
    $statusText = "Paid";
    $payNowButton = ''; // No button needed for Paid status
} elseif ($no_order_found === false && (int) $payment_status === 2) {
    $statusText = "Paid (EFT Confirmed)";
    $payNowButton = ''; // No button needed for Paid status
} else {
    $statusText = "Unknown Status"; // Optional: Handle unexpected values
    $payNowButton = ''; // No button needed for unknown status
}

// Close the statement
mysqli_stmt_close($stmt);
// You can also close the result set if desired
mysqli_free_result($result);

$order_id = $order_id_raw;
if ($no_order_found === false) {
    include 'payNowForm.php';
    if (empty($payNowForm)) {
        $payNowButton = '';
    }
}
include 'header.php';
?>

<!-- Meta and Open Graph tags -->
<link rel="canonical" href="https://www.candybird.co.za/profile">
<meta name="description" content="Order details for CandyBird.">
<meta property="og:title" content="My Account - CandyBird">
<meta property="og:description" content="View your order details on CandyBird.">
<meta property="og:image" content="your_image_url_here">
<meta property="og:url" content="https://www.candybird.co.za/profile">
<meta property="og:type" content="website">

<title>Order Details - Order <?=$display_order_id?> on CandyBird</title>

<style>
.admin-view-label {
    position: sticky;
    top: 0;
    width: 100%;
    background-color: #ffcc00;
    color: #000;
    text-align: center;
    font-weight: bold;
    padding: 10px 0;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

@media screen {
    .print-only {
        display: none !important;
    }

}


@media print {
    * {
        font-size: 0.93em !important;
    }
    body, .container {
        background: none !important;
        padding: 8px !important;
        margin: 8px auto !important;
    }
    .no-print {
        display: none !important;
    }
    .custom-table {
        border: 1px solid black;
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        page-break-inside: auto;
        font-size: 7px;
    }
    .custom-table th, .custom-table td {
        border: 1px solid black;
        padding: 2px !important;
        vertical-align: middle;
        text-align: left;
        overflow: hidden;
        word-wrap: break-word;
        line-height: 0.9;
    }
    .page-break {
        page-break-before: always;
    }
}
</style>

<?php 
$username = $_SESSION['username'] ?? 'Guest';
include 'page_menues.php';

?>

<?= $payNowForm ?? '' ?>
<?php if ($autoSubmitPayfast && $no_order_found === false && (int) $payment_status === 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('payNowForm');
    if (form) {
        form.submit();
    }
});
</script>
<?php endif; ?>

<?php
if ($admin_id !== NULL) {
    echo '<div class="no-print admin-view-label">ADMIN VIEW</div>';
}
?>

<div class="my-account pt-20 pb-50">
  <div class="container">

<?php if ($no_order_found === false): ?>


<div style="margin-bottom: 20px; margin-top: 20px;">
        <?php
        if (isset($_GET['thankyou'])) {
            echo "
            <div class='pb-30 text-center'>
            <h2>Thank you for shopping with us!</h2>
            <p>We received your order. We will send your order updates and tracking information via email. :-)</p>
            </div>
            ";
        }
        if (isset($_GET['payment-cancelled'])) {
            echo "
            <div class='pb-30 text-center'>
            <h2>Your order is saved</h2>
            <p>Payment was not completed, but your order details are still here. You can pay when you are ready.</p>
            </div>
            ";
        }
        ?>

    <div class="order-page-header print-only">
        <div class="header-left">
            <img src="<?=$home_directory?>assets/img/logo/logo.png" alt="logo" class="company-logo" width="150px"/>
            <br>
            <strong>Suppliers and distributors of quality nuts, dried fruit, and snacks</strong>
        </div>
        <div class="header-right">
            <div class="company-details">
                <strong>Candybird Pty Ltd</strong><br>
                Port Elizabeth, Eastern Cape<br>
                South Africa<br>
                Email: sales@candybird.co.za<br>
                Phone: +27 41 00 11 786
            </div>
        </div>
    </div>

    <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff; text-align: left;">
        <thead>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Order Number:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($display_order_id) ?></td>
                <th style="padding: 10px; border: 1px solid #ccc;">Payment Status:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><span><?= $statusText ?></span> <span class="mx-2"><?= $payNowButton ?></span></td>
            </tr>
        </thead>
    </table>
</div>

<h3 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; font-family: 'Raleway', sans-serif;">Order details</h3>

<div style="margin-bottom: 20px;">
    <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff; text-align: left;">
        <thead>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Deliver To:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($fetched_shipping_address) ?></td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Total Weight Estimate:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars(formatCandybirdWeightKg($fetched_weight_kg)) ?></td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Order Status:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($fetched_order_status) ?></td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Payment Method:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($fetched_payment_method_label) ?></td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Banking Details for direct deposits:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= $banking_details ?></td>
            </tr>
        </thead>
    </table>
</div>

<h3 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; font-family: 'Raleway', sans-serif;">Items in this order</h3>

<div style="margin-bottom: 20px;">
    <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff;">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Discount</th>
                <th>Tax</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?=$order_items?>
        </tbody>
        <tfoot>
            <tr>
                <td style="padding: 10px; border: none; text-align: left;">Order Notes:</td>
                <td colspan="3" style="padding: 10px; border: none; text-align: left;"><?= htmlspecialchars($fetched_order_notes) ?></td>
            </tr>


            <!-- Add coupon information if a coupon was used -->
            <?php if (!empty($fetched_coupon_id) && $fetched_coupon_amount > 0): ?>
                <tr>
                    <td colspan="6" style="padding: 10px; border: none; text-align: right;">
                        <div class="text-success" style="border: 1px dashed #000; padding: 10px; display: inline-block;">
                            COUPON: Congratulations, you saved R<?= htmlspecialchars(number_format($fetched_coupon_amount, 2)) ?>!
                        </div>
                    </td>
                </tr>
            <?php endif; ?>



            <tr>
                <th colspan="5" style="padding: 10px; border: none; text-align: right;">Subtotal:</th>
                <th style="padding: 10px; border: none; text-align: right;">R<?= htmlspecialchars($fetched_subtotal_amount) ?></th>
            </tr>

            <tr>
                <th colspan="5" style="padding: 10px; border: none; text-align: right;">
                    Shipping:
                </th>
                <th style="padding: 10px; border: none; text-align: right;">
                    <?php if ($fetched_shipping_discount_amount > 0): ?>
                        <!-- Display the original shipping amount with strikethrough -->
                        <span style="text-decoration: line-through;">R<?= htmlspecialchars($fetched_shipping_amount) ?></span>
                        <!-- Calculate the discounted shipping amount -->
                        <?php
                        $discounted_amount = number_format($fetched_shipping_amount - $fetched_shipping_discount_amount,2);
                        ?>
                        <!-- Display the discounted amount in red -->
                        <span style="margin-left: 10px;">R<?= htmlspecialchars($discounted_amount) ?></span>
                    <?php else: ?>
                        <!-- Display the shipping amount normally -->
                        R<?= htmlspecialchars($fetched_shipping_amount) ?>
                    <?php endif; ?>
                </th>
            </tr>


            <?php
            // Calculate the total savings amount
            $total_savings = $fetched_order_discount_amount + $fetched_shipping_discount_amount + $fetched_coupon_amount;

            // Check if the total savings amount is greater than 0
            if ($total_savings > 0): ?>
                <tr>
                    <th colspan="5" style="padding: 10px; border: none; text-align: right;">Savings:</th>
                    <th class="text-danger" style="padding: 10px; border: none; text-align: right;">
                        -R<?= htmlspecialchars(number_format($total_savings, 2)) ?>
                    </th>
                </tr>
            <?php endif; ?>



            <tr>
                <th colspan="5" style="padding: 10px; border: none; text-align: right;">Total:</th>
                <th style="padding: 10px; border: none; text-align: right;">R<?= htmlspecialchars($fetched_grand_total_amount) ?></th>
            </tr>
        </tfoot>
    </table>

    <a href="profile" class="no-print btn btn-secondary">Back to Profile</a>
</div>

<?php else: ?>
<div style="margin: 20px 0; padding: 20px; border: 1px solid #ccc; background-color: #f9f9f9; text-align: center;">
    <h3 style="font-size: 18px; font-weight: bold; font-family: 'Raleway', sans-serif; ">No order found with this order number</h3>
    <p style="font-family: 'Raleway', sans-serif; color: #333;">Please check the order number and try again, or contact our support team for assistance.</p>
    <a href="profile" style="text-decoration: none; color: #007bff; font-family: 'Raleway', sans-serif;">Back to Profile</a>
</div>
<?php endif; ?>

  </div>
</div>


<?php include "footer.php"; ?>
