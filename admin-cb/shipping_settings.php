<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("shipping_settings"));
    exit();
}

include 'header.php';
include 'dbh.inc.php';
require_once __DIR__ . '/website_settings_helpers.php';

$row = cbWebsiteSettingsLoad($conn);
$settingsFlash = cbWebsiteSettingsFlash();
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

include 'page_menues.php';
?>

<title>Shipping Settings</title>

<div class="container mt-5 mb-5">
    <h2>Shipping</h2>
    <p class="text-muted">Manage delivery methods, delivery estimates, free shipping threshold and shipping weights from one place.</p>
    <?php cbWebsiteSettingsAlert($settingsFlash); ?>
    <form id="website-settings-form" action="update_settings.php?ajax=1&section=shipping" method="post">
        <input type="hidden" name="settings_section" value="shipping">
        <div class="border rounded p-3 mb-4">
            <h4 class="mb-3">Free Shipping</h4>
            <div class="form-group">
                <label for="free_shipping_amount">Free Shipping Threshold</label>
                <input type="number" step="0.01" class="form-control" id="free_shipping_amount" name="free_shipping_amount" value="<?= cbWebsiteSettingsText($row['free_shipping_amount'] ?? '') ?>">
                <small class="form-text text-muted">Calculated after product discounts and coupons. The delivery method must also be marked as eligible below.</small>
            </div>
        </div>

        <div class="border rounded p-3 mb-4">
            <h4 class="mb-3">Shipping Prices</h4>
            <p class="text-muted mb-3">These values feed cart, checkout, admin edit order, emails, and PayFast totals. At least one customer-facing method must be enabled.</p>
            <div class="alert <?= $enabledDeliveryCount > 0 ? 'alert-success' : 'alert-danger' ?>">
                <?= $enabledDeliveryCount > 0 ? number_format($enabledDeliveryCount) . ' shipping/collection method(s) currently enabled for customers.' : 'No delivery method is enabled. Customers will not be able to choose delivery until one is enabled.' ?>
            </div>
            <div class="form-group">
                <label for="default_unit_weight_kg">Default weight for unit/pc/ml/lt items</label>
                <input type="number" step="0.001" min="0.001" class="form-control" id="default_unit_weight_kg" name="default_unit_weight_kg" value="<?= cbWebsiteSettingsText(number_format((float)($row['default_unit_weight_kg'] ?? 0.25), 3, '.', '')) ?>">
                <small class="form-text text-muted">Used when a product size is not in grams/kg and no shipping_weight is provided. 0.250 means 250g.</small>
            </div>
            <div class="form-group">
                <label for="google_maps_api_key">Google Maps API Key</label>
                <input type="text" class="form-control" id="google_maps_api_key" name="google_maps_api_key" value="<?= cbWebsiteSettingsText($row['google_maps_api_key'] ?? '') ?>" autocomplete="off">
                <small class="form-text text-muted">Used for checkout address autocomplete. Leave blank to keep manual address typing only.</small>
            </div>
            <div class="row">
                <?php foreach ($deliveryOptions as $methodKey => $method): ?>
                    <div class="col-md-6">
                        <div class="border rounded p-3 mb-3">
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
<?php include 'footer.php'; ?>
