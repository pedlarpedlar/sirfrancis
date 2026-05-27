<?php
require_once __DIR__ . '/product_sheet_helpers.php';

$query = trim($_GET['query'] ?? $_GET['search'] ?? '');
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 8;
$limit = max(1, min(20, $limit));

$results = [];
foreach (searchSheetProducts($query, $limit) as $product) {
    $price = getSheetProductPrice($product);
    $originalPrice = candybirdParseSheetMoney($product['price'] ?? $price);
    $discountAmount = max(0, $originalPrice - $price);
    $results[] = [
        'id' => $product['id'] ?? '',
        'name' => $product['name'] ?? $product['title'] ?? '',
        'size' => $product['size'] ?? $product['weight'] ?? '',
        'price' => $price,
        'original_price' => $originalPrice,
        'discounted_price' => $price,
        'discount_amount' => $discountAmount,
        'discount_rate' => $originalPrice > 0 && $discountAmount > 0 ? round(($discountAmount / $originalPrice) * 100, 2) : 0,
        'is_clearance' => $product['is_clearance'] ?? 'no',
        'clearance_id' => $product['clearance_id'] ?? '',
        'image_url' => getSheetProductImage($product),
        'category' => implode(' / ', array_filter([
            $product['parent_category'] ?? '',
            $product['child_category_1'] ?? '',
            $product['child_category_2'] ?? '',
        ])),
        'url' => getSheetProductUrl($product),
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'query' => $query,
    'count' => count($results),
    'results' => $results,
]);
?>
