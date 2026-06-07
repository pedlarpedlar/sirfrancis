<?php
function applyCoupon($conn, $coupon_code, $user_id, $guest_identifier) {
    $couponContext = ['conn' => $conn];
    if (!empty($_SESSION['email'])) {
        $couponContext['email'] = $_SESSION['email'];
    }
    $selection = selectBestSheetCouponForCart($coupon_code, getCartItems($user_id, $guest_identifier), $couponContext);

    if (!$selection['valid']) {
        $_SESSION['coupon']['coupon_savings'] = 0;
        $_SESSION['coupon']['coupon_message'] = $selection['message'];
        return ['status' => 'error', 'success' => false, 'message' => $selection['message']];
    }

    $coupon = $selection['coupon'];
    $discountDetails = $selection['discount'];
    $_SESSION['coupon'] = [
        'id' => $coupon['id'] ?? $coupon['coupon_code'],
        'code' => $coupon['coupon_code'],
        'discount_type' => $coupon['discount_type'],
        'discount_value' => (float) $coupon['discount_value'],
        'scope' => 'sitewide',
        'min_order_value' => (float) ($coupon['min_order_value'] ?? 0),
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
        'coupon_message' => $discountDetails['message'],
    ];

    return ['status' => 'success', 'success' => true, 'message' => $discountDetails['message']];
}
?>
