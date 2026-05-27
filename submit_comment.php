<?php
include 'session_logins.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle AJAX form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve form data
    $blogId = $_POST["blog_id"];
    $commentId = isset($_POST["comment_id"]) ? $_POST["comment_id"] : null;
    $name = $_POST["name"];
    $email = $_POST["email"];
    $website = $_POST["website"];
    $commentText = nl2br($_POST["comment"]);

    // Perform basic validation (you may want to enhance this)
    if (empty($name) || empty($email) || empty($commentText)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Check if comment_id is provided
    if ($commentId) {
        // Update the existing comment
        $updateCommentQuery = "UPDATE blog_comments SET name = ?, email = ?, website = ?, comment = ? WHERE id = ? AND (user_id = ? OR guest_identifier = ?)";
        $updateCommentStatement = $conn->prepare($updateCommentQuery);
        $updateCommentStatement->bind_param("ssssiss", $name, $email, $website, $commentText, $commentId, $userId, $guestIdentifier);

        if ($updateCommentStatement->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Your comment has been updated successfully!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating comment. Please try again.']);
        }

        // Close the statement
        $updateCommentStatement->close();
    } else {
        // Insert a new comment
        $insertCommentQuery = "INSERT INTO blog_comments (blog_id, name, email, website, comment, user_id, guest_identifier)
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertCommentStatement = $conn->prepare($insertCommentQuery);
        $insertCommentStatement->bind_param("issssss", $blogId, $name, $email, $website, $commentText, $userId, $guestIdentifier);

        if ($insertCommentStatement->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Your comment has been submitted successfully!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error submitting comment. Please try again.']);
        }

        // Close the statement
        $insertCommentStatement->close();
    }
} else {
    // Handle non-POST requests (optional)
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}


// Close the connection
$conn->close();