<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Please sign in as admin first.']);
    exit();
}

// Include database connection file if necessary
include 'dbh.inc.php';
require_once 'campaign_email_helpers.php';
cbCampaignEnsureTables($conn);

// Define the target directory
$target_dir = "uploads/email_scheduler_images/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    echo json_encode(['error' => 'No image file was uploaded.']);
    exit();
}

// Generate a unique filename for the uploaded image
$target_filename = uniqid('', true) . '.' . pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION); // Generates a unique filename with original extension
$target_file = $target_dir . $target_filename;

// Check if image file is an actual image or fake image
$response = [];

$check = getimagesize($_FILES["file"]["tmp_name"]);
if($check !== false) {
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // Image uploaded successfully, prepare response
        $response['location'] = 'https://www.fishgelatine.co.za/v2/admin-sf/uploads/email_scheduler_images/' . $target_filename;

        // Optionally, insert the image URL into the database

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO email_scheduler_images (image_url) VALUES (?)");
        $stmt->bind_param("s", $response['location']); // Bind the image URL to the statement

        // Execute SQL statement
        if ($stmt->execute()) {
            $response['db_inserted'] = true;
        } else {
            $response['db_inserted'] = false;
            $response['db_error'] = $conn->error;
        }

        // Close statement
        $stmt->close();
        
    } else {
        $response['error'] = 'Sorry, there was an error uploading your file.';
    }
} else {
    $response['error'] = 'File is not an image.';
}

// Close database connection
$conn->close();

echo json_encode($response);
?>
