<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=clearance");
    exit();
}
require __DIR__ . '/sheet_page_helpers.php';

$intro = '<p class="mb-2">Manage Clearance Basket items from a separate live Google Sheet. These rows can reuse existing product IDs for image and detail fallback, while keeping clearance price, stock, slug and labels separate from the normal product listing.</p>'
    . '<p class="mb-2">Download the clearance template, fill in rows from line 3, publish the current sheet to the web as TSV, and save the links below.</p>'
    . '<p class="mb-0">Clearance stock is treated separately. A quantity of 0 can still show as sold out on product/category pages, but it will not be orderable.</p>';

cbAdminSheetPage('clearance', 'Clearance Basket', $intro);
