<?php
include '../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode('social_accounts'));
    exit();
}

include 'dbh.inc.php';
require_once 'business_ops_helpers.php';

cbOpsEnsureTables($conn);

$flash = null;

function cbSocialFetch($conn, $id) {
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM admin_social_accounts WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? 'save_account';
        if ($action === 'save_settings') {
            $email = trim((string) ($_POST['recipient_email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Add a valid reminder email address.');
            }
            $day = cbOpsCleanHeader($_POST['reminder_day'] ?? 'Monday');
            $time = cbOpsCleanHeader($_POST['reminder_time'] ?? '08:00');
            $subject = cbOpsCleanHeader($_POST['subject'] ?? 'Sir Francis social posting reminder');
            $enabled = !empty($_POST['enabled']) ? 1 : 0;

            $conn->query("INSERT IGNORE INTO admin_social_reminder_settings (id, recipient_email, reminder_day, reminder_time, subject, enabled, updated_at) VALUES (1, '', 'Monday', '08:00:00', 'Sir Francis social posting reminder', 1, NOW())");
            $stmt = $conn->prepare("UPDATE admin_social_reminder_settings SET recipient_email = ?, reminder_day = ?, reminder_time = ?, subject = ?, enabled = ?, updated_at = NOW() WHERE id = 1");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('ssssi', $email, $day, $time, $subject, $enabled);
            $stmt->execute();
            $stmt->close();
            $flash = ['success' => true, 'message' => 'Reminder settings saved.'];
        } elseif ($action === 'delete_account') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM admin_social_accounts WHERE id = ?");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $flash = ['success' => true, 'message' => 'Social account deleted.'];
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $platform = cbOpsCleanHeader($_POST['platform'] ?? '');
            $handle = cbOpsCleanHeader($_POST['handle'] ?? '');
            $profileUrl = trim((string) ($_POST['profile_url'] ?? ''));
            $loginEmail = cbOpsCleanHeader($_POST['login_email'] ?? '');
            $loginUsername = cbOpsCleanHeader($_POST['login_username'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $notes = trim((string) ($_POST['notes'] ?? ''));
            $isActive = !empty($_POST['is_active']) ? 1 : 0;
            $mostActive = !empty($_POST['most_active']) ? 1 : 0;
            $frequency = ($_POST['reminder_frequency'] ?? 'weekly') === 'daily' ? 'daily' : 'weekly';
            $lastPostedAt = trim((string) ($_POST['last_posted_at'] ?? ''));
            $lastPostedAt = $lastPostedAt !== '' ? date('Y-m-d H:i:s', strtotime($lastPostedAt)) : null;

            if ($platform === '') {
                throw new Exception('Add a platform name.');
            }
            if ($profileUrl !== '' && !filter_var($profileUrl, FILTER_VALIDATE_URL)) {
                throw new Exception('Profile link must start with https://.');
            }

            if ($id > 0) {
                $existing = cbSocialFetch($conn, $id);
                $storedPassword = $password !== '' ? cbOpsEncryptSecret($password) : ($existing['encrypted_password'] ?? '');
                $stmt = $conn->prepare("UPDATE admin_social_accounts SET platform = ?, handle = ?, profile_url = ?, login_email = ?, login_username = ?, encrypted_password = ?, notes = ?, is_active = ?, most_active = ?, reminder_frequency = ?, last_posted_at = ?, updated_at = NOW() WHERE id = ?");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param('sssssssiissi', $platform, $handle, $profileUrl, $loginEmail, $loginUsername, $storedPassword, $notes, $isActive, $mostActive, $frequency, $lastPostedAt, $id);
                $stmt->execute();
                $stmt->close();
                $flash = ['success' => true, 'message' => 'Social account updated.'];
            } else {
                $storedPassword = $password !== '' ? cbOpsEncryptSecret($password) : '';
                $stmt = $conn->prepare("INSERT INTO admin_social_accounts (platform, handle, profile_url, login_email, login_username, encrypted_password, notes, is_active, most_active, reminder_frequency, last_posted_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param('sssssssiiss', $platform, $handle, $profileUrl, $loginEmail, $loginUsername, $storedPassword, $notes, $isActive, $mostActive, $frequency, $lastPostedAt);
                $stmt->execute();
                $stmt->close();
                $flash = ['success' => true, 'message' => 'Social account saved.'];
            }
        }
    } catch (Exception $e) {
        $flash = ['success' => false, 'message' => $e->getMessage()];
    }
}

$editing = cbSocialFetch($conn, $_GET['edit'] ?? 0);
$settingsRows = cbOpsRows($conn, "SELECT * FROM admin_social_reminder_settings WHERE id = 1 LIMIT 1");
$settings = $settingsRows[0] ?? [];
$accounts = cbOpsRows($conn, "SELECT * FROM admin_social_accounts ORDER BY most_active DESC, is_active DESC, platform ASC");

include 'header.php';
include 'page_menues.php';
?>

<title>Social Accounts & Reminders - Sir Francis Admin</title>

<style>
  .ops-wrap { padding: 28px 0 70px; }
  .ops-hero { background:#2d1739; color:#fff; border-radius:8px; padding:22px; margin-bottom:16px; }
  .ops-hero h1 { color:#CEBD88; font-size:28px; margin:0 0 6px; }
  .ops-panel { background:#fff; border:1px solid #eadfd2; border-radius:8px; padding:18px; height:auto !important; min-height:0; overflow:visible; }
  .ops-panel h2, .ops-panel h3 { color:#28364B; }
  .field-help { color:#6d6270; font-size:12px; margin-top:4px; }
  .ops-table th { background:#f0e8f4; color:#4b185f; font-size:12px; text-transform:uppercase; white-space:nowrap; }
  .ops-table td { vertical-align:top; }
  .password-chip { background:#fbfaf7; border:1px solid #eadfd2; border-radius:6px; display:inline-block; font-family:monospace; padding:4px 7px; }
  .button-row { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
  .social-admin-grid { align-items:start; display:grid; gap:18px; grid-template-columns:minmax(320px, 460px) minmax(0, 1fr); }
  .social-admin-side { align-content:start; display:grid; gap:18px; min-width:0; }
  .social-accounts-list { min-width:0; }
  .social-quick-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
  @media (max-width: 991px) {
    .social-admin-grid { grid-template-columns:1fr; }
  }
</style>

<div class="container ops-wrap">
  <div class="ops-hero">
    <h1>Social Accounts & Posting Reminder</h1>
    <p class="mb-0">Keep handles, login details and posting reminders in one admin-only place. Most active platforms can be marked for daily posting reminders.</p>
    <div class="social-quick-actions">
      <a class="btn btn-warning btn-sm" href="#social-account-form">Add social account</a>
      <a class="btn btn-outline-light btn-sm" href="#saved-social-accounts">View saved accounts</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert <?= !empty($flash['success']) ? 'alert-success' : 'alert-danger' ?>"><?= cbOpsText($flash['message']) ?></div>
  <?php endif; ?>

  <div class="social-admin-grid">
    <div class="social-admin-side">
      <div class="ops-panel mb-4">
        <h2>Reminder Email</h2>
        <form method="post">
          <input type="hidden" name="action" value="save_settings">
          <div class="form-group">
            <label>Send reminder to</label>
            <input type="email" class="form-control" name="recipient_email" value="<?= cbOpsText($settings['recipient_email'] ?? ($smtp_username1 ?? '')) ?>" required>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Weekly day</label>
              <select class="form-control" name="reminder_day">
                <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
                  <option value="<?= $day ?>" <?= (($settings['reminder_day'] ?? 'Monday') === $day) ? 'selected' : '' ?>><?= $day ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label>Send time</label>
              <input type="time" class="form-control" name="reminder_time" value="<?= cbOpsText(substr((string) ($settings['reminder_time'] ?? '08:00'), 0, 5)) ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Subject</label>
            <input type="text" class="form-control" name="subject" value="<?= cbOpsText($settings['subject'] ?? 'Sir Francis social posting reminder') ?>">
          </div>
          <div class="custom-control custom-checkbox mb-3">
            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" <?= !isset($settings['enabled']) || (int) $settings['enabled'] === 1 ? 'checked' : '' ?>>
            <label class="custom-control-label" for="enabled">Enable cron reminder email</label>
          </div>
          <button class="btn btn-primary" type="submit">Save reminder settings</button>
        </form>
      </div>

      <div class="ops-panel" id="social-account-form">
        <h2><?= $editing ? 'Edit Social Account' : 'Add Social Account' ?></h2>
        <form method="post">
          <input type="hidden" name="action" value="save_account">
          <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
          <div class="form-group">
            <label>Platform</label>
            <input type="text" class="form-control" name="platform" value="<?= cbOpsText($editing['platform'] ?? '') ?>" placeholder="Instagram, Facebook, TikTok, LinkedIn" required>
          </div>
          <div class="form-group">
            <label>Handle</label>
            <input type="text" class="form-control" name="handle" value="<?= cbOpsText($editing['handle'] ?? '') ?>" placeholder="@sirfrancis">
          </div>
          <div class="form-group">
            <label>Profile link</label>
            <input type="url" class="form-control" name="profile_url" value="<?= cbOpsText($editing['profile_url'] ?? '') ?>" placeholder="https://...">
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Login email</label>
              <input type="text" class="form-control" name="login_email" value="<?= cbOpsText($editing['login_email'] ?? '') ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Username</label>
              <input type="text" class="form-control" name="login_username" value="<?= cbOpsText($editing['login_username'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" class="form-control" name="password" value="" placeholder="<?= $editing ? 'Leave blank to keep saved password' : '' ?>">
            <div class="field-help">Passwords are stored encrypted where server support allows it, and hidden by default on this page.</div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Reminder type</label>
              <select class="form-control" name="reminder_frequency">
                <option value="weekly" <?= (($editing['reminder_frequency'] ?? 'weekly') === 'weekly') ? 'selected' : '' ?>>Weekly</option>
                <option value="daily" <?= (($editing['reminder_frequency'] ?? '') === 'daily') ? 'selected' : '' ?>>Daily</option>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label>Last posted</label>
              <input type="datetime-local" class="form-control" name="last_posted_at" value="<?= !empty($editing['last_posted_at']) ? cbOpsText(date('Y-m-d\TH:i', strtotime($editing['last_posted_at']))) : '' ?>">
            </div>
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" <?= !$editing || (int) ($editing['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
            <label class="custom-control-label" for="is_active">Active account</label>
          </div>
          <div class="custom-control custom-checkbox mb-3">
            <input type="checkbox" class="custom-control-input" id="most_active" name="most_active" value="1" <?= (int) ($editing['most_active'] ?? 0) === 1 ? 'checked' : '' ?>>
            <label class="custom-control-label" for="most_active">Most active platform, remind daily</label>
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea class="form-control" name="notes" rows="4"><?= cbOpsText($editing['notes'] ?? '') ?></textarea>
          </div>
          <div class="button-row">
            <button class="btn btn-primary" type="submit"><?= $editing ? 'Update account' : 'Save account' ?></button>
            <?php if ($editing): ?><a href="social_accounts" class="btn btn-link">Start new</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="social-accounts-list">
      <div class="ops-panel" id="saved-social-accounts">
        <h3>Saved Social Accounts</h3>
        <div class="table-responsive">
          <table class="table table-hover ops-table">
            <thead><tr><th>Platform</th><th>Login</th><th>Reminder</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if (empty($accounts)): ?>
                <tr><td colspan="4" class="text-center py-4">No social accounts saved yet.</td></tr>
              <?php else: foreach ($accounts as $account): ?>
                <?php $password = cbOpsDecryptSecret($account['encrypted_password'] ?? ''); ?>
                <tr>
                  <td>
                    <strong><?= cbOpsText($account['platform']) ?></strong>
                    <?php if (!empty($account['handle'])): ?><div><?= cbOpsText($account['handle']) ?></div><?php endif; ?>
                    <?php if (!empty($account['profile_url'])): ?><a href="<?= cbOpsText($account['profile_url']) ?>" target="_blank" rel="noopener noreferrer">Open profile</a><?php endif; ?>
                    <div class="field-help"><?= (int) $account['is_active'] === 1 ? 'Active' : 'Inactive' ?><?= (int) $account['most_active'] === 1 ? ' | Most active' : '' ?></div>
                  </td>
                  <td>
                    <?php if (!empty($account['login_email'])): ?><div>Email: <?= cbOpsText($account['login_email']) ?></div><?php endif; ?>
                    <?php if (!empty($account['login_username'])): ?><div>User: <?= cbOpsText($account['login_username']) ?></div><?php endif; ?>
                    <?php if ($password !== ''): ?>
                      <details><summary>Password</summary><span class="password-chip"><?= cbOpsText($password) ?></span></details>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= cbOpsText(ucfirst($account['reminder_frequency'] ?? 'weekly')) ?>
                    <?php if (!empty($account['last_posted_at'])): ?><div class="field-help">Last posted <?= cbOpsText($account['last_posted_at']) ?></div><?php endif; ?>
                  </td>
                  <td>
                    <div class="button-row">
                      <a class="btn btn-sm btn-outline-primary" href="social_accounts?edit=<?= (int) $account['id'] ?>">Edit</a>
                      <form method="post" onsubmit="return confirm('Delete this social account?');">
                        <input type="hidden" name="action" value="delete_account">
                        <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>
