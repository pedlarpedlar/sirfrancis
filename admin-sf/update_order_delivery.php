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
$deliveryMethod = $_POST['deliveryMethod'] ?? 'locker';

if ($orderId <= 0 || !in_array($deliveryMethod, ['locker', 'door', 'collect', 'digital'], true)) {
    echo json_encode(['success' => false, 'message' => 'Choose a valid delivery method.']);
    exit();
}

echo json_encode(cbAdminRecalculateOrderTotals($conn, $orderId, $deliveryMethod, null));
