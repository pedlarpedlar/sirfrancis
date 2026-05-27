<?php
// Start or resume the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "manage_website_information";
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

// Include header and page menus
include 'header.php';
include 'dbh.inc.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

$shippingColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'shipping_rates_json'");
if ($shippingColumnCheck && $shippingColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE admin_website_settings ADD COLUMN shipping_rates_json LONGTEXT NULL");
}
$unitWeightColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'default_unit_weight_kg'");
if ($unitWeightColumnCheck && $unitWeightColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE admin_website_settings ADD COLUMN default_unit_weight_kg DECIMAL(10,3) NULL DEFAULT 0.25");
}
$categoryOrderColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'category_display_order'");
if ($categoryOrderColumnCheck && $categoryOrderColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE admin_website_settings ADD COLUMN category_display_order TEXT NULL");
}
$mapsKeyColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'google_maps_api_key'");
if ($mapsKeyColumnCheck && $mapsKeyColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE admin_website_settings ADD COLUMN google_maps_api_key VARCHAR(255) NULL");
}
$recaptchaEnabledColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'contact_recaptcha_enabled'");
if ($recaptchaEnabledColumnCheck && $recaptchaEnabledColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_enabled TINYINT(1) NOT NULL DEFAULT 0");
}
$recaptchaTypeColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'contact_recaptcha_type'");
if ($recaptchaTypeColumnCheck && $recaptchaTypeColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_type VARCHAR(20) NOT NULL DEFAULT 'v3'");
}
$recaptchaSiteColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'contact_recaptcha_site_key'");
if ($recaptchaSiteColumnCheck && $recaptchaSiteColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_site_key VARCHAR(255) NULL");
}
$recaptchaSecretColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'contact_recaptcha_secret_key'");
if ($recaptchaSecretColumnCheck && $recaptchaSecretColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE admin_website_settings ADD COLUMN contact_recaptcha_secret_key VARCHAR(255) NULL");
}

// Fetch settings from the database
$sql = "SELECT * FROM admin_website_settings LIMIT 1";
$result = $conn->query($sql);
$row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : [];
$settingsFlash = $_SESSION['website_settings_flash'] ?? null;
unset($_SESSION['website_settings_flash']);
$deliveryOptions = getCandybirdDeliveryOptions();
$enabledDeliveryCount = count(getCandybirdEnabledDeliveryOptions($deliveryOptions));
$extraTierLines = [];
foreach ($deliveryOptions as $methodKey => $method) {
    foreach ($method['tiers'] as $tierKey => $tier) {
        if (!in_array($tierKey, ['locker_2kg', 'locker_5kg', 'locker_20kg', 'locker_over_20kg', 'door_2kg', 'door_5kg', 'door_20kg', 'door_over_20kg'], true) && isset($tier['max_kg'])) {
            $extraTierLines[] = $methodKey . ',' . $tier['max_kg'] . ',' . $tier['price'] . ',' . $tier['label'];
        }
    }
}
$extraTierText = implode("\n", $extraTierLines);

?>

<title>Manage Website</title>

<?php
include 'page_menues.php';
?>


<div class="container mt-5">
    <h2>Edit Website Settings</h2>
