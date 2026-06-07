<?php
include 'session_logins.php';

header('Content-Type: application/json');

$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
$user_id = $_SESSION['user_id'] ?? null;
$guest_identifier = $_SESSION['guest_identifier'] ?? null;
$coupon_email = candybirdNormalizeCouponEmail($_POST['billing_email_address'] ?? $_POST['email'] ?? $_SESSION['email'] ?? '');
$coupon_phone = candybirdNormalizePhone($_POST['billing_phone_number'] ?? $_POST['phone'] ?? '');

$response = [
    'success' => false,
    'status' => 'error',
    'message' => '',
    'product_ids' => ''
];

if ($coupon_code === '') {
    $response['message'] = 'Please enter a coupon code.';
    echo json_encode($response);
    exit;
}

$cartItems = getCartItems($user_id, $guest_identifier);
if (empty($cartItems)) {
    $response['message'] = 'Add items to your cart before applying a coupon.';
    echo json_encode($response);
    exit;
}

$couponContext = ['conn' => $conn];
if ($coupon_email !== '') {
    $couponContext['email'] = $coupon_email;
}
if ($coupon_phone !== '') {
    $couponContext['phone'] = $coupon_phone;
}
$selection = selectBestSheetCouponForCart($coupon_code, $cartItems, $couponContext);
if (!$selection['valid']) {
    $response['message'] = $selection['message'];
    logAction('Coupon Failed Attempt', $response['message'] . ' (' . $coupon_code . ')', $user_id, $guest_identifier);
    echo json_encode($response);
    exit;
}

$coupon = $selection['coupon'];
$discountDetails = $selection['discount'];

$_SESSION['coupon'] = [
    'id' => $coupon['id'] ?? $coupon['coupon_code'],
    'code' => $coupon['coupon_code'],
    'discount_type' => $coupon['discount_type'],
    'discount_value' => (float) $coupon['discount_value'],
    'scope' => 'sitewide',
    'max_usages' => '',
    'usage_per_user' => '',
    'min_order_value' => (float) ($coupon['min_order_value'] ?? 0),
    'start_date' => $coupon['valid_from'] ?? '',
    'expiry_date' => $coupon['valid_until'] ?? '',
    'valid_from' => $coupon['valid_from'] ?? '',
    'valid_until' => $coupon['valid_until'] ?? '',
    'valid_on_sale_items' => $coupon['valid_on_sale_items'] ?? 'no',
    'category_restriction' => $coupon['category_restriction'] ?? $coupon['category_restrictions'] ?? $coupon['valid_categories'] ?? '',
    'product_type_exclusion' => $coupon['product_type_exclusion'] ?? $coupon['product_type_exclusions'] ?? $coupon['excluded_product_types'] ?? '',
    'product_id_restriction' => $coupon['product_id_restriction'] ?? $coupon['product_id_restrictions'] ?? $coupon['valid_product_ids'] ?? '',
    'coupon_savings' => $discountDetails['coupon_savings'],
    'original_amount' => $discountDetails['eligible_amount'],
    'total_after_coupon' => $discountDetails['total_after_coupon'],
    'shipping_coupon' => false,
    'shipping_coupon_value' => 0,
    'shipping_coupon_type' => '',
];

$response['success'] = true;
$response['status'] = 'success';
$response['message'] = 'Coupon "' . htmlspecialchars($coupon_code, ENT_QUOTES, 'UTF-8') . '" applied. You saved R' . number_format($discountDetails['coupon_savings'], 2) . '.';
$response['coupon_code'] = $coupon['coupon_code'];
$response['coupon_id'] = $coupon['id'] ?? $coupon['coupon_code'];
$response['coupon_savings'] = $discountDetails['coupon_savings'];
$response['eligible_amount'] = $discountDetails['eligible_amount'];
$response['total_after_coupon'] = $discountDetails['total_after_coupon'];

logAction('Coupon Applied', $response['message'], $user_id, $guest_identifier);

echo json_encode($response);
?>
