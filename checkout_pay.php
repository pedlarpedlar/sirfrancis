<?php
//sample file
// include 'session_logins.php';

// // Check if the user is logged in
// if (!isset($_SESSION['user_id'])) {
//     $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
//     $redirect_url = "checkout_pay" . ($order_id ? "?order_id=" . urlencode($order_id) : "");
//     header("Location: login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
//     exit(); // Stop further execution
// }

// $user_id = $_SESSION['user_id'];

// // Fetch order details
// $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

// if (!$order_id) {
//     echo "Order ID is missing.";
//     header("Location: checkout?error");
//     exit();
// }

// // Left-zero-pad with 3 zeroes
// $order_id = str_pad($order_id, 7, '0', STR_PAD_LEFT);


// $sql = "SELECT o.id, o.order_date, o.grand_total_amount, o.shipping_address, o.order_status, o.order_notes, o.subtotal_amount, o.shipping_amount, o.discount_amount, 
//         pm.label AS payment_method, oi.product_id, oi.quantity, oi.price, oi.discount_amount, oi.tax_amount, p.title, p.id AS product_id, p.weight,
//         ua.billing_first_name, ua.billing_last_name, ua.billing_phone_number, ua.billing_email_address, ua.billing_company_name,
//         ua.billing_street_address_1, ua.billing_street_address_2, ua.billing_city, ua.billing_country, ua.billing_province, ua.billing_post_code
//         FROM orders o
//         JOIN order_items oi ON o.id = oi.order_id
//         JOIN product p ON oi.product_id = p.id
//         LEFT JOIN payment_methods pm ON o.payment_method = pm.id
//         LEFT JOIN user_addresses ua ON o.user_id = ua.user_id
//         WHERE o.user_id = ? AND o.id = ?";
// $stmt = mysqli_prepare($conn, $sql);
// mysqli_stmt_bind_param($stmt, "ii", $user_id, $order_id);
// mysqli_stmt_execute($stmt);
// $result = mysqli_stmt_get_result($stmt);

// $discount_totals = 0;

// if (mysqli_num_rows($result) > 0) {
//     $order = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
//     foreach ($order as $item) {
//         $discount_totals += $item['discount_amount'];
//     }
// } else {
//     echo "No order details found.";
//     exit();
// }







// /*PAYFAST INTERGRATION*/

// /*See Docs: https://developers.payfast.co.za/docs#step_2_signature*/






// /**
//  * @param array $data
//  * @param null $passPhrase
//  * @return string
//  */
// function generateSignature($data, $passPhrase = null) {
//     // Create parameter string
//     $pfOutput = '';
//     foreach( $data as $key => $val ) {
//         if($val !== '') {
//             $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
//         }
//     }
//     // Remove last ampersand
//     $getString = substr( $pfOutput, 0, -1 );
//     if( $passPhrase !== null ) {
//         $getString .= '&passphrase='. urlencode( trim( $passPhrase ) );
//     }
//     return md5( $getString );
// } 


// // Construct variables
// $cartTotal = $order[0]['grand_total_amount']; // This amount needs to be sourced from your application


// $passphrase = 'jt7NOE43FZPn'; //'My3eautifulPass';
// $data = array(
//     // Merchant details
//     'merchant_id' => '10000100', // live: 14090292
//     'merchant_key' => '46f0cd694581a', //live: 5ksggz4e5rru2
//     'return_url' => 'https://www.fishgelatine.co.za/v2/order_details?thankyou',
//     'cancel_url' => 'https://www.fishgelatine.co.za/v2/checkout',
//     'notify_url' => 'https://www.fishgelatine.co.za/v2/notify?order_id='.$order[0]['id'].'&user_id='.$user_id,
//     // Buyer details
//     'name_first' => $order[0]['billing_first_name'],
//     'name_last'  => $order[0]['billing_last_name'],
//     'email_address'=> $order[0]['billing_email_address'],
//     // Transaction details
//     'm_payment_id' => $order[0]['id'], //Unique payment ID to pass through to notify_url
//     'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
//     'item_name' => 'Order#'.$order_id
// );

// $signature = generateSignature($data, $passphrase);
// $data['signature'] = $signature;

// // If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
// $testingMode = true;

// $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
// $htmlForm = '<form action="https://'.$pfHost.'/eng/process" method="post">';
// foreach($data as $name=> $value)
// {
//     $htmlForm .= '<input name="'.$name.'" type="hidden" value=\''.$value.'\' />';
// }
// $htmlForm .= '<input type="submit" class="btn btn-success" value="Pay Now" /></form>';


// echo $htmlForm;

?>