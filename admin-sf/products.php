<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=products");
    exit();
}
require __DIR__ . '/sheet_page_helpers.php';

$intro = '<p class="mb-0">Start by uploading product images to the gallery, then download the product template, add your products in Google Sheets, publish the sheet as TSV, and save those links on this page.</p>';

cbAdminSheetPage('products', 'Products', $intro);
