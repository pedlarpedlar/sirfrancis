<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please sign in as admin first.']);
    exit;
}

include 'dbh.inc.php';

$orderId = (int) ($_POST['order_id'] ?? 0);
$paymentStatus = (int) ($_POST['payment_status'] ?? 2);
$paymentStatus = in_array($paymentStatus, [1, 2], true) ? $paymentStatus : 2;

if ($orderId <= 0 || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Order could not be updated.']);
    exit;
}

$stmt = $conn->prepare("UPDATE orders SET payment_status = ?, order_status = CASE WHEN order_status IN ('Pending', 'Unpaid') THEN 'Processing' ELSE order_status END WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Could not prepare payment update.']);
    exit;
}

$stmt->bind_param('ii', $paymentStatus, $orderId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Order marked as paid.' : 'Order payment status could not be updated.'
]);
