<?php
include 'session_logins.php';

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
$productId = isset($_POST['productId']) ? $_POST['productId'] : null;
$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;

$offcanvas_cart = "";

// Check if the product is already in the cart
$checkSql = "SELECT id, quantity FROM cart WHERE (user_id = ? OR guest_identifier = ?) AND product_id = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error in preparing statement']);
} else {
    mysqli_stmt_bind_param($checkStmt, "iss", $userId, $guestIdentifier, $productId);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $cartId, $currentQuantity);
    mysqli_stmt_fetch($checkStmt);

    // Close the check statement
    mysqli_stmt_close($checkStmt);

    if ($cartId) {
        // Product already in the cart, update the quantity
        $newQuantity = $currentQuantity + $quantity;

        $updateSql = "UPDATE cart SET quantity = ? WHERE id = ?";
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
                echo json_encode(['success' => true, 'message' => 'Product quantity updated in cart', 'cart' => $cartParams]);
            }
        }
    } else {
        // Insert into the cart table
        $insertSql = "INSERT INTO cart (user_id, guest_identifier, product_id, quantity) VALUES (?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertSql);

        if (!$insertStmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error in preparing insert statement']);
        } else {
            mysqli_stmt_bind_param($insertStmt, "issi", $userId, $guestIdentifier, $productId, $quantity);
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
                    $offcanvas_cart .= '<a href="product?id=' .$item['id']. '" class="image">';
                    $offcanvas_cart .= '<img src="' . $image_url . '" alt="Cart product Image"/>';
                    $offcanvas_cart .= '</a>';
                    $offcanvas_cart .= '<div class="content">';
                    $offcanvas_cart .= '<a href="product?id=' .$item['id']. '" class="title">' . $item['title'] . '</a>';
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
                echo json_encode(['success' => true, 'message' => 'Product added to cart', 'cart' => $cartParams, 'offcanvascart' => $offcanvascart_html]);
            }
        }
    }
}

// Function to fetch cart parameters
function fetchCartParameters($conn, $userId, $guestIdentifier, $productId) {
    // Calculate subtotal and item quantity
    $cartParamsSql = "SELECT SUM((p.price - p.discount_amount) * c.quantity) AS subtotal, SUM(c.quantity) AS item_quantity
                      FROM cart c
                      JOIN product p ON c.product_id = p.id
                      WHERE (c.user_id = ? OR c.guest_identifier = ?)";
    $cartParamsStmt = mysqli_prepare($conn, $cartParamsSql);

    if (!$cartParamsStmt) {
        return ['subtotal' => 0, 'item_quantity' => 0, 'product' => []]; // Return default values if there's an error
    }

    mysqli_stmt_bind_param($cartParamsStmt, "is", $userId, $guestIdentifier);
    mysqli_stmt_execute($cartParamsStmt);
    mysqli_stmt_bind_result($cartParamsStmt, $subtotal, $item_quantity);
    mysqli_stmt_fetch($cartParamsStmt);
    mysqli_stmt_close($cartParamsStmt);

    // Fetch product details
    $productDetailsSql = "SELECT p.title, p.price - p.discount_amount AS final_price, i.image_url
                          FROM product p
                          LEFT JOIN images i ON p.id = i.product_id
                          WHERE p.id = ? AND p.enabled = 1
                          LIMIT 1";
    $productDetailsStmt = mysqli_prepare($conn, $productDetailsSql);

    if (!$productDetailsStmt) {
        return ['subtotal' => number_format($subtotal, 2), 'item_quantity' => $item_quantity, 'product' => []]; // Return default values if there's an error
    }

    mysqli_stmt_bind_param($productDetailsStmt, "i", $productId);
    mysqli_stmt_execute($productDetailsStmt);
    mysqli_stmt_bind_result($productDetailsStmt, $title, $final_price, $imageUrl);
    mysqli_stmt_fetch($productDetailsStmt);
    mysqli_stmt_close($productDetailsStmt);

    return [
        'subtotal' => number_format($subtotal, 2),
        'item_quantity' => $item_quantity,
        'product' => [
            'title' => $title,
            'price' => number_format($final_price, 2),
            'image_url' => $imageUrl
        ]
    ];
}

// Close the database connection
mysqli_close($conn);

?>
