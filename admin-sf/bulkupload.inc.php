<?php
header('Content-Type: application/json');
require_once('dbh.inc.php');

// Headers for the new CSV file
$headers = ['product_id', 'title', 'category_id', 'price', 'discount_rate', 'discount_amount', 'tax_rate', 'tax_amount', 'description', 'weight', 'product_group', 'dimensions', 'other_info', 'features', 'label', 'product_label_exp', 'related_products', 'enabled', 'image_urls', 'delete'];

// Collect skipped and unsuccessful rows
$skipped_rows = [];
$errors = [];

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['status'] = 'error';
        $response['message'] = 'File upload error: ' . $file['error'];
        echo json_encode($response);
        exit;
    }

    // Validate file type
    $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($fileType != 'csv') {
        $response['status'] = 'error';
        $response['message'] = 'Invalid file type. Only CSV files are allowed.';
        echo json_encode($response);
        exit;
    }

    // Move uploaded file to a temporary location
    $tmpName = $file['tmp_name'];
    $filename = 'uploads/' . basename($file['name']);
    if (!move_uploaded_file($tmpName, $filename)) {
        $response['status'] = 'error';
        $response['message'] = 'Error moving uploaded file.';
        echo json_encode($response);
        exit;
    }

    // Function to read and parse the CSV file
    function readCSV($filename) {
        $rows = array();
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Remove the end marker if it exists
                if (!empty($data) && end($data) === 'END') {
                    // array_pop($data); // Remove the last element (END)
                }
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }

    // Read the CSV file
    $csvData = readCSV($filename);

    // Skip the header row (assuming the first row contains column names)
    array_shift($csvData);

    $batchSize = 50;
    $totalRows = count($csvData);
    $inserted = 0;
    $updated = 0;
    $deleted = 0;
    $errors = array();
    $details = array();

    foreach ($csvData as $index => $row) {
        $index = $index + 2;
        try {

            // Check for "END" or "end" in row[20]
            if (!isset($row[20]) || strtolower($row[20]) != 'end') {
                $rowcontent = [];
                $rowcontent_csv = [];
                for ($i = 0; $i < 20; $i++) {
                    $rowcontent["Row $i"] = isset($row[$i]) ? $row[$i] : "NULL";
                    $rowcontent_csv[$headers[$i]] = isset($row[$i]) ? $row[$i] : "NULL";
                }
                $skipped_rows[] = $rowcontent_csv;
                $details[] = "Skipped row number " . ($index) . " with contents:\n" . json_encode($rowcontent, JSON_PRETTY_PRINT) . "\n";
                continue; // Skip this row
            }


            // Start transaction
            $conn->begin_transaction();

            // Extract data from the CSV row
            $product_id = isset($row[0]) ? $row[0] : 'not set';
            $title = isset($row[1]) ? $row[1] : 'not set';
            $category_id = isset($row[2]) ? $row[2] : 'not set';
            $price = isset($row[3]) ? $row[3] : 'not set';
            $discount_rate = isset($row[4]) ? $row[4] : 'not set';
            $discount_amount = isset($row[5]) ? $row[5] : 'not set';
            $tax_rate = isset($row[6]) ? $row[6] : 'not set';
            $tax_amount = isset($row[7]) ? $row[7] : 'not set';
            $description = isset($row[8]) ? str_replace('"', '', $row[8]) : 'not set';
            $weight = isset($row[9]) ? $row[9] : 'not set';
            $product_group = isset($row[10]) ? $row[10] : 'not set';
            $dimensions = isset($row[11]) ? $row[11] : 'not set';
            $other_info = isset($row[12]) ? str_replace('"', '', $row[12]) : '';
            $features = isset($row[13]) ? str_replace('"', '', $row[13]) : '';
            $label = isset($row[14]) ? $row[14] : '';
            $product_label_exp = isset($row[15]) ? $row[15] : null;
            $related_products = isset($row[16]) ? $row[16] : '';
            $enabled = isset($row[17]) ? $row[17] : 0;
            $image_urls = isset($row[18]) ? str_replace('"', '', $row[18]) : '';
            $delete = isset($row[19]) ? $row[19] : 0;

            // Validate and sanitize other fields if necessary
            $price = is_numeric($price) ? $price : 0;
            $discount_rate = is_numeric($discount_rate) ? $discount_rate : 0;
            $discount_amount = is_numeric($discount_amount) ? $discount_amount : 0;
            $tax_rate = is_numeric($tax_rate) ? $tax_rate : 0;
            $tax_amount = is_numeric($tax_amount) ? $tax_amount : 0;
            $product_group = is_numeric($product_group) ? $product_group : null;
            $enabled = is_numeric($enabled) ? $enabled : 0;


            if (!empty($row[15])) {
                try {
                    $product_label_exp = (new DateTime($row[15]))->format('Y-m-d');
                } catch (Exception $e) {
                    $errors[] = "Invalid date format for product_label_exp in product with title '$title'. Please use yyyy-mm-dd.";
                    $details[] = "Row for product '$title': Invalid date format.";
                    $skipped_rows[] = $row;
                    continue; // Skip this row
                }
            } else {
                $product_label_exp = null;
            }


            // Check if it's a new product based on indicator (e.g., product_id = 0 or product_id = 'new')
            $is_new_product = false;
            if (empty($product_id) || strtolower($product_id) === 'new') {
                $is_new_product = true;
            }

            if ($is_new_product) {
                // Insert new product
                $stmt = $conn->prepare("INSERT INTO product (title, category_id, price, discount_rate, discount_amount, tax_rate, tax_amount, description, weight, product_group, dimensions, other_info, label, product_label_exp, related_products, features, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siddiddssssssssss", $title, $category_id, $price, $discount_rate, $discount_amount, $tax_rate, $tax_amount, $description, $weight, $product_group, $dimensions, $other_info, $label, $product_label_exp, $related_products, $features, $enabled);
            } else {
                // Check if the product ID exists in the database
                $check_stmt = $conn->prepare("SELECT id FROM product WHERE id = ?");
                $check_stmt->bind_param("i", $product_id);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows > 0) {
                    // Handle deletion if required
                    if (strcasecmp($delete, 'yes') == 0) {
                        $delete_stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
                        $delete_stmt->bind_param("i", $product_id);
                        if ($delete_stmt->execute()) {
                            $deleted++;
                            $details[] = "Row for product ID $product_id: Deleted successfully.";

                            // Commit the transaction after deletion
                            $conn->commit();
                            continue;  // Skip to the next row after deletion
                        } else {
                            $errors[] = "Error deleting product with ID $product_id: " . $delete_stmt->error;
                            $details[] = "Row for product ID $product_id: Error deleting.";
                            // Rollback the transaction on error
                            $conn->rollback();
                            continue;  // Skip this row after error
                        }
                    }

                    // Update existing product
                    $stmt = $conn->prepare("UPDATE product SET title = ?, category_id = ?, price = ?, discount_rate = ?, discount_amount = ?, tax_rate = ?, tax_amount = ?, description = ?, weight = ?, product_group = ?, dimensions = ?, other_info = ?, label = ?, product_label_exp = ?, related_products = ?, features = ?, enabled = ? WHERE id = ?");
                    $stmt->bind_param("siddiddssssssssssi", $title, $category_id, $price, $discount_rate, $discount_amount, $tax_rate, $tax_amount, $description, $weight, $product_group, $dimensions, $other_info, $label, $product_label_exp, $related_products, $features, $enabled, $product_id);
                } else {
                    $errors[] = "Product ID $product_id not found.";
                    $details[] = "Row for product ID $product_id: Product not found.";
                    // Rollback the transaction if product ID is not found
                    $conn->rollback();
                    continue;  // Skip this row if the product ID is not found
                }
            }

            if ($stmt->execute()) {
                if ($is_new_product) {

                    $product_id = $is_new_product ? $stmt->insert_id : $product_id;

                    $inserted++;
                    $details[] = "Row $index for product '$title': Inserted successfully.";
                } else {
                    $updated++;
                    $details[] = "Row $index for product ID $product_id: Updated successfully.";
                }

                // Delete existing images for the product ID before inserting new ones
                $delete_stmt = $conn->prepare("DELETE FROM images WHERE product_id = ?");
                $delete_stmt->bind_param("i", $product_id);
                if (!$delete_stmt->execute()) {
                    $errors[] = "Error deleting existing images for product ID $product_id: " . $delete_stmt->error;
                    $details[] = "Row for product ID $product_id: Error deleting existing images.";
                }
                $delete_stmt->close();
                
                // Process images
                $imageUrlsArray = array_filter(explode(',', $image_urls), 'trim'); // Remove empty values and trim whitespace
                if (count($imageUrlsArray) > 0) {
                    $image_stmt = $conn->prepare("INSERT INTO images (product_id, image_url) VALUES (?, ?)");
                    foreach ($imageUrlsArray as $image_url) {
                        $image_stmt->bind_param("is", $product_id, $image_url);
                        if (!$image_stmt->execute()) {
                            $errors[] = "Error inserting image for product ID $product_id: " . $image_stmt->error;
                            $details[] = "Row for product ID $product_id: Error inserting image.";
                        }
                    }
                    $image_stmt->close();
                }

                // Commit the transaction after successful insertion or update
                $conn->commit();
            } else {
                $errors[] = "Error inserting/updating product: " . $stmt->error;
                $details[] = "Row for product ID $product_id: Error inserting/updating.";
                // Rollback the transaction on error
                $conn->rollback();
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "Exception occurred: " . $e->getMessage();
            $details[] = "Row for product '$title': Exception occurred.";
            // Rollback the transaction on exception
            $conn->rollback();
        }
    }


    // Cleanup: Delete the uploaded file
    if (file_exists($filename)) {
        unlink($filename);
    }

    $target_dir = __DIR__ . '/csv_failed/';
    $file_name = 'skipped_rows.csv';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Generate new CSV content
    $csv_content = '';
    $csv_content .= implode(',', $headers) . "\n";
    foreach ($skipped_rows as $skipped_row) {
        $csv_content .= implode(',', $skipped_row) . "\n";
    }

    // Set the file path
    $new_file_path = $target_dir . $file_name;

    // Save the CSV content to the file
    file_put_contents($new_file_path, $csv_content);

    // Prepare the final response
    $response = [];
    $response['file'] = $file_name;
    $response['status'] = 'success';
    $response['inserted'] = $inserted;
    $response['updated'] = $updated;
    $response['deleted'] = $deleted;
    $response['errors'] = $errors;
    $response['details'] = $details;

    // Return the response as JSON
    echo json_encode($response);


} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
}
