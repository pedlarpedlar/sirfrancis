<?php
include 'dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize the input
    $imageUrl = isset($_POST['imageUrl']) ? $_POST['imageUrl'] : '';

    // Perform image deletion logic
    if (!empty($imageUrl)) {
        // Assuming the images are stored in a folder named "uploads"
        $imagePath = '../uploads/products/' . basename($imageUrl);

        // Unlink the image file from the folder
        if (file_exists($imagePath)) {
            unlink($imagePath);
            echo json_encode(['status' => 'success', 'message' => 'Image deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Image file not found']);
            exit();
        }

        // Delete the image record from the database (replace with your database logic)
        $sql = "DELETE FROM images WHERE image_url = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $imageUrl);

        // Execute the statement
        if ($stmt->execute()) {
            // Image record deleted successfully from the database
            $stmt->close();
            $conn->close();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid image URL']);
        exit();
    }
} else {
    // Handle non-POST requests
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}
?>
