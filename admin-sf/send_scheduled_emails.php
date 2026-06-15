<?php
include 'dbh.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/PHPMailer/src/PHPMailer.php';
require '../PHPMailer/PHPMailer/src/Exception.php';
require '../PHPMailer/PHPMailer/src/SMTP.php';

$liveConfigPath = '/home/candybirdco/configs_candybird/candybird_config.php';
if (file_exists($liveConfigPath)) {
    require_once($liveConfigPath);
} elseif (file_exists(__DIR__ . '/../configs/email_config.php')) {
    require_once(__DIR__ . '/../configs/email_config.php');
}

require_once 'campaign_email_helpers.php';
cbCampaignEnsureTables($conn);

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/email_scheduler_logs';

function cbCampaignLog($message)
{
    global $logFile;
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

$emails = $conn->query("SELECT * FROM scheduled_emails WHERE scheduled_at <= NOW() AND sent = 0 ORDER BY scheduled_at ASC");
if (!$emails) {
    cbCampaignLog('Could not fetch scheduled emails: ' . $conn->error);
    exit;
}

while ($email = $emails->fetch_assoc()) {
    $emailId = (int) $email['id'];
    $payload = cbCampaignPayloadFromScheduledEmail($email);
    $payload['campaign_id'] = $emailId;
    $sentCount = 0;
    $failedCount = 0;
    $failures = array();

    $subscriberRows = [];
    if (($payload['recipient_mode'] ?? 'subscribers_plus_custom') !== 'custom_only') {
        $recipients = $conn->query("SELECT id, email FROM subscribers WHERE is_subscribed = 1 AND email <> '' ORDER BY id ASC");
        if (!$recipients) {
            cbCampaignLog("Campaign {$emailId} could not fetch subscribers: " . $conn->error);
            continue;
        }

        while ($recipient = $recipients->fetch_assoc()) {
            $subscriberRows[] = $recipient;
        }
    }

    $unsubscribedKeys = !empty($payload['exclude_unsubscribed_manual']) ? cbCampaignGetUnsubscribedEmailKeys($conn) : [];
    $recipientBuild = cbCampaignBuildRecipientList($subscriberRows, cbCampaignParseManualRecipients($payload['manual_recipients'] ?? ''), $unsubscribedKeys);
    $recipientEmails = $recipientBuild['recipients'];
    $recipientStats = $recipientBuild['stats'];

    foreach ($recipientEmails as $recipientEmail) {
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $failedCount++;
            $failures[] = $recipientEmail . ' invalid email';
            continue;
        }

        try {
            cbCampaignSendEmail($recipientEmail, 'Sir Francis Subscriber', $payload);
            $sentCount++;
            cbCampaignLog("Campaign {$emailId} sent to {$recipientEmail}");
        } catch (Exception $e) {
            $failedCount++;
            $failures[] = $recipientEmail . ' ' . $e->getMessage();
            cbCampaignLog("Campaign {$emailId} failed for {$recipientEmail}: " . $e->getMessage());
        }
    }

    $recipientStatsJson = json_encode($recipientStats);
    $recipientSnapshotJson = json_encode(array_values($recipientEmails));
    $failureSummary = implode(' | ', array_slice($failures, 0, 50));
    $stmt = $conn->prepare("UPDATE scheduled_emails SET sent = 1, sent_at = NOW(), sent_success_count = ?, sent_failed_count = ?, scheduled_recipient_count = ?, recipient_stats_json = ?, recipient_snapshot_json = ?, failure_summary = ? WHERE id = ?");
    if ($stmt) {
        $totalRecipients = count($recipientEmails);
        $stmt->bind_param('iiisssi', $sentCount, $failedCount, $totalRecipients, $recipientStatsJson, $recipientSnapshotJson, $failureSummary, $emailId);
        $stmt->execute();
        $stmt->close();
    }

    try {
        cbCampaignSendAdminSummary(
            'Sir Francis broadcast sent',
            'A scheduled subscriber broadcast has finished sending.',
            $payload,
            array(
                'Campaign ID' => $emailId,
                'Audience' => ($payload['recipient_mode'] ?? '') === 'custom_only' ? 'Custom emails only' : 'Subscribers plus custom emails',
                'Attachments' => count(cbCampaignNormalizeAttachments($payload['attachments'] ?? [])),
                'Sent' => $sentCount,
                'Failed' => $failedCount,
                'Total recipients' => count($recipientEmails),
                'Extra recipients entered' => $recipientStats['manual_count'],
                'Duplicates skipped' => $recipientStats['duplicate_count'],
                'Invalid extras skipped' => $recipientStats['invalid_count'],
                'Unsubscribed extras skipped' => $recipientStats['unsubscribed_count'],
                'Unsubscribed filter' => !empty($payload['exclude_unsubscribed_manual']) ? 'On' : 'Off',
                'Scheduled for' => $email['scheduled_at']
            )
        );
    } catch (Exception $e) {
        cbCampaignLog("Campaign {$emailId} admin summary failed: " . $e->getMessage());
    }

    if (!empty($failures)) {
        cbCampaignLog("Campaign {$emailId} failures: " . implode(' | ', array_slice($failures, 0, 20)));
    }
    if (!empty($recipientStats['duplicate_count']) || !empty($recipientStats['invalid_count']) || !empty($recipientStats['unsubscribed_count'])) {
        cbCampaignLog("Campaign {$emailId} skipped {$recipientStats['duplicate_count']} duplicate(s), {$recipientStats['invalid_count']} invalid extra recipient(s), and {$recipientStats['unsubscribed_count']} unsubscribed extra recipient(s).");
    }
}
