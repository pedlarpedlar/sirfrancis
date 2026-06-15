<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please sign in as admin first.']);
    exit();
}

include 'dbh.inc.php';

function cbCancelJson($payload) {
    echo json_encode($payload);
    exit();
}

function cbCancelEnsureSchema($conn) {
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payfast_payment_id VARCHAR(100) NULL");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS refund_status VARCHAR(50) NULL");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS refunded_amount DECIMAL(10,2) NOT NULL DEFAULT 0");
    $conn->query("CREATE TABLE IF NOT EXISTS order_adjustments (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        order_item_id INT NULL,
        product_id INT NULL,
        action_type VARCHAR(50) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        reason TEXT NULL,
        refund_status VARCHAR(50) NOT NULL DEFAULT 'not_required',
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(order_id),
        INDEX(order_item_id)
    )");
}

function cbCancelMoney($amount) {
    return round((float) $amount, 2);
}

function cbCancelFetchOrder($conn, $orderId) {
    $stmt = $conn->prepare("SELECT o.*, pm.label AS payment_label
        FROM orders o
        LEFT JOIN payment_methods pm ON o.payment_method = pm.id
        WHERE o.id = ?");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cbCancelJson(['success' => false, 'message' => 'Invalid request.']);
}

if (!($conn instanceof mysqli)) {
    cbCancelJson(['success' => false, 'message' => 'Order system is unavailable.']);
}

cbCancelEnsureSchema($conn);

$orderId = (int) ($_POST['orderId'] ?? 0);
$mode = $_POST['mode'] ?? '';
$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));
$reason = trim((string) ($_POST['reason'] ?? ''));
$adminId = (int) ($_SESSION['admin_id'] ?? 0);

if ($orderId <= 0 || !in_array($mode, ['full', 'item'], true)) {
    cbCancelJson(['success' => false, 'message' => 'Choose a valid order cancellation action.']);
}

$order = cbCancelFetchOrder($conn, $orderId);
if (!$order) {
    cbCancelJson(['success' => false, 'message' => 'Order not found.']);
}

$isPaid = (int) ($order['payment_status'] ?? 0) > 0;
$isPayFast = stripos((string) ($order['payment_label'] ?? ''), 'payfast') !== false || (int) ($order['payment_method'] ?? 0) === 1;
$refundStatus = ($isPaid && $isPayFast) ? 'pending_payfast_refund' : (($isPaid && !$isPayFast) ? 'manual_refund_required' : 'not_required');

mysqli_begin_transaction($conn);

