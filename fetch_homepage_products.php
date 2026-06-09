<?php
include_once __DIR__ . '/product_sheet_helpers.php';
include_once __DIR__ . '/dbh.inc.php';

$requestedIds = isset($_POST['productIds']) && is_array($_POST['productIds']) ? array_map('strval', $_POST['productIds']) : [];
$canUseHomepageCache = empty($requestedIds);
$homepageCacheDir = __DIR__ . '/sheet_cache';
$homepageCacheFile = $homepageCacheDir . '/homepage_products_v2.json';
if ($canUseHomepageCache && is_file($homepageCacheFile) && (time() - filemtime($homepageCacheFile)) < 300) {
    header('Content-Type: application/json');
    readfile($homepageCacheFile);
    exit;
}

$products = getSheetProducts();

if (!empty($requestedIds)) {
    $products = array_filter($products, function ($product) use ($requestedIds) {
        return in_array((string) $product['id'], $requestedIds, true);
    });
}

$monthlySales = [];
if (isset($conn) && $conn instanceof mysqli) {
    $salesResult = $conn->query("SELECT product_id, COALESCE(SUM(quantity), 0) AS qty_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        GROUP BY product_id");
    if ($salesResult) {
        while ($row = $salesResult->fetch_assoc()) {
            $monthlySales[(string) $row['product_id']] = (int) $row['qty_sold'];
        }
    }
}

if (empty($requestedIds)) {
    $products = array_slice($products, 0, 250);
}

$responseProducts = [];

foreach ($products as $product) {
    $price = isset($product['price']) ? (float) $product['price'] : 0;
    $discountedPrice = getSheetProductPrice($product);
    $discountAmount = max(0, $price - $discountedPrice);
    $discountRate = ($price > 0 && $discountAmount > 0) ? round(($discountAmount / $price) * 100, 1) : 0;

    $responseProducts[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'title' => $product['name'],
        'size' => $product['size'] ?? '',
        'weight' => $product['size'] ?? '',
        'price' => $price,
        'label' => $product['label'] ?? '',
        'discount_rate' => $discountRate,
        'discount_amount' => $discountAmount,
        'discounted_price' => $discountedPrice,
        'img_url' => getSheetProductImage($product),
        'image_url' => getSheetProductImage($product),
        'avg_rating' => isset($product['rating']) ? (float) $product['rating'] : 0,
        'parent_category' => $product['parent_category'] ?? '',
        'child_category_1' => $product['child_category_1'] ?? '',
        'child_category_2' => $product['child_category_2'] ?? '',
        'monthly_sales' => $monthlySales[(string) $product['id']] ?? 0,
        'tags' => $product['tags'] ?? $product['tag'] ?? $product['label'] ?? '',
        'homepage_featured' => $product['homepage_featured'] ?? ''
    ];
}

header('Content-Type: application/json');
$payload = json_encode([
    'success' => !empty($responseProducts),
    'products' => $responseProducts,
    'message' => empty($responseProducts) ? 'No products found.' : ''
]);
if ($canUseHomepageCache && $payload) {
    if (!is_dir($homepageCacheDir)) {
        @mkdir($homepageCacheDir, 0755, true);
    }
    if (is_dir($homepageCacheDir) && is_writable($homepageCacheDir)) {
        @file_put_contents($homepageCacheFile, $payload, LOCK_EX);
    }
}
echo $payload;
