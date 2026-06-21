<?php
date_default_timezone_set('Africa/Johannesburg');
require_once __DIR__ . '/product_sheet_sources.php';

if (!defined('SIRFRANCIS_SITE_BASE_URL')) {
    define('SIRFRANCIS_SITE_BASE_URL', 'https://sirfrancis.co.za');
}

if (!defined('SIRFRANCIS_PRODUCT_PLACEHOLDER_IMAGE')) {
    define('SIRFRANCIS_PRODUCT_PLACEHOLDER_IMAGE', 'assets/img/product/1.png');
}

if (!defined('SIRFRANCIS_LEGACY_ORDER_CUTOFF_DATE')) {
    define('SIRFRANCIS_LEGACY_ORDER_CUTOFF_DATE', '2026-06-20');
}

if (!function_exists('sirFrancisSiteUrl')) {
    function sirFrancisSiteUrl($path = '') {
        $path = trim((string) $path);
        if ($path === '') {
            return SIRFRANCIS_SITE_BASE_URL;
        }
        return rtrim(SIRFRANCIS_SITE_BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('isSirFrancisLegacyCandybirdAsset')) {
    function isSirFrancisLegacyCandybirdAsset($url) {
        $url = strtolower(trim((string) $url));
        if ($url === '') {
            return false;
        }

        foreach ([
            'candybird.co.za',
            'candybird',
            'fishgelatine.co.za/v2/assets/img/wholesale.jpg',
            'fishgelatine.co.za/v2/assets/img/pricelist.jpg',
            'fishgelatine.co.za/v2/assets/img/reseller.jpeg',
        ] as $legacyNeedle) {
            if (strpos($url, $legacyNeedle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('parseCandybirdTsvRows')) {
    function parseCandybirdTsvRows($tsvData) {
        $rows = [];
        $handle = fopen('php://temp', 'r+');
        if (!$handle) {
            return $rows;
        }

        fwrite($handle, (string) $tsvData);
        rewind($handle);

        $headers = fgetcsv($handle, 0, "\t");
        if (!$headers) {
            fclose($handle);
            return $rows;
        }

        $headers = array_map('trim', $headers);
        $headerCount = count($headers);
        $rowNumberAfterHeader = 0;

        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            if (count(array_filter($row, static function($cell) {
                return trim((string) $cell) !== '';
            })) === 0) {
                continue;
            }

            $firstCell = strtoupper(trim((string) ($row[0] ?? '')));
            if (in_array($firstCell, ['END', 'STOP', '__END__'], true)) {
                break;
            }

            $rowNumberAfterHeader++;
            $firstHeader = strtolower(trim((string) ($headers[0] ?? '')));
            $firstValue = trim((string) ($row[0] ?? ''));
            if (in_array(strtolower($firstValue), ['note', 'notes', 'explainer', 'instructions', 'instruction', 'example', 'help'], true)
                || ($firstHeader === 'id' && $firstValue !== '' && !is_numeric($firstValue))) {
                continue;
            }

            if (count($row) < $headerCount) {
                $row = array_pad($row, $headerCount, '');
            } elseif (count($row) > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            $rows[] = array_combine($headers, array_map('trim', $row));
        }

        fclose($handle);
        return $rows;
    }
}

if (!function_exists('candybirdParseSheetMoney')) {
    function candybirdParseSheetMoney($value) {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace(["\xc2\xa0", 'R', 'r', ' '], '', $value);
        $value = preg_replace('/[^\d,.\-]/', '', $value);
        if ($value === '' || $value === '-' || $value === ',' || $value === '.') {
            return 0.0;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
            return (float) $value;
        }

        if ($lastComma !== false) {
            if (preg_match('/^-?\d{1,3}(,\d{3})+(?:,\d+)?$/', $value)) {
                $value = str_replace(',', '', $value);
            } else {
                $value = str_replace(',', '.', $value);
            }
            return (float) $value;
        }

        if (substr_count($value, '.') > 1 && preg_match('/^-?\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return (float) $value;
    }
}

if (!function_exists('normalizeCandybirdSheetMoneyFields')) {
    function normalizeCandybirdSheetMoneyFields($row) {
        if (!is_array($row)) {
            return $row;
        }

        foreach (['price', 'discounted_price', 'discount', 'discount_amount', 'clearance_price', 'min_order_value', 'discount_value'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = candybirdParseSheetMoney($row[$field]);
            }
        }

        return $row;
    }
}

if (!function_exists('candybirdTsvValidProductCount')) {
    function candybirdTsvValidProductCount($tsvData) {
        $count = 0;
        foreach (parseCandybirdTsvRows($tsvData) as $row) {
            if (!empty($row['id']) && trim((string) ($row['name'] ?? '')) !== '' && trim((string) ($row['price'] ?? '')) !== '') {
                $count++;
            }
        }
        return $count;
    }
}

if (!function_exists('candybirdTsvDataLooksSafeToCache')) {
    function candybirdTsvDataLooksSafeToCache($cacheKey, $tsvData) {
        if ($cacheKey !== 'products') {
            return true;
        }

        return candybirdTsvValidProductCount($tsvData) >= 1;
    }
}

if (!function_exists('getCandybirdProductPrimaryCategoryParts')) {
    function getCandybirdProductPrimaryCategoryParts($product) {
        $parts = [];
        foreach (['parent_category', 'child_category_1', 'child_category_2'] as $field) {
            $value = trim((string) ($product[$field] ?? ''));
            if ($value !== '' && !in_array($value, $parts, true)) {
                $parts[] = $value;
            }
        }
        return $parts;
    }
}

if (!function_exists('getCandybirdProductAdditionalCategoryPaths')) {
    function getCandybirdProductAdditionalCategoryPaths($product) {
        $raw = trim((string) ($product['additional_categories'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $paths = [];
        foreach (explode('|', $raw) as $pathText) {
            $parts = [];
            foreach (explode('>', (string) $pathText) as $part) {
                $part = trim((string) $part);
                if ($part !== '' && !in_array($part, $parts, true)) {
                    $parts[] = $part;
                }
            }
            if (!empty($parts)) {
                $pathKey = implode(' > ', $parts);
                $paths[$pathKey] = $parts;
            }
        }

        return array_values($paths);
    }
}

if (!function_exists('getCandybirdProductCategoryPaths')) {
    function getCandybirdProductCategoryPaths($product, $includePrimary = true) {
        $paths = [];
        if ($includePrimary) {
            $primary = getCandybirdProductPrimaryCategoryParts($product);
            if (!empty($primary)) {
                $paths[implode(' > ', $primary)] = $primary;
            }
        }
        foreach (getCandybirdProductAdditionalCategoryPaths($product) as $path) {
            $paths[implode(' > ', $path)] = $path;
        }
        return array_values($paths);
    }
}

if (!function_exists('getCandybirdProductCategoryNames')) {
    function getCandybirdProductCategoryNames($product) {
        $names = [];
        foreach (getCandybirdProductCategoryPaths($product) as $path) {
            foreach ($path as $part) {
                $part = trim((string) $part);
                if ($part !== '' && !in_array($part, $names, true)) {
                    $names[] = $part;
                }
            }
        }
        return $names;
    }
}

if (!function_exists('isCandybirdProductInCategory')) {
    function isCandybirdProductInCategory($product, $categoryName) {
        $categoryToken = function_exists('getCandybirdCategorySlug')
            ? getCandybirdCategorySlug($categoryName)
            : strtolower(trim((string) $categoryName));
        if ($categoryToken === '') {
            return false;
        }
        foreach (getCandybirdProductCategoryNames($product) as $name) {
            $nameToken = function_exists('getCandybirdCategorySlug')
                ? getCandybirdCategorySlug($name)
                : strtolower(trim((string) $name));
            if ($nameToken !== '' && $nameToken === $categoryToken) {
                return true;
            }
        }
        foreach (getCandybirdProductCategoryPaths($product) as $path) {
            $pathText = implode(' > ', $path);
            $pathToken = function_exists('getCandybirdCategorySlug')
                ? getCandybirdCategorySlug($pathText)
                : strtolower(trim($pathText));
            if ($pathToken !== '' && $pathToken === $categoryToken) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('getSheetProducts')) {
    function getSheetProducts($forceRefresh = false) {
        static $sheetProducts = null;

        if (!$forceRefresh && $sheetProducts !== null) {
            return $sheetProducts;
        }

        $sheet_url = getCandybirdSheetUrl('products');
        $sheetProducts = [];

        foreach (fetchCandybirdTsvSheet($sheet_url, 'products', 3600, $forceRefresh) as $item) {
            $item = normalizeCandybirdSheetMoneyFields($item);
            if (!empty($item['id']) && trim((string) ($item['name'] ?? '')) !== '' && trim((string) ($item['price'] ?? '')) !== '') {
                $sheetProducts[(string) $item['id']] = normalizeCandybirdProductSpecial($item);
            }
        }

        return $sheetProducts;
    }
}

if (!function_exists('parseCandybirdClearanceDate')) {
    function parseCandybirdClearanceDate($dateValue, $endOfDay = false) {
        return parseCandybirdProductSpecialDate($dateValue, $endOfDay);
    }
}

if (!function_exists('isCandybirdClearanceRowActive')) {
    function isCandybirdClearanceRowActive($row) {
        $from = parseCandybirdClearanceDate($row['valid_from'] ?? '', false);
        $until = parseCandybirdClearanceDate($row['valid_until'] ?? '', true);
        $now = new DateTime('now', new DateTimeZone('Africa/Johannesburg'));
        if ($from instanceof DateTime && $now < $from) {
            return false;
        }
        if ($until instanceof DateTime && $now > $until) {
            return false;
        }
        return true;
    }
}

if (!function_exists('getSheetClearanceRows')) {
    function getSheetClearanceRows($forceRefresh = false) {
        static $clearanceRows = null;

        if (!$forceRefresh && $clearanceRows !== null) {
            return $clearanceRows;
        }

        $clearanceRows = [];
        $sheetUrl = getCandybirdSheetUrl('clearance');
        if (trim((string) $sheetUrl) === '') {
            return $clearanceRows;
        }

        foreach (fetchCandybirdTsvSheet($sheetUrl, 'clearance', 120, $forceRefresh) as $row) {
            $row = normalizeCandybirdSheetMoneyFields($row);
            $clearanceId = strtoupper(trim((string) ($row['clearance_id'] ?? $row['id'] ?? '')));
            $sourceProductId = trim((string) ($row['product_id'] ?? $row['source_product_id'] ?? ''));
            $qtyAvailable = max(0, (int) ($row['qty_available'] ?? $row['quantity'] ?? $row['stock'] ?? 0));
            $clearancePrice = candybirdParseSheetMoney($row['clearance_price'] ?? $row['price'] ?? 0);

            if ($clearanceId === '' || $sourceProductId === '' || $clearancePrice <= 0 || !isCandybirdClearanceRowActive($row)) {
                continue;
            }

            $row['clearance_id'] = $clearanceId;
            $row['product_id'] = $sourceProductId;
            $row['qty_available'] = $qtyAvailable;
            $row['clearance_price'] = $clearancePrice;
            $clearanceRows[$clearanceId] = $row;
        }

        return $clearanceRows;
    }
}

if (!function_exists('getSheetClearanceRowById')) {
    function getSheetClearanceRowById($clearanceId) {
        $clearanceId = strtoupper(trim((string) $clearanceId));
        if (strpos($clearanceId, 'CLR:') === 0) {
            $clearanceId = substr($clearanceId, 4);
        }
        $rows = getSheetClearanceRows();
        return $rows[$clearanceId] ?? null;
    }
}

if (!function_exists('buildCandybirdClearanceProduct')) {
    function buildCandybirdClearanceProduct($clearanceRow) {
        $sourceProduct = getSheetProductById($clearanceRow['product_id'] ?? '');
        if (!$sourceProduct) {
            return null;
        }

        $clearanceId = strtoupper(trim((string) ($clearanceRow['clearance_id'] ?? '')));
        $price = candybirdParseSheetMoney($sourceProduct['price'] ?? 0);
        $clearancePrice = candybirdParseSheetMoney($clearanceRow['clearance_price'] ?? 0);
        $tag = trim((string) ($clearanceRow['clearance_tag'] ?? 'Clearance / dated'));
        $titleOverride = trim((string) ($clearanceRow['clearance_title'] ?? ''));
        $imageOverride = trim((string) ($clearanceRow['clearance_img_url'] ?? ''));
        $descriptionOverride = trim((string) ($clearanceRow['clearance_description'] ?? ''));

        $product = $sourceProduct;
        $product['id'] = 'CLR:' . $clearanceId;
        $product['source_product_id'] = (string) ($sourceProduct['id'] ?? $clearanceRow['product_id']);
        $product['clearance_id'] = $clearanceId;
        $product['is_clearance'] = 'yes';
        $product['parent_category'] = 'Clearance Basket';
        $product['child_category_1'] = $tag;
        $product['child_category_2'] = '';
        $product['slug'] = normalizeCandybirdProductSlug($clearanceRow['slug'] ?? '');
        $clearanceName = $titleOverride !== '' ? $titleOverride : (string) ($sourceProduct['name'] ?? $sourceProduct['title'] ?? getSheetProductDisplayTitle($sourceProduct));
        $product['name'] = stripos($clearanceName, 'clearance') === false ? 'CLEARANCE - ' . $clearanceName : $clearanceName;
        $product['price'] = $price > 0 ? $price : $clearancePrice;
        $product['discounted_price'] = $clearancePrice;
        $product['discount'] = max(0, (float) $product['price'] - $clearancePrice);
        $product['discount_amount'] = $product['discount'];
        $product['discount_rate'] = (float) $product['price'] > 0 ? round(($product['discount'] / (float) $product['price']) * 100, 2) : 0;
        $clearanceQty = (int) ($clearanceRow['qty_available'] ?? 0);
        $product['qty_in_stock'] = $clearanceQty;
        $product['stock_qty'] = $clearanceQty;
        $product['qty_available'] = $clearanceQty;
        $product['quantity_available'] = $clearanceQty;
        $product['available_qty'] = $clearanceQty;
        $product['stock'] = $clearanceQty;
        $product['inventory'] = $clearanceQty;
        $product['clearance_reason'] = trim((string) ($clearanceRow['clearance_reason'] ?? 'Clearance stock'));
        $product['clearance_notes'] = trim((string) ($clearanceRow['clearance_notes'] ?? ''));
        $product['html_description'] = $descriptionOverride !== '' ? $descriptionOverride : ($sourceProduct['html_description'] ?? '');
        $product['img_url'] = $imageOverride !== '' ? $imageOverride : getSheetProductImage($sourceProduct);
        $product['rating'] = 0;
        $product['review_count'] = 0;
        $product['reviews_disabled'] = 'yes';
        return $product;
    }
}

if (!function_exists('getSheetProductsWithClearance')) {
    function getSheetProductsWithClearance($forceRefresh = false) {
        $products = getSheetProducts($forceRefresh);
        foreach (getSheetClearanceRows($forceRefresh) as $clearanceRow) {
            $clearanceProduct = buildCandybirdClearanceProduct($clearanceRow);
            if ($clearanceProduct) {
                $products[(string) $clearanceProduct['id']] = $clearanceProduct;
            }
        }
        return $products;
    }
}

if (!function_exists('parseCandybirdProductSpecialDate')) {
    function parseCandybirdProductSpecialDate($dateValue, $endOfDay = false) {
        $dateValue = trim((string) $dateValue);
        if ($dateValue === '') {
            return null;
        }

        $timezone = new DateTimeZone('Africa/Johannesburg');
        $hasTime = preg_match('/\d{1,2}:\d{2}/', $dateValue) === 1;
        $formats = ['d-m-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format . ' H:i:s', $dateValue . ' 00:00:00', $timezone);
            if ($date instanceof DateTime) {
                if ($endOfDay && !$hasTime) {
                    $date->setTime(23, 59, 59);
                }
                return $date;
            }
        }

        try {
            $date = new DateTime($dateValue, $timezone);
            if ($endOfDay && !$hasTime) {
                $date->setTime(23, 59, 59);
            }
            return $date;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('isCandybirdProductSpecialActive')) {
    function isCandybirdProductSpecialActive($product, $now = null) {
        if (!is_array($product)) {
            return false;
        }

        $fromRaw = $product['discount_valid_from'] ?? $product['special_valid_from'] ?? $product['sale_valid_from'] ?? '';
        $untilRaw = $product['discount_valid_until'] ?? $product['special_valid_until'] ?? $product['sale_valid_until'] ?? '';
        $from = parseCandybirdProductSpecialDate($fromRaw, false);
        $until = parseCandybirdProductSpecialDate($untilRaw, true);
        $now = $now instanceof DateTime ? clone $now : new DateTime('now', new DateTimeZone('Africa/Johannesburg'));

        if ($from instanceof DateTime && $now < $from) {
            return false;
        }
        if ($until instanceof DateTime && $now > $until) {
            return false;
        }

        return true;
    }
}

if (!function_exists('normalizeCandybirdProductSpecial')) {
    function normalizeCandybirdProductSpecial($product) {
        if (!is_array($product)) {
            return $product;
        }
        $product = normalizeCandybirdSheetMoneyFields($product);
        if (strtolower((string) ($product['is_clearance'] ?? '')) === 'yes') {
            return $product;
        }

        $price = isset($product['price']) ? candybirdParseSheetMoney($product['price']) : 0;
        $discountedPrice = isset($product['discounted_price']) ? candybirdParseSheetMoney($product['discounted_price']) : 0;
        $discountAmount = isset($product['discount']) ? candybirdParseSheetMoney($product['discount']) : (isset($product['discount_amount']) ? candybirdParseSheetMoney($product['discount_amount']) : 0);
        $discountRate = isset($product['discount_rate']) ? (float) $product['discount_rate'] : 0;
        $hasSpecial = ($discountedPrice > 0 && $discountedPrice < $price) || $discountAmount > 0 || $discountRate > 0;

        if ($hasSpecial && !isCandybirdProductSpecialActive($product)) {
            $product['discounted_price'] = $price;
            $product['discount_amount'] = 0;
            $product['discount'] = 0;
            $product['discount_rate'] = 0;
            $product['special_active'] = 'no';
            return $product;
        }

        if ($hasSpecial) {
            $effectivePrice = $price;
            if ($discountedPrice > 0 && $discountedPrice < $price) {
                $effectivePrice = $discountedPrice;
            } elseif ($discountAmount > 0) {
                $effectivePrice = max(0, $price - $discountAmount);
            } elseif ($discountRate > 0) {
                $effectivePrice = max(0, $price - ($price * $discountRate / 100));
            }

            $effectiveDiscount = max(0, $price - $effectivePrice);
            $product['discounted_price'] = $effectivePrice;
            $product['discount_amount'] = $effectiveDiscount;
            $product['discount'] = $effectiveDiscount;
            $product['discount_rate'] = $price > 0 && $effectiveDiscount > 0 ? round(($effectiveDiscount / $price) * 100, 2) : 0;
            $product['special_active'] = 'yes';
        } else {
            $product['discounted_price'] = $price;
            $product['discount_amount'] = 0;
            $product['discount'] = 0;
            $product['discount_rate'] = 0;
            $product['special_active'] = 'no';
        }

        return $product;
    }
}

if (!function_exists('isCandybirdProductOnSpecial')) {
    function isCandybirdProductOnSpecial($product) {
        if (!is_array($product)) {
            return false;
        }
        if (strtolower((string) ($product['is_clearance'] ?? '')) === 'yes') {
            return false;
        }

        $product = normalizeCandybirdProductSpecial($product);
        $price = isset($product['price']) ? candybirdParseSheetMoney($product['price']) : 0;
        $discountedPrice = isset($product['discounted_price']) ? candybirdParseSheetMoney($product['discounted_price']) : 0;
        $discountAmount = isset($product['discount_amount']) ? candybirdParseSheetMoney($product['discount_amount']) : (isset($product['discount']) ? candybirdParseSheetMoney($product['discount']) : 0);
        $discountRate = isset($product['discount_rate']) ? (float) $product['discount_rate'] : 0;

        return $price > 0 && (
            ($discountedPrice > 0 && $discountedPrice < $price)
            || $discountAmount > 0
            || $discountRate > 0
            || strtolower((string) ($product['special_active'] ?? '')) === 'yes'
        );
    }
}

if (!function_exists('fetchCandybirdTsvSheet')) {
    function fetchCandybirdTsvSheet($sheetUrl, $cacheKey = 'sheet', $ttlSeconds = 600, $forceRefresh = false) {
        $cacheDir = __DIR__ . '/sheet_cache';
        $cacheFile = $cacheDir . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $cacheKey) . '.tsv';
        $tsv_data = false;
        $freshFetched = false;

        $cacheIsFresh = is_file($cacheFile) && ((int) $ttlSeconds <= 0 || (time() - filemtime($cacheFile)) <= (int) $ttlSeconds);
        if (!$forceRefresh && $cacheIsFresh) {
            $tsv_data = file_get_contents($cacheFile);
        }

        if (function_exists('curl_init')) {
            $freshData = false;
            if ($forceRefresh || $tsv_data === false) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $sheetUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                $freshData = curl_exec($ch);
                curl_close($ch);
            }

            if ($freshData) {
                $tsv_data = $freshData;
                $freshFetched = true;
            }
        }

        if (($forceRefresh || $tsv_data === false) && ini_get('allow_url_fopen')) {
            $context = stream_context_create(['http' => ['timeout' => 4]]);
            $freshData = @file_get_contents($sheetUrl, false, $context);
            if ($freshData) {
                $tsv_data = $freshData;
                $freshFetched = true;
            }
        }

        if ($freshFetched && $tsv_data && !candybirdTsvDataLooksSafeToCache($cacheKey, $tsv_data)) {
            error_log('Sir Francis sheet fetch ignored because product row count looked unsafe: ' . candybirdTsvValidProductCount($tsv_data));
            $tsv_data = is_file($cacheFile) ? file_get_contents($cacheFile) : false;
            $freshFetched = false;
        }

        if ($tsv_data && !is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        if ($freshFetched && $tsv_data && is_dir($cacheDir) && is_writable($cacheDir)) {
            @file_put_contents($cacheFile, $tsv_data, LOCK_EX);
        }

        if (!$tsv_data && is_file($cacheFile)) {
            $tsv_data = file_get_contents($cacheFile);
        }

        if (!$tsv_data) {
            return [];
        }

        return parseCandybirdTsvRows($tsv_data);
    }
}

if (!function_exists('getSheetCoupons')) {
    function getSheetCoupons($forceRefresh = false) {
        static $sheetCoupons = null;

        if (!$forceRefresh && $sheetCoupons !== null) {
            return $sheetCoupons;
        }

        $sheetCoupons = [];
        $sheet_url = getCandybirdSheetUrl('coupons');

        foreach (fetchCandybirdTsvSheet($sheet_url, 'coupons', 120, $forceRefresh) as $coupon) {
            $code = strtoupper(trim($coupon['coupon_code'] ?? $coupon['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $coupon['coupon_code'] = $code;
            $coupon['discount_type'] = strtolower(trim($coupon['discount_type'] ?? $coupon['type'] ?? 'percentage'));
            $coupon['discount_value'] = candybirdParseSheetMoney($coupon['discount_value'] ?? $coupon['discount'] ?? $coupon['discount_percent'] ?? $coupon['value'] ?? 0);
            $coupon['min_order_value'] = candybirdParseSheetMoney($coupon['min_order_value'] ?? 0);
            $coupon['valid_on_sale_items'] = strtolower(trim($coupon['valid_on_sale_items'] ?? 'no'));
            if (!isset($sheetCoupons[$code])) {
                $sheetCoupons[$code] = [];
            }
            $sheetCoupons[$code][] = $coupon;
        }

        return $sheetCoupons;
    }
}

if (!function_exists('parseCandybirdCouponDate')) {
    function parseCandybirdCouponDate($dateValue, $endOfDay = false) {
        $dateValue = trim((string) $dateValue);
        if ($dateValue === '') {
            return null;
        }

        $formats = ['d-m-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format . ' H:i:s', $dateValue . ' 00:00:00', new DateTimeZone('Africa/Johannesburg'));
            if ($date instanceof DateTime) {
                if ($endOfDay) {
                    $date->modify('+1 day');
                }
                return $date;
            }
        }

        try {
            return new DateTime($dateValue, new DateTimeZone('Africa/Johannesburg'));
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('getSheetCouponByCode')) {
    function getSheetCouponByCode($couponCode) {
        $couponCode = strtoupper(trim((string) $couponCode));
        $coupons = getSheetCoupons();
        $couponRows = $coupons[$couponCode] ?? [];

        if (empty($couponRows)) {
            return null;
        }

        usort($couponRows, function ($a, $b) {
            return ((float) ($b['min_order_value'] ?? 0)) <=> ((float) ($a['min_order_value'] ?? 0));
        });

        return $couponRows[0];
    }
}

if (!function_exists('getSheetCouponRowsByCode')) {
    function getSheetCouponRowsByCode($couponCode) {
        $couponCode = strtoupper(trim((string) $couponCode));
        $coupons = getSheetCoupons();
        return $coupons[$couponCode] ?? [];
    }
}

if (!function_exists('candybirdNormalizeCouponEmail')) {
    function candybirdNormalizeCouponEmail($email) {
        $email = strtolower(trim((string) $email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }
}

if (!function_exists('candybirdCouponContextEmail')) {
    function candybirdCouponContextEmail($context = []) {
        if (!is_array($context)) {
            return '';
        }

        foreach (['email', 'billing_email_address', 'customer_email'] as $key) {
            if (!empty($context[$key])) {
                $email = candybirdNormalizeCouponEmail($context[$key]);
                if ($email !== '') {
                    return $email;
                }
            }
        }

        return '';
    }
}

if (!function_exists('candybirdNormalizePhone')) {
    function candybirdNormalizePhone($phone) {
        $rawPhone = trim((string) $phone);
        if ($rawPhone === '') {
            return '';
        }

        $rawPhone = trim($rawPhone, " \t\n\r\0\x0B'");

        if (preg_match('/^[+-]?\d+(?:[.,]\d+)?e[+-]?\d+$/i', $rawPhone)) {
            $scientificPhone = str_replace(',', '.', $rawPhone);
            $number = (float) $scientificPhone;
            if ($number > 0) {
                $rawPhone = sprintf('%.0F', $number);
            }
        }

        $digits = preg_replace('/\D+/', '', $rawPhone);
        if ($digits === '') {
            return '';
        }
        if (strpos($digits, '0027') === 0) {
            $digits = '27' . substr($digits, 4);
        }
        if (strpos($digits, '27') === 0 && strlen($digits) === 11) {
            return '0' . substr($digits, 2);
        }
        if (strlen($digits) === 9) {
            return '0' . $digits;
        }
        return $digits;
    }
}

if (!function_exists('candybirdCouponContextPhone')) {
    function candybirdCouponContextPhone($context = []) {
        if (!is_array($context)) {
            return '';
        }
        foreach (['phone', 'billing_phone_number', 'customer_phone'] as $key) {
            if (!empty($context[$key])) {
                $phone = candybirdNormalizePhone($context[$key]);
                if ($phone !== '') {
                    return $phone;
                }
            }
        }
        return '';
    }
}

if (!function_exists('candybirdEnsureCouponEmailUsageTable')) {
    function candybirdEnsureCouponEmailUsageTable($conn) {
        if (!($conn instanceof mysqli)) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS coupon_email_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coupon_code VARCHAR(120) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(40) NULL,
            order_id INT NULL,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY coupon_email_once (coupon_code, email),
            KEY coupon_email_order (order_id)
        )";

        $ok = mysqli_query($conn, $sql) !== false;
        if ($ok) {
            $phoneCheck = $conn->query("SHOW COLUMNS FROM coupon_email_usage LIKE 'phone'");
            if ($phoneCheck && $phoneCheck->num_rows === 0) {
                $conn->query("ALTER TABLE coupon_email_usage ADD COLUMN phone VARCHAR(40) NULL AFTER email");
                $conn->query("CREATE INDEX coupon_phone_lookup ON coupon_email_usage (coupon_code, phone)");
            }
        }
        return $ok;
    }
}

if (!function_exists('candybirdCouponEmailAlreadyUsed')) {
    function candybirdCouponEmailAlreadyUsed($conn, $couponCode, $email, $excludeOrderId = null) {
        $email = candybirdNormalizeCouponEmail($email);
        $couponCode = strtoupper(trim((string) $couponCode));
        if (!($conn instanceof mysqli) || $couponCode === '' || $email === '') {
            return false;
        }

        if (!candybirdEnsureCouponEmailUsageTable($conn)) {
            return false;
        }

        $sql = "SELECT order_id FROM coupon_email_usage WHERE coupon_code = ? AND email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ss', $couponCode, $email);
        mysqli_stmt_execute($stmt);
        $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);

        if (!$row) {
            return false;
        }

        if ($excludeOrderId !== null && (int) ($row['order_id'] ?? 0) === (int) $excludeOrderId) {
            return false;
        }

        return true;
    }
}

if (!function_exists('candybirdCouponPhoneAlreadyUsed')) {
    function candybirdCouponPhoneAlreadyUsed($conn, $couponCode, $phone, $excludeOrderId = null) {
        $phone = candybirdNormalizePhone($phone);
        $couponCode = strtoupper(trim((string) $couponCode));
        if (!($conn instanceof mysqli) || $couponCode === '' || $phone === '') {
            return false;
        }
        if (!candybirdEnsureCouponEmailUsageTable($conn)) {
            return false;
        }
        $stmt = mysqli_prepare($conn, "SELECT order_id FROM coupon_email_usage WHERE coupon_code = ? AND phone = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ss', $couponCode, $phone);
        mysqli_stmt_execute($stmt);
        $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);
        if (!$row) {
            return false;
        }
        if ($excludeOrderId !== null && (int) ($row['order_id'] ?? 0) === (int) $excludeOrderId) {
            return false;
        }
        return true;
    }
}

if (!function_exists('candybirdCouponUsageCount')) {
    function candybirdCouponUsageCount($conn, $couponCode) {
        $couponCode = strtoupper(trim((string) $couponCode));
        if (!($conn instanceof mysqli) || $couponCode === '') {
            return 0;
        }
        if (!candybirdEnsureCouponEmailUsageTable($conn)) {
            return 0;
        }
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS used_count FROM coupon_email_usage WHERE coupon_code = ?");
        if (!$stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, 's', $couponCode);
        mysqli_stmt_execute($stmt);
        $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);
        return (int) ($row['used_count'] ?? 0);
    }
}

if (!function_exists('candybirdRecordCouponEmailUsage')) {
    function candybirdRecordCouponEmailUsage($conn, $couponCode, $email, $orderId = null, $phone = '') {
        $email = candybirdNormalizeCouponEmail($email);
        $phone = candybirdNormalizePhone($phone);
        $couponCode = strtoupper(trim((string) $couponCode));
        $orderId = $orderId !== null ? (int) $orderId : null;
        if (!($conn instanceof mysqli) || $couponCode === '' || $email === '') {
            return false;
        }

        if (!candybirdEnsureCouponEmailUsageTable($conn)) {
            return false;
        }

        $stmtExisting = mysqli_prepare($conn, "SELECT order_id FROM coupon_email_usage WHERE coupon_code = ? AND email = ? LIMIT 1");
        if (!$stmtExisting) {
            return false;
        }
        mysqli_stmt_bind_param($stmtExisting, 'ss', $couponCode, $email);
        mysqli_stmt_execute($stmtExisting);
        $existing = mysqli_stmt_get_result($stmtExisting)->fetch_assoc();
        mysqli_stmt_close($stmtExisting);

        if ($existing) {
            return $orderId !== null && (int) ($existing['order_id'] ?? 0) === $orderId;
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO coupon_email_usage (coupon_code, email, phone, order_id) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'sssi', $couponCode, $email, $phone, $orderId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}

if (!function_exists('candybirdEnsureSubscribersTable')) {
    function candybirdEnsureSubscribersTable($conn) {
        if (!($conn instanceof mysqli)) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS subscribers (
            id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            is_subscribed TINYINT(2) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY subscriber_email_unique (email)
        )";

        return mysqli_query($conn, $sql) !== false;
    }
}

if (!function_exists('candybirdEmailIsSubscribed')) {
    function candybirdEmailIsSubscribed($conn, $email) {
        $email = candybirdNormalizeCouponEmail($email);
        if (!($conn instanceof mysqli) || $email === '') {
            return false;
        }

        if (!candybirdEnsureSubscribersTable($conn)) {
            return false;
        }

        $stmt = mysqli_prepare($conn, "SELECT id FROM subscribers WHERE LOWER(email) = ? AND is_subscribed = 1 LIMIT 1");
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $isSubscribed = $result && mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        return $isSubscribed;
    }
}

if (!function_exists('candybirdCouponRequiresSubscribedEmail')) {
    function candybirdCouponRequiresSubscribedEmail($coupon) {
        $code = strtoupper(trim((string) ($coupon['coupon_code'] ?? $coupon['code'] ?? '')));
        if ($code === 'SUBSCRIBENOW') {
            return true;
        }

        foreach (['subscriber_only', 'subscription_only', 'requires_subscription', 'valid_for_subscribers'] as $field) {
            $value = strtolower(trim((string) ($coupon[$field] ?? '')));
            if (in_array($value, ['yes', 'y', 'true', '1'], true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('selectBestSheetCouponForCart')) {
    function selectBestSheetCouponForCart($couponCode, $cartItems, $context = []) {
        $couponRows = getSheetCouponRowsByCode($couponCode);
        $bestCoupon = null;
        $bestDiscount = null;
        $nextTier = null;
        $invalidMessage = 'Invalid coupon code.';

        usort($couponRows, function ($a, $b) {
            return ((float) ($a['min_order_value'] ?? 0)) <=> ((float) ($b['min_order_value'] ?? 0));
        });

        foreach ($couponRows as $coupon) {
            $validation = validateSheetCoupon($coupon, false, $context);
            if (!$validation['valid']) {
                $invalidMessage = $validation['message'];
                continue;
            }

            $discountDetails = calculateSheetCouponDiscount($coupon, $cartItems);
            if ($discountDetails['coupon_savings'] > 0) {
                $bestCoupon = $coupon;
                $bestDiscount = $discountDetails;
                continue;
            }

            if ($nextTier === null) {
                $nextTier = [
                    'coupon' => $coupon,
                    'details' => $discountDetails,
                ];
            }
        }

        if ($bestCoupon) {
            return [
                'valid' => true,
                'coupon' => $bestCoupon,
                'discount' => $bestDiscount,
                'message' => $bestDiscount['message'],
            ];
        }

        if ($nextTier) {
            return [
                'valid' => false,
                'coupon' => $nextTier['coupon'],
                'discount' => $nextTier['details'],
                'message' => $nextTier['details']['message'],
            ];
        }

        return [
            'valid' => false,
            'coupon' => null,
            'discount' => null,
            'message' => $invalidMessage,
        ];
    }
}

if (!function_exists('validateSheetCoupon')) {
    function validateSheetCoupon($coupon, $requireDiscountValue = true, $context = []) {
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code.'];
        }

        $couponCode = strtoupper(trim((string) ($coupon['coupon_code'] ?? $coupon['code'] ?? '')));
        $email = candybirdCouponContextEmail($context);
        $phone = candybirdCouponContextPhone($context);
        $conn = is_array($context) && isset($context['conn']) && $context['conn'] instanceof mysqli ? $context['conn'] : null;
        $excludeOrderId = is_array($context) && isset($context['exclude_order_id']) ? (int) $context['exclude_order_id'] : null;

        $now = new DateTime('now', new DateTimeZone('Africa/Johannesburg'));
        if (is_array($context) && !empty($context['now'])) {
            try {
                $now = new DateTime((string) $context['now'], new DateTimeZone('Africa/Johannesburg'));
            } catch (Exception $exception) {
                $now = new DateTime('now', new DateTimeZone('Africa/Johannesburg'));
            }
        }
        $validFrom = parseCandybirdCouponDate($coupon['valid_from'] ?? '');
        $validUntil = parseCandybirdCouponDate($coupon['valid_until'] ?? '', true);

        if ($validFrom && $now < $validFrom) {
            return ['valid' => false, 'message' => 'This coupon is not active yet.'];
        }

        if ($validUntil && $now >= $validUntil) {
            return ['valid' => false, 'message' => 'This coupon has expired.'];
        }

        if ($requireDiscountValue && (float) ($coupon['discount_value'] ?? 0) <= 0) {
            return ['valid' => false, 'message' => 'This coupon is active, but no discount value has been set yet.'];
        }

        $validCount = (int) ($coupon['valid_count'] ?? $coupon['max_usages'] ?? 0);
        if ($conn && $validCount > 0 && candybirdCouponUsageCount($conn, $couponCode) >= $validCount) {
            return ['valid' => false, 'message' => 'This coupon has reached its usage limit.'];
        }

        $emailRestriction = candybirdNormalizeCouponEmail($coupon['email_restriction'] ?? '');
        if ($emailRestriction !== '') {
            if ($email === '') {
                return ['valid' => false, 'message' => 'Enter your email address before using this coupon.'];
            }
            if ($email !== $emailRestriction) {
                return ['valid' => false, 'message' => 'This coupon is only valid for its assigned email address.'];
            }
        }

        $phoneRestriction = candybirdNormalizePhone($coupon['phone_restriction'] ?? '');
        if ($phoneRestriction !== '') {
            if ($phone === '') {
                return ['valid' => false, 'message' => 'Enter your phone number before using this coupon.'];
            }
            if ($phone !== $phoneRestriction) {
                return ['valid' => false, 'message' => 'This coupon is only valid for its assigned phone number.'];
            }
        }

        $multiUser = strtolower(trim((string) ($coupon['multi_user'] ?? 'yes')));
        if (in_array($multiUser, ['no', 'n', 'false', '0'], true) && $conn && $validCount <= 0 && candybirdCouponUsageCount($conn, $couponCode) >= 1) {
            return ['valid' => false, 'message' => 'This exclusive coupon has already been used.'];
        }

        if (candybirdCouponRequiresSubscribedEmail($coupon)) {
            if ($email === '') {
                return ['valid' => false, 'message' => 'Enter your subscribed email address before using this coupon.'];
            }

            if (!$conn || !candybirdEmailIsSubscribed($conn, $email)) {
                return ['valid' => false, 'message' => 'This coupon is only valid for subscribed email addresses.'];
            }
        }

        if ($conn && $email !== '' && candybirdCouponEmailAlreadyUsed($conn, $couponCode, $email, $excludeOrderId)) {
            return ['valid' => false, 'message' => 'This coupon has already been used with this email address.'];
        }

        if ($conn && $phone !== '' && candybirdCouponPhoneAlreadyUsed($conn, $couponCode, $phone, $excludeOrderId)) {
            return ['valid' => false, 'message' => 'This coupon has already been used with this phone number.'];
        }

        return ['valid' => true, 'message' => 'Coupon is valid.'];
    }
}

if (!function_exists('candybirdCouponListValues')) {
    function candybirdCouponListValues($value) {
        if (is_array($value)) {
            $pieces = $value;
        } else {
            $pieces = preg_split('/[,;\n|]+/', (string) $value);
        }
        $values = [];
        foreach ($pieces as $piece) {
            $piece = trim((string) $piece);
            if ($piece !== '') {
                $values[] = $piece;
            }
        }
        return $values;
    }
}

if (!function_exists('candybirdCouponNormalizedToken')) {
    function candybirdCouponNormalizedToken($value) {
        if (function_exists('normalizeCandybirdProductSlug')) {
            return normalizeCandybirdProductSlug($value);
        }
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim((string) $value, '-');
    }
}

if (!function_exists('candybirdCouponFieldValue')) {
    function candybirdCouponFieldValue($coupon, $fields) {
        foreach ($fields as $field) {
            if (isset($coupon[$field]) && trim((string) $coupon[$field]) !== '') {
                return (string) $coupon[$field];
            }
        }
        return '';
    }
}

if (!function_exists('candybirdCouponCategoryTokens')) {
    function candybirdCouponCategoryTokens($category) {
        $category = trim((string) $category);
        if ($category === '') {
            return [];
        }

        $tokens = [candybirdCouponNormalizedToken($category)];
        if (function_exists('getCandybirdCategoryDisplayLabel')) {
            $tokens[] = candybirdCouponNormalizedToken(getCandybirdCategoryDisplayLabel($category));
        }
        if (function_exists('getCandybirdCategorySlug')) {
            $tokens[] = candybirdCouponNormalizedToken(getCandybirdCategorySlug($category));
        }

        return array_values(array_unique(array_filter($tokens)));
    }
}

if (!function_exists('candybirdCouponCategoryParentMap')) {
    function candybirdCouponCategoryParentMap() {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $map = [];
        $products = function_exists('getSheetProducts') ? getSheetProducts(false) : [];
        if (!is_array($products)) {
            return $map;
        }

        $addParent = static function($child, $parent) use (&$map) {
            $child = trim((string) $child);
            $parent = trim((string) $parent);
            if ($child === '' || $parent === '') {
                return;
            }

            foreach (candybirdCouponCategoryTokens($child) as $childToken) {
                foreach (candybirdCouponCategoryTokens($parent) as $parentToken) {
                    if ($childToken !== '' && $parentToken !== '' && $childToken !== $parentToken) {
                        $map[$childToken][$parentToken] = true;
                    }
                }
            }
        };

        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }
            $paths = function_exists('getCandybirdProductCategoryPaths')
                ? getCandybirdProductCategoryPaths($product)
                : [array_values(array_filter([
                    trim((string) ($product['parent_category'] ?? '')),
                    trim((string) ($product['child_category_1'] ?? '')),
                    trim((string) ($product['child_category_2'] ?? '')),
                ]))];

            foreach ($paths as $parts) {
                $parts = array_values(array_filter(array_map('trim', (array) $parts)));
                $count = count($parts);
                for ($i = 1; $i < $count; $i++) {
                    $addParent($parts[$i], $parts[$i - 1]);
                    for ($ancestor = 0; $ancestor < $i - 1; $ancestor++) {
                        $addParent($parts[$i], $parts[$ancestor]);
                    }
                }
            }
        }

        return $map;
    }
}

if (!function_exists('candybirdCouponCategoryAncestorTokens')) {
    function candybirdCouponCategoryAncestorTokens($category) {
        $map = candybirdCouponCategoryParentMap();
        $tokens = candybirdCouponCategoryTokens($category);
        $seen = array_fill_keys($tokens, true);
        $queue = $tokens;

        while (!empty($queue)) {
            $token = array_shift($queue);
            foreach (array_keys($map[$token] ?? []) as $parentToken) {
                if ($parentToken !== '' && empty($seen[$parentToken])) {
                    $seen[$parentToken] = true;
                    $queue[] = $parentToken;
                }
            }
        }

        return array_keys($seen);
    }
}

if (!function_exists('candybirdCouponItemMatchesCategoryRestriction')) {
    function candybirdCouponItemMatchesCategoryRestriction($coupon, $item) {
        $restrictionValue = candybirdCouponFieldValue($coupon, [
            'category_restriction',
            'category_restrictions',
            'valid_categories',
            'eligible_categories',
            'applies_to_categories',
            'category',
        ]);
        $restrictions = candybirdCouponListValues($restrictionValue);
        if (empty($restrictions)) {
            return true;
        }

        $itemCategories = [];
        $categoryNames = function_exists('getCandybirdProductCategoryNames')
            ? getCandybirdProductCategoryNames($item)
            : array_filter([
                trim((string) ($item['parent_category'] ?? '')),
                trim((string) ($item['child_category_1'] ?? '')),
                trim((string) ($item['child_category_2'] ?? '')),
            ]);
        foreach ($categoryNames as $category) {
            $itemCategories = array_merge($itemCategories, candybirdCouponCategoryAncestorTokens($category));
        }
        $itemCategories = array_unique(array_filter($itemCategories));

        foreach ($restrictions as $restriction) {
            $restrictionToken = candybirdCouponNormalizedToken($restriction);
            if ($restrictionToken !== '' && in_array($restrictionToken, $itemCategories, true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('candybirdCouponItemMatchesProductTypeRestriction')) {
    function candybirdCouponItemMatchesProductTypeRestriction($coupon, $item) {
        $type = candybirdCouponNormalizedToken($item['product_type'] ?? $item['type'] ?? '');

        $excludedTypes = candybirdCouponListValues(candybirdCouponFieldValue($coupon, [
            'product_type_exclusion',
            'product_type_exclusions',
            'excluded_product_types',
            'exclude_product_type',
            'exclude_product_types',
        ]));
        foreach ($excludedTypes as $excludedType) {
            if ($type !== '' && $type === candybirdCouponNormalizedToken($excludedType)) {
                return false;
            }
        }

        $allowedTypes = candybirdCouponListValues(candybirdCouponFieldValue($coupon, [
            'product_type_restriction',
            'product_type_restrictions',
            'valid_product_types',
            'eligible_product_types',
            'applies_to_product_types',
        ]));
        if (empty($allowedTypes)) {
            return true;
        }
        foreach ($allowedTypes as $allowedType) {
            if ($type !== '' && $type === candybirdCouponNormalizedToken($allowedType)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('candybirdCouponItemMatchesProductIdRestriction')) {
    function candybirdCouponItemMatchesProductIdRestriction($coupon, $item) {
        $itemIds = array_filter([
            trim((string) ($item['id'] ?? '')),
            trim((string) ($item['product_id'] ?? '')),
            trim((string) ($item['source_product_id'] ?? '')),
        ]);

        $exclusionValue = candybirdCouponFieldValue($coupon, [
            'product_id_exclusion',
            'product_id_exclusions',
            'excluded_product_ids',
            'exclude_product_id',
            'exclude_product_ids',
        ]);
        $excludedIds = candybirdCouponListValues($exclusionValue);
        foreach ($excludedIds as $excludedId) {
            if (in_array(trim((string) $excludedId), $itemIds, true)) {
                return false;
            }
        }

        $restrictionValue = candybirdCouponFieldValue($coupon, [
            'product_id_restriction',
            'product_id_restrictions',
            'valid_product_ids',
            'eligible_product_ids',
            'applies_to_product_ids',
        ]);
        $allowedIds = candybirdCouponListValues($restrictionValue);
        if (empty($allowedIds)) {
            return true;
        }

        foreach ($allowedIds as $allowedId) {
            if (in_array(trim((string) $allowedId), $itemIds, true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('candybirdCouponItemIsEligible')) {
    function candybirdCouponItemIsEligible($coupon, $item) {
        return candybirdCouponItemMatchesCategoryRestriction($coupon, $item)
            && candybirdCouponItemMatchesProductTypeRestriction($coupon, $item)
            && candybirdCouponItemMatchesProductIdRestriction($coupon, $item);
    }
}

if (!function_exists('candybirdCouponRestrictionMessage')) {
    function candybirdCouponRestrictionMessage($coupon) {
        $categoryValue = candybirdCouponFieldValue($coupon, ['category_restriction', 'category_restrictions', 'valid_categories', 'eligible_categories', 'applies_to_categories', 'category']);
        $typeExclusion = candybirdCouponFieldValue($coupon, ['product_type_exclusion', 'product_type_exclusions', 'excluded_product_types', 'exclude_product_type', 'exclude_product_types']);
        $productIdRestriction = candybirdCouponFieldValue($coupon, ['product_id_restriction', 'product_id_restrictions', 'valid_product_ids', 'eligible_product_ids', 'applies_to_product_ids']);
        $productIdExclusion = candybirdCouponFieldValue($coupon, ['product_id_exclusion', 'product_id_exclusions', 'excluded_product_ids', 'exclude_product_id', 'exclude_product_ids']);
        $parts = [];
        if (trim($categoryValue) !== '') {
            $parts[] = 'selected ' . trim($categoryValue) . ' products';
        }
        if (trim($productIdRestriction) !== '') {
            $parts[] = 'only product IDs ' . trim($productIdRestriction);
        }
        if (trim($typeExclusion) !== '') {
            $parts[] = 'excluding ' . trim($typeExclusion) . ' items';
        }
        if (trim($productIdExclusion) !== '') {
            $parts[] = 'excluding product IDs ' . trim($productIdExclusion);
        }
        return $parts ? 'This coupon is valid on ' . implode(', ', $parts) . '.' : 'This coupon is not valid on the products currently in your cart.';
    }
}

if (!function_exists('calculateSheetCouponDiscount')) {
    function calculateSheetCouponDiscount($coupon, $cartItems) {
        $eligibleAmount = 0;
        $allowSaleItems = strtolower(trim($coupon['valid_on_sale_items'] ?? 'no')) === 'yes';
        $minOrderValue = (float) ($coupon['min_order_value'] ?? 0);

        foreach ($cartItems as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $discountedPrice = (float) ($item['discounted_price'] ?? $price);
            $isSaleItem = $discountedPrice < $price || (float) ($item['discount_amount'] ?? 0) > 0;

            if (!$allowSaleItems && $isSaleItem) {
                continue;
            }

            if (!candybirdCouponItemIsEligible($coupon, $item)) {
                continue;
            }

            $eligibleAmount += $price * $quantity;
        }

        if ($eligibleAmount <= 0) {
            return [
                'coupon_savings' => 0,
                'eligible_amount' => 0,
                'total_after_coupon' => 0,
                'message' => candybirdCouponRestrictionMessage($coupon)
            ];
        }

        if ($eligibleAmount < $minOrderValue) {
            return [
                'coupon_savings' => 0,
                'eligible_amount' => $eligibleAmount,
                'total_after_coupon' => $eligibleAmount,
                'message' => 'Add R' . number_format($minOrderValue - $eligibleAmount, 2) . ' more in eligible products to use this coupon.'
            ];
        }

        $discountType = strtolower(trim($coupon['discount_type'] ?? 'percentage'));
        $discountValue = (float) ($coupon['discount_value'] ?? 0);
        $discount = 0;

        if ($discountType === 'fixed') {
            $discount = min($discountValue, $eligibleAmount);
        } else {
            $discount = ($eligibleAmount * $discountValue) / 100;
        }

        return [
            'coupon_savings' => round($discount, 2),
            'eligible_amount' => $eligibleAmount,
            'total_after_coupon' => max(0, $eligibleAmount - $discount),
            'message' => 'Coupon applied.'
        ];
    }
}

if (!function_exists('getSheetProductById')) {
    function getSheetProductById($productId) {
        $products = getSheetProducts();
        $productId = (string) $productId;
        return isset($products[$productId]) ? $products[$productId] : null;
    }
}

if (!function_exists('normalizeCandybirdProductSlug')) {
    function normalizeCandybirdProductSlug($slug) {
        $slug = strtolower(trim((string) $slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim((string) $slug, '-');
    }
}

if (!function_exists('getSheetProductSlug')) {
    function getSheetProductSlug($product) {
        $slug = normalizeCandybirdProductSlug($product['slug'] ?? '');
        if ($slug !== '') {
            return $slug;
        }

        $title = trim((string) ($product['name'] ?? $product['title'] ?? ''));
        $size = trim((string) ($product['size'] ?? $product['weight'] ?? ''));
        if (strtolower((string) ($product['is_clearance'] ?? '')) === 'yes') {
            $title = preg_replace('/\bclearance\b/i', '', $title);
            $clearanceSlug = normalizeCandybirdProductSlug(trim($title . ' ' . $size . ' clearance'));
            if ($clearanceSlug !== '') {
                return $clearanceSlug;
            }
        }
        $generatedSlug = normalizeCandybirdProductSlug(trim($title . ' ' . $size));
        if ($generatedSlug !== '') {
            return $generatedSlug;
        }

        $id = normalizeCandybirdProductSlug($product['id'] ?? '');
        return $id !== '' ? 'product-' . $id : '';
    }
}

if (!function_exists('getSheetProductBySlug')) {
    function getSheetProductBySlug($slug) {
        $slug = normalizeCandybirdProductSlug($slug);
        if ($slug === '') {
            return null;
        }

        $products = function_exists('getSheetProductsWithClearance') ? getSheetProductsWithClearance() : getSheetProducts();
        $clearanceProducts = [];
        $regularProducts = [];
        foreach ($products as $product) {
            if (strtolower((string) ($product['is_clearance'] ?? '')) === 'yes') {
                $clearanceProducts[] = $product;
            } else {
                $regularProducts[] = $product;
            }
        }

        foreach (array_merge($clearanceProducts, $regularProducts) as $product) {
            if (getSheetProductSlug($product) === $slug) {
                return $product;
            }
        }

        if (preg_match('/^product-(\d+)$/', $slug, $matches)) {
            return getSheetProductById($matches[1]);
        }

        return null;
    }
}

if (!function_exists('getCandybirdCategorySlug')) {
    function getCandybirdCategorySlug($categoryName) {
        $normalizedName = normalizeCandybirdProductSlug($categoryName);
        if (in_array($normalizedName, ['special', 'specials', 'sale', 'sales'], true)) {
            return 'specials';
        }
        return normalizeCandybirdProductSlug($categoryName);
    }
}

if (!function_exists('getCandybirdCategoryUrl')) {
    function getCandybirdCategoryUrl($categoryName, $absolute = false) {
        $slug = getCandybirdCategorySlug(function_exists('getCandybirdCategoryDisplayLabel') ? getCandybirdCategoryDisplayLabel($categoryName) : $categoryName);
        if ($slug === '') {
            $slug = getCandybirdCategorySlug($categoryName);
        }
        $path = $slug !== '' ? '/' . rawurlencode($slug) : '/products';
        return $absolute ? sirFrancisSiteUrl($path) : ltrim($path, '/');
    }
}

if (!function_exists('getCandybirdCategoryBySlug')) {
    function getCandybirdCategoryBySlug($slug) {
        $slug = normalizeCandybirdProductSlug($slug);
        if ($slug === '') {
            return '';
        }
        if (in_array($slug, ['special', 'specials', 'sale', 'sales'], true)) {
            return 'Specials';
        }

        $products = function_exists('getSheetProductsWithClearance') ? getSheetProductsWithClearance() : getSheetProducts();
        $categories = [];
        foreach ($products as $product) {
            $categoryNames = function_exists('getCandybirdProductCategoryNames')
                ? getCandybirdProductCategoryNames($product)
                : array_filter([
                    trim((string) ($product['parent_category'] ?? '')),
                    trim((string) ($product['child_category_1'] ?? '')),
                    trim((string) ($product['child_category_2'] ?? '')),
                ]);
            foreach ($categoryNames as $category) {
                $category = trim((string) $category);
                if ($category !== '') {
                    $categories[$category] = true;
                }
            }
        }

        foreach (array_keys($categories) as $category) {
            $displayLabel = function_exists('getCandybirdCategoryDisplayLabel') ? getCandybirdCategoryDisplayLabel($category) : $category;
            if (getCandybirdCategorySlug($displayLabel) === $slug || getCandybirdCategorySlug($category) === $slug) {
                return $category;
            }
        }

        return '';
    }
}

if (!function_exists('getSheetProductUrl')) {
    function getSheetProductUrl($product, $absolute = false) {
        $slug = getSheetProductSlug($product);
        $path = $slug !== ''
            ? '/' . rawurlencode($slug)
            : '/product';
        return $absolute ? sirFrancisSiteUrl($path) : ltrim($path, '/');
    }
}

if (!function_exists('getSheetProductStockQty')) {
    function getSheetProductStockQty($product) {
        foreach (['qty_available', 'stock_qty', 'qty_in_stock', 'quantity_available', 'available_qty', 'inventory', 'stock'] as $field) {
            if (isset($product[$field]) && trim((string) $product[$field]) !== '' && is_numeric($product[$field])) {
                return max(0, (int) floor((float) $product[$field]));
            }
        }

        return null;
    }
}

if (!function_exists('ensureCandybirdCartTimestampColumn')) {
    function ensureCandybirdCartTimestampColumn($conn) {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }
        if (!($conn instanceof mysqli)) {
            $checked = false;
            return false;
        }
        $columnCheck = $conn->query("SHOW COLUMNS FROM cart LIKE 'updated_at'");
        if ($columnCheck && $columnCheck->num_rows > 0) {
            $checked = true;
            return true;
        }
        $checked = $conn->query("ALTER TABLE cart ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP") !== false;
        return $checked;
    }
}

if (!function_exists('getCandybirdActiveCartReservedQty')) {
    function getCandybirdActiveCartReservedQty($conn, $productId, $excludeUserId = null, $excludeGuestIdentifier = '', $clearanceId = '') {
        if (!($conn instanceof mysqli)) {
            return 0;
        }

        $productId = (int) $productId;
        if ($productId <= 0) {
            return 0;
        }

        ensureCandybirdCartTimestampColumn($conn);
        ensureCandybirdCartClearanceColumns($conn);
        $clearanceId = strtoupper(trim((string) $clearanceId));
        $sql = "SELECT COALESCE(SUM(quantity), 0) AS reserved_qty
            FROM cart
            WHERE product_id = ?
              AND COALESCE(clearance_id, '') = ?
              AND (updated_at IS NULL OR updated_at >= DATE_SUB(NOW(), INTERVAL 3 HOUR))
              AND NOT ((? IS NOT NULL AND user_id = ?) OR (? <> '' AND guest_identifier = ?))";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $excludeUserId = $excludeUserId !== null ? (int) $excludeUserId : null;
        $excludeGuestIdentifier = trim((string) $excludeGuestIdentifier);
        $stmt->bind_param('isiiss', $productId, $clearanceId, $excludeUserId, $excludeUserId, $excludeGuestIdentifier, $excludeGuestIdentifier);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['reserved_qty'] ?? 0);
    }
}

if (!function_exists('getCandybirdAvailableStockForCart')) {
    function getCandybirdAvailableStockForCart($conn, $product, $excludeUserId = null, $excludeGuestIdentifier = '') {
        $sheetStock = getSheetProductStockQty($product);
        if ($sheetStock === null) {
            return null;
        }

        $reservedProductId = (int) ($product['source_product_id'] ?? $product['id'] ?? 0);
        $reserved = getCandybirdActiveCartReservedQty($conn, $reservedProductId, $excludeUserId, $excludeGuestIdentifier, $product['clearance_id'] ?? '');
        return max(0, $sheetStock - $reserved);
    }
}

if (!function_exists('isSheetProductDigital')) {
    function isSheetProductDigital($product) {
        $size = strtolower(trim((string) ($product['size'] ?? $product['weight'] ?? '')));
        $productType = strtolower(trim((string) ($product['product_type'] ?? $product['type'] ?? $product['delivery_type'] ?? '')));
        $categories = strtolower(trim(implode(' ', [
            $product['parent_category'] ?? '',
            $product['child_category_1'] ?? '',
            $product['child_category_2'] ?? '',
            $product['additional_categories'] ?? '',
            $product['name'] ?? '',
            $product['title'] ?? '',
        ])));

        return $size === '0' ||
            $size === '0g' ||
            $size === '0 g' ||
            $size === '0kg' ||
            $size === '0 kg' ||
            strpos($productType, 'digital') !== false ||
            strpos($productType, 'ebook') !== false ||
            strpos($productType, 'e-book') !== false ||
            strpos($productType, 'voucher') !== false ||
            strpos($categories, 'voucher') !== false ||
            strpos($categories, 'e-book') !== false ||
            strpos($categories, 'ebook') !== false;
    }
}

if (!function_exists('getSheetProductDisplaySize')) {
    function getSheetProductDisplaySize($product) {
        if (isSheetProductDigital($product)) {
            return '';
        }

        return trim((string) ($product['size'] ?? $product['weight'] ?? ''));
    }
}

if (!function_exists('getSheetProductDisplayTitle')) {
    function getSheetProductDisplayTitle($product) {
        $name = trim((string) ($product['name'] ?? $product['title'] ?? ''));
        $size = getSheetProductDisplaySize($product);
        if ($size !== '' && stripos($name, $size) !== false) {
            return $name;
        }
        return trim($name . ($size !== '' ? ' ' . $size : ''));
    }
}

if (!function_exists('normalizeCandybirdSearchText')) {
    function normalizeCandybirdSearchText($value) {
        $value = strtolower((string) $value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }
}

if (!function_exists('getSheetProductSearchText')) {
    function getSheetProductSearchText($product) {
        $fields = [
            'id', 'name', 'title', 'size', 'weight', 'price',
            'parent_category', 'child_category_1', 'child_category_2', 'additional_categories',
            'category', 'category_name', 'description', 'short_description',
            'flavour', 'flavor', 'label', 'tags'
        ];

        $parts = [];
        foreach ($fields as $field) {
            if (!empty($product[$field])) {
                $parts[] = $product[$field];
            }
        }
        if (function_exists('getCandybirdProductCategoryNames')) {
            $parts = array_merge($parts, getCandybirdProductCategoryNames($product));
        }
        if (function_exists('getCandybirdProductCategoryPaths')) {
            foreach (getCandybirdProductCategoryPaths($product) as $path) {
                $parts[] = implode(' ', $path);
                $parts[] = implode(' > ', $path);
            }
        }

        return normalizeCandybirdSearchText(implode(' ', $parts));
    }
}

if (!function_exists('searchSheetProducts')) {
    function searchSheetProducts($query, $limit = null) {
        $query = normalizeCandybirdSearchText($query);
        if ($query === '') {
            return [];
        }

        $rawTokens = array_filter(explode(' ', $query));
        $tokenGroups = [];
        foreach ($rawTokens as $token) {
            $variants = [$token];
            if (strlen($token) > 3 && substr($token, -1) === 's') {
                $variants[] = substr($token, 0, -1);
            }
            $tokenGroups[] = array_values(array_unique($variants));
        }
        $tokens = array_values(array_unique(array_merge(...$tokenGroups)));

        $matches = [];
        $products = function_exists('getSheetProductsWithClearance') ? getSheetProductsWithClearance() : getSheetProducts();
        foreach ($products as $product) {
            $stockQty = getSheetProductStockQty($product);
            if ($stockQty !== null && $stockQty <= 0) {
                continue;
            }

            $haystack = getSheetProductSearchText($product);
            $name = normalizeCandybirdSearchText($product['name'] ?? $product['title'] ?? '');
            $displayTitle = normalizeCandybirdSearchText(getSheetProductDisplayTitle($product));
            $score = 0;
            $coverage = 0;

            if ($displayTitle === $query) {
                $score += 800;
            } elseif ($name === $query) {
                $score += 700;
            } elseif (strpos($displayTitle, $query) !== false) {
                $score += 520;
            } elseif (strpos($name, $query) !== false) {
                $score += 420;
            } elseif (strpos($haystack, $query) !== false) {
                $score += 260;
            }

            foreach ($tokenGroups as $variants) {
                $matchedThisToken = false;
                foreach ($variants as $variant) {
                    if ($variant !== '' && strpos($haystack, $variant) !== false) {
                        $matchedThisToken = true;
                        break;
                    }
                }
                if ($matchedThisToken) {
                    $coverage++;
                }
            }

            if ($coverage === count($tokenGroups) && count($tokenGroups) > 1) {
                $score += 220;
            }

            foreach ($tokens as $token) {
                if ($token === '') {
                    continue;
                }

                if ($displayTitle === $token || $name === $token) {
                    $score += 100;
                } elseif (preg_match('/\b' . preg_quote($token, '/') . '\b/', $displayTitle)) {
                    $score += 75;
                } elseif (preg_match('/\b' . preg_quote($token, '/') . '\b/', $name)) {
                    $score += 65;
                } elseif (strpos($name, $token) !== false) {
                    $score += 35;
                } elseif (strpos($haystack, $token) !== false) {
                    $score += 18;
                }
            }

            if ($score > 0) {
                $product['_search_score'] = $score;
                $product['_search_coverage'] = $coverage;
                $matches[] = $product;
            }
        }

        usort($matches, function ($a, $b) {
            if (($b['_search_coverage'] ?? 0) !== ($a['_search_coverage'] ?? 0)) {
                return ($b['_search_coverage'] ?? 0) <=> ($a['_search_coverage'] ?? 0);
            }
            if (($b['_search_score'] ?? 0) === ($a['_search_score'] ?? 0)) {
                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            }
            return ($b['_search_score'] ?? 0) <=> ($a['_search_score'] ?? 0);
        });

        if ($limit !== null) {
            return array_slice($matches, 0, (int) $limit);
        }

        return $matches;
    }
}

if (!function_exists('ensureCandybirdSiteFlagsTable')) {
    function ensureCandybirdSiteFlagsTable($conn) {
        static $checked = false;
        if ($checked) {
            return true;
        }
        if (!($conn instanceof mysqli)) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS candybird_site_flags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flag_type VARCHAR(40) NOT NULL DEFAULT 'notice',
            title VARCHAR(160) NOT NULL DEFAULT '',
            label_text TEXT NOT NULL,
            placements VARCHAR(255) NOT NULL DEFAULT 'all',
            starts_at DATETIME NULL,
            ends_at DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_by_admin_id INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_site_flags_status_dates (status, starts_at, ends_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $checked = (bool) mysqli_query($conn, $sql);
        return $checked;
    }
}

if (!function_exists('getCandybirdSiteFlagTypes')) {
    function getCandybirdSiteFlagTypes() {
        return [
            'shop_closed' => 'Close shop / delayed processing',
            'maintenance' => 'Maintenance notice',
            'notice' => 'General notice',
        ];
    }
}

if (!function_exists('getCandybirdSiteFlagPlacements')) {
    function getCandybirdSiteFlagPlacements() {
        return [
            'all' => 'All shop pages',
            'products' => 'Products listing',
            'product' => 'Product details',
            'checkout' => 'Checkout',
            'cart' => 'Cart',
        ];
    }
}

if (!function_exists('normalizeCandybirdSiteFlagPlacements')) {
    function normalizeCandybirdSiteFlagPlacements($placements) {
        $allowed = array_keys(getCandybirdSiteFlagPlacements());
        $values = is_array($placements) ? $placements : preg_split('/\s*,\s*/', (string) $placements);
        $clean = [];
        foreach ($values as $placement) {
            $placement = strtolower(trim((string) $placement));
            if ($placement !== '' && in_array($placement, $allowed, true)) {
                $clean[] = $placement;
            }
        }
        $clean = array_values(array_unique($clean));
        return empty($clean) ? ['all'] : $clean;
    }
}

if (!function_exists('getCandybirdActiveSiteFlags')) {
    function getCandybirdActiveSiteFlags($placement = 'all') {
        global $conn;
        if (!($conn instanceof mysqli) || !ensureCandybirdSiteFlagsTable($conn)) {
            return [];
        }

        $placement = strtolower(trim((string) $placement));
        if ($placement === '') {
            $placement = 'all';
        }

        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM candybird_site_flags
                WHERE status = 'active'
                  AND (starts_at IS NULL OR starts_at = '0000-00-00 00:00:00' OR starts_at <= ?)
                  AND (ends_at IS NULL OR ends_at = '0000-00-00 00:00:00' OR ends_at >= ?)
                  AND (placements = '' OR FIND_IN_SET('all', REPLACE(placements, ' ', '')) OR FIND_IN_SET(?, REPLACE(placements, ' ', '')))
                ORDER BY FIELD(flag_type, 'shop_closed', 'maintenance', 'notice'), COALESCE(starts_at, created_at) DESC, id DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sss', $now, $now, $placement);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('getCandybirdShowingSiteFlags')) {
    function getCandybirdSiteFlagsPublicFile() {
        return __DIR__ . '/sheet_cache/site_flags_public.json';
    }

    function candybirdFilterShowingSiteFlags($flags) {
        $now = time();
        $showing = [];
        foreach ((array) $flags as $flag) {
            if (strtolower(trim((string) ($flag['status'] ?? ''))) !== 'active') {
                continue;
            }
            $startsAt = trim((string) ($flag['starts_at'] ?? ''));
            $endsAt = trim((string) ($flag['ends_at'] ?? ''));
            if ($startsAt !== '' && $startsAt !== '0000-00-00 00:00:00' && strtotime($startsAt) > $now) {
                continue;
            }
            if ($endsAt !== '' && $endsAt !== '0000-00-00 00:00:00' && strtotime($endsAt) < $now) {
                continue;
            }
            $showing[] = $flag;
        }
        return $showing;
    }

    function publishCandybirdSiteFlagsPublicCache($flags) {
        $cacheFile = getCandybirdSiteFlagsPublicFile();
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return (bool) @file_put_contents($cacheFile, json_encode(array_values((array) $flags), JSON_PRETTY_PRINT), LOCK_EX);
    }

    function candybirdFetchShowingSiteFlagsFromPublicCache() {
        $cacheFile = getCandybirdSiteFlagsPublicFile();
        if (!is_file($cacheFile)) {
            return [];
        }
        $decoded = json_decode((string) @file_get_contents($cacheFile), true);
        if (!is_array($decoded)) {
            return [];
        }
        return candybirdFilterShowingSiteFlags($decoded);
    }

    function candybirdFetchShowingSiteFlagsFromConnection($siteFlagConn) {
        if (!($siteFlagConn instanceof mysqli) || !ensureCandybirdSiteFlagsTable($siteFlagConn)) {
            return [];
        }

        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM candybird_site_flags
                WHERE status = 'active'
                  AND (starts_at IS NULL OR starts_at = '0000-00-00 00:00:00' OR starts_at <= ?)
                  AND (ends_at IS NULL OR ends_at = '0000-00-00 00:00:00' OR ends_at >= ?)
                ORDER BY FIELD(flag_type, 'shop_closed', 'maintenance', 'notice'), COALESCE(starts_at, created_at) DESC, id DESC";
        $stmt = $siteFlagConn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ss', $now, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    function getCandybirdShowingSiteFlags() {
        global $conn;
        $rows = candybirdFetchShowingSiteFlagsFromConnection($conn ?? null);
        if (!empty($rows)) {
            return $rows;
        }
        return candybirdFetchShowingSiteFlagsFromPublicCache();
    }
}

if (!function_exists('renderCandybirdSiteFlags')) {
    function getCandybirdCurrentSiteFlagPlacement() {
        $page = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '.php'));
        $path = strtolower(trim(parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '', '/'));

        if (in_array($page, ['products', 'fetch_sheet', 'search'], true) || in_array($path, ['products', 'search', 'specials', 'clearance-basket'], true)) {
            return 'products';
        }
        if ($page === 'checkout' || $path === 'checkout') {
            return 'checkout';
        }
        if ($page === 'cart' || $path === 'cart') {
            return 'cart';
        }
        if ($page === 'product') {
            return 'product';
        }
        if ($path !== '' && preg_match('/^[a-z0-9][a-z0-9-]{2,120}$/', $path)) {
            return (function_exists('getCandybirdCategoryBySlug') && getCandybirdCategoryBySlug($path) !== '') ? 'products' : 'product';
        }

        return 'site';
    }

    function renderCandybirdSiteFlags($placement = 'all', $ignorePlacement = false) {
        static $renderedFlagIds = [];
        $flags = $ignorePlacement ? getCandybirdShowingSiteFlags() : getCandybirdActiveSiteFlags($placement);
        if (empty($flags)) {
            return '';
        }

        $flags = array_values(array_filter($flags, static function($flag) use (&$renderedFlagIds) {
            $id = (int) ($flag['id'] ?? 0);
            if ($id > 0 && isset($renderedFlagIds[$id])) {
                return false;
            }
            if ($id > 0) {
                $renderedFlagIds[$id] = true;
            }
            return true;
        }));

        if (empty($flags)) {
            return '';
        }

        $style = '<style>
            body.cb-site-flag-active{padding-top:var(--cb-site-flag-height, 0px);}
            .cb-site-flag-wrap{background:#fff7ed;border-bottom:2px solid #f0c795;left:0;margin:0;padding:8px 14px;position:fixed;right:0;top:0;z-index:2147483000;}
            body.cb-site-flag-active #sticky.is-isticky{top:var(--cb-site-flag-height, 0px);}
            .cb-site-flag{align-items:flex-start;background:#fff;border:1px solid #f0c795;border-left:6px solid #d36b20;border-radius:8px;box-shadow:0 8px 22px rgba(76,43,20,.12);color:#3b2518;display:flex;gap:12px;margin:0 auto 8px;max-width:1180px;padding:10px 14px;}
            .cb-site-flag:last-child{margin-bottom:0;}
            .cb-site-flag.maintenance{background:#f1f7ff;border-color:#b9d2f1;border-left-color:#3267b7;color:#172a43;}
            .cb-site-flag.notice{background:#f8f4ff;border-color:#d8c7ec;border-left-color:#7a42aa;color:#2d193f;}
            .cb-site-flag-icon{align-items:center;border-radius:50%;display:inline-flex;flex:0 0 30px;font-size:14px;height:30px;justify-content:center;background:rgba(255,255,255,.72);}
            .cb-site-flag-body strong{display:block;font-size:15px;line-height:1.2;margin-bottom:2px;}
            .cb-site-flag-body p{font-size:14px;line-height:1.4;margin:0;}
            .cb-site-flag-body small{color:inherit;display:block;font-size:12px;margin-top:5px;opacity:.76;}
            @media(max-width:575px){.cb-site-flag-wrap{padding:7px 8px}.cb-site-flag{padding:9px 10px}.cb-site-flag-icon{display:none}.cb-site-flag-body strong{font-size:14px}.cb-site-flag-body p{font-size:13px}}
        </style>';

        $html = $style . '<div class="cb-site-flag-wrap" role="status" aria-live="polite">';
        foreach ($flags as $flag) {
            $type = (string) ($flag['flag_type'] ?? 'notice');
            $class = $type === 'maintenance' ? 'maintenance' : ($type === 'notice' ? 'notice' : 'shop-closed');
            $icon = $type === 'maintenance' ? 'fa-tools' : ($type === 'notice' ? 'fa-info-circle' : 'fa-store-slash');
            $fallbackTitle = $type === 'maintenance' ? 'Website maintenance notice' : ($type === 'shop_closed' ? 'Shop processing notice' : 'Notice');
            $title = trim((string) ($flag['title'] ?? ''));
            $message = trim((string) ($flag['label_text'] ?? ''));
            $endsAt = trim((string) ($flag['ends_at'] ?? ''));

            $html .= '<div class="cb-site-flag ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<span class="cb-site-flag-icon"><i class="fas ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i></span>';
            $html .= '<div class="cb-site-flag-body">';
            $html .= '<strong>' . htmlspecialchars($title !== '' ? $title : $fallbackTitle, ENT_QUOTES, 'UTF-8') . '</strong>';
            $html .= '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';
            if ($type === 'shop_closed' && $endsAt !== '' && $endsAt !== '0000-00-00 00:00:00') {
                $html .= '<small>Expected processing resumes after ' . htmlspecialchars(date('d M Y H:i', strtotime($endsAt)), ENT_QUOTES, 'UTF-8') . '.</small>';
            }
            $html .= '</div></div>';
        }
        $html .= '</div>';
        $html .= '<script>(function(){function cbSetSiteFlagOffset(){var el=document.querySelector(".cb-site-flag-wrap");if(!el)return;document.body.classList.add("cb-site-flag-active");document.documentElement.style.setProperty("--cb-site-flag-height",el.offsetHeight+"px");}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",cbSetSiteFlagOffset);}else{cbSetSiteFlagOffset();}window.addEventListener("resize",cbSetSiteFlagOffset);})();</script>';

        return $html;
    }
}

if (!function_exists('getSheetProductImage')) {
    function getSheetProductImage($product) {
        $imageValue = $product['img_url'] ?? $product['image_url'] ?? $product['image_urls'] ?? $product['image'] ?? '';
        $images = array_filter(array_map('trim', explode(',', (string) $imageValue)));
        foreach ($images as $image) {
            if ($image === '' || $image === '#' || isSirFrancisLegacyCandybirdAsset($image)) {
                continue;
            }

            $path = parse_url($image, PHP_URL_PATH);
            if ($path && preg_match('#^/assets/#', $path)) {
                $localPath = __DIR__ . '/' . ltrim(urldecode($path), '/');
                if (!is_file($localPath)) {
                    continue;
                }
            } elseif (!preg_match('#^https?://#i', $image) && strpos($image, '//') !== 0) {
                $localPath = __DIR__ . '/' . ltrim(urldecode($image), '/');
                if (!is_file($localPath)) {
                    continue;
                }
            }

            return $image;
        }

        return SIRFRANCIS_PRODUCT_PLACEHOLDER_IMAGE;
    }
}

if (!function_exists('getCandybirdAbsoluteImageUrl')) {
    function getCandybirdAbsoluteImageUrl($imageUrl, $fallback = null) {
        $fallback = $fallback ?: sirFrancisSiteUrl(SIRFRANCIS_PRODUCT_PLACEHOLDER_IMAGE);
        $imageUrl = trim((string) $imageUrl);

        if ($imageUrl === '' || $imageUrl === '#' || isSirFrancisLegacyCandybirdAsset($imageUrl)) {
            return $fallback;
        }

        if (strpos($imageUrl, '//') === 0) {
            return 'https:' . str_replace(' ', '%20', $imageUrl);
        }

        if (preg_match('#^https?://#i', $imageUrl)) {
            return str_replace(' ', '%20', $imageUrl);
        }

        $imageUrl = ltrim($imageUrl, '/');
        return sirFrancisSiteUrl(str_replace(' ', '%20', $imageUrl));
    }
}

if (!function_exists('getSheetProductEmailImage')) {
    function getSheetProductEmailImage($product) {
        return getCandybirdAbsoluteImageUrl(getSheetProductImage($product));
    }
}

if (!function_exists('getSheetProductPrice')) {
    function getSheetProductPrice($product) {
        if (is_array($product) && strtolower((string) ($product['is_clearance'] ?? '')) === 'yes') {
            $clearancePrice = isset($product['discounted_price']) ? candybirdParseSheetMoney($product['discounted_price']) : 0;
            return $clearancePrice > 0 ? $clearancePrice : candybirdParseSheetMoney($product['price'] ?? 0);
        }
        $product = normalizeCandybirdProductSpecial($product);
        $price = isset($product['price']) ? candybirdParseSheetMoney($product['price']) : 0;
        $discountedPrice = isset($product['discounted_price']) ? candybirdParseSheetMoney($product['discounted_price']) : 0;
        $discountAmount = isset($product['discount']) ? candybirdParseSheetMoney($product['discount']) : (isset($product['discount_amount']) ? candybirdParseSheetMoney($product['discount_amount']) : 0);

        if ($discountedPrice > 0) {
            return $discountedPrice;
        }

        if ($discountAmount > 0) {
            return max(0, $price - $discountAmount);
        }

        return $price;
    }
}

if (!function_exists('isCandybirdFreeDeliveryExcluded')) {
    function isCandybirdFreeDeliveryExcluded($product) {
        if (!is_array($product)) {
            return false;
        }

        $value = strtolower(trim((string) ($product['free_delivery_excluded'] ?? $product['free_shipping_excluded'] ?? $product['exclude_free_delivery'] ?? '')));
        return in_array($value, ['yes', 'true', '1', 'y'], true);
    }
}

if (!function_exists('buildSheetCartItem')) {
    function buildSheetCartItem($cartRow) {
        $clearanceId = strtoupper(trim((string) ($cartRow['clearance_id'] ?? '')));
        if (strpos($clearanceId, 'CLR:') === 0) {
            $clearanceId = substr($clearanceId, 4);
        }
        $clearanceRow = $clearanceId !== '' ? getSheetClearanceRowById($clearanceId) : null;
        if ($clearanceId !== '' && !$clearanceRow) {
            return null;
        }
        $product = $clearanceRow ? buildCandybirdClearanceProduct($clearanceRow) : getSheetProductById($cartRow['product_id']);

        if (!$product) {
            return null;
        }

        $price = isset($product['price']) ? candybirdParseSheetMoney($product['price']) : 0;
        $discountedPrice = getSheetProductPrice($product);
        $discountAmount = max(0, $price - $discountedPrice);

        return [
            'id' => $product['id'],
            'product_id' => $product['source_product_id'] ?? $product['id'],
            'source_product_id' => $product['source_product_id'] ?? $product['id'],
            'clearance_id' => $clearanceId,
            'is_clearance' => $clearanceRow ? 'yes' : 'no',
            'clearance_reason' => $product['clearance_reason'] ?? '',
            'clearance_notes' => $product['clearance_notes'] ?? '',
            'parent_category' => $product['parent_category'] ?? '',
            'child_category_1' => $product['child_category_1'] ?? '',
            'child_category_2' => $product['child_category_2'] ?? '',
            'additional_categories' => $product['additional_categories'] ?? '',
            'product_type' => $product['product_type'] ?? '',
            'title' => getSheetProductDisplayTitle($product),
            'product_weight' => getSheetProductDisplaySize($product),
            'weight' => getSheetProductDisplaySize($product),
            'shipping_weight_kg' => getSheetProductWeightKg($product),
            'stock_qty' => getSheetProductStockQty($product),
            'free_delivery_excluded' => isCandybirdFreeDeliveryExcluded($product) ? 'yes' : 'no',
            'price' => $price,
            'discount_rate' => 0,
            'discount_amount' => $discountAmount,
            'discounted_price' => $discountedPrice,
            'original_price' => $price,
            'final_price' => $discountedPrice,
            'tax_amount' => 0,
            'tax_rate' => 0,
            'coupon_code' => $cartRow['coupon_code'] ?? '',
            'quantity' => isset($cartRow['quantity']) ? (int) $cartRow['quantity'] : 1,
            'image_url' => getSheetProductImage($product),
            'product_url' => getSheetProductUrl($product)
        ];
    }
}

if (!function_exists('isCandybirdCartItemUnavailable')) {
    function isCandybirdCartItemUnavailable($item) {
        if (!is_array($item)) {
            return true;
        }

        if (!array_key_exists('stock_qty', $item) || $item['stock_qty'] === null || $item['stock_qty'] === '') {
            return false;
        }

        return (int) $item['stock_qty'] <= 0;
    }
}

if (!function_exists('deleteCandybirdCartRow')) {
    function deleteCandybirdCartRow($conn, $userId, $guestIdentifier, $productId, $clearanceId = '') {
        if (!($conn instanceof mysqli)) {
            return false;
        }

        ensureCandybirdCartClearanceColumns($conn);

        $productId = trim((string) $productId);
        $clearanceId = strtoupper(trim((string) $clearanceId));
        if (strpos($clearanceId, 'CLR:') === 0) {
            $clearanceId = substr($clearanceId, 4);
        }

        if ($productId === '') {
            return false;
        }

        $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ? AND COALESCE(clearance_id, '') = ? AND (user_id = ? OR guest_identifier = ?)");
        if (!$stmt) {
            return false;
        }

        $userId = $userId !== null ? (int) $userId : 0;
        $guestIdentifier = trim((string) $guestIdentifier);
        $stmt->bind_param('ssis', $productId, $clearanceId, $userId, $guestIdentifier);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('ensureCandybirdOrderItemSnapshotColumns')) {
    function ensureCandybirdOrderItemSnapshotColumns($conn) {
        static $checked = null;

        if ($checked !== null) {
            return $checked;
        }

        if (!($conn instanceof mysqli)) {
            $checked = false;
            return false;
        }

        $columns = [
            'product_image_url' => "ALTER TABLE order_items ADD COLUMN product_image_url VARCHAR(512) NULL AFTER product_title",
            'product_weight' => "ALTER TABLE order_items ADD COLUMN product_weight VARCHAR(120) NULL AFTER product_image_url",
            'clearance_id' => "ALTER TABLE order_items ADD COLUMN clearance_id VARCHAR(120) NULL AFTER product_id",
            'clearance_note' => "ALTER TABLE order_items ADD COLUMN clearance_note VARCHAR(255) NULL AFTER product_weight",
        ];

        foreach ($columns as $column => $alterSql) {
            $safeColumn = mysqli_real_escape_string($conn, $column);
            $columnCheck = $conn->query("SHOW COLUMNS FROM order_items LIKE '{$safeColumn}'");
            if ($columnCheck && $columnCheck->num_rows > 0) {
                continue;
            }

            if (!$conn->query($alterSql)) {
                error_log("Sir Francis could not add order item snapshot column {$column}: " . $conn->error);
                $checked = false;
                return false;
            }
        }

        $checked = true;
        return true;
    }
}

if (!function_exists('isSirFrancisLegacyOrderDate')) {
    function isSirFrancisLegacyOrderDate($orderDate) {
        $orderTimestamp = strtotime((string) $orderDate);
        $cutoffTimestamp = strtotime(SIRFRANCIS_LEGACY_ORDER_CUTOFF_DATE . ' 00:00:00');
        return $orderTimestamp !== false && $cutoffTimestamp !== false && $orderTimestamp < $cutoffTimestamp;
    }
}

if (!function_exists('getCandybirdLegacyDbProductSnapshot')) {
    function getCandybirdLegacyDbProductSnapshot($conn, $productId) {
        static $cache = [];

        $productId = (int) $productId;
        if (!($conn instanceof mysqli) || $productId <= 0) {
            return null;
        }

        if (array_key_exists($productId, $cache)) {
            return $cache[$productId];
        }

        $tableCheck = $conn->query("SHOW TABLES LIKE 'product'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            $cache[$productId] = null;
            return null;
        }

        $imageTableCheck = $conn->query("SHOW TABLES LIKE 'images'");
        $hasImagesTable = $imageTableCheck && $imageTableCheck->num_rows > 0;
        $stmt = $conn->prepare($hasImagesTable ? "
            SELECT
                p.id,
                p.title,
                p.price,
                p.discount_rate,
                p.discount_amount,
                p.weight,
                MIN(i.image_url) AS image_url
            FROM product p
            LEFT JOIN images i ON i.product_id = p.id
            WHERE p.id = ?
            GROUP BY p.id, p.title, p.price, p.discount_rate, p.discount_amount, p.weight
            LIMIT 1
        " : "
            SELECT
                p.id,
                p.title,
                p.price,
                p.discount_rate,
                p.discount_amount,
                p.weight,
                '' AS image_url
            FROM product p
            WHERE p.id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            $cache[$productId] = null;
            return null;
        }

        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $snapshot = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$snapshot) {
            $cache[$productId] = null;
            return null;
        }

        $snapshot['title'] = trim((string) ($snapshot['title'] ?? ''));
        $snapshot['weight'] = trim((string) ($snapshot['weight'] ?? ''));
        $snapshot['image_url'] = trim((string) ($snapshot['image_url'] ?? ''));
        $snapshot['price'] = (float) ($snapshot['price'] ?? 0);
        $snapshot['discount_amount'] = (float) ($snapshot['discount_amount'] ?? 0);
        $snapshot['discount_rate'] = (float) ($snapshot['discount_rate'] ?? 0);

        $cache[$productId] = $snapshot;
        return $snapshot;
    }
}

if (!function_exists('getCandybirdOrderItemDisplaySnapshot')) {
    function getCandybirdOrderItemDisplaySnapshot($conn, $item, $orderDate = null, $options = []) {
        $item = is_array($item) ? $item : [];
        $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
        $isLegacyOrder = isSirFrancisLegacyOrderDate($orderDate);
        $legacyProduct = $isLegacyOrder ? getCandybirdLegacyDbProductSnapshot($conn, $productId) : null;
        $sheetProduct = null;
        $allowSheetFallback = array_key_exists('allow_sheet_fallback', $options) ? (bool) $options['allow_sheet_fallback'] : true;

        $title = trim((string) ($item['product_title'] ?? $item['title'] ?? ''));
        $weight = trim((string) ($item['product_weight'] ?? $item['weight'] ?? ''));
        $imageUrl = trim((string) ($item['product_image_url'] ?? $item['image_url'] ?? ''));

        if ($title === '' && $legacyProduct && !empty($legacyProduct['title'])) {
            $title = $legacyProduct['title'];
        }
        if ($weight === '' && $legacyProduct && !empty($legacyProduct['weight'])) {
            $weight = $legacyProduct['weight'];
        }
        if ($imageUrl === '' && $legacyProduct && !empty($legacyProduct['image_url'])) {
            $imageUrl = $legacyProduct['image_url'];
        }

        if ($allowSheetFallback && ($title === '' || $imageUrl === '' || $weight === '') && $productId > 0) {
            $sheetProduct = getSheetProductById($productId);
            if ($sheetProduct) {
                if ($title === '') {
                    $title = getSheetProductDisplayTitle($sheetProduct);
                }
                if ($weight === '') {
                    $weight = getSheetProductDisplaySize($sheetProduct);
                }
                if ($imageUrl === '') {
                    $imageUrl = getSheetProductImage($sheetProduct);
                }
            }
        }

        if ($title !== '' && $weight !== '' && stripos($title, $weight) === false) {
            $title = trim($title . ' ' . $weight);
        }
        if ($title === '') {
            $title = 'Product #' . $productId;
        }
        if ($imageUrl === '') {
            $imageUrl = SIRFRANCIS_PRODUCT_PLACEHOLDER_IMAGE;
        }

        $price = array_key_exists('price', $item)
            ? (float) $item['price']
            : (array_key_exists('product_price', $item) ? (float) $item['product_price'] : null);
        if (($price === null || $price <= 0) && $legacyProduct && (float) ($legacyProduct['price'] ?? 0) > 0) {
            $price = (float) $legacyProduct['price'];
        }
        if ($price === null) {
            $price = 0;
        }

        $discount = array_key_exists('discount_amount', $item)
            ? (float) $item['discount_amount']
            : (array_key_exists('product_discount_amount', $item) ? (float) $item['product_discount_amount'] : null);
        if (($discount === null || $discount <= 0) && $legacyProduct) {
            $legacyDiscount = (float) ($legacyProduct['discount_amount'] ?? 0);
            if ($legacyDiscount <= 0 && $price > 0 && (float) ($legacyProduct['discount_rate'] ?? 0) > 0) {
                $legacyDiscount = round($price * min(100, (float) $legacyProduct['discount_rate']) / 100, 2);
            }
            if ($legacyDiscount > 0) {
                $discount = $legacyDiscount;
            }
        }
        if ($discount === null) {
            $discount = 0;
        }

        return [
            'product_id' => $productId,
            'title' => $title,
            'weight' => $weight,
            'image_url' => $imageUrl,
            'price' => $price,
            'discount_amount' => $discount,
            'legacy_order' => $isLegacyOrder,
            'source' => $legacyProduct ? 'legacy_product_table' : ($sheetProduct ? 'sheet' : 'order_item'),
        ];
    }
}

if (!function_exists('ensureCandybirdCartClearanceColumns')) {
    function ensureCandybirdCartClearanceColumns($conn) {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }
        if (!($conn instanceof mysqli)) {
            $checked = false;
            return false;
        }
        $columnCheck = $conn->query("SHOW COLUMNS FROM cart LIKE 'clearance_id'");
        if ($columnCheck && $columnCheck->num_rows > 0) {
            $checked = true;
            return true;
        }
        $checked = (bool) $conn->query("ALTER TABLE cart ADD COLUMN clearance_id VARCHAR(120) NULL AFTER product_id");
        if (!$checked) {
            error_log('Sir Francis could not add cart clearance_id column: ' . $conn->error);
        }
        return $checked;
    }
}

if (!function_exists('syncSheetProductMirrorToDb')) {
    function syncSheetProductMirrorToDb($conn, $product) {
        if (!($conn instanceof mysqli) || empty($product['id'])) {
            return false;
        }

        $productId = (int) $product['id'];
        if ($productId <= 0) {
            return false;
        }

        $title = trim((string) ($product['name'] ?? $product['title'] ?? 'Product ' . $productId));
        $price = isset($product['price']) ? candybirdParseSheetMoney($product['price']) : 0;
        $discountedPrice = getSheetProductPrice($product);
        $discountAmount = max(0, $price - $discountedPrice);
        $discountRate = $price > 0 && $discountAmount > 0 ? round(($discountAmount / $price) * 100, 2) : 0;
        $taxRate = isset($product['tax_rate']) ? (float) $product['tax_rate'] : 0;
        $taxAmount = isset($product['tax_amount']) ? (float) $product['tax_amount'] : 0;
        $description = (string) ($product['html_description'] ?? $product['description'] ?? '');
        $weight = (string) ($product['size'] ?? $product['weight'] ?? '');
        $dimensions = (string) ($product['dimensions'] ?? '');
        $otherInfo = trim(implode(' | ', array_filter([
            $product['parent_category'] ?? '',
            $product['child_category_1'] ?? '',
            $product['child_category_2'] ?? '',
            $product['additional_categories'] ?? ''
        ])));
        $features = (string) ($product['features'] ?? '');
        $label = (string) ($product['label'] ?? '');
        $enabled = 1;

        $sql = "INSERT INTO product
            (id, title, price, discount_rate, discount_amount, tax_rate, tax_amount, description, weight, dimensions, other_info, features, label, enabled)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                price = VALUES(price),
                discount_rate = VALUES(discount_rate),
                discount_amount = VALUES(discount_amount),
                tax_rate = VALUES(tax_rate),
                tax_amount = VALUES(tax_amount),
                description = VALUES(description),
                weight = VALUES(weight),
                dimensions = VALUES(dimensions),
                other_info = VALUES(other_info),
                features = VALUES(features),
                label = VALUES(label),
                enabled = VALUES(enabled)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isdddddssssssi",
            $productId,
            $title,
            $price,
            $discountRate,
            $discountAmount,
            $taxRate,
            $taxAmount,
            $description,
            $weight,
            $dimensions,
            $otherInfo,
            $features,
            $label,
            $enabled
        );

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            syncSheetProductImageMirrorToDb($conn, $productId, getSheetProductImage($product));
        }

        return $ok;
    }
}

if (!function_exists('syncSheetProductImageMirrorToDb')) {
    function syncSheetProductImageMirrorToDb($conn, $productId, $imageUrl) {
        if (!($conn instanceof mysqli) || empty($imageUrl)) {
            return false;
        }

        $productId = (int) $productId;
        $imageUrl = trim((string) $imageUrl);
        if ($productId <= 0 || $imageUrl === '') {
            return false;
        }

        $delete = mysqli_prepare($conn, "DELETE FROM images WHERE product_id = ?");
        if ($delete) {
            mysqli_stmt_bind_param($delete, "i", $productId);
            mysqli_stmt_execute($delete);
            mysqli_stmt_close($delete);
        }

        $insert = mysqli_prepare($conn, "INSERT INTO images (product_id, image_url) VALUES (?, ?)");
        if (!$insert) {
            return false;
        }

        mysqli_stmt_bind_param($insert, "is", $productId, $imageUrl);
        $ok = mysqli_stmt_execute($insert);
        mysqli_stmt_close($insert);
        return $ok;
    }
}

if (!function_exists('syncSheetProductsMirrorToDb')) {
    function syncSheetProductsMirrorToDb($conn, $disableMissing = true, $forceRefresh = true) {
        $health = function_exists('checkCandybirdSheetHealth') ? checkCandybirdSheetHealth('products') : [];
        $summary = [
            'success' => false,
            'sheet_rows' => (int) ($health['row_count'] ?? 0),
            'unique_sheet_ids' => (int) ($health['unique_id_count'] ?? 0),
            'duplicate_id_row_count' => (int) ($health['duplicate_id_row_count'] ?? 0),
            'duplicate_ids' => $health['duplicate_ids'] ?? [],
            'processed' => 0,
            'synced' => 0,
            'failed' => 0,
            'disabled_missing' => 0,
            'authoritative' => $disableMissing,
            'errors' => []
        ];

        if (!($conn instanceof mysqli)) {
            $summary['errors'][] = 'Database connection is not available.';
            return $summary;
        }

        $products = getSheetProducts($forceRefresh);
        $sheetIds = [];
        $minimumSafeDisableCount = 250;

        if (!empty($summary['duplicate_id_row_count'])) {
            $summary['errors'][] = 'The sheet has ' . $summary['duplicate_id_row_count'] . ' valid rows sharing duplicate product IDs. The database mirror can only keep one product per ID, so the last row for each duplicate ID wins.';
        }

        foreach ($products as $product) {
            $summary['processed']++;
            $productId = (int) ($product['id'] ?? 0);
            if ($productId > 0) {
                $sheetIds[] = $productId;
            }

            if (syncSheetProductMirrorToDb($conn, $product)) {
                $summary['synced']++;
            } else {
                $summary['failed']++;
                $summary['errors'][] = 'Could not sync product ID ' . ($product['id'] ?? 'unknown') . '.';
            }
        }

        if ($disableMissing && count(array_unique($sheetIds)) < $minimumSafeDisableCount) {
            $disableMissing = false;
            $summary['authoritative'] = false;
            $summary['errors'][] = 'Old products were not disabled because only ' . count(array_unique($sheetIds)) . ' unique sheet product IDs were read. This protects the database from a partial or malformed sheet fetch.';
        }

        if ($disableMissing && !empty($sheetIds)) {
            $idList = implode(',', array_map('intval', array_unique($sheetIds)));
            $sql = "UPDATE product SET enabled = 0 WHERE id NOT IN ($idList)";
            if (mysqli_query($conn, $sql)) {
                $summary['disabled_missing'] = mysqli_affected_rows($conn);
            }
        }

        $summary['success'] = $summary['failed'] === 0;
        return $summary;
    }
}

if (!function_exists('parseCandybirdWeightToKg')) {
    function parseCandybirdWeightToKg($value, $numberOnlyUnit = '') {
        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        $raw = str_replace(',', '.', $raw);
        if (preg_match('/(\d+(?:\.\d+)?)\s*(kg|kgs|kilogram|kilograms)\b/', $raw, $match)) {
            return max(0, (float) $match[1]);
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(g|gram|grams)\b/', $raw, $match)) {
            return max(0, ((float) $match[1]) / 1000);
        }

        if (preg_match('/\b(ml|millilitre|milliliter|millilitres|milliliters|l|lt|ltr|litre|liter|litres|liters|pc|pcs|piece|pieces|unit|units|box|boxes|bottle|bottles)\b/', $raw)) {
            return null;
        }

        if (is_numeric($raw)) {
            $number = (float) $raw;
            if ($number <= 0) {
                return null;
            }

            if ($numberOnlyUnit === 'g') {
                return $number / 1000;
            }

            if ($numberOnlyUnit === 'kg') {
                return $number;
            }

            return $number > 20 ? $number / 1000 : $number;
        }

        return null;
    }
}

if (!function_exists('getSheetProductWeightKg')) {
    function getSheetProductWeightKg($product) {
        if (isSheetProductDigital($product)) {
            return 0;
        }

        $shippingWeightFields = [
            ['shipping_weight_kg', 'kg'],
            ['weight_kg', 'kg'],
            ['shipping_weight_g', 'g'],
            ['weight_g', 'g'],
            ['shipping_weight', ''],
            ['actual_weight', ''],
            ['parcel_weight', ''],
            ['weight_for_shipping', ''],
        ];

        foreach ($shippingWeightFields as $field) {
            [$fieldName, $numberOnlyUnit] = $field;
            if (!array_key_exists($fieldName, $product)) {
                continue;
            }

            $shippingWeightKg = parseCandybirdWeightToKg($product[$fieldName], $numberOnlyUnit);
            if ($shippingWeightKg !== null) {
                return $shippingWeightKg;
            }
        }

        $sizeWeightKg = parseCandybirdWeightToKg($product['size'] ?? $product['weight'] ?? '');
        if ($sizeWeightKg !== null) {
            return $sizeWeightKg;
        }

        return getCandybirdDefaultUnitWeightKg();
    }
}

if (!function_exists('getCandybirdDefaultUnitWeightKg')) {
    function getCandybirdDefaultUnitWeightKg($configuredWeight = null) {
        static $cachedWeight = null;
        if ($configuredWeight !== null && is_numeric($configuredWeight)) {
            $weight = (float) $configuredWeight;
            if ($weight <= 0) {
                return 0.25;
            }

            return $weight > 20 ? $weight / 1000 : $weight;
        }
        if ($cachedWeight !== null) {
            return $cachedWeight;
        }

        $cachedWeight = 0.25;
        $settingsConn = $GLOBALS['conn'] ?? null;
        if ($settingsConn instanceof mysqli) {
            $columnCheck = $settingsConn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'default_unit_weight_kg'");
            if ($columnCheck && $columnCheck->num_rows === 0) {
                $settingsConn->query("ALTER TABLE admin_website_settings ADD COLUMN default_unit_weight_kg DECIMAL(10,3) NULL DEFAULT 0.25");
            }
            $result = $settingsConn->query("SELECT default_unit_weight_kg FROM admin_website_settings LIMIT 1");
            if ($result && $row = $result->fetch_assoc()) {
                $cachedWeight = getCandybirdDefaultUnitWeightKg($row['default_unit_weight_kg'] ?? null);
            }
        }

        return $cachedWeight;
    }
}

if (!function_exists('formatCandybirdWeightKg')) {
    function formatCandybirdWeightKg($weightKg) {
        $weightKg = (float) $weightKg;
        if ($weightKg <= 0) {
            return '0 kg';
        }

        if ($weightKg < 1) {
            return number_format($weightKg * 1000, 0) . ' g';
        }

        return rtrim(rtrim(number_format($weightKg, 2), '0'), '.') . ' kg';
    }
}

if (!function_exists('getCandybirdCategoryDisplayOrder')) {
    function getCandybirdCategoryDisplayConfig() {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $config = ['items' => []];
        if (function_exists('mysqli_connect')) {
            $configPath = __DIR__ . '/dbh.inc.php';
            $settingsConn = null;
            if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli && !$GLOBALS['conn']->connect_error) {
                $settingsConn = $GLOBALS['conn'];
            } elseif (is_file($configPath)) {
                include $configPath;
                if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                    $settingsConn = $conn;
                }
            }
            if ($settingsConn instanceof mysqli) {
                $columnCheck = $settingsConn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'category_display_config'");
                if ($columnCheck && $columnCheck->num_rows === 0) {
                    $settingsConn->query("ALTER TABLE admin_website_settings ADD COLUMN category_display_config LONGTEXT NULL");
                }
                $result = $settingsConn->query("SELECT category_display_config FROM admin_website_settings ORDER BY id ASC");
                while ($result && ($row = $result->fetch_assoc())) {
                    $decoded = json_decode((string) ($row['category_display_config'] ?? ''), true);
                    if (is_array($decoded) && !empty($decoded['items']) && is_array($decoded['items'])) {
                        $config = $decoded;
                        break;
                    }
                }
            }
        }

        return $config;
    }

    function getCandybirdCategoryDisplayMap() {
        $map = [];
        foreach (getCandybirdCategoryDisplayConfig()['items'] ?? [] as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $map[$name] = [
                'label' => trim((string) ($item['label'] ?? $name)),
                'visible' => !array_key_exists('visible', $item) || filter_var($item['visible'], FILTER_VALIDATE_BOOLEAN),
                'position' => isset($item['position']) ? (int) $item['position'] : 9999,
            ];
        }
        uasort($map, static function($a, $b) {
            $posCompare = ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999);
            if ($posCompare !== 0) {
                return $posCompare;
            }
            return strnatcasecmp($a['label'] ?? '', $b['label'] ?? '');
        });
        return $map;
    }

    function getCandybirdCategoryPathDisplayMap() {
        $map = [];
        foreach (getCandybirdCategoryDisplayConfig()['paths'] ?? [] as $item) {
            $path = trim((string) ($item['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $map[$path] = [
                'label' => trim((string) ($item['label'] ?? $path)) ?: $path,
                'visible' => !array_key_exists('visible', $item) || filter_var($item['visible'], FILTER_VALIDATE_BOOLEAN),
                'position' => isset($item['position']) ? (int) $item['position'] : 9999,
            ];
        }
        uasort($map, static function($a, $b) {
            $posCompare = ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999);
            if ($posCompare !== 0) {
                return $posCompare;
            }
            return strnatcasecmp($a['label'] ?? '', $b['label'] ?? '');
        });
        return $map;
    }

    function getCandybirdCategoryPathDisplayPosition($categoryPath) {
        $categoryPath = trim((string) $categoryPath);
        $categorySlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($categoryPath) : strtolower($categoryPath);
        foreach (getCandybirdCategoryPathDisplayMap() as $sourcePath => $item) {
            $sourceSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($sourcePath) : strtolower($sourcePath);
            $labelSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($item['label'] ?? $sourcePath) : strtolower((string) ($item['label'] ?? $sourcePath));
            if ($categoryPath === $sourcePath || $categorySlug === $sourceSlug || $categorySlug === $labelSlug) {
                return (int) ($item['position'] ?? 9999);
            }
        }
        return PHP_INT_MAX;
    }

    function getCandybirdCategoryPathDisplayLabel($categoryPath) {
        $categoryPath = trim((string) $categoryPath);
        $map = getCandybirdCategoryPathDisplayMap();
        if (isset($map[$categoryPath])) {
            return $map[$categoryPath]['label'] ?? $categoryPath;
        }
        $categorySlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($categoryPath) : strtolower($categoryPath);
        foreach ($map as $sourcePath => $item) {
            $sourceSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($sourcePath) : strtolower($sourcePath);
            if ($categorySlug === $sourceSlug) {
                return $item['label'] ?? $categoryPath;
            }
        }
        return $categoryPath;
    }

    function isCandybirdCategoryPathVisible($categoryPath) {
        $categoryPath = trim((string) $categoryPath);
        $map = getCandybirdCategoryPathDisplayMap();
        if (isset($map[$categoryPath])) {
            return !empty($map[$categoryPath]['visible']);
        }
        $categorySlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($categoryPath) : strtolower($categoryPath);
        foreach ($map as $sourcePath => $item) {
            $sourceSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($sourcePath) : strtolower($sourcePath);
            $labelSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($item['label'] ?? $sourcePath) : strtolower((string) ($item['label'] ?? $sourcePath));
            if ($categorySlug === $sourceSlug || $categorySlug === $labelSlug) {
                return !empty($item['visible']);
            }
        }
        return true;
    }

    function getCandybirdCategoryDisplayPosition($categoryName) {
        $categoryName = trim((string) $categoryName);
        $categorySlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($categoryName) : strtolower($categoryName);
        foreach (getCandybirdCategoryDisplayMap() as $sourceName => $item) {
            $sourceSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($sourceName) : strtolower($sourceName);
            $labelSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($item['label'] ?? $sourceName) : strtolower((string) ($item['label'] ?? $sourceName));
            if ($categoryName === $sourceName || $categorySlug === $sourceSlug || $categorySlug === $labelSlug) {
                return (int) ($item['position'] ?? 9999);
            }
        }
        return PHP_INT_MAX;
    }

    function getCandybirdCategoryDisplayLabel($categoryName) {
        $categoryName = trim((string) $categoryName);
        $map = getCandybirdCategoryDisplayMap();
        if (isset($map[$categoryName])) {
            return $map[$categoryName]['label'] ?? $categoryName;
        }
        $categorySlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($categoryName) : strtolower($categoryName);
        foreach ($map as $sourceName => $item) {
            $sourceSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($sourceName) : strtolower($sourceName);
            if ($categorySlug === $sourceSlug) {
                return $item['label'] ?? $categoryName;
            }
        }
        return $categoryName;
    }

    function isCandybirdCategoryVisible($categoryName) {
        $categoryName = trim((string) $categoryName);
        $map = getCandybirdCategoryDisplayMap();
        if (isset($map[$categoryName])) {
            return !empty($map[$categoryName]['visible']);
        }
        $categorySlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($categoryName) : strtolower($categoryName);
        foreach ($map as $sourceName => $item) {
            $sourceSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($sourceName) : strtolower($sourceName);
            $labelSlug = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($item['label'] ?? $sourceName) : strtolower((string) ($item['label'] ?? $sourceName));
            if ($categorySlug === $sourceSlug || $categorySlug === $labelSlug) {
                return !empty($item['visible']);
            }
        }
        return true;
    }

    function getCandybirdCategoryDisplayOrder() {
        static $order = null;
        if ($order !== null) {
            return $order;
        }

        $displayMap = getCandybirdCategoryDisplayMap();
        if (!empty($displayMap)) {
            $order = array_keys(array_filter($displayMap, static function($item) {
                return !empty($item['visible']);
            }));
            return $order;
        }

        $order = [
            'Marine Collagen', 'Fish Gelatine', 'Hydrolysed Collagen',
            'Sea Moss', 'Retail Packs', 'Bulk Supply',
            'Private Labelling', 'For Resellers', 'Specials', 'Clearance Basket'
        ];

        if (function_exists('mysqli_connect')) {
            $configPath = __DIR__ . '/dbh.inc.php';
            $settingsConn = null;
            if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli && !$GLOBALS['conn']->connect_error) {
                $settingsConn = $GLOBALS['conn'];
            } elseif (is_file($configPath)) {
                include $configPath;
                if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                    $settingsConn = $conn;
                }
            }
            if ($settingsConn instanceof mysqli) {
                $columnCheck = $settingsConn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'category_display_order'");
                if ($columnCheck && $columnCheck->num_rows === 0) {
                    $settingsConn->query("ALTER TABLE admin_website_settings ADD COLUMN category_display_order TEXT NULL");
                }
                $result = $settingsConn->query("SELECT category_display_order FROM admin_website_settings ORDER BY id ASC");
                while ($result && ($row = $result->fetch_assoc())) {
                    $saved = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) ($row['category_display_order'] ?? '')))));
                    if (!empty($saved)) {
                        $order = $saved;
                        break;
                    }
                }
            }
        }

        return $order;
    }
}

if (!defined('SIRFRANCIS_FREE_SHIPPING_AMOUNT')) {
    define('SIRFRANCIS_FREE_SHIPPING_AMOUNT', 500);
}

if (!function_exists('getCandybirdFreeShippingAmount')) {
    function getCandybirdFreeShippingAmount($configuredAmount = null) {
        $amount = is_numeric($configuredAmount) ? (float) $configuredAmount : 0;
        return $amount > 0 ? $amount : (float) SIRFRANCIS_FREE_SHIPPING_AMOUNT;
    }
}

if (!function_exists('getCandybirdDefaultDeliveryOptions')) {
    function getCandybirdDefaultDeliveryOptions() {
        return [
            'locker' => [
                'label' => 'Pudo locker',
                'enabled' => true,
                'estimate' => '2-5 working days after dispatch',
                'free_shipping_eligible' => true,
                'tiers' => [
                    'locker_2kg' => ['label' => 'Up to 2kg', 'max_kg' => 2, 'price' => 50],
                    'locker_5kg' => ['label' => 'Up to 5kg', 'max_kg' => 5, 'price' => 80],
                    'locker_20kg' => ['label' => 'Up to 20kg', 'max_kg' => 20, 'price' => 180],
                    'locker_over_20kg' => ['label' => 'Over 20kg flat rate', 'max_kg' => null, 'price' => 350],
                ],
            ],
            'door' => [
                'label' => 'Door-to-door',
                'enabled' => true,
                'estimate' => '2-5 working days after dispatch',
                'free_shipping_eligible' => false,
                'tiers' => [
                    'door_2kg' => ['label' => 'Up to 2kg', 'max_kg' => 2, 'price' => 89],
                    'door_5kg' => ['label' => 'Up to 5kg', 'max_kg' => 5, 'price' => 130],
                    'door_20kg' => ['label' => 'Up to 20kg', 'max_kg' => 20, 'price' => 250],
                    'door_over_20kg' => ['label' => 'Over 20kg flat rate', 'max_kg' => null, 'price' => 350],
                ],
            ],
            'collect' => [
                'label' => 'Collect from Sir Francis',
                'enabled' => true,
                'estimate' => 'Usually ready within 1-2 working days once confirmed',
                'free_shipping_eligible' => false,
                'collection_address' => '18 Babiana Rd, Malabar, Port Elizabeth',
                'tiers' => [
                    'collect' => ['label' => 'Collection', 'max_kg' => null, 'price' => 0],
                ],
            ],
        ];
    }
}

if (!function_exists('normalizeCandybirdDeliveryOptions')) {
    function normalizeCandybirdDeliveryOptions($savedOptions) {
        $options = getCandybirdDefaultDeliveryOptions();
        if (!is_array($savedOptions)) {
            return $options;
        }

        foreach ($options as $methodKey => $method) {
            if (isset($savedOptions[$methodKey]['enabled'])) {
                $options[$methodKey]['enabled'] = filter_var($savedOptions[$methodKey]['enabled'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($savedOptions[$methodKey]['estimate'])) {
                $options[$methodKey]['estimate'] = trim((string) $savedOptions[$methodKey]['estimate']);
            }
            if (array_key_exists('free_shipping_eligible', $savedOptions[$methodKey] ?? [])) {
                $options[$methodKey]['free_shipping_eligible'] = filter_var($savedOptions[$methodKey]['free_shipping_eligible'], FILTER_VALIDATE_BOOLEAN);
            }
            if ($methodKey === 'collect' && isset($savedOptions[$methodKey]['collection_address'])) {
                $options[$methodKey]['collection_address'] = trim((string) $savedOptions[$methodKey]['collection_address']);
            }
            foreach ($method['tiers'] as $tierKey => $tier) {
                if (isset($savedOptions[$methodKey]['tiers'][$tierKey]['price']) && is_numeric($savedOptions[$methodKey]['tiers'][$tierKey]['price'])) {
                    $options[$methodKey]['tiers'][$tierKey]['price'] = (float) $savedOptions[$methodKey]['tiers'][$tierKey]['price'];
                }
            }
            if (!empty($savedOptions[$methodKey]['tiers']) && is_array($savedOptions[$methodKey]['tiers'])) {
                foreach ($savedOptions[$methodKey]['tiers'] as $tierKey => $tier) {
                    if (isset($options[$methodKey]['tiers'][$tierKey])) {
                        continue;
                    }
                    $maxKg = $tier['max_kg'] ?? null;
                    if ($maxKg !== null && is_numeric($maxKg) && (float) $maxKg > 20 && isset($tier['price']) && is_numeric($tier['price'])) {
                        $options[$methodKey]['tiers'][$tierKey] = [
                            'label' => trim((string) ($tier['label'] ?? ('Up to ' . (float) $maxKg . 'kg'))),
                            'max_kg' => (float) $maxKg,
                            'price' => (float) $tier['price'],
                        ];
                    }
                }
            }
            uasort($options[$methodKey]['tiers'], function ($a, $b) {
                $aMax = $a['max_kg'] ?? null;
                $bMax = $b['max_kg'] ?? null;
                if ($aMax === null && $bMax === null) {
                    return 0;
                }
                if ($aMax === null) {
                    return 1;
                }
                if ($bMax === null) {
                    return -1;
                }
                return $aMax <=> $bMax;
            });
        }

        $hasEnabled = false;
        foreach ($options as $option) {
            if (!empty($option['enabled'])) {
                $hasEnabled = true;
                break;
            }
        }
        if (!$hasEnabled) {
            $options['locker']['enabled'] = true;
        }

        return $options;
    }
}

if (!function_exists('getCandybirdDeliveryOptions')) {
    function getCandybirdDeliveryOptions() {
        static $cachedOptions = null;
        if ($cachedOptions !== null) {
            return $cachedOptions;
        }

        global $conn;

        $savedOptions = null;
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $columnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'shipping_rates_json'");
            if ($columnCheck && $columnCheck->num_rows > 0) {
                $result = $conn->query("SELECT shipping_rates_json FROM admin_website_settings LIMIT 1");
                if ($result && ($row = $result->fetch_assoc())) {
                    $decoded = json_decode((string) ($row['shipping_rates_json'] ?? ''), true);
                    if (is_array($decoded)) {
                        $savedOptions = $decoded;
                    }
                }
            }
        }

        $cachedOptions = normalizeCandybirdDeliveryOptions($savedOptions);
        return $cachedOptions;
    }
}

if (!function_exists('getCandybirdEnabledDeliveryOptions')) {
    function getCandybirdEnabledDeliveryOptions($options = null) {
        $options = is_array($options) ? $options : getCandybirdDeliveryOptions();
        return array_filter($options, static function ($option) {
            return !empty($option['enabled']);
        });
    }
}

if (!function_exists('getCandybirdDefaultDeliveryMethod')) {
    function getCandybirdDefaultDeliveryMethod($options = null) {
        $options = is_array($options) ? $options : getCandybirdDeliveryOptions();
        foreach (['locker', 'door', 'collect'] as $preferred) {
            if (!empty($options[$preferred]['enabled'])) {
                return $preferred;
            }
        }
        foreach ($options as $methodKey => $option) {
            if (!empty($option['enabled'])) {
                return $methodKey;
            }
        }
        return 'locker';
    }
}

if (!function_exists('getCandybirdDeliveryQuote')) {
    function getCandybirdDeliveryQuote($method, $cartWeightKg, $orderTotal, $freeShippingAmount) {
        if ((float) $cartWeightKg <= 0) {
            return [
                'method' => 'digital',
                'method_label' => 'Digital delivery',
                'tier_key' => 'digital',
                'tier_label' => 'No courier required',
                'max_kg' => 0,
                'shipping_amount' => 0,
                'shipping_discount_amount' => 0,
                'payable_shipping_amount' => 0,
            ];
        }

        $options = getCandybirdDeliveryOptions();
        $method = isset($options[$method]) && !empty($options[$method]['enabled']) ? $method : getCandybirdDefaultDeliveryMethod($options);
        $selectedOption = $options[$method];
        $selectedTierKey = null;
        $selectedTier = null;

        foreach ($selectedOption['tiers'] as $tierKey => $tier) {
            if ($tier['max_kg'] === null || $cartWeightKg <= $tier['max_kg']) {
                $selectedTierKey = $tierKey;
                $selectedTier = $tier;
                break;
            }
        }

        if (!$selectedTier) {
            $tierKeys = array_keys($selectedOption['tiers']);
            $selectedTierKey = end($tierKeys);
            $selectedTier = $selectedOption['tiers'][$selectedTierKey];
        }

        $shippingAmount = (float) $selectedTier['price'];
        $shippingDiscountAmount = 0;

        $tierMaxKg = $selectedTier['max_kg'] ?? null;
        if (!empty($selectedOption['free_shipping_eligible']) && $orderTotal >= $freeShippingAmount && $tierMaxKg !== null && (float) $tierMaxKg <= 20) {
            $shippingDiscountAmount = $shippingAmount;
        }

        return [
            'method' => $method,
            'method_label' => $selectedOption['label'],
            'estimate' => $selectedOption['estimate'] ?? '',
            'collection_address' => $selectedOption['collection_address'] ?? '',
            'tier_key' => $selectedTierKey,
            'tier_label' => $selectedTier['label'],
            'max_kg' => $selectedTier['max_kg'],
            'shipping_amount' => $shippingAmount,
            'shipping_discount_amount' => $shippingDiscountAmount,
            'payable_shipping_amount' => max(0, $shippingAmount - $shippingDiscountAmount),
        ];
    }
}
