<?php

include 'dbh.inc.php';


// Check if the request is a POST request and if 'productId' is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['productId'])) {
    $productId = $_POST['productId'];


    // Prepare SQL query with joins
    $query = "
        WITH RECURSIVE category_path AS (
            SELECT id, name, parent_id
            FROM categories
            WHERE id = (SELECT category_id FROM product WHERE id = ?)
            UNION ALL
            SELECT c.id, c.name, c.parent_id
            FROM categories c
            INNER JOIN category_path cp ON cp.parent_id = c.id
        )
        SELECT p.*, 
               GROUP_CONCAT(DISTINCT i.image_url) as image_urls,
               (SELECT GROUP_CONCAT(CONCAT(c.id, ':', c.name) ORDER BY cp.parent_id ASC SEPARATOR ' > ')
                FROM category_path cp
                JOIN categories c ON cp.id = c.id) as category_breadcrumb,
               COALESCE(AVG(r.rating), 0) as rating,
               (SELECT GROUP_CONCAT(id) FROM product WHERE enabled = 1 AND product_group = p.product_group AND id <> ?) as other_products_in_group,
               (SELECT GROUP_CONCAT(weight) FROM product WHERE enabled = 1 AND product_group = p.product_group AND id <> ?) as other_weights
        FROM product p
        LEFT JOIN images i ON p.id = i.product_id
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.id = ? AND p.enabled = 1
        GROUP BY p.id;
    ";

    // Prepare and bind parameters
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt === false) {
        // Handle SQL query preparation error
        http_response_code(500);
        echo json_encode(['error' => 'SQL query preparation error']);
        exit();
    }

    mysqli_stmt_bind_param($stmt, "iiii", $productId, $productId, $productId, $productId);

    // Execute query
    mysqli_stmt_execute($stmt);

    // Get result
    $result = mysqli_stmt_get_result($stmt);

    if ($result === false) {
        // Handle SQL query execution error
        http_response_code(500);
        echo json_encode(['error' => 'SQL query execution error']);
        exit();
    }

    // Fetch product details
    $productData = mysqli_fetch_assoc($result);

    if ($productData) {
        // Calculate discount amount if necessary
        $price = $productData['price'];
        $discountRate = $productData['discount_rate'];
        $discountAmount = $productData['discount_amount'];

        if ($discountAmount == 0 && $discountRate > 0) {
            $discountAmount = ($price * $discountRate) / 100;
        }

        $productData['discount_amount'] = $discountAmount;

        // Convert image URLs to an array
        $productData['image_urls'] = !empty($productData['image_urls']) ? explode(',', $productData['image_urls']) : [
            'assets/img/slider/thumb/1.jpg'
        ];

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($productData);
    } else {
        // Product not found
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
    }

    // Close statement and connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    // Invalid request
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}








