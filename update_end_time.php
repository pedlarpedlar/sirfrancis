<?php
date_default_timezone_set('Africa/Johannesburg');
require_once __DIR__ . '/dbh.inc.php';

$current_session_id = $_POST['session_id'] ?? NULL;
$end_time = date('Y-m-d H:i:s');

// Update session end time in the database
if ($current_session_id && $conn instanceof mysqli) {
    $stmt = $conn->prepare("UPDATE sessions SET end_time = ? WHERE id = ?");
    if ($stmt) {
        $current_session_id = (int) $current_session_id;
        $stmt->bind_param("si", $end_time, $current_session_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
