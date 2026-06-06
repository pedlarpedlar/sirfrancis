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

if ($orderId <= 0) {
    error_log('Ozow notify ignored: no order reference. Payload keys: ' . implode(',', array_keys($data)));
    exit;
}

$result = candybirdProcessOzowResponse($conn, $data, $orderId);
if (empty($result['success'])) {
    error_log('Ozow notify not marked paid for order #' . $orderId . '. ' . ($result['message'] ?? 'Unknown error'));
}
