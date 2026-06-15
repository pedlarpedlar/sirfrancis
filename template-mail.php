<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require_once('/home/rukbanor/configs_Candybird/Candybird_config.php');

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

    $fullName = $firstName . ' ' . $lastName;

    // Set sender and recipient(s)
    $mail->setFrom($smtp_username5, 'Sir Francis'); // Your email address and your name
    $mail->addAddress($email, $fullName); // Recipient's email address and name

    // Set email subject
    $mail->Subject = "Welcome to Sir Francis - Registration Confirmation";

    // Get the email body from the template file
    $email_body = file_get_contents('emails/email_register.php');

    // Replace placeholders with actual values
    $recipient_name = $firstName;
    $email_body = str_replace('{recipient_name}', $recipient_name, $email_body);
    $email_body = str_replace('{user_email_unsubscribe}', $email, $email_body);

    // Set the email body
    $mail->Body = $email_body;

    // Set the email content type to HTML
    $mail->isHTML(true);

    // Send the email
    if ($mail->send()) {
        $mail_response = array('success' => true, 'message' => 'Registration successful! Email sent successfully!');
    } else {
        $mail_response = array('success' => true, 'message' => 'Registration successful! Email could not be sent.');
        $err = "IP Address ".$user_ip." - Registration Email Error: Could not send!";
        $errorLogger->error($err);
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
    $admin_mail->setFrom($smtp_username5, 'Sir Francis'); // Your email address and your name
    $admin_mail->addAddress($smtp_username1, 'Admin'); // Admin email address

    // Set email subject
    $admin_mail->Subject = "New User Registration";

    // Get the email body for admin from the template file
    $admin_email_body = file_get_contents('emails/email_register_admin.php');

    // Replace placeholders with actual values for admin email
    $admin_email_body = str_replace('{recipient_name}', 'Admin', $admin_email_body);
    $admin_email_body = str_replace('{user_id}', $user_id, $admin_email_body);
    $admin_email_body = str_replace('{user_name}', $fullName, $admin_email_body);
    $admin_email_body = str_replace('{user_email}', $email, $admin_email_body);
    
    // Set the email body for admin
    $admin_mail->Body = $admin_email_body;

    // Set the email content type to HTML
    $admin_mail->isHTML(true);

    // Send the email to the admin
    if ($admin_mail->send()) {
        $admin_response = array('success' => true, 'message' => 'Registration successful! Admin email sent successfully!');
    } else {
        $admin_response = array('success' => false, 'message' => 'Registration successful, but admin email could not be sent.');
        $err = "IP Address ".$user_ip." - Registration Email Error: Could not send!";
        $errorLogger->error($err);
    }


} catch (Exception $e) {
    $admin_response = array('success' => false, 'message' => 'Registration successful, but an error occurred while sending the admin email.');
    $err = "IP Address ".$user_ip." - Registration Email Error: " . $e;
    $errorLogger->error($err);
}

// Close the statement and database connection
$stmt->close();
$conn->close();

// Check if both email sending processes were successful
if ($response['success'] && $mail_response['success'] && $admin_response['success']) {
    // Both emails sent successfully
    $response = array('success' => true, 'message' => 'Registration successful! Emails sent successfully!');
} else {
    // At least one email failed to send
    $response = array('success' => false, 'message' => 'Registration successful, but some emails could not be sent.');
}

// Return the response as JSON
echo json_encode($response);