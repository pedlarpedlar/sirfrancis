<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please sign in as admin first.']);
    exit();
}

include_once 'dbh.inc.php';
require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/admin_order_totals.php';

$orderId = (int) ($_POST['orderId'] ?? 0);
$mode = trim((string) ($_POST['mode'] ?? ''));
$discountType = strtolower(trim((string) ($_POST['discountType'] ?? '')));
$discountValue = max(0, (float) ($_POST['discountValue'] ?? 0));
$deliveryMethod = trim((string) ($_POST['deliveryMethod'] ?? ''));

if ($orderId <= 0 || !in_array($mode, ['item', 'order'], true)) {
    echo json_encode(['success' => false, 'message' => 'Choose a valid order and discount type.']);
    exit();
}

if ($discountValue <= 0) {
    $discountType = '';
}

if ($discountType !== '' && !in_array($discountType, ['fixed', 'percentage'], true)) {
    echo json_encode(['success' => false, 'message' => 'Discount must be fixed or percentage.']);
    exit();
}

cbAdminEnsureOrderDiscountColumns($conn);
cbAdminEnsureOrderItemDiscountColumns($conn);

if ($mode === 'order') {
    $stmt = $conn->prepare("UPDATE orders SET admin_custom_discount_type = ?, admin_custom_discount_value = ? WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Could not prepare order discount update.']);
        exit();
    }
    $stmt->bind_param('sdi', $discountType, $discountValue, $orderId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Could not save order discount.']);
        exit();
    }
} else {
    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Choose a valid product.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT price FROM order_items WHERE order_id = ? AND product_id = ? LIMIT 1");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Could not read order item.']);
        exit();
    }
    $stmt->bind_param('ii', $orderId, $productId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Order item not found.']);
        exit();
    }

    $price = max(0, (float) ($item['price'] ?? 0));
    $sheetProduct = getSheetProductById($productId);
    $sheetDiscount = 0;
    if ($sheetProduct) {
        $sheetDiscount = max(0, $price - getSheetProductPrice($sheetProduct));
    }
    $baseAfterSale = max(0, $price - $sheetDiscount);
    $customDiscount = 0;
    if ($discountValue > 0 && $discountType !== '') {
        $customDiscount = $discountType === 'percentage'
            ? round($baseAfterSale * min(100, $discountValue) / 100, 2)
            : round($discountValue, 2);
        $customDiscount = min($baseAfterSale, max(0, $customDiscount));
    }
    $totalUnitDiscount = min($price, round($sheetDiscount + $customDiscount, 2));

    $stmt = $conn->prepare("UPDATE order_items SET discount_amount = ?, admin_custom_discount_type = ?, admin_custom_discount_value = ? WHERE order_id = ? AND product_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Could not prepare item discount update.']);
        exit();
    }
    $stmt->bind_param('dsdii', $totalUnitDiscount, $discountType, $discountValue, $orderId, $productId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Could not save item discount.']);
        exit();
    }
}

$result = cbAdminRecalculateOrderTotals($conn, $orderId, $deliveryMethod !== '' ? $deliveryMethod : null, null);
if (!empty($result['success'])) {
    $result['message'] = $mode === 'order' ? 'Order discount saved.' : 'Item discount saved.';
}

echo json_encode($result);
