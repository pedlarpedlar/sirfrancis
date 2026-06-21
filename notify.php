<?php
// Tell Payfast that this page is reachable by triggering a header 200
header('HTTP/1.0 200 OK');
flush();

define('SANDBOX_MODE', false);
$pfHost = SANDBOX_MODE ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';

date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2
include 'dbh.inc.php';
require_once __DIR__ . '/product_sheet_helpers.php';

// Posted variables from ITN
$pfData = $_POST;
$pfParamString = '';

$pfPassphrase = 'My3eautifulPass'; //'jt7NOE43FZPn'; //'My3eautifulPass';

// Strip any slashes in data
foreach ($pfData as $key => $val) {
    $pfData[$key] = stripslashes($val);
}

// Convert posted variables to a string
foreach ($pfData as $key => $val) {
    if ($key !== 'signature') {
        $pfParamString .= $key . '=' . urlencode($val) . '&';
    }
}
$pfParamString = substr($pfParamString, 0, -1);

function pfValidSignature($pfData, $pfParamString, $pfPassphrase = null) {
    // Calculate security signature
    if ($pfPassphrase !== null) {
        $pfParamString .= '&passphrase=' . urlencode($pfPassphrase);
    }

    $signature = md5($pfParamString);
    return ($pfData['signature'] === $signature);
}

function pfValidIP() {
    // Variable initialization
    $validHosts = array(
        'www.payfast.co.za',
        'sandbox.payfast.co.za',
        'w1w.payfast.co.za',
        'w2w.payfast.co.za',
    );

    $validIps = [];

    foreach ($validHosts as $pfHostname) {
        $ips = gethostbynamel($pfHostname);

        if ($ips !== false) {
            $validIps = array_merge($validIps, $ips);
        }
    }

    // Remove duplicates
    $validIps = array_unique($validIps);
    $requestIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));
        foreach ($forwardedIps as $forwardedIp) {
            if (filter_var($forwardedIp, FILTER_VALIDATE_IP)) {
                $requestIp = $forwardedIp;
                break;
            }
        }
    }
    return $requestIp !== '' && in_array($requestIp, $validIps, true);
}

function pfValidPaymentData($cartTotal, $pfData) {
    return !(abs((float)$cartTotal - (float)$pfData['amount_gross']) > 0.01);
}

function pfPaymentMarkedComplete($pfData) {
    return strtoupper(trim((string) ($pfData['payment_status'] ?? ''))) === 'COMPLETE';
}

function pfValidServerConfirmation($pfParamString, $pfHost = 'sandbox.payfast.co.za', $pfProxy = null) {
    // Use cURL (if available)
    if (in_array('curl', get_loaded_extensions(), true)) {
        // Variable initialization
        $url = 'https://' . $pfHost . '/eng/query/validate';

        // Create default cURL object
        $ch = curl_init();

        // Set cURL options - Use curl_setopt for greater PHP compatibility
        // Base settings
        curl_setopt($ch, CURLOPT_USERAGENT, null);  // Set user agent
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      // Return output as string rather than outputting it
        curl_setopt($ch, CURLOPT_HEADER, false);             // Don't include header in output
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        // Standard settings
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pfParamString);
        if (!empty($pfProxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $pfProxy);
        }

        // Execute cURL
        $response = curl_exec($ch);
        curl_close($ch);
        return $response === 'VALID';
    }
    return false;
}

function logFailedCheck($conn, $order_id, $cartTotal, $check_name, $error_details, $check_result) {
    $stmt = $conn->prepare("INSERT INTO payment_checks (order_id, payment_total, check_name, error_details, check_result) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idssi", $order_id, $cartTotal, $check_name, $error_details, $check_result);
    $stmt->execute();
    $stmt->close();
}

function ensureCandybirdOrderColumn($conn, $column, $definition) {
    if (!($conn instanceof mysqli)) {
        return;
    }
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
    if ($safeColumn === '') {
        return;
    }
    $columnCheck = $conn->query("SHOW COLUMNS FROM orders LIKE '" . $conn->real_escape_string($safeColumn) . "'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE orders ADD COLUMN " . $safeColumn . " " . $definition);
    }
}

