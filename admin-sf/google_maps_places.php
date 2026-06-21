<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("google_maps_places"));
    exit();
}

include 'header.php';
include 'dbh.inc.php';
require_once __DIR__ . '/website_settings_helpers.php';
require_once __DIR__ . '/../google_integrations_helpers.php';

$row = cbWebsiteSettingsLoad($conn);
$googleDefaults = sfGoogleIntegrationSettings($conn);
$settingsFlash = cbWebsiteSettingsFlash();

include 'page_menues.php';
?>

<title>Google Maps & Places Settings</title>

<style>
    .maps-settings-shell { padding-top:28px; padding-bottom:60px; }
    .maps-settings-card { background:#fff; border:1px solid #e8ded2; border-radius:0; padding:22px; margin-bottom:24px; box-shadow:0 8px 24px rgba(23, 34, 53, .05); }
    .maps-settings-card h4 { color:#172235; margin-bottom:14px; }
    .maps-settings-card label { color:#172235; font-weight:800; }
    .maps-help { background:#f8f5ee; border:1px solid #d8c895; color:#574f45; line-height:1.55; padding:16px; }
    .maps-help ul { margin-bottom:0; padding-left:20px; }
    .maps-help li { margin-bottom:8px; }
    .maps-settings-actions .btn { border-radius:0; }
</style>

<div class="container maps-settings-shell">
    <h2>Google Maps & Places</h2>
    <p class="text-muted">Store the API keys used for live maps, agent maps and address autocomplete.</p>
    <?php cbWebsiteSettingsAlert($settingsFlash); ?>

    <form id="website-settings-form" action="update_settings.php?ajax=1&section=maps" method="post">
        <input type="hidden" name="settings_section" value="maps">

        <div class="maps-settings-card">
            <h4>API Keys</h4>
            <div class="form-group">
                <label for="google_maps_api_key">Google Maps JavaScript API Key</label>
                <input type="text" class="form-control" id="google_maps_api_key" name="google_maps_api_key" value="<?= cbWebsiteSettingsText(($row['google_maps_api_key'] ?? '') ?: ($googleDefaults['google_maps_api_key'] ?? '')) ?>" autocomplete="off">
                <small class="form-text text-muted">Used for live Google Maps displays such as the Find an Agent map.</small>
            </div>
            <div class="form-group mb-0">
                <label for="google_places_api_key">Google Places API Key</label>
                <input type="text" class="form-control" id="google_places_api_key" name="google_places_api_key" value="<?= cbWebsiteSettingsText(($row['google_places_api_key'] ?? '') ?: ($googleDefaults['google_places_api_key'] ?? '')) ?>" autocomplete="off">
                <small class="form-text text-muted">Used for address autocomplete on checkout and admin order creation. If left blank, the Maps key will be used as the fallback.</small>
            </div>
        </div>

        <div class="maps-settings-card">
            <h4>Google Reviews & Customer Badge</h4>
            <div class="form-group">
                <label for="google_business_place_id">Sir Francis Google Business Place ID</label>
                <input type="text" class="form-control" id="google_business_place_id" name="google_business_place_id" value="<?= cbWebsiteSettingsText(($row['google_business_place_id'] ?? '') ?: ($googleDefaults['google_business_place_id'] ?? '')) ?>" autocomplete="off">
                <small class="form-text text-muted">Used to show Sir Francis Google reviews on the homepage and about page. Get this from Google's Place ID Finder for the Sir Francis Business Profile.</small>
            </div>
            <div class="form-group mb-0">
                <label for="google_customer_reviews_merchant_id">Google Customer Reviews Merchant ID</label>
                <input type="text" class="form-control" id="google_customer_reviews_merchant_id" name="google_customer_reviews_merchant_id" value="<?= cbWebsiteSettingsText(($row['google_customer_reviews_merchant_id'] ?? '') ?: ($googleDefaults['google_customer_reviews_merchant_id'] ?? '')) ?>" autocomplete="off">
                <small class="form-text text-muted">Used for the Google Customer Reviews badge and post-order opt-in. This is your numeric Google Merchant Center ID.</small>
            </div>
        </div>

        <div class="maps-settings-card">
            <h4>Setup Notes</h4>
            <div class="maps-help">
                <ul>
                    <li>In Google Cloud Console, enable Maps JavaScript API for the live map.</li>
                    <li>Enable Places API for address autocomplete and place lookups.</li>
                    <li>Restrict the key to your website domains, for example sirfrancis.co.za and www.sirfrancis.co.za.</li>
                    <li>If you use one key for both, paste the same key into both fields.</li>
                    <li>Google reviews need the Sir Francis Business Profile Place ID. Google Customer Reviews need an approved Merchant Center account.</li>
                    <li>If a key is missing or restricted incorrectly, the site will keep manual address entry and static map fallbacks available.</li>
                </ul>
            </div>
        </div>

        <div class="maps-settings-actions">
            <button type="submit" class="btn btn-primary">Save Google Settings</button>
        </div>
    </form>
</div>

<?php cbWebsiteSettingsSaveScript(false); ?>
<?php include 'footer.php'; ?>