try {
    if ($mode === 'full') {
        $refundAmount = cbCancelMoney($order['grand_total_amount']);
        $stmt = $conn->prepare("UPDATE orders
            SET order_status = 'Cancelled',
                grand_total_amount = 0,
                subtotal_amount = 0,
                discount_amount = 0,
                coupon_amount = 0,
                shipping_amount = 0,
                shipping_discount_amount = 0,
                refund_status = ?,
                refunded_amount = refunded_amount + ?
            WHERE id = ?");
        $stmt->bind_param('sdi', $refundStatus, $refundAmount, $orderId);
        if (!$stmt->execute()) {
            throw new Exception('Could not cancel order.');
        }

        $stmtItems = $conn->prepare("UPDATE order_items SET quantity = 0 WHERE order_id = ?");
        $stmtItems->bind_param('i', $orderId);
        $stmtItems->execute();

        $stmtAdj = $conn->prepare("INSERT INTO order_adjustments
            (order_id, action_type, quantity, amount, reason, refund_status, created_by)
            VALUES (?, 'full_cancel', 0, ?, ?, ?, ?)");
        $stmtAdj->bind_param('idssi', $orderId, $refundAmount, $reason, $refundStatus, $adminId);
        $stmtAdj->execute();
    } else {
        if ($productId <= 0) {
            throw new Exception('Choose an item to cancel.');
        }

        $stmtItem = $conn->prepare("SELECT id, product_id, product_title, quantity, price, discount_amount
            FROM order_items
            WHERE order_id = ? AND product_id = ?
            LIMIT 1");
        $stmtItem->bind_param('ii', $orderId, $productId);
        $stmtItem->execute();
        $item = $stmtItem->get_result()->fetch_assoc();
        if (!$item) {
            throw new Exception('Order item not found.');
        }

        $currentQty = (int) $item['quantity'];
        if ($currentQty <= 0) {
            throw new Exception('This item has already been fully cancelled.');
        }
        $cancelQty = min($quantity, $currentQty);

        $lineNet = cbCancelMoney(((float) $item['price'] - (float) $item['discount_amount']) * $cancelQty);
        $totalLineNetResult = $conn->query("SELECT COALESCE(SUM((price - COALESCE(discount_amount, 0)) * quantity), 0) AS total_net FROM order_items WHERE order_id = " . (int) $orderId);
        $totalLineNetRow = $totalLineNetResult ? $totalLineNetResult->fetch_assoc() : ['total_net' => 0];
        $totalLineNet = max(0.01, (float) $totalLineNetRow['total_net']);
        $couponShare = cbCancelMoney(((float) ($order['coupon_amount'] ?? 0)) * ($lineNet / $totalLineNet));
        $refundAmount = max(0, cbCancelMoney($lineNet - $couponShare));

        $newQty = $currentQty - $cancelQty;
        $stmtQty = $conn->prepare("UPDATE order_items SET quantity = ? WHERE id = ?");
        $stmtQty->bind_param('ii', $newQty, $item['id']);
        if (!$stmtQty->execute()) {
            throw new Exception('Could not update item quantity.');
        }

        $subtotalReduction = cbCancelMoney((float) $item['price'] * $cancelQty);
        $discountReduction = cbCancelMoney((float) $item['discount_amount'] * $cancelQty);

        $stmtOrder = $conn->prepare("UPDATE orders
            SET subtotal_amount = GREATEST(0, subtotal_amount - ?),
                discount_amount = GREATEST(0, discount_amount - ?),
                coupon_amount = GREATEST(0, coupon_amount - ?),
                grand_total_amount = GREATEST(0, grand_total_amount - ?),
                refund_status = ?,
                refunded_amount = refunded_amount + ?
            WHERE id = ?");
        $stmtOrder->bind_param('ddddsdi', $subtotalReduction, $discountReduction, $couponShare, $refundAmount, $refundStatus, $refundAmount, $orderId);
        if (!$stmtOrder->execute()) {
            throw new Exception('Could not update order totals.');
        }

        $actionType = $newQty === 0 ? 'item_cancel' : 'partial_item_cancel';
        $stmtAdj = $conn->prepare("INSERT INTO order_adjustments
            (order_id, order_item_id, product_id, action_type, quantity, amount, reason, refund_status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtAdj->bind_param('iiisidssi', $orderId, $item['id'], $productId, $actionType, $cancelQty, $refundAmount, $reason, $refundStatus, $adminId);
        $stmtAdj->execute();
    }

    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    cbCancelJson(['success' => false, 'message' => $e->getMessage()]);
}

$message = $mode === 'full' ? 'Order cancelled.' : 'Item quantity cancelled.';
if ($refundStatus === 'pending_payfast_refund') {
    $message .= ' Process a PayFast refund for R' . number_format($refundAmount, 2) . ' in the PayFast dashboard, then record the proof against this order.';
} elseif ($refundStatus === 'manual_refund_required') {
    $message .= ' A manual refund/credit of R' . number_format($refundAmount, 2) . ' is required.';
}

cbCancelJson([
    'success' => true,
    'message' => $message,
    'refund_required' => $refundStatus !== 'not_required',
    'refund_status' => $refundStatus,
    'refund_amount' => $refundAmount,
    'payfast_payment_id' => $order['payfast_payment_id'] ?? '',
    'payfast_refund_url' => 'https://www.payfast.co.za/'
]);
