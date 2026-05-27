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

    $recipientEmails = [];
    $recipients = $conn->query("SELECT id, email FROM subscribers WHERE is_subscribed = 1 AND email <> '' ORDER BY id ASC");
    if (!$recipients) {
        cbCampaignLog("Campaign {$emailId} could not fetch subscribers: " . $conn->error);
        continue;
    }

    while ($recipient = $recipients->fetch_assoc()) {
        $recipientEmail = trim($recipient['email']);
        if ($recipientEmail !== '') {
            $recipientEmails[strtolower($recipientEmail)] = $recipientEmail;
        }
    }

    foreach (cbCampaignParseManualRecipients($payload['manual_recipients'] ?? '') as $manualEmail) {
        $recipientEmails[strtolower($manualEmail)] = $manualEmail;
    }

    foreach ($recipientEmails as $recipientEmail) {
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $failedCount++;
            $failures[] = $recipientEmail . ' invalid email';
            continue;
        }

        try {
            cbCampaignSendEmail($recipientEmail, 'CandyBird Subscriber', $payload);
            $sentCount++;
            cbCampaignLog("Campaign {$emailId} sent to {$recipientEmail}");
        } catch (Exception $e) {
            $failedCount++;
            $failures[] = $recipientEmail . ' ' . $e->getMessage();
            cbCampaignLog("Campaign {$emailId} failed for {$recipientEmail}: " . $e->getMessage());
        }
    }

    $stmt = $conn->prepare("UPDATE scheduled_emails SET sent = 1 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $emailId);
        $stmt->execute();
        $stmt->close();
    }

    try {
        cbCampaignSendAdminSummary(
            'CandyBird broadcast sent',
            'A scheduled subscriber broadcast has finished sending.',
            $payload,
            array(
                'Campaign ID' => $emailId,
                'Sent' => $sentCount,
                'Failed' => $failedCount,
                'Total recipients' => count($recipientEmails),
                'Extra recipients' => count(cbCampaignParseManualRecipients($payload['manual_recipients'] ?? '')),
                'Scheduled for' => $email['scheduled_at']
            )
        );
    } catch (Exception $e) {
        cbCampaignLog("Campaign {$emailId} admin summary failed: " . $e->getMessage());
    }

    if (!empty($failures)) {
        cbCampaignLog("Campaign {$emailId} failures: " . implode(' | ', array_slice($failures, 0, 20)));
    }
}
