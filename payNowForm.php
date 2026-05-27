<?php
/**
 * @param array $data
 * @param null $passPhrase
 * @return string
 */
function generateSignature($data, $passPhrase = null) {
    // Create parameter string
    $pfOutput = '';
    foreach( $data as $key => $val ) {
        if($val !== '') {
            $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
        }
    }
    // Remove last ampersand
    $getString = substr( $pfOutput, 0, -1 );
    if( $passPhrase !== null ) {
        $getString .= '&passphrase='. urlencode( trim( $passPhrase ) );
    }
    return md5( $getString );
} 

$user_id = !isset($_SESSION['user_id']) ? null : $_SESSION['user_id'];
$user_is_guest = false;
$sessionParam = isset($_SESSION['session_id']) ? '&session=' . urlencode($_SESSION['session_id']) : '';
if ($user_id === null) {
    $user_is_guest = true;
}

$order_id = (string) (int) preg_replace('/\D/', '', (string) ($order_id ?? ''));
$cartTotal = round((float) ($cartTotal ?? 0), 2);
$payNowForm = '';
$return_url = 'https://www.candybird.co.za/order_details?order_id='.$order_id.$sessionParam.'&thankyou';
$cancel_url = 'https://www.candybird.co.za/order_details?order_id='.$order_id.$sessionParam.'&payment-cancelled';

if ($order_id === '' || $cartTotal <= 0) {
    return;
}


// $passphrase = 'jt7NOE43FZPn'; //'My3eautifulPass';
$passphrase = 'My3eautifulPass';

$data = array(
    // Merchant details
    'merchant_id' => '14090292', // '10000100', // live: 14090292
    'merchant_key' => '5ksggz4e5rru2', // '46f0cd694581a', //live: 5ksggz4e5rru2
    'return_url' => $return_url,
    'cancel_url' => $cancel_url,
    'notify_url' => 'https://www.candybird.co.za/notify',
    // Transaction details
    'm_payment_id' => $order_id, //Unique payment ID to pass through to notify_url
    'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
    'item_name' => substr('CandyBird Order ' . $order_id, 0, 100)
);

$data = array_filter($data, function ($value) {
    return trim((string) $value) !== '';
});

$signature = generateSignature($data, $passphrase);
$data['signature'] = $signature;

// If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
// $testingMode = true;
$testingMode = false;

$pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
$payNowForm = '<form action="https://'.$pfHost.'/eng/process" method="post" id="payNowForm">';
foreach($data as $name=> $value) {
    $payNowForm .= '<input name="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'" type="hidden" value=\''.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'\' />';
}
//button is set here or at a custom place in your design.
$payNowForm .= '</form>';
?>
