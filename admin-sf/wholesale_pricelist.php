<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=wholesale_pricelist");
    exit();
}
require __DIR__ . '/sheet_page_helpers.php';

$intro = '<p class="mb-2">Manage the wholesale/bulk pricelist from a separate Google Sheet tab or file. This list is public at <strong>/wholesale-pricelist</strong>, printable as a PDF, and exportable as TSV for future scheduled emails.</p>'
    . '<p class="mb-2">Use product_id to match the normal product sheet. The public product page will only show a small “available in wholesale/bulk” note; wholesale prices are not shown on product pages or used in cart pricing.</p>'
    . '<p class="mb-0">Rows can describe case sizes, per kg pricing, pack-down fees, minimum quantities, boxing notes and lead time. Publish the sheet as TSV and save the link below.</p>';

cbAdminSheetPage('wholesale', 'Wholesale Pricelist', $intro);
?>
