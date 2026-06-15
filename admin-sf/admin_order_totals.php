<?php
require_once __DIR__ . '/../product_sheet_helpers.php';

if (!function_exists('cbAdminOrderItemsForCoupon')) {
    function cbAdminOrderItemsForCoupon($conn, $orderId) {
        $items = [];
        $stmt = mysqli_prepare($conn, "SELECT product_id, quantity, price, discount_amount FROM order_items WHERE order_id = ?");
        if (!$stmt) {
            return $items;
        }

        mysqli_stmt_bind_param($stmt, 'i', $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $price = (float) $row['price'];
            $discount = (float) $row['discount_amount'];
            $items[] = [
                'id' => $row['product_id'],
                'quantity' => (int) $row['quantity'],
                'price' => $price,
                'discount_amount' => $discount,
                'discounted_price' => max(0, $price - $discount),
            ];
        }
        mysqli_stmt_close($stmt);
        return $items;
    }
}

if (!function_exists('cbAdminOrderWeightKg')) {
    function cbAdminOrderWeightKg($conn, $orderId) {
        $weight = 0;
        $stmt = mysqli_prepare($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, 'i', $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $product = getSheetProductById($row['product_id']);
            if ($product) {
                $weight += getSheetProductWeightKg($product) * (int) $row['quantity'];
            }
        }
        mysqli_stmt_close($stmt);
        return $weight;
    }
}

if (!function_exists('cbAdminInferDeliveryMethod')) {
    function cbAdminInferDeliveryMethod($order) {
        $notes = strtolower((string) ($order['order_notes'] ?? ''));
        if (strpos($notes, 'door') !== false) {
            return 'door';
        }
        if (strpos($notes, 'digital') !== false) {
            return 'digital';
        }
        if (strpos($notes, 'collection') !== false || strpos($notes, 'collect') !== false) {
            return 'collect';
        }
        return 'locker';
    }
}

if (!function_exists('cbAdminOrderCouponEmail')) {
    function cbAdminOrderCouponEmail($conn, $order) {
        if (!($conn instanceof mysqli) || !is_array($order)) {
            return '';
        }

        $userId = isset($order['user_id']) ? (int) $order['user_id'] : 0;
        if ($userId > 0) {
            $stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
                mysqli_stmt_close($stmt);
                $email = candybirdNormalizeCouponEmail($row['email'] ?? '');
                if ($email !== '') {
                    return $email;
                }
            }
        }

        $guestIdentifier = trim((string) ($order['guest_identifier'] ?? ''));
        $stmt = null;
        if ($guestIdentifier !== '') {
            $stmt = mysqli_prepare($conn, "SELECT billing_email_address FROM user_addresses WHERE guest_identifier = ? ORDER BY id DESC LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $guestIdentifier);
            }
        } elseif ($userId > 0) {
            $stmt = mysqli_prepare($conn, "SELECT billing_email_address FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $userId);
            }
        }

        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
            mysqli_stmt_close($stmt);
            return candybirdNormalizeCouponEmail($row['billing_email_address'] ?? '');
        }

        return '';
    }
}

