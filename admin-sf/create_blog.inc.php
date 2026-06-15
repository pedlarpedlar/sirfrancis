<?php
ob_start();
ob_clean();
// Start or resume the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "create_blog";
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

include 'dbh.inc.php';

// Get form data
$blog_id = $_POST['blog_id'];
$title = $_POST['title'];
$author = $_POST['author'];
$tags = $_POST['tags'];
$content = $_POST['content'];
$date = $_POST['display_date']; // Display date

$targetFilePath = '';

// Handle image upload if an image is uploaded
if (!empty($_FILES["image"]["name"])) {
    $targetDirectory = "../uploads/blogs/";
    $targetFileName = time() . basename($_FILES["image"]["name"]);
    $targetFilePathFileSystem = $targetDirectory . $targetFileName;
    $targetFilePathDatabase = "uploads/blogs/" . $targetFileName; // Database path without "../"
    $imageFileType = strtolower(pathinfo($targetFilePathFileSystem, PATHINFO_EXTENSION));

    // Check if the file is a valid image
    $allowedExtensions = array("jpg", "jpeg", "png", "gif");

    if (in_array($imageFileType, $allowedExtensions)) {
        // Check if the file was successfully uploaded
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePathFileSystem)) {
            echo "The image file has been uploaded successfully.";
        } else {
            echo "Error uploading the image file.";
            $targetFilePathDatabase = ""; // Set empty value for the image URL
        }
    } else {
        echo "Invalid image file format. Allowed formats: JPG, JPEG, PNG, GIF.";
        $targetFilePathDatabase = ""; // Set empty value for the image URL
    }
}


if (empty($blog_id)) {
    // Prepare and bind statement
    $stmt = $conn->prepare("INSERT INTO blogs (title, content, author_id, image_url, display_date, tags, author) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $title, $content, $admin_id, $targetFilePathDatabase, $date, $tags, $author);

    // Execute statement
    if ($stmt->execute() === TRUE) {
        $lastInsertId = $conn->insert_id;
        header("Location: manage_blogs?id=" . $lastInsertId); // Redirect to posts page with latest ID
        exit(); // Make sure to exit after sending the header
    } else {
        echo "Error: " . $stmt->error;
    }

} else {
    // Check if the blog ID exists
    $checkStmt = $conn->prepare("SELECT id FROM blogs WHERE id = ?");
    $checkStmt->bind_param("i", $blog_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Prepare and bind statement
        $stmt = $conn->prepare("UPDATE blogs SET title=?, content=?, author_id=?, image_url=?, display_date=?, tags=?, author=? WHERE id=?");
        $stmt->bind_param("ssssssss", $title, $content, $admin_id, $targetFilePath, $date, $tags, $author, $blog_id);

        // Execute statement
        if ($stmt->execute() === TRUE) {
            header("Location: manage_blogs?id=" . $blog_id); // Redirect to posts page with the latest ID
            exit(); // Make sure to exit after sending the header
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Error: Blog with ID $blog_id does not exist.";
    }

    // Close the check statement
    $checkStmt->close();

}

// Close statement and database connection
$stmt->close();
$conn->close();
?>

