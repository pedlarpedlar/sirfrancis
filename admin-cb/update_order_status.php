<?php
// Include database connection file
include 'dbh.inc.php';

// Check if database connection is successful
if ($conn->connect_error) {
    $response = array(
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    );
    // Set content type to JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
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
    $updatedStatus = $_POST['updatedStatus'];
    $partialNote = cbAdminStatusPartialNote($_POST['partialFulfillment'] ?? '', $_POST['outstandingItems'] ?? '');

    // $response = array(
    //         'status' => 'success',
    //         'message' => 'Order '.$orderId.' status updated to '.$updatedStatus.' successfully.'
    //     );
    // Set content type to JSON
    // header('Content-Type: application/json');
    // echo json_encode($response);
    // exit();

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
            $response = array(
                'status' => 'success',
                'message' => $stmt->affected_rows > 0
                    ? 'Order '.$orderId.' status updated to '.$updatedStatus.' successfully.'
                    : 'No status change was needed. The order is already marked as '.$updatedStatus.'.'
            );
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            // Execution error
            $response = array(
                'status' => 'error',
                'message' => 'Execution error: ' . $stmt->error
            );
            // Set content type to JSON
            header('Content-Type: application/json');
            echo json_encode($response);
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
}

?>