if (!function_exists('cbAdminRecalculateOrderTotals')) {
    function cbAdminRecalculateOrderTotals($conn, $orderId, $deliveryMethod = null, $couponCode = null) {
        $stmtOrder = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
        if (!$stmtOrder) {
            return ['success' => false, 'message' => 'Could not read order.'];
        }
        mysqli_stmt_bind_param($stmtOrder, 'i', $orderId);
        mysqli_stmt_execute($stmtOrder);
        $order = mysqli_stmt_get_result($stmtOrder)->fetch_assoc();
        mysqli_stmt_close($stmtOrder);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $stmtTotals = mysqli_prepare($conn, "SELECT
            COALESCE(SUM(quantity * price), 0) AS subtotal,
            COALESCE(SUM(quantity * COALESCE(discount_amount, 0)), 0) AS discount
            FROM order_items
            WHERE order_id = ?");
        mysqli_stmt_bind_param($stmtTotals, 'i', $orderId);
        mysqli_stmt_execute($stmtTotals);
        $totals = mysqli_stmt_get_result($stmtTotals)->fetch_assoc();
        mysqli_stmt_close($stmtTotals);

        $subtotal = round((float) ($totals['subtotal'] ?? 0), 2);
        $discount = round((float) ($totals['discount'] ?? 0), 2);
        $couponAmount = (float) ($order['coupon_amount'] ?? 0);
        $couponEmail = cbAdminOrderCouponEmail($conn, $order);

        if ($couponCode !== null) {
            $couponCode = trim((string) $couponCode);
            if ($couponCode === '') {
                $couponAmount = 0;
            } else {
                if ($couponEmail === '') {
                    return ['success' => false, 'message' => 'Add a customer email address before applying a coupon.'];
                }

                $couponSelection = selectBestSheetCouponForCart(
                    $couponCode,
                    cbAdminOrderItemsForCoupon($conn, $orderId),
                    ['conn' => $conn, 'email' => $couponEmail, 'exclude_order_id' => $orderId]
                );
                if (empty($couponSelection['valid'])) {
                    return ['success' => false, 'message' => $couponSelection['message'] ?? 'Coupon could not be applied.'];
                }
                $couponAmount = (float) ($couponSelection['discount']['coupon_savings'] ?? 0);
            }
        }

        $beforeShipping = max(0, round($subtotal - $discount - $couponAmount, 2));
        $weightKg = cbAdminOrderWeightKg($conn, $orderId);
        $method = $deliveryMethod ?: cbAdminInferDeliveryMethod($order);
        if ($weightKg <= 0) {
            $method = 'digital';
        }
        $quote = getCandybirdDeliveryQuote($method, $weightKg, $beforeShipping, getCandybirdFreeShippingAmount());
        $shippingAmount = (float) $quote['shipping_amount'];
        $shippingDiscount = (float) $quote['shipping_discount_amount'];
        $grandTotal = max(0, round($beforeShipping + $shippingAmount - $shippingDiscount, 2));

        $notes = (string) ($order['order_notes'] ?? '');
        $notes = preg_replace('/\n?Delivery: .*$/m', '', $notes);
        $notes = trim($notes . "\nDelivery: " . $quote['method_label'] . " (" . $quote['tier_label'] . ")");

        $stmtUpdate = mysqli_prepare($conn, "UPDATE orders SET
            subtotal_amount = ?,
            discount_amount = ?,
            coupon_amount = ?,
            shipping_amount = ?,
            shipping_discount_amount = ?,
            grand_total_amount = ?,
            order_notes = ?
            WHERE id = ?");
        if (!$stmtUpdate) {
            return ['success' => false, 'message' => 'Could not prepare order total update.'];
        }

        mysqli_stmt_bind_param($stmtUpdate, 'ddddddsi', $subtotal, $discount, $couponAmount, $shippingAmount, $shippingDiscount, $grandTotal, $notes, $orderId);
        $ok = mysqli_stmt_execute($stmtUpdate);
        $message = $ok ? 'Order totals updated.' : mysqli_stmt_error($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);

        if ($ok && $couponCode !== null && trim((string) $couponCode) !== '' && $couponAmount > 0) {
            if (!candybirdRecordCouponEmailUsage($conn, $couponCode, $couponEmail, $orderId)) {
                return ['success' => false, 'message' => 'This coupon has already been used with this email address.'];
            }
        }

        return [
            'success' => $ok,
            'message' => $message,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon_amount' => $couponAmount,
            'shipping_amount' => $shippingAmount,
            'shipping_discount_amount' => $shippingDiscount,
            'shipping_payable' => max(0, $shippingAmount - $shippingDiscount),
            'grand_total' => $grandTotal,
            'weight_kg' => $weightKg,
            'delivery_method' => $quote['method'],
            'delivery_label' => $quote['method_label'],
            'tier_label' => $quote['tier_label'],
        ];
    }
}
