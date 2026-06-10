<?php
date_default_timezone_set('Africa/Johannesburg');

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/dbh.inc.php';

header('Content-Type: application/json');

function cbAsyncTrackingIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function cbAsyncTrackingLooksLikeBot($userAgent) {
    return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegrambot|preview|monitor|uptime|curl|wget|headless|lighthouse/i', (string) $userAgent);
}

function cbAsyncTrackingExcludedUrl($url) {
    $path = strtolower((string) parse_url($url, PHP_URL_PATH));
    if ($path === '') {
        return true;
    }

    $excludedFragments = [
        '/assets/',
        '/uploads/',
        '/admin-cb/',
        'track_page_view',
        'log_action',
        'update_end_time',
        'fetch_',
        'add_to_',
        'remove_from_',
        'update_cart',
        'apply_coupon',
        'remove_coupon',
    ];

    foreach ($excludedFragments as $fragment) {
        if (strpos($path, $fragment) !== false) {
            return true;
        }
    }

    return (bool) preg_match('/\.(css|js|map|png|jpe?g|webp|gif|svg|ico|woff2?|ttf|pdf)$/i', $path);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false]);
    exit;
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ipAddress = cbAsyncTrackingIpAddress();
$url = trim((string) ($_POST['url'] ?? ''));
$referrerUrl = trim((string) ($_POST['referrer_url'] ?? ''));

if ($url === '' || cbAsyncTrackingLooksLikeBot($userAgent) || cbAsyncTrackingExcludedUrl($url)) {
    $_SESSION['tracking_bot'] = cbAsyncTrackingLooksLikeBot($userAgent);
    echo json_encode(['success' => false, 'ignored' => true]);
    exit;
}

if (empty($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}
if (empty($_SESSION['guest_identifier'])) {
    $_SESSION['guest_identifier'] = $_SESSION['session_id'];
}

$userId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$sessionToken = $_SESSION['session_id'];
$currentSessionId = isset($_SESSION['current_session_id']) ? (int) $_SESSION['current_session_id'] : 0;
$now = date('Y-m-d H:i:s');

if ($currentSessionId <= 0) {
    $stmt = $conn->prepare("SELECT id FROM sessions WHERE session_id = ? ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $sessionToken);
        $stmt->execute();
        $stmt->bind_result($existingSessionId);
        if ($stmt->fetch()) {
            $currentSessionId = (int) $existingSessionId;
        }
        $stmt->close();
    }
}

if ($currentSessionId <= 0) {
    $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_id, ip_address, user_agent, start_time) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $userId, $sessionToken, $ipAddress, $userAgent, $now);
        $stmt->execute();
        $currentSessionId = (int) $stmt->insert_id;
        $stmt->close();
    }
} elseif ($userId) {
    $stmt = $conn->prepare("UPDATE sessions SET user_id = COALESCE(user_id, ?), end_time = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("isi", $userId, $now, $currentSessionId);
        $stmt->execute();
        $stmt->close();
    }
}

$_SESSION['current_session_id'] = $currentSessionId;

if ($currentSessionId > 0) {
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    $referrerHost = $referrerUrl !== '' ? (string) parse_url($referrerUrl, PHP_URL_HOST) : '';
    $referrerType = $referrerUrl === '' ? 'direct' : ($referrerHost === $currentHost ? 'direct' : 'external source');

    $stmt = $conn->prepare("INSERT INTO page_views (session_id, url, timestamp, referrer, referrer_url) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $currentSessionId, $url, $now, $referrerType, $referrerUrl);
        $success = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $success, 'session_id' => $currentSessionId]);
        exit;
    }
}

echo json_encode(['success' => false]);
