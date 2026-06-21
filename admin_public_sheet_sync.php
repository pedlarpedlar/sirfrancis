<?php
date_default_timezone_set('Africa/Johannesburg');

include __DIR__ . '/session_logins.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Admin login required.'
    ]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Use POST to sync sheets.'
    ]);
    exit;
}

require_once __DIR__ . '/product_sheet_helpers.php';
require_once __DIR__ . '/wholesale_pricelist_helpers.php';

function sfPublicSheetSyncClearCacheFiles() {
    foreach ([
        __DIR__ . '/sheet_cache/products_json_with_reviews.json',
        __DIR__ . '/sheet_cache/homepage_products.json',
        __DIR__ . '/sheet_cache/homepage_products_v3.json',
        __DIR__ . '/sheet_cache/menu_categories_v3.json',
    ] as $cacheFile) {
        if (is_file($cacheFile)) {
            @unlink($cacheFile);
        }
    }
}

$summary = [
    'success' => false,
    'message' => '',
    'sheets' => [],
    'product_mirror' => null,
];

try {
    sfPublicSheetSyncClearCacheFiles();

    $products = getSheetProducts(true);
    $summary['sheets']['products'] = count($products);
    $summary['sheets']['coupons'] = count(getSheetCoupons(true));
    $summary['sheets']['clearance'] = count(getSheetClearanceRows(true));
    $summary['sheets']['wholesale'] = count(getCandybirdWholesaleRows(true));

    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $summary['product_mirror'] = syncSheetProductsMirrorToDb($conn, true, false);
    } else {
        $summary['product_mirror'] = [
            'success' => false,
            'errors' => ['Database connection is not available, so only sheet caches were refreshed.']
        ];
    }

    sfPublicSheetSyncClearCacheFiles();

    $mirrorOk = !empty($summary['product_mirror']['success']);
    $summary['success'] = $mirrorOk;
    $summary['message'] = $mirrorOk
        ? 'All sheets synced and products were mirrored to the website.'
        : 'Sheets refreshed, but the product database mirror needs attention.';

    echo json_encode($summary);
} catch (Throwable $e) {
    error_log('Sir Francis public admin sheet sync failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sheet sync failed. Please check the server error log.',
    ]);
}
