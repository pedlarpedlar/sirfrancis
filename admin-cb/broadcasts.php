<?php
include '../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "broadcasts";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';
require_once 'campaign_email_helpers.php';

cbCampaignEnsureTables($conn);

function cbBroadcastText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbBroadcastRows($conn) {
    if (!($conn instanceof mysqli)) {
        return [];
    }
    $result = $conn->query("SELECT * FROM scheduled_emails ORDER BY sent ASC, scheduled_at DESC, id DESC LIMIT 250");
    if (!$result) {
        return [];
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function cbBroadcastStats($row) {
    $stats = json_decode($row['recipient_stats_json'] ?? '', true);
    return is_array($stats) ? $stats : [];
}

function cbBroadcastRecipients($row) {
    $recipients = json_decode($row['recipient_snapshot_json'] ?? '', true);
    if (!is_array($recipients)) {
        return [];
    }
    $clean = [];
    foreach ($recipients as $recipient) {
        $email = trim((string) $recipient);
        if ($email !== '') {
            $clean[] = $email;
        }
    }
    return array_values(array_unique($clean));
}

$flash = $_SESSION['broadcast_list_flash'] ?? null;
unset($_SESSION['broadcast_list_flash']);
$rows = cbBroadcastRows($conn);

include 'header.php';
include 'page_menues.php';
?>

<title>Email Broadcast History - CandyBird</title>

<style>
    .broadcast-history-wrap { padding: 28px 0 70px; }
    .broadcast-hero { background: #2d1739; color: #fff; padding: 22px; border-radius: 8px; margin-bottom: 16px; }
    .broadcast-hero h1 { color: #fcb42f; font-size: 28px; margin: 0 0 6px; }
    .broadcast-card { background: #fff; border: 1px solid #e8dfd2; border-radius: 8px; overflow: hidden; }
    .broadcast-table { margin-bottom: 0; }
    .broadcast-table th { background: #fbf7ed; border-top: 0; color: #4d2459; font-size: 12px; text-transform: uppercase; white-space: nowrap; }
    .broadcast-table td { vertical-align: top; }
    .status-pill { display: inline-flex; align-items: center; border-radius: 999px; font-size: 12px; font-weight: 700; padding: 4px 9px; }
    .status-pending { background: #fff6d9; color: #765300; }
    .status-sent { background: #e7f7ed; color: #166534; }
    .small-muted { color: #6d6570; font-size: 12px; }
    .broadcast-actions { display: flex; flex-wrap: wrap; gap: 6px; min-width: 210px; }
    .details-box { background: #fbfaf7; border: 1px solid #ece4d8; border-radius: 6px; padding: 8px; font-size: 12px; max-width: 360px; }
    @media (max-width: 767px) {
        .broadcast-actions { min-width: 0; }
        .broadcast-table { font-size: 13px; }
    }
</style>

<div class="container broadcast-history-wrap">
    <div class="broadcast-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1>Email Broadcasts</h1>
                <p class="mb-0">Review past broadcasts, edit pending broadcasts, copy campaigns and check send results.</p>
            </div>
            <a href="schedule_email" class="btn btn-light mt-3 mt-md-0">Create broadcast</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= !empty($flash['success']) ? 'alert-success' : 'alert-danger' ?>">
            <?= cbBroadcastText($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="broadcast-card">
        <div class="table-responsive">
            <table class="table table-hover broadcast-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Broadcast</th>
                        <th>Schedule</th>
                        <th>Recipients</th>
                        <th>Result</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="text-center py-4">No broadcasts found yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $payload = cbCampaignPayloadFromScheduledEmail($row);
                                $stats = cbBroadcastStats($row);
                                $isSent = (int) ($row['sent'] ?? 0) === 1;
                                $scheduledCount = (int) ($row['scheduled_recipient_count'] ?? 0);
                                $sentCount = (int) ($row['sent_success_count'] ?? 0);
                                $failedCount = (int) ($row['sent_failed_count'] ?? 0);
                                $hasTrackedHistory = !empty($row['sent_at'])
                                    || $sentCount > 0
                                    || $failedCount > 0
                                    || !empty($row['recipient_stats_json'])
                                    || !empty($row['recipient_snapshot_json']);
                                $manualCount = (int) ($stats['manual_count'] ?? 0);
                                $duplicateCount = (int) ($stats['duplicate_count'] ?? 0);
                                $invalidCount = (int) ($stats['invalid_count'] ?? 0);
                                $unsubscribedCount = (int) ($stats['unsubscribed_count'] ?? 0);
                                $recipientList = cbBroadcastRecipients($row);
                            ?>
                            <tr>
                                <td>
                                    <span class="status-pill <?= $isSent ? 'status-sent' : 'status-pending' ?>"><?= $isSent ? 'Sent' : 'Pending' ?></span>
                                    <div class="small-muted mt-1">#<?= (int) $row['id'] ?></div>
                                </td>
                                <td>
                                    <strong><?= cbBroadcastText($row['subject'] ?? '') ?></strong>
                                    <div class="small-muted"><?= cbBroadcastText($row['email_heading'] ?? '') ?></div>
                                    <?php if (!empty($payload['coupon_code'])): ?>
                                        <div class="small-muted">Coupon: <strong><?= cbBroadcastText($payload['coupon_code']) ?></strong></div>
                                    <?php endif; ?>
                                    <div class="small-muted">Audience: <strong><?= ($payload['recipient_mode'] ?? 'subscribers_plus_custom') === 'custom_only' ? 'Custom only' : 'Subscribers + custom' ?></strong></div>
                                </td>
                                <td>
                                    <?= cbBroadcastText($row['scheduled_at'] ?? '') ?>
                                    <?php if ($isSent && !empty($row['sent_at'])): ?>
                                        <div class="small-muted">Sent: <?= cbBroadcastText($row['sent_at']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= number_format($scheduledCount) ?></strong> queued
                                    <div class="small-muted"><?= number_format($manualCount) ?> extra entered</div>
                                    <?php if ($duplicateCount || $invalidCount || $unsubscribedCount): ?>
                                        <div class="small-muted"><?= number_format($duplicateCount) ?> duplicate, <?= number_format($invalidCount) ?> invalid, <?= number_format($unsubscribedCount) ?> unsubscribed skipped</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isSent): ?>
                                        <?php if ($hasTrackedHistory): ?>
                                            <strong><?= number_format($sentCount) ?></strong> sent
                                            <div class="small-muted"><?= number_format($failedCount) ?> failed</div>
                                        <?php else: ?>
                                            <strong>History unknown</strong>
                                            <div class="small-muted">Sent before detailed tracking was added</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="small-muted">Waiting for sender cron</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="details-box">
                                        <div><strong>Button:</strong> <?= cbBroadcastText($payload['cta_label'] ?? '') ?></div>
                                        <div><strong>Link:</strong> <?= cbBroadcastText($payload['cta_url'] ?? '') ?></div>
                                        <?php if (!empty($payload['manual_recipients'])): ?>
                                            <div><strong>Extra emails:</strong> <?= number_format(count(cbCampaignParseManualRecipients($payload['manual_recipients']))) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($recipientList)): ?>
                                            <details class="mt-1">
                                                <summary>View <?= number_format(count($recipientList)) ?> recipient<?= count($recipientList) === 1 ? '' : 's' ?></summary>
                                                <div style="max-height:150px;overflow:auto;margin-top:6px;word-break:break-word;">
                                                    <?= nl2br(cbBroadcastText(implode("\n", $recipientList))) ?>
                                                </div>
                                            </details>
                                        <?php endif; ?>
                                        <?php if (!empty($row['failure_summary'])): ?>
                                            <?php $failureText = (string) $row['failure_summary']; ?>
                                            <div class="text-danger mt-1"><?= cbBroadcastText(substr($failureText, 0, 180)) ?><?= strlen($failureText) > 180 ? '...' : '' ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="broadcast-actions">
                                        <?php if (!$isSent): ?>
                                            <a href="schedule_email?edit=<?= (int) $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <?php endif; ?>
                                        <a href="schedule_email?copy=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary">Copy</a>
                                        <?php if (!$isSent): ?>
                                            <form action="broadcast_action.php" method="post" onsubmit="return confirm('Delete this pending broadcast? This cannot be undone.');">
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
