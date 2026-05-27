<?php
include 'session_logins.php';
include_once "dbh.inc.php";

// Retrieve product ID from the AJAX request
$productId = isset($_POST['productId']) ? $_POST['productId'] : null;

// Check if the product is in the compare list within the last hour
$checkSql = "SELECT COUNT(*) FROM compare WHERE (user_id = ? OR guest_identifier = ?) 
              AND product_id = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error in preparing statement: ' . mysqli_error($conn)]);
} else {
    mysqli_stmt_bind_param($checkStmt, "iss", $userId, $guestIdentifier, $productId);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $count);
    mysqli_stmt_fetch($checkStmt);

    // Close the check statement
    mysqli_stmt_close($checkStmt);

    if ($count > 0) {
        // Product is in the compare list within the last hour, proceed to delete
        $deleteSql = "DELETE FROM compare WHERE (user_id = ? OR guest_identifier = ?) 
                      AND product_id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteSql);

        if (!$deleteStmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error in preparing delete statement: ' . mysqli_error($conn)]);
        } else {
            mysqli_stmt_bind_param($deleteStmt, "iss", $userId, $guestIdentifier, $productId);
            mysqli_stmt_execute($deleteStmt);

            if (mysqli_stmt_errno($deleteStmt)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error in executing delete statement: ' . mysqli_error($conn)]);
            } else {
                // Close the delete statement
                mysqli_stmt_close($deleteStmt);

                // Return a success response
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product removed from compare list']);
            }
        }
    } else {
        // Product is not in the compare list within the last hour
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Product not found in the compare list']);
    }
}

// Close the database connection
mysqli_close($conn);
?>