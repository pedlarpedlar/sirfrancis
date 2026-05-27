<?php
session_start();

//include action logger and database connection
include 'log_action_function.php';
include 'dbh.inc.php';

function candybirdSetRememberCookie($token, $expiration) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('remember_token', $token, [
        'expires' => $expiration,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function candybirdSafeLoginRedirect($redirectUrl) {
    $redirectUrl = trim((string) $redirectUrl);

    if ($redirectUrl === '' ||
        preg_match('/[\r\n]/', $redirectUrl) ||
        preg_match('#^(https?:)?//#i', $redirectUrl)) {
        return 'profile';
    }

    return ltrim($redirectUrl, '/');
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the input values from the form
    $redirect_url = candybirdSafeLoginRedirect($_POST['redirect_url'] ?? 'profile');
    $username = $_POST['user-name'];
    $password = $_POST['user-password'];
    $rememberMe = isset($_POST['remember']); // Check if the "Remember Me" checkbox is checked
    $guestIdentifier = isset($_POST['guest-identifier']) ? $_POST['guest-identifier'] : NULL;

    // Initialize an array to store field-specific errors
    $errors = [];

    // Validate the inputs (add more validation as needed)
    if (empty($username)) {
        $error = 'Username is required.';
        $errors['username'] = $error;
        logAction('Login Failed Attempt', 'Error: '.$error, null, $guestIdentifier);
    }
    if (empty($password)) {
        $error = 'Password is required.';
        $errors['password'] = $error;
        logAction('Login Failed Attempt', 'Error: '.$error, null, $guestIdentifier);
    }

    if (!empty($errors)) {
        // Return validation errors
        $response = array(
            "success" => false,
            "errors" => $errors
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        // Check the database for the user
        $sql = "SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        // Bind parameters and execute the statement
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);

        // Get the result
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            // Bind the result variables
            mysqli_stmt_bind_result($stmt, $userId, $dbUsername, $dbEmail, $dbPasswordHash);
            mysqli_stmt_fetch($stmt);

            // Verify the password
            if (password_verify($password, $dbPasswordHash)) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $dbUsername;
                $_SESSION['email'] = $dbEmail;

                // Set a secure token for "Remember Me" if checked
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32)); // Generate a secure token
                    $expiration = time() + (30 * 24 * 60 * 60); // 30 days

                    // Store the token and expiration in the database associated with the user
                    $sql = "UPDATE users SET remember_token = ?, remember_token_expiration = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssi", $token, $expiration, $userId);
                    mysqli_stmt_execute($stmt);
                    
                    candybirdSetRememberCookie($token, $expiration);
                }

                // Update other tables to associate with the user when logging in
                $updateTables = ['reviews', 'cart', 'wishlist', 'compare', 'orders', 'blog_comments', 'applied_coupons'];

                if ($guestIdentifier != NULL) {
                    foreach ($updateTables as $table) {
                        // Update user_id for existing records
                        $sqlUpdate = "UPDATE $table SET user_id = ? WHERE guest_identifier = ?";
                        $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
                        mysqli_stmt_bind_param($stmtUpdate, "ss", $userId, $guestIdentifier);
                        mysqli_stmt_execute($stmtUpdate);
                        mysqli_stmt_close($stmtUpdate);
                    }

                    // Update sessions table
                    $sqlUpdate = "UPDATE sessions SET user_id = ? WHERE session_id = ?";
                    $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
                    mysqli_stmt_bind_param($stmtUpdate, "ss", $userId, $_SESSION['guest_identifier']);
                    mysqli_stmt_execute($stmtUpdate);
                    mysqli_stmt_close($stmtUpdate);
                }

                // Return success response
                $response = array(
                    "success" => true,
                    "message" => "Login successful",
                    "redirect_url" => $redirect_url
                );
                logAction('Login', 'Succesfully logged in', $userId, $guestIdentifier);
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            } else {
                // Invalid password
                $error = 'Invalid password.';
                $errors['password'] = $error;
                logAction('Login Failed Attempt', 'Error: '.$error, null, $guestIdentifier);
                $response = array(
                    "success" => false,
                    "errors" => $errors
                );
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }
        } else {
            // User not found
            $error = 'User not found.';
            $errors['username'] = $error;
            logAction('Login Failed Attempt', 'Error: '.$error, null, $guestIdentifier);
            $response = array(
                "success" => false,
                "errors" => $errors
            );
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    }
} else {
    // Form not submitted
    $response = array(
        "success" => false,
        "message" => "Form not submitted."
    );
    logAction('Login Failed Attempt', 'Error: Form not submitted', null, $guestIdentifier);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
