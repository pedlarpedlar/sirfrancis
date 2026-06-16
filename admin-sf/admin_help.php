<?php
if (!function_exists('cbAdminHelpText')) {
    function cbAdminHelpText($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cbAdminHelpContent')) {
    function cbAdminHelpContent($page) {
        $common = [
            'title' => 'Page helper',
            'body' => 'Start with the Admin Sitemap if you are unsure where to go. For a new setup, first save contact info, shipping, sheet links, and payment settings before editing orders or campaigns.',
            'links' => [
                ['Admin Sitemap', 'index'],
                ['Products', 'products'],
                ['Shipping', 'shipping_settings'],
            ],
        ];

        $map = [
            'index' => [
                'title' => 'Admin Sitemap helper',
                'body' => 'This page is the quickest way around admin. Use it when staff are unsure where a feature lives.',
                'links' => [['Dashboard', 'dashboard'], ['Products', 'products'], ['Orders', 'manage_orders']],
            ],
            'dashboard' => [
                'title' => 'Dashboard helper',
                'body' => 'Dashboard cards depend on products, orders, visitors, payments and broadcasts. On quiet days or new setups, empty sections are normal.',
                'links' => [['Orders', 'manage_orders'], ['Visitor Activity', 'visitor_activity'], ['Products', 'products']],
            ],
            'products' => [
                'title' => 'Products helper',
                'body' => 'Download the product template, fill rows from line 3, publish the current Google Sheet as TSV, paste the TSV link here, then sync the product mirror.',
                'links' => [['TSV How-to', 'tsv_how_to'], ['Categories', 'category_order'], ['Mega Sync', 'sheets']],
            ],
            'coupons' => [
                'title' => 'Coupons helper',
                'body' => 'Add coupons in the coupon sheet, then force refresh. Test restricted coupons before sending them to customers.',
                'links' => [['TSV How-to', 'tsv_how_to'], ['Coupon Tester', 'coupon_tester'], ['Broadcasts', 'schedule_email']],
            ],
            'clearance' => [
                'title' => 'Clearance helper',
                'body' => 'Clearance rows reuse a normal product ID but carry their own price, stock and slug. Use quantity 0 for sold out items.',
                'links' => [['TSV How-to', 'tsv_how_to'], ['Products', 'products']],
            ],
            'wholesale_pricelist' => [
                'title' => 'Wholesale helper',
                'body' => 'Wholesale rows are public on the wholesale pricelist only. They do not replace normal product prices or cart prices.',
                'links' => [['TSV How-to', 'tsv_how_to'], ['Wholesale Page', '../wholesale-pricelist']],
            ],
            'sheets' => [
                'title' => 'Sheet links helper',
                'body' => 'Published TSV links are what the website reads. Editable sheet links are only for admin buttons that open Google Sheets.',
                'links' => [['TSV How-to', 'tsv_how_to'], ['Products', 'products'], ['Coupons', 'coupons']],
            ],
            'tsv_how_to' => [
                'title' => 'TSV setup helper',
                'body' => 'Follow the screenshots from top to bottom. The published TSV URL goes into the TSV box; the browser address of the editable sheet goes into the edit URL box.',
                'links' => [['Products', 'products'], ['Coupons', 'coupons'], ['Clearance', 'clearance']],
            ],
            'shipping_settings' => [
                'title' => 'Shipping helper',
                'body' => 'Enable at least one shipping or collection method. Delivery prices, free shipping rules and the Maps API all affect checkout.',
                'links' => [['Contact Info', 'manage_website_information'], ['Checkout', '../checkout']],
            ],
            'site_flags' => [
                'title' => 'Site notices helper',
                'body' => 'Use this for shop closure, delayed processing, holidays, stocktake, or maintenance. Leave dates blank when a notice should stay active until manually paused.',
                'links' => [['Products page', '../products'], ['Checkout', '../checkout']],
            ],
            'manage_orders' => [
                'title' => 'Orders helper',
                'body' => 'Newest orders show first. Open an order for printing, payment status updates, editing, resending emails or customer support.',
                'links' => [['Create Order', 'create_order'], ['Customers', 'manage_users']],
            ],
            'schedule_email' => [
                'title' => 'Broadcast helper',
                'body' => 'Send a test first, then schedule or send. Keep unsubscribe exclusions on unless the message is a genuine operational notice.',
                'links' => [['Broadcast History', 'broadcasts'], ['Email Lists', 'email_lists'], ['Coupons', 'coupons']],
            ],
            'social_accounts' => [
                'title' => 'Social accounts helper',
                'body' => 'Save social profile links and login notes here. Mark the platforms used most often so the reminder cron can tell staff where to post.',
                'links' => [['Business Documents', 'business_documents'], ['Dashboard', 'dashboard']],
            ],
            'business_documents' => [
                'title' => 'Business documents helper',
                'body' => 'Upload admin-only documents like CIPC, SARS, payment provider contracts, trademark files and supplier agreements.',
                'links' => [['Social Accounts', 'social_accounts'], ['Admin Sitemap', 'index']],
            ],
        ];

        return $map[$page] ?? $common;
    }
}

$adminHelpPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '.php');
$adminHelp = cbAdminHelpContent($adminHelpPage);
?>
<style>
    .admin-help-bubble {
        bottom: 18px;
        position: fixed;
        right: 18px;
        z-index: 1080;
    }
    .admin-help-toggle {
        align-items: center;
        background: var(--sf-gold);
        border: 0;
        border-radius: 999px;
        box-shadow: 0 12px 28px rgba(45,23,57,.24);
        color: var(--sf-navy);
        display: flex;
        font-weight: 900;
        gap: 8px;
        padding: 10px 14px;
    }
    .admin-help-panel {
        background: #fff;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        box-shadow: 0 18px 42px rgba(45,23,57,.2);
        bottom: 54px;
        color: #2b2018;
        display: none;
        max-width: 350px;
        padding: 16px;
        position: absolute;
        right: 0;
        width: calc(100vw - 34px);
    }
    .admin-help-panel.open { display: block; }
    .admin-help-panel::after {
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-top: 10px solid #fff;
        bottom: -10px;
        content: "";
        position: absolute;
        right: 22px;
    }
    .admin-help-panel h2 {
        color: #28364B;
        font-size: 17px;
        margin: 0 0 8px;
    }
    .admin-help-panel p {
        color: #5b5049;
        font-size: 13px;
        line-height: 1.55;
        margin: 0 0 12px;
    }
    .admin-help-panel a {
        background: #fbf5ea;
        border-radius: 999px;
        color: #28364B;
        display: inline-block;
        font-size: 12px;
        font-weight: 800;
        margin: 0 7px 7px 0;
        padding: 6px 9px;
    }
</style>
<div class="admin-help-bubble no-print">
    <div class="admin-help-panel" id="adminHelpPanel">
        <h2><?= cbAdminHelpText($adminHelp['title'] ?? 'Page helper') ?></h2>
        <p><?= cbAdminHelpText($adminHelp['body'] ?? '') ?></p>
        <?php foreach (($adminHelp['links'] ?? []) as $link): ?>
            <a href="<?= cbAdminHelpText($link[1] ?? '#') ?>"><?= cbAdminHelpText($link[0] ?? 'Open') ?></a>
        <?php endforeach; ?>
    </div>
    <button class="admin-help-toggle" type="button" id="adminHelpToggle" aria-expanded="false" aria-controls="adminHelpPanel">Help</button>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('adminHelpToggle');
    var panel = document.getElementById('adminHelpPanel');
    if (!toggle || !panel) return;
    toggle.addEventListener('click', function() {
        var isOpen = panel.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
</script>
