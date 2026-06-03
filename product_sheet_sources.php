<?php
date_default_timezone_set('Africa/Johannesburg');
if (!function_exists('getCandybirdDefaultSheetSources')) {
    function getCandybirdDefaultSheetSources() {
        return [
            'products' => [
                'label' => 'Products',
                'published_url' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRhtg-QlUDokG6Tcsj29r1RMRWp9y9Fl2rcjh17s5F3xc5Re6tfaU54imMepBWNbA1xJKoVvNCUOX2d/pub?gid=380423212&single=true&output=tsv',
                'edit_url' => 'https://docs.google.com/spreadsheets/d/17L-lvBdS0W2Fvf9tzjQj55CweIKpKAdqlcP4aAPLZT8/edit?gid=380423212#gid=380423212',
                'required_headers' => ['id', 'name', 'size', 'price', 'parent_category', 'child_category_1', 'child_category_2', 'html_description', 'img_url'],
                'optional_headers' => ['discount', 'discounted_price', 'discount_valid_from', 'discount_valid_until', 'product_type', 'qty_in_stock', 'lead_time', 'slug', 'homepage_featured', 'shipping_weight'],
            ],
            'coupons' => [
                'label' => 'Coupons',
                'published_url' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vS6OtaChjDDrYCGBfpSaK8nnixhqpoZpJwPKRnA0MmuTCHrIIYSoksqoFkB8syrlxfOjn27rOY0wvll/pub?gid=0&single=true&output=tsv',
                'edit_url' => 'https://docs.google.com/spreadsheets/d/1aofJluANxsJ-jtEIh9w1DDRzVuArtJVG32nFndX6bqw/edit?gid=0#gid=0',
                'required_headers' => ['id', 'coupon_code', 'valid_from', 'valid_until', 'valid_on_sale_items', 'min_order_value', 'discount_type', 'discount_value'],
                'optional_headers' => ['valid_count', 'multi_user', 'email_restriction', 'phone_restriction'],
            ],
            'clearance' => [
                'label' => 'Clearance Basket',
                'published_url' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSvZQRweWXcY9ap_m_wZnut2KpFF-Y7Kcvwh9AutZwdB7H768y0bZZhZfdXo28L6740zYbRTA-K2da-/pub?gid=0&single=true&output=tsv',
                'edit_url' => '',
                'required_headers' => ['clearance_id', 'product_id', 'clearance_price', 'qty_available'],
                'optional_headers' => ['slug', 'clearance_reason', 'clearance_tag', 'clearance_notes', 'valid_from', 'valid_until', 'clearance_title', 'clearance_img_url', 'clearance_description'],
            ],
            'wholesale' => [
                'label' => 'Wholesale Pricelist',
                'published_url' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRhtg-QlUDokG6Tcsj29r1RMRWp9y9Fl2rcjh17s5F3xc5Re6tfaU54imMepBWNbA1xJKoVvNCUOX2d/pub?gid=0&single=true&output=tsv',
                'edit_url' => '',
                'required_headers' => ['product_id', 'size', 'price'],
                'optional_headers' => ['title', 'description', 'case_size', 'price_per_kg', 'pack_down_fee', 'moq', 'lead_time', 'enabled'],
            ],
        ];
    }
}

if (!function_exists('getCandybirdSheetSourceFile')) {
    function getCandybirdSheetSourceFile() {
        return __DIR__ . '/sheet_cache/sheet_sources.json';
    }
}

if (!function_exists('getCandybirdSheetSources')) {
    function getCandybirdSheetSources() {
        $sources = getCandybirdDefaultSheetSources();
        $sourceFile = getCandybirdSheetSourceFile();

        if (is_file($sourceFile)) {
            $saved = json_decode((string) file_get_contents($sourceFile), true);
            if (is_array($saved)) {
                foreach ($saved as $key => $source) {
                    if (isset($sources[$key]) && is_array($source)) {
                        $sources[$key] = array_merge($sources[$key], array_intersect_key($source, $sources[$key]));
                    }
                }
            }
        }

        return $sources;
    }
}

if (!function_exists('getCandybirdSheetSource')) {
    function getCandybirdSheetSource($key) {
        $sources = getCandybirdSheetSources();
        return $sources[$key] ?? null;
    }
}

if (!function_exists('getCandybirdSheetUrl')) {
    function getCandybirdSheetUrl($key) {
        $source = getCandybirdSheetSource($key);
        return $source['published_url'] ?? '';
    }
}

