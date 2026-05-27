<?php
include 'session_logins.php';
include_once __DIR__ . '/product_sheet_helpers.php';

include('apply-coupon-function.php');

// Fetch cart items based on user or guest
$cartItems = getCartItems($userId, $guestIdentifier);

// Format cart items
$subtotals = 0;
$discounts = 0;
$cart_total = 0;
$taxes = 0;

foreach ($cartItems as $item) {
    
    $product_name = $item['title'];
    $product_weight = $item['product_weight'];
    $price = $item['price'];
    $quantity = $item['quantity'];
    $discount_rate = $item['discount_rate'];
    $tax = !empty($item['tax_amount']) ? $item['tax_amount'] : 0;

    // Calculate discount amount based on discount rate
    $discount = 0;
    if ($discount_rate > 0) {
        $discount = ($price * $discount_rate) / 100;
    }

    // Apply discounts to price for further calculations
    $discounted_price = $price - $discount;

    // Calculate subtotal without tax and discounts
    $subtotal = $quantity * $discounted_price;

    // Accumulate taxes and discounts
    $taxes += $quantity * $tax;
    $discounts += $quantity * $discount;

    // Accumulate subtotals
    $subtotals += ($quantity * $price);

}

$cart_total += $subtotals;
$cart_total += $taxes;
$cart_total -= $discounts;

// Update session variables
$_SESSION['cart_total'] = $cart_total;

// Retrieve product ID from the AJAX request
$productId = isset($_POST['productId']) ? trim((string) $_POST['productId']) : null;
$postedClearanceId = strtoupper(trim((string) ($_POST['clearanceId'] ?? $_POST['clearance_id'] ?? '')));
$quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

$offcanvas_cart = "";

if (!($conn instanceof mysqli)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Cart is unavailable until the local database is connected.',
        'product_id' => $productId,
        'cart' => [
            'subtotal' => '0.00',
            'item_quantity' => 0,
            'product' => [
                'id' => $productId
            ]
        ],
        'offcanvascart' => ''
    ]);
    exit;
}

$clearanceId = '';
$sourceProductId = $productId;
if (stripos((string) $productId, 'CLR:') === 0) {
    $clearanceId = strtoupper(substr((string) $productId, 4));
} elseif ($postedClearanceId !== '') {
    $clearanceId = strpos($postedClearanceId, 'CLR:') === 0 ? substr($postedClearanceId, 4) : $postedClearanceId;
}

if ($clearanceId !== '') {
    $clearanceRow = getSheetClearanceRowById($clearanceId);
    $sourceProductId = $clearanceRow['product_id'] ?? '';
    $sheetProductForStock = $clearanceRow ? buildCandybirdClearanceProduct($clearanceRow) : null;
} else {
    $sheetProductForStock = getSheetProductById($productId);
}
if (!$sheetProductForStock) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Product could not be found.']);
    exit;
}
ensureCandybirdCartTimestampColumn($conn);
ensureCandybirdCartClearanceColumns($conn);

