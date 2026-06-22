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
    fputcsv($out, $headers, "\t");
    foreach (getSheetProducts(false) as $product) {
        $row = [];
        foreach ($headers as $header) {
            $row[] = (string) ($product[$header] ?? '');
        }
        fputcsv($out, $row, "\t");
    }
} else {
    foreach (cbAdminSheetTemplateRows($type) as $row) {
        fputcsv($out, $row, "\t");
    }
}
fclose($out);
