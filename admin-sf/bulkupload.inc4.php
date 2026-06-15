<?php

include 'dbh.inc.php';

// Function to validate and sanitize CSV data
function validate_csv_row($row) {
    $required_fields = [
        'title', 'category_id', 'price', 'enabled'
    ];

    foreach ($required_fields as $field) {
        if (empty($row[$field])) {
            return false;
        }
    }
    return true;
}

// Handling CSV upload
if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
    
    if ($file === false) {
        die(json_encode([["error" => "Failed to open uploaded file."]]));
    }

    // Read headers
    $headers = fgetcsv($file);
    if ($headers === false) {
        die(json_encode([["error" => "Failed to read CSV headers."]]));
    }

    $responses = [];
    $batch_size = 50;
    $batch = [];

    while (($data = fgetcsv($file)) !== false) {
        $row = array_combine($headers, $data);

        if ($row === false) {
            $responses[] = ["error" => "Invalid CSV format."];
            continue;
        }

        // Validate the row
        if (!validate_csv_row($row)) {
            $responses[] = ["error" => "Invalid data in row: " . json_encode($row)];
            continue;
        }

        $batch[] = $row;

        // Process the batch if it reaches the batch size
        if (count($batch) >= $batch_size) {
            process_batch($conn, $batch, $responses);
            $batch = [];
        }
    }

    // Process any remaining rows in the batch
    if (count($batch) > 0) {
        process_batch($conn, $batch, $responses);
    }

    fclose($file);
    $conn->close();

    echo json_encode($responses);
} else {
    die(json_encode([["error" => "File upload error."]]));
}

// Function to process a batch of rows
function process_batch($conn, $batch, &$responses) {
    foreach ($batch as $row) {
        // Check if product exists
        if (!empty($row['id'])) {
            $stmt = $conn->prepare("SELECT id FROM product WHERE id = ?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();

            if ($exists) {
                // Update existing product
                if (!empty($row['delete']) && $row['delete'] == 1) {
                    $stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
                    $stmt->bind_param("i", $row['id']);
                    $stmt->execute();
                    $stmt->close();

                    $responses[] = ["status" => "deleted", "id" => $row['id']];
                } else {
                    $stmt = $conn->prepare("UPDATE product SET title = ?, category_id = ?, price = ?, discount_rate = ?, discount_amount = ?, tax_rate = ?, tax_amount = ?, description = ?, weight = ?, product_group = ?, dimensions = ?, other_info = ?, features = ?, label = ?, product_label_exp = ?, related_products = ?, enabled = ? WHERE id = ?");
                    $stmt->bind_param("siddddddssissssisi", $row['title'], $row['category_id'], $row['price'], $row['discount_rate'], $row['discount_amount'], $row['tax_rate'], $row['tax_amount'], $row['description'], $row['weight'], $row['product_group'], $row['dimensions'], $row['other_info'], $row['features'], $row['label'], $row['product_label_exp'], $row['related_products'], $row['enabled'], $row['id']);
                    $stmt->execute();
                    $stmt->close();

                    // Update images
                    if (!empty($row['image_urls'])) {
                        $stmt = $conn->prepare("DELETE FROM images WHERE product_id = ?");
                        $stmt->bind_param("i", $row['id']);
                        $stmt->execute();
                        $stmt->close();

                        $image_urls = explode(',', $row['image_urls']);
                        foreach ($image_urls as $url) {
                            $stmt = $conn->prepare("INSERT INTO images (product_id, image_url) VALUES (?, ?)");
                            $stmt->bind_param("is", $row['id'], $url);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }

                    $responses[] = ["status" => "updated", "id" => $row['id']];
                }
            } else {
                $responses[] = ["error" => "Product ID " . $row['id'] . " not found."];
            }
        } else {
            // Insert new product
            $stmt = $conn->prepare("INSERT INTO product (title, category_id, price, discount_rate, discount_amount, tax_rate, tax_amount, description, weight, product_group, dimensions, other_info, features, label, product_label_exp, related_products, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siddddddssissssis", $row['title'], $row['category_id'], $row['price'], $row['discount_rate'], $row['discount_amount'], $row['tax_rate'], $row['tax_amount'], $row['description'], $row['weight'], $row['product_group'], $row['dimensions'], $row['other_info'], $row['features'], $row['label'], $row['product_label_exp'], $row['related_products'], $row['enabled']);
            $stmt->execute();
            $insert_id = $stmt->insert_id;
            $stmt->close();

            // Insert images
            if (!empty($row['image_urls'])) {
                $image_urls = explode(',', $row['image_urls']);
                foreach ($image_urls as $url) {
                    $stmt = $conn->prepare("INSERT INTO images (product_id, image_url) VALUES (?, ?)");
                    $stmt->bind_param("is", $insert_id, $url);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $responses[] = ["status" => "inserted", "id" => $insert_id];
        }
    }
}

?>
