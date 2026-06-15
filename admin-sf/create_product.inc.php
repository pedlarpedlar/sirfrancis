<?php
include 'dbh.inc.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Retrieve form data
$product_id = $_POST['product_id'];
$title = $_POST['title'];
$category_id = $_POST['category_id'];
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$discount_rate = isset($_POST['discount_rate']) ? floatval($_POST['discount_rate']) : 0;
$tax_rate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0;
$tax_amount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0;
$description = $_POST['description'];
$weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
$product_group = !empty($_POST['product_group']) ? $_POST['product_group'] : null;
$dimensions = $_POST['dimensions'];
$other_info = $_POST['other_info'];
$images = $_FILES['images'];
$product_label = $_POST['product_label'];
$product_label_exp = $_POST['expiry_date'];

// Calculate discount amount
if ($price > 0 && $discount_rate > 0) {
    $discount_amount = $price * ($discount_rate / 100);
} else {
    $discount_amount = 0;
}


// Check if the product_id is null, blank, or does not exist in the database
if (empty($product_id)) {
    // Product_id is null or blank, perform an insert

    // Insert data into the product table
    $sql_product = "INSERT INTO product (title, category_id, price, discount_rate, discount_amount, description, weight, product_group, dimensions, other_info, tax_rate, tax_amount, label, product_label_exp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("ssssssssssssss", $title, $category_id, $price, $discount_rate, $discount_amount, $description, $weight, $product_group, $dimensions, $other_info, $tax_rate, $tax_amount, $product_label, $product_label_exp);

    if (!$stmt_product->execute()) {
        echo json_encode(['status' => 'error', 'message' => "Error inserting product: " . $stmt_product->error]);
        exit();
    }

    $last_product_id = $stmt_product->insert_id;

    // Handle multiple image uploads
    $targetDirFileSystem = "../uploads/products/"; // Specify your file system upload directory
    $targetDirDatabase = "uploads/products/"; // Specify your database upload directory
    $uploadedImages = [];

    foreach ($images['name'] as $key => $imageName) {
        $targetFileFileSystem = $targetDirFileSystem . basename($imageName);
        $targetFileDatabase = $targetDirDatabase . basename($imageName); // Database path without "../"

        move_uploaded_file($images['tmp_name'][$key], $targetFileFileSystem);
        $uploadedImages[] = $targetFileDatabase;
    }

    // Insert data into the images table
    foreach ($uploadedImages as $image) {
        $sql_images = "INSERT INTO images (product_id, image_url) VALUES (?, ?)";
        $stmt_images = $conn->prepare($sql_images);
        $stmt_images->bind_param("is", $last_product_id, $image);

        if (!$stmt_images->execute()) {
            echo json_encode(['status' => 'error', 'message' => "Error inserting into images: " . $stmt_images->error]);
            exit();
        }

        $stmt_images->close();
    }


    // Return a JSON response for AJAX
    echo json_encode(['status' => 'success', 'message' => 'Product inserted successfully', 'product_id' => $last_product_id]);
    exit();

    $stmt_product->close();

} else {
    // Product_id is not null or blank, check if it exists for an update

    // Check if the product exists before updating
    $check_product_existence = "SELECT id FROM product WHERE id=?";
    $stmt_check_product = $conn->prepare($check_product_existence);
    $stmt_check_product->bind_param("i", $product_id);
    $stmt_check_product->execute();
    $stmt_check_product->store_result();

    if ($stmt_check_product->num_rows === 0) {
        // Product with the specified id does not exist
        echo json_encode(['status' => 'error', 'message' => "Error updating product: Product not found."]);
        exit();
    }

    $stmt_check_product->close();

    // Product exists, perform an update of product details

    // Update data in the product table
    $sql_product = "UPDATE product SET title=?, category_id=?, price=?, discount_rate=?, discount_amount=?, description=?, weight=?, product_group=?, dimensions=?, other_info=?, tax_rate=?, tax_amount=?, label=?, product_label_exp=? WHERE id=?";

    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("sisddsssssissss", $title, $category_id, $price, $discount_rate, $discount_amount, $description, $weight, $product_group, $dimensions, $other_info, $tax_rate, $tax_amount, $product_label, $product_label_exp, $product_id);

    if (!$stmt_product->execute()) {
        echo json_encode(['status' => 'error', 'message' => "Error updating product: " . $stmt_product->error]);
        exit();
    }

    $stmt_product->close();

    // Check if new images are provided before inserting
    if (!empty($images['name'][0])) {
        // Insert new images

        // Handle multiple image uploads
        $targetDir = "../uploads/products/"; // Specify your upload directory
        $uploadedImages = [];

        foreach ($images['name'] as $key => $imageName) {
            $targetFileFileSystem = $targetDir . basename($imageName);
            $targetFileDatabase = "uploads/products/" . basename($imageName); // Database path without "../"

            // Check if the image already exists in the uploads folder
            if (!file_exists($targetFileFileSystem)) {
                move_uploaded_file($images['tmp_name'][$key], $targetFileFileSystem);
            }

            $uploadedImages[] = $targetFileDatabase;
        }

        // Insert data into the images table
        foreach ($uploadedImages as $image) {
            $sql_images = "INSERT INTO images (product_id, image_url) VALUES (?, ?)";
            $stmt_images = $conn->prepare($sql_images);
            $stmt_images->bind_param("is", $product_id, $image);

            if (!$stmt_images->execute()) {
                echo json_encode(['status' => 'error', 'message' => "Error inserting into images: " . $stmt_images->error]);
                exit();
            }

            $stmt_images->close();
        }
    }

    // Return a JSON response for AJAX
    echo json_encode(['status' => 'success', 'message' => 'Product updated successfully', 'product_id' => $product_id]);
    exit();
}


// If execution reaches here, something unexpected happened
echo json_encode(['status' => 'error', 'message' => 'Unexpected error occurred']);
exit();