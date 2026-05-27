<?php
// Initialize variables
$tracking_referrer = '';
$tracking_referrer_url = '';

if (!($conn instanceof mysqli)) {
    return;
}

$tracking_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegrambot|preview|monitor|uptime|curl|wget/i', $tracking_user_agent)) {
    return;
}

// Check if the request is an AJAX request
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
if ($is_ajax_request) {
    return;
}

// If it's not an AJAX request, proceed with tracking referrer
// Determine referrer type and get referrer URL
if (!empty($_SERVER['HTTP_REFERER'])) {
    $tracking_referrer_url = $_SERVER['HTTP_REFERER'];
    $tracking_referrer_domain = parse_url($tracking_referrer_url, PHP_URL_HOST);
    $current_domain = $_SERVER['HTTP_HOST'];

    // Check if the referrer domain matches the current domain
    if ($tracking_referrer_domain == $current_domain) {
        $tracking_referrer = 'direct';
    } else {
        $tracking_referrer = 'external source';
    }
} else {
    $tracking_referrer = 'direct';
    $tracking_referrer_url = 'external source or page refresh';
}

// Get the full URL including http/https
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Check if the URL should be tracked (exclude certain paths or extensions)
if (!shouldExcludeFromTracking($_SERVER['REQUEST_URI'])) {
    $timestamp = date('Y-m-d H:i:s');
    if (isset($_SESSION['session_id']) && !empty($_SESSION['session_id']) && !empty($_SESSION['current_session_id'])) {
        $current_session_id = (int) $_SESSION['current_session_id'];

        // Insert page view record
        $stmt = $conn->prepare("INSERT INTO page_views (session_id, url, timestamp, referrer, referrer_url) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $current_session_id, $url, $timestamp, $tracking_referrer, $tracking_referrer_url);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Function to exclude certain paths or extensions from tracking
function shouldExcludeFromTracking($request_uri) {
    $excluded_paths = array(
        '/assets/', '/admin/', '/admin-cb/',
        '.css', '.js', '.map', '.png', '.jpg', '.jpeg', '.webp', '.gif', '.svg', '.ico',
        'log_action.php', 'update_end_time.php', 'session_logins.php',
        'fetch_sheet', 'fetch_homepage_products', 'add_to_cart', 'update_cart', 'remove_from_cart',
        'apply_coupon', 'remove_coupon', 'get_product_reviews', 'getBadgeCounts'
    );

    foreach ($excluded_paths as $path) {
        if (strpos($request_uri, $path) !== false) {
            return true; // Exclude from tracking
        }
    }
    return false; // Track this URL
}
