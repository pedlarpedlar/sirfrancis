<?php
// Include your database connection file
include_once "session_logins.php"; // Adjust the filename as needed

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the input values from the AJAX request
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $streetAddress1 = $_POST['street_address_1'];
    $streetAddress2 = $_POST['street_address_2'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $country = $_POST['country'];
    $postCode = $_POST['post_code'];
    $phoneNumber = $_POST['phone_number'];
    $emailAddress = $_POST['email_address'];

    // Validate the inputs (add more validation as needed)
    if (empty($firstName) || empty($lastName) || empty($streetAddress1) || empty($city) || empty($province) || empty($country) || empty($postCode) || empty($phoneNumber) || empty($emailAddress)) {
        // Handle validation errors or send an error response
        $error = "Please fill in all required fields.";
        $response = array(
            "success" => false,
            "message" => $error
        );
        logAction('User Billing Address Update Failed Attempt', $error, $userId, $guestIdentifier);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        // Check if the user has an existing address
        $sql_check_address = "SELECT id FROM user_addresses WHERE user_id = ?";
        $stmt_check_address = mysqli_prepare($conn, $sql_check_address);
        mysqli_stmt_bind_param($stmt_check_address, "i", $userId);
        mysqli_stmt_execute($stmt_check_address);
        mysqli_stmt_store_result($stmt_check_address);

        if (mysqli_stmt_num_rows($stmt_check_address) > 0) {
            // User has an existing address, perform an update
            $sql = "UPDATE user_addresses SET
                    billing_first_name = ?,
                    billing_last_name = ?,
                    billing_street_address_1 = ?,
                    billing_street_address_2 = ?,
                    billing_city = ?,
                    billing_province = ?,
                    billing_country = ?,
                    billing_post_code = ?,
                    billing_phone_number = ?,
                    billing_email_address = ?,
                    guest_identifier = ?
                    WHERE user_id = ?";
        } else {
            // User does not have an existing address, perform an insert
            $sql = "INSERT INTO user_addresses (billing_first_name, billing_last_name, billing_street_address_1, billing_street_address_2, billing_city, billing_province, billing_country, billing_post_code, billing_phone_number, billing_email_address, guest_identifier, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        // Execute the prepared statement
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssssss", $firstName, $lastName, $streetAddress1, $streetAddress2, $city, $province, $country, $postCode, $phoneNumber, $emailAddress, $guest_identifier, $userId);
        mysqli_stmt_execute($stmt);

        // Check if the operation was successful
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            
            logAction('User Changed Billing Address Successfully', 'from Profile page', $userId, $guestIdentifier);
            
            // Send a success response
            $response = array(
                "success" => true,
                "message" => 'Successfully updated address!',
                "isNewAddress" => mysqli_stmt_num_rows($stmt_check_address) == 0
            );

        } else {
            // Send an error response if the operation fails
            $error = "Error updating address. Please try again.";
            $response = array(
                "success" => false,
                "message" => $error
            );
            logAction('User Billing Address Update Failed Attempt', $error, $userId, $guestIdentifier);
        }

        // Close the statement
        mysqli_stmt_close($stmt);

        // Send the JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
} else {
    // Unauthorized request
    $error = "Unauthorized request.";
    $response = array(
        "success" => false,
        "message" => $error
    );
    logAction('User Billing Address Update Failed Attempt', $error, $userId, $guestIdentifier);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>