<?php
if (!function_exists('sfGoogleIntegrationSettings')) {
    function sfGoogleIntegrationSettings($conn) {
        static $settingsCache = null;
        if ($settingsCache !== null) {
            return $settingsCache;
        }

        $settingsCache = [
            'google_maps_api_key' => '',
            'google_places_api_key' => '',
            'google_business_place_id' => '',
            'google_customer_reviews_merchant_id' => '',
        ];

        if (!($conn instanceof mysqli) || $conn->connect_error) {
            return $settingsCache;
        }

        $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_website_settings'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return $settingsCache;
        }

        $columns = [];
        $columnResult = $conn->query("SHOW COLUMNS FROM admin_website_settings");
        if ($columnResult) {
            while ($column = $columnResult->fetch_assoc()) {
                $columns[] = $column['Field'];
            }
        }

        $wanted = array_values(array_intersect(array_keys($settingsCache), $columns));
        if (!$wanted) {
            return $settingsCache;
        }

        $selectColumns = implode(', ', array_map(static function($column) {
            return '`' . str_replace('`', '``', $column) . '`';
        }, $wanted));

        $result = $conn->query("SELECT {$selectColumns} FROM admin_website_settings ORDER BY id ASC LIMIT 1");
        if ($result && ($row = $result->fetch_assoc())) {
            foreach ($settingsCache as $key => $value) {
                if (array_key_exists($key, $row)) {
                    $settingsCache[$key] = trim((string) $row[$key]);
                }
            }
        }

        return $settingsCache;
    }
}

if (!function_exists('sfGooglePlacesBrowserKey')) {
    function sfGooglePlacesBrowserKey($conn) {
        $settings = sfGoogleIntegrationSettings($conn);
        return $settings['google_places_api_key'] !== ''
            ? $settings['google_places_api_key']
            : $settings['google_maps_api_key'];
    }
}

if (!function_exists('sfGoogleCustomerReviewsMerchantId')) {
    function sfGoogleCustomerReviewsMerchantId($conn) {
        $settings = sfGoogleIntegrationSettings($conn);
        return preg_replace('/\D+/', '', $settings['google_customer_reviews_merchant_id']);
    }
}
?>
