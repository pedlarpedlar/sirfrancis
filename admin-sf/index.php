<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "index";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include __DIR__ . '/header.php';
include __DIR__ . '/page_menues.php';

function cbAdminSitemapText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$siteBase = '../';
$linkGroups = [
    'Daily Work' => [
        ['Admin Sitemap', 'index', 'Fast staff jump page'],
        ['Dashboard', 'dashboard', 'Main admin overview and reports'],
        ['Orders', 'manage_orders', 'Find, edit, print, cancel and update orders'],
        ['Create Order', 'create_order', 'Create an order for a customer'],
        ['Customers', 'manage_users', 'Customer and account management'],
        ['Visitor Activity', 'visitor_activity', 'Human activity, carts, sessions and searches'],
        ['Email Broadcasts', 'schedule_email', 'Schedule and test campaign emails'],
        ['Email Lists', 'email_lists', 'Saved custom recipient groups for targeted broadcasts'],
        ['Broadcast History', 'broadcasts', 'Past and pending broadcasts, results, edit, copy and delete'],
        ['Social Accounts', 'social_accounts', 'Social handles, login notes and posting reminder settings'],
        ['Business Documents', 'business_documents', 'Admin-only CIPC, SARS, contracts, tax and business document vault'],
        ['Find Agents', 'agents', 'Add supplier agents, map pins and phone numbers'],
    ],
    'Sheets & Products' => [
        ['Products', 'products', 'Product sheet links, health, template and force sync'],
        ['Coupons', 'coupons', 'Coupon sheet links, health, template and force sync'],
        ['Clearance Basket', 'clearance', 'Clearance sheet links, health, template and force sync'],
        ['Wholesale Pricelist', 'wholesale_pricelist', 'Wholesale sheet links, template, printable list and force sync'],
        ['Categories', 'category_order', 'Edit category labels, visibility and display order'],
        ['Mega Sync All Sheets', 'sheets', 'Force refresh products, coupons, clearance and wholesale sheets'],
        ['TSV How-to', 'tsv_how_to', 'Screenshots and steps for publishing Google Sheets as TSV'],
        ['Product Sheet', 'https://docs.google.com/spreadsheets/d/17L-lvBdS0W2Fvf9tzjQj55CweIKpKAdqlcP4aAPLZT8/edit?gid=380423212#gid=380423212', 'Open Google product sheet'],
        ['Coupon Sheet', 'https://docs.google.com/spreadsheets/d/1aofJluANxsJ-jtEIh9w1DDRzVuArtJVG32nFndX6bqw/edit?gid=0#gid=0', 'Open Google coupon sheet'],
        ['Product Gallery', 'manage_gallery', 'Upload and manage product images'],
        ['Categories', 'manage_categories', 'Category management'],
    ],
    'Website & Settings' => [
        ['Contact Info', 'manage_website_information', 'Company, contact, address and banking settings'],
        ['Shipping Settings', 'shipping_settings', 'Shipping methods, prices and free shipping'],
        ['Google Maps & Places', 'google_maps_places', 'Maps and Places API keys for live maps and address autocomplete'],
        ['Google reCAPTCHA', 'google_recaptcha', 'Contact form spam protection settings'],
        ['Editor Settings', 'editor_settings', 'TinyMCE rich text editor API key and editor configuration'],
        ['Site Notices', 'site_flags', 'Shop closure, delayed-processing and maintenance flags'],
        ['Run Cron Jobs', 'run_cron', 'Manually trigger available cron jobs'],
        ['Backups', 'backups', 'Backup downloads and backup cron status'],
        ['Legacy Shipping Zones', 'shipping', 'Old province/country shipping zone page'],
        ['Admin Settings', 'settings', 'Admin account settings'],
    ],
    'Public Website' => [
        ['Homepage', $siteBase . 'index', 'Public homepage'],
        ['Shop', $siteBase . 'products', 'Main product listing'],
        ['Pricelist', $siteBase . 'pricelist', 'Customer pricelist'],
        ['Wholesale Pricelist', $siteBase . 'wholesale-pricelist', 'Wholesale/bulk pricelist'],
        ['Cart', $siteBase . 'cart', 'Customer cart'],
        ['Checkout', $siteBase . 'checkout', 'Customer checkout'],
        ['Contact', $siteBase . 'contact', 'Contact page'],
        ['Find an Agent', $siteBase . 'find-agent', 'Public agent map and agent lookup'],
    ],
    'Policy & Sales Pages' => [
        ['Terms', $siteBase . 'terms', 'Terms and conditions'],
        ['Privacy Policy', $siteBase . 'privacypolicy', 'Privacy information'],
        ['Returns / Iqaalah', $siteBase . 'refund', 'Returns and buyer protection'],
        ['Delivery Policy', $siteBase . 'delivery-policy', 'Delivery information'],
        ['Wholesale', $siteBase . 'wholesale', 'Wholesale information'],
        ['Private Labelling', $siteBase . 'private-labelling', 'Private label information'],
        ['Bulk Ordering', $siteBase . 'bulk-ordering', 'Bulk order information'],
        ['Private Labelling', $siteBase . 'private_labelling', 'Private labelling information'],
    ],
    'Utilities' => [
        ['Google Shopping Feed Cron', '../crons/generate_google_shopping_items.php', 'Generate product feed'],
        ['Daily Backup Cron', '../crons/db_backup_and_email.php?mode=daily', 'Daily important data backup'],
        ['Full Backup Cron', '../crons/db_backup_and_email.php?mode=full', 'Monthly full backup'],
        ['Social Posting Reminder Cron', '../crons/social_posting_reminder.php', 'Sends the social posting accountability reminder when due'],
        ['Send Scheduled Emails', 'send_scheduled_emails', 'Process due broadcast emails'],
    ],
];
?>

