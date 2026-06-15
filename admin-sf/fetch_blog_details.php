<?php
include 'dbh.inc.php';

// Check if the blog ID is provided in the request
if (isset($_GET['id'])) {
    $blog_id = $_GET['id'];

    // Fetch blog details from the database using prepared statement
    $sql = "SELECT id, title, tags, author, content, image_url, display_date FROM blogs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $blog_id); // Assuming the ID is an integer, adjust the type accordingly
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $blog = $result->fetch_assoc();

        // Return blog details in JSON format
        echo json_encode(['status' => 'success', 'data' => $blog]);
        exit;
    }
}

// If blog ID is not valid or not provided, return an error
echo json_encode(['status' => 'error', 'message' => 'Invalid or missing blog ID']);

// Close the database connection
$stmt->close();
$conn->close();
