<?php
require_once __DIR__ . '/../product_sheet_helpers.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    @include_once __DIR__ . '/../dbh.inc.php';
}

if (!function_exists('cbAdminSheetText')) {
    function cbAdminSheetText($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cbAdminSheetClearPublicProductCache')) {
    function cbAdminSheetClearPublicProductCache() {
        foreach ([
            dirname(__DIR__) . '/sheet_cache/products_json_with_reviews.json',
            dirname(__DIR__) . '/sheet_cache/products.tsv',
            dirname(__DIR__) . '/sheet_cache/coupons.tsv',
            dirname(__DIR__) . '/sheet_cache/clearance.tsv',
        ] as $cacheFile) {
            if (is_file($cacheFile)) {
                @unlink($cacheFile);
            }
        }
    }
}

if (!function_exists('cbAdminSheetRefreshSource')) {
    function cbAdminSheetRefreshSource($key) {
        if ($key === 'products') {
            cbAdminSheetClearPublicProductCache();
            $items = getSheetProducts(true);
            return ['ok' => count($items) > 0, 'count' => count($items)];
        }
        if ($key === 'coupons') {
            $items = getSheetCoupons(true);
            return ['ok' => !empty($items), 'count' => count($items)];
        }
        if ($key === 'clearance') {
            cbAdminSheetClearPublicProductCache();
            $items = getSheetClearanceRows(true);
            return ['ok' => true, 'count' => count($items)];
        }
        return ['ok' => false, 'count' => 0];
    }
}

if (!function_exists('cbAdminSheetSaveSingleSource')) {
    function cbAdminSheetSaveSingleSource($key) {
        $sources = getCandybirdSheetSources();
        if (!isset($sources[$key])) {
            return false;
        }
        $sources[$key]['published_url'] = trim((string) ($_POST['published_url'] ?? ''));
        $sources[$key]['edit_url'] = trim((string) ($_POST['edit_url'] ?? ''));
        return saveCandybirdSheetSources([$key => $sources[$key]]);
    }
}

if (!function_exists('cbAdminSheetTemplateRows')) {
    function cbAdminSheetTemplateRows($key) {
        $sources = getCandybirdSheetSources();
        $headers = array_values(array_unique(array_merge(
            $sources[$key]['required_headers'] ?? [],
            $sources[$key]['optional_headers'] ?? []
        )));

        $explainers = [
            'id' => 'Unique sheet product ID. Keep stable forever.',
            'parent_category' => 'Main category shown on the shop.',
            'child_category_1' => 'First child category. Leave blank if none.',
            'child_category_2' => 'Smaller child category. Leave blank if none.',
            'name' => 'Customer-facing product name without repeated size.',
            'price' => 'Normal selling price, e.g. 145.00.',
            'img_url' => 'First product image URL. Extra images can be comma separated.',
            'size' => 'Customer-facing size, e.g. 100g, 1kg, 250ml, 1pc.',
            'discount' => 'Optional discount percent or amount, depending on your pricing setup.',
            'discounted_price' => 'Optional final sale price.',
            'discount_valid_from' => 'Optional date the special starts.',
            'discount_valid_until' => 'Optional date the special ends. Valid through that date.',
            'html_description' => 'HTML product description shown on product page.',
            'product_type' => 'Use digital for vouchers/e-books. Blank means physical.',
            'qty_in_stock' => 'Available stock quantity.',
            'lead_time' => 'Optional customer note, e.g. 2-5 working days.',
            'slug' => 'Clean URL slug, e.g. plain-cashews-1kg.',
            'homepage_featured' => 'yes/no. yes gives this product priority on homepage sections.',
            'shipping_weight' => 'Optional actual shipping weight, e.g. 750g or 0.75kg.',
            'coupon_code' => 'Coupon code customers type at checkout.',
            'valid_from' => 'Coupon/clearance start date.',
            'valid_until' => 'Coupon/clearance end date.',
            'valid_on_sale_items' => 'yes/no. Whether coupon applies to already discounted items.',
            'min_order_value' => 'Minimum product subtotal before shipping.',
            'discount_type' => 'percentage or fixed.',
            'discount_value' => 'Discount amount, e.g. 10 or 100.',
            'valid_count' => 'Total times coupon can be used.',
            'multi_user' => 'yes/no. no means restricted to one customer identity.',
            'email_restriction' => 'Optional allowed email address.',
            'phone_restriction' => 'Optional allowed phone number.',
            'clearance_id' => 'Unique clearance item ID, e.g. CLR-001.',
            'product_id' => 'Original product sheet ID used for image/details fallback.',
            'clearance_price' => 'Clearance selling price.',
            'qty_available' => 'Clearance stock quantity. 0 shows sold out.',
            'clearance_reason' => 'Reason shown/admin note, e.g. dated stock.',
            'clearance_tag' => 'Short label such as CLEARANCE.',
            'clearance_notes' => 'Extra note for clearance item.',
            'clearance_title' => 'Optional override title.',
            'clearance_img_url' => 'Optional override image URL.',
            'clearance_description' => 'Optional override description.',
        ];

        $row2 = [];
        $row3 = [];
        foreach ($headers as $header) {
            $row2[] = $explainers[$header] ?? 'Optional sheet field.';
            if ($key === 'products') {
                $examples = [
                    'id' => '101',
                    'parent_category' => 'Nuts',
                    'child_category_1' => 'Raw Nuts',
                    'child_category_2' => '',
                    'name' => 'Plain Cashews',
                    'price' => '145.00',
                    'img_url' => 'https://www.candybird.co.za/assets/img/product/1.png',
                    'size' => '100g',
                    'discount' => '',
                    'discounted_price' => '',
                    'discount_valid_from' => '',
                    'discount_valid_until' => '',
                    'html_description' => '<p>Fresh, crunchy plain cashews.</p>',
                    'product_type' => '',
                    'qty_in_stock' => '20',
                    'lead_time' => '',
                    'slug' => 'plain-cashews-100g',
                    'homepage_featured' => 'yes',
                    'shipping_weight' => '',
                ];
            } elseif ($key === 'coupons') {
                $examples = [
                    'id' => '1',
                    'coupon_code' => 'PAYDAY',
                    'valid_from' => '01-06-2026',
                    'valid_until' => '30-06-2026',
                    'valid_on_sale_items' => 'no',
                    'min_order_value' => '500',
                    'discount_type' => 'fixed',
                    'discount_value' => '100',
                    'valid_count' => '1',
                    'multi_user' => 'yes',
                    'email_restriction' => '',
                    'phone_restriction' => '',
                ];
            } else {
                $examples = [
                    'clearance_id' => 'CLR-001',
                    'product_id' => '101',
                    'clearance_price' => '75.00',
                    'qty_available' => '10',
                    'slug' => 'clearance-plain-cashews-100g',
                    'clearance_reason' => 'Dated stock',
                    'clearance_tag' => 'CLEARANCE',
                    'clearance_notes' => 'Limited quantity clearance item.',
                    'valid_from' => '',
                    'valid_until' => '',
                    'clearance_title' => '',
                    'clearance_img_url' => '',
                    'clearance_description' => '',
                ];
            }
            $row3[] = $examples[$header] ?? '';
        }

        return [$headers, $row2, $row3];
    }
}

if (!function_exists('cbAdminSheetPage')) {
    function cbAdminSheetPage($key, $title, $introHtml) {
        $sourceKeys = ['products', 'coupons', 'clearance'];
        if (!in_array($key, $sourceKeys, true)) {
            http_response_code(404);
            echo 'Unknown sheet page.';
            return;
        }

        $message = '';
        $success = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['sheet_action'] ?? '';
            if ($action === 'save_source') {
                $success = cbAdminSheetSaveSingleSource($key);
                if ($success) {
                    cbAdminSheetClearPublicProductCache();
                }
                $message = $success ? 'Sheet links saved and caches cleared.' : 'Sheet links could not be saved.';
            } elseif ($action === 'refresh_source') {
                $result = cbAdminSheetRefreshSource($key);
                $success = !empty($result['ok']);
                $message = $success ? 'Force refresh complete. Loaded ' . number_format((int) $result['count']) . ' row/group(s).' : 'Force refresh failed. Check the TSV link and headers.';
            } elseif ($action === 'refresh_all') {
                $parts = [];
                $success = true;
                foreach ($sourceKeys as $refreshKey) {
                    $result = cbAdminSheetRefreshSource($refreshKey);
                    $success = $success && !empty($result['ok']);
                    $parts[] = ucfirst($refreshKey) . ': ' . number_format((int) $result['count']);
                }
                $message = 'Mega sync complete. ' . implode(' | ', $parts);
            }
        }

        $sources = getCandybirdSheetSources();
        $source = $sources[$key];
        $syncLabel = $key === 'products' ? 'Sync Product Mirror' : 'Sync ' . ($source['label'] ?? $title);
        $health = checkCandybirdSheetHealth($key);
        include __DIR__ . '/header.php';
        include __DIR__ . '/page_menues.php';
        ?>
        <title><?= cbAdminSheetText($title) ?></title>
        <style>
            .sheet-page { padding: 30px 0 70px; }
            .sheet-hero { background:#2d1739; color:#fff; border-radius:8px; padding:24px; margin-bottom:18px; }
            .sheet-hero h1 { color:#fcb42f; font-size:30px; margin-bottom:8px; }
            .sheet-hero p, .sheet-hero li { color:#f8ecff; }
            .sheet-panel { background:#fff; border:1px solid #eadfd2; border-radius:8px; padding:20px; margin-bottom:18px; }
            .sheet-panel h2 { color:#5b1178; font-size:21px; margin-bottom:12px; }
            .sheet-badge { display:inline-block; border-radius:999px; padding:5px 10px; font-size:12px; font-weight:800; }
            .sheet-good { background:#e3f8e8; color:#186f33; }
            .sheet-bad { background:#ffe4e4; color:#9f1d1d; }
            .header-list { display:flex; flex-wrap:wrap; gap:6px; padding:0; margin:8px 0 0; list-style:none; }
            .header-list li { background:#f6f1ea; border:1px solid #eadfd2; border-radius:999px; padding:4px 8px; font-size:12px; }
            .sheet-actions { display:flex; flex-wrap:wrap; gap:10px; }
        </style>
        <div class="container sheet-page">
            <div class="sheet-hero">
                <h1><?= cbAdminSheetText($title) ?></h1>
                <?= $introHtml ?>
                <div class="sheet-actions mt-3">
                    <a class="btn btn-light" href="download_sheet_template?type=<?= cbAdminSheetText($key) ?>">Download template</a>
                    <form method="post" class="m-0"><input type="hidden" name="sheet_action" value="refresh_source"><button class="btn btn-warning" type="submit"><?= cbAdminSheetText($syncLabel) ?></button></form>
                    <form method="post" class="m-0"><input type="hidden" name="sheet_action" value="refresh_all"><button class="btn btn-outline-light" type="submit">Mega Sync All Sheets</button></form>
                    <a class="btn btn-outline-light" href="../products" target="_blank" rel="noopener noreferrer">View Shop</a>
                </div>
            </div>

            <?php if ($message): ?><div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>"><?= cbAdminSheetText($message) ?></div><?php endif; ?>

            <div class="sheet-panel">
                <h2>Editable Sheet Links</h2>
                <form method="post">
                    <input type="hidden" name="sheet_action" value="save_source">
                    <div class="form-group">
                        <label>Published TSV URL</label>
                        <input type="url" class="form-control" name="published_url" value="<?= cbAdminSheetText($source['published_url'] ?? '') ?>" <?= $key === 'clearance' ? '' : 'required' ?>>
                    </div>
                    <div class="form-group">
                        <label>Editable Google Sheet URL</label>
                        <input type="url" class="form-control" name="edit_url" value="<?= cbAdminSheetText($source['edit_url'] ?? '') ?>">
                    </div>
                    <div class="sheet-actions">
                        <button class="btn btn-primary" type="submit">Save sheet links</button>
                        <?php if (!empty($source['edit_url'])): ?><a class="btn btn-outline-primary" href="<?= cbAdminSheetText($source['edit_url']) ?>" target="_blank" rel="noopener noreferrer">Open editable sheet</a><?php endif; ?>
                        <?php if (!empty($source['published_url'])): ?><a class="btn btn-outline-secondary" href="<?= cbAdminSheetText($source['published_url']) ?>" target="_blank" rel="noopener noreferrer">Open TSV feed</a><?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="sheet-panel">
                <div class="d-flex justify-content-between align-items-start">
                    <h2><?= cbAdminSheetText($source['label']) ?> Health</h2>
                    <span class="sheet-badge <?= !empty($health['ok']) ? 'sheet-good' : 'sheet-bad' ?>"><?= !empty($health['ok']) ? 'Healthy' : 'Needs attention' ?></span>
                </div>
                <p><?= cbAdminSheetText($health['message'] ?? '') ?></p>
                <p><strong>Valid rows:</strong> <?= number_format((int) ($health['row_count'] ?? 0)) ?> | <strong>Rows scanned:</strong> <?= number_format((int) ($health['scanned_row_count'] ?? 0)) ?></p>
                <?php if (!empty($health['missing_headers'])): ?>
                    <p class="text-danger"><strong>Missing headers:</strong> <?= cbAdminSheetText(implode(', ', $health['missing_headers'])) ?></p>
                <?php endif; ?>
                <p class="mb-1"><strong>Required headers:</strong></p>
                <ul class="header-list"><?php foreach ($source['required_headers'] as $header): ?><li><?= cbAdminSheetText($header) ?></li><?php endforeach; ?></ul>
                <?php if (!empty($source['optional_headers'])): ?>
                    <p class="mb-1 mt-3"><strong>Supported optional headers:</strong></p>
                    <ul class="header-list"><?php foreach ($source['optional_headers'] as $header): ?><li><?= cbAdminSheetText($header) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>
                <p class="mb-1 mt-3"><strong>Detected headers:</strong></p>
                <ul class="header-list"><?php foreach (($health['headers'] ?? []) as $header): ?><li><?= cbAdminSheetText($header) ?></li><?php endforeach; ?></ul>
            </div>
        </div>
        <?php
        include __DIR__ . '/../footer.php';
    }
}
