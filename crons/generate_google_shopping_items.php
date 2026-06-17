<?php
date_default_timezone_set('Africa/Johannesburg');

$rootDir = dirname(__DIR__);

require_once $rootDir . '/product_sheet_helpers.php';

if (file_exists($rootDir . '/dbh.inc.php')) {
    include $rootDir . '/dbh.inc.php';
}

$feedDir = $rootDir . '/uploads/google_products';

if (!is_dir($feedDir)) {
    @mkdir($feedDir, 0755, true);
}

if (!is_dir($feedDir)) {
    throw new RuntimeException('Google Shopping feed folder does not exist and could not be created: ' . $feedDir);
}

if (!is_writable($feedDir)) {
    throw new RuntimeException('Google Shopping feed folder is not writable: ' . $feedDir);
}

$feedFile = $feedDir . '/google_shopping_feed.txt';
$file = fopen($feedFile, 'w');
if (!$file) {
    $lastError = error_get_last();
    $message = $lastError['message'] ?? 'Unknown file open error.';
    throw new RuntimeException('Could not open Google Shopping feed file: ' . $feedFile . ' | ' . $message);
}

$headers = [
    'id', 'title', 'description', 'availability', 'availability date', 'shipping_weight', 'expiration date', 'link',
    'mobile link', 'image link', 'price', 'sale price', 'sale price effective date', 'identifier exists',
    'gtin', 'mpn', 'brand', 'product highlight', 'product detail', 'additional image link', 'condition',
    'adult', 'color', 'size', 'size type', 'size system', 'gender', 'material', 'pattern', 'age group',
    'multipack', 'is bundle', 'unit pricing measure', 'unit pricing base measure', 'energy efficiency class',
    'min energy efficiency class', 'max energy efficiency class', 'item group id', 'sell on google quantity'
];
fputcsv($file, $headers, "\t");

$products = getSheetProducts();

foreach ($products as $product) {
    $id = trim((string) ($product['id'] ?? ''));
    if ($id === '') {
        continue;
    }

    $name = trim((string) ($product['name'] ?? $product['title'] ?? 'Sir Francis product'));
    $size = trim((string) ($product['size'] ?? $product['weight'] ?? ''));
    $title = trim($name . ' ' . $size);
    $description = trim(strip_tags((string) ($product['html_description'] ?? $product['description'] ?? $title)));
    $price = (float) ($product['price'] ?? 0);
    $salePrice = getSheetProductPrice($product);
    $images = array_values(array_filter(array_map('trim', explode(',', (string) ($product['img_url'] ?? $product['image_url'] ?? $product['image_urls'] ?? '')))));
    $imageLink = getCandybirdAbsoluteImageUrl($images[0] ?? getSheetProductImage($product));
    $additionalImages = [];

    foreach (array_slice($images, 1) as $image) {
        $additionalImages[] = getCandybirdAbsoluteImageUrl($image);
    }

    $link = function_exists('getSheetProductUrl')
        ? getSheetProductUrl($product, true)
        : 'https://www.fishgelatine.co.za/v2/' . rawurlencode(getSheetProductSlug($product));
    $itemGroupId = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));

    $values = [
        $id,
        $title,
        $description,
        'in_stock',
        '',
        $size,
        '',
        $link,
        $link,
        $imageLink,
        number_format($price, 2, '.', '') . ' ZAR',
        ($salePrice > 0 && $salePrice < $price) ? number_format($salePrice, 2, '.', '') . ' ZAR' : '',
        '',
        'no',
        '',
        $id,
        'Sir Francis',
        trim((string) ($product['label'] ?? '')),
        trim((string) ($product['parent_category'] ?? '')),
        implode(',', $additionalImages),
        'new',
        'no',
        '',
        $size,
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        $size,
        '',
        '',
        '',
        '',
        $itemGroupId,
        ''
    ];

    fputcsv($file, $values, "\t");
}

fclose($file);

if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("INSERT INTO cronjobs (job_name, description) VALUES (?, ?)");
    if ($stmt) {
        $jobName = 'generate_google_shopping_items.php';
        $description = 'Sheet product feed generated: uploads/google_products/google_shopping_feed.txt (' . count($products) . ' products)';
        $stmt->bind_param('ss', $jobName, $description);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}
?>
