<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

define('CB_SETTINGS_SAVE_VERSION', '2026-05-19-3');

function cbSettingsWantsJson() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return isset($_GET['ajax'])
        || stripos($accept, 'application/json') !== false
        || strtolower($requestedWith) === 'xmlhttprequest';
}

function cbSettingsRedirect($status, $message) {
    if (cbSettingsWantsJson()) {
        http_response_code($status === 'success' ? 200 : 422);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => $status === 'success',
            'message' => $message,
            'version' => CB_SETTINGS_SAVE_VERSION,
        ]);
        exit();
    }

    $_SESSION['website_settings_flash'] = [
        'status' => $status,
        'message' => $message,
    ];
    header("Location: manage_website_information");
    exit();
}

if (isset($_GET['health'])) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'message' => 'Website settings save handler is reachable.',
        'version' => CB_SETTINGS_SAVE_VERSION,
    ]);
    exit();
}

if (!isset($_SESSION['admin_id'])) {
    if (cbSettingsWantsJson()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Your admin session expired. Please log in again, then save the settings.',
            'version' => CB_SETTINGS_SAVE_VERSION,
        ]);
        exit();
    }
    header("Location: admin_login?redirect=" . urlencode("manage_website_information"));
    exit();
}

include __DIR__ . '/dbh.inc.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

function cbSettingsColumnExists($conn, $column) {
    $safeColumn = $conn->real_escape_string($column);
    $check = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE '{$safeColumn}'");
    if (!$check) {
        throw new RuntimeException("Could not check website settings column {$column}: " . $conn->error);
    }
    return $check->num_rows > 0;
}

