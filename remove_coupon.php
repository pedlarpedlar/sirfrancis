<?php
include 'session_logins.php';

if (isset($_SESSION['coupon'])) {
    unset($_SESSION['coupon']);
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Coupon removed from cart.',
    'coupon_savings' => 0,
    'coupon_code' => ''
]);
?>