if (!function_exists('getCandybirdSheetEditUrl')) {
    function getCandybirdSheetEditUrl($key) {
        $source = getCandybirdSheetSource($key);
        return $source['edit_url'] ?? '';
    }
}

if (!function_exists('saveCandybirdSheetSources')) {
    function saveCandybirdSheetSources($sources) {
        $current = getCandybirdSheetSources();
        foreach (['products', 'coupons', 'clearance', 'wholesale'] as $key) {
            if (!isset($sources[$key])) {
                continue;
            }

            foreach (['published_url', 'edit_url'] as $field) {
                $value = trim((string) ($sources[$key][$field] ?? ''));
                if ($value !== '') {
                    $current[$key][$field] = $value;
                }
            }
        }

        $sourceFile = getCandybirdSheetSourceFile();
        $dir = dirname($sourceFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return (bool) @file_put_contents($sourceFile, json_encode($current, JSON_PRETTY_PRINT), LOCK_EX);
    }
}

if (!function_exists('fetchCandybirdSheetRaw')) {
    function fetchCandybirdSheetRaw($url) {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            $data = curl_exec($ch);
            curl_close($ch);
            if ($data !== false && trim((string) $data) !== '') {
                return $data;
            }
        }

        if (ini_get('allow_url_fopen')) {
            $context = stream_context_create(['http' => ['timeout' => 8]]);
            $data = @file_get_contents($url, false, $context);
            if ($data !== false && trim((string) $data) !== '') {
                return $data;
            }
        }

        return false;
    }
}