// Check if the product is already in the cart
$checkSql = "SELECT id, quantity FROM cart WHERE (user_id = ? OR guest_identifier = ?) AND product_id = ? AND COALESCE(clearance_id, '') = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error in preparing statement']);
} else {
    mysqli_stmt_bind_param($checkStmt, "isss", $userId, $guestIdentifier, $sourceProductId, $clearanceId);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $cartId, $currentQuantity);
    mysqli_stmt_fetch($checkStmt);

    // Close the check statement
    mysqli_stmt_close($checkStmt);

    if ($cartId) {
        // Product already in the cart, update the quantity
        $newQuantity = $currentQuantity + $quantity;
        $availableStock = getCandybirdAvailableStockForCart($conn, $sheetProductForStock, $userId, $guestIdentifier);
        if ($availableStock !== null && $newQuantity > $availableStock) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Only ' . $availableStock . ' available for this item right now.', 'product_id' => $productId]);
            exit;
        }

        $updateSql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);

        if (!$updateStmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error in preparing update statement']);
        } else {
            mysqli_stmt_bind_param($updateStmt, "ii", $newQuantity, $cartId);
            mysqli_stmt_execute($updateStmt);

            if (mysqli_stmt_errno($updateStmt)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error in executing update statement']);
            } else {
                // Close the update statement
                mysqli_stmt_close($updateStmt);

                // Update cart total after modifying the cart
                updateCartTotal($conn, $userId, $guestIdentifier);

                // Fetch new cart parameters after update
                $cartParams = fetchCartParameters($conn, $userId, $guestIdentifier, $productId);

                // Return a success response with new cart parameters
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product quantity updated in cart', 'product_id' => $productId, 'cart' => $cartParams]);
            }
        }
    } else {
        $availableStock = getCandybirdAvailableStockForCart($conn, $sheetProductForStock, $userId, $guestIdentifier);
        if ($availableStock !== null && $quantity > $availableStock) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Only ' . $availableStock . ' available for this item right now.', 'product_id' => $productId]);
            exit;
        }

        // Insert into the cart table
        $insertSql = "INSERT INTO cart (user_id, guest_identifier, product_id, clearance_id, quantity, updated_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $insertStmt = mysqli_prepare($conn, $insertSql);

        if (!$insertStmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error in preparing insert statement']);
        } else {
            mysqli_stmt_bind_param($insertStmt, "isssi", $userId, $guestIdentifier, $sourceProductId, $clearanceId, $quantity);
            mysqli_stmt_execute($insertStmt);

            if (mysqli_stmt_errno($insertStmt)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error in executing insert statement']);
            } else {
                // Close the insert statement
                mysqli_stmt_close($insertStmt);

                // Update cart total after inserting into the cart
                updateCartTotal($conn, $userId, $guestIdentifier);

                // Fetch new cart parameters after insert
                $cartParams = fetchCartParameters($conn, $userId, $guestIdentifier, $productId);

                $offCanvasCartItems = getCartItems($userId, $guestIdentifier);
                foreach ($offCanvasCartItems as $item) {
                    $image_url = isset($item['image_url']) ? $item['image_url'] : 'assets/img/product/1.png';

                    $offcanvas_cart .= '<li>';
                    $itemLink = $item['product_url'] ?? ('product?id=' . urlencode((string) $item['id']));
                    $offcanvas_cart .= '<a href="' . $itemLink . '" class="image">';
                    $offcanvas_cart .= '<img src="' . $image_url . '" alt="Cart product Image"/>';
                    $offcanvas_cart .= '</a>';
                    $offcanvas_cart .= '<div class="content">';
                    $offcanvas_cart .= '<a href="' . $itemLink . '" class="title">' . $item['title'] . (!empty($item['is_clearance']) && $item['is_clearance'] === 'yes' ? ' <small class="text-danger">(Clearance)</small>' : '') . '</a>';
                    $offcanvas_cart .= '<span class="quantity-price">' . $item['quantity'] . ' x <span class="amount">R' . $item['discounted_price'] . '</span>';
                    $offcanvas_cart .= '<span><a href="#" class="remove removeFromCart" data-product-id="' . $item['id'] . '">×</a></span>';
                    $offcanvas_cart .= '</div>';
                    $offcanvas_cart .= '</li>';
                }

                $offcanvascart_html = '
                <ul class="minicart-product-list">
                  '.$offcanvas_cart.'
                </ul>
                ';

                // Return a success response with new cart parameters
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product added to cart', 'product_id' => $productId, 'cart' => $cartParams, 'offcanvascart' => $offcanvascart_html]);
            }
        }
    }
}

// Function to fetch cart parameters
function fetchCartParameters($conn, $userId, $guestIdentifier, $productId) {
    $cartParamsSql = "SELECT product_id, clearance_id, quantity
                      FROM cart
                      WHERE user_id = ? OR guest_identifier = ?";
    $cartParamsStmt = mysqli_prepare($conn, $cartParamsSql);

    if (!$cartParamsStmt) {
        return ['subtotal' => 0, 'item_quantity' => 0, 'product' => []]; // Return default values if there's an error
    }

    mysqli_stmt_bind_param($cartParamsStmt, "ss", $userId, $guestIdentifier);
    mysqli_stmt_execute($cartParamsStmt);
    $result = mysqli_stmt_get_result($cartParamsStmt);

    $subtotal = 0;
    $item_quantity = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $product = !empty($row['clearance_id']) ? buildCandybirdClearanceProduct(getSheetClearanceRowById($row['clearance_id'])) : getSheetProductById($row['product_id']);
        if (!$product) continue;

        $quantity = (int) $row['quantity'];
        $subtotal += getSheetProductPrice($product) * $quantity;
        $item_quantity += $quantity;
    }

    mysqli_stmt_close($cartParamsStmt);

    $product = stripos((string) $productId, 'CLR:') === 0 ? buildCandybirdClearanceProduct(getSheetClearanceRowById(substr((string) $productId, 4))) : getSheetProductById($productId);
    $title = $product ? getSheetProductDisplayTitle($product) : '';
    $original_price = $product ? (float) ($product['price'] ?? 0) : 0;
    $final_price = $product ? getSheetProductPrice($product) : 0;
    $imageUrl = $product ? getSheetProductImage($product) : 'assets/img/product/1.png';

    return [
        'subtotal' => number_format($subtotal, 2),
        'item_quantity' => $item_quantity,
        'product' => [
            'title' => $title,
            'id' => $productId,
            'price' => number_format($final_price, 2),
            'original_price' => number_format($original_price > 0 ? $original_price : $final_price, 2),
            'discounted_price' => number_format($final_price, 2),
            'has_discount' => $original_price > 0 && $final_price < $original_price,
            'image_url' => $imageUrl
        ]
    ];
}


// Call applyCoupon if a coupon code is provided
if (isset($_SESSION['coupon'])) {
    $coupon_code = $_SESSION['coupon'];
    $coupon_code = $coupon_code['code'];
    applyCoupon($conn, $coupon_code, $userId, $guestIdentifier);
}


// Close the database connection
mysqli_close($conn);

?>
