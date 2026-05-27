<?php
// Include database connection file
include 'dbh.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/PHPMailer/src/PHPMailer.php';
require '../PHPMailer/PHPMailer/src/Exception.php';
require '../PHPMailer/PHPMailer/src/SMTP.php';

$liveConfigPath = '/home/candybirdco/configs_candybird/candybird_config.php';
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

        // Check if execute was successful
        if ($executeResult) {
            // Check if update was successful
            if ($stmt->affected_rows > 0) {

            } else {
                // No rows affected, so likely no update made
                $response = array(
                    'status' => 'error',
                    'message' => 'No changes made. Order status might already be "'.$updatedStatus.'".'
                );
                // Set content type to JSON
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }
        } else {
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
    $mail = new PHPMailer(true);

    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = $smtp_server;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username5;
    $mail->Password = $smtp_password5;
    $mail->SMTPSecure = $smtp_type;
    $mail->Port = $smtp_port;

    // Set sender and recipient(s)
    $mail->setFrom($smtp_username5, 'CandyBird'); // Your email address and your name
    $mail->addAddress($billing_email_address, $billing_first_name); // Recipient's email address and name

    // Set "Reply-To" address
    $mail->addReplyTo($smtp_username1, 'CandyBird');

    // Set email subject
    $mail->Subject = $emailSubject;

    // Get the email body from the template file
    $email_body = file_get_contents('../emails/email_order_update.php');

    // Replace placeholders with actual values
    $email_body = str_replace('{recipient_name}', $billing_first_name, $email_body);
    $email_body = str_replace('{user_email_unsubscribe}', $billing_email_address, $email_body);
    $email_body = str_replace('{order_id}', $orderId_zeropad, $email_body);
    $email_body = str_replace('{order_status}', $updatedStatus, $email_body);
    $email_body = str_replace('{custom_message}', $emailBody, $email_body);

    // Set the email body
    $mail->Body = $email_body;

    // Set the email content type to HTML
    $mail->isHTML(true);

    // Send the email
    if ($mail->send()) {
        $mail_response = array('success' => true, 'message' => 'Email sent successfully!');
    } else {
        $mail_response = array('success' => true, 'message' => 'Email could not be sent.');
    }

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
    $admin_mail->setFrom($smtp_username5, 'CandyBird'); // Your email address and your name
    $admin_mail->addAddress($smtp_username1, 'Admin'); // Admin email address

    // Set email subject
    $admin_mail->Subject = "Order ".$orderId_zeropad." Update - ".$emailSubject;

    // Get the email body from the template file
    $admin_email_body = file_get_contents('../emails/email_order_update_admin.php');

    // Replace placeholders with actual values
    $admin_email_body = str_replace('{recipient_name}', "Admin", $admin_email_body);
    $admin_email_body = str_replace('{user_email_unsubscribe}', $billing_email_address, $admin_email_body);
    $admin_email_body = str_replace('{order_id}', $orderId_zeropad, $admin_email_body);
    $admin_email_body = str_replace('{order_status}', $updatedStatus, $admin_email_body);
    $admin_email_body = str_replace('{custom_message}', $emailBody, $admin_email_body);
    
    
    // Set the email body for admin
    $admin_mail->Body = $admin_email_body;

    // Set the email content type to HTML
    $admin_mail->isHTML(true);

    // Send the email to the admin
    if ($admin_mail->send()) {
        $admin_response = array('success' => true, 'message' => 'Admin email sent successfully!');
    } else {
        $admin_response = array('success' => false, 'message' => 'admin email could not be sent.');
    }


    // Success response
    $response = array(
        'status' => 'success',
        'message' => 'Order '.$orderId.' status updated to '.$updatedStatus.' successfully and email sent.'
    );
    // Set content type to JSON
    header('Content-Type: application/json');
    echo json_encode($response);




} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Order status was updated, but the email could not be sent: ' . $e->getMessage()
    ]);
}



?>