if (!function_exists('checkCandybirdSheetHealth')) {
    function candybirdSheetRowHasValue($row, $headers, $field) {
        $index = array_search(strtolower($field), array_map('strtolower', $headers), true);
        return $index !== false && trim((string) ($row[$index] ?? '')) !== '';
    }

    function candybirdSheetRowIsMeaningful($key, $row, $headers) {
        if ($key === 'products') {
            return candybirdSheetRowHasValue($row, $headers, 'id') &&
                candybirdSheetRowHasValue($row, $headers, 'name') &&
                candybirdSheetRowHasValue($row, $headers, 'price');
        }

        if ($key === 'coupons') {
            return candybirdSheetRowHasValue($row, $headers, 'coupon_code');
        }

        if ($key === 'clearance') {
            return candybirdSheetRowHasValue($row, $headers, 'clearance_id') &&
                candybirdSheetRowHasValue($row, $headers, 'product_id') &&
                candybirdSheetRowHasValue($row, $headers, 'clearance_price') &&
                candybirdSheetRowHasValue($row, $headers, 'qty_available');
        }

        if ($key === 'wholesale') {
            return candybirdSheetRowHasValue($row, $headers, 'product_id') &&
                (candybirdSheetRowHasValue($row, $headers, 'price') || candybirdSheetRowHasValue($row, $headers, 'price_per_kg')) &&
                (candybirdSheetRowHasValue($row, $headers, 'size') || candybirdSheetRowHasValue($row, $headers, 'case_size'));
        }

        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return true;
            }
        }

        return false;
    }

    function checkCandybirdSheetHealth($key, $forceRefresh = false) {
        $source = getCandybirdSheetSource($key);
        if (!$source) {
            return ['ok' => false, 'message' => 'Unknown sheet source.', 'headers' => [], 'missing_headers' => [], 'row_count' => 0];
        }

        if ($key === 'clearance' && trim((string) ($source['published_url'] ?? '')) === '') {
            return ['ok' => true, 'message' => 'Optional clearance sheet is not configured yet.', 'headers' => [], 'missing_headers' => [], 'row_count' => 0];
        }

        $raw = fetchCandybirdSheetRaw($source['published_url']);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'The published TSV link could not be read.', 'headers' => [], 'missing_headers' => $source['required_headers'], 'row_count' => 0];
        }

        $handle = fopen('php://temp', 'r+');
        if (!$handle) {
            return ['ok' => false, 'message' => 'The sheet could not be parsed.', 'headers' => [], 'missing_headers' => $source['required_headers'], 'row_count' => 0];
        }

        fwrite($handle, (string) $raw);
        rewind($handle);

        $headers = fgetcsv($handle, 0, "\t");
        if (!$headers) {
            fclose($handle);
            return ['ok' => false, 'message' => 'The sheet is empty.', 'headers' => [], 'missing_headers' => $source['required_headers'], 'row_count' => 0];
        }

        $headers = array_map('trim', $headers);
        $normalizedHeaders = array_map('strtolower', $headers);
        $missing = [];
        foreach ($source['required_headers'] as $header) {
            if (!in_array(strtolower($header), $normalizedHeaders, true)) {
                $missing[] = $header;
            }
        }

        $rowCount = 0;
        $ignoredRowCount = 0;
        $scannedRowCount = 0;
        $stoppedAtRow = null;
        $explanationRowSkipped = false;
        $couponPhoneRestrictionCount = 0;
        $couponEmailRestrictionCount = 0;
        $couponInvalidPhoneRestrictionCount = 0;
        $productIds = [];
        $headerCount = count($headers);
        $normalizedHeadersForRows = array_map('strtolower', $headers);
        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            if (count(array_filter($row, static function($cell) {
                return trim((string) $cell) !== '';
            })) === 0) {
                continue;
            }

            $scannedRowCount++;
            $firstCell = strtoupper(trim((string) ($row[0] ?? '')));
            if (in_array($firstCell, ['END', 'STOP', '__END__'], true)) {
                $stoppedAtRow = $scannedRowCount + 1;
                break;
            }

            if ($scannedRowCount === 1) {
                $firstHeader = strtolower(trim((string) ($headers[0] ?? '')));
                $firstValue = trim((string) ($row[0] ?? ''));
                if (in_array(strtolower($firstValue), ['note', 'notes', 'explainer', 'instructions', 'instruction', 'example', 'help'], true)
                    || ($firstHeader === 'id' && $firstValue !== '' && !is_numeric($firstValue))) {
                    $ignoredRowCount++;
                    $explanationRowSkipped = true;
                    continue;
                }
            }

            if (count($row) < $headerCount) {
                $row = array_pad($row, $headerCount, '');
            } elseif (count($row) > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            if (candybirdSheetRowIsMeaningful($key, $row, $headers)) {
                $rowCount++;
                if ($key === 'products') {
                    $idIndex = array_search('id', $normalizedHeadersForRows, true);
                    if ($idIndex !== false) {
                        $productIds[] = trim((string) ($row[$idIndex] ?? ''));
                    }
                } elseif ($key === 'coupons') {
                    $phoneIndex = array_search('phone_restriction', $normalizedHeadersForRows, true);
                    $emailIndex = array_search('email_restriction', $normalizedHeadersForRows, true);
                    if ($phoneIndex !== false && trim((string) ($row[$phoneIndex] ?? '')) !== '') {
                        $couponPhoneRestrictionCount++;
                        $phoneDigits = preg_replace('/\D+/', '', (string) ($row[$phoneIndex] ?? ''));
                        if (strlen($phoneDigits) > 0 && strlen($phoneDigits) < 9) {
                            $couponInvalidPhoneRestrictionCount++;
                        }
                    }
                    if ($emailIndex !== false && trim((string) ($row[$emailIndex] ?? '')) !== '') {
                        $couponEmailRestrictionCount++;
                    }
                }
            } else {
                $ignoredRowCount++;
            }
        }
        fclose($handle);

        $uniqueProductIds = $key === 'products' ? array_values(array_unique(array_filter($productIds))) : [];
        $duplicateProductIds = [];
        if ($key === 'products' && !empty($productIds)) {
            $idCounts = array_count_values(array_filter($productIds));
            foreach ($idCounts as $id => $count) {
                if ($count > 1) {
                    $duplicateProductIds[] = $id;
                }
            }
        }

        return [
            'ok' => empty($missing) && $rowCount > 0,
            'message' => empty($missing) ? 'Readable and formatted correctly.' : 'Readable, but required headers are missing.',
            'headers' => $headers,
            'missing_headers' => $missing,
            'row_count' => $rowCount,
            'ignored_row_count' => $ignoredRowCount,
            'scanned_row_count' => $scannedRowCount,
            'stopped_at_row' => $stoppedAtRow,
            'explanation_row_skipped' => $explanationRowSkipped,
            'coupon_phone_restriction_count' => $couponPhoneRestrictionCount,
            'coupon_email_restriction_count' => $couponEmailRestrictionCount,
            'coupon_invalid_phone_restriction_count' => $couponInvalidPhoneRestrictionCount,
            'unique_id_count' => count($uniqueProductIds),
            'duplicate_id_row_count' => max(0, $rowCount - count($uniqueProductIds)),
            'duplicate_ids' => array_slice($duplicateProductIds, 0, 25),
            'published_url' => $source['published_url'],
            'edit_url' => $source['edit_url'],
        ];
    }
}
?>
