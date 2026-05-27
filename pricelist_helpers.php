<?php
require_once __DIR__ . '/product_sheet_helpers.php';

if (!function_exists('cbPricelistCategoryName')) {
    function cbPricelistCategoryName($product) {
        $parent = trim((string) ($product['parent_category'] ?? ''));
        return $parent !== '' ? $parent : 'Other Products';
    }
}

if (!function_exists('cbPricelistSortValue')) {
    function cbPricelistSortValue($value) {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(kg|g|ml|l)/i', (string) $value, $match)) {
            $number = (float) $match[1];
            $unit = strtolower($match[2]);
            return ($unit === 'kg' || $unit === 'l') ? $number * 1000 : $number;
        }

        return PHP_INT_MAX;
    }
}

if (!function_exists('cbPricelistText')) {
    function cbPricelistText($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cbPricelistSpecialUntil')) {
    function cbPricelistSpecialUntil($product) {
        $untilRaw = trim((string) ($product['discount_valid_until'] ?? $product['special_valid_until'] ?? $product['sale_valid_until'] ?? ''));
        if ($untilRaw === '') {
            return '';
        }

        $until = parseCandybirdProductSpecialDate($untilRaw, true);
        if (!$until instanceof DateTime) {
            return $untilRaw;
        }

        return $until->format('d M Y');
    }
}

if (!function_exists('cbPricelistPricing')) {
    function cbPricelistPricing($product) {
        $product = normalizeCandybirdProductSpecial($product);
        $normalPrice = isset($product['price']) ? (float) $product['price'] : 0;
        $salePrice = getSheetProductPrice($product);
        $saving = max(0, $normalPrice - $salePrice);
        $isSpecial = $normalPrice > 0 && $saving > 0.009;

        return [
            'normal_price' => $normalPrice,
            'sale_price' => $salePrice,
            'saving_percent' => $isSpecial ? round(($saving / $normalPrice) * 100) : 0,
            'is_special' => $isSpecial,
            'valid_until' => $isSpecial ? cbPricelistSpecialUntil($product) : '',
        ];
    }
}

if (!function_exists('cbPricelistProductsByCategory')) {
    function cbPricelistProductsByCategory() {
        $customCategoryOrder = [
            'Gifting' => 0,
            'Travel Treats' => 1,
            'Nuts' => 2,
            'Peanuts' => 3,
            'Dried Fruit' => 4,
            'Sweets' => 5,
            'Ingredients' => 6,
            'Resellers & Wholesale' => 100,
            'For Resellers' => 100,
            'Affiliate Products' => 101,
            'Other Products' => 999,
        ];

        $products = array_values(getSheetProducts());
        $products = array_filter($products, function($product) {
            $id = trim((string) ($product['id'] ?? ''));
            $name = trim((string) ($product['name'] ?? ''));
            $enabled = strtolower(trim((string) ($product['enabled'] ?? '1')));
            return $id !== '' && $name !== '' && !in_array($enabled, ['0', 'false', 'no', 'disabled'], true);
        });

        $productsByCategory = [];
        foreach ($products as $product) {
            $productsByCategory[cbPricelistCategoryName($product)][] = $product;
        }

        uksort($productsByCategory, function($a, $b) use ($customCategoryOrder) {
            $posA = $customCategoryOrder[$a] ?? PHP_INT_MAX;
            $posB = $customCategoryOrder[$b] ?? PHP_INT_MAX;
            return $posA === $posB ? strnatcasecmp($a, $b) : $posA <=> $posB;
        });

        foreach ($productsByCategory as &$categoryProducts) {
            usort($categoryProducts, function($a, $b) {
                $nameCompare = strnatcasecmp($a['name'] ?? '', $b['name'] ?? '');
                if ($nameCompare !== 0) {
                    return $nameCompare;
                }

                $sizeCompare = cbPricelistSortValue($a['size'] ?? '') <=> cbPricelistSortValue($b['size'] ?? '');
                return $sizeCompare !== 0 ? $sizeCompare : strnatcasecmp($a['size'] ?? '', $b['size'] ?? '');
            });
        }
        unset($categoryProducts);

        return $productsByCategory;
    }
}

if (!function_exists('cbPricelistProductCount')) {
    function cbPricelistProductCount($productsByCategory) {
        $count = 0;
        foreach ($productsByCategory as $products) {
            $count += count($products);
        }
        return $count;
    }
}
?>
