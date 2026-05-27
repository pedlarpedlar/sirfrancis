<?php

include 'dbh.inc.php';

// Check if imageUrl is set in the POST request
if (isset($_POST['imageUrl'])) {
    $imageUrl = $_POST['imageUrl'];

    // Use prepared statements to prevent SQL injection
    $sql = "SELECT COUNT(*) AS count FROM images WHERE image_url = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $imageUrl);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the count from the result
    $row = $result->fetch_assoc();
    $count = $row['count'];

    // Return a JSON response
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'exists' => ($count > 0)]);
} else {
    // If imageUrl is not set in the POST request, return an error response
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Image URL not provided']);
}

// Close the database connection (if applicable)
$stmt->close();
$conn->close();

?>
