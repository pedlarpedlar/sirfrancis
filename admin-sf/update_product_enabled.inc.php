<?php
// Include database connection
include 'dbh.inc.php';

// Check if productId and enabled status are received
if (isset($_POST['productId']) && isset($_POST['enabled'])) {
    $productId = $_POST['productId'];
    $enabled = $_POST['enabled'];

    // Prepare update query
    $stmt = $conn->prepare("UPDATE product SET enabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $enabled, $productId);

    // Execute update
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Product enabled status updated successfully.']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $stmt->error, 'message' => 'Failed to update product enabled status.']);
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request parameters', 'message' => 'Missing productId or enabled status.']);
}
?>