<?php
session_start(); // Start or resume the PHP session

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$payfastError = '';

// Check if all required parameters are present
if (isset($_GET['source'], $_GET['order'])) {
    // Retrieve and sanitize the parameters
    $source = $_GET['source'];
    $order_id = preg_replace('/\D/', '', (string) $_GET['order']);
        // Validation successful, proceed with checking order existence
        // Connect to your database (assuming you have already included dbh.inc.php or similar)
        include 'dbh.inc.php'; // Adjust the path as per your file structure

        if (!($conn instanceof mysqli)) {
            $payfastError = 'Payment could not be started because the order system is temporarily unavailable.';
        } else {

        $userId = $_SESSION['user_id'] ?? null;
        $guestIdentifier = $_SESSION['guest_identifier'] ?? '';

        // Retrieve the order first. Address data is useful, but it must never hide a real order.
        $stmt = $conn->prepare("
            SELECT o.*, ua.*
            FROM orders AS o
            LEFT JOIN user_addresses AS ua
                ON (
                    (o.user_id IS NOT NULL AND ua.user_id = o.user_id)
                    OR (o.guest_identifier <> '' AND ua.guest_identifier = o.guest_identifier)
                )
            WHERE o.id = ?
                AND (
                    ? IS NOT NULL
                    OR o.user_id = ?
                    OR o.guest_identifier = ?
                    OR ? = 1
                )
        ");
        $guestAccess = 1;
        $userIdForQuery = $userId ?? 0;
        $stmt->bind_param("iiisi", $order_id, $userId, $userIdForQuery, $guestIdentifier, $guestAccess);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Order exists, fetch additional details
            $orderDetails = $result->fetch_assoc();
            $fetched_billing_first_name = $orderDetails['billing_first_name'];
            $fetched_billing_last_name = $orderDetails['billing_last_name'];
            $fetched_billing_email_address = $orderDetails['billing_email_address'] ?: ($_SESSION['email'] ?? '');
            $cartTotal = $orderDetails['grand_total_amount']; // Assuming 'total_amount' is the field name for cart total

            if (empty($fetched_billing_email_address) && preg_match('/Email:\s*([^,\n]+)/i', (string) ($orderDetails['shipping_address'] ?? ''), $emailMatch)) {
                $fetched_billing_email_address = trim($emailMatch[1]);
            }
            if (empty($fetched_billing_first_name) || empty($fetched_billing_last_name)) {
                $addressFirstLine = trim(strtok((string) ($orderDetails['shipping_address'] ?? ''), ",\n"));
                $nameParts = preg_split('/\s+/', $addressFirstLine, 2);
                if (empty($fetched_billing_first_name)) {
                    $fetched_billing_first_name = $nameParts[0] ?? 'Customer';
                }
                if (empty($fetched_billing_last_name)) {
                    $fetched_billing_last_name = $nameParts[1] ?? '';
                }
            }

            if ((int) ($orderDetails['payment_status'] ?? 0) !== 0) {
                $payfastError = 'This order is already marked as paid.';
            } else {
                // Include the payment form
                include 'payNowForm.php';

                // Close the database connection
                $stmt->close();
                $conn->close();

                if (!empty($payNowForm)) {
                    // Output the form and automatically submit it using JavaScript.
                    echo $payNowForm;
                    echo '<script type="text/javascript">
                        document.addEventListener("DOMContentLoaded", function() {
                            var form = document.getElementById("payNowForm");
                            if (form) {
                                form.submit();
                            }
                        });
                    </script>';
                } else {
                    $payfastError = 'Payment could not be started because the order total is missing or invalid.';
                }
            }

        } else {
            // Order does not exist
            $payfastError = "We could not find that order. Please open your order details and try Pay Now again.";
        }
        }

} else {
    // Missing required parameters
    $payfastError = "Payment could not be started because required order details were missing.";
}

if ($payfastError) {
    echo '<p>' . htmlspecialchars($payfastError, ENT_QUOTES, 'UTF-8') . '</p>';
}
echo "Redirecting to secure pay...";
?>
