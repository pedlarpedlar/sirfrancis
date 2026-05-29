<?php
include 'session_logins.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/candybird_mail_helpers.php';
if (file_exists('/home/candybirdco/configs_candybird/candybird_config.php')) {
    require_once('/home/candybirdco/configs_candybird/candybird_config.php');
}

$order_items = "
<tr>
<th style='text-align: left;'></th>
<th style='text-align: left;'>Item</th>
<th style='text-align: left;'>Qty</th>
<th style='text-align: left;'>Subtotal</th>
</tr>
";
$payment_method_name = "";
$order_discount = 0;
$user_registered = false;
$orderCreated = false;
$response = array(
    "success" => false,
    "message" => "Checkout could not be completed."
);

if (!function_exists('saveCandybirdCheckoutAddress')) {
    function saveCandybirdCheckoutAddress(
        $conn,
        $userId,
        $guestIdentifier,
        $billing_first_name,
        $billing_last_name,
        $billing_phone_number,
        $billing_email_address,
        $billing_company_name,
        $billing_street_address_1,
        $billing_street_address_2,
        $billing_city,
        $billing_country,
        $billing_province,
        $billing_post_code,
        $ship_to_different_address,
        $shipping_first_name,
        $shipping_last_name,
        $shipping_phone_number,
        $shipping_email_address,
        $shipping_company_name,
        $shipping_street_address_1,
        $shipping_street_address_2,
        $shipping_city,
        $shipping_country,
        $shipping_province,
        $shipping_post_code
    ) {
        if (!($conn instanceof mysqli)) {
            return false;
        }

        $sqlCheckExistence = "SELECT id FROM user_addresses WHERE user_id = ? OR guest_identifier = ? LIMIT 1";
        $stmtCheckExistence = mysqli_prepare($conn, $sqlCheckExistence);
        if (!$stmtCheckExistence) {
            return false;
        }

        mysqli_stmt_bind_param($stmtCheckExistence, "is", $userId, $guestIdentifier);
        mysqli_stmt_execute($stmtCheckExistence);
        $resultCheckExistence = mysqli_stmt_get_result($stmtCheckExistence);
        $exists = $resultCheckExistence && mysqli_num_rows($resultCheckExistence) > 0;
        mysqli_stmt_close($stmtCheckExistence);

        if ($exists) {
            $sql = "UPDATE user_addresses SET
                user_id = COALESCE(?, user_id),
                guest_identifier = ?,
                billing_first_name = ?,
                billing_last_name = ?,
                billing_phone_number = ?,
                billing_email_address = ?,
                billing_company_name = ?,
                billing_street_address_1 = ?,
                billing_street_address_2 = ?,
                billing_city = ?,
                billing_country = ?,
                billing_province = ?,
                billing_post_code = ?,
                ship_to_different_address = ?,
                shipping_first_name = ?,
                shipping_last_name = ?,
                shipping_phone_number = ?,
                shipping_email_address = ?,
                shipping_company_name = ?,
                shipping_street_address_1 = ?,
                shipping_street_address_2 = ?,
                shipping_city = ?,
                shipping_country = ?,
                shipping_province = ?,
                shipping_post_code = ?
            WHERE user_id = ? OR guest_identifier = ?";
        } else {
            $sql = "INSERT INTO user_addresses (
                user_id,
                guest_identifier,
                billing_first_name,
                billing_last_name,
                billing_phone_number,
                billing_email_address,
                billing_company_name,
                billing_street_address_1,
                billing_street_address_2,
                billing_city,
                billing_country,
                billing_province,
                billing_post_code,
                ship_to_different_address,
                shipping_first_name,
                shipping_last_name,
                shipping_phone_number,
                shipping_email_address,
                shipping_company_name,
                shipping_street_address_1,
                shipping_street_address_2,
                shipping_city,
                shipping_country,
                shipping_province,
                shipping_post_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        if ($exists) {
            mysqli_stmt_bind_param(
                $stmt,
                "issssssssssssssssssssssssis",
                $userId,
                $guestIdentifier,
                $billing_first_name,
                $billing_last_name,
                $billing_phone_number,
                $billing_email_address,
                $billing_company_name,
                $billing_street_address_1,
                $billing_street_address_2,
                $billing_city,
                $billing_country,
                $billing_province,
                $billing_post_code,
                $ship_to_different_address,
                $shipping_first_name,
                $shipping_last_name,
                $shipping_phone_number,
                $shipping_email_address,
                $shipping_company_name,
                $shipping_street_address_1,
                $shipping_street_address_2,
                $shipping_city,
                $shipping_country,
                $shipping_province,
                $shipping_post_code,
                $userId,
                $guestIdentifier
            );
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "issssssssssssssssssssssss",
                $userId,
                $guestIdentifier,
                $billing_first_name,
                $billing_last_name,
                $billing_phone_number,
                $billing_email_address,
                $billing_company_name,
                $billing_street_address_1,
                $billing_street_address_2,
                $billing_city,
                $billing_country,
                $billing_province,
                $billing_post_code,
                $ship_to_different_address,
                $shipping_first_name,
                $shipping_last_name,
                $shipping_phone_number,
                $shipping_email_address,
                $shipping_company_name,
                $shipping_street_address_1,
                $shipping_street_address_2,
                $shipping_city,
                $shipping_country,
                $shipping_province,
                $shipping_post_code
            );
        }

        $saved = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $saved;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!($conn instanceof mysqli)) {
        $response = array(
            "success" => false,
            "message" => "Checkout is temporarily unavailable. Please try again shortly."
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Assuming your form has input fields with appropriate names
    $billing_first_name = trim($_POST['billing_first_name'] ?? '');
    $billing_last_name = trim($_POST['billing_last_name'] ?? '');
    $billing_company_name = trim($_POST['billing_company_name'] ?? '');
    $billing_street_address_1 = trim($_POST['billing_street_address_1'] ?? '');
    $billing_street_address_2 = trim($_POST['billing_street_address_2'] ?? '');
    $billing_city = trim($_POST['billing_city'] ?? '');
    $billing_country = trim($_POST['billing_country'] ?? '');
    $billing_province = trim($_POST['billing_province'] ?? '');
    $billing_post_code = trim($_POST['billing_post_code'] ?? '');
    $billing_phone_number = trim($_POST['billing_phone_number'] ?? '');
    $billing_email_address = trim($_POST['billing_email_address'] ?? '');

    $requiredFields = array(
        'First name' => $billing_first_name,
        'Last name' => $billing_last_name,
        'Street address' => $billing_street_address_1,
        'Town / City' => $billing_city,
        'Country' => $billing_country,
        'Province' => $billing_province,
        'Postal code' => $billing_post_code,
        'Phone number' => $billing_phone_number,
        'Email address' => $billing_email_address,
    );
    $missingFields = array_keys(array_filter($requiredFields, function ($value) {
        return $value === '';
    }));
    if (!filter_var($billing_email_address, FILTER_VALIDATE_EMAIL)) {
        $missingFields[] = 'A valid email address';
    }
    if (empty($_POST['payment-method'])) {
        $missingFields[] = 'Payment method';
    }
    if (!empty($missingFields)) {
        $response = array(
            "success" => false,
            "message" => "Please complete: " . implode(', ', array_unique($missingFields)) . "."
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    $wantsAccount = !isset($_SESSION['user_id']) && !empty($_POST['create_account']);
    $username = $wantsAccount ? trim($_POST['user-name'] ?? '') : '';

    $ship_to_different_address = isset($_POST['ship_to_different_address']) ? 1 : 0;

    if ($ship_to_different_address) {
        $shipping_first_name = trim($_POST['shipping_first_name'] ?? '');
        $shipping_last_name = trim($_POST['shipping_last_name'] ?? '');
        $shipping_company_name = trim($_POST['shipping_company_name'] ?? '');
        $shipping_street_address_1 = trim($_POST['shipping_street_address_1'] ?? '');
        $shipping_street_address_2 = trim($_POST['shipping_street_address_2'] ?? '');
        $shipping_city = trim($_POST['shipping_city'] ?? '');
        $shipping_country = trim($_POST['shipping_country'] ?? '');
        $shipping_province = trim($_POST['shipping_province'] ?? '');
        $shipping_post_code = trim($_POST['shipping_post_code'] ?? '');
        $shipping_phone_number = trim($_POST['shipping_phone_number'] ?? '');
        $shipping_email_address = trim($_POST['shipping_email_address'] ?? '');
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



      // Check if the customer asked to create an account.
        if ($wantsAccount) {
            // Check if the username is valid
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
                $response = array(
                    "success" => false,
                    "message" => "Invalid username. It must be 3 to 20 characters long and can only contain letters, numbers, and underscores."
                );
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            $passwordRaw = (string) ($_POST['password'] ?? '');
            if (strlen($passwordRaw) < 8) {
                $response = array(
                    "success" => false,
                    "message" => "Please choose a password with at least 8 characters, or untick create account to checkout as a guest."
                );
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
            $special_code = '';

            // Check if the username already exists in the database
            $sqlCheckUsername = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmtCheckUsername = mysqli_prepare($conn, $sqlCheckUsername);
            mysqli_stmt_bind_param($stmtCheckUsername, "ss", $username, $billing_email_address);
            mysqli_stmt_execute($stmtCheckUsername);
            $resultCheckUsername = mysqli_stmt_get_result($stmtCheckUsername);

            if (mysqli_num_rows($resultCheckUsername) > 0) {
                $response = array(
                    "success" => false,
                    "message" => "There is already an account with that username or email. You can log in, or untick create account to checkout as a guest.",
                    "account_exists" => true,
                    "login_url" => "login?redirect=checkout"
                );
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
                // Handle the error or redirect the user as needed
            }

            // Insert the new user into the users table
            $sqlInsertUser = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
            $stmtInsertUser = mysqli_prepare($conn, $sqlInsertUser);
            mysqli_stmt_bind_param($stmtInsertUser, "sss", $username, $billing_email_address, $password);

            if (mysqli_stmt_execute($stmtInsertUser)) {


                $user_registered = true;

                // User registered successfully, set up a session
                
                if (session_status() == PHP_SESSION_NONE) {
                    // Start or resume the session
                    session_start();
                }

                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $billing_email_address;
                
                $userId = $_SESSION['user_id'];

                foreach (['reviews', 'cart', 'wishlist', 'compare', 'applied_coupons'] as $table) {
                    $sqlUpdateOwner = "UPDATE $table SET user_id = ? WHERE guest_identifier = ?";
                    $stmtUpdateOwner = mysqli_prepare($conn, $sqlUpdateOwner);
                    if ($stmtUpdateOwner) {
                        mysqli_stmt_bind_param($stmtUpdateOwner, "is", $userId, $guestIdentifier);
                        mysqli_stmt_execute($stmtUpdateOwner);
                        mysqli_stmt_close($stmtUpdateOwner);
                    }
                }

                logAction('Registered User', 'from checkout page', $userId, $billing_email_address);
                
                // $response = array(
                //     "success" => true,
                //     "message" => "User registered successfully!" //send email here!
                // );
                // header('Content-Type: application/json');
                // echo json_encode($response);


            } else {
                $response = array(
                    "success" => false,
                    "message" => "Error registering user: ".$stmt->error
                );
                logAction('Register Error from checkout page', 'Error: '.$stmt->error, null, $billing_email_address);
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            mysqli_stmt_close($stmtInsertUser);
        }

        $orderUserId = $userId;
        if (!$orderUserId && filter_var($billing_email_address, FILTER_VALIDATE_EMAIL)) {
            $stmtExistingEmailUser = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
            if ($stmtExistingEmailUser) {
                mysqli_stmt_bind_param($stmtExistingEmailUser, "s", $billing_email_address);
                mysqli_stmt_execute($stmtExistingEmailUser);
                mysqli_stmt_bind_result($stmtExistingEmailUser, $matchedUserId);
                if (mysqli_stmt_fetch($stmtExistingEmailUser)) {
                    $orderUserId = (int) $matchedUserId;
                }
                mysqli_stmt_close($stmtExistingEmailUser);
            }
        }
    
    // ...............
    // add the order into the orders and order-items table here




    if (function_exists('ensureCandybirdCartClearanceColumns')) {
        ensureCandybirdCartClearanceColumns($conn);
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

        logAction('Checkout Error', 'No items in order!', $userId, $guestIdentifier);

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

        saveCandybirdCheckoutAddress(
            $conn,
            $userId,
            $guestIdentifier,
            $billing_first_name,
            $billing_last_name,
            $billing_phone_number,
            $billing_email_address,
            $billing_company_name,
            $billing_street_address_1,
            $billing_street_address_2,
            $billing_city,
            $billing_country,
            $billing_province,
            $billing_post_code,
            $ship_to_different_address,
            $shipping_first_name,
            $shipping_last_name,
            $shipping_phone_number,
            $shipping_email_address,
            $shipping_company_name,
            $shipping_street_address_1,
            $shipping_street_address_2,
            $shipping_city,
            $shipping_country,
            $shipping_province,
            $shipping_post_code
        );

        $payment_method = isset($_POST['payment-method']) ? $_POST['payment-method'] : null;

        $sqlLabel = "SELECT label FROM payment_methods WHERE id = ?";
        $stmtLabel = mysqli_prepare($conn, $sqlLabel);

        if ($stmtLabel) {
            mysqli_stmt_bind_param($stmtLabel, "i", $payment_method);
            mysqli_stmt_execute($stmtLabel);
            mysqli_stmt_bind_result($stmtLabel, $payment_method_name);

            if (mysqli_stmt_fetch($stmtLabel)) {
                // $payment_method_name now holds the label
            } else {
                // Handle case where no results were found
                $payment_method_name = "Cash"; // or handle as needed
            }

            mysqli_stmt_close($stmtLabel);
        }

        // $response = array(
        //         "success" => true,
        //         "message" => "Payment Method: ".$payment_method_name,
        //         "paymentMethodID" => $payment_method
        //     );
        // header('Content-Type: application/json');
        // echo json_encode($response);
        // exit();


        $deliveryOptionsForCheckout = getCandybirdDeliveryOptions();
        $postedDeliveryMethod = trim((string) ($_POST['delivery_method'] ?? ''));
        $delivery_method = !empty($deliveryOptionsForCheckout[$postedDeliveryMethod]['enabled'])
            ? $postedDeliveryMethod
            : getCandybirdDefaultDeliveryMethod($deliveryOptionsForCheckout);
        $shipping_id = $_POST['shipping_id'] ?? $_POST['shipping_tier'] ?? '';
        $order_notes = trim($_POST['order_notes'] ?? '');
        $cartLeadTimeNotes = [];
        $order_status = "Processing";

        // Initialize variables
        $discountRate = 0;
        $subtotalAmount = 0;
        $shippingAmount = 0;
        $shippingDiscountAmount = 0;
        $couponId = null;
        $couponAmount = 0;
        $grandTotalAmount = 0;
        $productSubtotal = 0;
        $discountAmount = 0;
        $taxAmount = 0;
        $couponEligibleAmount = 0; // Amount eligible for coupon
        $productTaxRate = 0;
        $cartWeightKg = 0;

        if (isset($_SESSION['coupon'])) {
            calculateCouponDiscount($conn, $userId, $guestIdentifier, $billing_email_address);
        }

        // Loop through cart items and calculate amounts
        while ($rowCart = mysqli_fetch_assoc($resultCart)) {
            $productId = $rowCart['product_id'];
            $quantity = $rowCart['quantity'];
            $clearanceId = strtoupper(trim((string) ($rowCart['clearance_id'] ?? '')));

            $sheetProduct = $clearanceId !== '' ? buildCandybirdClearanceProduct(getSheetClearanceRowById($clearanceId)) : getSheetProductById($productId);
            if (!$sheetProduct) {
                continue;
            }

            $availableStock = getCandybirdAvailableStockForCart($conn, $sheetProduct, $userId, $guestIdentifier);
            if ($availableStock !== null && (int) $quantity > $availableStock) {
                $stockTitle = getSheetProductDisplayTitle($sheetProduct);
                $response = [
                    "success" => false,
                    "message" => "Only " . $availableStock . " available for " . $stockTitle . ". Please update your cart before checkout."
                ];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }

            $cartWeightKg += getSheetProductWeightKg($sheetProduct) * (int) $quantity;
            $itemLeadTime = trim((string) ($sheetProduct['delivery_estimate'] ?? $sheetProduct['product_delivery_estimate'] ?? $sheetProduct['dispatch_estimate'] ?? $sheetProduct['lead_time'] ?? ''));
            if ($itemLeadTime !== '') {
                $cartLeadTimeNotes[] = getSheetProductDisplayTitle($sheetProduct) . ': ' . $itemLeadTime;
            }
            $productPrice = isset($sheetProduct['price']) ? (float) $sheetProduct['price'] : 0;
            $sheetPrice = getSheetProductPrice($sheetProduct);
            $productDiscountAmount = max(0, $productPrice - $sheetPrice);

            // Calculate product subtotal
            $productSubtotal += ($productPrice * $quantity);

            // Update total amounts
            $subtotalAmount += ($productPrice * $quantity);
            $discountAmount += ($productDiscountAmount * $quantity);

            // Calculate tax amount for this product
            $taxAmount += 0;

            // Add to coupon eligible amount if no discount
            if ($productDiscountAmount == 0) {
                $couponEligibleAmount += $productPrice * $quantity;
            }
        }

        // Calculate the tax amount after discounts
        $taxableAmount = $subtotalAmount - $discountAmount;
        $totalTaxAmount = $taxableAmount * $productTaxRate;

        if (isset($_SESSION['coupon'])) {
            $couponCodeForCheckout = (string) ($_SESSION['coupon']['code'] ?? '');
            $couponSelection = selectBestSheetCouponForCart(
                $couponCodeForCheckout,
                getCartItems($userId, $guestIdentifier),
                ['conn' => $conn, 'email' => $billing_email_address, 'phone' => $billing_phone_number]
            );
            if (empty($couponSelection['valid'])) {
                unset($_SESSION['coupon']);
                $response = [
                    "success" => false,
                    "message" => $couponSelection['message'] ?? 'Coupon could not be used with this email address.'
                ];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }

            $checkoutCoupon = $couponSelection['coupon'];
            $checkoutDiscount = $couponSelection['discount'];
            $_SESSION['coupon'] = array_merge($_SESSION['coupon'], [
                'id' => $checkoutCoupon['id'] ?? $checkoutCoupon['coupon_code'],
                'code' => $checkoutCoupon['coupon_code'],
                'discount_type' => $checkoutCoupon['discount_type'],
                'discount_value' => (float) $checkoutCoupon['discount_value'],
                'min_order_value' => (float) ($checkoutCoupon['min_order_value'] ?? 0),
                'valid_from' => $checkoutCoupon['valid_from'] ?? '',
                'valid_until' => $checkoutCoupon['valid_until'] ?? '',
                'valid_on_sale_items' => $checkoutCoupon['valid_on_sale_items'] ?? 'no',
                'coupon_savings' => $checkoutDiscount['coupon_savings'],
                'original_amount' => $checkoutDiscount['eligible_amount'],
                'total_after_coupon' => $checkoutDiscount['total_after_coupon'],
                'coupon_message' => $checkoutDiscount['message'],
            ]);

            $couponId = $_SESSION['coupon']['id'];
            $couponAmount = (float) $_SESSION['coupon']['coupon_savings'];
        }

        // Calculate the grand total amount before shipping
        $grandTotalAmount = $taxableAmount + $totalTaxAmount - $couponAmount;
        $freeShippingBasisAmount = $grandTotalAmount;

        $shippingCountryNormalized = strtolower(trim((string) $shipping_country));
        $isSouthAfricaDelivery = in_array($shippingCountryNormalized, ['', 'south africa', 'sa', 'za', 'zaf'], true);
        if ($cartWeightKg > 0 && !$isSouthAfricaDelivery && $delivery_method !== 'collect') {
            $deliveryQuote = [
                'method_key' => 'international',
                'method_label' => 'International shipping quote',
                'tier_key' => 'international_quote',
                'tier_label' => 'Shipping quote to follow',
                'shipping_amount' => 0.0,
                'shipping_discount_amount' => 0.0,
                'payable_shipping_amount' => 0.0,
                'free_shipping_applied' => false,
                'estimate' => 'Shipping quote to be confirmed before dispatch',
            ];
        } else {
            $deliveryQuote = getCandybirdDeliveryQuote($delivery_method, $cartWeightKg, $freeShippingBasisAmount, $free_shipping_amount);
        }
        $shipping_id = $deliveryQuote['tier_key'];
        $shippingAmount = $deliveryQuote['shipping_amount'];
        $shippingDiscountAmount = $deliveryQuote['shipping_discount_amount'];
        $order_notes = trim($order_notes . "\nDelivery: " . $deliveryQuote['method_label'] . " (" . $deliveryQuote['tier_label'] . ")");
        if (!empty($deliveryQuote['estimate'])) {
            $order_notes = trim($order_notes . "\nDelivery estimate: " . $deliveryQuote['estimate']);
        }
        if ($delivery_method === 'collect' && !empty($deliveryQuote['collection_address'])) {
            $order_notes = trim($order_notes . "\nCollection address: " . $deliveryQuote['collection_address']);
        }
        if (!empty($cartLeadTimeNotes)) {
            $order_notes = trim($order_notes . "\nDispatch note: Some items may delay dispatch. Consider ordering separately if the rest is urgent.\n- " . implode("\n- ", array_unique($cartLeadTimeNotes)));
        }
        if ($cartWeightKg > 0 && !$isSouthAfricaDelivery && $delivery_method !== 'collect') {
            $order_notes = trim($order_notes . "\nShipping quote: Customer is outside South Africa. Confirm courier cost by email, WhatsApp, or phone before dispatch.");
        }
        
        // Update grand total with taxes and shipping
        $grandTotalAmount += $shippingAmount;


        // Check if coupon details are set in the session
        if (isset($_SESSION['coupon'])) {
            $shippingCoupon = isset($_SESSION['coupon']['shipping_coupon']) ? $_SESSION['coupon']['shipping_coupon'] : false;
            $shippingCouponValue = isset($_SESSION['coupon']['shipping_coupon_value']) ? $_SESSION['coupon']['shipping_coupon_value'] : 0;
            $shippingCouponType = isset($_SESSION['coupon']['shipping_coupon_type']) ? $_SESSION['coupon']['shipping_coupon_type'] : '';

            // If a shipping coupon is applied
            if ($shippingCoupon) {
                if ($shippingCouponType === 'percentage') {
                    // Calculate percentage discount
                    $shippingDiscountAmount = ($shippingAmount * $shippingCouponValue) / 100;
                } elseif ($shippingCouponType === 'fixed') {
                    // Calculate fixed discount, ensuring it doesn't exceed the shipping amount
                    $shippingDiscountAmount = min($shippingAmount, $shippingCouponValue);
                }
            }
        }

        // Subtract the shipping discount from the total. This will either be the full shipping amount or 0 depending on the above condition.
        $grandTotalAmount -= $shippingDiscountAmount;


        mysqli_begin_transaction($conn);

        try {
            $orderShippingId = is_numeric($shipping_id) ? (int) $shipping_id : null;
            $orderCouponId = null;

            // Sheet-backed delivery tiers and coupons are not rows in the old shipping/coupon tables.
            // Store the amounts and labels on the order, but avoid old foreign keys blocking checkout.
            $sqlOrders = "INSERT INTO orders (user_id, guest_identifier, order_status, discount_amount, subtotal_amount, shipping_id, shipping_amount, shipping_discount_amount, coupon_id, coupon_amount, grand_total_amount, payment_method, shipping_address, order_notes)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtOrders = mysqli_prepare($conn, $sqlOrders);
            if (!$stmtOrders) {
                throw new Exception("Could not prepare order insert: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmtOrders, "issddiddiddiss", $orderUserId, $guestIdentifier, $order_status, $discountAmount, $subtotalAmount, $orderShippingId, $shippingAmount, $shippingDiscountAmount, $orderCouponId, $couponAmount, $grandTotalAmount, $payment_method, $shipping_address, $order_notes);
            if (!mysqli_stmt_execute($stmtOrders)) {
                throw new Exception("Could not create order: " . mysqli_stmt_error($stmtOrders));
            }

            // // Insert data into the orders table
            // $sqlOrders = "INSERT INTO orders (user_id, guest_identifier, order_status, discount_amount, subtotal_amount, shipping_id, shipping_amount, shipping_discount_amount, coupon_id, coupon_amount, grand_total_amount, payment_method, shipping_address, order_notes)
            //               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            // $stmtOrders = mysqli_prepare($conn, $sqlOrders);
            // mysqli_stmt_bind_param($stmtOrders, "ssssssssssssss", $userId, $guestIdentifier, $order_status, $discountAmount, $subtotalAmount, $shipping_id, $shippingAmount, $shippingDiscountAmount, $couponId, $couponAmount, $grandTotalAmount, $payment_method, $shipping_address, $order_notes);
            // mysqli_stmt_execute($stmtOrders);

            $orderId = mysqli_insert_id($conn);
            // Left-zero-pad with 3 zeroes
            $orderId_zeropad = str_pad($orderId, 7, '0', STR_PAD_LEFT);

            if (!empty($_SESSION['coupon']['code']) && (float) $couponAmount > 0) {
                if (!candybirdRecordCouponEmailUsage($conn, $_SESSION['coupon']['code'], $billing_email_address, $orderId, $billing_phone_number)) {
                    throw new Exception("This coupon has already been used with this email address.");
                }
            }
            

            // Insert data into the coupon_usage table
            if ($orderCouponId) { // Check if there is a valid database coupon ID
                $sqlCouponUsage = "INSERT INTO coupon_usage (coupon_id, user_id, order_id) VALUES (?, ?, ?)";
                $stmtCouponUsage = mysqli_prepare($conn, $sqlCouponUsage);
                mysqli_stmt_bind_param($stmtCouponUsage, "iii", $orderCouponId, $userId, $orderId);
                if (!mysqli_stmt_execute($stmtCouponUsage)) {
                    throw new Exception("Could not save coupon usage: " . mysqli_stmt_error($stmtCouponUsage));
                }
            }
            


            $allInsertsSuccessful = true;

            $cartItemsForOrder = getCartItems($userId, $guestIdentifier);
            if (empty($cartItemsForOrder)) {
                throw new Exception("Cart items could not be read for order items.");
            }
            $orderItemSnapshotsReady = ensureCandybirdOrderItemSnapshotColumns($conn);

            foreach ($cartItemsForOrder as $rowCart1) {
                $productId = (int) ($rowCart1['source_product_id'] ?? $rowCart1['product_id'] ?? $rowCart1['id']);
                $clearanceId = strtoupper(trim((string) ($rowCart1['clearance_id'] ?? '')));
                $quantity = $rowCart1['quantity'];
                $product_title = $rowCart1['title'];
                $product_weight = $rowCart1['product_weight'];
                $productPrice = $rowCart1['price'];
                $productDiscountAmount = $rowCart1['discount_amount'];
                $sheetProductForMirror = getSheetProductById($productId);

                if (!$sheetProductForMirror || !syncSheetProductMirrorToDb($conn, $sheetProductForMirror)) {
                    throw new Exception("Could not sync product mirror for sheet product " . $productId . ".");
                }

                if ($clearanceId !== '') {
                    $clearanceProductForMirror = buildCandybirdClearanceProduct(getSheetClearanceRowById($clearanceId));
                    $productTitle = $clearanceProductForMirror ? getSheetProductDisplayTitle($clearanceProductForMirror) : trim($product_title . ($product_weight !== '' && stripos($product_title, $product_weight) === false ? ' ' . $product_weight : ''));
                } else {
                    $productTitle = getSheetProductDisplayTitle($sheetProductForMirror);
                }
                $image_url = $rowCart1['image_url'] ?? getSheetProductEmailImage($sheetProductForMirror);
                $clearanceNote = $clearanceId !== '' ? trim('Clearance item: ' . ($rowCart1['clearance_reason'] ?? 'Clearance stock')) : '';

                $order_discount += ($productDiscountAmount * $quantity);
                $productDiscountedPrice = max(0, (float) $productPrice - (float) $productDiscountAmount);

                // Insert into order_items table
                if ($orderItemSnapshotsReady) {
                    $sqlOrderItems = "INSERT INTO order_items (order_id, product_id, clearance_id, product_title, product_image_url, product_weight, clearance_note, quantity, price, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                } else {
                    $sqlOrderItems = "INSERT INTO order_items (order_id, product_id, product_title, quantity, price, discount_amount) VALUES (?, ?, ?, ?, ?, ?)";
                }
                $stmtOrderItems = mysqli_prepare($conn, $sqlOrderItems);
                if (!$stmtOrderItems) {
                    throw new Exception("Could not prepare order item insert: " . mysqli_error($conn));
                }

                if ($orderItemSnapshotsReady) {
                    mysqli_stmt_bind_param($stmtOrderItems, "iisssssidd", $orderId, $productId, $clearanceId, $productTitle, $image_url, $product_weight, $clearanceNote, $quantity, $productPrice, $productDiscountAmount);
                } else {
                    mysqli_stmt_bind_param($stmtOrderItems, "iisidd", $orderId, $productId, $productTitle, $quantity, $productPrice, $productDiscountAmount);
                }
                if (!mysqli_stmt_execute($stmtOrderItems)) {
                    throw new Exception("Could not save order item " . $productId . ": " . mysqli_stmt_error($stmtOrderItems));
                }

                $emailItemTitle = htmlspecialchars($productTitle, ENT_QUOTES, 'UTF-8');
                if ($clearanceNote !== '') {
                    $emailItemTitle .= '<br><span style="display:inline-block;margin-top:4px;background:#fff0c7;color:#7b4b00;font-size:12px;font-weight:700;padding:3px 6px;">' . htmlspecialchars($clearanceNote, ENT_QUOTES, 'UTF-8') . '</span>';
                }
                $emailItemImage = htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8');
                $emailItemUrl = $rowCart1['product_url'] ?? ('product?id=' . urlencode($clearanceId !== '' ? ('CLR:' . $clearanceId) : (string) $productId));
                if (!preg_match('#^https?://#i', $emailItemUrl)) {
                    $emailItemUrl = 'https://www.candybird.co.za/' . ltrim($emailItemUrl, '/');
                }
                $emailFullLineTotal = (float) $productPrice * (int) $quantity;
                $emailDiscountedLineTotal = $productDiscountedPrice * (int) $quantity;
                if ((float) $productDiscountAmount > 0) {
                    $emailPriceHtml = '<span style="color:#777777;text-decoration:line-through;font-weight:400;">R' . number_format($emailFullLineTotal, 2) . '</span><br>'
                        . '<strong style="color:#1d7d38;">R' . number_format($emailDiscountedLineTotal, 2) . '</strong>'
                        . '<div style="color:#777777;font-size:12px;font-weight:400;">Unit: R' . number_format($productDiscountedPrice, 2) . ' was R' . number_format((float) $productPrice, 2) . '</div>';
                } else {
                    $emailPriceHtml = '<strong>R' . number_format($emailFullLineTotal, 2) . '</strong>'
                        . '<div style="color:#777777;font-size:12px;font-weight:400;">Unit: R' . number_format((float) $productPrice, 2) . '</div>';
                }

                $order_items .=
                    '<tr>
                        <td style="width:64px;padding:12px 10px;border-bottom:1px solid #e8e1d7;vertical-align:top;"><img src="' . $emailItemImage . '" width="54" height="54" alt="" style="display:block;width:54px;height:54px;object-fit:cover;border:1px solid #eee;"></td>
                        <td style="padding:12px 10px;border-bottom:1px solid #e8e1d7;vertical-align:top;"><a href="' . $emailItemUrl . '" style="color:#5b1178;text-decoration:none;font-weight:700;">' . $emailItemTitle . '</a></td>
                        <td align="center" style="width:48px;padding:12px 10px;border-bottom:1px solid #e8e1d7;vertical-align:top;color:#555555;">x' . (int) $quantity . '</td>
                        <td align="right" style="width:120px;padding:12px 0 12px 10px;border-bottom:1px solid #e8e1d7;vertical-align:top;font-weight:700;">' . $emailPriceHtml . '</td>
                    </tr>';
            }

            // Remove cart items if all inserts were successful
            if ($allInsertsSuccessful) {
                $sqlRemoveCart = "DELETE FROM cart WHERE user_id = ? OR guest_identifier = ?";
                $stmtRemoveCart = mysqli_prepare($conn, $sqlRemoveCart);
                if (!$stmtRemoveCart) {
                    throw new Exception("Could not prepare cart clear: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmtRemoveCart, "is", $userId, $guestIdentifier);
                if (!mysqli_stmt_execute($stmtRemoveCart)) {
                    throw new Exception("Could not clear cart: " . mysqli_stmt_error($stmtRemoveCart));
                }
                mysqli_stmt_close($stmtRemoveCart);
            }

            // Close prepared statements
            if (isset($stmtOrderItems) && $stmtOrderItems) {
                mysqli_stmt_close($stmtOrderItems);
            }


            // Commit the transaction
            mysqli_commit($conn);

            logAction('Checkout Success', 'Successfully placed order '.$orderId, $userId, $guestIdentifier);

            // Return a success response (you can adjust this based on your needs)
            $sessionParam = urlencode($_SESSION['session_id'] ?? session_id());
            $redirectUrl = 'order_details?order_id=' . $orderId . '&session=' . $sessionParam;
            $isPayFast = stripos((string) $payment_method_name, 'payfast') !== false || (string) $payment_method === '1';
            if ($isPayFast) {
                $redirectUrl = 'order_details?order_id=' . $orderId . '&session=' . $sessionParam . '&payfast=1';
            }

            $response = array(
                "success" => true,
                "message" => "Order placed successfully.",
                "orderId" => $orderId,
                "totalAmount" => $grandTotalAmount,
                "payment_method" => $payment_method,
                "redirect_url" => $redirectUrl
            );
            $orderCreated = true;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            // Log the error message or handle it in a way that suits your application
            error_log("Error placing order: " . $e->getMessage());
            logAction('Checkout Error', 'Error: '. $e->getMessage(), $userId, $guestIdentifier);

            // Return an error response (you can adjust this based on your needs)
            $response = array(
                "success" => false,
                "message" => "We could not place your order. Please check your details and try again."
            );
        }











    // ...............




    

    // Check if a row exists for the user or guest identifier
    $sqlCheckExistence = "SELECT id FROM user_addresses WHERE user_id = ? OR guest_identifier = ?";
    $stmtCheckExistence = mysqli_prepare($conn, $sqlCheckExistence);
    mysqli_stmt_bind_param($stmtCheckExistence, "is", $userId, $guestIdentifier);
    mysqli_stmt_execute($stmtCheckExistence);
    $resultCheckExistence = mysqli_stmt_get_result($stmtCheckExistence);

    if (mysqli_num_rows($resultCheckExistence) > 0) {
        // Row exists, perform update
        $sqlUpdateAddress = "UPDATE user_addresses SET 
            billing_first_name = ?, 
            billing_last_name = ?, 
            billing_phone_number = ?, 
            billing_email_address = ?, 
            billing_company_name = ?, 
            billing_street_address_1 = ?, 
            billing_street_address_2 = ?, 
            billing_city = ?, 
            billing_country = ?, 
            billing_province = ?, 
            billing_post_code = ?, 
            ship_to_different_address = ?, 
            shipping_first_name = ?, 
            shipping_last_name = ?, 
            shipping_phone_number = ?, 
            shipping_email_address = ?, 
            shipping_company_name = ?, 
            shipping_street_address_1 = ?, 
            shipping_street_address_2 = ?, 
            shipping_city = ?, 
            shipping_country = ?, 
            shipping_province = ?, 
            shipping_post_code = ?
        WHERE user_id = ? OR guest_identifier = ?";

        $stmtUpdateAddress = mysqli_prepare($conn, $sqlUpdateAddress);
        mysqli_stmt_bind_param(
            $stmtUpdateAddress,
            "sssssssssssssssssssssssss",
            $billing_first_name,
            $billing_last_name,
            $billing_phone_number,
            $billing_email_address,
            $billing_company_name,
            $billing_street_address_1,
            $billing_street_address_2,
            $billing_city,
            $billing_country,
            $billing_province,
            $billing_post_code,
            $ship_to_different_address,
            $shipping_first_name,
            $shipping_last_name,
            $shipping_phone_number,
            $shipping_email_address,
            $shipping_company_name,
            $shipping_street_address_1,
            $shipping_street_address_2,
            $shipping_city,
            $shipping_country,
            $shipping_province,
            $shipping_post_code,
            $userId,
            $guestIdentifier
        );

        if (mysqli_stmt_execute($stmtUpdateAddress)) {
            logAction('Address Update', 'from checkout page', $userId, $guestIdentifier);
        } else {
            logAction('Address Update Error', mysqli_stmt_error($stmtUpdateAddress), $userId, $guestIdentifier);
        }

        mysqli_stmt_close($stmtUpdateAddress);
    } else {
        // Row doesn't exist, perform insert
        $sqlInsertAddress = "INSERT INTO user_addresses (
            user_id, 
            guest_identifier, 
            billing_first_name, 
            billing_last_name, 
            billing_phone_number, 
            billing_email_address, 
            billing_company_name, 
            billing_street_address_1, 
            billing_street_address_2, 
            billing_city, 
            billing_country, 
            billing_province, 
            billing_post_code, 
            ship_to_different_address, 
            shipping_first_name, 
            shipping_last_name, 
            shipping_phone_number, 
            shipping_email_address, 
            shipping_company_name, 
            shipping_street_address_1, 
            shipping_street_address_2, 
            shipping_city, 
            shipping_country, 
            shipping_province, 
            shipping_post_code
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtInsertAddress = mysqli_prepare($conn, $sqlInsertAddress);
        mysqli_stmt_bind_param(
            $stmtInsertAddress,
            "sssssssssssssssssssssssss",
            $userId,
            $guestIdentifier,
            $billing_first_name,
            $billing_last_name,
            $billing_phone_number,
            $billing_email_address,
            $billing_company_name,
            $billing_street_address_1,
            $billing_street_address_2,
            $billing_city,
            $billing_country,
            $billing_province,
            $billing_post_code,
            $ship_to_different_address,
            $shipping_first_name,
            $shipping_last_name,
            $shipping_phone_number,
            $shipping_email_address,
            $shipping_company_name,
            $shipping_street_address_1,
            $shipping_street_address_2,
            $shipping_city,
            $shipping_country,
            $shipping_province,
            $shipping_post_code
        );

        if (mysqli_stmt_execute($stmtInsertAddress)) {
            logAction('Address Insert', 'from checkout page', $userId, $guestIdentifier);
        } else {
            logAction('Address Insert Error', mysqli_stmt_error($stmtInsertAddress), $userId, $guestIdentifier);
        }

        mysqli_stmt_close($stmtInsertAddress);
    }

    // Close the database connection
    mysqli_close($conn);

} else {
    $response = array(
        "success" => false,
        "message" => "Invalid request method."
    );
    logAction('Checkout Attempt', 'did not use post method', $userId, $guestIdentifier);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}


// Now, proceed to send the order confirmation email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $orderCreated) {
if (!isset($smtp_server, $smtp_username5, $smtp_password5, $smtp_type, $smtp_port, $smtp_username1)) {
    $response['email_message'] = 'Order placed. Email settings are not available in this environment.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (!function_exists('candybirdEmailMoney')) {
    function candybirdEmailMoney($amount)
    {
        return 'R' . number_format((float) $amount, 2);
    }
}

if (!function_exists('candybirdEmailText')) {
    function candybirdEmailText($value)
    {
        return nl2br(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
    }
}

$shippingPayableAmount = max(0, (float) $shippingAmount - (float) $shippingDiscountAmount);
$shippingContent = candybirdEmailMoney($shippingPayableAmount);
if ((float) $shippingDiscountAmount > 0) {
    $shippingContent = '<span style="color:#777777;text-decoration:line-through;">' . candybirdEmailMoney($shippingAmount) . '</span> <strong style="color:#1d7d38;">' . ($shippingPayableAmount > 0 ? candybirdEmailMoney($shippingPayableAmount) : 'Free') . '</strong>';
}

$deliveryMethodLabel = isset($deliveryQuote['method_label']) ? $deliveryQuote['method_label'] : ($delivery_method === 'door' ? 'Door-to-door' : 'Pudo locker');
$deliveryTierLabel = isset($deliveryQuote['tier_label']) ? $deliveryQuote['tier_label'] : $shipping_id;
$deliverySummary = trim($deliveryMethodLabel . ' - ' . $deliveryTierLabel, ' -');
$orderWeightEstimate = formatCandybirdWeightKg($cartWeightKg);
$couponCode = isset($_SESSION['coupon']['code']) ? (string) $_SESSION['coupon']['code'] : '';
$productDiscountAmount = max((float) $order_discount, (float) $discountAmount);
$totalSavingsAmount = $productDiscountAmount + (float) $couponAmount + (float) $shippingDiscountAmount;
$orderDiscountTotal = '-' . candybirdEmailMoney($totalSavingsAmount);
$sessionParam = urlencode($_SESSION['session_id'] ?? session_id());
$orderDetailsUrl = 'https://www.candybird.co.za/order_details?order_id=' . urlencode((string) $orderId) . '&session=' . $sessionParam;
$adminOrderUrl = 'https://www.candybird.co.za/admin-cb/manage_order?order_id=' . urlencode((string) $orderId);
$couponLabel = $couponCode !== '' ? 'Coupon (' . candybirdEmailText($couponCode) . ')' : 'Coupon';

$productDiscountRow = '';
if ($productDiscountAmount > 0) {
    $productDiscountRow = '<tr><td style="padding:8px 0;color:#555555;">Product savings</td><td align="right" style="padding:8px 0;color:#1d7d38;font-weight:700;">-' . candybirdEmailMoney($productDiscountAmount) . '</td></tr>';
}

$couponRow = '';
if (!empty($couponId) && (float) $couponAmount > 0) {
    $couponRow = '<tr><td style="padding:8px 0;color:#555555;">' . $couponLabel . '</td><td align="right" style="padding:8px 0;color:#1d7d38;font-weight:700;">-' . candybirdEmailMoney($couponAmount) . '</td></tr>';
}

$shippingDiscountRow = '';
if ((float) $shippingDiscountAmount > 0) {
    $shippingDiscountRow = '<tr><td style="padding:8px 0;color:#555555;">Delivery discount</td><td align="right" style="padding:8px 0;color:#1d7d38;font-weight:700;">-' . candybirdEmailMoney($shippingDiscountAmount) . '</td></tr>';
}

$couponSection = '';
if (!empty($couponId) && (float) $couponAmount > 0) {
    $couponSection = '<tr><td colspan="4" style="padding:12px 0;text-align:right;"><span style="background:#eefaf1;border:1px solid #b7e3c1;color:#1d7d38;display:inline-block;padding:10px 14px;">Coupon applied: ' . candybirdEmailText($couponCode !== '' ? $couponCode : 'discount') . ' saved ' . candybirdEmailMoney($couponAmount) . '</span></td></tr>';
}

$emailReplacements = array(
    '{year}' => date('Y'),
    '{order_id}' => $orderId_zeropad,
    '{order_id_raw}' => (string) $orderId,
    '{order_details_url}' => $orderDetailsUrl,
    '{admin_order_url}' => $adminOrderUrl,
    '{order_items}' => $order_items,
    '{delivery_address}' => candybirdEmailText($shipping_address),
    '{delivery_method}' => candybirdEmailText($deliveryMethodLabel),
    '{delivery_tier}' => candybirdEmailText($deliveryTierLabel),
    '{delivery_summary}' => candybirdEmailText($deliverySummary),
    '{order_weight_estimate}' => candybirdEmailText($orderWeightEstimate),
    '{coupon_code}' => candybirdEmailText($couponCode !== '' ? $couponCode : 'None'),
    '{coupon_amount}' => '-' . candybirdEmailMoney($couponAmount),
    '{coupon_row}' => $couponRow,
    '{coupon_section}' => $couponSection,
    '{product_discount}' => '-' . candybirdEmailMoney($productDiscountAmount),
    '{product_discount_row}' => $productDiscountRow,
    '{shipping_before_discount}' => candybirdEmailMoney($shippingAmount),
    '{shipping_discount}' => '-' . candybirdEmailMoney($shippingDiscountAmount),
    '{shipping_discount_row}' => $shippingDiscountRow,
    '{shipping_payable}' => $shippingPayableAmount > 0 ? candybirdEmailMoney($shippingPayableAmount) : 'Free',
    '{order_subtotal}' => candybirdEmailMoney($subtotalAmount),
    '{order_shipping}' => $shippingContent,
    '{order_discount}' => $orderDiscountTotal,
    '{order_total}' => candybirdEmailMoney($grandTotalAmount),
    '{order_status}' => candybirdEmailText($order_status),
    '{payment_method}' => candybirdEmailText($payment_method_name),
    '{order_notes}' => trim((string) $order_notes) !== '' ? candybirdEmailText($order_notes) : 'No order notes.',
    '{user_email_unsubscribe}' => urlencode((string) $billing_email_address)
);


try {
    // Get the email body from the template file
    $email_body = file_get_contents('emails/email_order_confirmation.php');

    $email_body = strtr($email_body, array_merge($emailReplacements, array(
        '{recipient_name}' => candybirdEmailText($billing_first_name)
    )));

    $customerMailResult = cbCandybirdSendMail(
        $billing_email_address,
        $billing_first_name,
        "CandyBird | Order Confirmation | #".$orderId_zeropad,
        $email_body,
        ['prefer_mail_transport' => true]
    );
    if (!empty($customerMailResult['success'])) {
        $response['email_message'] = 'Order email sent successfully.';
    } else {
        error_log('CandyBird order confirmation email failed for order ' . $orderId_zeropad . ': ' . ($customerMailResult['error'] ?? 'unknown error'));
        $response['email_message'] = 'Order placed, but the confirmation email could not be sent.';
    }

    // Get the email body for admin from the template file
    $admin_email_body = file_get_contents('emails/email_order_confirmation_admin.php');

    $admin_email_body = strtr($admin_email_body, array_merge($emailReplacements, array(
        '{recipient_name}' => 'Admin',
        '{user_name}' => candybirdEmailText($billing_first_name),
        '{user_email}' => candybirdEmailText($billing_email_address)
    )));

    $adminMailResult = cbCandybirdSendMail(
        $smtp_username1,
        'Admin',
        "CandyBird | Order Received | #".$orderId_zeropad,
        $admin_email_body,
        [
            'reply_to_email' => $billing_email_address,
            'reply_to_name' => trim($billing_first_name) ?: 'CandyBird customer',
            'prefer_mail_transport' => true,
        ]
    );
    if (!empty($adminMailResult['success'])) {
        $response['admin_email_message'] = 'Admin email sent successfully.';
    } else {
        error_log('CandyBird admin order email failed for order ' . $orderId_zeropad . ': ' . ($adminMailResult['error'] ?? 'unknown error'));
        $response['admin_email_message'] = 'Order placed, but the admin email could not be sent.';
    }





    //If user registered:
    if ($user_registered == true) {
        $mailRegister = new PHPMailer(true);

        // SMTP configuration
        $mailRegister->isSMTP();
        $mailRegister->Host = $smtp_server;
        $mailRegister->SMTPAuth = true;
        $mailRegister->Username = $smtp_username5;
        $mailRegister->Password = $smtp_password5;
        $mailRegister->SMTPSecure = $smtp_type;
        $mailRegister->Port = $smtp_port;

        // Set sender and recipient(s)
        $mailRegister->setFrom($smtp_username5, 'CandyBird'); // Your email address and your name
        $mailRegister->addAddress($billing_email_address, $billing_first_name); // Recipient's email address and name

        // Set "Reply-To" address
        $mailRegister->addReplyTo($smtp_username1, 'CandyBird');

        // Set email subject
        $mailRegister->Subject = "Welcome to CandyBird - Registration Confirmation";

        // Get the email body from the template file
        $email_body = file_get_contents('emails/email_register.php');

        // Replace placeholders with actual values
        $email_body = str_replace('{recipient_name}', $billing_first_name, $email_body);
        $email_body = str_replace('{user_email_unsubscribe}', $billing_email_address, $email_body);

        // Set the email body
        $mailRegister->Body = $email_body;

        // Set the email content type to HTML
        $mailRegister->isHTML(true);

        // Send the email
        if ($mailRegister->send()) {
            $mail_response = array('success' => true, 'message' => 'Registration successful! Email sent successfully!');
        } else {
            $mail_response = array('success' => true, 'message' => 'Registration successful! Email could not be sent.');
            $err = "IP Address ".$user_ip." - Registration Email Error: Could not send!";
            // $errorLogger->error($err);
        }

        // Send a separate email to the admin
        $admin_mail_register = new PHPMailer(true);
        $admin_mail_register->isSMTP();
        $admin_mail_register->Host = $smtp_server;
        $admin_mail_register->SMTPAuth = true;
        $admin_mail_register->Username = $smtp_username5;
        $admin_mail_register->Password = $smtp_password5;
        $admin_mail_register->SMTPSecure = $smtp_type;
        $admin_mail_register->Port = $smtp_port;

        // Set sender and recipient(s)
        $admin_mail_register->setFrom($smtp_username5, 'CandyBird'); // Your email address and your name
        $admin_mail_register->addAddress($smtp_username1, 'Admin'); // Admin email address

        // Set email subject
        $admin_mail_register->Subject = "New User Registration";

        // Get the email body for admin from the template file
        $admin_email_body = file_get_contents('emails/email_register_admin.php');

        // Replace placeholders with actual values for admin email
        $admin_email_body = str_replace('{recipient_name}', 'Admin', $admin_email_body);
        $admin_email_body = str_replace('{user_id}', $userId, $admin_email_body);
        $admin_email_body = str_replace('{user_name}', $username, $admin_email_body);
        $admin_email_body = str_replace('{user_email}', $billing_email_address, $admin_email_body);
        $admin_email_body = str_replace('{special_code}', $special_code, $admin_email_body); //password for security and training purposes
        
        // Set the email body for admin
        $admin_mail_register->Body = $admin_email_body;

        // Set the email content type to HTML
        $admin_mail_register->isHTML(true);

        // Send the email to the admin
        if ($admin_mail_register->send()) {
            $admin_response = array('success' => true, 'message' => 'Registration successful! Admin email sent successfully!');
        } else {
            $admin_response = array('success' => false, 'message' => 'Registration successful, but admin email could not be sent.');
            $err = "IP Address ".$user_ip." - Registration Email Error: Could not send!";
            // $errorLogger->error($err);
        }
    } /*END IF*/


} catch (Exception $e) {
    error_log('Order email error: ' . $e->getMessage());
    if (!empty($response['success'])) {
        $response['email_message'] = 'Order placed, but one of the confirmation emails could not be sent.';
    } else {
        $response = array('success' => false, 'message' => 'Checkout could not be completed.');
    }
}
}

header('Content-Type: application/json');
echo json_encode($response);

?>
