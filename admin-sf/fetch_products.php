<?php

include 'dbh.inc.php';

// Function to fetch product details by product_id
function fetchProductDetailsById($product_id, $conn) {
    // Use prepared statements to prevent SQL injection
    $sql = "SELECT p.*, GROUP_CONCAT(i.image_url) AS image_urls
            FROM product p
            LEFT JOIN images i ON p.id = i.product_id
            WHERE p.id = ?
            GROUP BY p.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Fetch product details as an associative array
    $productDetails = $result->fetch_assoc();

    // Close the statement
    $stmt->close();

    // Convert the image URLs string to an array
    $productDetails['images'] = explode(',', $productDetails['image_urls']);

    return $productDetails;
}

// Get product_id from the URL (you should validate and sanitize this input)
$product_id = $_GET['product_id'];

// Fetch product details by product_id
$productDetails = fetchProductDetailsById($product_id, $conn);

// Close the database connection
$conn->close();

// Return product details as JSON (you can adjust the format as needed)
header('Content-Type: application/json');
echo json_encode($productDetails);
?>