<?php
header('HTTP/1.0 200 OK');

date_default_timezone_set('Africa/Johannesburg');
include __DIR__ . '/dbh.inc.php';
require_once __DIR__ . '/ozow_helpers.php';

$data = $_POST;
if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = $_GET;
}

function cbOzowPostedValue(array $data, array $keys, $default = '') {
    foreach ($keys as $key) {
        if (isset($data[$key]) && trim((string) $data[$key]) !== '') {
            return trim((string) $data[$key]);
        }
    }
    return $default;
}

function cbOzowLooksPaid(array $data) {
    $status = strtolower(cbOzowPostedValue($data, ['Status', 'status', 'PaymentStatus', 'payment_status', 'TransactionStatus']));
    $success = strtolower(cbOzowPostedValue($data, ['IsSuccessful', 'isSuccessful', 'Successful', 'successful', 'Success', 'success']));
    $statusCode = strtolower(cbOzowPostedValue($data, ['StatusCode', 'statusCode', 'StatusId', 'status_id']));

    if (in_array($success, ['true', '1', 'yes'], true)) {
        return true;
    }

    if (in_array($status, ['complete', 'completed', 'successful', 'success', 'paid'], true)) {
        return true;
    }

    return in_array($statusCode, ['1', 'complete', 'completed', 'successful', 'success', 'paid'], true);
}

function cbOzowLogPaymentCheck($conn, $orderId, $amount, $name, $details, $result) {
    if (!($conn instanceof mysqli)) {
        return;
    }
    $tableCheck = $conn->query("SHOW TABLES LIKE 'payment_checks'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return;
    }
    $stmt = $conn->prepare("INSERT INTO payment_checks (order_id, payment_total, check_name, error_details, check_result) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("idssi", $orderId, $amount, $name, $details, $result);
    $stmt->execute();
    $stmt->close();
}

if (!($conn instanceof mysqli)) {
    error_log('Ozow notify failed: database unavailable.');
    exit;
}

$orderId = (int) cbOzowPostedValue($data, ['TransactionReference', 'transactionReference', 'm_payment_id', 'order_id']);
$paidAmount = (float) cbOzowPostedValue($data, ['Amount', 'amount', 'AmountPaid', 'amount_paid'], '0');
$transactionId = cbOzowPostedValue($data, ['TransactionId', 'transactionId', 'OzowTransactionId', 'ozowTransactionId', 'PaymentId', 'payment_id']);
$status = cbOzowPostedValue($data, ['Status', 'status', 'PaymentStatus', 'payment_status', 'TransactionStatus'], 'unknown');

if ($orderId <= 0) {
    error_log('Ozow notify ignored: no order reference. Payload keys: ' . implode(',', array_keys($data)));
    exit;
}

$stmt = $conn->prepare("SELECT grand_total_amount FROM orders WHERE id = ? LIMIT 1");
if (!$stmt) {
    error_log('Ozow notify failed: could not prepare order lookup.');
    exit;
}
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    error_log('Ozow notify ignored: order not found #' . $orderId);
    exit;
}

$expectedAmount = (float) $order['grand_total_amount'];
$amountValid = $paidAmount > 0 && abs($expectedAmount - $paidAmount) <= 0.01;
$looksPaid = cbOzowLooksPaid($data);
$hashValid = candybirdOzowResponseHashValid($data);

if ($looksPaid && $amountValid && $hashValid) {
    candybirdEnsureOzowOrderColumns($conn);
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 1, ozow_transaction_id = ?, ozow_payment_status = ?, order_status = CASE WHEN order_status IN ('Pending', 'Unpaid') THEN 'Processing' ELSE order_status END WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $transactionId, $status, $orderId);
        $stmt->execute();
        $stmt->close();
    }
    cbOzowLogPaymentCheck($conn, $orderId, $expectedAmount, 'ozow complete', 'Ozow payment marked successful.', 1);
} else {
    $details = 'Status: ' . $status . '; amount received: ' . $paidAmount . '; expected: ' . $expectedAmount . '; hash valid: ' . ($hashValid ? 'yes' : 'no');
    cbOzowLogPaymentCheck($conn, $orderId, $expectedAmount, 'ozow payment check', $details, 0);
    error_log('Ozow notify not marked paid for order #' . $orderId . '. ' . $details);
}
