<?php
include 'session_logins.php';
include 'header.php';
$page_url_canonical = "https://www.fishgelatine.co.za/v2/bulk_ordering";
$title_og = 'Bulk Ordering - Sir Francis';
$page_url_og = $page_url_canonical;
$description_meta = 'Bulk ordering for Sir Francis marine collagen, fish gelatine, peptides, sea moss, resellers and business customers.';
include 'page_menues.php';
?>

<title>Bulk Ordering - <?=$website_company_name?></title>

<style>
    .cb-service { background: #fbfaf7; color: #251d18; }
    .cb-service-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 48px 0 36px; }
    .cb-service-hero h1 { color: #251d18; font-size: clamp(2.2rem, 5vw, 4rem); line-height: 1.05; margin: 0 0 12px; }
    .cb-service-hero p { color: #5d514b; max-width: 780px; font-size: 1.08rem; line-height: 1.7; }
    .cb-service-wrap { padding: 36px 0 66px; }
    .cb-service-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin: 24px 0; }
    .cb-service-card, .cb-service-section { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; box-shadow: 0 12px 34px rgba(71,44,22,.06); }
    .cb-service-card { padding: 18px; }
    .cb-service-section { padding: clamp(20px, 4vw, 34px); margin-top: 18px; }
    .cb-service-card h2, .cb-service-section h2 { color: #251d18; font-size: 1.15rem; margin: 0 0 10px; }
    .cb-service-card p, .cb-service-section p, .cb-service-section li { color: #5d514b; line-height: 1.75; }
    .cb-service-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .cb-service-actions a { border-radius: 6px; padding: 12px 16px; font-weight: 700; }
    .cb-service-actions .primary { background: #2a1b1b; color: #fff; }
    .cb-service-actions .secondary { background: #CEBD88; color: #251d18; }
    @media (max-width: 991px) { .cb-service-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575px) { .cb-service-grid { grid-template-columns: 1fr; } .cb-service-hero { padding: 38px 0 30px; } }
</style>

<main class="cb-service">
    <section class="cb-service-hero">
        <div class="container">
            <h1>Bulk Ordering</h1>
            <p>Ordering for a clinic, reseller, wellness brand, manufacturer, retailer or distribution project? Sir Francis can help you plan bigger orders with sensible product choices and delivery expectations.</p>
            <div class="cb-service-actions">
                <a class="primary" href="contact">Request help with a bulk order</a>
                <a class="secondary" href="pricelist">View pricelist</a>
            </div>
        </div>
    </section>

    <section class="cb-service-wrap">
        <div class="container">
            <div class="cb-service-grid">
                <div class="cb-service-card">
                    <h2>Events</h2>
                    <p>Retail packs, repeat-use orders and specialist wellness ingredient supply.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Workplaces</h2>
                    <p>Bulk marine collagen, fish gelatine and sea moss support for business buyers.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Resellers</h2>
                    <p>Bulk supply for shops, distributors, wellness brands and private-label projects.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Pantry orders</h2>
                    <p>Large family orders where freshness, storage and pack sizes matter.</p>
                </div>
            </div>

            <div class="cb-service-section">
                <h2>What to include in your enquiry</h2>
                <ul>
                    <li>Products or categories you are interested in</li>
                    <li>Approximate quantity or budget</li>
                    <li>Preferred pack size, if known</li>
                    <li>Delivery area and date needed</li>
                    <li>Whether branding, labels or private-label packaging is required</li>
                </ul>
                <p>For very large orders, shipping may need a custom quote. Lead times may apply where stock needs to be prepared, sourced or packed specially.</p>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
