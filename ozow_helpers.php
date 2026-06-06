<?php

if (!function_exists('candybirdOzowConfig')) {
    function candybirdOzowConfig() {
        $siteCode = $GLOBALS['ozow_site_code'] ?? (defined('OZOW_SITE_CODE') ? OZOW_SITE_CODE : '');
        $privateKey = $GLOBALS['ozow_private_key'] ?? (defined('OZOW_PRIVATE_KEY') ? OZOW_PRIVATE_KEY : '');
        $apiKey = $GLOBALS['ozow_api_key'] ?? (defined('OZOW_API_KEY') ? OZOW_API_KEY : '');
        $testMode = $GLOBALS['ozow_test_mode'] ?? (defined('OZOW_TEST_MODE') ? OZOW_TEST_MODE : true);
        $displayName = $GLOBALS['ozow_display_name'] ?? (defined('OZOW_DISPLAY_NAME') ? OZOW_DISPLAY_NAME : 'CandyBird');

        return [
            'site_code' => trim((string) $siteCode),
            'private_key' => trim((string) $privateKey),
            'api_key' => trim((string) $apiKey),
            'test_mode' => filter_var($testMode, FILTER_VALIDATE_BOOLEAN),
            'display_name' => trim((string) $displayName) !== '' ? trim((string) $displayName) : 'CandyBird',
            'pay_url' => 'https://pay.ozow.com',
        ];
    }
}

if (!function_exists('candybirdOzowEnabled')) {
    function candybirdOzowEnabled() {
        $config = candybirdOzowConfig();
        return $config['site_code'] !== '' && $config['private_key'] !== '';
    }
}

if (!function_exists('candybirdIsOzowPaymentLabel')) {
    function candybirdIsOzowPaymentLabel($label) {
        return stripos((string) $label, 'ozow') !== false;
    }
}

if (!function_exists('candybirdEnsureOzowPaymentMethod')) {
    function candybirdEnsureOzowPaymentMethod($conn) {
        if (!($conn instanceof mysqli) || !candybirdOzowEnabled()) {
            return null;
        }

        $tableCheck = $conn->query("SHOW TABLES LIKE 'payment_methods'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return null;
        }

        $config = candybirdOzowConfig();
        $label = $config['test_mode'] ? 'Ozow Instant EFT (test mode)' : 'Ozow Instant EFT';
        $description = $config['test_mode']
            ? 'Test checkout via Ozow Instant EFT. No real cash is collected while test mode is enabled.'
            : 'Pay securely by instant EFT through Ozow.';

        $existing = $conn->query("SELECT id FROM payment_methods WHERE LOWER(label) LIKE '%ozow%' LIMIT 1");
        if ($existing && ($row = $existing->fetch_assoc())) {
            $id = (int) $row['id'];
            $stmt = $conn->prepare("UPDATE payment_methods SET label = ?, description = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $label, $description, $id);
                $stmt->execute();
                $stmt->close();
            }
            return $id;
        }

        $stmt = $conn->prepare("INSERT INTO payment_methods (label, description) VALUES (?, ?)");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("ss", $label, $description);
        $stmt->execute();
        $id = $stmt->insert_id ?: null;
        $stmt->close();

        return $id ? (int) $id : null;
    }
}

if (!function_exists('candybirdOzowHash')) {
    function candybirdOzowHash(array $data, $privateKey) {
        $fields = [
            'SiteCode',
            'CountryCode',
            'CurrencyCode',
            'Amount',
            'TransactionReference',
            'BankReference',
            'Optional1',
            'Optional2',
            'Optional3',
            'Optional4',
            'Optional5',
            'Customer',
            'CancelUrl',
            'ErrorUrl',
            'SuccessUrl',
            'NotifyUrl',
            'IsTest',
        ];

        $hashString = '';
        foreach ($fields as $field) {
            $hashString .= (string) ($data[$field] ?? '');
        }
        $hashString .= (string) $privateKey;

        return hash('sha512', strtolower($hashString));
    }
}

