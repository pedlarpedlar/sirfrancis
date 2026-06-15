<?php
require_once __DIR__ . '/product_sheet_helpers.php';

if (!function_exists('cbPricelistCategoryName')) {
    function cbPricelistCategoryName($product) {
        $parent = trim((string) ($product['parent_category'] ?? ''));
        return $parent !== '' ? $parent : 'Other Products';
    }
}

if (!function_exists('cbPricelistCategoryParts')) {
    function cbPricelistCategoryParts($product) {
        $parts = [];
        foreach (['parent_category', 'child_category_1', 'child_category_2'] as $field) {
            $value = trim((string) ($product[$field] ?? ''));
            if ($value !== '' && !in_array($value, $parts, true)) {
                $parts[] = $value;
            }
        }
        return !empty($parts) ? $parts : ['Other Products'];
    }
}

if (!function_exists('cbPricelistCategoryPath')) {
    function cbPricelistCategoryPath($product) {
        return implode(' > ', cbPricelistCategoryParts($product));
    }
}

if (!function_exists('cbPricelistDisplayCategoryPath')) {
    function cbPricelistDisplayCategoryPath($categoryPath) {
        if (function_exists('getCandybirdCategoryPathDisplayLabel')) {
            $customLabel = trim((string) getCandybirdCategoryPathDisplayLabel($categoryPath));
            if ($customLabel !== '' && $customLabel !== $categoryPath) {
                return $customLabel;
            }
        }

        $parts = array_filter(array_map('trim', explode('>', (string) $categoryPath)));
        if (empty($parts)) {
            $parts = ['Other Products'];
        }

        $labels = [];
        foreach ($parts as $part) {
            $labels[] = function_exists('getCandybirdCategoryDisplayLabel') ? getCandybirdCategoryDisplayLabel($part) : $part;
        }
        return implode(' / ', $labels);
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

if (!function_exists('cbPricelistNormalizeArrayParam')) {
    function cbPricelistNormalizeArrayParam($value) {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/,/', (string) $value);
        }
        $clean = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $clean[] = $item;
            }
        }
        return array_values(array_unique($clean));
    }
}

if (!function_exists('cbPricelistFiltersFromRequest')) {
    function cbPricelistFiltersFromRequest($source) {
        $sale = strtolower(trim((string) ($source['sale'] ?? 'all')));
        if (!in_array($sale, ['all', 'sale', 'regular'], true)) {
            $sale = 'all';
        }
        $limit = isset($source['limit']) ? (int) $source['limit'] : 0;
        return [
            'q' => trim((string) ($source['q'] ?? '')),
            'categories' => cbPricelistNormalizeArrayParam($source['categories'] ?? []),
            'sizes' => cbPricelistNormalizeArrayParam($source['sizes'] ?? []),
            'min_price' => isset($source['min_price']) && $source['min_price'] !== '' ? max(0, (float) $source['min_price']) : null,
            'max_price' => isset($source['max_price']) && $source['max_price'] !== '' ? max(0, (float) $source['max_price']) : null,
            'sale' => $sale,
            'limit' => $limit > 0 ? min($limit, 1000) : 0,
        ];
    }
}

if (!function_exists('cbPricelistNormalizeSearchText')) {
    function cbPricelistNormalizeSearchText($value) {
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }
}

