<?php
if (!defined('SF_DEFAULT_GOOGLE_MAPS_API_KEY')) {
    define('SF_DEFAULT_GOOGLE_MAPS_API_KEY', 'AIzaSyDv_kbN3lexH5uDzhuUJccnqIMimC5OBE4');
}
if (!defined('SF_DEFAULT_GOOGLE_PLACES_API_KEY')) {
    define('SF_DEFAULT_GOOGLE_PLACES_API_KEY', SF_DEFAULT_GOOGLE_MAPS_API_KEY);
}
if (!defined('SF_DEFAULT_GOOGLE_BUSINESS_PLACE_ID')) {
    define('SF_DEFAULT_GOOGLE_BUSINESS_PLACE_ID', 'ChIJ_8dRaiwH9x4RqDnwIhjh7GI');
}
if (!defined('SF_DEFAULT_GOOGLE_CUSTOMER_REVIEWS_MERCHANT_ID')) {
    define('SF_DEFAULT_GOOGLE_CUSTOMER_REVIEWS_MERCHANT_ID', '518700294');
}

if (!function_exists('sfGoogleIntegrationSettings')) {
    function sfGoogleIntegrationSettings($conn) {
        static $settingsCache = null;
        if ($settingsCache !== null) {
            return $settingsCache;
        }

        $settingsCache = [
            'google_maps_api_key' => SF_DEFAULT_GOOGLE_MAPS_API_KEY,
            'google_places_api_key' => SF_DEFAULT_GOOGLE_PLACES_API_KEY,
            'google_business_place_id' => SF_DEFAULT_GOOGLE_BUSINESS_PLACE_ID,
            'google_customer_reviews_merchant_id' => SF_DEFAULT_GOOGLE_CUSTOMER_REVIEWS_MERCHANT_ID,
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
                if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
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
