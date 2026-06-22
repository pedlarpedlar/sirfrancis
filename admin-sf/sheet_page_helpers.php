<?php
require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/../wholesale_pricelist_helpers.php';
require_once __DIR__ . '/website_settings_helpers.php';
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

if (!function_exists('cbAdminSheetProductUploadUrls')) {
    function cbAdminSheetProductUploadUrls($productId, $productName) {
        $urls = [];
        if (empty($_FILES['product_images']) || !is_array($_FILES['product_images']['name'] ?? null)) {
            return $urls;
        }

        $uploadDir = dirname(__DIR__) . '/assets/img/product_images';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return $urls;
        }

        $safeBase = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($productId ?: $productName)), '-'));
        if ($safeBase === '') {
            $safeBase = 'product';
        }
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $count = count($_FILES['product_images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ((int) ($_FILES['product_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = (string) ($_FILES['product_images']['tmp_name'][$i] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                continue;
            }
            $extension = strtolower(pathinfo((string) $_FILES['product_images']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowed, true)) {
                continue;
            }
            $targetName = $safeBase . '-' . date('Ymd-His') . '-' . ($i + 1) . '.' . $extension;
            $targetPath = $uploadDir . '/' . $targetName;
            $suffix = 2;
            while (file_exists($targetPath)) {
                $targetName = $safeBase . '-' . date('Ymd-His') . '-' . ($i + 1) . '-' . $suffix . '.' . $extension;
                $targetPath = $uploadDir . '/' . $targetName;
                $suffix++;
            }
            if (move_uploaded_file($tmp, $targetPath)) {
                $urls[] = 'https://www.sirfrancis.co.za/assets/img/product_images/' . rawurlencode($targetName);
            }
        }

        return $urls;
    }
}

if (!function_exists('cbAdminSheetSaveManualProductFromPost')) {
    function cbAdminSheetSaveManualProductFromPost() {
        $headers = getCandybirdProductTemplateHeaders();
        $posted = is_array($_POST['product'] ?? null) ? $_POST['product'] : [];
        $product = [];
        foreach ($headers as $header) {
            $product[$header] = trim((string) ($posted[$header] ?? ''));
        }

        if ($product['id'] === '') {
            return [false, 'Add a unique product ID before saving.'];
        }
        if ($product['name'] === '') {
            return [false, 'Add a product name before saving.'];
        }
        if ($product['price'] === '' || !is_numeric(str_replace(',', '.', $product['price']))) {
            return [false, 'Add a numeric product price before saving.'];
        }

        $uploadedUrls = cbAdminSheetProductUploadUrls($product['id'], $product['name']);
        if ($uploadedUrls) {
            $existingUrls = array_filter(array_map('trim', explode(',', (string) $product['img_url'])));
            $product['img_url'] = implode(', ', array_merge($existingUrls, $uploadedUrls));
        }

        if (saveCandybirdManualProduct($product)) {
            cbAdminSheetClearPublicProductCache();
            return [true, 'Manual product saved. It is now included with the product feed.'];
        }

        return [false, 'Manual product could not be saved. Check that sheet_cache is writable.'];
    }
}

