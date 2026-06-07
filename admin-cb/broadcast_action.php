<?php
include '../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode('broadcasts'));
    exit();
}

include 'dbh.inc.php';
require_once 'campaign_email_helpers.php';

cbCampaignEnsureTables($conn);

$action = $_POST['action'] ?? '';
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$message = 'Nothing was changed.';
$success = false;

try {
    $row = cbCampaignFetchScheduledEmail($conn, $id);
    if (!$row) {
        throw new Exception('Broadcast could not be found.');
    }

    if ($action === 'delete') {
        if ((int) ($row['sent'] ?? 0) === 1) {
            throw new Exception('Sent broadcasts are kept as history and cannot be deleted here.');
        }
        $stmt = $conn->prepare("DELETE FROM scheduled_emails WHERE id = ? AND sent = 0");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $message = 'Pending broadcast deleted.';
        $success = true;
    } else {
        throw new Exception('Unknown broadcast action.');
    }
} catch (Exception $e) {
    $message = $e->getMessage();
}

$_SESSION['broadcast_list_flash'] = [
    'success' => $success,
    'message' => $message,
];

header('Location: broadcasts');
exit();
