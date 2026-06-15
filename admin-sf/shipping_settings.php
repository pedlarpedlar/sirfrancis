<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("shipping_settings"));
    exit();
}

include 'header.php';
include 'dbh.inc.php';
require_once __DIR__ . '/website_settings_helpers.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

$row = cbWebsiteSettingsLoad($conn);
$settingsFlash = cbWebsiteSettingsFlash();
$deliveryOptions = getCandybirdDeliveryOptions();
$enabledDeliveryCount = count(getCandybirdEnabledDeliveryOptions($deliveryOptions));
$defaultUnitWeightGrams = getCandybirdDefaultUnitWeightKg($row['default_unit_weight_kg'] ?? 0.25) * 1000;
$extraTierLines = [];
foreach ($deliveryOptions as $methodKey => $method) {
    foreach ($method['tiers'] as $tierKey => $tier) {
        if (!in_array($tierKey, ['locker_2kg', 'locker_5kg', 'locker_20kg', 'locker_over_20kg', 'door_2kg', 'door_5kg', 'door_20kg', 'door_over_20kg'], true) && isset($tier['max_kg'])) {
            $extraTierLines[] = $methodKey . ',' . $tier['max_kg'] . ',' . $tier['price'] . ',' . $tier['label'];
        }
    }
}
$extraTierText = implode("\n", $extraTierLines);

include 'page_menues.php';
?>

<title>Shipping Settings</title>

