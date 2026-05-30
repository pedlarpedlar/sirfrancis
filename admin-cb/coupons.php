<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=coupons");
    exit();
}
require __DIR__ . '/sheet_page_helpers.php';

$intro = '<p class="mb-2">Manage coupon codes via a live Google Sheet. Download the coupon template, fill in your coupon rows from line 3, publish the current sheet to the web in TSV format, and save both links below.</p>'
    . '<p class="mb-2">Use the force sync button whenever you add or change a coupon and want checkout to read it immediately. If you skip this, coupons refresh automatically at midnight.</p>'
    . '<p class="mb-0">Only one coupon can be used per order. Keep coupon dates, minimum order values, restrictions, and usage counts clean so checkout can calculate discounts correctly.</p>';

cbAdminSheetPage('coupons', 'Coupons', $intro);
