<?php
// Tell Payfast that this page is reachable by triggering a header 200
header('HTTP/1.0 200 OK');
flush();

$pfParamString = '';

define('SANDBOX_MODE', true);
$pfHost = SANDBOX_MODE ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';

include 'dbh.inc.php';

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

// Example usage:
$cartTotal = 5; // Replace with your cart total
$order_id = $pfData['m_payment_id']; // Assuming m_payment_id is your order ID from PayFast

$check1 = pfValidSignature($pfData, $pfParamString, $pfPassphrase);
$check2 = pfValidIP();
$check3 = pfValidPaymentData($cartTotal, $pfData);
$check4 = pfValidServerConfirmation($pfParamString, $pfHost);

if ($check1 && $check2 && $check3 && $check4) {
    // All checks have passed, the payment is successful
    // Add your own code here to update your database, etc.

    $stmt = $conn->prepare("INSERT INTO payment_checks (order_id, payment_total, check_name, error_details, check_result) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idssi", $order_id, $cartTotal, $check_name, 'SUCCESSFUL!', $check_result);
    $stmt->execute();
    $stmt->close();

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
}

$conn->close();
?>