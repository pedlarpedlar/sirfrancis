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
        $normalPrice = candybirdParseSheetMoney($product['price'] ?? 0);
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
    function cbPricelistProductsByCategory($sort = 'name', $direction = 'asc') {
        $sort = in_array($sort, ['id', 'name', 'size', 'price'], true) ? $sort : 'name';
        $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';
        $categoryOrder = function_exists('getCandybirdCategoryDisplayOrder') ? getCandybirdCategoryDisplayOrder() : [];
        $customCategoryOrder = [];
        foreach ($categoryOrder as $index => $categoryName) {
            $customCategoryOrder[$categoryName] = $index;
        }
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
            usort($categoryProducts, function($a, $b) use ($sort, $direction) {
                switch ($sort) {
                    case 'id':
                        $compare = strnatcasecmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
                        break;
                    case 'price':
                        $compare = cbPricelistPricing($a)['sale_price'] <=> cbPricelistPricing($b)['sale_price'];
                        break;
                    case 'size':
                        $compare = cbPricelistSortValue($a['size'] ?? '') <=> cbPricelistSortValue($b['size'] ?? '');
                        if ($compare === 0) {
                            $compare = strnatcasecmp((string) ($a['size'] ?? ''), (string) ($b['size'] ?? ''));
                        }
                        break;
                    case 'name':
                    default:
                        $compare = strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
                        break;
                }

                if ($compare === 0 && $sort !== 'name') {
                    $compare = strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
                }

                return $direction === 'desc' ? -$compare : $compare;
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
