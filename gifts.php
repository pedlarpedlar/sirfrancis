<?php
include 'session_logins.php';
include 'header.php';
$page_url_canonical = "https://www.candybird.co.za/gifting";
$title_og = 'Gifting - CandyBird';
$page_url_og = $page_url_canonical;
$description_meta = 'Thoughtful CandyBird gifting for Eid, Ramadan, corporate events, weddings, staff gifts and custom occasions.';
include 'page_menues.php';
?>

<title>Gifting - <?=$website_company_name?></title>

<style>
    .cb-service { background: #fbfaf7; color: #251d18; }
    .cb-service-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 48px 0 36px; }
    .cb-service-hero h1 { color: #251d18; font-size: clamp(2.2rem, 5vw, 4rem); line-height: 1.05; margin: 0 0 12px; }
    .cb-service-hero p { color: #5d514b; max-width: 760px; font-size: 1.08rem; line-height: 1.7; }
    .cb-service-wrap { padding: 36px 0 66px; }
    .cb-service-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin: 24px 0; }
    .cb-service-card { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; padding: 20px; box-shadow: 0 12px 34px rgba(71,44,22,.06); }
    .cb-service-card h2, .cb-service-section h2 { color: #251d18; font-size: 1.25rem; margin: 0 0 10px; }
    .cb-service-card p, .cb-service-section p, .cb-service-section li { color: #5d514b; line-height: 1.75; }
    .cb-service-section { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; padding: clamp(20px, 4vw, 34px); margin-top: 18px; }
    .cb-service-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .cb-service-actions a { border-radius: 6px; padding: 12px 16px; font-weight: 700; }
    .cb-service-actions .primary { background: #2a1b1b; color: #fff; }
    .cb-service-actions .secondary { background: #FCB42F; color: #251d18; }
    @media (max-width: 767px) { .cb-service-grid { grid-template-columns: 1fr; } .cb-service-hero { padding: 38px 0 30px; } }
</style>

<main class="cb-service">
    <section class="cb-service-hero">
        <div class="container">
            <h1>Gifting</h1>
            <p>Beautiful edible gifts for people who appreciate freshness, quality and a little delight. We can help with once-off gifts, family occasions, Eid and Ramadan gifting, staff appreciation, wedding favours and corporate boxes.</p>
            <div class="cb-service-actions">
                <a class="primary" href="gifting">Shop gifts</a>
                <a class="secondary" href="contact">Plan a custom gift</a>
            </div>
        </div>
    </section>

    <section class="cb-service-wrap">
        <div class="container">
            <div class="cb-service-grid">
                <div class="cb-service-card">
                    <h2>Ready-to-shop treats</h2>
                    <p>Choose from nuts, dried fruit, health mixes, sweet treats and seasonal favourites.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Custom occasions</h2>
                    <p>We can help shape gift boxes for weddings, events, family gifting, staff packs and client drops.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Fresh and thoughtful</h2>
                    <p>Food gifts are practical, generous and easy to share, especially when packed close to dispatch.</p>
                </div>
            </div>

            <div class="cb-service-section">
                <h2>What we can customise</h2>
                <p>Depending on quantities and lead time, CandyBird can assist with product selection, size mix, labels, ribbons, branded inserts, gift boxes and curated bundles. For larger campaigns, see our private labelling and wholesale pages.</p>
                <ul>
                    <li>Corporate gifting and staff appreciation</li>
                    <li>Eid, Ramadan and festive gifting</li>
                    <li>Wedding favours and event packs</li>
                    <li>Thank-you gifts, hampers and snack boxes</li>
                </ul>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
