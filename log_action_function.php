<?php
function logAction($action, $details = '', $userId = null, $guestIdentifier = '') {
    global $conn; // Use your database connection
    if (!($conn instanceof mysqli)) {
        return;
    }

    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO action_logs (action, details, user_id, guest_identifier, user_agent, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $action, $details, $userId, $guestIdentifier, $user_agent, $ip_address);
    $stmt->execute();
    $stmt->close();
}