function cbSettingsEnsureColumn($conn, $column, $alterSql) {
    if (!cbSettingsColumnExists($conn, $column) && !$conn->query($alterSql)) {
        throw new RuntimeException("Could not prepare website settings storage for {$column}: " . $conn->error);
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: manage_website_information");
    exit();
}

try {
    if (!($conn instanceof mysqli) || $conn->connect_error) {
        throw new RuntimeException("Database connection is not available.");
    }

    if (!$conn->query("SHOW TABLES LIKE 'admin_website_settings'")) {
        throw new RuntimeException("Could not check website settings table: " . $conn->error);
    }

    $settingsTableCheck = $conn->query("SHOW TABLES LIKE 'admin_website_settings'");
    if (!$settingsTableCheck || $settingsTableCheck->num_rows === 0) {
        throw new RuntimeException("Website settings table is missing.");
    }

    cbSettingsEnsureColumn($conn, 'tel', "ALTER TABLE admin_website_settings ADD COLUMN tel VARCHAR(30) NULL");
    cbSettingsEnsureColumn($conn, 'hotline', "ALTER TABLE admin_website_settings ADD COLUMN hotline VARCHAR(30) NULL");
    cbSettingsEnsureColumn($conn, 'email_1', "ALTER TABLE admin_website_settings ADD COLUMN email_1 VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'email_2', "ALTER TABLE admin_website_settings ADD COLUMN email_2 VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'address', "ALTER TABLE admin_website_settings ADD COLUMN address VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'headquarters', "ALTER TABLE admin_website_settings ADD COLUMN headquarters VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'free_shipping_amount', "ALTER TABLE admin_website_settings ADD COLUMN free_shipping_amount DECIMAL(10,2) NULL DEFAULT 0");
    cbSettingsEnsureColumn($conn, 'banking_details', "ALTER TABLE admin_website_settings ADD COLUMN banking_details TEXT NULL");
    cbSettingsEnsureColumn($conn, 'website_company_name', "ALTER TABLE admin_website_settings ADD COLUMN website_company_name VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'support_email', "ALTER TABLE admin_website_settings ADD COLUMN support_email VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'shipping_rates_json', "ALTER TABLE admin_website_settings ADD COLUMN shipping_rates_json LONGTEXT NULL");
    cbSettingsEnsureColumn($conn, 'default_unit_weight_kg', "ALTER TABLE admin_website_settings ADD COLUMN default_unit_weight_kg DECIMAL(10,3) NULL DEFAULT 0.25");
    cbSettingsEnsureColumn($conn, 'google_maps_api_key', "ALTER TABLE admin_website_settings ADD COLUMN google_maps_api_key VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'contact_recaptcha_enabled', "ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_enabled TINYINT(1) NOT NULL DEFAULT 0");
    cbSettingsEnsureColumn($conn, 'contact_recaptcha_type', "ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_type VARCHAR(20) NOT NULL DEFAULT 'v3'");
    cbSettingsEnsureColumn($conn, 'contact_recaptcha_site_key', "ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_site_key VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'contact_recaptcha_secret_key', "ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_secret_key VARCHAR(255) NULL");
    cbSettingsEnsureColumn($conn, 'category_display_order', "ALTER TABLE admin_website_settings ADD COLUMN category_display_order TEXT NULL");

    $tel = trim((string) ($_POST['tel'] ?? ''));
    $hotline = trim((string) ($_POST['hotline'] ?? ''));
    $email_1 = trim((string) ($_POST['email_1'] ?? ''));
    $email_2 = trim((string) ($_POST['email_2'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $headquarters = trim((string) ($_POST['headquarters'] ?? ''));
    $free_shipping_amount = is_numeric($_POST['free_shipping_amount'] ?? null) ? (string) $_POST['free_shipping_amount'] : '0';
    $google_maps_api_key = trim((string) ($_POST['google_maps_api_key'] ?? ''));
    $contact_recaptcha_enabled = isset($_POST['contact_recaptcha_enabled']) ? 1 : 0;
    $contact_recaptcha_type = in_array($_POST['contact_recaptcha_type'] ?? 'v3', ['v3', 'v2_checkbox'], true) ? $_POST['contact_recaptcha_type'] : 'v3';
    $contact_recaptcha_site_key = trim((string) ($_POST['contact_recaptcha_site_key'] ?? ''));
    $contact_recaptcha_secret_key = trim((string) ($_POST['contact_recaptcha_secret_key'] ?? ''));
    $default_unit_weight_kg = (float) ($_POST['default_unit_weight_kg'] ?? 0.25);
    if ($default_unit_weight_kg <= 0) {
        $default_unit_weight_kg = 0.25;
    }
    $banking_details = (string) ($_POST['banking_details'] ?? '');
    $website_company_name = trim((string) ($_POST['website_company_name'] ?? ''));
    $support_email = trim((string) ($_POST['support_email'] ?? ''));
    $category_display_order = trim((string) ($_POST['category_display_order'] ?? ''));
    $postedShippingRates = $_POST['shipping_rates'] ?? [];
    $enabledShippingMethods = $_POST['shipping_enabled'] ?? [];
    foreach (['locker', 'door', 'collect'] as $methodKey) {
        if (!isset($postedShippingRates[$methodKey]) || !is_array($postedShippingRates[$methodKey])) {
            $postedShippingRates[$methodKey] = [];
        }
        $postedShippingRates[$methodKey]['enabled'] = isset($enabledShippingMethods[$methodKey]);
        $postedShippingRates[$methodKey]['estimate'] = trim((string) ($_POST['shipping_estimate'][$methodKey] ?? ''));
    }
    $postedShippingRates['collect']['collection_address'] = trim((string) ($_POST['collection_address'] ?? ''));
    $enabledCount = 0;
    foreach (['locker', 'door', 'collect'] as $methodKey) {
        if (!empty($postedShippingRates[$methodKey]['enabled'])) {
            $enabledCount++;
        }
    }
    if ($enabledCount < 1) {
        throw new RuntimeException('Enable at least one customer delivery or collection method.');
    }
    $extraTierLines = preg_split('/\r\n|\r|\n/', (string) ($_POST['shipping_extra_tiers'] ?? ''));
    foreach ($extraTierLines as $lineIndex => $line) {
        $parts = array_map('trim', explode(',', $line, 4));
        if (count($parts) < 3 || $parts[0] === '') {
            continue;
        }
        [$methodKey, $maxKg, $price] = $parts;
        if (!in_array($methodKey, ['locker', 'door'], true) || !is_numeric($maxKg) || !is_numeric($price) || (float) $maxKg <= 20) {
            continue;
        }
        $tierKey = $methodKey . '_custom_' . str_replace('.', '_', (string) (float) $maxKg) . 'kg_' . $lineIndex;
        $postedShippingRates[$methodKey]['tiers'][$tierKey] = [
            'label' => $parts[3] ?? ('Up to ' . (float) $maxKg . 'kg'),
            'max_kg' => (float) $maxKg,
            'price' => (float) $price,
        ];
    }
    $shipping_rates_json = json_encode(normalizeCandybirdDeliveryOptions($postedShippingRates));

    $settingsId = 1;
    $hasSettingsRow = false;
    $settingsResult = $conn->query("SELECT id FROM admin_website_settings ORDER BY id ASC LIMIT 1");
    if ($settingsResult && ($settingsRow = $settingsResult->fetch_assoc())) {
        $settingsId = (int) $settingsRow['id'];
        $hasSettingsRow = true;
    }

    $stmt = $conn->prepare("UPDATE admin_website_settings SET 
                            tel = ?, 
                            hotline = ?, 
                            email_1 = ?, 
                            email_2 = ?, 
                            address = ?, 
                            headquarters = ?, 
                            free_shipping_amount = ?, 
                            google_maps_api_key = ?,
                            contact_recaptcha_enabled = ?,
                            contact_recaptcha_type = ?,
                            contact_recaptcha_site_key = ?,
                            contact_recaptcha_secret_key = ?,
                            banking_details = ?,
                            website_company_name = ?,
                            support_email = ?,
                            shipping_rates_json = ?,
                            default_unit_weight_kg = ?,
                            category_display_order = ?
                            WHERE id = ?");

    if (!$stmt) {
        throw new RuntimeException("Could not prepare the website settings update: " . $conn->error);
    }

    $stmt->bind_param("ssssssssisssssssdsi", $tel, $hotline, $email_1, $email_2, $address, $headquarters, $free_shipping_amount, $google_maps_api_key, $contact_recaptcha_enabled, $contact_recaptcha_type, $contact_recaptcha_site_key, $contact_recaptcha_secret_key, $banking_details, $website_company_name, $support_email, $shipping_rates_json, $default_unit_weight_kg, $category_display_order, $settingsId);
    if (!$stmt->execute()) {
        throw new RuntimeException("Could not save website settings: " . $stmt->error);
    }
    $changedRows = $stmt->affected_rows;
    $stmt->close();

    if ($changedRows === 0 && !$hasSettingsRow) {
        $insert = $conn->prepare("INSERT INTO admin_website_settings (tel, hotline, email_1, email_2, address, headquarters, free_shipping_amount, google_maps_api_key, contact_recaptcha_enabled, contact_recaptcha_type, contact_recaptcha_site_key, contact_recaptcha_secret_key, banking_details, website_company_name, support_email, shipping_rates_json, default_unit_weight_kg, category_display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$insert) {
            throw new RuntimeException("Could not prepare the website settings insert: " . $conn->error);
        }
        $insert->bind_param("ssssssssisssssssds", $tel, $hotline, $email_1, $email_2, $address, $headquarters, $free_shipping_amount, $google_maps_api_key, $contact_recaptcha_enabled, $contact_recaptcha_type, $contact_recaptcha_site_key, $contact_recaptcha_secret_key, $banking_details, $website_company_name, $support_email, $shipping_rates_json, $default_unit_weight_kg, $category_display_order);
        if (!$insert->execute()) {
            throw new RuntimeException("Could not create website settings: " . $insert->error);
        }
        $insert->close();
    }

    cbSettingsRedirect('success', 'Website settings saved.');
} catch (Throwable $e) {
    error_log('CandyBird website settings save failed: ' . $e->getMessage());
    cbSettingsRedirect('error', 'Website settings could not be saved: ' . $e->getMessage());
}
?>
