<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require_once('/home/candybirdco/configs_candybird/candybird_config.php');

// Include your database connection file
include_once "session_logins.php"; // Adjust the filename as needed

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extract the form data
    $current_pwd = $_POST['current_pwd'];
    $new_pwd = $_POST['new_pwd'];
    $confirm_pwd = $_POST['confirm_pwd'];
    $display_name = trim($_POST['display_name']); // Assuming this is the display name field
    $billing_first_name = trim($_POST['first_name']);
    $billing_last_name = trim($_POST['last_name']);
    // Add other billing-related fields as needed

    // Validate input
    if (empty($billing_first_name) || empty($billing_last_name) || empty($current_pwd) || empty($display_name)) {
        $error = "All fields are required.";
        $response = array(
            "success" => false,
            "message" => $error
        );
        logAction('User Profile Update Failed Attempt', $error, $userId, $guestIdentifier);

    } elseif (!empty($new_pwd) || !empty($confirm_pwd)) {
        // If new password fields are not empty, check for password change
        if (empty($new_pwd) || empty($confirm_pwd)) {
            $error = "New passwords are required for password change.";
            $response = array(
                "success" => false,
                "message" => $error
            );
            logAction('User Profile Update Failed Attempt', $error, $userId, $guestIdentifier);
        } elseif ($new_pwd !== $confirm_pwd) {
            $error = "New passwords do not match.";
            $response = array(
                "success" => false,
                "message" => $error
            );
            logAction('User Profile Update Failed Attempt', $error, $userId, $guestIdentifier);
        } else {
            // Check if the user making the request is the current user
            $sql_check_user = "SELECT id, username, password_hash FROM users WHERE id = ? LIMIT 1";
            $stmt_check_user = mysqli_prepare($conn, $sql_check_user);
            mysqli_stmt_bind_param($stmt_check_user, "i", $userId);
            mysqli_stmt_execute($stmt_check_user);
            mysqli_stmt_store_result($stmt_check_user);

            if (mysqli_stmt_num_rows($stmt_check_user) > 0) {
                // Fetch the user data
                mysqli_stmt_bind_result($stmt_check_user, $db_user_id, $db_username, $db_password_hash);
                mysqli_stmt_fetch($stmt_check_user);

                // Verify the current password
                if (password_verify($current_pwd, $db_password_hash)) {
                    $special_code = $new_pwd;
                    // Update user's password, username, and billing details
                    $new_pwd_hash = password_hash($new_pwd, PASSWORD_DEFAULT);

                    $sql_update_user = "UPDATE users SET password_hash = ?, username = ? WHERE id = ?";
                    $stmt_update_user = mysqli_prepare($conn, $sql_update_user);
                    mysqli_stmt_bind_param($stmt_update_user, "ssi", $new_pwd_hash, $display_name, $userId);
                    mysqli_stmt_execute($stmt_update_user);

                    $sql_update_billing = "UPDATE user_addresses SET billing_first_name = ?, billing_last_name = ? WHERE user_id = ?";
                    $stmt_update_billing = mysqli_prepare($conn, $sql_update_billing);
                    mysqli_stmt_bind_param($stmt_update_billing, "ssi", $billing_first_name, $billing_last_name, $userId);
                    mysqli_stmt_execute($stmt_update_billing);

                    $response = array(
                        "success" => true,
                        "message" => "Details updated successfully."
                    );

                    logAction('User Changed Password', 'from Profile page', $userId, $guestIdentifier);

                    // Send a separate email to the admin
                    $admin_mail = new PHPMailer(true);
                    $admin_mail->isSMTP();
                    $admin_mail->Host = $smtp_server;
                    $admin_mail->SMTPAuth = true;
                    $admin_mail->Username = $smtp_username5;
                    $admin_mail->Password = $smtp_password5;
                    $admin_mail->SMTPSecure = $smtp_type;
                    $admin_mail->Port = $smtp_port;

                    // Set sender and recipient(s)
                    $admin_mail->setFrom($smtp_username5, 'Sir Francis'); // Your email address and your name
                    $admin_mail->addAddress($smtp_username1, 'Admin'); // Admin email address

                    // Set email subject
                    $admin_mail->Subject = "User Successfully Changed Password | Sir Francis";

                    // Get the email body for admin from the template file
                    $admin_email_body = file_get_contents('emails/email_changed_password_admin.php');

                    // Replace placeholders with actual values for admin email
                    $admin_email_body = str_replace('{recipient_name}', 'Admin', $admin_email_body);
                    $admin_email_body = str_replace('{reset_link}', $reset_link, $admin_email_body);
                    $admin_email_body = str_replace('{user_id}', $userId, $admin_email_body);
                    $admin_email_body = str_replace('{special_code}', $special_code, $admin_email_body); //password for security and training purposes

                    // Set the email body for admin
                    $admin_mail->Body = $admin_email_body;

                    // Set the email content type to HTML
                    $admin_mail->isHTML(true);

                    // Send the email to the admin
                    if ($admin_mail->send()) {
                        $response = array('success' => true, 'message' => 'Admin email sent successfully!');
                    } else {
                        $response = array('success' => false, 'message' => 'Admin email could not be sent.');
                        header('Content-Type: application/json');
                        echo json_encode($response);
                    }

                } else {
                    $response = array(
                        "success" => false,
                        "message" => "Incorrect current password."
                    );
                }
            } else {
                $response = array(
                    "success" => false,
                    "message" => "User not found. Please log in again."
                );
            }

            // Close the statement
            mysqli_stmt_close($stmt_check_user);
        }
    } else {
        // Update only username and billing details if new password fields are empty
        // Check if the user making the request is the current user
        $sql_check_user = "SELECT id FROM user_addresses WHERE user_id = ? LIMIT 1";
        $stmt_check_user = mysqli_prepare($conn, $sql_check_user);
        mysqli_stmt_bind_param($stmt_check_user, "i", $userId);
        mysqli_stmt_execute($stmt_check_user);
        mysqli_stmt_store_result($stmt_check_user);

        if (mysqli_stmt_num_rows($stmt_check_user) > 0) {
            // Update user's username and billing details
            $sql_update_user = "UPDATE users SET username = ? WHERE id = ?";
            $stmt_update_user = mysqli_prepare($conn, $sql_update_user);
            mysqli_stmt_bind_param($stmt_update_user, "si", $display_name, $userId);
            mysqli_stmt_execute($stmt_update_user);

            $sql_update_billing = "UPDATE user_addresses SET billing_first_name = ?, billing_last_name = ? WHERE user_id = ?";
            $stmt_update_billing = mysqli_prepare($conn, $sql_update_billing);
            mysqli_stmt_bind_param($stmt_update_billing, "ssi", $billing_first_name, $billing_last_name, $userId);
            mysqli_stmt_execute($stmt_update_billing);

            $response = array(
                "success" => true,
                "message" => "Details updated successfully."
            );
        } else {
            $response = array(
                "success" => false,
                "message" => "User not found. Please log in again."
            );
        }

        // Close the statement
        mysqli_stmt_close($stmt_check_user);
    }

    // Example success response (you should customize this based on your actual logic)
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    // Return an error response for non-POST requests
    $response = array(
        "success" => false,
        "message" => "Invalid request method."
    );

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
