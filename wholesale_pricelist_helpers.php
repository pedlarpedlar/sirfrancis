<?php
date_default_timezone_set('Africa/Johannesburg');
require_once __DIR__ . '/product_sheet_helpers.php';

if (!function_exists('cbWholesaleText')) {
    function cbWholesaleText($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cbWholesaleFirstValue')) {
    function cbWholesaleFirstValue($row, $keys, $default = '') {
        foreach ((array) $keys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }
        return $default;
    }
}

if (!function_exists('cbWholesaleMoney')) {
    function cbWholesaleMoney($value) {
        return candybirdParseSheetMoney($value);
    }
}

if (!function_exists('cbWholesaleFormatMoney')) {
    function cbWholesaleFormatMoney($value) {
        $amount = cbWholesaleMoney($value);
        return $amount > 0 ? 'R' . number_format($amount, 2) : '';
    }
}

if (!function_exists('cbWholesaleSortValue')) {
    function cbWholesaleSortValue($value) {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(kg|g|ml|l|lt)/i', (string) $value, $match)) {
            $number = (float) $match[1];
            $unit = strtolower($match[2]);
            return in_array($unit, ['kg', 'l', 'lt'], true) ? $number * 1000 : $number;
        }
        return PHP_INT_MAX;
    }
}

if (!function_exists('cbWholesaleAllowedPackSizes')) {
    function cbWholesaleAllowedPackSizes($value = '') {
        $value = trim((string) $value);
        $sizes = $value !== ''
            ? preg_split('/[,;|]+/', $value)
            : ['1kg', '500g', '340g', '100g', '29g'];

        $clean = [];
        foreach ($sizes as $size) {
            $size = trim((string) $size);
            if ($size !== '') {
                $clean[] = $size;
            }
        }
        return array_values(array_unique($clean));
    }
}

if (!function_exists('cbWholesaleSizeToKg')) {
    function cbWholesaleSizeToKg($value) {
        $value = strtolower(trim((string) $value));
        if (!preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|g)/', $value, $match)) {
            return 0.0;
        }
        $number = (float) str_replace(',', '.', $match[1]);
        return $match[2] === 'kg' ? $number : ($number / 1000);
    }
}

if (!function_exists('cbWholesaleProductTitle')) {
    function cbWholesaleProductTitle($row, $product) {
        $title = cbWholesaleFirstValue($row, ['title', 'product_title', 'reference_title', 'name']);
        if ($title !== '') {
            return $title;
        }
        if (is_array($product)) {
            return getSheetProductDisplayTitle($product);
        }
        return 'Product ' . cbWholesaleFirstValue($row, ['product_id', 'id']);
    }
}

if (!function_exists('cbWholesaleProductCategory')) {
    function cbWholesaleProductCategory($product) {
        if (!is_array($product)) {
            return 'Wholesale';
        }
        $category = trim((string) ($product['parent_category'] ?? ''));
        return $category !== '' ? $category : 'Wholesale';
    }
}

if (!function_exists('cbWholesaleRowEnabled')) {
    function cbWholesaleRowEnabled($row) {
        $enabled = strtolower(trim((string) ($row['enabled'] ?? $row['available'] ?? $row['active'] ?? 'yes')));
        return !in_array($enabled, ['0', 'no', 'false', 'disabled', 'hidden'], true);
    }
}

