<?php
date_default_timezone_set('Africa/Johannesburg');

include __DIR__ . '/dbh.inc.php';
require_once __DIR__ . '/ozow_helpers.php';

$orderId = (int) ($_GET['o'] ?? $_POST['o'] ?? $_GET['TransactionReference'] ?? $_POST['TransactionReference'] ?? 0);
$state = strtolower(trim((string) ($_GET['s'] ?? $_POST['s'] ?? '')));
$data = array_merge($_GET, $_POST);

if ($orderId <= 0) {
    header('Location: /profile?payment-error=1');
    exit;
}

$result = candybirdProcessOzowResponse($conn ?? null, $data, $orderId);

$target = '/order_details?order_id=' . urlencode((string) $orderId);
if (!empty($result['success'])) {
    $target .= '&thankyou=1&ozow=success';
} elseif ($state === 'c') {
    $target .= '&payment-cancelled=1';
} else {
    $target .= '&payment-error=1';
}

header('Location: ' . $target);
exit;