// Example usage:
$order_id = (int) ($pfData['m_payment_id'] ?? 0); // PayFast sends our order ID in m_payment_id
$orderId_zeropad = str_pad($order_id, 7, '0', STR_PAD_LEFT);
$pfPaymentId = $pfData['pf_payment_id'] ?? '';

$sql = "SELECT grand_total_amount AS cartTotal FROM orders WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $myRow = mysqli_fetch_assoc($result);
    $cartTotal = $myRow['cartTotal'];
} else {
    $cartTotal = 0; // Default value if no result is found
}

mysqli_stmt_close($stmt);

$check1 = pfValidSignature($pfData, $pfParamString, $pfPassphrase);
$check2 = pfValidIP();
$check3 = pfValidPaymentData($cartTotal, $pfData);
$check4 = pfValidServerConfirmation($pfParamString, $pfHost);
$check5 = pfPaymentMarkedComplete($pfData);

if ($check1 && $check3 && $check5 && ($check4 || $check2)) {
    // All checks have passed, the payment is successful
    ensureCandybirdOrderColumn($conn, 'payfast_payment_id', 'VARCHAR(100) NULL');
    ensureCandybirdOrderColumn($conn, 'refund_status', 'VARCHAR(50) NULL');
    ensureCandybirdOrderColumn($conn, 'refunded_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0');

    // Update the orders table to set payment_status = 1
    $update_stmt = $conn->prepare("UPDATE orders SET payment_status = 1, payfast_payment_id = ?, order_status = CASE WHEN order_status IN ('Pending', 'Unpaid') THEN 'Processing' ELSE order_status END WHERE id = ?");
    $update_stmt->bind_param("si", $pfPaymentId, $order_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Insert the successful payment check
    if ($check4 && $check2) {
        $successDetails = 'SUCCESSFUL!';
    } elseif ($check4) {
        $successDetails = 'SUCCESSFUL - PayFast IP warning, verified by signature and server confirmation.';
    } else {
        $successDetails = 'SUCCESSFUL - PayFast server confirmation unavailable, verified by signature and PayFast IP.';
    }
    $stmt = $conn->prepare("INSERT INTO payment_checks (order_id, payment_total, check_name, error_details, check_result) VALUES (?, ?, 'payfast complete', ?, 1)");
    $stmt->bind_param("ids", $order_id, $cartTotal, $successDetails);
    $stmt->execute();
    $stmt->close();

    if (!$check2) {
        logFailedCheck($conn, $order_id, $cartTotal, 'pfValidIP', 'IP did not match PayFast host lookup, but payment was confirmed by PayFast server validation.', 0);
    }
    if (!$check4) {
        logFailedCheck($conn, $order_id, $cartTotal, 'pfValidServerConfirmation', 'Server confirmation unavailable, but request matched PayFast IP, signature, amount, and COMPLETE status.', 0);
    }

    //Set Email Subjects and Bodies for successful payments
    $email_subject = "Sir Francis | Payment Confirmation for Order #".$orderId_zeropad."";
    $admin_email_subject = "Sir Francis | Payment Received for Order #".$orderId_zeropad."";

    // Get the email body from the template file
    $email_body = file_get_contents('emails/email_payment.php');
    // Get the email body for admin from the template file
    $admin_email_body = file_get_contents('emails/email_payment_admin.php');

} else {
    // Some checks have failed, check payment manually and log for investigation
    if (!$check1) {
        logFailedCheck($conn, $order_id, $cartTotal, 'pfValidSignature', 'Signature validation failed', $check1);
    }
    if (!$check2) {
        logFailedCheck($conn, $order_id, $cartTotal, 'pfValidIP', 'IP validation failed', $check2);
    }
    if (!$check3) {
        logFailedCheck($conn, $order_id, $cartTotal, 'pfValidPaymentData', 'Payment data validation failed', $check3);
    }
    if (!$check4) {
        logFailedCheck($conn, $order_id, $cartTotal, 'pfValidServerConfirmation', 'Server confirmation validation failed', $check4);
    }
    if (!$check5) {
        logFailedCheck($conn, $order_id, $cartTotal, 'pfPaymentMarkedComplete', 'PayFast payment_status was not COMPLETE. Received: ' . ($pfData['payment_status'] ?? 'missing'), $check5);
    }

    //Set Email Subjects and Bodies for failed payments
    $email_subject = "Oops... Payment Failed for Order #".$orderId_zeropad." on Sir Francis";
    $admin_email_subject = "Oops... Payment Failed for Order #".$orderId_zeropad." on Sir Francis";
    
    // Get the email body from the template file
    $email_body = file_get_contents('emails/email_payment_fail.php');
    // Get the email body for admin from the template file
    $admin_email_body = file_get_contents('emails/email_payment_fail_admin.php');

}


// $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
// $user_is_guest = false;

// if ($user_id === null) {
//     $user_is_guest = true;
// }

$sql = "SELECT o.id, o.order_date, o.grand_total_amount, o.shipping_address, o.order_status, o.order_notes, o.coupon_id, o.coupon_amount, o.subtotal_amount, o.shipping_amount, o.shipping_discount_amount, o.discount_amount AS discount_amount, 
        pm.label AS payment_method, oi.product_id, oi.product_title, oi.quantity, oi.price, oi.discount_amount AS item_discount_amount, oi.tax_amount,
        ua.billing_first_name, ua.billing_last_name, ua.billing_phone_number, ua.billing_email_address, ua.billing_company_name,
        ua.billing_street_address_1, ua.billing_street_address_2, ua.billing_city, ua.billing_country, ua.billing_province, ua.billing_post_code
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN payment_methods pm ON o.payment_method = pm.id
        LEFT JOIN user_addresses ua 
        ON (o.user_id = ua.user_id AND ua.user_id IS NOT NULL)
        OR (o.guest_identifier = ua.guest_identifier AND ua.guest_identifier IS NOT NULL)
        WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    error_log( "Error executing query: " . mysqli_error($conn));
    // exit();
}

