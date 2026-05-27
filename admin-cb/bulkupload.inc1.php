<?php
include 'dbh.inc.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== false) {

        $firstRow = true; // Flag to skip the first row

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if ($firstRow) {
                $firstRow = false;
                continue;
            }

            // Print out $data to debug the structure
            var_dump($data);

            // Ensure data array has enough elements
            if (count($data) < 17) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid row format.']);
                continue;
            }

            // Extract fields from CSV data dynamically
            $product_id = $data[0] ?? '';
            $title = $data[1] ?? '';
            $category_id = $data[2] ?? '';
            $price = $data[3] ?? '';
            $discount_rate = $data[4] ?? '';
            $tax_rate = $data[5] ?? '';
            $tax_amount = $data[6] ?? '';
            $description = $data[7] ?? '';
            $weight = $data[8] ?? '';
            $product_group = $data[9] ?? '';
            $dimensions = $data[10] ?? '';
            $other_info = $data[11] ?? '';
            $product_label = $data[12] ?? '';
            $product_label_exp = $data[13] ?? '';
            $related_products = $data[14] ?? '';
            $delete = $data[15] ?? '';
            $img_urls_csv = $data[16] ?? '';

            // Check for "delete" under product ID
            if (strtolower($delete) === 'yes') {
                $delete_sql = "DELETE FROM product WHERE id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("i", $product_id);
                
                if (!$delete_stmt->execute()) {
                    echo json_encode(['status' => 'error', 'message' => 'Error deleting product ID ' . $product_id . ': ' . $delete_stmt->error]);
                } else {
                    echo json_encode(['status' => 'success', 'message' => 'Product ID ' . $product_id . ' deleted successfully.']);
                }

                $delete_stmt->close();
                continue;
            }

            // Ensure numeric fields or default to 0
            $price = is_numeric($price) ? floatval($price) : 0;
            $discount_rate = is_numeric($discount_rate) ? floatval($discount_rate) : 0;
            $tax_rate = is_numeric($tax_rate) ? floatval($tax_rate) : 0;
            $product_group = is_numeric($product_group) ? floatval($product_group) : null;
            $discount_amount = 0;

            if ($discount_rate > 0) {
                $discount_amount = ($price * $discount_rate) / 100;
            }

            // Validate and set default values
            $description = !empty($description) ? $description : '';
            $weight = !empty($weight) ? $weight : 0;
            $dimensions = !empty($dimensions) ? $dimensions : '';
            $other_info = !empty($other_info) ? $other_info : '';
            $product_label = !empty($product_label) ? $product_label : '';
            $related_products = !empty($related_products) ? $related_products : '';

            // Validate and format product_label_exp
            if (!empty($product_label_exp)) {
                $product_label_exp = date('Y-m-d H:i:s', strtotime($product_label_exp));
            } else {
                $product_label_exp = null;
            }

            // Handle image URLs
            $img_urls = explode(',', $img_urls_csv); // Assuming CSV has comma-separated URLs

            // Check if both product_id and title are empty
            if (empty($product_id) && empty($title)) {
                // Log or skip empty rows
                echo json_encode(['status' => 'error', 'message' => 'Skipping empty row.']);
                continue;
            }

            // Check if this row is for an existing or new product
            if (!empty($product_id)) {
                // Product exists, handle updates
                $check_sql = "SELECT id FROM product WHERE id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $product_id);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows > 0) {
                    // Update existing product
                    $update_sql = "UPDATE product SET title=?, category_id=?, price=?, discount_rate=?, discount_amount=?, tax_rate=?, tax_amount=?, description=?, weight=?, product_group=?, dimensions=?, other_info=?, label=?, product_label_exp=?, related_products=? WHERE id=?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("siddddsssssssssi", $title, $category_id, $price, $discount_rate, $discount_amount, $tax_rate, $tax_amount, $description, $weight, $product_group, $dimensions, $other_info, $product_label, $product_label_exp, $related_products, $product_id);

                    // Update images for existing product
                    updateImages($conn, $product_id, $img_urls);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Product ID ' . $product_id . ' does not exist.']);
                }

                $check_stmt->close();
            } else {
                // Insert new product
                $insert_sql = "INSERT INTO product (title, category_id, price, discount_rate, discount_amount, tax_rate, tax_amount, description, weight, product_group, dimensions, other_info, label, product_label_exp, related_products) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("siddddsssssssss", $title, $category_id, $price, $discount_rate, $discount_amount, $tax_rate, $tax_amount, $description, $weight, $product_group, $dimensions, $other_info, $product_label, $product_label_exp, $related_products);

                if ($stmt->execute()) {
                    $product_id = $stmt->insert_id;
                    echo json_encode(['status' => 'success', 'message' => 'Product inserted successfully with ID ' . $product_id]);

                    // Insert images for new product
                    insertImages($conn, $product_id, $img_urls);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error inserting product: ' . $stmt->error]);
                }
            }

            $stmt->close();
        }
        fclose($handle);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: Could not open file.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error: No file uploaded.']);
}

// Function to insert images
function insertImages($conn, $product_id, $img_urls) {
    foreach ($img_urls as $img_url) {
        $insert_img_sql = "INSERT INTO images (product_id, image_url) VALUES (?, ?)";
        $insert_img_stmt = $conn->prepare($insert_img_sql);
        $insert_img_stmt->bind_param("is", $product_id, $img_url);
        
        if (!$insert_img_stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Error inserting image URL for product ID ' . $product_id . ': ' . $insert_img_stmt->error]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Image URL inserted successfully for product ID ' . $product_id]);
        }

        $insert_img_stmt->close();
    }
}

// Function to update images
function updateImages($conn, $product_id, $img_urls) {
    foreach ($img_urls as $img_url) {
        // Check if image URL already exists
        $check_img_sql = "SELECT id FROM images WHERE product_id = ? AND image_url = ?";
        $check_img_stmt = $conn->prepare($check_img_sql);
        $check_img_stmt->bind_param("is", $product_id, $img_url);
        $check_img_stmt->execute();
        $check_img_stmt->store_result();

        if ($check_img_stmt->num_rows > 0) {
            // Image URL exists, update if needed
            // Example: UPDATE images SET image_url = ? WHERE product_id = ? AND image_url = ?
        } else {
            // Insert new image URL
            $insert_img_sql = "INSERT INTO images (product_id, image_url) VALUES (?, ?)";
            $insert_img_stmt = $conn->prepare($insert_img_sql);
            $insert_img_stmt->bind_param("is", $product_id, $img_url);
            
            if (!$insert_img_stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Error inserting image URL for product ID ' . $product_id . ': ' . $insert_img_stmt->error]);
            } else {
                echo json_encode(['status' => 'success', 'message' => 'Image URL inserted successfully for product ID ' . $product_id]);
            }

            $insert_img_stmt->close();
        }

        $check_img_stmt->close();
    }
}
?>
