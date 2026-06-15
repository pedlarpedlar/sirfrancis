<?php
// Include your database connection file
include_once "dbh.inc.php";
include 'admin_variables.php';
require_once __DIR__ . '/admin_order_totals.php';

// Retrieve product ID and order ID from the AJAX request
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
$orderId = isset($_POST['orderId']) ? $_POST['orderId'] : null;

$response = array('success' => false, 'message' => '');

// Validate input
if ($product_id === null || $orderId === null) {
    $response['message'] = 'Product ID and Order ID are required.';
    echo json_encode($response);
    exit();
}

// Check if there are any products in the order
$checkSql = "SELECT COUNT(*) AS item_count FROM order_items WHERE order_id = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    $response['message'] = 'Error in preparing statement: ' . mysqli_error($conn);
    echo json_encode($response);
    exit();
}

mysqli_stmt_bind_param($checkStmt, "i", $orderId);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_bind_result($checkStmt, $item_count);
mysqli_stmt_fetch($checkStmt);
mysqli_stmt_close($checkStmt);

if ($item_count <= 0) {
    $response['message'] = 'No items found in the order.';
    echo json_encode($response);
    exit();
}

// Proceed with deletion if items exist
$deleteSql = "DELETE FROM order_items WHERE product_id = ? AND order_id = ?";
$deleteStmt = mysqli_prepare($conn, $deleteSql);

if (!$deleteStmt) {
    $response['message'] = 'Error in preparing statement: ' . mysqli_error($conn);
    echo json_encode($response);
    exit();
}

mysqli_stmt_bind_param($deleteStmt, "ii", $product_id, $orderId);
$success = mysqli_stmt_execute($deleteStmt);

if (!$success) {
    $response['message'] = 'Error in executing statement: ' . mysqli_stmt_error($deleteStmt);
    echo json_encode($response);
    exit();
}

// Check if the deletion was successful
if (mysqli_stmt_affected_rows($deleteStmt) === 0) {
    $response['message'] = 'No rows deleted. Check if the product ID and order ID match any records.';
    echo json_encode($response);
    exit();
}

// Close the delete statement
mysqli_stmt_close($deleteStmt);

// Update order totals using the same shipping/coupon rules as checkout.
$totalsResult = cbAdminRecalculateOrderTotals($conn, (int) $orderId);
if (empty($totalsResult['success'])) {
    echo json_encode($totalsResult);
    exit();
}

$response['success'] = true;
$response['message'] = 'Product removed and order totals recalculated.';
$response['totals'] = $totalsResult;
echo json_encode($response);
exit();

// Legacy fallback below is intentionally bypassed.
// Update order totals
$orderTotalsSql = "
    UPDATE orders o
    JOIN (
        SELECT 
            order_id, 
            SUM(quantity * price) AS subtotal, 
            SUM(quantity * discount_amount) AS total_discount, 
            SUM(quantity * (price - discount_amount + tax_amount)) AS total_items
        FROM order_items 
        WHERE order_id = ?
    ) oi ON o.id = oi.order_id
    SET 
        o.subtotal_amount = oi.subtotal, 
        o.discount_amount = oi.total_discount, 
        o.grand_total_amount = oi.subtotal - oi.total_discount - o.coupon_amount
    WHERE o.id = ?";

$orderTotalsStmt = mysqli_prepare($conn, $orderTotalsSql);
if ($orderTotalsStmt) {
    mysqli_stmt_bind_param($orderTotalsStmt, "ii", $orderId, $orderId);
    if (!mysqli_stmt_execute($orderTotalsStmt)) {
        $response['message'] = 'Error in executing order update statement: ' . mysqli_stmt_error($orderTotalsStmt);
        echo json_encode($response);
        exit();
    }
    mysqli_stmt_close($orderTotalsStmt);
} else {
    $response['message'] = 'Error in preparing order totals statement: ' . mysqli_error($conn);
    echo json_encode($response);
    exit();
}

// Check if grand total falls below the free shipping amount and adjust shipping discount if necessary
$checkTotalSql = "SELECT grand_total_amount, shipping_amount, shipping_discount_amount FROM orders WHERE id = ?";
$checkTotalStmt = mysqli_prepare($conn, $checkTotalSql);
if ($checkTotalStmt) {
    mysqli_stmt_bind_param($checkTotalStmt, "i", $orderId);
    mysqli_stmt_execute($checkTotalStmt);
    mysqli_stmt_bind_result($checkTotalStmt, $grandTotalAmount, $shippingAmount, $shippingDiscountAmount);
    mysqli_stmt_fetch($checkTotalStmt);
    mysqli_stmt_close($checkTotalStmt);


        if ($grandTotalAmount < $free_shipping_amount) {
            // Grand total is below the free shipping amount
            // if ($shippingDiscountAmount > 0) {
                // Remove the discount applied
                $updateShippingDiscountSql = "UPDATE orders SET shipping_discount_amount = 0, grand_total_amount = grand_total_amount + shipping_amount WHERE id = ?";
                $updateShippingDiscountStmt = mysqli_prepare($conn, $updateShippingDiscountSql);
                if ($updateShippingDiscountStmt) {
                    mysqli_stmt_bind_param($updateShippingDiscountStmt, "i", $orderId);
                    mysqli_stmt_execute($updateShippingDiscountStmt);
                    mysqli_stmt_close($updateShippingDiscountStmt);
                } else {
                    $response['message'] = 'Error in preparing shipping discount update statement: ' . mysqli_error($conn);
                    echo json_encode($response);
                    exit();
                }
            // }
        } else {
            // Grand total exceeds the free shipping amount, apply the discount if not already applied
            // if ($shippingDiscountAmount === 0) {
                $updateShippingDiscountSql = "UPDATE orders SET shipping_discount_amount = shipping_amount WHERE id = ?";
                $updateShippingDiscountStmt = mysqli_prepare($conn, $updateShippingDiscountSql);
                if ($updateShippingDiscountStmt) {
                    mysqli_stmt_bind_param($updateShippingDiscountStmt, "i", $orderId);
                    mysqli_stmt_execute($updateShippingDiscountStmt);
                    mysqli_stmt_close($updateShippingDiscountStmt);
                } else {
                    $response['message'] = 'Error in preparing shipping discount update statement: ' . mysqli_error($conn);
                    echo json_encode($response);
                    exit();
                }
            // }
        }
    
} else {
    $response['message'] = 'Error in preparing grand total check statement: ' . mysqli_error($conn);
    echo json_encode($response);
    exit();
}

// Set success response
$response['success'] = true;
$response['message'] = 'Product removed successfully and order totals adjusted.';

// Return JSON response
echo json_encode($response);
?>