if (mysqli_num_rows($result) > 0) {
    $order = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    error_log( "No order details found.");
    // exit();
}

$order_items = '';
$orderWeightKg = 0;

foreach ($order as $item) {
    $productId = $item['product_id'];
    $sheetProduct = getSheetProductById($productId);
    $displaySnapshot = getCandybirdOrderItemDisplaySnapshot($conn, [
        'product_id' => $productId,
        'product_title' => $item['product_title'] ?? '',
        'price' => $item['price'] ?? 0,
        'discount_amount' => $item['item_discount_amount'] ?? 0,
    ], $item['order_date'] ?? null);
    $product_title = htmlspecialchars($displaySnapshot['title'], ENT_QUOTES, 'UTF-8');
    $quantity = $item['quantity'];
    if ($sheetProduct) {
        $orderWeightKg += getSheetProductWeightKg($sheetProduct) * (float) $quantity;
    }
    $productFullPrice = (float) $displaySnapshot['price'];
    $productDiscountAmount = (float) $displaySnapshot['discount_amount'];
    $productDiscountedPrice = max(0, $productFullPrice - $productDiscountAmount);
    $image_url = getCandybirdAbsoluteImageUrl($displaySnapshot['image_url']);
    $image_url = htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8');
    $fullLineTotal = $productFullPrice * (float) $quantity;
    $discountedLineTotal = $productDiscountedPrice * (float) $quantity;
    if ($productDiscountAmount > 0) {
        $emailPriceHtml = '<span style="color:#777;text-decoration:line-through;">R' . number_format($fullLineTotal, 2) . '</span><br>'
            . '<strong style="color:#1d7d38;">R' . number_format($discountedLineTotal, 2) . '</strong>'
            . '<div style="font-size:12px;color:#777;">Unit: R' . number_format($productDiscountedPrice, 2) . ' was R' . number_format($productFullPrice, 2) . '</div>';
    } else {
        $emailPriceHtml = '<strong>R' . number_format($fullLineTotal, 2) . '</strong>'
            . '<div style="font-size:12px;color:#777;">Unit: R' . number_format($productFullPrice, 2) . '</div>';
    }

    $order_items .=
        '<tr>
            <td style="width:15%; padding: 10px; border: 1px solid #ccc;">
                <img src="' . $image_url . '" width="50px" alt="item image"/>
            </td>
            <td style="width:50%; padding: 10px; border: 1px solid #ccc;">
                <a href="' . htmlspecialchars(sirFrancisSiteUrl('product?id=' . rawurlencode((string) $productId)), ENT_QUOTES, 'UTF-8') . '">' . $product_title . '</a>
            </td>
            <td style="width:10%; padding: 10px; border: 1px solid #ccc;">' . $quantity . '</td>
            <td style="width:25%; padding: 10px; border: 1px solid #ccc;">' . $emailPriceHtml . '</td>
        </tr>';
}
$orderWeightEstimate = formatCandybirdWeightKg($orderWeightKg);

