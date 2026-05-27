<?php
include 'session_logins.php';
// Include your database connection file
include_once "dbh.inc.php"; // Adjust the filename as needed

// Retrieve product ID from the AJAX request
$productId = isset($_POST['productId']) ? trim((string) $_POST['productId']) : null;

if (!($conn instanceof mysqli)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Wishlist is unavailable until the local database is connected.', 'product_id' => $productId]);
    exit;
}

// Check if the product is already in the wishlist
$checkSql = "SELECT COUNT(*) FROM wishlist WHERE (user_id = ? OR guest_identifier = ?) AND product_id = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error in preparing statement']);
} else {
    mysqli_stmt_bind_param($checkStmt, "iss", $userId, $guestIdentifier, $productId);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $count);
    mysqli_stmt_fetch($checkStmt);

    // Close the check statement
    mysqli_stmt_close($checkStmt);

    if ($count > 0) {
        // Product already in the wishlist
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Product is already in the wishlist', 'product_id' => $productId]);
    } else {
        // Insert into the wishlist table
        $insertSql = "INSERT INTO wishlist (user_id, guest_identifier, product_id) VALUES (?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertSql);

        if (!$insertStmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error in preparing insert statement']);
        } else {
            mysqli_stmt_bind_param($insertStmt, "iss", $userId, $guestIdentifier, $productId);
            mysqli_stmt_execute($insertStmt);

            if (mysqli_stmt_errno($insertStmt)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error in executing insert statement']);
            } else {
                // Close the insert statement
                mysqli_stmt_close($insertStmt);

                // Return a success response
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product added to wishlist', 'product_id' => $productId]);
            }
        }
    }
}

// Close the database connection
mysqli_close($conn);
?>
