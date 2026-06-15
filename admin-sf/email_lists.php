<?php
include '../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "email_lists";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';
require_once 'campaign_email_helpers.php';

cbCampaignEnsureTables($conn);

function cbEmailListText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbEmailListFetch($conn, $id) {
    if (!($conn instanceof mysqli) || (int) $id <= 0) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM email_recipient_lists WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $id = (int) $id;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function cbEmailListRows($conn) {
    $rows = [];
    if (!($conn instanceof mysqli)) {
        return $rows;
    }
    $result = $conn->query("SELECT * FROM email_recipient_lists ORDER BY updated_at DESC, title ASC");
    if (!$result) {
        return $rows;
    }
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM email_recipient_lists WHERE id = ?");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['email_list_flash'] = ['success' => true, 'message' => 'Email list deleted.'];
            header('Location: email_lists');
            exit();
        }

        $id = (int) ($_POST['id'] ?? 0);
        $title = cbCampaignCleanHeaderText($_POST['title'] ?? '');
        $purpose = cbCampaignCleanHeaderText($_POST['purpose'] ?? '');
        $sourceNote = cbCampaignCleanHeaderText($_POST['source_note'] ?? '');
        $parsed = cbCampaignValidEmailList($_POST['emails'] ?? '');
        $emails = implode("\n", $parsed['valid']);
        $emailCount = count($parsed['valid']);

        if ($title === '') {
            throw new Exception('Add a list title.');
        }
        if ($emailCount === 0) {
            throw new Exception('Add at least one valid email address.');
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE email_recipient_lists SET title = ?, purpose = ?, source_note = ?, emails = ?, email_count = ?, updated_at = NOW() WHERE id = ?");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('ssssii', $title, $purpose, $sourceNote, $emails, $emailCount, $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Email list updated with ' . number_format($emailCount) . ' address(es).';
        } else {
            $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
            $stmt = $conn->prepare("INSERT INTO email_recipient_lists (title, purpose, source_note, emails, email_count, created_by_admin_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param('ssssii', $title, $purpose, $sourceNote, $emails, $emailCount, $adminId);
            $stmt->execute();
            $stmt->close();
            $message = 'Email list saved with ' . number_format($emailCount) . ' address(es).';
        }

        if (!empty($parsed['invalid'])) {
            $message .= ' Skipped invalid email(s): ' . implode(', ', array_slice($parsed['invalid'], 0, 8));
            if (count($parsed['invalid']) > 8) {
                $message .= ' and more.';
            }
        }

        $_SESSION['email_list_flash'] = ['success' => true, 'message' => $message];
        header('Location: email_lists');
        exit();
    } catch (Exception $e) {
        $flash = ['success' => false, 'message' => $e->getMessage()];
    }
}

if (!$flash && isset($_SESSION['email_list_flash'])) {
    $flash = $_SESSION['email_list_flash'];
    unset($_SESSION['email_list_flash']);
}

$editing = cbEmailListFetch($conn, $_GET['edit'] ?? 0);
$rows = cbEmailListRows($conn);

include 'header.php';
include 'page_menues.php';
?>

<title>Email Lists - Sir Francis Admin</title>

<style>
    .email-list-wrap { padding: 28px 0 70px; }
    .email-list-hero { background: #2d1739; color: #fff; padding: 22px; border-radius: 8px; margin-bottom: 16px; }
    .email-list-hero h1 { color: #CEBD88; font-size: 28px; margin: 0 0 6px; }
    .email-list-panel { background: #fff; border: 1px solid #e9e2d8; border-radius: 8px; padding: 18px; height: 100%; }
    .email-list-panel h2, .email-list-panel h3 { color: #28364B; }
    .field-help { color: #6d6570; font-size: 13px; margin-top: 4px; }
    .email-list-table th { background: #fbf7ed; color: #4d2459; font-size: 12px; text-transform: uppercase; white-space: nowrap; }
    .email-preview { max-height: 130px; overflow: auto; white-space: pre-wrap; word-break: break-word; background: #fbfaf7; border: 1px solid #ece4d8; border-radius: 6px; padding: 8px; font-size: 12px; }
    .button-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
</style>

<div class="container email-list-wrap">
    <div class="email-list-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1>Email Lists</h1>
                <p class="mb-0">Save reusable recipient groups for targeted broadcasts, wholesale lists, special clients or staff-only test groups.</p>
            </div>
            <a href="schedule_email" class="btn btn-light mt-3 mt-md-0">Create broadcast</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= !empty($flash['success']) ? 'alert-success' : 'alert-danger' ?>">
            <?= cbEmailListText($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="email-list-panel">
                <h2><?= $editing ? 'Edit Email List' : 'New Email List' ?></h2>
                <form method="post">
                    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                    <div class="form-group">
                        <label for="title">List title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= cbEmailListText($editing['title'] ?? '') ?>" required>
                        <div class="field-help">For example: Wholesale leads June, private-label enquiries, Durban retail buyers.</div>
                    </div>
                    <div class="form-group">
                        <label for="purpose">What this list is for</label>
                        <input type="text" class="form-control" id="purpose" name="purpose" value="<?= cbEmailListText($editing['purpose'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="source_note">Where it came from</label>
                        <input type="text" class="form-control" id="source_note" name="source_note" value="<?= cbEmailListText($editing['source_note'] ?? '') ?>">
                        <div class="field-help">Useful for staff memory, such as "Expo 2026 signup sheet" or "Imported from old client base".</div>
                    </div>
                    <div class="form-group">
                        <label for="emails">Email addresses</label>
                        <textarea class="form-control" id="emails" name="emails" rows="10" required><?= cbEmailListText($editing['emails'] ?? '') ?></textarea>
                        <div class="field-help">One per line is best. Commas and spaces also work. Duplicates are removed automatically.</div>
                    </div>
                    <div class="button-row">
                        <button type="submit" name="action" value="save" class="btn btn-primary"><?= $editing ? 'Update list' : 'Save list' ?></button>
                        <?php if ($editing): ?>
                            <a href="email_lists" class="btn btn-link">Start new</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="email-list-panel">
                <h3>Saved Lists</h3>
                <div class="table-responsive">
                    <table class="table table-hover email-list-table">
                        <thead>
                            <tr>
                                <th>List</th>
                                <th>Emails</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="4" class="text-center py-4">No saved lists yet.</td></tr>
                            <?php else: foreach ($rows as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= cbEmailListText($row['title'] ?? '') ?></strong>
                                        <?php if (!empty($row['purpose'])): ?>
                                            <div class="field-help"><?= cbEmailListText($row['purpose']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['source_note'])): ?>
                                            <div class="field-help">Source: <?= cbEmailListText($row['source_note']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= number_format((int) ($row['email_count'] ?? 0)) ?></strong>
                                        <details class="mt-1">
                                            <summary>Preview</summary>
                                            <div class="email-preview"><?= cbEmailListText($row['emails'] ?? '') ?></div>
                                        </details>
                                    </td>
                                    <td><?= cbEmailListText($row['updated_at'] ?? '') ?></td>
                                    <td>
                                        <div class="button-row">
                                            <a href="email_lists?edit=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <form method="post" onsubmit="return confirm('Delete this saved email list?');">
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Delete</button>
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
