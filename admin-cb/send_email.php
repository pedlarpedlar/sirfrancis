<?php
include '../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "schedule_email";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

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

$message = '';
$success = false;
$oldPost = $_POST;

function cbCampaignReturnToForm($success, $message, $oldPost)
{
    $_SESSION['broadcast_flash'] = [
        'success' => (bool) $success,
        'message' => strip_tags((string) $message),
        'old' => $oldPost,
    ];

    header('Location: schedule_email');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'schedule';
    $payload = cbCampaignPayloadFromPost();
    $errors = cbCampaignValidatePayload($payload);

    $scheduledAtInput = trim($_POST['scheduled_at'] ?? date('Y-m-d\TH:i', strtotime('+10 minutes')));
    $scheduledAt = null;

    if ($action === 'schedule') {
        if ($scheduledAtInput === '') {
            $errors[] = 'Choose a send date and time.';
        } else {
            $timestamp = strtotime(str_replace('T', ' ', $scheduledAtInput));

            if ($timestamp === false) {
                $errors[] = 'Choose a valid send date and time.';
            } else {
                $scheduledAt = date('Y-m-d H:i:s', $timestamp);
            }
        }
    }

    if ($action === 'test') {
        $testEmail = trim($_POST['test_email'] ?? '');
        if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Add a valid test email address.';
        }
    }

    if (!empty($errors)) {
        $message = implode(' ', $errors);
    } else {
        try {
            if ($action === 'test') {
                $payload['campaign_id'] = 'test';
                cbCampaignSendEmail($testEmail, 'CandyBird Test', $payload);
                $message = 'Test email sent to ' . htmlspecialchars($testEmail, ENT_QUOTES, 'UTF-8') . '.';
                $success = true;
            } else {
                $stmt = $conn->prepare("INSERT INTO scheduled_emails (email_heading, subject, body, scheduled_at, sent) VALUES (?, ?, ?, ?, 0)");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }

                $bodyJson = json_encode($payload);
                $stmt->bind_param('ssss', $payload['email_heading'], $payload['subject'], $bodyJson, $scheduledAt);
                $stmt->execute();
                $campaignId = $stmt->insert_id;
                $stmt->close();
                $payload['campaign_id'] = $campaignId;

                $countResult = $conn->query("SELECT COUNT(*) AS total FROM subscribers WHERE is_subscribed = 1");
                $subscriberCount = 0;
                if ($countResult && $row = $countResult->fetch_assoc()) {
                    $subscriberCount = (int) $row['total'];
                }
                $manualRecipientCount = count(cbCampaignParseManualRecipients($payload['manual_recipients'] ?? ''));

                $summaryMessage = 'A confirmation email was sent to the web admin.';
                try {
                    cbCampaignSendAdminSummary(
                        'CandyBird broadcast scheduled',
                        'A subscriber broadcast has been scheduled and will be sent by the email sender when it is due.',
                        $payload,
                        array(
                            'Campaign ID' => $campaignId,
                            'Scheduled for' => $scheduledAt,
                            'Active subscribers' => $subscriberCount,
                            'Extra recipients' => $manualRecipientCount,
                            'Created by admin ID' => $_SESSION['admin_id']
                        )
                    );
                } catch (Exception $summaryError) {
                    $summaryMessage = 'The campaign was scheduled, but the admin confirmation email could not be sent: ' . $summaryError->getMessage();
                }

                $message = 'Broadcast scheduled successfully. ' . $summaryMessage;
                $success = true;
            }
        } catch (Exception $e) {
            $message = 'The broadcast could not be processed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    cbCampaignReturnToForm($success, $message, $oldPost);
} else {
    $message = 'Invalid request method.';
    cbCampaignReturnToForm(false, $message, []);
}
