<?php
require_once __DIR__ . '/../product_sheet_helpers.php';

if (!defined('SF_DEFAULT_TINYMCE_API_KEY')) {
    define('SF_DEFAULT_TINYMCE_API_KEY', 'uf9ixo1olhq0m9yx1bgn5opvoxzqfqit0qeagiiuwofx9xdz');
}

if (!function_exists('cbWebsiteSettingsEnsureColumns')) {
    function cbWebsiteSettingsEnsureColumns($conn) {
        $columns = [
            'shipping_rates_json' => "ALTER TABLE admin_website_settings ADD COLUMN shipping_rates_json LONGTEXT NULL",
            'default_unit_weight_kg' => "ALTER TABLE admin_website_settings ADD COLUMN default_unit_weight_kg DECIMAL(10,3) NULL DEFAULT 0.25",
            'category_display_order' => "ALTER TABLE admin_website_settings ADD COLUMN category_display_order TEXT NULL",
            'google_maps_api_key' => "ALTER TABLE admin_website_settings ADD COLUMN google_maps_api_key VARCHAR(255) NULL",
            'google_places_api_key' => "ALTER TABLE admin_website_settings ADD COLUMN google_places_api_key VARCHAR(255) NULL",
            'google_business_place_id' => "ALTER TABLE admin_website_settings ADD COLUMN google_business_place_id VARCHAR(255) NULL",
            'google_customer_reviews_merchant_id' => "ALTER TABLE admin_website_settings ADD COLUMN google_customer_reviews_merchant_id VARCHAR(80) NULL",
            'contact_recaptcha_enabled' => "ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_enabled TINYINT(1) NOT NULL DEFAULT 0",
            'contact_recaptcha_type' => "ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_type VARCHAR(20) NOT NULL DEFAULT 'v3'",
            'contact_recaptcha_site_key' => "ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_site_key VARCHAR(255) NULL",
            'contact_recaptcha_secret_key' => "ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_secret_key VARCHAR(255) NULL",
            'tinymce_api_key' => "ALTER TABLE admin_website_settings ADD COLUMN tinymce_api_key VARCHAR(255) NULL",
            'free_shipping_amount' => "ALTER TABLE admin_website_settings ADD COLUMN free_shipping_amount DECIMAL(10,2) NULL DEFAULT 0",
            'payfast_enabled' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_enabled TINYINT(1) NOT NULL DEFAULT 0",
            'payfast_sandbox' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_sandbox TINYINT(1) NOT NULL DEFAULT 1",
            'payfast_merchant_id' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_merchant_id VARCHAR(80) NULL",
            'payfast_merchant_key' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_merchant_key VARCHAR(120) NULL",
            'payfast_passphrase' => "ALTER TABLE admin_website_settings ADD COLUMN payfast_passphrase VARCHAR(255) NULL",
        ];
        foreach ($columns as $column => $alterSql) {
            $safeColumn = $conn->real_escape_string($column);
            $check = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE '{$safeColumn}'");
            if ($check && $check->num_rows === 0) {
                $conn->query($alterSql);
            }
        }
    }
}

if (!function_exists('cbWebsiteSettingsText')) {
    function cbWebsiteSettingsText($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cbWebsiteSettingsLoad')) {
    function cbWebsiteSettingsLoad($conn) {
        cbWebsiteSettingsEnsureColumns($conn);
        $result = $conn->query("SELECT * FROM admin_website_settings LIMIT 1");
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : [];
    }
}

if (!function_exists('cbWebsiteSettingsFlash')) {
    function cbWebsiteSettingsFlash() {
        $settingsFlash = $_SESSION['website_settings_flash'] ?? null;
        unset($_SESSION['website_settings_flash']);
        return $settingsFlash;
    }
}

if (!function_exists('cbWebsiteSettingsAlert')) {
    function cbWebsiteSettingsAlert($settingsFlash) {
        if ($settingsFlash) {
            echo '<div id="website-settings-alert" class="alert ' . (($settingsFlash['status'] ?? '') === 'success' ? 'alert-success' : 'alert-danger') . '">' . cbWebsiteSettingsText($settingsFlash['message'] ?? '') . '</div>';
        } else {
            echo '<div id="website-settings-alert" class="alert d-none"></div>';
        }
    }
}

if (!function_exists('cbWebsiteSettingsSaveScript')) {
    function cbWebsiteSettingsSaveScript($requireShipping = false) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('website-settings-form');
            var alertBox = document.getElementById('website-settings-alert');
            if (!form || !alertBox) return;
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                var submitButton = form.querySelector('button[type="submit"]');
                var originalText = submitButton ? submitButton.textContent : '';
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving...';
                }
                alertBox.className = 'alert alert-info';
                alertBox.textContent = 'Saving settings...';
                <?php if ($requireShipping): ?>
                if (form.querySelectorAll('.shipping-method-enabled:checked').length < 1) {
                    alertBox.className = 'alert alert-danger';
                    alertBox.textContent = 'Enable at least one shipping or collection method before saving.';
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                    return;
                }
                <?php endif; ?>
                var saveUrl = form.getAttribute('action') + '&t=' + Date.now();
                fetch(saveUrl, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    redirect: 'manual',
                    cache: 'no-store',
                    headers: {'Accept': '*/*', 'X-Requested-With': 'XMLHttpRequest'}
                }).then(function(response) {
                    return response.text().then(function(text) {
                        var data;
                        try { data = text ? JSON.parse(text) : {}; }
                        catch (error) { data = {success: response.ok, message: response.ok ? 'Settings saved.' : (text || 'Settings could not be saved.')}; }
                        if (!response.ok || !data.success) throw data;
                        return data;
                    });
                }).then(function(data) {
                    alertBox.className = 'alert alert-success';
                    alertBox.textContent = data.message || 'Settings saved.';
                }).catch(function(error) {
                    alertBox.className = 'alert alert-danger';
                    alertBox.textContent = (error && error.message) ? error.message : 'Settings could not be saved.';
                }).finally(function() {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                });
            });
        });
        </script>
        <?php
    }
}
