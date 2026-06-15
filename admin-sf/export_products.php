<?php
include 'dbh.inc.php'; // Include your database connection file

// Query to select products data with image URLs joined
$sql = "SELECT p.id, p.title, p.category_id, p.price, p.discount_rate, p.discount_amount, p.tax_rate, p.tax_amount, 
               p.description, p.weight, p.product_group, p.dimensions, p.other_info, p.label, 
               p.product_label_exp, p.related_products, GROUP_CONCAT(i.image_url SEPARATOR ',') AS img_urls, p.enabled, p.features
        FROM product p
        LEFT JOIN images i ON p.id = i.product_id
        GROUP BY p.id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=products_' . date('Y-m-d_H-i-s') . '.csv');

    // Initialize CSV file handle or buffer
    $fp = fopen('php://output', 'w');

    // Write CSV headers
    // fputcsv($fp, [
    //     'product id [Leave blank to create a new product or type in existing product ID to edit]',
    //     'product title',
    //     'category id',
    //     'price',
    //     'discount rate [Percentage]',
    //     'discount amount [Leave blank]',
    //     'tax rate',
    //     'tax amount',
    //     'description [Use HTML and inline CSS if desired]',
    //     'weight',
    //     'product group',
    //     'dimensions',
    //     'other info',
    //     'label [Use sparingly. Some ideas: New - Featured - Hot]',
    //     'product label expiry date [Format: yyyy-mm-dd. This indicates how long you wish to keep the label on your product]',
    //     'related products [comma separated list of related product ids]',
    //     'image urls [comma separated image urls. Use external links or upload images to your website gallery and copy urls]',
    //     'features or properties [comma separated properties. Use a maximum of 3 words to maintain easy filtering for customers]',
    //     'enabled [Type 1 to publish this product to live view. Type 0 to hide product indefinitely.]',
    //     'delete [type YES to delete this row permanently]'
    // ]);

    fputcsv($fp, [
        'id',
        'title',
        'category_id',
        'price',
        'discount_rate',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'description',
        'weight',
        'product_group',
        'dimensions',
        'other_info',
        'features',
        'label',
        'product_label_exp',
        'related_products',
        'enabled',
        'image_urls',
        'delete',
        'set_end'
    ]);


    // Fetch rows and write to CSV
    while ($row = $result->fetch_assoc()) {

        // Format product_label_exp to yyyy-mm-dd

        if (!empty($row['product_label_exp'])) {
            try {
                // Create a new DateTime object
                $date = new DateTime($row['product_label_exp']);
                // Format the date to 'Y-m-d'
                $formatted_date = $date->format('Y-m-d');
                // Encase the formatted date in double quotation marks
                $product_label_exp = '"' . $formatted_date . '"';
            } catch (Exception $e) {
                // If there is an error in date conversion, leave as empty
                $product_label_exp = '';
            }
        } else {
            // If 'product_label_exp' is empty, leave as empty
            $product_label_exp = '';
        }


        // Prepare data array with properly formatted fields
        $data = [
            $row['id'],
            $row['title'],
            $row['category_id'],
            $row['price'],
            $row['discount_rate'],
            $row['discount_amount'],
            $row['tax_rate'],
            $row['tax_amount'],
            '"' . str_replace('"', '', $row['description']) . '"',
            $row['weight'],
            $row['product_group'],
            $row['dimensions'],
            '"' . str_replace('"', '', $row['other_info']) . '"',
            '"' . str_replace('"', '', $row['features']) . '"',
            $row['label'],
            $product_label_exp,
            '"' . str_replace('"', '', $row['related_products']) . '"',
            $row['enabled'],
            '"' . str_replace('"', '', $row['img_urls']) . '"',
            0, // Leave delete field blank initially
            "end" //important
        ];


        // Write row to CSV
        fputcsv($fp, $data);
    }

    // Close file handle or output buffer
    fclose($fp);
    exit();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No products found.']);
    exit();
}

// Close database connection
$conn->close();
?>