if (!function_exists('candybirdOzowBuildPaymentData')) {
    function candybirdOzowBuildPaymentData($orderId, $amount, $customerEmail, $customerName, $sessionParam = '') {
        $config = candybirdOzowConfig();
        $orderId = (string) (int) $orderId;
        $amount = number_format((float) $amount, 2, '.', '');
        $baseOrderUrl = 'https://www.candybird.co.za/order_details?order_id=' . urlencode($orderId) . $sessionParam;

        $data = [
            'SiteCode' => $config['site_code'],
            'CountryCode' => 'ZA',
            'CurrencyCode' => 'ZAR',
            'Amount' => $amount,
            'TransactionReference' => $orderId,
            'BankReference' => substr($config['display_name'] . ' Order ' . str_pad($orderId, 7, '0', STR_PAD_LEFT), 0, 20),
            'Optional1' => $config['display_name'],
            'Optional2' => $customerEmail,
            'Optional3' => '',
            'Optional4' => '',
            'Optional5' => '',
            'Customer' => trim((string) $customerName) !== '' ? trim((string) $customerName) : $customerEmail,
            'CancelUrl' => $baseOrderUrl . '&payment-cancelled=1',
            'ErrorUrl' => $baseOrderUrl . '&payment-error=1',
            'SuccessUrl' => $baseOrderUrl . '&thankyou=1&ozow=success',
            'NotifyUrl' => 'https://www.candybird.co.za/ozow_notify',
            'IsTest' => $config['test_mode'] ? 'true' : 'false',
        ];

        $data['HashCheck'] = candybirdOzowHash($data, $config['private_key']);

        return $data;
    }
}

if (!function_exists('candybirdOzowResponseHash')) {
    function candybirdOzowResponseHash(array $data, $privateKey) {
        $fields = [
            'SiteCode',
            'TransactionId',
            'TransactionReference',
            'Amount',
            'Status',
            'Optional1',
            'Optional2',
            'Optional3',
            'Optional4',
            'Optional5',
            'CurrencyCode',
            'IsTest',
            'StatusMessage',
        ];

        $hashString = '';
        foreach ($fields as $field) {
            $hashString .= (string) ($data[$field] ?? '');
        }
        $hashString .= (string) $privateKey;

        return hash('sha512', strtolower($hashString));
    }
}

if (!function_exists('candybirdOzowResponseLooksComplete')) {
    function candybirdOzowResponseLooksComplete(array $data) {
        $status = strtolower(trim((string) ($data['Status'] ?? $data['status'] ?? '')));
        return in_array($status, ['complete', 'completed', 'successful', 'success', 'paid'], true);
    }
}

if (!function_exists('candybirdOzowResponseHashValid')) {
    function candybirdOzowResponseHashValid(array $data) {
        $config = candybirdOzowConfig();
        $sentHash = trim((string) ($data['Hash'] ?? $data['hash'] ?? ''));
        if ($sentHash === '' || $config['private_key'] === '') {
            return false;
        }
        return hash_equals(strtolower($sentHash), strtolower(candybirdOzowResponseHash($data, $config['private_key'])));
    }
}

if (!function_exists('candybirdEnsureOzowOrderColumns')) {
    function candybirdEnsureOzowOrderColumns($conn) {
        if (!($conn instanceof mysqli)) {
            return;
        }
        $columns = [
            'ozow_transaction_id' => 'VARCHAR(100) NULL',
            'ozow_payment_status' => 'VARCHAR(100) NULL',
        ];
        foreach ($columns as $column => $definition) {
            $columnCheck = $conn->query("SHOW COLUMNS FROM orders LIKE '" . $conn->real_escape_string($column) . "'");
            if ($columnCheck && $columnCheck->num_rows === 0) {
                $conn->query("ALTER TABLE orders ADD COLUMN " . $column . " " . $definition);
            }
        }
    }
}
