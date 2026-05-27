<?php
include 'session_logins.php';
include 'header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require_once('/home/candybirdco/configs_candybird/candybird_config.php');

?>

<title>Unsubscribe from CandyBird mailing list - Sad to see you go!</title>

<?php
include 'page_menues.php';
?>

  <div class="container">

    <div class="unsubscribe-content">
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["email"]) && filter_var($_GET["email"], FILTER_VALIDATE_EMAIL)) {
            // Sanitize the email parameter to prevent SQL injection
            $user_email = $conn->real_escape_string($_GET["email"]);

            // Prepare the statement
            $stmt = $conn->prepare("UPDATE subscribers SET is_subscribed = 0 WHERE email = ?");

            // Bind the email parameter to the prepared statement
            $stmt->bind_param("s", $user_email);

            // Execute the prepared statement
            if ($stmt->execute()) {
               echo '<h1 class="text-center">Sad to see you go!</h1><p class="text-center text-success">&#10003; You have been successfully unsubscribed from CandyBird newsletter emails.<br>
                It can take up to 5 business days for this to go into effect. Thanks for being patient.</p>
                <p class="text-center">Please note: If you use CandyBird services and website or shop online, we will continue to send vital system updates and important information about your account.</p>';


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
                $admin_mail->Subject = "Oh no! User unsubscribed from mailing list on CandyBird";

                // Get the email body for admin from the template file
                $admin_email_body = file_get_contents('emails/email_subscribe_admin.php');

                // Replace placeholders with actual values for admin email
                $admin_email_body = str_replace('{recipient_name}', 'Admin', $admin_email_body);
                $admin_email_body = str_replace('{user_email}', $user_email, $admin_email_body);

                // Set the email body for admin
                $admin_mail->Body = $admin_email_body;

                // Set the email content type to HTML
                $admin_mail->isHTML(true);

                // Send the email to the admin
                if ($admin_mail->send()) {
                    $response = array('success' => true, 'message' => 'Order successful! Admin email sent successfully!');
                } else {
                    $response = array('success' => false, 'message' => 'Order successful, but admin email could not be sent.');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                }

            } else {
                echo "<p class='text-center'>Failed to unsubscribe. Please try again later or contact us.</p>";
            }

            $stmt->close();
            $conn->close();
        } else {
            echo "<p class='text-center'>Invalid unsubscribe link.</p>";
        }
        ?>
    </div>
</div>

<?php
include "footer.php";
?>