if (!function_exists('cbAdminSheetTemplateRows')) {
    function cbAdminSheetTemplateRows($key) {
        $sources = getCandybirdSheetSources();
        $templateHeaders = [
            'products' => getCandybirdProductTemplateHeaders(),
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
                'product_id_restriction',
                'product_id_exclusion',
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
            'product_id_restriction' => 'Optional eligible product IDs, comma separated. If filled, the coupon applies only to these product IDs.',
            'product_id_exclusion' => 'Optional excluded product IDs, comma separated. If filled, the coupon will not apply to these product IDs.',
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
                    'product_id_exclusion' => '',
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
        global $conn;

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
            if ($key === 'products' && $action === 'add_manual_product') {
                [$success, $message] = cbAdminSheetSaveManualProductFromPost();
            } elseif ($action === 'save_source') {
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
        $health = $key === 'products' ? [] : checkCandybirdSheetHealth($key);
        $nextProductIdHint = '';
        if ($key === 'products') {
            $maxProductId = 0;
            foreach (getSheetProducts(false) as $product) {
                $productId = trim((string) ($product['id'] ?? ''));
                if (ctype_digit($productId)) {
                    $maxProductId = max($maxProductId, (int) $productId);
                }
            }
            $nextProductIdHint = $maxProductId > 0
                ? 'The highest numeric product ID currently found is ' . $maxProductId . ', so you can use IDs from ' . ($maxProductId + 1) . ' upward.'
                : 'No numeric product IDs were found yet. You can start from 1 upward.';
        }
        $tinymceApiKey = '';
        if ($key === 'products') {
            if (defined('SF_DEFAULT_TINYMCE_API_KEY')) {
                $tinymceApiKey = SF_DEFAULT_TINYMCE_API_KEY;
            }
            if (isset($conn) && $conn instanceof mysqli) {
                $websiteSettings = cbWebsiteSettingsLoad($conn);
                $savedTinymceApiKey = trim((string) ($websiteSettings['tinymce_api_key'] ?? ''));
                if ($savedTinymceApiKey !== '') {
                    $tinymceApiKey = $savedTinymceApiKey;
                }
            }
        }
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
            .sheet-start-card { background:#fff; border:1px solid var(--sf-border); border-radius:8px; margin-bottom:18px; overflow:hidden; }
            .sheet-start-header { background:#f8f5ee; padding:22px; }
            .sheet-start-header h2 { color:#28364B; font-size:28px; margin-bottom:8px; }
            .sheet-start-header p { color:#574f45; font-size:16px; margin:0; }
            .sheet-start-steps { display:grid; gap:0; grid-template-columns:repeat(4, minmax(0, 1fr)); }
            .sheet-start-step { border-top:1px solid var(--sf-border); padding:20px; }
            .sheet-start-step + .sheet-start-step { border-left:1px solid var(--sf-border); }
            .sheet-start-step:nth-child(1) { background:#f4f8fb; }
            .sheet-start-step:nth-child(2) { background:#fbf7ef; }
            .sheet-start-step:nth-child(3) { background:#f3f7f1; }
            .sheet-start-step:nth-child(4) { background:#f8f3fb; }
            .sheet-start-step span { align-items:center; background:#28364B; color:#CEBD88; display:inline-flex; font-weight:900; height:34px; justify-content:center; margin-bottom:12px; width:34px; }
            .sheet-start-step h3 { color:#28364B; font-size:18px; margin-bottom:8px; }
            .sheet-start-step p { color:#574f45; min-height:54px; }
            .sheet-start-step:nth-child(1) .btn { background:#315f7c !important; border-color:#315f7c !important; color:#fff !important; }
            .sheet-start-step:nth-child(2) .btn { background:#9b7d2d !important; border-color:#9b7d2d !important; color:#fff !important; }
            .sheet-start-step:nth-child(3) .btn { background:#4f7742 !important; border-color:#4f7742 !important; color:#fff !important; }
            .sheet-start-step:nth-child(4) .btn { background:#6d4c7d !important; border-color:#6d4c7d !important; color:#fff !important; }
            .sheet-links-panel { background:#fbfaf6; border-color:#d8c895; }
            .sheet-links-panel .btn-primary { background:#28364B !important; border-color:#28364B !important; color:#fff !important; }
            .manual-product-grid { display:grid; gap:12px; grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .manual-product-field label { color:#28364B; display:block; font-size:13px; font-weight:800; margin-bottom:4px; }
            .manual-product-field input,
            .manual-product-field textarea { border:1px solid var(--sf-border); border-radius:0; font-size:14px; min-height:40px; padding:8px 10px; width:100%; }
            .manual-product-field small { color:#70695f; display:block; font-size:12px; line-height:1.35; margin-top:4px; }
            .manual-product-field--wide { grid-column:1 / -1; }
            .manual-product-html-tools { align-items:center; display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
            .manual-product-html-tools button { border-radius:0; font-size:12px; font-weight:800; padding:7px 10px; }
            .manual-product-html-output { background:#fbfaf6; border:1px solid #d8c895; color:#28364B; display:none; font-family:Consolas, monospace; font-size:12px; margin-top:8px; min-height:80px; padding:8px; width:100%; }
            .manual-product-html-output.is-visible { display:block; }
            .manual-product-modal .modal-dialog { max-width:980px; }
            .manual-product-modal .modal-content { border-radius:0; }
            .manual-product-modal .modal-header { background:#28364B; color:#fff; }
            .manual-product-modal .modal-header h5 { color:#CEBD88; }
            .manual-product-modal .close { color:#fff; opacity:1; }
            @media (max-width: 991px) {
                .sheet-start-steps { grid-template-columns:1fr; }
                .sheet-start-step + .sheet-start-step { border-left:0; }
                .manual-product-grid { grid-template-columns:1fr; }
            }
        </style>
        <div class="container sheet-page">
            <div class="sheet-hero">
                <h1><?= cbAdminSheetText($title) ?></h1>
                <div><?= $introHtml ?></div>
                <div class="sheet-actions mt-3">
                    <?php if ($key !== 'products'): ?>
                        <a class="btn btn-light" href="download_sheet_template?type=<?= cbAdminSheetText($key) ?>">Download template</a>
                    <?php endif; ?>
                    <form method="post" class="m-0"><input type="hidden" name="sheet_action" value="refresh_source"><button class="btn btn-warning" type="submit"><?= cbAdminSheetText($syncLabel) ?></button></form>
                    <form method="post" class="m-0"><input type="hidden" name="sheet_action" value="refresh_all"><button class="btn btn-outline-light" type="submit">Mega Sync All Sheets</button></form>
                    <a class="btn btn-outline-light" href="../products" target="_blank" rel="noopener noreferrer">View Shop</a>
                </div>
            </div>

            <?php if ($message): ?><div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>"><?= cbAdminSheetText($message) ?></div><?php endif; ?>

            <?php if ($key === 'products'): ?>
                <section class="sheet-start-card" aria-labelledby="product-sheet-start-title">
                    <div class="sheet-start-header">
                        <h2 id="product-sheet-start-title">Where To Start?</h2>
                        <p>Use this page in order: add images, prepare your product sheet, then save the two Google Sheet links. If you prefer, create a product directly from the form.</p>
                    </div>
                    <div class="sheet-start-steps">
                        <div class="sheet-start-step">
                            <span>1</span>
                            <h3>Add Product Images</h3>
                            <p>Upload your product images to the gallery first. Copy each image URL from the gallery and paste it into the sheet's image column.</p>
                            <a class="btn btn-primary" href="manage_gallery">Open Image Gallery</a>
                        </div>
                        <div class="sheet-start-step">
                            <span>2</span>
                            <h3>Download Template</h3>
                            <p>Use the template so the columns are already correct. Add your real products from line 3 onward.</p>
                            <a class="btn btn-primary" href="download_sheet_template?type=products">Download Product Template</a>
                        </div>
                        <div class="sheet-start-step">
                            <span>3</span>
                            <h3>Save Links Here</h3>
                            <p>After publishing your Google Sheet as TSV, paste the published TSV link and editable sheet link below.</p>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#sheetLinksModal">Save Product Sheet Links</button>
                        </div>
                        <div class="sheet-start-step">
                            <span>4</span>
                            <h3>Create Product</h3>
                            <p>If you prefer to add one product without editing the sheet, use the product creation form here.</p>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createProductModal">Open Product Form</button>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($key === 'products'): ?>
                <?php
                    $manualHeaders = getCandybirdProductTemplateHeaders();
                    $manualFieldLabels = [
                        'id' => 'Product ID',
                        'parent_category' => 'Main Category',
                        'child_category_1' => 'Subcategory',
                        'child_category_2' => 'Second Subcategory',
                        'name' => 'Product Name',
                        'price' => 'Normal Price',
                        'img_url' => 'Image URL',
                        'size' => 'Display Size',
                        'shipping_weight' => 'Shipping Weight',
                        'free_delivery_excluded' => 'Exclude Free Delivery',
                        'discount' => 'Discount Amount',
                        'discounted_price' => 'Final Sale Price',
                        'discount_valid_from' => 'Discount Start Date',
                        'discount_valid_until' => 'Discount End Date',
                        'html_description' => 'Product Description',
                        'disclaimers' => 'Product Disclaimers',
                        'product_type' => 'Product Type',
                        'qty_in_stock' => 'Quantity In Stock',
                        'lead_time' => 'Lead Time',
                        'slug' => 'Product Slug',
                        'additional_categories' => 'Additional Categories',
                    ];
                    $manualFieldHelp = [
                        'id' => 'Unique product ID. Keep it stable because carts, reviews and orders use this. ' . $nextProductIdHint,
                        'parent_category' => 'Main menu category, for example Marine Wellness.',
                        'child_category_1' => 'Optional category under the main category.',
                        'child_category_2' => 'Optional smaller category under the subcategory.',
                        'name' => 'Product name without the size, for example Marine Collagen.',
                        'price' => 'Use numbers only, for example 145.00.',
                        'img_url' => 'You can paste existing image URLs here, upload images below, or do both. Multiple URLs are separated with commas.',
                        'size' => 'What customers see, for example 100g, 1kg, 250ml or 1pc.',
                        'shipping_weight' => 'Only fill this if it differs from size, for example 0.75kg.',
                        'free_delivery_excluded' => 'Type yes only if free delivery should not apply.',
                        'discount' => 'Rand amount off the normal price. Optional.',
                        'discounted_price' => 'Final sale price. Optional. Overrides discount amount.',
                        'discount_valid_from' => 'Optional. Use DD-MM-YYYY.',
                        'discount_valid_until' => 'Optional. Use DD-MM-YYYY.',
                        'html_description' => 'Rich product description shown to customers.',
                        'disclaimers' => 'Optional disclaimer text, such as image or product notes.',
                        'product_type' => 'Use digital for vouchers/e-books. Leave blank for normal products.',
                        'qty_in_stock' => 'Leave blank if stock is unlimited or not tracked.',
                        'lead_time' => 'Optional, for example 2-5 working days.',
                        'slug' => 'Clean URL text. Leave blank to let the site use the product name and size.',
                        'additional_categories' => 'Optional extra category paths. Use Parent > Child and separate multiple paths with |.',
                    ];
                    $textareaFields = ['html_description', 'disclaimers', 'additional_categories'];
                    $hiddenProductModalFields = ['img_url'];
                ?>
            <?php endif; ?>

            <?php if ($key !== 'products'): ?>
            <div class="sheet-panel sheet-links-panel" id="sheet-links">
                <h2><?= $key === 'products' ? 'Save Product Sheet Links Here' : 'Editable Sheet Links' ?></h2>
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
            <?php endif; ?>

            <?php if ($key === 'products'): ?>
                <div class="modal fade manual-product-modal" id="sheetLinksModal" tabindex="-1" role="dialog" aria-labelledby="sheetLinksModalTitle" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="sheetLinksModalTitle">Save Product Sheet Links</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post">
                                <div class="modal-body">
                                    <input type="hidden" name="sheet_action" value="save_source">
                                    <p class="text-muted">Paste the published TSV URL and the editable Google Sheet URL here. Once saved, use Sync Products when you want the website to refresh immediately.</p>
                                    <div class="form-group">
                                        <label>Published TSV URL</label>
                                        <input type="url" class="form-control" name="published_url" value="<?= cbAdminSheetText($source['published_url'] ?? '') ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Editable Google Sheet URL</label>
                                        <input type="url" class="form-control" name="edit_url" value="<?= cbAdminSheetText($source['edit_url'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <?php if (!empty($source['edit_url'])): ?><a class="btn btn-outline-primary" href="<?= cbAdminSheetText($source['edit_url']) ?>" target="_blank" rel="noopener noreferrer">Open editable sheet</a><?php endif; ?>
                                    <?php if (!empty($source['published_url'])): ?><a class="btn btn-outline-secondary" href="<?= cbAdminSheetText($source['published_url']) ?>" target="_blank" rel="noopener noreferrer">Open TSV feed</a><?php endif; ?>
                                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary" type="submit">Save Links</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade manual-product-modal" id="createProductModal" tabindex="-1" role="dialog" aria-labelledby="createProductModalTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="createProductModalTitle">Create Product</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post" enctype="multipart/form-data" id="manual-product-entry-form">
                                <div class="modal-body">
                                    <input type="hidden" name="sheet_action" value="add_manual_product">
                                    <p class="text-muted">Create a product using the same fields as the product template. Uploaded images are saved into <strong>assets/img/product_images</strong> and added to the image URL field.</p>
                                    <div class="manual-product-grid">
                                        <?php foreach ($manualHeaders as $header): ?>
                                            <?php if (in_array($header, $hiddenProductModalFields, true)): ?>
                                                <input type="hidden" name="product[<?= cbAdminSheetText($header) ?>]" value="">
                                                <?php continue; ?>
                                            <?php endif; ?>
                                            <?php $isWideField = in_array($header, ['img_url', 'html_description', 'disclaimers', 'additional_categories'], true); ?>
                                            <div class="manual-product-field <?= $isWideField ? 'manual-product-field--wide' : '' ?>">
                                                <label for="manual_product_<?= cbAdminSheetText($header) ?>"><?= cbAdminSheetText($manualFieldLabels[$header] ?? ucwords(str_replace('_', ' ', $header))) ?></label>
                                                <?php if (in_array($header, $textareaFields, true)): ?>
                                                    <textarea id="manual_product_<?= cbAdminSheetText($header) ?>" name="product[<?= cbAdminSheetText($header) ?>]" rows="<?= $header === 'html_description' ? 6 : 3 ?>" class="<?= in_array($header, ['html_description', 'disclaimers'], true) ? 'manual-product-richtext' : '' ?>" data-product-html-field="<?= cbAdminSheetText($header) ?>"></textarea>
                                                    <?php if (in_array($header, ['html_description', 'disclaimers'], true)): ?>
                                                        <div class="manual-product-html-tools">
                                                            <button type="button" class="btn btn-outline-primary manual-product-convert-html" data-target="manual_product_<?= cbAdminSheetText($header) ?>">Convert To HTML</button>
                                                            <button type="button" class="btn btn-outline-secondary manual-product-copy-html" data-target="manual_product_<?= cbAdminSheetText($header) ?>">Copy HTML</button>
                                                        </div>
                                                        <textarea class="manual-product-html-output" id="manual_product_<?= cbAdminSheetText($header) ?>_html_output" readonly aria-label="<?= cbAdminSheetText($manualFieldLabels[$header] ?? $header) ?> HTML output"></textarea>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <input id="manual_product_<?= cbAdminSheetText($header) ?>" name="product[<?= cbAdminSheetText($header) ?>]" type="<?= in_array($header, ['price', 'discount', 'discounted_price'], true) ? 'number' : 'text' ?>" <?= in_array($header, ['price', 'discount', 'discounted_price'], true) ? 'step="0.01"' : '' ?> <?= in_array($header, ['id', 'name', 'price'], true) ? 'required' : '' ?>>
                                                <?php endif; ?>
                                                <?php if (!empty($manualFieldHelp[$header])): ?>
                                                    <small><?= cbAdminSheetText($manualFieldHelp[$header]) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="manual-product-field manual-product-field--wide">
                                            <label for="manual_product_images">Upload Product Images</label>
                                            <input id="manual_product_images" name="product_images[]" type="file" accept="image/*" multiple>
                                            <small>Images upload to assets/img/product_images and their URLs are added to Image URL.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                                    <button class="btn btn-primary" type="submit">Save Product</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($key !== 'products'): ?>
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
            <?php endif; ?>
        </div>
        <?php if ($key === 'products' && $tinymceApiKey !== ''): ?>
            <script src="https://cdn.tiny.cloud/1/<?= cbAdminSheetText($tinymceApiKey) ?>/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
        <?php endif; ?>
        <?php if ($key === 'products'): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    function escapeHtml(value) {
                        return String(value || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    }

                    function plainTextToHtml(value) {
                        var text = String(value || '').trim();
                        if (!text) return '';
                        return text.split(/\n{2,}/).map(function(block) {
                            var lines = block.split(/\n/).map(function(line) {
                                return escapeHtml(line.trim());
                            }).filter(Boolean);
                            return lines.length ? '<p>' + lines.join('<br>') + '</p>' : '';
                        }).filter(Boolean).join("\n");
                    }

                    function productFieldHtml(fieldId) {
                        if (window.tinymce && tinymce.get(fieldId)) {
                            return tinymce.get(fieldId).getContent();
                        }
                        var field = document.getElementById(fieldId);
                        return field ? plainTextToHtml(field.value) : '';
                    }

                    function updateProductHtmlOutput(fieldId) {
                        var output = document.getElementById(fieldId + '_html_output');
                        if (!output) return '';
                        var html = productFieldHtml(fieldId);
                        output.value = html;
                        output.classList.add('is-visible');
                        return html;
                    }

                    function initManualProductEditors() {
                        if (!window.tinymce) return;
                        document.querySelectorAll('.manual-product-richtext').forEach(function(field) {
                            if (!field.id || tinymce.get(field.id)) return;
                            tinymce.init({
                                selector: '#' + field.id,
                                menubar: false,
                                plugins: 'lists link table code paste',
                                toolbar: 'undo redo | formatselect | bold italic underline | bullist numlist | link table | removeformat | code',
                                branding: false,
                                height: 260,
                                convert_urls: false
                            });
                        });
                    }

                    initManualProductEditors();

                    var createProductModal = document.getElementById('createProductModal');
                    if (createProductModal && window.jQuery) {
                        jQuery(createProductModal).on('shown.bs.modal', function() {
                            initManualProductEditors();
                        });
                    }

                    if (window.tinymce) {
                        var form = document.getElementById('manual-product-entry-form');
                        if (form) {
                            form.addEventListener('submit', function() {
                                tinymce.triggerSave();
                            });
                        }
                    }

                    document.querySelectorAll('.manual-product-convert-html').forEach(function(button) {
                        button.addEventListener('click', function() {
                            updateProductHtmlOutput(button.getAttribute('data-target'));
                        });
                    });

                    document.querySelectorAll('.manual-product-copy-html').forEach(function(button) {
                        button.addEventListener('click', function() {
                            var fieldId = button.getAttribute('data-target');
                            var html = updateProductHtmlOutput(fieldId);
                            if (!html) return;
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                navigator.clipboard.writeText(html);
                            } else {
                                var output = document.getElementById(fieldId + '_html_output');
                                if (output) {
                                    output.focus();
                                    output.select();
                                    document.execCommand('copy');
                                }
                            }
                            button.textContent = 'Copied';
                            window.setTimeout(function() {
                                button.textContent = 'Copy HTML';
                            }, 1400);
                        });
                    });
                });
            </script>
        <?php endif; ?>
        <?php
        include __DIR__ . '/../footer.php';
    }
}
