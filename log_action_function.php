<?php
function logAction($action, $details = '', $userId = null, $guestIdentifier = '') {
    global $conn; // Use your database connection
    if (!($conn instanceof mysqli)) {
        return;
    }

    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    $conn->query("CREATE TABLE IF NOT EXISTS action_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(255) NOT NULL,
        details TEXT NULL,
        user_id INT NULL,
        guest_identifier VARCHAR(255) NULL,
        user_agent TEXT NULL,
        ip_address VARCHAR(64) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->prepare("INSERT INTO action_logs (action, details, user_id, guest_identifier, user_agent, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('Sir Francis logAction prepare failed: ' . $conn->error);
        return;
    }
    $stmt->bind_param("ssisss", $action, $details, $userId, $guestIdentifier, $user_agent, $ip_address);
    if (!$stmt->execute()) {
        error_log('Sir Francis logAction execute failed: ' . $stmt->error);
    }
    $stmt->close();
}
