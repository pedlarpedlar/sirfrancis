<?php
include '../session_logins.php';

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "manage_gallery";
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Ensure the uploads directory exists
$uploadDir = '../uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {

    $file = $_FILES['image'];
    $timestamp = time();
    $name = $file['name'];
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    $newFileName = $timestamp . '.' . $extension;
    $targetFile = $uploadDir . $newFileName;

    if ($file['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $successMessage = "File uploaded successfully";
            echo json_encode(['status' => 'success', 'message' => $successMessage]);
        } else {
            $errorMessage = "Failed to move uploaded file: $name";
            echo json_encode(['status' => 'error', 'message' => $errorMessage]);
        }
    } else {
        $uploadError = $file['error'];
        $errorMessage = "Upload error ($uploadError) occurred for file: $name";
        echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    }
    exit();
}

// Get list of images
$images = glob($uploadDir . '*.*');
$imagesPerPage = 8;
$totalImages = count($images);
$totalPages = ceil($totalImages / $imagesPerPage);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startIndex = ($page - 1) * $imagesPerPage;
$imagesToDisplay = array_slice($images, $startIndex, $imagesPerPage);

// Get the full URL of the site
$siteUrl = 'https://www.fishgelatine.co.za/v2/uploads/products/';

include 'header.php';
?>