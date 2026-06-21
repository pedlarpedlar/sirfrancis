<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("editor_settings"));
    exit();
}

include __DIR__ . '/header.php';
include __DIR__ . '/dbh.inc.php';
require_once __DIR__ . '/website_settings_helpers.php';

$row = cbWebsiteSettingsLoad($conn);
$settingsFlash = cbWebsiteSettingsFlash();
$tinymceApiKey = trim((string) ($row['tinymce_api_key'] ?? ''));
if ($tinymceApiKey === '') {
    $tinymceApiKey = SF_DEFAULT_TINYMCE_API_KEY;
}

include __DIR__ . '/page_menues.php';
?>

<title>Editor Settings - Sir Francis Admin</title>

<style>
    .editor-settings-shell { padding: 30px 0 70px; }
    .editor-settings-hero { background: var(--sf-navy); color: #fff; border-radius: 8px; margin-bottom: 18px; padding: 22px; }
    .editor-settings-hero h1 { color: var(--sf-gold); margin-bottom: 6px; }
    .editor-settings-panel { background: #fff; border: 1px solid var(--sf-border); border-radius: 8px; padding: 20px; }
    .editor-settings-panel h2 { color: var(--sf-navy); font-size: 21px; margin-bottom: 10px; }
    .editor-settings-note { background: #fffaf2; border: 1px solid var(--sf-border); color: #344154; font-size: 13px; line-height: 1.6; margin-bottom: 18px; padding: 12px 14px; }
    .editor-settings-note strong { color: var(--sf-navy); }
</style>

<div class="container editor-settings-shell">
    <div class="editor-settings-hero">
        <h1>Editor Settings</h1>
        <p class="mb-0">Manage third-party editor tools used by admin content fields.</p>
    </div>

    <?php cbWebsiteSettingsAlert($settingsFlash); ?>

    <div class="editor-settings-panel">
        <h2>TinyMCE API Key</h2>
        <div class="editor-settings-note">
            <p><strong>What this is used for:</strong> TinyMCE powers rich text editing in admin areas where staff write formatted content, such as product descriptions, policy copy, campaign text or future page sections.</p>
            <p class="mb-0">The API key identifies this website to TinyMCE Cloud. If the key is blank or invalid, rich text fields may fall back to plain text or show TinyMCE warnings.</p>
        </div>

        <form id="website-settings-form" action="update_settings.php?ajax=1&section=editor" method="post">
            <input type="hidden" name="settings_section" value="editor">
            <div class="form-group">
                <label for="tinymce_api_key">TinyMCE API Key</label>
                <input type="text" class="form-control" id="tinymce_api_key" name="tinymce_api_key" value="<?= cbWebsiteSettingsText($tinymceApiKey) ?>" autocomplete="off">
                <small class="form-text text-muted">Keep this synced with the API key in your TinyMCE account. Do not paste unrelated Google, Maps or payment keys here.</small>
            </div>
            <button type="submit" class="btn btn-primary">Save Editor Settings</button>
        </form>
    </div>
</div>

<?php cbWebsiteSettingsSaveScript(false); ?>
<?php include __DIR__ . '/../footer.php'; ?>