// Ensure $order is not empty before accessing its elements
if (!empty($order)) {
    $client_name = htmlspecialchars($order[0]['billing_first_name']);
    $client_number = htmlspecialchars($order[0]['billing_phone_number']);
    $client_email = htmlspecialchars($order[0]['billing_email_address']);
    $payment_total = $order[0]['grand_total_amount'];
    $order_status = "Processing"; // Assuming this is set elsewhere in your code
} else {
    error_log( "No order details found.");
    // exit();
}






$shippingDiscountAmount = $order[0]['shipping_discount_amount'];
$shippingAmount = $order[0]['shipping_amount'];
$order_discount = $order[0]['discount_amount'];
$discountedAmount = 0;
$couponId = $order[0]['coupon_id'];
$couponAmount = $order[0]['coupon_amount'];

// Check if there is a shipping discount
if ($shippingDiscountAmount > 0) {
    // Calculate the discounted amount
    $discountedAmount = number_format($shippingAmount - $shippingDiscountAmount, 2);
    // Create the HTML content for the shipping section with discount
    $shippingContent = '<span style="text-decoration: line-through;">R' . htmlspecialchars($shippingAmount) . '</span><span style="margin-left: 10px;">R' . htmlspecialchars($discountedAmount) . '</span>';
} else {
    // Create the HTML content for the shipping section without discount
    $shippingContent = 'R' . htmlspecialchars($shippingAmount);
}

$orderDiscountTotal = number_format($order_discount + $discountedAmount + $shippingDiscountAmount + $couponAmount, 2);

$couponSection = "";
$couponSectionAdmin = "";

if (!empty($couponId) && $couponAmount > 0) {
    $couponSectionAdmin = '
    <tr>
        <td colspan="4" style="padding: 10px; border: none; text-align: right;">
            <div style="color: green; border: 1px dashed #000; padding: 10px; display: inline-block;">
                User applied coupon and saved R'.number_format($couponAmount, 2).' on their order
            </div>
        </td>
    </tr>';
    $couponSection = '
    <tr>
        <td colspan="4" style="padding: 10px; border: none; text-align: right;">
            <div style="color: green; border: 1px dashed #000; padding: 10px; display: inline-block;">
                Congratulations, you saved R'.number_format($couponAmount, 2).' with a Coupon!
            </div>
        </td>
    </tr>';
}


// echo 'shippingDiscountAmount: ' .$order[0]['shipping_discount_amount'].'<br>';
// echo 'shippingAmount: ' .$order[0]['shipping_amount'].'<br>';
// echo 'order_discount: ' .$order[0]['discount_amount'].'<br>';
// echo 'discountedAmount: ' . $discountedAmount.'<br>';
// echo 'couponId: ' .$order[0]['coupon_id'].'<br>';
// echo 'couponAmount: ' .$order[0]['coupon_amount'].'<br>';
// echo 'couponSection: ' .$couponSection.'<br>';
// echo 'orderDiscountTotal: ' .$orderDiscountTotal.'<br>';

// exit();


// Now, proceed to send the order confirmation email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/candybird_mail_helpers.php';
$liveConfigPath = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: dirname(__DIR__)), '/') . '/configs_sirfrancis/sirfrancis_config.php';
if (file_exists($liveConfigPath)) {
    require_once $liveConfigPath;
} elseif (file_exists(__DIR__ . '/configs/email_config.php')) {
    require_once __DIR__ . '/configs/email_config.php';
}

