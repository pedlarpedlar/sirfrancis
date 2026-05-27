<?php
require 'dbh.inc.php';

header('Content-Type: application/json');

function cbTrackingIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function cbTrackingLooksLikeBot($userAgent) {
    return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegrambot|preview|monitor|uptime|curl|wget/i', (string) $userAgent);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false]);
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));
$details = trim((string) ($_POST['details'] ?? ''));
$user_id = isset($_POST['user_id']) && is_numeric($_POST['user_id']) ? (int) $_POST['user_id'] : null;
$guest_identifier = trim((string) ($_POST['guest_identifier'] ?? ''));
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip_address = cbTrackingIpAddress();

if ($action === '' || cbTrackingLooksLikeBot($user_agent)) {
    echo json_encode(['success' => false, 'ignored' => true]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO action_logs (action, details, user_id, guest_identifier, user_agent, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false]);
    exit;
}
$stmt->bind_param("ssisss", $action, $details, $user_id, $guest_identifier, $user_agent, $ip_address);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);

