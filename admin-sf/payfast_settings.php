<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("payfast_settings"));
    exit();
}

include 'header.php';
include 'dbh.inc.php';
require_once __DIR__ . '/website_settings_helpers.php';
require_once __DIR__ . '/../payfast_settings_helpers.php';

$row = cbWebsiteSettingsLoad($conn);
$payfastSettings = sfPayfastSettings($conn);
$settingsFlash = cbWebsiteSettingsFlash();

include 'page_menues.php';
?>

<title>PayFast Settings</title>

<style>
    .payfast-settings-shell { padding-top:28px; padding-bottom:60px; }
    .payfast-settings-card { background:#fff; border:1px solid #e8ded2; border-radius:0; padding:22px; margin-bottom:24px; box-shadow:0 8px 24px rgba(23, 34, 53, .05); }
    .payfast-settings-card h4 { color:#172235; margin-bottom:14px; }
    .payfast-settings-card label { color:#172235; font-weight:800; }
    .payfast-help { background:#f8f5ee; border:1px solid #d8c895; color:#574f45; line-height:1.55; padding:16px; }
    .payfast-help ul { margin-bottom:0; padding-left:20px; }
    .payfast-help li { margin-bottom:8px; }
    .payfast-settings-actions .btn { border-radius:0; }
</style>

<div class="container payfast-settings-shell">
    <h2>PayFast Settings</h2>
    <p class="text-muted">Connect Sir Francis checkout to your PayFast merchant account.</p>
    <?php cbWebsiteSettingsAlert($settingsFlash); ?>

    <form id="website-settings-form" action="update_settings.php?ajax=1&section=payfast" method="post">
        <input type="hidden" name="settings_section" value="payfast">

        <div class="payfast-settings-card">
            <h4>Merchant Credentials</h4>
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="payfast_enabled" name="payfast_enabled" value="1" <?= !empty($payfastSettings['payfast_enabled']) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="payfast_enabled">Enable PayFast on checkout</label>
            </div>
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="payfast_sandbox" name="payfast_sandbox" value="1" <?= !empty($payfastSettings['payfast_sandbox']) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="payfast_sandbox">Use PayFast sandbox/testing mode</label>
            </div>
            <div class="form-group">
                <label for="payfast_merchant_id">Merchant ID</label>
                <input type="text" class="form-control" id="payfast_merchant_id" name="payfast_merchant_id" value="<?= cbWebsiteSettingsText($payfastSettings['payfast_merchant_id'] ?? '') ?>" autocomplete="off">
                <small class="form-text text-muted">Found in your PayFast dashboard under Integration or Merchant Details.</small>
            </div>
            <div class="form-group">
                <label for="payfast_merchant_key">Merchant Key</label>
                <input type="text" class="form-control" id="payfast_merchant_key" name="payfast_merchant_key" value="<?= cbWebsiteSettingsText($payfastSettings['payfast_merchant_key'] ?? '') ?>" autocomplete="off">
                <small class="form-text text-muted">This is not your login password. It is the merchant key PayFast gives for website integration.</small>
            </div>
            <div class="form-group mb-0">
                <label for="payfast_passphrase">Passphrase</label>
                <input type="password" class="form-control" id="payfast_passphrase" name="payfast_passphrase" value="<?= cbWebsiteSettingsText($payfastSettings['payfast_passphrase'] ?? '') ?>" autocomplete="new-password">
                <small class="form-text text-muted">Use the exact passphrase configured in PayFast. Leave blank only if your PayFast account has no passphrase set.</small>
            </div>
        </div>

        <div class="payfast-settings-card">
            <h4>PayFast Dashboard URLs</h4>
            <div class="payfast-help">
                <ul>
                    <li><strong>Return URL:</strong> https://www.sirfrancis.co.za/order_details</li>
                    <li><strong>Cancel URL:</strong> https://www.sirfrancis.co.za/order_details</li>
                    <li><strong>Notify URL / ITN URL:</strong> https://www.sirfrancis.co.za/notify</li>
                    <li>For live payments, turn sandbox/testing mode off here and use your live PayFast merchant ID/key.</li>
                    <li>For testing, leave sandbox mode on and use PayFast sandbox credentials.</li>
                </ul>
            </div>
        </div>

        <div class="payfast-settings-actions">
            <button type="submit" class="btn btn-primary">Save PayFast Settings</button>
        </div>
    </form>
</div>

<?php cbWebsiteSettingsSaveScript(false); ?>
<?php include '../footer.php'; ?>
