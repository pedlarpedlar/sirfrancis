<?php
include 'session_logins.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assuming your form has input fields with appropriate names
    $billing_first_name = $_POST['billing_first_name'];
    $billing_last_name = $_POST['billing_last_name'];
    $billing_company_name = $_POST['billing_company_name'];
    $billing_street_address_1 = $_POST['billing_street_address_1'];
    $billing_street_address_2 = $_POST['billing_street_address_2'];
    $billing_city = $_POST['billing_city'];
    $billing_country = $_POST['billing_country'];
    $billing_province = $_POST['billing_province'];
    $billing_post_code = $_POST['billing_post_code'];
    $billing_phone_number = $_POST['billing_phone_number'];
    $billing_email_address = $_POST['billing_email_address'];

    $username = !empty($_POST['user-name']) ? $_POST['user-name'] : '';

    $ship_to_different_address = isset($_POST['ship_to_different_address']) ? 1 : 0;

    if ($ship_to_different_address) {
        $shipping_first_name = $_POST['shipping_first_name'];
        $shipping_last_name = $_POST['shipping_last_name'];
        $shipping_company_name = $_POST['shipping_company_name'];
        $shipping_street_address_1 = $_POST['shipping_street_address_1'];
        $shipping_street_address_2 = $_POST['shipping_street_address_2'];
        $shipping_city = $_POST['shipping_city'];
        $shipping_country = $_POST['shipping_country'];
        $shipping_province = $_POST['shipping_province'];
        $shipping_post_code = $_POST['shipping_post_code'];
        $shipping_phone_number = $_POST['shipping_phone_number'];
        $shipping_email_address = $_POST['shipping_email_address'];
    } else {
        // If not shipping to a different address, use billing details for shipping
        $shipping_first_name = $billing_first_name;
        $shipping_last_name = $billing_last_name;
        $shipping_company_name = $billing_company_name;
        $shipping_street_address_1 = $billing_street_address_1;
        $shipping_street_address_2 = $billing_street_address_2;
        $shipping_city = $billing_city;
        $shipping_country = $billing_country;
        $shipping_province = $billing_province;
        $shipping_post_code = $billing_post_code;
        $shipping_phone_number = $billing_phone_number;
        $shipping_email_address = $billing_email_address;
    }

    // Fetch cart items for the current user or guest identifier
    $sqlCart = "SELECT * FROM cart WHERE user_id = ? OR guest_identifier = ?";
    $stmtCart = mysqli_prepare($conn, $sqlCart);
    mysqli_stmt_bind_param($stmtCart, "is", $userId, $guestIdentifier);
    mysqli_stmt_execute($stmtCart);
    $resultCart = mysqli_stmt_get_result($stmtCart);

    if (mysqli_num_rows($resultCart) == 0) {
    
        $response = array(
            "success" => false,
            "message" => "No items in order!"
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

        // Create a shipping address variable
        $shipping_address = "$shipping_first_name $shipping_last_name" . ($shipping_company_name ? ",\n$shipping_company_name" : "")
        . ($shipping_street_address_1 ? ",\n$shipping_street_address_1" : "")
        . ($shipping_street_address_2 ? ",\n$shipping_street_address_2" : "")
        . ($shipping_city ? ",\n$shipping_city" : "")
        . ($shipping_country ? ",\n$shipping_country" : "")
        . ($shipping_province ? ",\n$shipping_province" : "")
        . ($shipping_post_code ? ",\n$shipping_post_code" : "")
        . ($shipping_phone_number ? ",\nPhone: $shipping_phone_number" : "")
        . ($shipping_email_address ? ",\nEmail: $shipping_email_address" : "");

        $payment_method = $_POST['payment-method'];
        $shipping_id = $_POST['shipping_id'];
        $order_notes = $_POST['order_notes'];
        $order_status = "Processing";

        // Initialize variables
        $discountRate = 0;
        $discountAmount = 0;
        $subtotalAmount = 0;
        $shippingAmount = 0;
        $couponId = null;
        $couponAmount = 0;
        $grandTotalAmount = 0;
        $productSubtotal = 0;

        // Loop through cart items and calculate amounts
        while ($rowCart = mysqli_fetch_assoc($resultCart)) {
            $productId = $rowCart['product_id'];
            $quantity = $rowCart['quantity'];
            $couponId = $rowCart['coupon_id'];

            // Fetch product information
            $sqlProduct = "SELECT * FROM product WHERE id = ?";
            $stmtProduct = mysqli_prepare($conn, $sqlProduct);
            mysqli_stmt_bind_param($stmtProduct, "i", $productId);
            mysqli_stmt_execute($stmtProduct);
            $resultProduct = mysqli_stmt_get_result($stmtProduct);
            $rowProduct = mysqli_fetch_assoc($resultProduct);

            // Apply product-specific calculations
            $productPrice = $rowProduct['price'];
            $productDiscountAmount = $rowProduct['discount_amount'];

            // Calculate product subtotal
            $productSubtotal += ($productPrice * $quantity);

            // Update total amounts
            $subtotalAmount += ($productPrice * $quantity);
            $discountAmount += ($productDiscountAmount * $quantity);

        }

        $grandTotalAmount = ($subtotalAmount - $discountAmount);

        // Fetch coupons (if any)
        $sqlCoupon = "SELECT * FROM coupons WHERE id = ? AND expiration_date >= CURDATE() AND (max_usage IS NULL OR used_count < max_usage)";
        $stmtCoupon = mysqli_prepare($conn, $sqlCoupon);
        // Execute coupon query
        mysqli_stmt_bind_param($stmtCoupon, "s", $couponId);
        mysqli_stmt_execute($stmtCoupon);
        $resultCoupon = mysqli_stmt_get_result($stmtCoupon);
        $rowCoupon = mysqli_fetch_assoc($resultCoupon);

        // Check if coupon is valid
        if ($rowCoupon) {
            $discountRate = $rowCoupon['discount_amount']; // Assuming discount_amount is the percentage
            $minimumAmount = $rowCoupon['minimum_amount'];

            // Check if grand total is greater than the minimum amount
            if ($grandTotalAmount > $minimumAmount) {
                // Apply coupon discount to total
                $couponAmount = ($discountRate / 100) * $subtotalAmount; // Convert percentage to amount
                $grandTotalAmount = $subtotalAmount - $couponAmount;
            }
        }

            // Calculate shipping amount
        if ($grandTotalAmount > $free_shipping_amount) {
            // Free shipping
            $shippingAmount = 0;
        } else {
            // Fetch shipping amount from the database based on the selected shipping method
            $sqlShipping = "SELECT * FROM shipping_zones WHERE id = ?";
            $stmtShipping = mysqli_prepare($conn, $sqlShipping);
            mysqli_stmt_bind_param($stmtShipping, "i", $shipping_id);
            mysqli_stmt_execute($stmtShipping);
            $resultShipping = mysqli_stmt_get_result($stmtShipping);
            $rowShipping = mysqli_fetch_assoc($resultShipping);

            // Check if shipping amount is available
            if ($rowShipping) {
                $shippingAmount = $rowShipping['shipping_cost'];
            } else {
                // Handle error or set a default shipping amount
                $shippingAmount = 0; // Set a default amount
            }
        }

        // Update grand total with taxes and shipping
        $grandTotalAmount += $shippingAmount;

        mysqli_begin_transaction($conn);

        try {

            // Insert data into the orders table
            $sqlOrders = "INSERT INTO orders (user_id, guest_identifier, order_status, discount_amount, subtotal_amount, shipping_id, shipping_amount, coupon_id, coupon_amount, grand_total_amount, payment_method, shipping_address, order_notes)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtOrders = mysqli_prepare($conn, $sqlOrders);
            mysqli_stmt_bind_param($stmtOrders, "sssssssssssss", $userId, $guestIdentifier, $order_status, $discountAmount, $subtotalAmount, $shipping_id, $shippingAmount, $couponId, $couponAmount, $grandTotalAmount, $payment_method, $shipping_address, $order_notes);
            mysqli_stmt_execute($stmtOrders);

            // Get the generated order ID
            $orderId = mysqli_insert_id($conn);


            $sqlCart1 = "SELECT c.product_id, c.quantity, p.price, p.discount_amount FROM cart c LEFT JOIN product p ON p.id = c.product_id WHERE c.user_id = ? OR c.guest_identifier = ?";
            $stmtCart1 = mysqli_prepare($conn, $sqlCart1);
            mysqli_stmt_bind_param($stmtCart1, "is", $userId, $guestIdentifier);
            mysqli_stmt_execute($stmtCart1);
            $resultCart1 = mysqli_stmt_get_result($stmtCart1);
            while ($rowCart1 = mysqli_fetch_assoc($resultCart1)) {
                $productId = $rowCart1['product_id'];
                $quantity = $rowCart1['quantity'];
                $productPrice = $rowCart1['price'];
                $productDiscountAmount = $rowCart1['discount_amount'];

                // Loop through cart1 items and insert into order_items table
                $sqlOrderItems = "INSERT INTO order_items (order_id, product_id, quantity, price, discount_amount) VALUES (?, ?, ?, ?, ?)";
                $stmtOrderItems = mysqli_prepare($conn, $sqlOrderItems);

                mysqli_stmt_bind_param($stmtOrderItems, "sssss", $orderId, $productId, $quantity, $productPrice, $productDiscountAmount);
                mysqli_stmt_execute($stmtOrderItems);
            }

            // Remove cart items (optional, depending on your logic)
            $sqlRemoveCart = "DELETE FROM cart WHERE user_id = ? OR guest_identifier = ?";
            $stmtRemoveCart = mysqli_prepare($conn, $sqlRemoveCart);
            mysqli_stmt_bind_param($stmtRemoveCart, "is", $userId, $guestIdentifier);
            mysqli_stmt_execute($stmtRemoveCart);

            // Close prepared statements
            mysqli_stmt_close($stmtOrders);
            // mysqli_stmt_close($stmtOrderItems);
            mysqli_stmt_close($stmtRemoveCart);

            // Commit the transaction
            mysqli_commit($conn);

            // Return a success response (you can adjust this based on your needs)
            $response = array(
                "success" => true,
                "message" => "Order placed successfully.",
                "orderId" => $orderId
            );

        } catch (Exception $e) {
            mysqli_rollback($conn);
            // Log the error message or handle it in a way that suits your application
            error_log("Error placing order: " . $e->getMessage());
            // Return an error response (you can adjust this based on your needs)
            $response = array(
                "success" => false,
                "message" => "Error placing order." . $e->getMessage()
            );
            throw $e; // Rethrow the exception after rolling back the transaction
        }


    // Close the database connection
    mysqli_close($conn);

    // Send the JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    $response = array(
        "success" => false,
        "message" => "Invalid request method."
    );
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>