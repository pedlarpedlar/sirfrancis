<?php
include 'dbh.inc.php';

// Fetch category data from the database
$sql = "SELECT id, parent_id, name FROM categories";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Store category data in an array
    $categories = array();
    while ($row = $result->fetch_assoc()) {
        // Format the data as an associative array
        $category = array(
            'id' => $row['id'],
            'parent_id' => $row['parent_id'],
            'name' => $row['name']
        );
        $categories[] = $category;
    }

    // Return category data as JSON
    echo json_encode($categories);
} else {
    echo json_encode(["response" => "No categories found"]);
}

$conn->close();