<title>Admin Sitemap - Sir Francis</title>

<style>
    .admin-sitemap-wrap {
        padding: 30px 0 70px;
    }

    .admin-sitemap-hero {
        background: var(--sf-navy);
        border-radius: 8px;
        color: #fff;
        margin-bottom: 18px;
        padding: 22px;
    }

    .admin-sitemap-hero h1 {
        color: var(--sf-gold);
        font-size: 28px;
        margin-bottom: 6px;
    }

    .admin-sitemap-hero p {
        color: rgba(255, 255, 255, .84);
        margin: 0;
    }

    .admin-sitemap-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-sitemap-card {
        background: #fff;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        padding: 16px;
    }

    .admin-sitemap-card h2 {
        color: #28364B;
        font-size: 18px;
        margin-bottom: 10px;
    }

    .admin-sitemap-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .admin-sitemap-list li {
        border-top: 1px solid #f0e7de;
        padding: 9px 0;
    }

    .admin-sitemap-list li:first-child {
        border-top: 0;
        padding-top: 0;
    }

    .admin-sitemap-list a {
        color: #251810;
        font-weight: 800;
        text-decoration: none;
    }

    .admin-sitemap-list a:hover {
        color: #28364B;
        text-decoration: underline;
    }

    .admin-sitemap-list small {
        color: #75675d;
        display: block;
        font-size: 12px;
        line-height: 1.35;
        margin-top: 2px;
    }

    @media (max-width: 767px) {
        .admin-sitemap-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container admin-sitemap-wrap">
    <div class="admin-sitemap-hero">
        <h1>Admin Sitemap</h1>
        <p>A lightweight jump page for staff. It avoids heavy sheet checks and dashboard reports so it opens quickly.</p>
    </div>

    <div class="admin-sitemap-grid">
        <?php foreach ($linkGroups as $groupTitle => $links): ?>
            <section class="admin-sitemap-card">
                <h2><?= cbAdminSitemapText($groupTitle) ?></h2>
                <ul class="admin-sitemap-list">
                    <?php foreach ($links as $link): ?>
                        <?php
                            $href = $link[1];
                            $isExternal = preg_match('/^https?:\/\//i', $href) === 1;
                        ?>
                        <li>
                            <a href="<?= cbAdminSitemapText($href) ?>"<?= $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= cbAdminSitemapText($link[0]) ?></a>
                            <small><?= cbAdminSitemapText($link[2]) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
