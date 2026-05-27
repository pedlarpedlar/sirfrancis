<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    include 'dbh.inc.php';

    // Get form data
    $c_id = !empty($_POST['c_id']) ? $_POST['c_id'] : NULL;
    $c_name = $_POST['c_name'];
    $c_parent = !empty($_POST['c_parent']) ? $_POST['c_parent'] : NULL;

    // Check if parent_id is the same as the category id
    if ($c_id !== NULL && $c_id == $c_parent) {
        $response = ['status' => 'error', 'message' => 'Parent category cannot be the same as the category itself'];
        echo json_encode($response);
        exit(); // Stop further execution
    }

    // Perform the database query (replace with your SQL query)
    if (empty($c_id)) {
        // Insert new category
        $sql = "INSERT INTO categories (name, parent_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $c_name, $c_parent);
    } else {
        // Update existing category
        $sql = "UPDATE categories SET name=?, parent_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $c_name, $c_parent, $c_id);
    }

    // Execute the statement
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Category submitted successfully'];
    } else {
        $response = ['status' => 'error', 'message' => 'Error submitting category: ' . $stmt->error];
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Return the JSON response
    echo json_encode($response);
} else {
    // Handle the case where the form is not submitted
    echo json_encode(['status' => 'error', 'message' => 'Form not submitted']);
}
?>
