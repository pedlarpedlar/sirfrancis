<?php
// Include your database connection file
include_once "dbh.inc.php";
include 'admin_variables.php';
require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/admin_order_totals.php';

// Retrieve product ID, quantity, and order ID from the AJAX request
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));
$orderId = isset($_POST['orderId']) ? $_POST['orderId'] : null;

$response = array('success' => false, 'message' => '');

// Validate input
if ($product_id === null || $orderId === null || $quantity < 1) {
    $response['message'] = 'Invalid product ID, quantity, or order ID.';
    echo json_encode($response);
    exit();
}

$sheetProduct = getSheetProductById($product_id);
if (!$sheetProduct) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit();
}

syncSheetProductMirrorToDb($conn, $sheetProduct);
ensureCandybirdOrderItemSnapshotColumns($conn);
cbAdminEnsureOrderItemDiscountColumns($conn);

$product_id = (int) $sheetProduct['id'];
$product_title = getSheetProductDisplayTitle($sheetProduct);
$product_image_url = getSheetProductEmailImage($sheetProduct);
$product_weight = getSheetProductDisplaySize($sheetProduct);
$product_price = (float) ($sheetProduct['price'] ?? 0);
$discounted_price = getSheetProductPrice($sheetProduct);
$discount_amount = max(0, $product_price - $discounted_price);
$product_tax = (float) ($sheetProduct['tax_amount'] ?? 0);
$sheetStock = getSheetProductStockQty($sheetProduct);

// Check if the product already exists in the order
$checkSql = "SELECT product_id, quantity FROM order_items WHERE product_id = ? AND order_id = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    $response['message'] = 'Error in preparing statement: ' . mysqli_error($conn);
    echo json_encode($response);
    exit();
}

mysqli_stmt_bind_param($checkStmt, "ii", $product_id, $orderId);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_bind_result($checkStmt, $existing_item_id, $existing_quantity);
mysqli_stmt_fetch($checkStmt);
mysqli_stmt_close($checkStmt);

// Update existing item quantity or insert a new item
if ($existing_item_id) {
    if ($sheetStock !== null && ((int) $existing_quantity + (int) $quantity) > $sheetStock) {
        $response['message'] = 'Only ' . $sheetStock . ' available for this product.';
        echo json_encode($response);
        exit();
    }

    // Update quantity if product already exists
    $updateSql = "UPDATE order_items SET quantity = quantity + ? WHERE product_id = ? AND order_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);

    if (!$updateStmt) {
        $response['message'] = 'Error in preparing statement: ' . mysqli_error($conn);
        echo json_encode($response);
        exit();
    }

    mysqli_stmt_bind_param($updateStmt, "iii", $quantity, $product_id, $orderId);
    $success = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    if (!$success) {
        $response['message'] = 'Error in executing statement: ' . mysqli_stmt_error($updateStmt);
        echo json_encode($response);
        exit();
    }

    $response['success'] = true;
    $response['message'] = 'Product quantity updated successfully.';
} else {
    if ($sheetStock !== null && (int) $quantity > $sheetStock) {
        $response['message'] = 'Only ' . $sheetStock . ' available for this product.';
        echo json_encode($response);
        exit();
    }

    // Insert new item if the product does not exist
    $insertSql = "INSERT INTO order_items (order_id, product_id, product_title, product_image_url, product_weight, quantity, price, discount_amount, tax_amount, admin_custom_discount_type, admin_custom_discount_value) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', 0)";
    $insertStmt = mysqli_prepare($conn, $insertSql);
    if ($insertStmt) {
        mysqli_stmt_bind_param($insertStmt, "iisssiddd", $orderId, $product_id, $product_title, $product_image_url, $product_weight, $quantity, $product_price, $discount_amount, $product_tax);
        if (!mysqli_stmt_execute($insertStmt)) {
            $response['message'] = 'Error in executing insert statement: ' . mysqli_stmt_error($insertStmt);
            echo json_encode($response);
            exit();
        }
        mysqli_stmt_close($insertStmt);
    } else {
        $response['message'] = 'Error in preparing insert statement: ' . mysqli_error($conn);
        echo json_encode($response);
        exit();
    }
}

// Calculate order totals (subtotal, discount, shipping, grand total) using the same rules as checkout.
$totalsResult = cbAdminRecalculateOrderTotals($conn, (int) $orderId);
if (empty($totalsResult['success'])) {
    echo json_encode($totalsResult);
    exit();
}

$response['success'] = true;
$response['message'] = 'Product added and order totals recalculated.';
$response['totals'] = $totalsResult;
echo json_encode($response);
exit();

// Legacy fallback below is intentionally bypassed.
// Calculate order totals (subtotal, discount, grand total) and update order table in a single step
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

// Check if grand total exceeds the free shipping amount and apply shipping discount
$checkGrandTotalSql = "SELECT grand_total_amount, shipping_amount, shipping_discount_amount FROM orders WHERE id = ?";
$checkGrandTotalStmt = mysqli_prepare($conn, $checkGrandTotalSql);

if ($checkGrandTotalStmt) {
    mysqli_stmt_bind_param($checkGrandTotalStmt, "i", $orderId);
    mysqli_stmt_execute($checkGrandTotalStmt);
    mysqli_stmt_bind_result($checkGrandTotalStmt, $grand_total, $shipping_amount, $shipping_discount_amount);
    mysqli_stmt_fetch($checkGrandTotalStmt);
    mysqli_stmt_close($checkGrandTotalStmt);

    // Assuming $free_shipping_amount is set and represents the threshold for free shipping
    // if (!empty($free_shipping_amount)) {
        if ($grand_total < $free_shipping_amount) {
            // Grand total is below the free shipping amount
            $updateShippingDiscountSql = "UPDATE orders SET shipping_discount_amount = 0 WHERE id = ?";
            $updateShippingDiscountStmt = mysqli_prepare($conn, $updateShippingDiscountSql);

            if ($updateShippingDiscountStmt) {
                mysqli_stmt_bind_param($updateShippingDiscountStmt, "i", $orderId);
                mysqli_stmt_execute($updateShippingDiscountStmt);
                mysqli_stmt_close($updateShippingDiscountStmt);

                // Adjust grand_total_amount
                $updateGrandTotalSql = "UPDATE orders SET grand_total_amount = grand_total_amount + shipping_amount WHERE id = ?";
                $updateGrandTotalStmt = mysqli_prepare($conn, $updateGrandTotalSql);

                if ($updateGrandTotalStmt) {
                    mysqli_stmt_bind_param($updateGrandTotalStmt, "i", $orderId);
                    mysqli_stmt_execute($updateGrandTotalStmt);
                    mysqli_stmt_close($updateGrandTotalStmt);
                } else {
                    $response['message'] = 'Error in preparing grand total update statement: ' . mysqli_error($conn);
                    echo json_encode($response);
                    exit();
                }
            } else {
                $response['message'] = 'Error in preparing shipping discount update statement: ' . mysqli_error($conn);
                echo json_encode($response);
                exit();
            }
        } else {
            // Grand total exceeds or meets the free shipping amount
            // if ($shipping_discount_amount === 0) {
                // Apply shipping discount
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
    // }
} else {
    $response['message'] = 'Error in preparing grand total check statement: ' . mysqli_error($conn);
    echo json_encode($response);
    exit();
}

// Response on successful execution
$response['success'] = true;
$response['message'] = 'Product added to order successfully.';

// Return JSON response
echo json_encode($response);
?>
