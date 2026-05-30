<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("manage_website_information"));
    exit();
}

include 'header.php';
include 'dbh.inc.php';
require_once __DIR__ . '/website_settings_helpers.php';

$row = cbWebsiteSettingsLoad($conn);
$settingsFlash = cbWebsiteSettingsFlash();

include 'page_menues.php';
?>

<title>Contact Info Settings</title>

<div class="container mt-5 mb-5">
    <h2>Contact Info</h2>
    <p class="text-muted">Manage the public company details used around the website, footer, contact page, policies and emails.</p>
    <?php cbWebsiteSettingsAlert($settingsFlash); ?>
    <form id="website-settings-form" action="update_settings.php?ajax=1&section=contact" method="post">
        <input type="hidden" name="settings_section" value="contact">
        <div class="form-group">
            <label for="tel">Telephone</label>
            <input type="text" class="form-control" id="tel" name="tel" value="<?= cbWebsiteSettingsText($row['tel'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="hotline">Mobile / WhatsApp Number</label>
            <input type="text" class="form-control" id="hotline" name="hotline" value="<?= cbWebsiteSettingsText($row['hotline'] ?? '') ?>">
            <small class="form-text text-muted">Used for the WhatsApp link in the footer. If blank, the telephone number is used.</small>
        </div>
        <div class="form-group">
            <label for="email_1">Email 1</label>
            <input type="email" class="form-control" id="email_1" name="email_1" value="<?= cbWebsiteSettingsText($row['email_1'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="email_2">Email 2</label>
            <input type="email" class="form-control" id="email_2" name="email_2" value="<?= cbWebsiteSettingsText($row['email_2'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="address">Operational Address</label>
            <input type="text" class="form-control" id="address" name="address" value="<?= cbWebsiteSettingsText($row['address'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="headquarters">Head Office Address</label>
            <input type="text" class="form-control" id="headquarters" name="headquarters" value="<?= cbWebsiteSettingsText($row['headquarters'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="support_email">Support Email</label>
            <input type="email" class="form-control" id="support_email" name="support_email" value="<?= cbWebsiteSettingsText($row['support_email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="website_company_name">Website Company Name</label>
            <input type="text" class="form-control" id="website_company_name" name="website_company_name" value="<?= cbWebsiteSettingsText($row['website_company_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="banking_details">Banking Details</label>
            <textarea class="form-control" id="banking_details" name="banking_details" rows="4"><?= cbWebsiteSettingsText($row['banking_details'] ?? '') ?></textarea>
            <small class="form-text text-muted">Used for EFT payment communication.</small>
        </div>
        <button type="submit" class="btn btn-primary">Save Contact Info</button>
    </form>
</div>

<?php cbWebsiteSettingsSaveScript(false); ?>
<?php include 'footer.php'; ?>
