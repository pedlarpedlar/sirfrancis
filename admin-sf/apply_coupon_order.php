<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please sign in as admin first.']);
    exit();
}

include_once 'dbh.inc.php';
require_once __DIR__ . '/admin_order_totals.php';

$orderId = (int) ($_POST['orderId'] ?? 0);
$couponCode = trim((string) ($_POST['couponCode'] ?? $_POST['couponId'] ?? ''));
$deliveryMethod = $_POST['deliveryMethod'] ?? null;

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required.']);
    exit();
}

$result = cbAdminRecalculateOrderTotals($conn, $orderId, $deliveryMethod, $couponCode);
if ($result['success']) {
    $result['message'] = $couponCode === '' ? 'Coupon removed and order recalculated.' : 'Coupon applied from the coupon sheet.';
}

echo json_encode($result);
