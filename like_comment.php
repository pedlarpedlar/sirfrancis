<?php
session_start();
include 'dbh.inc.php';

$commentId = $_POST['comment_id'];
$action = $_POST['action'];
$userId = $_SESSION['user_id'] ?? null; // Logged-in user ID
$guestIdentifier = $_SESSION['session_id']; // Guest identifier

// Check if user or guest has already reacted
$sql = "SELECT action FROM recipe_comment_likes WHERE comment_id = ? AND (user_id = ? OR guest_identifier = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iis', $commentId, $userId, $guestIdentifier);
$stmt->execute();
$stmt->bind_result($existingAction);
$stmt->fetch();
$stmt->close();

if ($existingAction) {
    if ($existingAction === $action) {
        // If the same action, remove the reaction
        $sql = "DELETE FROM recipe_comment_likes WHERE comment_id = ? AND (user_id = ? OR guest_identifier = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $commentId, $userId, $guestIdentifier);
        $stmt->execute();
        $stmt->close();

        // Decrease the count
        if ($action == 'like') {
            $sql = "UPDATE recipe_comments SET likes = likes - 1 WHERE id = ?";
        } elseif ($action == 'dislike') {
            $sql = "UPDATE recipe_comments SET dislikes = dislikes - 1 WHERE id = ?";
        }
    } else {
        // If different action, update the reaction
        $sql = "UPDATE recipe_comment_likes SET action = ? WHERE comment_id = ? AND (user_id = ? OR guest_identifier = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('siis', $action, $commentId, $userId, $guestIdentifier);
        $stmt->execute();
        $stmt->close();

        // Adjust the counts
        if ($action == 'like') {
            $sql = "UPDATE recipe_comments SET likes = likes + 1, dislikes = dislikes - 1 WHERE id = ?";
        } elseif ($action == 'dislike') {
            $sql = "UPDATE recipe_comments SET dislikes = dislikes + 1, likes = likes - 1 WHERE id = ?";
        }
    }
} else {
    // If no previous reaction, insert the new reaction
    $sql = "INSERT INTO recipe_comment_likes (comment_id, user_id, guest_identifier, action) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiss', $commentId, $userId, $guestIdentifier, $action);
    $stmt->execute();
    $stmt->close();

    // Increase the count
    if ($action == 'like') {
        $sql = "UPDATE recipe_comments SET likes = likes + 1 WHERE id = ?";
    } elseif ($action == 'dislike') {
        $sql = "UPDATE recipe_comments SET dislikes = dislikes + 1 WHERE id = ?";
    }
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $commentId);
$stmt->execute();
$stmt->close();

// Return updated counts
$sql = "SELECT likes, dislikes FROM recipe_comments WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $commentId);
$stmt->execute();
$stmt->bind_result($likes, $dislikes);
$stmt->fetch();
$stmt->close();

echo json_encode(['likes' => $likes, 'dislikes' => $dislikes, 'success' => true]);
?>