if (!function_exists('cbPricelistProductMatchesFilters')) {
    function cbPricelistProductMatchesFilters($product, $filters) {
        $pricing = cbPricelistPricing($product);
        $salePrice = (float) $pricing['sale_price'];

        if ($filters['min_price'] !== null && $salePrice < (float) $filters['min_price']) {
            return false;
        }
        if ($filters['max_price'] !== null && $salePrice > (float) $filters['max_price']) {
            return false;
        }
        if ($filters['sale'] === 'sale' && empty($pricing['is_special'])) {
            return false;
        }
        if ($filters['sale'] === 'regular' && !empty($pricing['is_special'])) {
            return false;
        }

        $size = getSheetProductDisplaySize($product);
        if (!empty($filters['sizes']) && !in_array($size, $filters['sizes'], true)) {
            return false;
        }

        $categoryPath = cbPricelistCategoryPath($product);
        if (!empty($filters['categories']) && !in_array($categoryPath, $filters['categories'], true)) {
            return false;
        }

        $query = cbPricelistNormalizeSearchText($filters['q'] ?? '');
        if ($query !== '') {
            $haystack = cbPricelistNormalizeSearchText(implode(' ', [
                $product['id'] ?? '',
                $product['name'] ?? '',
                $product['title'] ?? '',
                $size,
                $categoryPath,
                $product['parent_category'] ?? '',
                $product['child_category_1'] ?? '',
                $product['child_category_2'] ?? '',
            ]));
            foreach (array_filter(explode(' ', $query)) as $token) {
                if (strpos($haystack, $token) === false) {
                    return false;
                }
            }
        }

        return true;
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

if (!function_exists('cbPricelistManualSortValue')) {
    function cbPricelistManualSortValue($product) {
        $value = trim((string) ($product['pricelist_sort'] ?? $product['price_list_sort'] ?? $product['sort_order'] ?? ''));
        if ($value === '') {
            return PHP_INT_MAX;
        }
        return is_numeric($value) ? (float) $value : PHP_INT_MAX;
    }
}

if (!function_exists('cbPricelistProductGroups')) {
    function cbPricelistProductGroups($products, $sort = 'name', $direction = 'asc') {
        $sort = in_array($sort, ['custom', 'id', 'name', 'size', 'price', 'sale'], true) ? $sort : 'custom';
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
                    'manual_sort' => cbPricelistManualSortValue($product),
                ];
            }

            $pricing = cbPricelistPricing($product);
            $price = (float) $pricing['sale_price'];
            $groups[$key]['products'][] = $product;
            $groups[$key]['min_price'] = $groups[$key]['min_price'] === null ? $price : min($groups[$key]['min_price'], $price);
            $groups[$key]['max_price'] = $groups[$key]['max_price'] === null ? $price : max($groups[$key]['max_price'], $price);
            $groups[$key]['first_id'] = strnatcasecmp((string) ($product['id'] ?? ''), (string) $groups[$key]['first_id']) < 0 ? (string) ($product['id'] ?? '') : $groups[$key]['first_id'];
            $groups[$key]['min_size_sort'] = min((float) $groups[$key]['min_size_sort'], (float) cbPricelistSortValue($product['size'] ?? ''));
            $groups[$key]['manual_sort'] = min((float) $groups[$key]['manual_sort'], (float) cbPricelistManualSortValue($product));
        }

        $groups = array_values($groups);
        usort($groups, static function($a, $b) use ($sort, $direction) {
            switch ($sort) {
                case 'custom':
                    $compare = ((float) ($a['manual_sort'] ?? PHP_INT_MAX)) <=> ((float) ($b['manual_sort'] ?? PHP_INT_MAX));
                    break;
                case 'id':
                    $compare = strnatcasecmp((string) ($a['first_id'] ?? ''), (string) ($b['first_id'] ?? ''));
                    break;
                case 'price':
                    $compare = ((float) ($a['min_price'] ?? 0)) <=> ((float) ($b['min_price'] ?? 0));
                    break;
                case 'sale':
                    $aSale = 0;
                    foreach ($a['products'] as $product) {
                        if (cbPricelistPricing($product)['is_special']) {
                            $aSale = 1;
                            break;
                        }
                    }
                    $bSale = 0;
                    foreach ($b['products'] as $product) {
                        if (cbPricelistPricing($product)['is_special']) {
                            $bSale = 1;
                            break;
                        }
                    }
                    $compare = $bSale <=> $aSale;
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
    function cbPricelistProductsByCategory($sort = 'name', $direction = 'asc', $filters = []) {
        $sort = in_array($sort, ['custom', 'id', 'name', 'size', 'price', 'sale'], true) ? $sort : 'custom';
        $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';
        $filters = array_merge([
            'q' => '',
            'categories' => [],
            'sizes' => [],
            'min_price' => null,
            'max_price' => null,
            'sale' => 'all',
            'limit' => 0,
        ], is_array($filters) ? $filters : []);
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
            $categoryPath = cbPricelistCategoryPath($product);
            return $id !== '' && $name !== '' && !in_array($enabled, ['0', 'false', 'no', 'disabled'], true)
                && (!function_exists('isCandybirdCategoryVisible') || isCandybirdCategoryVisible($category))
                && (!function_exists('isCandybirdCategoryPathVisible') || isCandybirdCategoryPathVisible($categoryPath));
        });
        $products = array_values(array_filter($products, function($product) use ($filters) {
            return cbPricelistProductMatchesFilters($product, $filters);
        }));

        $productsByCategory = [];
        foreach ($products as $product) {
            $productsByCategory[cbPricelistCategoryPath($product)][] = $product;
        }

        uksort($productsByCategory, function($a, $b) use ($customCategoryOrder) {
            $partsA = array_filter(array_map('trim', explode('>', (string) $a)));
            $partsB = array_filter(array_map('trim', explode('>', (string) $b)));
            $rootA = $partsA[0] ?? $a;
            $rootB = $partsB[0] ?? $b;
            $keyA = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($rootA) : $rootA;
            $keyB = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($rootB) : $rootB;
            $posA = function_exists('getCandybirdCategoryDisplayPosition') ? getCandybirdCategoryDisplayPosition($rootA) : ($customCategoryOrder[$rootA] ?? ($customCategoryOrder[$keyA] ?? PHP_INT_MAX));
            $posB = function_exists('getCandybirdCategoryDisplayPosition') ? getCandybirdCategoryDisplayPosition($rootB) : ($customCategoryOrder[$rootB] ?? ($customCategoryOrder[$keyB] ?? PHP_INT_MAX));
            $pathPosA = function_exists('getCandybirdCategoryPathDisplayPosition') ? getCandybirdCategoryPathDisplayPosition($a) : PHP_INT_MAX;
            $pathPosB = function_exists('getCandybirdCategoryPathDisplayPosition') ? getCandybirdCategoryPathDisplayPosition($b) : PHP_INT_MAX;
            if ($pathPosA !== $pathPosB) {
                return $pathPosA <=> $pathPosB;
            }
            return $posA === $posB ? strnatcasecmp($a, $b) : $posA <=> $posB;
        });

        foreach ($productsByCategory as &$categoryProducts) {
            usort($categoryProducts, function($a, $b) use ($sort, $direction) {
                switch ($sort) {
                    case 'custom':
                        $compare = cbPricelistManualSortValue($a) <=> cbPricelistManualSortValue($b);
                        break;
                    case 'id':
                        $compare = strnatcasecmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
                        break;
                    case 'price':
                        $compare = cbPricelistPricing($a)['sale_price'] <=> cbPricelistPricing($b)['sale_price'];
                        break;
                    case 'sale':
                        $compare = (int) cbPricelistPricing($b)['is_special'] <=> (int) cbPricelistPricing($a)['is_special'];
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

        if ((int) ($filters['limit'] ?? 0) > 0) {
            $remaining = (int) $filters['limit'];
            foreach ($productsByCategory as $categoryName => $categoryProducts) {
                if ($remaining <= 0) {
                    unset($productsByCategory[$categoryName]);
                    continue;
                }
                if (count($categoryProducts) > $remaining) {
                    $productsByCategory[$categoryName] = array_slice($categoryProducts, 0, $remaining);
                    $remaining = 0;
                } else {
                    $remaining -= count($categoryProducts);
                }
            }
        }

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

if (!function_exists('cbPricelistFilterOptions')) {
    function cbPricelistFilterOptions() {
        $sizes = [];
        $categories = [];
        foreach (cbPricelistProductsByCategory('custom', 'asc') as $categoryPath => $products) {
            $categories[$categoryPath] = cbPricelistDisplayCategoryPath($categoryPath);
            foreach ($products as $product) {
                $size = getSheetProductDisplaySize($product);
                if ($size !== '') {
                    $sizes[$size] = $size;
                }
            }
        }
        uksort($sizes, function($a, $b) {
            $compare = cbPricelistSortValue($a) <=> cbPricelistSortValue($b);
            return $compare === 0 ? strnatcasecmp($a, $b) : $compare;
        });
        asort($categories, SORT_NATURAL | SORT_FLAG_CASE);
        return ['sizes' => $sizes, 'categories' => $categories];
    }
}

if (!function_exists('cbPricelistWhatsappMoney')) {
    function cbPricelistWhatsappMoney($value) {
        $value = (float) $value;
        return abs($value - round($value)) < 0.01 ? 'R' . number_format($value, 0) : 'R' . number_format($value, 2);
    }
}

if (!function_exists('cbPricelistWhatsappLine')) {
    function cbPricelistWhatsappLine($product) {
        $title = getSheetProductDisplayTitle($product);
        $pricing = cbPricelistPricing($product);
        if ($pricing['is_special']) {
            return $title . ' @ ~' . cbPricelistWhatsappMoney($pricing['normal_price']) . '~ ' . cbPricelistWhatsappMoney($pricing['sale_price']);
        }
        return $title . ' @ ' . cbPricelistWhatsappMoney($pricing['normal_price']);
    }
}

if (!function_exists('cbPricelistFlattenProducts')) {
    function cbPricelistFlattenProducts($productsByCategory) {
        $products = [];
        foreach ($productsByCategory as $categoryProducts) {
            foreach ($categoryProducts as $product) {
                $products[] = $product;
            }
        }
        return $products;
    }
}
?>