try {
    // // Get the email body from the template file
    // $email_body = file_get_contents('emails/email_payment.php');

    // Replace placeholders with actual values
    $recipient_name = $client_name;
    $email_body = str_replace('{recipient_name}', $recipient_name, $email_body);
    $email_body = str_replace('{user_email_unsubscribe}', $client_email, $email_body);
    $email_body = str_replace('{order_id}', $orderId_zeropad, $email_body);
    $email_body = str_replace('{order_items}', $order_items, $email_body);

    $email_body = str_replace('{delivery_address}', $order[0]['shipping_address'], $email_body);
    $email_body = str_replace('{order_weight_estimate}', $orderWeightEstimate, $email_body);
    $email_body = str_replace('{client_name}', $client_name, $email_body);
    $email_body = str_replace('{client_number}', $client_number, $email_body);
    $email_body = str_replace('{client_email}', $client_email, $email_body);
    $email_body = str_replace('{coupon_section}', $couponSection, $email_body);
    $email_body = str_replace('{order_subtotal}', 'R'.number_format($order[0]['subtotal_amount'], 2), $email_body);
    $email_body = str_replace('{order_shipping}', $shippingContent, $email_body);
    $email_body = str_replace('{order_discount}', '-R'.$orderDiscountTotal, $email_body);
    $email_body = str_replace('{order_total}', 'R'.number_format($order[0]['grand_total_amount'], 2), $email_body);
    $email_body = str_replace('{order_status}', $order_status, $email_body);
    $email_body = str_replace('{payment_method}', $order[0]['payment_method'], $email_body);
    $email_body = str_replace('{order_notes}', $order[0]['order_notes'], $email_body);

    $clientMailResult = cbCandybirdSendMail(
        $client_email,
        $client_name,
        $email_subject,
        $email_body,
        ['prefer_mail_transport' => true]
    );
    if (!empty($clientMailResult['success'])) {
        $response = array('success' => true, 'message' => 'Order successful! Email sent successfully!');
    } else {
        error_log("Sir Francis payment email failed for order " . $orderId_zeropad . ": " . ($clientMailResult['error'] ?? 'unknown error'));
    }

    // // Get the email body for admin from the template file
    // $admin_email_body = file_get_contents('emails/email_payment_admin.php');

    // Replace placeholders with actual values for admin email
    $admin_email_body = str_replace('{recipient_name}', 'Admin', $admin_email_body);
    $admin_email_body = str_replace('{order_id}', $orderId_zeropad, $admin_email_body);
    $admin_email_body = str_replace('{user_name}', $client_name, $admin_email_body);
    $admin_email_body = str_replace('{user_email}', $client_email, $admin_email_body);
    $admin_email_body = str_replace('{order_items}', $order_items, $admin_email_body);

    $admin_email_body = str_replace('{client_name}', $client_name, $admin_email_body);
    $admin_email_body = str_replace('{client_number}', $client_number, $admin_email_body);
    $admin_email_body = str_replace('{client_email}', $client_email, $admin_email_body);
    $admin_email_body = str_replace('{coupon_section}', $couponSectionAdmin, $admin_email_body);
    $admin_email_body = str_replace('{delivery_address}', $order[0]['shipping_address'], $admin_email_body);
    $admin_email_body = str_replace('{order_weight_estimate}', $orderWeightEstimate, $admin_email_body);
    $admin_email_body = str_replace('{order_subtotal}', 'R'.number_format($order[0]['subtotal_amount'], 2), $admin_email_body);
    $admin_email_body = str_replace('{order_shipping}', $shippingContent, $admin_email_body);
    $admin_email_body = str_replace('{order_discount}', '-R'.$orderDiscountTotal, $admin_email_body);
    $admin_email_body = str_replace('{order_total}', 'R'.number_format($order[0]['grand_total_amount'], 2), $admin_email_body);
    $admin_email_body = str_replace('{order_status}', $order_status, $admin_email_body);
    $admin_email_body = str_replace('{payment_method}', $order[0]['payment_method'], $admin_email_body);
    $admin_email_body = str_replace('{order_notes}', $order[0]['order_notes'], $admin_email_body);

    $adminMailResult = cbCandybirdSendMail(
        $smtp_username1,
        'Admin',
        $admin_email_subject,
        $admin_email_body,
        [
            'reply_to_email' => $client_email,
            'reply_to_name' => $client_name ?: 'Sir Francis customer',
            'prefer_mail_transport' => true,
        ]
    );
    if (!empty($adminMailResult['success'])) {
        $response = array('success' => true, 'message' => 'Order successful! Admin email sent successfully!');
    } else {
        error_log("Sir Francis admin payment email failed for order " . $orderId_zeropad . ": " . ($adminMailResult['error'] ?? 'unknown error'));
    }


} catch (Exception $e) {
    error_log('Payment successful, but an error occurred while sending the admin email. ' . $e);
}




?>
