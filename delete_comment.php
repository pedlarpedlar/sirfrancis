<?php
session_start();

include 'dbh.inc.php';

$comment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$guestIdentifier = $_SESSION['guest_identifier'];


$sql = "UPDATE recipe_comments SET deleted = TRUE WHERE id = ? AND (user_id = ? OR guest_identifier = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iis', $comment_id, $user_id, $guestIdentifier);

if ($stmt->execute()) {
    echo "Comment deleted successfully";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: recipe"); // Redirect back to the comments page
exit();
?>