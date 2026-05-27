<?php
require_once __DIR__ . '/product_sheet_helpers.php';

$cacheDir = __DIR__ . '/sheet_cache';
$cacheFile = $cacheDir . '/products_json_with_reviews.json';
if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
    header("Content-Type: application/json");
    readfile($cacheFile);
    exit;
}

$products = array_values(getSheetProductsWithClearance());

if (file_exists(__DIR__ . '/dbh.inc.php')) {
    include __DIR__ . '/dbh.inc.php';
}

if (isset($conn) && $conn instanceof mysqli) {
    $reviewStats = [];
    $result = $conn->query("SELECT product_id, AVG(rating) AS average_rating, COUNT(*) AS review_count FROM reviews GROUP BY product_id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reviewStats[(string) $row['product_id']] = [
                'rating' => round((float) $row['average_rating'], 1),
                'review_count' => (int) $row['review_count'],
            ];
        }
    }

    foreach ($products as &$product) {
        if (!empty($product['is_clearance']) && $product['is_clearance'] === 'yes') {
            $product['rating'] = 0;
            $product['review_count'] = 0;
            continue;
        }
        $productId = (string) ($product['id'] ?? '');
        if (isset($reviewStats[$productId])) {
            $product['rating'] = $reviewStats[$productId]['rating'];
            $product['review_count'] = $reviewStats[$productId]['review_count'];
        }
    }
    unset($product);
}

header("Content-Type: application/json");
$payload = json_encode($products);
if ($payload) {
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    if (is_dir($cacheDir) && is_writable($cacheDir)) {
        @file_put_contents($cacheFile, $payload, LOCK_EX);
    }
}
echo $payload;
?>
