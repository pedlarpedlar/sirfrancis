<?php
include 'session_logins.php';
date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2
$pricelist_table = "";

// Fetch all categories
$categoriesQuery = "SELECT id, parent_id, name FROM categories";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];

if ($categoriesResult->num_rows > 0) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[$row['id']] = $row;
    }
}

// Fetch all products
$productsQuery = "SELECT id, title, category_id, price, discount_rate, discount_amount, tax_rate, tax_amount, weight FROM product WHERE enabled = 1";
$productsResult = $conn->query($productsQuery);
$products = [];

if ($productsResult->num_rows > 0) {
    while ($row = $productsResult->fetch_assoc()) {
        $products[$row['category_id']][] = $row;
    }
}

// Fetch all images
$imagesQuery = "SELECT product_id, image_url FROM images";
$imagesResult = $conn->query($imagesQuery);
$productImages = [];

if ($imagesResult->num_rows > 0) {
    while ($row = $imagesResult->fetch_assoc()) {
        $productImages[$row['product_id']] = $row['image_url'];
    }
}

// Helper function to get category name
function getCategoryName($categories, $id) {
    return isset($categories[$id]) ? $categories[$id]['name'] : 'Unknown';
}

// Helper function to group products by title
function groupProductsByTitle($products) {
    $groupedProducts = [];
    foreach ($products as $categoryProducts) {
        foreach ($categoryProducts as $product) {
            $groupedProducts[$product['title']][] = $product;
        }
    }
    return $groupedProducts;
}

// Custom order for weights
$customWeightOrder = [
    'individual' => 0,
    'small' => 1,
    'medium' => 2,
    'large' => 3,
    '20g' => 4,
    '100g' => 5,
    '150g' => 6,
    '200g' => 7,
    '250g' => 8,
    '300g' => 9,
    '350g' => 10,
    '400g' => 11,
    '450g' => 12,
    '500g' => 13,
    '750g' => 14,
    '1kg' => 15,
    // Add more as needed
];

// Custom order for categories
$customCategoryOrder = [
    'Gifting' => 0,
    'Travel Treats' => 1,
    'Nuts' => 2,
    'Peanuts' => 3,
    'Dried Fruit' => 4,
    'Sweets' => 5,
    'Resellers & Wholesale' => 100
    // Add more as needed
];

// Helper function to sort categories based on custom order
function sortCategoriesByCustomOrder($categories, $order) {
    uasort($categories, function($a, $b) use ($order) {
        $posA = isset($order[$a['name']]) ? $order[$a['name']] : PHP_INT_MAX;
        $posB = isset($order[$b['name']]) ? $order[$b['name']] : PHP_INT_MAX;
        return $posA - $posB;
    });
    return $categories;
}

// Helper function to sort weights based on custom order
function sortWeightsByCustomOrder($weights, $order) {
    usort($weights, function($a, $b) use ($order) {
        $posA = isset($order[$a]) ? $order[$a] : PHP_INT_MAX;
        $posB = isset($order[$b]) ? $order[$b] : PHP_INT_MAX;
        return $posA - $posB;
    });
    return $weights;
}

// Group products by title
$groupedProducts = groupProductsByTitle($products);

// Sort categories based on custom order
$categories = sortCategoriesByCustomOrder($categories, $customCategoryOrder);

// Generate HTML Table
$pricelist_table .= "<table border='1' class='table table-striped table-bordered nowrap' style='width:100%'>";

foreach ($categories as $catId => $category) {
    if ($category['parent_id'] == 0) {
        // Collect weights for this category
        $categoryWeights = [];
        foreach ($groupedProducts as $productTitle => $products) {
            $firstProduct = $products[0];
            $belongsToCategory = ($firstProduct['category_id'] == $catId || $categories[$firstProduct['category_id']]['parent_id'] == $catId);

            if ($belongsToCategory) {
                foreach ($products as $product) {
                    if (!in_array($product['weight'], $categoryWeights)) {
                        $categoryWeights[] = $product['weight'];
                    }
                }
            }
        }

        if (empty($categoryWeights)) {
            continue; // Skip category if no weights are found
        }

        $categoryWeights = sortWeightsByCustomOrder($categoryWeights, $customWeightOrder);

        // Display category name as header
        $pricelist_table .= "<tr style='background-color:purple;color:yellow;'><td colspan='" . (count($categoryWeights) + 2) . "' style='font-weight:bold;'>" . htmlspecialchars($category['name']) . "</td></tr>";

        // Display weight headers for each category
        $pricelist_table .= "<tr><td></td>";
        foreach ($categoryWeights as $weight) {
            $pricelist_table .= "<td style='font-weight:bold;'>" . htmlspecialchars($weight) . "</td>";
        }
        $pricelist_table .= "</tr>";

        // List unique products under this category and its child categories
        foreach ($groupedProducts as $productTitle => $products) {
            $firstProduct = $products[0];
            $belongsToCategory = ($firstProduct['category_id'] == $catId || $categories[$firstProduct['category_id']]['parent_id'] == $catId);

            if ($belongsToCategory) {
                $pricelist_table .= "<tr>";

                // Get image URL for the first product
                $imageUrl = "";
                foreach ($products as $product) {
                    if (isset($productImages[$product['id']])) {
                        $imageUrl = $productImages[$product['id']];
                        break;
                    }
                }
                $imageTag = $imageUrl ? "<img src='" . htmlspecialchars($imageUrl) . "' alt='" . htmlspecialchars($productTitle) . "' width='40' height='40'>" : "";

                $pricelist_table .= "<td style='vertical-align: middle;'><span style='display: inline-block; vertical-align: middle; margin-right:5px'>" . $imageTag . "</span>";

                $productLink = "https://www.fishgelatine.co.za/v2/product?id=" . htmlspecialchars($firstProduct['id']);
                
                $pricelist_table .= "<a href='" . $productLink . "' style='vertical-align: middle'>" . htmlspecialchars($productTitle) . "</a></td>";


                foreach ($categoryWeights as $weight) {
                    $productFound = false;
                    foreach ($products as $product) {
                        if ($product['weight'] == $weight) {
                            $pricelist_table .= "<td>" . htmlspecialchars(number_format($product['price'], 2)) . "</td>";
                            $productFound = true;
                            break;
                        }
                    }
                    if (!$productFound) {
                        $pricelist_table .= "<td>-</td>"; // Placeholder if weight is not available
                    }
                }
                $pricelist_table .= "</tr>";
            }
        }
    }
}

$pricelist_table .= "</table>";

echo $pricelist_table;

// Close the connection
$conn->close();
?>