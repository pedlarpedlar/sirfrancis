<?php
require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/../wholesale_pricelist_helpers.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    @include_once __DIR__ . '/db_connect.php';
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
            dirname(__DIR__) . '/sheet_cache/wholesale.tsv',
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
        if ($key === 'wholesale') {
            $items = getCandybirdWholesaleRows(true);
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
        $templateHeaders = [
            'products' => [
                'id',
                'parent_category',
                'child_category_1',
                'child_category_2',
                'name',
                'price',
                'img_url',
                'size',
                'shipping_weight',
                'free_delivery_excluded',
                'discount',
                'discounted_price',
                'discount_valid_from',
                'discount_valid_until',
                'html_description',
                'disclaimers',
                'product_type',
                'qty_in_stock',
                'lead_time',
                'slug',
                'additional_categories',
            ],
            'coupons' => [
                'id',
                'coupon_code',
                'discount_type',
                'discount_value',
                'min_order_value',
                'valid_from',
                'valid_until',
                'valid_on_sale_items',
                'subscriber_only',
                'valid_count',
                'multi_user',
                'email_restriction',
                'phone_restriction',
                'category_restriction',
                'product_type_exclusion',
            ],
        ];
        $headers = $templateHeaders[$key] ?? array_values(array_unique(array_merge(
            $sources[$key]['required_headers'] ?? [],
            $sources[$key]['optional_headers'] ?? []
        )));

        $explainers = [
            'id' => 'Unique product ID. Keep stable because carts, reviews and orders use this.',
            'parent_category' => 'Main menu category, e.g. Groceries',
            'child_category_1' => 'First category under parent, e.g. Dairy Products',
            'child_category_2' => 'Smaller subcategory if needed. Leave blank if none.',
            'name' => 'Product name without size, e.g. Plain Cashews.',
            'price' => 'Normal price before discounts. Use numbers only.',
            'img_url' => 'Direct image URL. For multiple images, separate with commas. Upload your images in your Sir Francis gallery and copy the URL for images to show here.',
            'size' => 'Size shown to customers, e.g. 100g, 1kg, 250ml, 1pc.',
            'shipping_weight' => 'Actual shipping weight only if different from size, e.g. 750g or 0.75kg. Blank means use size, then admin default.',
            'free_delivery_excluded' => 'If your free-delivery options do not apply to a product, type yes',
            'discount' => 'Discount amount in rand off the normal price. Optional.',
            'discounted_price' => 'Final sale price. Optional. This overrides discount if filled.',
            'discount_valid_from' => 'Special starts on this date. Optional. Use DD-MM-YYYY.',
            'discount_valid_until' => 'Special last valid day. Optional. Use DD-MM-YYYY.',
            'html_description' => 'Full product description. HTML is allowed and encouraged.',
            'disclaimers' => 'Add disclaimers like "Images for illustration purposes only". Can use html.',
            'product_type' => 'Use "digital" for vouchers/e-books. Leave blank for normal products.',
            'qty_in_stock' => 'Available quantity customers may order. Leave blank if unlimited/not tracked.',
            'lead_time' => 'Shows lead-time required instead of in-stock, e.g. "2-5 days".',
            'slug' => 'Clean URL text, e.g. marine-collagen-1kg. Must be unique. Optional. Use a sheet formula if desired, such as "=LOWER(REGEXREPLACE(REGEXREPLACE(TRIM(E3&" "&H3),"[^A-Za-z0-9]+","-"),"(^-|-$)",""))"',
            'additional_categories' => 'Extra categories where this product should also show. Use Parent > Child > Subchild and separate multiple categories with |. Example: Gifting > Eid Gifts | Specials',
            'pricelist_sort' => 'Optional number for fine ordering inside a pricelist section. Lower numbers show first. Leave blank to sort normally.',
            'homepage_featured' => 'yes/no. yes gives this product priority on homepage sections.',
            'coupon_code' => 'Coupon code customers type at checkout.',
            'valid_from' => 'Coupon start date. Optional. Use DD-MM-YYYY. Starts at 00:00 at the beginning of this date, Africa/Johannesburg time.',
            'valid_until' => 'Coupon end date. Optional. Use DD-MM-YYYY. Valid through this whole date and expires at 00:00 the next day, Africa/Johannesburg time.',
            'valid_on_sale_items' => 'yes/no. Whether coupon applies to already discounted items.',
            'subscriber_only' => 'yes/no. yes means only subscribed email addresses can use this coupon.',
            'min_order_value' => 'Minimum product subtotal before shipping.',
            'discount_type' => 'percentage or fixed.',
            'discount_value' => 'Discount amount, e.g. 10 or 100.',
            'valid_count' => 'Total times coupon can be used.',
            'multi_user' => 'yes/no. no means restricted to one customer identity.',
            'email_restriction' => 'Optional allowed email address. Use this to give a coupon to one specific customer and make them feel extra special. They must use this same email when checking out.',
            'phone_restriction' => 'Optional allowed phone number. Use this to give a coupon to one specific customer and make them feel extra special. They must use this same phone number when checking out.',
            'category_restriction' => 'Optional eligible categories, comma separated. Parent categories include their children.',
            'product_type_exclusion' => 'Optional excluded product types, e.g. digital or voucher.',
            'product_id_restriction' => 'Optional eligible product IDs, comma separated.',
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
            'case_size' => 'Wholesale case or bulk size, e.g. 22kg case or 5kg bag.',
            'price_per_kg' => 'Optional wholesale per kg price shown alongside the bulk price.',
            'retail_price_kg' => 'Optional retail per kg reference shown to customers for comparison.',
            'cost_kg' => 'Private admin-only cost per kg reference. Never shown to customers.',
            'pack_down_fee' => 'Optional fee per requested pack/unit, e.g. 1.50. Calculated against the packing requested.',
            'pack_down_note' => 'Optional explanation, e.g. charged per 1kg pack or per requested retail pack.',
            'allowed_pack_sizes' => 'Comma-separated calculator sizes, e.g. 1kg,500g,340g,100g,29g. Blank uses the default list.',
            'moq' => 'Minimum order quantity, e.g. 1 case or 50kg.',
            'enabled' => 'yes/no. no hides this row from the wholesale list.',
        ];

        $row2 = [];
        $row3 = [];
        foreach ($headers as $header) {
            if ($key === 'coupons' && $header === 'id') {
                $row2[] = 'Unique coupon row ID. Start real coupon rows from line 3; line 2 is helper text and is ignored by the importer.';
            } else {
                $row2[] = $explainers[$header] ?? 'Optional sheet field.';
            }
            if ($key === 'products') {
                $examples = [
                    'id' => '101',
                    'parent_category' => 'Marine Wellness',
                    'child_category_1' => 'Collagen',
                    'child_category_2' => '',
                    'name' => 'Sir Francis Marine Collagen',
                    'price' => '145.00',
                    'img_url' => 'https://sirfrancis.co.za/assets/img/product/1.png',
                    'size' => '100g',
                    'discount' => '',
                    'discounted_price' => '',
                    'discount_valid_from' => '',
                    'discount_valid_until' => '',
                    'html_description' => '<p>Premium Sir Francis marine collagen.</p>',
                    'product_type' => '',
                    'qty_in_stock' => '20',
                    'lead_time' => '',
                    'slug' => 'sir-francis-marine-collagen-100g',
                    'homepage_featured' => 'yes',
                    'shipping_weight' => '',
                    'free_delivery_excluded' => '',
                    'disclaimers' => 'Images are for illustration purposes only.',
                    'additional_categories' => 'Gifting > Eid Gifts | Specials',
                ];
            } elseif ($key === 'coupons') {
                $examples = [
                    'id' => '1',
                    'coupon_code' => 'MARINE10',
                    'discount_type' => 'percentage',
                    'discount_value' => '10',
                    'min_order_value' => '750',
                    'valid_from' => '01-07-2026',
                    'valid_until' => '31-07-2026',
                    'valid_on_sale_items' => 'no',
                    'subscriber_only' => 'no',
                    'valid_count' => '100',
                    'multi_user' => 'yes',
                    'email_restriction' => '',
                    'phone_restriction' => '',
                    'category_restriction' => 'Marine Collagen',
                    'product_type_exclusion' => 'digital,voucher',
                    'product_id_restriction' => '',
                ];
            } elseif ($key === 'clearance') {
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
            } else {
                $examples = [
                    'product_id' => '101',
                    'title' => 'Plain Cashews',
                    'size' => '22kg case',
                    'price' => '2860.00',
                    'description' => 'Bulk case pricing. Subject to stock availability.',
                    'case_size' => '22kg',
                    'price_per_kg' => '130.00',
                    'retail_price_kg' => '165.00',
                    'pack_down_fee' => '1.50',
                    'pack_down_note' => 'Charged per requested pack/unit. Example: 22 x 1kg packs = 22 pack-down units.',
                    'allowed_pack_sizes' => '1kg,500g,340g,100g,29g',
                    'moq' => '1 case',
                    'lead_time' => '2-5 working days',
                    'enabled' => 'yes',
                    'free_delivery_excluded' => 'no',
                    'cost_kg' => '105.00',
                ];
            }
            $row3[] = $examples[$header] ?? '';
        }

        if ($key === 'coupons') {
            return [$headers, $row3, $row2];
        }

        return [$headers, $row2, $row3];
    }
}

