<?php
include 'dbh.inc.php';
include 'admin_variables.php';
require_once __DIR__ . '/admin_order_totals.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

// Initialize response array
$response = array('success' => false, 'message' => '');

// Retrieve product ID and quantity from the AJAX request
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;
$order_id = isset($_POST['orderId']) ? $_POST['orderId'] : null;


if ($product_id === null || $order_id === null || $quantity < 1) {
    $response['message'] = 'Invalid product ID, quantity, or order ID.';
    echo json_encode($response);
    exit();
}

$sheetProduct = getSheetProductById($product_id);
if ($sheetProduct) {
    $sheetStock = getSheetProductStockQty($sheetProduct);
    if ($sheetStock !== null && (int) $quantity > $sheetStock) {
        $response['message'] = 'Only ' . $sheetStock . ' available for this product.';
        echo json_encode($response);
        exit();
    }
}

// Update order item quantity
$updateSql = "UPDATE order_items SET quantity = ? WHERE product_id = ? AND order_id = ?";
$updateStmt = mysqli_prepare($conn, $updateSql);

if (!$updateStmt) {
    $response['message'] = 'Error in preparing statement: ' . mysqli_error($conn);
    echo json_encode($response);
    exit();
}

mysqli_stmt_bind_param($updateStmt, "iii", $quantity, $product_id, $order_id);
$success = mysqli_stmt_execute($updateStmt);

if (!$success) {
    $response['message'] = 'Error in executing statement: ' . mysqli_stmt_error($updateStmt);
    echo json_encode($response);
    exit();
}

$affectedRows = mysqli_stmt_affected_rows($updateStmt);
if ($affectedRows === 0) {
    $response['message'] = 'No rows updated. Check if the product ID and order ID match any records.';
    echo json_encode($response);
    exit();
}

// Close the update statement
mysqli_stmt_close($updateStmt);

$totalsResult = cbAdminRecalculateOrderTotals($conn, (int) $order_id);
if (empty($totalsResult['success'])) {
    echo json_encode($totalsResult);
    exit();
}

// Set success response
$response['success'] = true;
$response['message'] = 'Order item quantity updated and totals recalculated.';
$response['totals'] = $totalsResult;

// Return JSON response
echo json_encode($response);
?>
