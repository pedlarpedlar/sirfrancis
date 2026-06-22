<?php
if (!function_exists('sfPayfastEnsureColumns')) {
    function sfPayfastEnsureColumns($conn) {
        if (!($conn instanceof mysqli) || $conn->connect_error) {
            return false;
        }

        $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_website_settings'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return false;
        }

        $columns = [
            'payfast_enabled' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_enabled TINYINT(1) NOT NULL DEFAULT 0",
            'payfast_sandbox' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_sandbox TINYINT(1) NOT NULL DEFAULT 1",
            'payfast_merchant_id' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_merchant_id VARCHAR(80) NULL",
            'payfast_merchant_key' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_merchant_key VARCHAR(120) NULL",
            'payfast_passphrase' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_passphrase VARCHAR(255) NULL",
        ];

        foreach ($columns as $column => $alterSql) {
            $safeColumn = $conn->real_escape_string($column);
            $check = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE '{$safeColumn}'");
            if ($check && $check->num_rows === 0 && !$conn->query($alterSql)) {
                error_log('Sir Francis could not add PayFast settings column ' . $column . ': ' . $conn->error);
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('sfPayfastSettings')) {
    function sfPayfastSettings($conn) {
        $settings = [
            'payfast_enabled' => 0,
            'payfast_sandbox' => 1,
            'payfast_merchant_id' => '',
            'payfast_merchant_key' => '',
            'payfast_passphrase' => '',
        ];

        if (!sfPayfastEnsureColumns($conn)) {
            return $settings;
        }

        $result = $conn->query("SELECT payfast_enabled, payfast_sandbox, payfast_merchant_id, payfast_merchant_key, payfast_passphrase FROM admin_website_settings ORDER BY id ASC LIMIT 1");
        if ($result && ($row = $result->fetch_assoc())) {
            foreach ($settings as $key => $value) {
                if (array_key_exists($key, $row)) {
                    $settings[$key] = is_numeric($value) ? (int) $row[$key] : trim((string) $row[$key]);
                }
            }
        }

        return $settings;
    }
}

if (!function_exists('sfPayfastReady')) {
    function sfPayfastReady($settings) {
        return !empty($settings['payfast_enabled'])
            && trim((string) ($settings['payfast_merchant_id'] ?? '')) !== ''
            && trim((string) ($settings['payfast_merchant_key'] ?? '')) !== '';
    }
}

if (!function_exists('sfPayfastHost')) {
    function sfPayfastHost($settings) {
        return !empty($settings['payfast_sandbox']) ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
    }
}

if (!function_exists('sfPayfastSiteUrl')) {
    function sfPayfastSiteUrl($path = '') {
        $path = '/' . ltrim((string) $path, '/');
        return 'https://www.sirfrancis.co.za' . $path;
    }
}
?>