if (!function_exists('cbAdminSheetPage')) {
    function cbAdminSheetPage($key, $title, $introHtml) {
        $sourceKeys = ['products', 'coupons', 'clearance', 'wholesale'];
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
        $syncLabel = $key === 'products' ? 'Sync Products' : 'Sync ' . ($source['label'] ?? $title);
        $health = checkCandybirdSheetHealth($key);
        $adminHelpOverride = [
            'title' => $title . ' helper',
            'body' => trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', (string) $introHtml)))),
            'links' => array_values(array_filter([
                ['TSV How-to', 'tsv_how_to'],
                ['Sheet Links', 'sheets'],
                $key === 'products' ? ['Categories', 'category_order'] : null,
                $key === 'coupons' ? ['Coupon Tester', 'coupon_tester'] : null,
                $key === 'wholesale' ? ['Wholesale Page', '../wholesale-pricelist'] : null,
            ])),
        ];
        include __DIR__ . '/header.php';
        include __DIR__ . '/page_menues.php';
        ?>
        <title><?= cbAdminSheetText($title) ?></title>
        <style>
            .sheet-page { padding: 30px 0 70px; }
            .sheet-hero { background:var(--sf-navy); color:#fff; border-radius:8px; padding:24px; margin-bottom:18px; }
            .sheet-hero h1 { color:var(--sf-gold); font-size:30px; margin-bottom:8px; }
            .sheet-hero p, .sheet-hero li { color:rgba(255,255,255,.86); }
            .sheet-panel { background:#fff; border:1px solid var(--sf-border); border-radius:8px; padding:20px; margin-bottom:18px; }
            .sheet-panel h2 { color:#28364B; font-size:21px; margin-bottom:12px; }
            .sheet-badge { display:inline-block; border-radius:999px; padding:5px 10px; font-size:12px; font-weight:800; }
            .sheet-good { background:#e3f8e8; color:#186f33; }
            .sheet-bad { background:#ffe4e4; color:#9f1d1d; }
            .header-list { display:flex; flex-wrap:wrap; gap:6px; padding:0; margin:8px 0 0; list-style:none; }
            .header-list li { background:#f6f1ea; border:1px solid var(--sf-border); border-radius:999px; padding:4px 8px; font-size:12px; }
            .sheet-actions { display:flex; flex-wrap:wrap; gap:10px; }
        </style>
        <div class="container sheet-page">
            <div class="sheet-hero">
                <h1><?= cbAdminSheetText($title) ?></h1>
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
