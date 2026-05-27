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

function outputUrl($loc, $changefreq, $priority) {
    echo '<url>';
    echo '<loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . '</loc>';
    echo '<changefreq>' . htmlspecialchars($changefreq, ENT_XML1, 'UTF-8') . '</changefreq>';
    echo '<priority>' . htmlspecialchars($priority, ENT_XML1, 'UTF-8') . '</priority>';
    echo '</url>';
}

outputUrl('https://www.candybird.co.za/', 'weekly', '1.0');
outputUrl('https://www.candybird.co.za/products', 'daily', '0.8');
outputUrl('https://www.candybird.co.za/contact', 'monthly', '0.5');
outputUrl('https://www.candybird.co.za/about', 'monthly', '0.5');
outputUrl('https://www.candybird.co.za/return_policy', 'yearly', '0.3');
outputUrl('https://www.candybird.co.za/bankingdetails', 'yearly', '0.3');
outputUrl('https://www.candybird.co.za/wholesale', 'monthly', '0.5');
outputUrl('https://www.candybird.co.za/global-services', 'monthly', '0.5');

$categoryLinks = [];
foreach (getSheetProducts() as $product) {
    foreach (['parent_category', 'child_category_1', 'child_category_2'] as $field) {
        $category = trim((string) ($product[$field] ?? ''));
        if ($category !== '') {
            $categoryLinks[$category] = true;
        }
    }

    $id = trim((string) ($product['id'] ?? ''));
    if ($id !== '') {
        outputUrl('https://www.candybird.co.za/product?id=' . urlencode($id), 'weekly', '0.6');
    }
}

foreach (array_keys($categoryLinks) as $category) {
    outputUrl('https://www.candybird.co.za/products?category=' . urlencode($category), 'weekly', '0.7');
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
                outputUrl('https://www.candybird.co.za/recipe?id=' . urlencode($post['id']), 'weekly', '0.6');
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
