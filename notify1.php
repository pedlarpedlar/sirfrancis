<?php
// Tell Payfast that this page is reachable by triggering a header 200
header('HTTP/1.0 200 OK');
flush();

$pfParamString = '';

define('SANDBOX_MODE', false);
$pfHost = SANDBOX_MODE ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';

// Posted variables from ITN
$pfData = $_POST;

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
    $referrerIp = gethostbyname(parse_url($_SERVER['HTTP_REFERER'])['host']);
    return in_array($referrerIp, $validIps, true);
}

function pfValidPaymentData($cartTotal, $pfData) {
    return !(abs((float)$cartTotal - (float)$pfData['amount_gross']) > 0.01);
}

function pfValidServerConfirmation($pfParamString, $pfHost = 'www.payfast.co.za', $pfProxy = null) {
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


include_once __DIR__ . '/dbh.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require_once('/home/candybirdco/configs_candybird/candybird_config.php');

// Fetch order details
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$orderId_zeropad = str_pad($order_id, 7, '0', STR_PAD_LEFT);

$sql = "SELECT o.id, o.order_date, o.grand_total_amount, o.shipping_address, o.order_status, o.order_notes, o.subtotal_amount, o.shipping_amount, o.discount_amount, 
        pm.label AS payment_method, oi.product_id, oi.quantity, oi.price, oi.discount_amount, oi.tax_amount, p.title, p.id AS product_id, p.weight,
        ua.billing_first_name, ua.billing_last_name, ua.billing_phone_number, ua.billing_email_address, ua.billing_company_name,
        ua.billing_street_address_1, ua.billing_street_address_2, ua.billing_city, ua.billing_country, ua.billing_province, ua.billing_post_code,
        (SELECT image_url FROM images WHERE product_id = p.id LIMIT 1) AS image_url
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN product p ON oi.product_id = p.id
        LEFT JOIN payment_methods pm ON o.payment_method = pm.id
        LEFT JOIN user_addresses ua ON o.user_id = ua.user_id
        WHERE o.user_id = ? AND o.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($result) > 0) {
    $order = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    echo "No order details found.";
    exit();
}

$order_items = '';

foreach ($order as $item) {
    $productId = $item['product_id'];
    $product_title = htmlspecialchars($item['title']); // Use htmlspecialchars to prevent XSS
    $product_weight = htmlspecialchars($item['weight']);
    $quantity = $item['quantity'];
    $product_price_for_email = $item['price'] - $item['discount_amount'];
    $image_url = htmlspecialchars($item['image_url']); // Use the fetched image URL

    $order_items .=
        '<tr>
            <td style="width:15%; padding: 10px; border: 1px solid #ccc;">
                <img src="' . $image_url . '" width="50px" alt="item image"/>
            </td>
            <td style="width:50%; padding: 10px; border: 1px solid #ccc;">
                <a href="https://www.fishgelatine.co.za/v2/product?id=' . $productId . '">' . $product_title . ' ' . $product_weight . '</a>
            </td>
            <td style="width:10%; padding: 10px; border: 1px solid #ccc;">' . $quantity . '</td>
            <td style="width:25%; padding: 10px; border: 1px solid #ccc;">R' . ($product_price_for_email * $quantity) . '</td>
        </tr>';
}













$client_name = $order[0]['billing_first_name'];
$client_number = $order[0]['billing_phone_number'];
$client_email = $order[0]['billing_email_address'];
$payment_total = $order[0]['grand_total_amount'];
$cartTotal = $order[0]['grand_total_amount'];
$order_status = "Processing";

$check1 = pfValidSignature($pfData, $pfParamString); //pfValidSignature($pfData, $pfParamString); 
$check2 = pfValidIP();
$check3 = pfValidPaymentData($cartTotal, $pfData);
$check4 = pfValidServerConfirmation($pfParamString, $pfHost);


$result = "";

if ($check1 && $check2 && $check3 && $check4) {
    $result = "success";


    $email_subject = "Sir Francis | Payment Confirmation for Order #".$orderId_zeropad."";
    $admin_email_subject = "Sir Francis | Payment Received for Order #".$orderId_zeropad."";

    // Get the email body from the template file
    $email_body = file_get_contents('emails/email_payment.php');
    // Get the email body for admin from the template file
    $admin_email_body = file_get_contents('emails/email_payment_admin.php');

    
} else {
    $email_subject = "Oops... Payment Failed for Order #".$orderId_zeropad." on Sir Francis";
    $admin_email_subject = "Oops... Payment Failed for Order #".$orderId_zeropad." on Sir Francis";
    
    // Get the email body from the template file
    $email_body = file_get_contents('emails/email_payment_fail.php');
    // Get the email body for admin from the template file
    $admin_email_body = file_get_contents('emails/email_payment_fail_admin.php');
    

    if (!$check1) {
        $result = "failed check 1";
        $checkName = "check1"; // Example check name
    } elseif (!$check2) {
        $result = "failed check 2";
        $checkName = "check2"; // Example check name
    } elseif (!$check3) {
        $result = "failed check 3";
        $checkName = "check3"; // Example check name
    } elseif (!$check4) {
        $result = "failed check 4";
        $checkName = "check4"; // Example check name
    }
}

