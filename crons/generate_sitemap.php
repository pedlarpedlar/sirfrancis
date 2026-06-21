<?php
header('Content-type: application/xml');

$rootDir = dirname(__DIR__);

require_once $rootDir . '/product_sheet_helpers.php';

if (file_exists($rootDir . '/dbh.inc.php')) {
    include $rootDir . '/dbh.inc.php';
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$seenUrls = [];

function outputUrl($loc, $changefreq, $priority) {
    global $seenUrls;
    if (isset($seenUrls[$loc])) {
        return;
    }
    $seenUrls[$loc] = true;

    echo '<url>';
    echo '<loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . '</loc>';
    echo '<changefreq>' . htmlspecialchars($changefreq, ENT_XML1, 'UTF-8') . '</changefreq>';
    echo '<priority>' . htmlspecialchars($priority, ENT_XML1, 'UTF-8') . '</priority>';
    echo '</url>';
}

outputUrl(sirFrancisSiteUrl('/'), 'weekly', '1.0');
outputUrl(sirFrancisSiteUrl('products'), 'daily', '0.8');
outputUrl(sirFrancisSiteUrl('contact'), 'monthly', '0.5');
outputUrl(sirFrancisSiteUrl('about'), 'monthly', '0.5');
outputUrl(sirFrancisSiteUrl('gifting'), 'weekly', '0.7');
outputUrl(sirFrancisSiteUrl('pricelist'), 'weekly', '0.7');
outputUrl(sirFrancisSiteUrl('wholesale-pricelist'), 'monthly', '0.6');
outputUrl(sirFrancisSiteUrl('private_labelling'), 'monthly', '0.6');
outputUrl(sirFrancisSiteUrl('bulk_ordering'), 'monthly', '0.6');
outputUrl(sirFrancisSiteUrl('delivery_policy'), 'monthly', '0.5');
outputUrl(sirFrancisSiteUrl('return_policy'), 'monthly', '0.5');
outputUrl(sirFrancisSiteUrl('policies'), 'yearly', '0.5');
outputUrl(sirFrancisSiteUrl('terms'), 'yearly', '0.4');
outputUrl(sirFrancisSiteUrl('privacypolicy'), 'yearly', '0.4');
outputUrl(sirFrancisSiteUrl('cookie_policy'), 'yearly', '0.4');
outputUrl(sirFrancisSiteUrl('bankingdetails'), 'yearly', '0.3');
outputUrl(sirFrancisSiteUrl('global-services'), 'monthly', '0.5');

$categoryLinks = [];
$products = function_exists('getSheetProductsWithClearance') ? getSheetProductsWithClearance() : getSheetProducts();
foreach ($products as $product) {
    foreach (['parent_category', 'child_category_1', 'child_category_2'] as $field) {
        $category = trim((string) ($product[$field] ?? ''));
        if ($category !== '') {
            $categoryLinks[$category] = true;
        }
    }

    $id = trim((string) ($product['id'] ?? ''));
    if ($id !== '') {
        $productUrl = function_exists('getSheetProductUrl')
            ? getSheetProductUrl($product, true)
            : sirFrancisSiteUrl('product-' . rawurlencode($id));
        outputUrl($productUrl, 'weekly', '0.6');
    }
}

foreach (array_keys($categoryLinks) as $category) {
    $categoryUrl = function_exists('getCandybirdCategoryUrl')
        ? getCandybirdCategoryUrl($category, true)
        : sirFrancisSiteUrl(normalizeCandybirdProductSlug($category) ?: 'products');
    outputUrl($categoryUrl, 'weekly', '0.7');
}

if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("INSERT INTO cronjobs (job_name, description) VALUES (?, ?)");
    if ($stmt) {
        $jobName = 'generate_sitemap.php';
        $description = 'Sheet product sitemap generated successfully';
        $stmt->bind_param('ss', $jobName, $description);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

echo '</urlset>';
?>
