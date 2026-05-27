<?php
include 'session_logins.php';
include_once "dbh.inc.php";

// Retrieve product ID from the AJAX request
$productId = isset($_POST['productId']) ? trim((string) $_POST['productId']) : null;

if (!($conn instanceof mysqli)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Compare is unavailable until the local database is connected.', 'product_id' => $productId]);
    exit;
}

// Set the expiration time (1 hour in seconds)
$expirationTime = time() - 3600;

// Check if the product is already in the compare within the last hour
$checkSql = "SELECT COUNT(*) FROM compare WHERE (user_id = ? OR guest_identifier = ?) 
              AND product_id = ? AND c_timestamp > ?";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error in preparing statement: ' . mysqli_error($conn)]);
} else {
    mysqli_stmt_bind_param($checkStmt, "issi", $userId, $guestIdentifier, $productId, $expirationTime);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $count);
    mysqli_stmt_fetch($checkStmt);

    // Close the check statement
    mysqli_stmt_close($checkStmt);

    if ($count > 0) {
        // Product already in the compare within the last hour
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Product is already in the compare list', 'product_id' => $productId]);
    } else {
        // Insert into the compare table
        $insertSql = "INSERT INTO compare (user_id, guest_identifier, product_id, c_timestamp) VALUES (?, ?, ?, NOW())";
        $insertStmt = mysqli_prepare($conn, $insertSql);

        if (!$insertStmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error in preparing insert statement: ' . mysqli_error($conn)]);
        } else {
            mysqli_stmt_bind_param($insertStmt, "iss", $userId, $guestIdentifier, $productId);
            mysqli_stmt_execute($insertStmt);

            if (mysqli_stmt_errno($insertStmt)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error in executing insert statement: ' . mysqli_error($conn)]);
            } else {
                // Close the insert statement
                mysqli_stmt_close($insertStmt);

                // Return a success response
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product added to compare list', 'product_id' => $productId]);
            }
        }
    }
}

// Close the database connection
mysqli_close($conn);
?>