if (!function_exists('getCandybirdWholesaleRows')) {
    function getCandybirdWholesaleRows($forceRefresh = false) {
        static $rows = null;
        if (!$forceRefresh && $rows !== null) {
            return $rows;
        }

        $rows = [];
        $sheetUrl = getCandybirdSheetUrl('wholesale');
        if (trim((string) $sheetUrl) === '') {
            return $rows;
        }

        foreach (fetchCandybirdTsvSheet($sheetUrl, 'wholesale', 1800, $forceRefresh) as $row) {
            if (!cbWholesaleRowEnabled($row)) {
                continue;
            }

            $productId = cbWholesaleFirstValue($row, ['product_id', 'id', 'source_product_id']);
            $size = cbWholesaleFirstValue($row, ['size', 'case_size', 'bulk_size', 'pack_size']);
            $priceRaw = cbWholesaleFirstValue($row, ['price', 'wholesale_price', 'bulk_price', 'price_per_kg']);
            $price = cbWholesaleMoney($priceRaw);

            if ($productId === '' || $size === '' || $price <= 0) {
                continue;
            }

            $product = getSheetProductById($productId);
            $pricePerKg = cbWholesaleFirstValue($row, ['price_per_kg', 'per_kg_price', 'kg_price']);
            $retailPriceKg = cbWholesaleFirstValue($row, ['retail_price_kg', 'retail_kg_price', 'retail_per_kg']);
            $packDownFee = cbWholesaleFirstValue($row, ['pack_down_fee', 'packdown_fee', 'pack_down_price', 'packing_fee']);
            $packDownNote = cbWholesaleFirstValue($row, ['pack_down_note', 'packdown_note', 'packing_note']);
            $allowedPackSizes = cbWholesaleAllowedPackSizes(cbWholesaleFirstValue($row, ['allowed_pack_sizes', 'pack_sizes', 'allowed_sizes']));
            $description = cbWholesaleFirstValue($row, ['description', 'notes', 'bulk_description', 'boxing', 'box_description']);

            $rows[] = [
                'product_id' => $productId,
                'title' => cbWholesaleProductTitle($row, $product),
                'category' => cbWholesaleProductCategory($product),
                'size' => $size,
                'price' => $price,
                'price_label' => cbWholesaleFirstValue($row, ['price_label'], ''),
                'price_per_kg' => $pricePerKg !== '' ? cbWholesaleMoney($pricePerKg) : 0,
                'retail_price_kg' => $retailPriceKg !== '' ? cbWholesaleMoney($retailPriceKg) : 0,
                'pack_down_fee' => $packDownFee !== '' ? cbWholesaleMoney($packDownFee) : 0,
                'pack_down_note' => $packDownNote,
                'allowed_pack_sizes' => $allowedPackSizes,
                'moq' => cbWholesaleFirstValue($row, ['moq', 'minimum_order', 'minimum_qty']),
                'lead_time' => cbWholesaleFirstValue($row, ['lead_time', 'availability']),
                'free_delivery_excluded' => isCandybirdFreeDeliveryExcluded($row) ? 'yes' : 'no',
                'description' => $description,
                'image' => is_array($product) ? getSheetProductImage($product) : '',
                'product_url' => is_array($product) ? getSheetProductUrl($product) : '',
            ];
        }

        usort($rows, static function($a, $b) {
            $catCompare = strnatcasecmp((string) $a['category'], (string) $b['category']);
            if ($catCompare !== 0) {
                return $catCompare;
            }
            $titleCompare = strnatcasecmp((string) $a['title'], (string) $b['title']);
            if ($titleCompare !== 0) {
                return $titleCompare;
            }
            return cbWholesaleSortValue($a['size'] ?? '') <=> cbWholesaleSortValue($b['size'] ?? '');
        });

        return $rows;
    }
}

if (!function_exists('getCandybirdWholesaleRowsByCategory')) {
    function getCandybirdWholesaleRowsByCategory($forceRefresh = false) {
        $grouped = [];
        foreach (getCandybirdWholesaleRows($forceRefresh) as $row) {
            $grouped[$row['category']][] = $row;
        }
        uksort($grouped, static function($a, $b) {
            $posA = function_exists('getCandybirdCategoryDisplayPosition') ? getCandybirdCategoryDisplayPosition($a) : PHP_INT_MAX;
            $posB = function_exists('getCandybirdCategoryDisplayPosition') ? getCandybirdCategoryDisplayPosition($b) : PHP_INT_MAX;
            return $posA === $posB ? strnatcasecmp($a, $b) : $posA <=> $posB;
        });
        return $grouped;
    }
}

if (!function_exists('getCandybirdWholesaleProductIds')) {
    function getCandybirdWholesaleProductIds($forceRefresh = false) {
        $ids = [];
        foreach (getCandybirdWholesaleRows($forceRefresh) as $row) {
            $ids[(string) $row['product_id']] = true;
        }
        return array_keys($ids);
    }
}

if (!function_exists('hasCandybirdWholesaleOption')) {
    function hasCandybirdWholesaleOption($productId) {
        $productId = trim((string) $productId);
        if ($productId === '') {
            return false;
        }
        return in_array($productId, getCandybirdWholesaleProductIds(), true);
    }
}

if (!function_exists('cbWholesaleDisplayPrice')) {
    function cbWholesaleDisplayPrice($row) {
        $parts = [];
        $priceLabel = trim((string) ($row['price_label'] ?? ''));
        if ($priceLabel !== '') {
            $parts[] = $priceLabel;
        } else {
            $parts[] = cbWholesaleFormatMoney($row['price']);
        }
        if ((float) ($row['price_per_kg'] ?? 0) > 0) {
            $parts[] = cbWholesaleFormatMoney($row['price_per_kg']) . ' per kg';
        }
        if ((float) ($row['pack_down_fee'] ?? 0) > 0) {
            $parts[] = 'Pack-down fee ' . cbWholesaleFormatMoney($row['pack_down_fee']) . ' per requested pack/unit';
        }
        return implode(' | ', array_filter($parts));
    }
}

if (!function_exists('cbWholesaleRetailComparison')) {
    function cbWholesaleRetailComparison($row) {
        $retail = (float) ($row['retail_price_kg'] ?? 0);
        $wholesale = (float) ($row['price_per_kg'] ?? 0);
        if ($retail <= 0) {
            return '';
        }

        $text = 'Retail ref: ' . cbWholesaleFormatMoney($retail) . ' per kg';
        if ($wholesale > 0 && $retail > $wholesale) {
            $saving = round((($retail - $wholesale) / $retail) * 100);
            if ($saving > 0) {
                $text .= ' | Wholesale saves about ' . $saving . '%';
            }
        }
        return $text;
    }
}
?>
