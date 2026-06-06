<?php
require_once __DIR__ . '/ozow_helpers.php';

$ozowPayForm = '';
$order_id = (string) (int) preg_replace('/\D/', '', (string) ($order_id ?? ''));
$cartTotal = round((float) ($cartTotal ?? 0), 2);
$sessionParam = isset($_SESSION['session_id']) ? '&session=' . urlencode($_SESSION['session_id']) : '';
$customerEmail = trim((string) ($fetched_billing_email_address ?? ''));
$customerName = trim((string) (($fetched_billing_first_name ?? '') . ' ' . ($fetched_billing_last_name ?? '')));

if ($order_id === '' || $cartTotal <= 0 || !candybirdOzowEnabled()) {
    return;
}

$config = candybirdOzowConfig();
$data = candybirdOzowBuildPaymentData($order_id, $cartTotal, $customerEmail, $customerName, $sessionParam);

$ozowPayForm = '<form action="' . htmlspecialchars($config['pay_url'], ENT_QUOTES, 'UTF-8') . '" method="post" id="ozowPayForm">';
foreach ($data as $name => $value) {
    $ozowPayForm .= '<input name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" type="hidden" value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '" />';
}
$ozowPayForm .= '</form>';
