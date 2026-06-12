<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("site_flags"));
    exit();
}

include __DIR__ . '/header.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

ensureCandybirdSiteFlagsTable($conn);

function cbSiteFlagAdminText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbSiteFlagAdminDateForInput($value) {
    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return '';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
}

function cbSiteFlagAdminDateForDb($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}

function cbSiteFlagAdminLiveState($flag) {
    $status = strtolower(trim((string) ($flag['status'] ?? '')));
    if ($status !== 'active') {
        return ['label' => 'Paused', 'class' => 'paused'];
    }

    $now = time();
    $startsAt = trim((string) ($flag['starts_at'] ?? ''));
    $endsAt = trim((string) ($flag['ends_at'] ?? ''));
    if ($startsAt !== '' && $startsAt !== '0000-00-00 00:00:00' && strtotime($startsAt) > $now) {
        return ['label' => 'Scheduled', 'class' => 'paused'];
    }
    if ($endsAt !== '' && $endsAt !== '0000-00-00 00:00:00' && strtotime($endsAt) < $now) {
        return ['label' => 'Ended', 'class' => 'paused'];
    }

    return ['label' => 'Showing now', 'class' => 'active'];
}

$notice = '';
$noticeClass = 'info';
$editingFlag = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM candybird_site_flags WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $noticeClass = $stmt->execute() ? 'success' : 'danger';
            $notice = $noticeClass === 'success' ? 'Notice deleted.' : 'Notice could not be deleted.';
            $stmt->close();
        }
    } elseif ($action === 'toggle' && $id > 0) {
        $status = $_POST['status'] === 'active' ? 'active' : 'paused';
        $stmt = $conn->prepare("UPDATE candybird_site_flags SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $status, $id);
            $noticeClass = $stmt->execute() ? 'success' : 'danger';
            $notice = $noticeClass === 'success' ? 'Notice status updated.' : 'Notice status could not be updated.';
            $stmt->close();
        }
    } else {
        $flagType = array_key_exists($_POST['flag_type'] ?? '', getCandybirdSiteFlagTypes()) ? $_POST['flag_type'] : 'notice';
        $title = trim((string) ($_POST['title'] ?? ''));
        $labelText = trim((string) ($_POST['label_text'] ?? ''));
        $placements = implode(',', normalizeCandybirdSiteFlagPlacements($_POST['placements'] ?? ['all']));
        $startsAt = cbSiteFlagAdminDateForDb($_POST['starts_at'] ?? '');
        $endsAt = cbSiteFlagAdminDateForDb($_POST['ends_at'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'paused' ? 'paused' : 'active';
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);

        if ($labelText === '') {
            $noticeClass = 'danger';
            $notice = 'Please add the customer-facing notice text.';
        } elseif ($startsAt && $endsAt && strtotime($endsAt) < strtotime($startsAt)) {
            $noticeClass = 'danger';
            $notice = 'The end date cannot be before the start date.';
        } elseif ($id > 0) {
            $stmt = $conn->prepare("UPDATE candybird_site_flags SET flag_type = ?, title = ?, label_text = ?, placements = ?, starts_at = ?, ends_at = ?, status = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('sssssssi', $flagType, $title, $labelText, $placements, $startsAt, $endsAt, $status, $id);
                $noticeClass = $stmt->execute() ? 'success' : 'danger';
                $notice = $noticeClass === 'success' ? 'Notice updated.' : 'Notice could not be updated.';
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO candybird_site_flags (flag_type, title, label_text, placements, starts_at, ends_at, status, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sssssssi', $flagType, $title, $labelText, $placements, $startsAt, $endsAt, $status, $adminId);
                $noticeClass = $stmt->execute() ? 'success' : 'danger';
                $notice = $noticeClass === 'success' ? 'Notice saved.' : 'Notice could not be saved.';
                $stmt->close();
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM candybird_site_flags WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editingFlag = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}

$flags = [];
$result = $conn->query("SELECT * FROM candybird_site_flags ORDER BY FIELD(status, 'active', 'paused'), COALESCE(starts_at, created_at) DESC, id DESC");
while ($result && ($row = $result->fetch_assoc())) {
    $flags[] = $row;
}

include __DIR__ . '/page_menues.php';
?>

<title>Site Notices - CandyBird Admin</title>

<style>
    .site-flags-wrap { padding: 28px 0 60px; }
    .site-flags-hero { background: #2d1739; border-radius: 8px; color: #fff; margin-bottom: 18px; padding: 22px; }
    .site-flags-hero h1 { color: #fcb42f; font-size: 28px; margin: 0 0 8px; }
    .site-flags-hero p { color: rgba(255,255,255,.84); margin: 0; max-width: 850px; }
    .site-flags-grid { display: grid; gap: 18px; grid-template-columns: minmax(0, 420px) minmax(0, 1fr); }
    .site-flags-card { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; box-shadow: 0 14px 34px rgba(71,44,22,.08); padding: 18px; }
    .site-flags-card h2 { color: #281b14; font-size: 20px; margin-bottom: 12px; }
    .site-flags-help { color: #6a5c52; font-size: 13px; line-height: 1.55; }
    .site-flags-placements { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .site-flags-placements label { align-items: center; background: #faf7f2; border: 1px solid #eadfd2; border-radius: 6px; display: flex; gap: 8px; margin: 0; padding: 9px; }
    .site-flag-status { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 800; padding: 5px 8px; }
    .site-flag-status.active { background: #eaf8ed; color: #216a34; }
    .site-flag-status.paused { background: #f1edf5; color: #594264; }
    .site-flag-message { color: #5b5049; max-width: 430px; white-space: pre-line; }
    .site-flag-actions { display: flex; flex-wrap: wrap; gap: 6px; }
    @media (max-width: 991px) {
        .site-flags-grid { grid-template-columns: 1fr; }
        .site-flags-placements { grid-template-columns: 1fr; }
    }
</style>

<div class="container site-flags-wrap">
    <div class="site-flags-hero">
        <h1>Site Notices</h1>
        <p>Create shop closure, delayed-processing, maintenance or general notices. Dates are optional: leave the start blank to publish immediately, and leave the end blank to keep it active until you pause or delete it.</p>
    </div>

    <?php if ($notice !== ''): ?>
        <div class="alert alert-<?= cbSiteFlagAdminText($noticeClass) ?>"><?= cbSiteFlagAdminText($notice) ?></div>
    <?php endif; ?>

    <div class="site-flags-grid">
        <section class="site-flags-card">
            <h2><?= $editingFlag ? 'Edit notice' : 'Create notice' ?></h2>
            <form method="post" action="site_flags">
                <input type="hidden" name="id" value="<?= (int) ($editingFlag['id'] ?? 0) ?>">
                <div class="form-group">
                    <label for="flag_type">Notice type</label>
                    <select class="form-control" id="flag_type" name="flag_type">
                        <?php foreach (getCandybirdSiteFlagTypes() as $value => $label): ?>
                            <option value="<?= cbSiteFlagAdminText($value) ?>" <?= ($editingFlag['flag_type'] ?? 'shop_closed') === $value ? 'selected' : '' ?>><?= cbSiteFlagAdminText($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Use shop closure for holidays, stocktake, travel or delayed fulfilment. Use maintenance for technical work.</small>
                </div>

                <div class="form-group">
                    <label for="title">Short title</label>
                    <input class="form-control" id="title" name="title" value="<?= cbSiteFlagAdminText($editingFlag['title'] ?? '') ?>" placeholder="Example: Eid closure notice">
                </div>

                <div class="form-group">
                    <label for="label_text">Customer-facing message</label>
                    <textarea class="form-control" id="label_text" name="label_text" rows="5" required placeholder="Example: Orders are welcome, but packing and dispatch will resume after 18 June 2026. Thank you for your patience."><?= cbSiteFlagAdminText($editingFlag['label_text'] ?? '') ?></textarea>
                    <small class="form-text text-muted">This is the exact message customers will see on selected pages.</small>
                </div>

                <div class="form-group">
                    <label>Where should it show?</label>
                    <?php $selectedPlacements = normalizeCandybirdSiteFlagPlacements($editingFlag['placements'] ?? 'products,product,checkout'); ?>
                    <div class="site-flags-placements">
                        <?php foreach (getCandybirdSiteFlagPlacements() as $value => $label): ?>
                            <label>
                                <input type="checkbox" name="placements[]" value="<?= cbSiteFlagAdminText($value) ?>" <?= in_array($value, $selectedPlacements, true) ? 'checked' : '' ?>>
                                <span><?= cbSiteFlagAdminText($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="starts_at">Start showing</label>
                        <input class="form-control" type="datetime-local" id="starts_at" name="starts_at" value="<?= cbSiteFlagAdminText(cbSiteFlagAdminDateForInput($editingFlag['starts_at'] ?? '')) ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ends_at">End / resume after</label>
                        <input class="form-control" type="datetime-local" id="ends_at" name="ends_at" value="<?= cbSiteFlagAdminText(cbSiteFlagAdminDateForInput($editingFlag['ends_at'] ?? '')) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active" <?= ($editingFlag['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active / scheduled</option>
                        <option value="paused" <?= ($editingFlag['status'] ?? '') === 'paused' ? 'selected' : '' ?>>Paused</option>
                    </select>
                </div>

                <button class="btn btn-warning" type="submit"><?= $editingFlag ? 'Update notice' : 'Save notice' ?></button>
                <?php if ($editingFlag): ?><a class="btn btn-light" href="site_flags">Cancel edit</a><?php endif; ?>
            </form>
        </section>

        <section class="site-flags-card">
            <h2>Existing notices</h2>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Notice</th>
                            <th>Schedule</th>
                            <th>Pages</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($flags)): ?>
                        <tr><td colspan="5">No notices yet.</td></tr>
                    <?php else: foreach ($flags as $flag): ?>
                        <tr>
                            <td><span class="site-flag-status <?= cbSiteFlagAdminText($flag['status']) ?>"><?= cbSiteFlagAdminText(ucfirst($flag['status'])) ?></span></td>
                            <td>
                                <strong><?= cbSiteFlagAdminText($flag['title'] ?: (getCandybirdSiteFlagTypes()[$flag['flag_type']] ?? 'Notice')) ?></strong>
                                <?php $liveState = cbSiteFlagAdminLiveState($flag); ?>
                                <div><span class="site-flag-status <?= cbSiteFlagAdminText($liveState['class']) ?>"><?= cbSiteFlagAdminText($liveState['label']) ?></span></div>
                                <div class="site-flag-message"><?= cbSiteFlagAdminText($flag['label_text']) ?></div>
                            </td>
                            <td>
                                <small>
                                    From: <?= cbSiteFlagAdminText(cbSiteFlagAdminDateForInput($flag['starts_at']) ?: 'now') ?><br>
                                    Until: <?= cbSiteFlagAdminText(cbSiteFlagAdminDateForInput($flag['ends_at']) ?: 'manually paused') ?>
                                </small>
                            </td>
                            <td><small><?= cbSiteFlagAdminText(str_replace(',', ', ', $flag['placements'])) ?></small></td>
                            <td>
                                <div class="site-flag-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="site_flags?edit=<?= (int) $flag['id'] ?>">Edit</a>
                                    <form method="post" action="site_flags">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int) $flag['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $flag['status'] === 'active' ? 'paused' : 'active' ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $flag['status'] === 'active' ? 'Pause' : 'Activate' ?></button>
                                    </form>
                                    <form method="post" action="site_flags" onsubmit="return confirm('Delete this notice?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $flag['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
