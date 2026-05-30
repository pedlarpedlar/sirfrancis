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

if (!function_exists('cbPricelistProductGroupTitle')) {
    function cbPricelistProductGroupTitle($product) {
        $name = trim((string) ($product['name'] ?? $product['title'] ?? ''));
        return $name !== '' ? $name : 'Unnamed Product';
    }
}

if (!function_exists('cbPricelistProductGroups')) {
    function cbPricelistProductGroups($products, $sort = 'name', $direction = 'asc') {
        $sort = in_array($sort, ['id', 'name', 'size', 'price'], true) ? $sort : 'name';
        $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';
        $groups = [];

        foreach ($products as $product) {
            $title = cbPricelistProductGroupTitle($product);
            $key = strtolower(trim(preg_replace('/\s+/', ' ', $title)));
            if ($key === '') {
                $key = 'product-' . (string) ($product['id'] ?? count($groups));
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'id' => 'plg-' . substr(md5($key), 0, 10),
                    'title' => $title,
                    'products' => [],
                    'min_price' => null,
                    'max_price' => null,
                    'first_id' => (string) ($product['id'] ?? ''),
                    'min_size_sort' => cbPricelistSortValue($product['size'] ?? ''),
                ];
            }

            $pricing = cbPricelistPricing($product);
            $price = (float) $pricing['sale_price'];
            $groups[$key]['products'][] = $product;
            $groups[$key]['min_price'] = $groups[$key]['min_price'] === null ? $price : min($groups[$key]['min_price'], $price);
            $groups[$key]['max_price'] = $groups[$key]['max_price'] === null ? $price : max($groups[$key]['max_price'], $price);
            $groups[$key]['first_id'] = strnatcasecmp((string) ($product['id'] ?? ''), (string) $groups[$key]['first_id']) < 0 ? (string) ($product['id'] ?? '') : $groups[$key]['first_id'];
            $groups[$key]['min_size_sort'] = min((float) $groups[$key]['min_size_sort'], (float) cbPricelistSortValue($product['size'] ?? ''));
        }

        $groups = array_values($groups);
        usort($groups, static function($a, $b) use ($sort, $direction) {
            switch ($sort) {
                case 'id':
                    $compare = strnatcasecmp((string) ($a['first_id'] ?? ''), (string) ($b['first_id'] ?? ''));
                    break;
                case 'price':
                    $compare = ((float) ($a['min_price'] ?? 0)) <=> ((float) ($b['min_price'] ?? 0));
                    break;
                case 'size':
                    $compare = ((float) ($a['min_size_sort'] ?? PHP_INT_MAX)) <=> ((float) ($b['min_size_sort'] ?? PHP_INT_MAX));
                    break;
                case 'name':
                default:
                    $compare = strnatcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
                    break;
            }
            if ($compare === 0) {
                $compare = strnatcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
            }
            return $direction === 'desc' ? -$compare : $compare;
        });

        return $groups;
    }
}

if (!function_exists('cbPricelistPriceRange')) {
    function cbPricelistPriceRange($group) {
        $min = (float) ($group['min_price'] ?? 0);
        $max = (float) ($group['max_price'] ?? 0);
        if (abs($min - $max) < 0.01) {
            return 'R' . number_format($min, 2);
        }
        return 'R' . number_format($min, 2) . ' to R' . number_format($max, 2);
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
            if (function_exists('getCandybirdCategorySlug')) {
                $customCategoryOrder[getCandybirdCategorySlug($categoryName)] = $index;
            }
        }
        $products = array_values(getSheetProducts());
        $products = array_filter($products, function($product) {
            $id = trim((string) ($product['id'] ?? ''));
            $name = trim((string) ($product['name'] ?? ''));
            $enabled = strtolower(trim((string) ($product['enabled'] ?? '1')));
            $category = cbPricelistCategoryName($product);
            return $id !== '' && $name !== '' && !in_array($enabled, ['0', 'false', 'no', 'disabled'], true)
                && (!function_exists('isCandybirdCategoryVisible') || isCandybirdCategoryVisible($category));
        });

        $productsByCategory = [];
        foreach ($products as $product) {
            $productsByCategory[cbPricelistCategoryName($product)][] = $product;
        }

        uksort($productsByCategory, function($a, $b) use ($customCategoryOrder) {
            $keyA = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($a) : $a;
            $keyB = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($b) : $b;
            $posA = function_exists('getCandybirdCategoryDisplayPosition') ? getCandybirdCategoryDisplayPosition($a) : ($customCategoryOrder[$a] ?? ($customCategoryOrder[$keyA] ?? PHP_INT_MAX));
            $posB = function_exists('getCandybirdCategoryDisplayPosition') ? getCandybirdCategoryDisplayPosition($b) : ($customCategoryOrder[$b] ?? ($customCategoryOrder[$keyB] ?? PHP_INT_MAX));
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
