<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo 'Admin login required.';
    exit();
}

require __DIR__ . '/sheet_page_helpers.php';
@include_once __DIR__ . '/db_connect.php';

function cbDownloadTemplateBusinessSlug($conn) {
    $businessName = '';
    if ($conn instanceof mysqli) {
        $result = $conn->query("SELECT * FROM admin_website_settings LIMIT 1");
        if ($result && ($row = $result->fetch_assoc())) {
            foreach (['website_company_name', 'company_name', 'business_name', 'site_name', 'name'] as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    $businessName = $value;
                    break;
                }
            }
        }
    }

    if ($businessName === '') {
        $businessName = 'mywebsite';
    }

    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $businessName));
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'mywebsite';
}

function cbDownloadTemplateHtmlCell($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/<\s*(p|div|ul|ol|li|br|strong|em|b|i|span|h[1-6]|table|blockquote)\b/i', $value)) {
        return $value;
    }

    $blocks = preg_split('/\R{2,}/', $value);
    $html = [];
    foreach ($blocks as $block) {
        $lines = array_filter(array_map('trim', preg_split('/\R/', (string) $block)));
        if (!$lines) {
            continue;
        }
        $html[] = '<p>' . implode('<br>', array_map(static function($line) {
            return htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
        }, $lines)) . '</p>';
    }

    return implode("\n", $html);
}

function cbDownloadTemplateWriteQuotedTsvRow($handle, $row) {
    $cells = [];
    foreach ($row as $value) {
        $cell = str_replace('"', '""', (string) $value);
        $cells[] = '"' . $cell . '"';
    }
    fwrite($handle, implode("\t", $cells) . "\n");
}

$type = strtolower(trim((string) ($_GET['type'] ?? 'products')));
if (!in_array($type, ['products', 'coupons', 'clearance', 'wholesale'], true)) {
    $type = 'products';
}

$exportProducts = $type === 'products' && isset($_GET['export']) && $_GET['export'] === '1';
$filename = cbDownloadTemplateBusinessSlug($conn ?? null) . '-' . $type . ($exportProducts ? '-export.tsv' : '-template.tsv');
header('Content-Type: text/tab-separated-values; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
if ($exportProducts) {
    $headers = getCandybirdProductTemplateHeaders();
    cbDownloadTemplateWriteQuotedTsvRow($out, $headers);
    foreach (getSheetProducts(false) as $product) {
        $row = [];
        foreach ($headers as $header) {
            $value = (string) ($product[$header] ?? '');
            if (in_array($header, ['html_description', 'disclaimers'], true)) {
                $value = cbDownloadTemplateHtmlCell($value);
            }
            $row[] = $value;
        }
        cbDownloadTemplateWriteQuotedTsvRow($out, $row);
    }
} else {
    foreach (cbAdminSheetTemplateRows($type) as $row) {
        cbDownloadTemplateWriteQuotedTsvRow($out, $row);
    }
}
fclose($out);
