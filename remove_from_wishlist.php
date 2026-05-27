<?php
include 'session_logins.php';
// Include your database connection file
include_once "dbh.inc.php";

// Retrieve wishlist ID from the AJAX request
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;

// Wishlist item exists and belongs to the current user or guest, proceed with deletion
$deleteSql = "DELETE FROM wishlist WHERE product_id = ? AND (user_id = ? OR guest_identifier = ?)";
$deleteStmt = mysqli_prepare($conn, $deleteSql);

if (!$deleteStmt) {
    die('Error in preparing statement: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param($deleteStmt, "iis", $product_id, $userId, $guestIdentifier);
mysqli_stmt_execute($deleteStmt);


if (mysqli_stmt_errno($deleteStmt)) {
    die('Error in executing statement: ' . mysqli_stmt_error($deleteStmt));
}

// Close the delete statement
mysqli_stmt_close($deleteStmt);

// Return a response (optional)
echo json_encode(['success' => true, 'message' => 'Product removed from wishlist: ' . $userId . ' and guest: ' . $guestIdentifier . ' and product id: ' . $product_id]);
