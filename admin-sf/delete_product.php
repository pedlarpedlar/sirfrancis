<?php
// Start or resume the session
session_start();

include 'dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if the 'id' parameter is set in the URL
    if (isset($_GET['id'])) {
        // Sanitize the product ID
        $productId = mysqli_real_escape_string($conn, $_GET['id']);

        // Check if the admin_id is legitimate
        if (isset($_SESSION['admin_id'])) {
            $adminId = $_SESSION['admin_id'];

            // Check if the admin_id exists in the admin_users table
            $adminCheckQuery = "SELECT id FROM admin_users WHERE id = ? LIMIT 1";
            $adminStmt = mysqli_prepare($conn, $adminCheckQuery);

            if ($adminStmt) {
                mysqli_stmt_bind_param($adminStmt, 'i', $adminId);
                mysqli_stmt_execute($adminStmt);
                mysqli_stmt_store_result($adminStmt);

                if (mysqli_stmt_num_rows($adminStmt) > 0) {
                    // Admin_id is legitimate, proceed with the product deletion

                    // Prepare the delete statement
                    $deleteQuery = "DELETE FROM product WHERE id = ?";
                    $deleteStmt = mysqli_prepare($conn, $deleteQuery);

                    if ($deleteStmt) {
                        mysqli_stmt_bind_param($deleteStmt, 'i', $productId);
                        $result = mysqli_stmt_execute($deleteStmt);

                        if ($result) {
                            // Deletion successful
                            header("Location: manage_products.php?delete=success");
                            exit();
                        } else {
                            // Deletion failed
                            header("Location: manage_products.php?delete=error1");
                            exit();
                        }

                        // Close the statement
                        mysqli_stmt_close($deleteStmt);
                    } else {
                        // Error preparing the delete statement
                        header("Location: manage_products.php?delete=error2");
                        exit();
                    }
                }
            }
        }

        // If admin_id is not legitimate or not set
        header("Location: manage_products.php?delete=unauthorized");
        exit();
    } else {
        // 'id' parameter not set in the URL
        header("Location: manage_products.php?delete=error3");
        exit();
    }
} else {
    // Invalid request method
    header("Location: manage_products.php?delete=error4");
    exit();
}