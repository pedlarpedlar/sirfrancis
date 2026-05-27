<?php

header('Content-Type: application/json');
include 'dbh.inc.php';

$response = [];

// Function to validate and process each line
function processLine($line, $conn) {
    global $response;
    
    // Validation logic (implement your own validation rules)
    if (!isset($line['id']) || !is_numeric($line['id'])) {
        $response[] = ["error" => "Invalid product ID"];
        return;
    }

    $productId = intval($line['id']);
    $title = $conn->real_escape_string($line['title']);
    $categoryId = intval($line['category_id']);
    $price = floatval($line['price']);
    $discountRate = floatval($line['discount_rate']);
    $discountAmount = floatval($line['discount_amount']);
    $taxRate = floatval($line['tax_rate']);
    $taxAmount = floatval($line['tax_amount']);
    $description = $conn->real_escape_string($line['description']);
    $weight = $conn->real_escape_string($line['weight']);
    $productGroup = intval($line['product_group']);
    $dimensions = $conn->real_escape_string($line['dimensions']);
    $otherInfo = $conn->real_escape_string($line['other_info']);
    $features = $conn->real_escape_string($line['features']);
    $label = $conn->real_escape_string($line['label']);
    $productLabelExp = $conn->real_escape_string($line['product_label_exp']);
    $relatedProducts = $conn->real_escape_string($line['related_products']);
    $enabled = isset($line['enabled']) ? intval($line['enabled']) : 1;
    $deleted = isset($line['deleted']) ? intval($line['deleted']) : 0;

    // Check if product exists
    $result = $conn->query("SELECT * FROM product WHERE id = $productId");


    if ($result->num_rows > 0) {
        // Update product
        $sql = "UPDATE product SET 
                    title = '$title', 
                    category_id = $categoryId, 
                    price = $price, 
                    discount_rate = $discountRate, 
                    discount_amount = $discountAmount, 
                    tax_rate = $taxRate, 
                    tax_amount = $taxAmount, 
                    description = '$description', 
                    weight = '$weight', 
                    product_group = $productGroup, 
                    dimensions = '$dimensions', 
                    other_info = '$otherInfo', 
                    features = '$features', 
                    label = '$label', 
                    product_label_exp = '$productLabelExp', 
                    related_products = '$relatedProducts', 
                    enabled = $enabled 
                WHERE id = $productId";
    } else {
        // Insert product
        $sql = "INSERT INTO product (title, category_id, price, discount_rate, discount_amount, tax_rate, tax_amount, description, weight, product_group, dimensions, other_info, features, label, product_label_exp, related_products, enabled) 
                VALUES ('$title', $categoryId, $price, $discountRate, $discountAmount, $taxRate, $taxAmount, '$description', '$weight', $productGroup, '$dimensions', '$otherInfo', '$features', '$label', '$productLabelExp', '$relatedProducts', $enabled)";
    }

    if ($conn->query($sql) === TRUE) {
        if (isset($line['image_urls'])) {
            // Delete existing images
            $conn->query("DELETE FROM images WHERE product_id = $productId");
            
            // Insert new images
            $imageUrls = explode(',', $line['image_urls']);
            foreach ($imageUrls as $imageUrl) {
                $imageUrl = $conn->real_escape_string($imageUrl);
                $conn->query("INSERT INTO images (product_id, image_url) VALUES ($productId, '$imageUrl')");
            }
        }
        $response[] = ["success" => "Processed line for product ID $productId"];
    } else {
        $response[] = ["error" => "Error processing line for product ID $productId: " . $conn->error];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");
        $batchSize = 50;
        $batchCounter = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($batchCounter >= $batchSize) {
                sleep(1); // Pause for a moment to prevent server overload
                $batchCounter = 0;
            }
            
            $line = array_combine($header, $data);
            processLine($line, $conn);
            
            $batchCounter++;
        }
        fclose($handle);
    } else {
        $response[] = ["error" => "Error opening the CSV file."];
    }
} else {
    $response[] = ["error" => "No CSV file uploaded."];
}

echo json_encode($response);

$conn->close();
?>