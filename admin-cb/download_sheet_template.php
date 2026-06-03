<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo 'Admin login required.';
    exit();
}

require __DIR__ . '/sheet_page_helpers.php';

$type = strtolower(trim((string) ($_GET['type'] ?? 'products')));
if (!in_array($type, ['products', 'coupons', 'clearance', 'wholesale'], true)) {
    $type = 'products';
}

$filename = 'candybird-' . $type . '-template.tsv';
header('Content-Type: text/tab-separated-values; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
foreach (cbAdminSheetTemplateRows($type) as $row) {
    fputcsv($out, $row, "\t");
}
fclose($out);
