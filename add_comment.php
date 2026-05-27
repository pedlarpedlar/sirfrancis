<?php
session_start();

include 'dbh.inc.php';

$recipe_id = $_POST['recipe_id'];
$parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL; // Ensure parent_id is NULL if not set
$comment_text = $_POST['comment_text'];
$guest_identifier = session_id(); // Unique identifier for guest commenters

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "INSERT INTO recipe_comments (recipe_id, parent_id, user_id, comment_text) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiis', $recipe_id, $parent_id, $user_id, $comment_text);
} else {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $sql = "INSERT INTO recipe_comments (recipe_id, parent_id, guest_identifier, name, email,  comment_text) VALUES (?, ?, ?, ?,  ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iissss', $recipe_id, $parent_id, $guest_identifier, $name, $email, $comment_text);
}

if ($stmt->execute()) {
    // echo "New comment added successfully";
    header("Location: recipe"); // Redirect back to the comments page
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>