<style>
    .shipping-settings-shell { padding-top: 28px; padding-bottom: 60px; }
    .shipping-settings-card { background:#fff; border:1px solid #e8ded2; border-radius:8px; padding:22px; margin-bottom:24px; box-shadow:0 8px 24px rgba(42, 29, 18, .04); }
    .shipping-settings-card h4 { color:#28364B; margin-bottom:14px; }
    .shipping-settings-card .form-group { margin-bottom:18px; }
    .shipping-method-card { background:#fbfaf7; border:1px solid #e8ded2; border-radius:8px; padding:18px; margin-bottom:18px; height:100%; }
    .shipping-help-panel { display:none; background:#f6f0ff; border:1px solid #dfccf3; border-radius:8px; padding:16px; margin-top:14px; color:#47354f; }
    .shipping-help-panel.is-visible { display:block; }
    .shipping-help-panel ul { padding-left:20px; margin-bottom:0; }
    .shipping-help-panel li { margin-bottom:8px; }
    .shipping-inline-help { color:#6d6570; font-size:13px; line-height:1.5; margin-top:6px; }
    .shipping-field-grid { display:grid; grid-template-columns: minmax(0, 1fr); gap:18px; }
    @media (min-width: 768px) {
        .shipping-field-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>

<div class="container shipping-settings-shell">
    <h2>Shipping</h2>
    <p class="text-muted">Manage delivery methods, delivery estimates, free shipping threshold and shipping weights from one place.</p>
    <?php cbWebsiteSettingsAlert($settingsFlash); ?>
    <form id="website-settings-form" action="update_settings.php?ajax=1&section=shipping" method="post">
        <input type="hidden" name="settings_section" value="shipping">
        <div class="shipping-settings-card">
            <h4 class="mb-3">Free Shipping</h4>
            <div class="form-group">
                <label for="free_shipping_amount">Free Shipping Threshold</label>
                <input type="number" step="0.01" class="form-control" id="free_shipping_amount" name="free_shipping_amount" value="<?= cbWebsiteSettingsText($row['free_shipping_amount'] ?? '') ?>">
                <small class="form-text text-muted">Calculated after product discounts and coupons. The delivery method must also be marked as eligible below.</small>
            </div>
        </div>

        <div class="shipping-settings-card">
            <h4 class="mb-3">Shipping Prices</h4>
            <p class="text-muted mb-3">These values feed cart, checkout, admin edit order, emails, and PayFast totals. At least one customer-facing method must be enabled.</p>
            <div class="alert <?= $enabledDeliveryCount > 0 ? 'alert-success' : 'alert-danger' ?>">
                <?= $enabledDeliveryCount > 0 ? number_format($enabledDeliveryCount) . ' shipping/collection method(s) currently enabled for customers.' : 'No delivery method is enabled. Customers will not be able to choose delivery until one is enabled.' ?>
            </div>
            <div class="shipping-field-grid mb-4">
                <div class="form-group mb-0">
                    <label for="default_unit_weight_g">Default weight for unit/pc/ml/lt items</label>
                    <div class="input-group">
                        <input type="number" step="1" min="1" class="form-control" id="default_unit_weight_g" name="default_unit_weight_g" value="<?= cbWebsiteSettingsText(number_format($defaultUnitWeightGrams, 0, '.', '')) ?>">
                        <div class="input-group-append"><span class="input-group-text">grams</span></div>
                    </div>
                    <small class="form-text text-muted">Use a dot for decimals if needed. This is only a fallback when the sheet has no shipping_weight and the visible size is unit/pc/ml/lt. Example: 250 means 250 grams.</small>
                </div>
                <div class="form-group mb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="google_maps_api_key" class="mb-1">Google Maps API Key</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="shippingHelpToggle">Help mode</button>
                    </div>
                    <input type="text" class="form-control" id="google_maps_api_key" name="google_maps_api_key" value="<?= cbWebsiteSettingsText($row['google_maps_api_key'] ?? '') ?>" autocomplete="off">
                    <small class="form-text text-muted">Used for checkout address autocomplete. Leave blank to keep manual address typing only.</small>
                    <div class="shipping-help-panel" id="shippingHelpPanel">
                        <strong>Google Maps help</strong>
                        <ul>
                            <li>Get the key from Google Cloud Console by enabling the Maps JavaScript API and Places API.</li>
                            <li>Add your website domains as allowed referrers so the key cannot be freely used elsewhere.</li>
                            <li>This helps customers choose cleaner delivery addresses, suburbs and postal details at checkout.</li>
                            <li>If the key is empty or restricted incorrectly, checkout still allows manual address typing.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php foreach ($deliveryOptions as $methodKey => $method): ?>
                    <div class="col-md-6">
                        <div class="shipping-method-card">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input shipping-method-enabled" id="shipping_enabled_<?= cbWebsiteSettingsText($methodKey) ?>" name="shipping_enabled[<?= cbWebsiteSettingsText($methodKey) ?>]" value="1" <?= !empty($method['enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label font-weight-bold" for="shipping_enabled_<?= cbWebsiteSettingsText($methodKey) ?>"><?= cbWebsiteSettingsText($method['label']) ?> is visible to customers</label>
                            </div>
                            <?php if ($methodKey !== 'collect'): ?>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="shipping_free_<?= cbWebsiteSettingsText($methodKey) ?>" name="shipping_free_eligible[<?= cbWebsiteSettingsText($methodKey) ?>]" value="1" <?= !empty($method['free_shipping_eligible']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="shipping_free_<?= cbWebsiteSettingsText($methodKey) ?>">Free shipping threshold can apply to this method</label>
                                </div>
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="shipping_estimate_<?= cbWebsiteSettingsText($methodKey) ?>">Customer delivery estimate</label>
                                <input type="text" class="form-control" id="shipping_estimate_<?= cbWebsiteSettingsText($methodKey) ?>" name="shipping_estimate[<?= cbWebsiteSettingsText($methodKey) ?>]" value="<?= cbWebsiteSettingsText($method['estimate'] ?? '') ?>" placeholder="e.g. 2-5 working days after dispatch">
                            </div>
                            <?php if ($methodKey === 'collect'): ?>
                                <div class="form-group">
                                    <label for="collection_address">Collection address shown to customers</label>
                                    <textarea class="form-control" id="collection_address" name="collection_address" rows="3"><?= cbWebsiteSettingsText($method['collection_address'] ?? '') ?></textarea>
                                </div>
                            <?php else: ?>
                                <?php foreach ($method['tiers'] as $tierKey => $tier): ?>
                                    <div class="form-group">
                                        <label for="shipping_<?= cbWebsiteSettingsText($tierKey) ?>"><?= cbWebsiteSettingsText($tier['label']) ?></label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="shipping_<?= cbWebsiteSettingsText($tierKey) ?>" name="shipping_rates[<?= cbWebsiteSettingsText($methodKey) ?>][tiers][<?= cbWebsiteSettingsText($tierKey) ?>][price]" value="<?= cbWebsiteSettingsText(number_format((float)$tier['price'], 2, '.', '')) ?>">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-group mt-3">
                <label for="shipping_extra_tiers">Extra shipping tiers over 20kg</label>
                <textarea class="form-control" id="shipping_extra_tiers" name="shipping_extra_tiers" rows="4" placeholder="locker,50,500,Up to 50kg&#10;door,1000,2500,Up to 1 ton"><?= cbWebsiteSettingsText($extraTierText) ?></textarea>
                <small class="form-text text-muted">One tier per line: method, max kg, price, label. Method must be locker or door. These tiers are used before the final over-20kg flat rate.</small>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save Shipping Settings</button>
    </form>
</div>

<?php cbWebsiteSettingsSaveScript(true); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var helpButton = document.getElementById('shippingHelpToggle');
    var helpPanel = document.getElementById('shippingHelpPanel');
    if (helpButton && helpPanel) {
        helpButton.addEventListener('click', function() {
            helpPanel.classList.toggle('is-visible');
            helpButton.textContent = helpPanel.classList.contains('is-visible') ? 'Hide help' : 'Help mode';
        });
    }
});
</script>
<?php include 'footer.php'; ?>
