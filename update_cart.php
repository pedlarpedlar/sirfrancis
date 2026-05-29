<?php
include 'session_logins.php';
// Include your database connection file
include_once "dbh.inc.php";

// Retrieve product ID and quantity from the AJAX request
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : null;
$quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

if ($product_id === null || $quantity < 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity.']);
    exit;
}

if (!($conn instanceof mysqli)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Cart is temporarily unavailable. Please try again shortly.']);
    exit;
}

$rawProductId = trim((string) ($_POST['product_id'] ?? ''));
$clearanceId = '';
$sourceProductId = $product_id;
if (stripos($rawProductId, 'CLR:') === 0) {
    $clearanceId = strtoupper(substr($rawProductId, 4));
    $clearanceRow = getSheetClearanceRowById($clearanceId);
    $sourceProductId = (int) ($clearanceRow['product_id'] ?? 0);
    $sheetProductForStock = $clearanceRow ? buildCandybirdClearanceProduct($clearanceRow) : null;
} else {
    $sheetProductForStock = getSheetProductById($product_id);
}
if (!$sheetProductForStock) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Product could not be found.']);
    exit;
}
ensureCandybirdCartClearanceColumns($conn);

ensureCandybirdCartTimestampColumn($conn);
$availableStock = getCandybirdAvailableStockForCart($conn, $sheetProductForStock, $userId, $guestIdentifier);
if ($availableStock !== null && $quantity > $availableStock) {
    header('Content-Type: application/json');
    $stockMessage = $availableStock <= 0 ? 'This item is sold out. Please remove it from your cart.' : 'Only ' . $availableStock . ' available for this item right now.';
    echo json_encode(['success' => false, 'message' => $stockMessage]);
    exit;
}

// Update cart item quantity for the current user or guest
$updateSql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE product_id = ? AND COALESCE(clearance_id, '') = ? AND (user_id = ? OR guest_identifier = ?)";
$updateStmt = mysqli_prepare($conn, $updateSql);

if (!$updateStmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Could not update cart. Please try again.']);
    exit;
}

mysqli_stmt_bind_param($updateStmt, "iisis", $quantity, $sourceProductId, $clearanceId, $userId, $guestIdentifier);
mysqli_stmt_execute($updateStmt);

if (mysqli_stmt_errno($updateStmt)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Could not update cart. Please try again.']);
    exit;
}

// Close the update statement
mysqli_stmt_close($updateStmt);

// Update cart total and coupon state after updating the cart
updateCartTotal($conn, $userId, $guestIdentifier);
if (isset($_SESSION['coupon'])) {
    calculateCouponDiscount($conn, $userId, $guestIdentifier);
}

// Fetch new cart parameters after update
$cartParams = fetchCartParameters($conn, $userId, $guestIdentifier);

header('Content-Type: application/json');
// Return a response with new cart parameters
echo json_encode([
    'success' => true,
    'subtotal' => $cartParams['subtotal'],
    'item_quantity' => $cartParams['item_quantity'],
    'coupon_code' => $_SESSION['coupon']['code'] ?? '',
    'coupon_savings' => $_SESSION['coupon']['coupon_savings'] ?? 0,
    'message' => 'Cart updated.'
]);

// Function to fetch cart parameters
function fetchCartParameters($conn, $userId, $guestIdentifier) {
    $subtotal = 0;
    $item_quantity = 0;

    foreach (getCartItems($userId, $guestIdentifier) as $item) {
        $quantity = (int) ($item['quantity'] ?? 0);
        $subtotal += ((float) ($item['discounted_price'] ?? $item['price'] ?? 0)) * $quantity;
        $item_quantity += $quantity;
    }

    return ['subtotal' => number_format($subtotal, 2), 'item_quantity' => $item_quantity];
}

?>
