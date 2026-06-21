<?php
// Start or resume the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
    $redirect_url = "order_details" . ($order_id ? "?order_id=" . urlencode($order_id) : "");
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

include 'dbh.inc.php';
require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/../ozow_helpers.php';
require_once __DIR__ . '/admin_order_totals.php';

// Fetch order details
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

$no_order_found = false;

if (!function_exists('cbAdminOrderHasSuccessfulPayfastPayment')) {
    function cbAdminOrderHasSuccessfulPayfastPayment($conn, $orderId) {
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

        $columnCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'ozow_transaction_id'");
        if ($columnCheck && $columnCheck->num_rows > 0) {
            $stmt = $conn->prepare("SELECT ozow_transaction_id FROM orders WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!empty($row['ozow_transaction_id'])) {
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
    header("Location: manage_orders?notfound");
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
    o.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Left-zero-pad with 3 zeroes
$order_id = str_pad($order_id, 7, '0', STR_PAD_LEFT);

if (mysqli_num_rows($result) > 0) {
    $order = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Store necessary data before freeing the result
    $cartTotal = $order[0]['grand_total_amount'];
    $payment_status = $order[0]['payment_status'];
    if ((int) $payment_status === 0 && cbAdminOrderHasSuccessfulPayfastPayment($conn, (int) $order[0]['order_id'])) {
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
    $fetched_coupon_code = '';
    if ((float) $fetched_coupon_amount > 0) {
        $couponLookup = $conn->prepare("SELECT coupon_code FROM coupon_email_usage WHERE order_id = ? LIMIT 1");
        if ($couponLookup) {
            $couponLookup->bind_param('i', $fetched_id);
            $couponLookup->execute();
            $couponRow = $couponLookup->get_result()->fetch_assoc();
            $couponLookup->close();
            $fetched_coupon_code = $couponRow['coupon_code'] ?? '';
        }
    }
    $fetched_subtotal_amount = $order[0]['subtotal_amount'];
    $fetched_shipping_amount = $order[0]['shipping_amount'];
    $fetched_shipping_discount_amount = $order[0]['shipping_discount_amount'];
    $fetched_order_discount_amount = $order[0]['order_discount_amount'];
    $fetched_grand_total_amount = $order[0]['grand_total_amount'];
    $fetched_delivery_method = cbAdminInferDeliveryMethod($order[0]);
    $deliveryLabels = [
        'locker' => 'Pudo locker',
        'door' => 'Door-to-door',
        'digital' => 'Digital delivery',
        'collect' => 'Collection',
    ];
    $fetched_delivery_label = $deliveryLabels[$fetched_delivery_method] ?? 'Pudo locker';
    $fetched_shipping_payable = max(0, (float)$fetched_shipping_amount - (float)$fetched_shipping_discount_amount);
    $fetched_weight_kg = cbAdminOrderWeightKg($conn, (int) $fetched_id);
    $fetched_weight_label = formatCandybirdWeightKg($fetched_weight_kg);

    
    $order_items = '';
    foreach ($order as $item) {
        $displaySnapshot = getCandybirdOrderItemDisplaySnapshot($conn, $item, $order[0]['order_date'] ?? null);
        $image_url = htmlspecialchars($displaySnapshot['image_url']);
        $productId = htmlspecialchars($item['product_id']);
        $product_title = htmlspecialchars($displaySnapshot['title']);
        $sheetProduct = getSheetProductById($item['product_id']);
        $productHref = '../' . ($sheetProduct ? getSheetProductUrl($sheetProduct) : ('product-' . rawurlencode((string) $item['product_id'])));
        $quantity = (float)$item['quantity'];
        $product_price = (float)$displaySnapshot['price'];
        $discount_amount = (float)$displaySnapshot['discount_amount'];
        $tax_amount = (float)$item['product_tax_amount'];

        $subtotal = ($quantity * $product_price) - $discount_amount + $tax_amount;

        $order_items .=
            '<tr>
                <td>
                    ' . (!empty($image_url) ? '<img class="no-print" src="' . $image_url . '" alt="' . $product_title . '" style="max-width: 50px; max-height: 50px; margin-right: 10px;">' : '<img class="no-print" src="../assets/img/product/1.png" alt="Placeholder Image" style="max-width: 50px; max-height: 50px; margin-right: 10px;">') . '
                    <a href="' . htmlspecialchars($productHref, ENT_QUOTES, 'UTF-8') . '">' . $product_title . '</a>
                </td>
                <td>' . $quantity . '</td>
                <td>' . number_format($product_price, 2) . '</td>
                <td>' . number_format($discount_amount, 2) . '</td>
                <td>' . number_format($tax_amount, 2) . '</td>
                <td>' . number_format($subtotal, 2) . '</td>
            </tr>';
    }
} else {
    $no_order_found = true;
    // exit();
}

if ($payment_status == 0) {
    $statusText = "Unpaid";
    $payNowButton = '';
} elseif ($payment_status == 1) {
    $statusText = "Paid";
    $payNowButton = ''; // No button needed for Paid status
} elseif ($payment_status == 2) {
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

include 'payNowForm.php';
include 'header.php';
?>

<title>Order <?=$order_id?> on Sir Francis</title>

<style>
.admin-mode-bar {
    position: fixed;
    right: 14px;
    bottom: 14px;
    background: rgba(45, 23, 57, 0.94);
    color: #fff;
    border: 1px solid rgba(252, 180, 47, 0.7);
    border-radius: 6px;
    padding: 8px 12px;
    z-index: 1030;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
}

@media screen {
    .print-only {
        display: none !important;
    }

}


@media print {
    @page {
        size: A4 portrait;
        margin: 6mm;
    }
    * {
        box-shadow: none !important;
        text-shadow: none !important;
    }
    html,
    body {
        background: none !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 10px !important;
        line-height: 1.22 !important;
        width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        overflow: hidden !important;
    }
    body * {
        visibility: hidden !important;
    }
    .admin-print-wrapper,
    .admin-print-wrapper * {
        visibility: visible !important;
    }
    .admin-print-wrapper {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        page-break-after: avoid !important;
        break-after: avoid-page !important;
    }
    .my-account,
    .container {
        max-width: none !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    header,
    footer,
    script,
    .footer-rope,
    .offcanvas,
    .offcanvas-overlay,
    .search-box,
    .breadcrumb-section,
    .scroll-up,
    .scroll-to-top,
    .scroll-top,
    .back-to-top,
    .slick-arrow,
    .admin-mode-bar {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        min-height: 0 !important;
        overflow: hidden !important;
    }
    .no-print {
        display: none !important;
        visibility: hidden !important;
    }
    h1 {
        font-size: 16px !important;
        margin: 4px 0 8px !important;
    }
    h3 {
        font-size: 11px !important;
        margin: 8px 0 4px !important;
    }
    div[style*="margin-bottom: 20px"],
    div[style*="margin-bottom:20px"] {
        margin-bottom: 6px !important;
    }
    .packlist-mode table th:nth-child(n+3),
    .packlist-mode table td:nth-child(n+3),
    .packlist-mode table tfoot {
        display: none !important;
    }
    table,
    .custom-table {
        border: 1px solid black;
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        page-break-inside: auto;
        font-size: 9px !important;
        margin: 0 0 6px !important;
    }
    table th,
    table td,
    .custom-table th, .custom-table td {
        border: 1px solid black;
        padding: 3px 4px !important;
        vertical-align: middle;
        text-align: left;
        overflow: hidden;
        word-wrap: break-word;
        line-height: 1.15 !important;
    }
    table tfoot th,
    table tfoot td {
        padding-top: 2px !important;
        padding-bottom: 2px !important;
    }
    .page-break {
        page-break-before: auto !important;
        break-before: auto !important;
    }

    .print-only {
        width: 100%;
        margin: 0 auto;
    }
    .admin-print-wrapper > div:last-child,
    .admin-print-wrapper table:last-child,
    .admin-print-wrapper tbody:last-child,
    .admin-print-wrapper tfoot:last-child {
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
        page-break-after: avoid !important;
        break-after: avoid-page !important;
    }
    .order-page-header {
        margin-bottom: 6px !important;
        page-break-inside: avoid;
    }
    .order-page-header img {
        width: 150px !important;
        margin-bottom: 4px !important;
    }
    .order-page-header .row {
        margin-bottom: 4px !important;
    }
    .order-page-header .col-lg-6 {
        display: inline-block;
        width: 49%;
        margin-right: 0;
        vertical-align: top;
        box-sizing: border-box;
    }
    .order-page-header hr {
        margin: 4px 0 !important;
    }
    .float_left {
        width: 49% !important;
        float: left !important;
/*        background-color: blue !important;*/
    }
    .float_left_sm {
        width: 47% !important;
        float: left !important;
/*        background-color: yellow !important;*/
        text-align: right;
    }

}

</style>

<?php 
include 'page_menues.php';
?>

<?= $payNowForm ?>

<?php
if ($admin_id !== NULL) {
    echo '<div class="no-print admin-mode-bar">Admin view</div>';
}
?>

<div class="my-account pt-20 pb-50">
  <div class="container">

<?php if ($no_order_found === false): ?>


<div class="admin-print-wrapper">

<div style="margin-bottom: 20px; margin-top: 20px;">

    <div class="order-page-header print-only">

        <div class="row mb-4 clearfix">
            <div class="col-lg-6 float_left">
                <!-- Left column: User's logo, company name, etc. -->
                <img src="../assets/img/logo/logo.png" alt="logo" width="250px" class="mb-4">
                <br>
                <strong>Suppliers and distributors of marine collagen, fish gelatine and wellness ingredients</strong>
                <!-- ... Other company info ... -->
            </div>
            <div class="col-lg-6 float_left_sm">

                <strong>Sir Francis Pty Ltd</strong><br>
                Port Elizabeth, Eastern Cape<br>
                South Africa<br>
                Email: info@fishgelatine.co.za<br>
                Phone: +27 41 001 1786

            </div>
        </div>

        <hr class="w-100">

    </div>

    <h1 class="mb-4" style="font-weight:bold;font-size:2em !important">ORDER <?=$order_id?></h1>
    <a href="manage_order?order_id=<?=$order_id?>" class="no-print btn btn-secondary my-3">Edit Order</a>

    <table class="table table-bordered table-striped no-print" style="width: 100%; border-collapse: collapse; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff; text-align: left;">
        <thead>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Order Number:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($order_id) ?></td>
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
                <th style="padding: 10px; border: 1px solid #ccc;">Delivery Method:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($fetched_delivery_label) ?></td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Deliver To:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($fetched_shipping_address) ?></td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Total Weight Estimate:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($fetched_weight_label) ?></td>
            </tr>
            <tr class="no-print">
                <th style="padding: 10px; border: 1px solid #ccc;">Order Status:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($fetched_order_status) ?></td>
            </tr>
            <tr class="no-print">
                <th style="padding: 10px; border: 1px solid #ccc;">Payment Method:</th>
                <td style="padding: 10px; border: 1px solid #ccc;"><?= htmlspecialchars($fetched_payment_method_label) ?></td>
            </tr>
            <tr class="no-print">
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
            <?php if ($fetched_coupon_amount > 0): ?>
                <tr>
                    <td colspan="6" style="padding: 10px; border: none; text-align: right;">
                        <div class="text-success" style="border: 1px dashed #000; padding: 10px; display: inline-block;">
                            COUPON<?= $fetched_coupon_code ? ' (' . htmlspecialchars($fetched_coupon_code) . ')' : '' ?>: Congratulations, you saved R<?= htmlspecialchars(number_format($fetched_coupon_amount, 2)) ?>!
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
                    Shipping (<?= htmlspecialchars($fetched_delivery_label) ?>):
                </th>
                <th style="padding: 10px; border: none; text-align: right;">
                    <?php if ($fetched_shipping_discount_amount > 0): ?>
                        <span style="text-decoration: line-through;">R<?= htmlspecialchars(number_format((float)$fetched_shipping_amount, 2)) ?></span>
                        <span style="margin-left: 10px;">R<?= htmlspecialchars(number_format($fetched_shipping_payable, 2)) ?></span>
                    <?php else: ?>
                        R<?= htmlspecialchars(number_format((float)$fetched_shipping_amount, 2)) ?>
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

    <a href="manage_orders" class="no-print btn btn-secondary">Back to Orders</a>
    <button type="button" class="no-print btn btn-outline-dark" onclick="window.print();">Print Invoice</button>
    <button type="button" class="no-print btn btn-outline-dark" onclick="document.body.classList.add('packlist-mode'); window.print(); setTimeout(function(){document.body.classList.remove('packlist-mode');}, 500);">Print Packlist</button>
    <a href="order_receipt?order_id=<?= htmlspecialchars((string) $fetched_id) ?>" target="_blank" rel="noopener noreferrer" class="no-print btn btn-outline-dark">Print Receipt</a>
    <form class="no-print d-inline-flex align-items-center mt-2" action="order_receipt" method="get" target="_blank" style="gap:6px;">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars((string) $fetched_id) ?>">
        <span>Delivery copy</span>
        <input type="number" name="delivery_copy" min="1" max="99" value="1" class="form-control form-control-sm" style="width:70px;">
        <span>of</span>
        <input type="number" name="delivery_total" min="1" max="99" value="2" class="form-control form-control-sm" style="width:70px;">
        <button type="submit" class="btn btn-outline-dark btn-sm">Print Delivery Receipt</button>
    </form>
    <?php if ((int) $fetched_payment_status === 0): ?>
        <button type="button" class="no-print btn btn-success" id="mark-paid-btn" data-order-id="<?= htmlspecialchars($fetched_id) ?>">Mark Paid</button>
    <?php endif; ?>
</div>

</div>

<?php else: ?>
<div style="margin: 20px 0; padding: 20px; border: 1px solid #ccc; background-color: #f9f9f9; text-align: center;">
    <h3 style="font-size: 18px; font-weight: bold; font-family: 'Raleway', sans-serif; ">No order found with this order number</h3>
    <p style="font-family: 'Raleway', sans-serif; color: #333;">Please check the order number and try again, or contact our support team for assistance.</p>
    <a href="manage_orders" style="text-decoration: none; color: #007bff; font-family: 'Raleway', sans-serif;">Back to Orders</a>
</div>
<?php endif; ?>

  </div>
</div>


<?php include "../footer.php"; ?>
<script>
$(function() {
  $('#mark-paid-btn').on('click', function() {
    var orderId = $(this).data('order-id');
    if (!confirm('Mark this order as paid?')) return;
    $.ajax({
      url: 'mark_order_paid.php',
      method: 'POST',
      dataType: 'json',
      data: { order_id: orderId, payment_status: 2 },
      success: function(response) {
        if (typeof showNotification === 'function') {
          showNotification(!!response.success, response.message || 'Payment status updated.');
        }
        if (response.success) {
          window.location.reload();
        }
      }
    });
  });
});
</script>
