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

require_once __DIR__ . '/payfast_settings_helpers.php';

$user_id = !isset($_SESSION['user_id']) ? null : $_SESSION['user_id'];
$user_is_guest = false;
$sessionParam = isset($_SESSION['session_id']) ? '&session=' . urlencode($_SESSION['session_id']) : '';
if ($user_id === null) {
    $user_is_guest = true;
}

$order_id = (string) (int) preg_replace('/\D/', '', (string) ($order_id ?? ''));
$cartTotal = round((float) ($cartTotal ?? 0), 2);
$payfastSettings = sfPayfastSettings($conn ?? null);
$payNowForm = '';
$return_url = sfPayfastSiteUrl('order_details?order_id='.$order_id.$sessionParam.'&thankyou');
$cancel_url = sfPayfastSiteUrl('order_details?order_id='.$order_id.$sessionParam.'&payment-cancelled');

if ($order_id === '' || $cartTotal <= 0 || !sfPayfastReady($payfastSettings)) {
    return;
}

$passphrase = trim((string) ($payfastSettings['payfast_passphrase'] ?? ''));
$passphrase = $passphrase !== '' ? $passphrase : null;

$data = array(
    // Merchant details
    'merchant_id' => trim((string) $payfastSettings['payfast_merchant_id']),
    'merchant_key' => trim((string) $payfastSettings['payfast_merchant_key']),
    'return_url' => $return_url,
    'cancel_url' => $cancel_url,
    'notify_url' => sfPayfastSiteUrl('notify'),
    // Transaction details
    'm_payment_id' => $order_id, //Unique payment ID to pass through to notify_url
    'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
    'item_name' => substr('Sir Francis Order ' . $order_id, 0, 100)
);

$data = array_filter($data, function ($value) {
    return trim((string) $value) !== '';
});

$signature = generateSignature($data, $passphrase);
$data['signature'] = $signature;

$pfHost = sfPayfastHost($payfastSettings);
$payNowForm = '<form action="https://'.$pfHost.'/eng/process" method="post" id="payNowForm">';
foreach($data as $name=> $value) {
    $payNowForm .= '<input name="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'" type="hidden" value=\''.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'\' />';
}
//button is set here or at a custom place in your design.
$payNowForm .= '</form>';
?>