<?php if ($settingsFlash): ?>
    <div id="website-settings-alert" class="alert <?php echo ($settingsFlash['status'] ?? '') === 'success' ? 'alert-success' : 'alert-danger'; ?>">
        <?php echo htmlspecialchars($settingsFlash['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php else: ?>
    <div id="website-settings-alert" class="alert d-none"></div>
<?php endif; ?>
<form id="website-settings-form" action="update_settings.php?ajax=1&v=2026-05-19-3" method="post">
    <small class="text-muted d-block mb-3">Settings save handler version: 2026-05-19-3</small>
    <div class="form-group">
        <label for="tel">Telephone</label>
        <input type="text" class="form-control" id="tel" name="tel" value="<?php echo htmlspecialchars($row['tel'] ?? '', ENT_QUOTES); ?>">
    </div>
    <div class="form-group">
        <label for="hotline">Mobile / WhatsApp Number</label>
        <input type="text" class="form-control" id="hotline" name="hotline" value="<?php echo htmlspecialchars($row['hotline'] ?? '', ENT_QUOTES); ?>">
        <small class="form-text text-muted">Used for the WhatsApp link in the website footer. If blank, the telephone number is used.</small>
    </div>
    <div class="form-group">
        <label for="email_1">Email 1</label>
        <input type="email" class="form-control" id="email_1" name="email_1" value="<?php echo htmlspecialchars($row['email_1'] ?? '', ENT_QUOTES); ?>">
    </div>
    <div class="form-group">
        <label for="email_2">Email 2</label>
        <input type="email" class="form-control" id="email_2" name="email_2" value="<?php echo htmlspecialchars($row['email_2'] ?? '', ENT_QUOTES); ?>">
    </div>
    <div class="form-group">
        <label for="address">Address</label>
        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($row['address'] ?? '', ENT_QUOTES); ?>">
    </div>
    <div class="form-group">
        <label for="headquarters">Headquarters</label>
        <input type="text" class="form-control" id="headquarters" name="headquarters" value="<?php echo htmlspecialchars($row['headquarters'] ?? '', ENT_QUOTES); ?>">
    </div>
    <div class="form-group">
        <label for="free_shipping_amount">Free Shipping Amount</label>
        <input type="number" step="0.01" class="form-control" id="free_shipping_amount" name="free_shipping_amount" value="<?php echo htmlspecialchars($row['free_shipping_amount'] ?? '', ENT_QUOTES); ?>">
        <small class="form-text text-muted">Applies to Pudo locker delivery only.</small>
    </div>
    <div class="form-group">
        <label for="google_maps_api_key">Google Maps API Key</label>
        <input type="text" class="form-control" id="google_maps_api_key" name="google_maps_api_key" value="<?php echo htmlspecialchars($row['google_maps_api_key'] ?? '', ENT_QUOTES); ?>" autocomplete="off">
        <small class="form-text text-muted">Used for checkout address autocomplete. Leave blank to keep manual address typing only.</small>
    </div>
    <div class="border rounded p-3 mb-4">
        <h4 class="mb-3">Contact Form Spam Protection</h4>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="contact_recaptcha_enabled" name="contact_recaptcha_enabled" value="1" <?php echo !empty($row['contact_recaptcha_enabled']) ? 'checked' : ''; ?>>
            <label class="form-check-label font-weight-bold" for="contact_recaptcha_enabled">Enable Google reCAPTCHA on the contact form</label>
        </div>
        <div class="form-group">
            <label for="contact_recaptcha_type">reCAPTCHA Key Type</label>
            <?php $recaptchaType = $row['contact_recaptcha_type'] ?? 'v3'; ?>
            <select class="form-control" id="contact_recaptcha_type" name="contact_recaptcha_type">
                <option value="v3" <?php echo $recaptchaType === 'v3' ? 'selected' : ''; ?>>reCAPTCHA v3 score-based key</option>
                <option value="v2_checkbox" <?php echo $recaptchaType === 'v2_checkbox' ? 'selected' : ''; ?>>reCAPTCHA v2 "I'm not a robot" checkbox key</option>
            </select>
            <small class="form-text text-muted">Choose the same type you selected in Google. "Invalid key type" means this dropdown does not match the Google key.</small>
        </div>
        <div class="form-group">
            <label for="contact_recaptcha_site_key">reCAPTCHA Site Key</label>
            <input type="text" class="form-control" id="contact_recaptcha_site_key" name="contact_recaptcha_site_key" value="<?php echo htmlspecialchars($row['contact_recaptcha_site_key'] ?? '', ENT_QUOTES); ?>" autocomplete="off">
            <small class="form-text text-muted">Public site key from Google reCAPTCHA. The contact form will still use honeypot and rate limits even if this is blank.</small>
        </div>
        <div class="form-group mb-0">
            <label for="contact_recaptcha_secret_key">reCAPTCHA Secret Key</label>
            <input type="password" class="form-control" id="contact_recaptcha_secret_key" name="contact_recaptcha_secret_key" value="<?php echo htmlspecialchars($row['contact_recaptcha_secret_key'] ?? '', ENT_QUOTES); ?>" autocomplete="new-password">
            <small class="form-text text-muted">Private secret key used by the server to verify the contact form response.</small>
        </div>
    </div>
    <div class="border rounded p-3 mb-4">
        <h4 class="mb-3">Shipping Prices</h4>
        <p class="text-muted mb-3">These values feed cart, checkout, admin edit order, emails, and PayFast totals. At least one customer-facing method must be enabled.</p>
        <div class="alert <?= $enabledDeliveryCount > 0 ? 'alert-success' : 'alert-danger' ?>">
            <?= $enabledDeliveryCount > 0 ? number_format($enabledDeliveryCount) . ' shipping/collection method(s) currently enabled for customers.' : 'No delivery method is enabled. Customers will not be able to choose delivery until one is enabled.' ?>
        </div>
        <div class="form-group">
            <label for="default_unit_weight_kg">Default weight for unit/pc items</label>
            <input type="number" step="0.001" min="0.001" class="form-control" id="default_unit_weight_kg" name="default_unit_weight_kg" value="<?php echo htmlspecialchars(number_format((float)($row['default_unit_weight_kg'] ?? 0.25), 3, '.', ''), ENT_QUOTES); ?>">
            <small class="form-text text-muted">Used when a product size is not in grams/kg, for example boxes, pieces, ml, or litres. 0.250 means 250g.</small>
        </div>
        <div class="row">
            <?php foreach ($deliveryOptions as $methodKey => $method): ?>
                <div class="col-md-6">
                    <div class="border rounded p-3 mb-3">
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input shipping-method-enabled" id="shipping_enabled_<?php echo htmlspecialchars($methodKey, ENT_QUOTES); ?>" name="shipping_enabled[<?php echo htmlspecialchars($methodKey, ENT_QUOTES); ?>]" value="1" <?php echo !empty($method['enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label font-weight-bold" for="shipping_enabled_<?php echo htmlspecialchars($methodKey, ENT_QUOTES); ?>"><?php echo htmlspecialchars($method['label'], ENT_QUOTES); ?> is visible to customers</label>
                    </div>
                    <div class="form-group">
                        <label for="shipping_estimate_<?php echo htmlspecialchars($methodKey, ENT_QUOTES); ?>">Customer delivery estimate</label>
                        <input type="text" class="form-control" id="shipping_estimate_<?php echo htmlspecialchars($methodKey, ENT_QUOTES); ?>" name="shipping_estimate[<?php echo htmlspecialchars($methodKey, ENT_QUOTES); ?>]" value="<?php echo htmlspecialchars($method['estimate'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. 2-5 working days after dispatch">
                        <small class="form-text text-muted">Shown on cart and checkout. Product-specific estimates can override or extend this later.</small>
                    </div>
                    <?php if ($methodKey === 'collect'): ?>
                        <div class="form-group">
                            <label for="collection_address">Collection address shown to customers</label>
                            <textarea class="form-control" id="collection_address" name="collection_address" rows="3"><?php echo htmlspecialchars($method['collection_address'] ?? '', ENT_QUOTES); ?></textarea>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($method['tiers'] as $tierKey => $tier): ?>
                        <?php if ($methodKey === 'collect') continue; ?>
                        <div class="form-group">
                            <label for="shipping_<?php echo htmlspecialchars($tierKey, ENT_QUOTES); ?>"><?php echo htmlspecialchars($tier['label'], ENT_QUOTES); ?></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="shipping_<?php echo htmlspecialchars($tierKey, ENT_QUOTES); ?>" name="shipping_rates[<?php echo htmlspecialchars($methodKey, ENT_QUOTES); ?>][tiers][<?php echo htmlspecialchars($tierKey, ENT_QUOTES); ?>][price]" value="<?php echo htmlspecialchars(number_format((float)$tier['price'], 2, '.', ''), ENT_QUOTES); ?>">
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="form-group mt-3">
            <label for="shipping_extra_tiers">Extra shipping tiers over 20kg</label>
            <textarea class="form-control" id="shipping_extra_tiers" name="shipping_extra_tiers" rows="4" placeholder="locker,50,500,Up to 50kg&#10;door,1000,2500,Up to 1 ton"><?php echo htmlspecialchars($extraTierText, ENT_QUOTES); ?></textarea>
            <small class="form-text text-muted">One tier per line: method, max kg, price, label. Method must be locker or door. These tiers are used before the final over-20kg flat rate.</small>
        </div>
    </div>
    <div class="form-group">
        <label for="support_email">Support Email</label>
        <input type="email" class="form-control" id="support_email" name="support_email" value="<?php echo htmlspecialchars($row['support_email'] ?? '', ENT_QUOTES); ?>">
        <small id="support_email_help" class="form-text text-muted">For Terms and Conditions, Privacy Policy, and any support-related queries.</small>
    </div>
    <div class="form-group">
        <label for="website_company_name">Website Company Name</label>
        <input type="text" class="form-control" id="website_company_name" name="website_company_name" value="<?php echo htmlspecialchars($row['website_company_name'] ?? '', ENT_QUOTES); ?>">
        <small id="website_company_name_help" class="form-text text-muted">For Terms and Conditions, Privacy Policy, and Emails.</small>
    </div>
    <div class="form-group">
        <label for="category_display_order">Category display order</label>
        <textarea class="form-control" id="category_display_order" name="category_display_order" rows="5" placeholder="One parent category per line"><?php echo htmlspecialchars($row['category_display_order'] ?? implode("\n", getCandybirdCategoryDisplayOrder()), ENT_QUOTES); ?></textarea>
        <small class="form-text text-muted">Controls the main shop/menu parent category order. Put Clearance Basket first or last here whenever you prefer.</small>
    </div>
    <div class="form-group">
        <label for="banking_details">Banking Details</label>
        <textarea class="form-control" id="banking_details" name="banking_details" rows="4"><?php echo $row['banking_details']; ?></textarea>
        <small id="banking_details_help" class="form-text text-muted">For direct deposits and EFT payments.</small>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
</form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('website-settings-form');
    var alertBox = document.getElementById('website-settings-alert');
    if (!form || !alertBox) {
        return;
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        var submitButton = form.querySelector('button[type="submit"]');
        var originalText = submitButton ? submitButton.textContent : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }
        alertBox.className = 'alert alert-info';
        alertBox.textContent = 'Saving website settings...';

        if (form.querySelectorAll('.shipping-method-enabled:checked').length < 1) {
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = 'Enable at least one shipping or collection method before saving.';
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
            return;
        }

        var saveUrl = form.getAttribute('action') + '&t=' + Date.now();

        fetch(saveUrl, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            redirect: 'manual',
            cache: 'no-store',
            headers: {
                'Accept': '*/*',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function(response) {
            return response.text().then(function(text) {
                var data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (error) {
                    data = {
                        success: response.ok,
                        message: response.ok ? 'Website settings saved.' : (text || ('Website settings save failed at ' + saveUrl + ' with HTTP ' + response.status + '.'))
                    };
                }
                if (!response.ok || !data.success) {
                    throw data;
                }
                return data;
            });
        }).then(function(data) {
            alertBox.className = 'alert alert-success';
            alertBox.textContent = data.message || 'Website settings saved.';
        }).catch(function(error) {
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = (error && error.message) ? error.message : ('Browser could not reach the settings save handler at ' + saveUrl + '. Please check whether the latest admin files were uploaded.');
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
include 'footer.php';
?>
