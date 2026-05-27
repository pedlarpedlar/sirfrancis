<?php
// Start or resume the session
session_start();

date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2

// Include your database connection file
include_once "dbh.inc.php"; // Adjust the filename as needed
include 'log_action_function.php';

// Update session end time in the database for analytical tracking
$user_id = $_SESSION['user_id'] ?? NULL;
$guest_identifier = $_SESSION['guest_identifier'] ?? NULL;
$current_session_id = $_SESSION['current_session_id'] ?? NULL;
$end_time = date('Y-m-d H:i:s');

// First, try to update the session for the given user_id
$stmt = $conn->prepare("UPDATE sessions SET end_time = ? WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("si", $end_time, $user_id);
$stmt->execute();
$affected_rows = $stmt->affected_rows;
$stmt->close();

// If no rows were updated (i.e., no session found for the user_id), update the session with the given session_id
if ($affected_rows === 0) {
    $stmt = $conn->prepare("UPDATE sessions SET end_time = ? WHERE id = ?");
    $stmt->bind_param("ss", $end_time, $current_session_id);
    $stmt->execute();
    $stmt->close();
}

// Remove the "Remember Me" cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Remove the remember-me token from the database
    $token = $_COOKIE['remember_token'];
    $sql = "UPDATE users SET remember_token = NULL, remember_token_expiration = NULL WHERE remember_token = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
}

logAction('Logout', 'from logout page', $user_id, $guest_identifier);

// Destroy the session
session_destroy();

// var_dump($_SESSION['session_id']);
// var_dump($_SESSION['current_session_id']);

// Unset all of the session variables
$_SESSION = array();

// Redirect to the login page or any other desired location after logout
header("Location: login");
exit();
