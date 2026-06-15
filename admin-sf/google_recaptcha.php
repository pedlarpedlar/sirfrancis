<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("google_recaptcha"));
    exit();
}

include 'header.php';
include 'dbh.inc.php';
require_once __DIR__ . '/website_settings_helpers.php';

$row = cbWebsiteSettingsLoad($conn);
$settingsFlash = cbWebsiteSettingsFlash();
$recaptchaType = $row['contact_recaptcha_type'] ?? 'v3';

include 'page_menues.php';
?>

<title>Google reCAPTCHA Settings</title>

<div class="container mt-5 mb-5">
    <h2>Google reCAPTCHA</h2>
    <p class="text-muted">Control contact form spam protection separately from contact details and shipping settings.</p>
    <?php cbWebsiteSettingsAlert($settingsFlash); ?>
    <form id="website-settings-form" action="update_settings.php?ajax=1&section=recaptcha" method="post">
        <input type="hidden" name="settings_section" value="recaptcha">
        <div class="border rounded p-3 mb-4">
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="contact_recaptcha_enabled" name="contact_recaptcha_enabled" value="1" <?= !empty($row['contact_recaptcha_enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label font-weight-bold" for="contact_recaptcha_enabled">Enable Google reCAPTCHA on the contact form</label>
            </div>
            <div class="form-group">
                <label for="contact_recaptcha_type">reCAPTCHA Key Type</label>
                <select class="form-control" id="contact_recaptcha_type" name="contact_recaptcha_type">
                    <option value="v3" <?= $recaptchaType === 'v3' ? 'selected' : '' ?>>reCAPTCHA v3 score-based key</option>
                    <option value="v2_checkbox" <?= $recaptchaType === 'v2_checkbox' ? 'selected' : '' ?>>reCAPTCHA v2 checkbox key</option>
                </select>
                <small class="form-text text-muted">Choose the same type you selected in Google. "Invalid key type" means this dropdown does not match the Google key.</small>
            </div>
            <div class="form-group">
                <label for="contact_recaptcha_site_key">reCAPTCHA Site Key</label>
                <input type="text" class="form-control" id="contact_recaptcha_site_key" name="contact_recaptcha_site_key" value="<?= cbWebsiteSettingsText($row['contact_recaptcha_site_key'] ?? '') ?>" autocomplete="off">
            </div>
            <div class="form-group mb-0">
                <label for="contact_recaptcha_secret_key">reCAPTCHA Secret Key</label>
                <input type="password" class="form-control" id="contact_recaptcha_secret_key" name="contact_recaptcha_secret_key" value="<?= cbWebsiteSettingsText($row['contact_recaptcha_secret_key'] ?? '') ?>" autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save reCAPTCHA Settings</button>
    </form>
</div>

<?php cbWebsiteSettingsSaveScript(false); ?>
<?php include 'footer.php'; ?>