// Prepare and execute the SQL statement to insert into payment_checks table
$sql = "INSERT INTO payment_checks (order_id, payment_total, check_name, check_result, timestamp) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind parameters
    $stmt->bind_param("ssss", $order_id, $payment_total, $checkName, $checkResult);
    
    // Set parameters and execute statement
    $checkResult = $result === "success" ? 1 : 0; // Convert result to boolean
    
    $stmt->execute();
    
    echo "Result inserted into database.";
} else {
    echo "Prepare statement error: " . $conn->error;
}

// Close statement and connection
$stmt->close();


// Now, proceed to send the order confirmation email

try {
    $mail = new PHPMailer(true);

    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = $smtp_server;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username5;
    $mail->Password = $smtp_password5;
    $mail->SMTPSecure = $smtp_type;
    $mail->Port = $smtp_port;

    // Set sender and recipient(s)
    $mail->setFrom($smtp_username5, 'Sir Francis'); // Your email address and your name
    $mail->addAddress($client_email, $client_name); // Recipient's email address and name

    // Set email subject
    $mail->Subject = $email_subject;

    // // Get the email body from the template file
    // $email_body = file_get_contents('emails/email_payment.php');

    // Replace placeholders with actual values
    $recipient_name = $client_name;
    $email_body = str_replace('{recipient_name}', $recipient_name, $email_body);
    $email_body = str_replace('{user_email_unsubscribe}', $client_email, $email_body);
    $email_body = str_replace('{order_id}', $orderId_zeropad, $email_body);
    $email_body = str_replace('{order_items}', $order_items, $email_body);

    $email_body = str_replace('{delivery_address}', $order[0]['shipping_address'], $email_body);
    $email_body = str_replace('{client_name}', $client_name, $email_body);
    $email_body = str_replace('{client_number}', $client_number, $email_body);
    $email_body = str_replace('{client_email}', $client_email, $email_body);
    $email_body = str_replace('{order_subtotal}', number_format($order[0]['subtotal_amount'], 2), $email_body);
    $email_body = str_replace('{order_shipping}', number_format($order[0]['shipping_amount'], 2), $email_body);
    $email_body = str_replace('{order_discount}', number_format($order[0]['discount_amount'], 2), $email_body);
    $email_body = str_replace('{order_total}', number_format($order[0]['grand_total_amount'], 2), $email_body);
    $email_body = str_replace('{order_status}', $order_status, $email_body);
    $email_body = str_replace('{payment_method}', $order[0]['payment_method'], $email_body);
    $email_body = str_replace('{order_notes}', $order[0]['order_notes'], $email_body);

    // Set the email body
    $mail->Body = $email_body;

    // Set the email content type to HTML
    $mail->isHTML(true);

    // Send the email
    if ($mail->send()) {
        $response = array('success' => true, 'message' => 'Order successful! Email sent successfully!');
    } else {
        $response = array('success' => true, 'message' => 'Order successful! Email could not be sent.');
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    // Send a separate email to the admin
    $admin_mail = new PHPMailer(true);
    $admin_mail->isSMTP();
    $admin_mail->Host = $smtp_server;
    $admin_mail->SMTPAuth = true;
    $admin_mail->Username = $smtp_username5;
    $admin_mail->Password = $smtp_password5;
    $admin_mail->SMTPSecure = $smtp_type;
    $admin_mail->Port = $smtp_port;

    // Set sender and recipient(s)
    $admin_mail->setFrom($smtp_username5, 'Sir Francis'); // Your email address and your name
    $admin_mail->addAddress($smtp_username1, 'Admin'); // Admin email address

    // Set email subject
    $admin_mail->Subject = $admin_email_subject;

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
    $admin_email_body = str_replace('{delivery_address}', $order[0]['shipping_address'], $admin_email_body);
    $admin_email_body = str_replace('{order_subtotal}', number_format($order[0]['subtotal_amount'], 2), $admin_email_body);
    $admin_email_body = str_replace('{order_shipping}', number_format($order[0]['shipping_amount'], 2), $admin_email_body);
    $admin_email_body = str_replace('{order_discount}', number_format($order[0]['discount_amount'], 2), $admin_email_body);
    $admin_email_body = str_replace('{order_total}', number_format($order[0]['grand_total_amount'], 2), $admin_email_body);
    $admin_email_body = str_replace('{order_status}', $order_status, $admin_email_body);
    $admin_email_body = str_replace('{payment_method}', $order[0]['payment_method'], $admin_email_body);
    $admin_email_body = str_replace('{order_notes}', $order[0]['order_notes'], $admin_email_body);



    // Set the email body for admin
    $admin_mail->Body = $admin_email_body;

    // Set the email content type to HTML
    $admin_mail->isHTML(true);

    // Send the email to the admin
    if ($admin_mail->send()) {
        $response = array('success' => true, 'message' => 'Order successful! Admin email sent successfully!');
    } else {
        $response = array('success' => false, 'message' => 'Order successful, but admin email could not be sent.');
        header('Content-Type: application/json');
        echo json_encode($response);
    }


} catch (Exception $e) {
    $response = array('success' => false, 'message' => 'Order successful, but an error occurred while sending the admin email. ' . $e);
    header('Content-Type: application/json');
    echo json_encode($response);
}
