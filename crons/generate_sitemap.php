<?php
header('Content-type: application/xml');

$rootDir = dirname(__DIR__);
$liveRoot = '/home/candybirdco/public_html';

if (file_exists($rootDir . '/product_sheet_helpers.php')) {
    require_once $rootDir . '/product_sheet_helpers.php';
} else {
    require_once $liveRoot . '/product_sheet_helpers.php';
}

if (file_exists($rootDir . '/dbh.inc.php')) {
    include $rootDir . '/dbh.inc.php';
} elseif (file_exists($liveRoot . '/dbh.inc.php')) {
    include $liveRoot . '/dbh.inc.php';
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

outputUrl('https://www.fishgelatine.co.za/v2/', 'weekly', '1.0');
outputUrl('https://www.fishgelatine.co.za/v2/products', 'daily', '0.8');
outputUrl('https://www.fishgelatine.co.za/v2/contact', 'monthly', '0.5');
outputUrl('https://www.fishgelatine.co.za/v2/about', 'monthly', '0.5');
outputUrl('https://www.fishgelatine.co.za/v2/gifting', 'weekly', '0.7');
outputUrl('https://www.fishgelatine.co.za/v2/pricelist', 'weekly', '0.7');
outputUrl('https://www.fishgelatine.co.za/v2/wholesale-pricelist', 'monthly', '0.6');
outputUrl('https://www.fishgelatine.co.za/v2/private_labelling', 'monthly', '0.6');
outputUrl('https://www.fishgelatine.co.za/v2/bulk_ordering', 'monthly', '0.6');
outputUrl('https://www.fishgelatine.co.za/v2/delivery_policy', 'monthly', '0.5');
outputUrl('https://www.fishgelatine.co.za/v2/return_policy', 'monthly', '0.5');
outputUrl('https://www.fishgelatine.co.za/v2/policies', 'yearly', '0.5');
outputUrl('https://www.fishgelatine.co.za/v2/terms', 'yearly', '0.4');
outputUrl('https://www.fishgelatine.co.za/v2/privacypolicy', 'yearly', '0.4');
outputUrl('https://www.fishgelatine.co.za/v2/cookie_policy', 'yearly', '0.4');
outputUrl('https://www.fishgelatine.co.za/v2/bankingdetails', 'yearly', '0.3');
outputUrl('https://www.fishgelatine.co.za/v2/global-services', 'monthly', '0.5');
outputUrl('https://www.fishgelatine.co.za/v2/recipes', 'weekly', '0.5');

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
            : 'https://www.fishgelatine.co.za/v2/product?id=' . urlencode($id);
        outputUrl($productUrl, 'weekly', '0.6');
    }
}

foreach (array_keys($categoryLinks) as $category) {
    $categoryUrl = function_exists('getCandybirdCategoryUrl')
        ? getCandybirdCategoryUrl($category, true)
        : 'https://www.fishgelatine.co.za/v2/products?category=' . urlencode($category);
    outputUrl($categoryUrl, 'weekly', '0.7');
}

$recipeFile = $rootDir . '/recipe_posts.php';
if (!file_exists($recipeFile) && is_dir($liveRoot)) {
    $recipeFile = $liveRoot . '/recipe_posts.php';
}

if (file_exists($recipeFile)) {
    include $recipeFile;
    if (isset($blogPosts) && is_array($blogPosts)) {
        foreach ($blogPosts as $post) {
            if (!empty($post['id'])) {
                outputUrl('https://www.fishgelatine.co.za/v2/recipe?id=' . urlencode($post['id']), 'weekly', '0.6');
            }
        }
    }
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
