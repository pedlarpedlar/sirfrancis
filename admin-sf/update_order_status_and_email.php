<?php
// Include database connection file
include 'dbh.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/PHPMailer/src/PHPMailer.php';
require '../PHPMailer/PHPMailer/src/Exception.php';
require '../PHPMailer/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../candybird_mail_helpers.php';

$liveConfigPath = '/home2/rukbanor/configs_sirfrancis/sirfrancis_config.php';
if (file_exists($liveConfigPath)) {
    require_once($liveConfigPath);
} elseif (file_exists(__DIR__ . '/../configs/email_config.php')) {
    require_once(__DIR__ . '/../configs/email_config.php');
}

function cbAdminStatusPartialNote($partialFulfillment, $outstandingItems) {
    $partialFulfillment = trim((string) $partialFulfillment);
    $outstandingItems = trim((string) $outstandingItems);
    if ($partialFulfillment === '') {
        return '';
    }

    if ($partialFulfillment === 'partially_delivered') {
        $note = 'Partial fulfilment: Parcel delivered partially. Items are still outstanding for delivery.';
    } elseif ($partialFulfillment === 'partially_collected') {
        $note = 'Partial fulfilment: Parcel collected partially. Items are still outstanding for collection.';
    } else {
        return '';
    }

    if ($outstandingItems !== '') {
        $note .= "\nOutstanding: " . $outstandingItems;
    }

    return $note;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from POST request
    $orderId = $_POST['orderId'];
    $orderId_zeropad = str_pad($orderId, 7, '0', STR_PAD_LEFT);
    $updatedStatus = $_POST['updatedStatus'];
    $emailSubject = $_POST['emailSubject'];
    $emailBody = nl2br($_POST['emailBody']);
    $partialNote = cbAdminStatusPartialNote($_POST['partialFulfillment'] ?? '', $_POST['outstandingItems'] ?? '');
    // Store $emailBody in your database or file



    // Prepare SQL statement to fetch billing details
    $sql = "SELECT
            ua.billing_first_name,
            COALESCE(u.email, ua.billing_email_address) AS billing_email_address
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN user_addresses ua ON o.guest_identifier = ua.guest_identifier
            WHERE o.id = ?;
            ";

    // Initialize prepared statement
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind parameters and execute statement
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);

        // Bind result variables
        mysqli_stmt_bind_result($stmt, $billing_first_name, $billing_email_address);

        // Fetch result
        mysqli_stmt_fetch($stmt);

    }

    // Close statement
    mysqli_stmt_close($stmt);


    // Prepare SQL statement to update order status
    $sql = $partialNote !== ''
        ? "UPDATE orders SET order_status = ?, order_notes = TRIM(CONCAT(COALESCE(order_notes, ''), '\n', ?)) WHERE id = ?"
        : "UPDATE orders SET order_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters and execute statement
        if ($partialNote !== '') {
            $stmt->bind_param("ssi", $updatedStatus, $partialNote, $orderId);
        } else {
            $stmt->bind_param("si", $updatedStatus, $orderId);
        }
        $executeResult = $stmt->execute();

        if (!$executeResult) {
            // Execution error
            $response = array(
                'status' => 'error',
                'message' => 'Execution error: ' . $stmt->error
            );
            // Set content type to JSON
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        // Close statement
        $stmt->close();
    } else {
        // Prepare statement error
        $response = array(
            'status' => 'error',
            'message' => 'Prepare statement error: ' . $conn->error
        );
        // Set content type to JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
} else {
    // Handle other request methods (GET, PUT, DELETE, etc.) if needed
    $response = array(
        'status' => 'error',
        'message' => 'Invalid request method.'
    );
    // Set content type to JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}




// Now, proceed to send the registration confirmation email

try {
    // Get the email body from the template file
    $email_body = file_get_contents('../emails/email_order_update.php');

    // Replace placeholders with actual values
    $email_body = str_replace('{recipient_name}', $billing_first_name, $email_body);
    $email_body = str_replace('{user_email_unsubscribe}', $billing_email_address, $email_body);
    $email_body = str_replace('{order_id}', $orderId_zeropad, $email_body);
    $email_body = str_replace('{order_status}', $updatedStatus, $email_body);
    $email_body = str_replace('{custom_message}', $emailBody, $email_body);

    $mailResult = cbCandybirdSendMail(
        $billing_email_address,
        $billing_first_name,
        $emailSubject,
        $email_body,
        ['prefer_mail_transport' => true]
    );

    if (!empty($mailResult['success'])) {
        $mail_response = array('success' => true, 'message' => 'Email sent successfully!');
    } else {
        error_log('Sir Francis order update client email failed for order ' . $orderId_zeropad . ': ' . ($mailResult['error'] ?? 'unknown error'));
        $mail_response = array('success' => false, 'message' => 'Email could not be sent.');
    }

    // Get the email body from the template file
    $admin_email_body = file_get_contents('../emails/email_order_update_admin.php');

    // Replace placeholders with actual values
    $admin_email_body = str_replace('{recipient_name}', "Admin", $admin_email_body);
    $admin_email_body = str_replace('{user_email_unsubscribe}', $billing_email_address, $admin_email_body);
    $admin_email_body = str_replace('{order_id}', $orderId_zeropad, $admin_email_body);
    $admin_email_body = str_replace('{order_status}', $updatedStatus, $admin_email_body);
    $admin_email_body = str_replace('{custom_message}', $emailBody, $admin_email_body);
    
    
    $adminMailResult = cbCandybirdSendMail(
        $smtp_username1,
        'Admin',
        "Order ".$orderId_zeropad." Update - ".$emailSubject,
        $admin_email_body,
        [
            'reply_to_email' => $billing_email_address,
            'reply_to_name' => $billing_first_name ?: 'Sir Francis customer',
            'prefer_mail_transport' => true,
        ]
    );

    if (!empty($adminMailResult['success'])) {
        $admin_response = array('success' => true, 'message' => 'Admin email sent successfully!');
    } else {
        error_log('Sir Francis order update admin email failed for order ' . $orderId_zeropad . ': ' . ($adminMailResult['error'] ?? 'unknown error'));
        $admin_response = array('success' => false, 'message' => 'admin email could not be sent.');
    }

    if (empty($mail_response['success'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Order status was updated, but the client email could not be sent. The exact SMTP error has been logged.'
        ]);
        exit();
    }

    // Success response
    $response = array(
        'status' => 'success',
        'message' => 'Order '.$orderId.' status updated to '.$updatedStatus.' successfully and email sent.'
    );
    // Set content type to JSON
    header('Content-Type: application/json');
    echo json_encode($response);




} catch (Throwable $e) {
    error_log('Sir Francis order update email exception for order ' . ($orderId_zeropad ?? 'unknown') . ': ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Order status was updated, but the email could not be sent. The exact SMTP error has been logged.'
    ]);
}



?>
