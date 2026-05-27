<?php
// Include your database connection file
include 'dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user_id = $_POST['user_id'];
    $current_time = time();

    if ($user_id != null) {
        // Update the last seen time of the user
        $stmt = $conn->prepare("UPDATE users SET last_seen = ? WHERE id = ?");
        $stmt->bind_param("ii", $current_time, $user_id);
        if ($stmt->execute()) {
            echo "User last seen updated";
        }
        $stmt->close();
    }

    echo "Heartbeat received";
}
?>