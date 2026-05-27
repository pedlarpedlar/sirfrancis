<?php
include 'session_logins.php';
// Include your database connection file
include_once "dbh.inc.php";
require_once __DIR__ . '/product_sheet_helpers.php';

ensureCandybirdCartClearanceColumns($conn);

// Retrieve product ID from the AJAX request
$product_id = isset($_POST['product_id']) ? trim((string) $_POST['product_id']) : null;
$clearance_id = '';
$source_product_id = $product_id;
if (stripos((string) $product_id, 'CLR:') === 0) {
    $clearance_id = strtoupper(substr((string) $product_id, 4));
    $clearance_row = getSheetClearanceRowById($clearance_id);
    $source_product_id = $clearance_row['product_id'] ?? 0;
}

if ($product_id === null) {
    die('Product ID is required.');
}

// Cart item exists and belongs to the current user or guest, proceed with deletion
$deleteSql = "DELETE FROM cart WHERE product_id = ? AND COALESCE(clearance_id, '') = ? AND (user_id = ? OR guest_identifier = ?)";
$deleteStmt = mysqli_prepare($conn, $deleteSql);

if (!$deleteStmt) {
    die('Error in preparing statement: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param($deleteStmt, "isis", $source_product_id, $clearance_id, $userId, $guestIdentifier);
mysqli_stmt_execute($deleteStmt);

if (mysqli_stmt_errno($deleteStmt)) {
    die('Error in executing statement: ' . mysqli_stmt_error($deleteStmt));
}

// Close the delete statement
mysqli_stmt_close($deleteStmt);

// Update cart total and coupon state after removing from the cart
updateCartTotal($conn, $userId, $guestIdentifier);
if (isset($_SESSION['coupon'])) {
    calculateCouponDiscount($conn, $userId, $guestIdentifier);
}

// Fetch new cart parameters after removal
$cartParams = fetchCartParameters($conn, $userId, $guestIdentifier);

header('Content-Type: application/json');
// Return a response with new cart parameters
echo json_encode([
    'success' => true,
    'subtotal' => $cartParams['subtotal'],
    'item_quantity' => $cartParams['item_quantity'],
    'coupon_code' => $_SESSION['coupon']['code'] ?? '',
    'coupon_savings' => $_SESSION['coupon']['coupon_savings'] ?? 0,
    'message' => 'Product removed from cart.'
]);

// Function to fetch cart parameters
function fetchCartParameters($conn, $userId, $guestIdentifier) {
    $subtotal = 0;
    $item_quantity = 0;

    foreach (getCartItems($userId, $guestIdentifier) as $item) {
        $quantity = (int) ($item['quantity'] ?? 0);
        $subtotal += ((float) ($item['final_price'] ?? $item['discounted_price'] ?? $item['price'] ?? 0)) * $quantity;
        $item_quantity += $quantity;
    }

    return ['subtotal' => number_format($subtotal, 2), 'item_quantity' => $item_quantity];
}

?>
