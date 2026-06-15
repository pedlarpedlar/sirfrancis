<?php
// Start or resume the session
session_start();

// Check if the admin_id is set in the session
if (!isset($_SESSION['admin_id'])) {
    // Redirect or handle the case where admin_id is not set
    header("Location: admin_login"); // Redirect to login page, for example
    exit();
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

include 'dbh.inc.php';

// Check if the blog ID is provided in the URL
if (isset($_GET['id'])) {
    $blogId = $_GET['id'];

    // Check if the admin is the owner of the blog post
    $checkAdminSql = "SELECT id FROM blogs WHERE id = ? AND author_id = ?";
    $stmt = $conn->prepare($checkAdminSql);
    $stmt->bind_param("ii", $blogId, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Blog post exists and the admin is the author, proceed with deletion
        $stmt->close();

        // Prepare and execute the SQL DELETE query using a prepared statement
        $deleteSql = "DELETE FROM blogs WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $blogId);

        if ($deleteStmt->execute()) {
            // If the deletion was successful, redirect to the manage_blogs page
            header("Location: manage_blogs.php");
            exit();
        } else {
            echo "Error deleting blog: " . $conn->error;
        }
        $deleteStmt->close();
    } else {
        echo "Invalid blog ID or unauthorized access";
    }
} else {
    echo "Invalid blog ID";
}

$conn->close();
?>
