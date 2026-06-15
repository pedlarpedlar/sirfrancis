<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=products");
    exit();
}
require __DIR__ . '/sheet_page_helpers.php';

$intro = '<p class="mb-2">Manage product listings via a live Google Sheet. To get started, download a template here, fill in a few products starting at line 3 of the document, save it, upload it to your Google account, and publish the current sheet to the web in TSV format.</p>'
    . '<p class="mb-2">Copy the published TSV link into the Published TSV URL box below. Then copy the editable document URL from your browser and paste it into the Editable Google Sheet input. You only need to do this once.</p>'
    . '<p class="mb-2">Whenever you make updates to your sheet, come back here and use the Mega Sync All Sheets button when you want every sheet refreshed, or the product sync button when only products changed. If you do not do this, products will only update at midnight. After syncing, wait 5-10 minutes and force refresh your browser because older product data may still be cached.</p>'
    . '<p class="mb-0">Follow the correct data formats carefully. Bad formatting can spoil the upload. Categories are added in this same document, will appear on the live website, and are edited from the sheet. The display order can be changed from Categories.</p>';

cbAdminSheetPage('products', 'Products', $intro);
