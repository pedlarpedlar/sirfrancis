<?php
include 'session_logins.php';
include 'header.php';

$page_url_canonical = "https://www.candybird.co.za/wholesale";
$title_og = 'Wholesale - CandyBird';
$page_url_og = $page_url_canonical;
$description_og = 'Wholesale nuts, dried fruit, sweets, snack packs and bulk supply from CandyBird for shops, resellers, hospitality, gifting and business buyers.';
$description_meta = $description_og;

include 'page_menues.php';
?>

<title>Wholesale - <?=$website_company_name?></title>

<style>
    .cb-service { background: #fbfaf7; color: #251d18; }
    .cb-service-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 48px 0 36px; }
    .cb-service-hero h1 { color: #251d18; font-size: clamp(2.2rem, 5vw, 4rem); line-height: 1.05; margin: 0 0 12px; }
    .cb-service-hero p { color: #5d514b; max-width: 840px; font-size: 1.08rem; line-height: 1.7; }
    .cb-service-wrap { padding: 36px 0 66px; }
    .cb-service-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin: 24px 0; }
    .cb-service-card, .cb-service-section { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; box-shadow: 0 12px 34px rgba(71,44,22,.06); }
    .cb-service-card { padding: 18px; }
    .cb-service-section { padding: clamp(20px, 4vw, 34px); margin-top: 18px; }
    .cb-service-card h2, .cb-service-section h2 { color: #251d18; font-size: 1.18rem; margin: 0 0 10px; }
    .cb-service-card p, .cb-service-section p, .cb-service-section li, .cb-service-table td { color: #5d514b; line-height: 1.75; }
    .cb-service-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .cb-service-actions a { border-radius: 6px; padding: 12px 16px; font-weight: 700; }
    .cb-service-actions .primary { background: #2a1b1b; color: #fff; }
    .cb-service-actions .secondary { background: #FCB42F; color: #251d18; }
    .cb-service-note { background: #2a1b1b; color: #fff; border-radius: 8px; padding: 22px; margin: 22px 0; }
    .cb-service-note h2 { color: #fff; margin: 0 0 8px; }
    .cb-service-note p { color: #f7e9db; margin: 0; line-height: 1.75; }
    .cb-service-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
    .cb-service-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .cb-service-table th, .cb-service-table td { border-bottom: 1px solid #eee1d4; padding: 12px 10px; vertical-align: top; }
    .cb-service-table th { color: #251d18; background: #fbfaf7; text-align: left; }
    .cb-service-kicker { color: #c96f38; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; font-size: .78rem; margin-bottom: 6px; }
    @media (max-width: 991px) { .cb-service-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 767px) { .cb-service-columns { grid-template-columns: 1fr; } .cb-service-table { display: block; overflow-x: auto; } }
    @media (max-width: 575px) { .cb-service-grid { grid-template-columns: 1fr; } .cb-service-hero { padding: 38px 0 30px; } }
</style>

<main class="cb-service">
    <section class="cb-service-hero">
        <div class="container">
            <h1>Wholesale</h1>
            <p>Wholesale at CandyBird is for buyers who need reliable supply, sensible pack sizes and clear costing for resale, hospitality, gifting, offices, schools, events and bulk pantry use. We quote based on the product, quantity, stock position, packing work and delivery requirements.</p>
            <div class="cb-service-actions">
                <a class="primary" href="contact">Request a wholesale quote</a>
                <a class="secondary" href="wholesale-pricelist">View wholesale pricelist</a>
                <a class="secondary" href="private_labelling">Private labelling</a>
            </div>
        </div>
    </section>

    <section class="cb-service-wrap">
        <div class="container">
            <div class="cb-service-grid">
                <div class="cb-service-card">
                    <h2>Resellers</h2>
                    <p>Shops, online sellers, home businesses, market traders and gifting brands needing repeat stock.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Food service</h2>
                    <p>Guesthouses, lodges, bakeries, caterers, cafes and kitchens needing larger pantry quantities.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Corporate supply</h2>
                    <p>Staff snacks, client gifts, Ramadan/Eid packs, year-end gifts and wellness campaigns.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Bulk buyers</h2>
                    <p>Families, organisations, schools, masjids and events that need better planning than a normal cart order.</p>
                </div>
            </div>

            <div class="cb-service-note">
                <h2>How wholesale pricing works</h2>
                <p>Wholesale pricing is not one fixed percentage off every item. Nuts, dried fruit, sweets, packaging and freight all behave differently. The best quote depends on the exact product, quantity, packaging size, whether we are repacking, and how urgently the order is needed.</p>
            </div>

            <div class="cb-service-section">
                <div class="cb-service-kicker">Before requesting a quote</div>
                <h2>What we need from you</h2>
                <table class="cb-service-table">
                    <thead>
                        <tr>
                            <th>Information</th>
                            <th>Why it matters</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Product list</td>
                            <td>Raw nuts, roasted nuts, flavoured nuts, dried fruit, mixes, sweets and gifting packs have different supply and packing needs.</td>
                        </tr>
                        <tr>
                            <td>Quantity per product</td>
                            <td>A 10kg order, a 100kg order and a monthly 1 ton order are costed differently. Repeating orders may receive better planning than once-off orders.</td>
                        </tr>
                        <tr>
                            <td>Pack size</td>
                            <td>Loose bulk cartons, 1kg packs, 500g packs, 100g retail packs and gift packs all require different labour and packaging.</td>
                        </tr>
                        <tr>
                            <td>Delivery area and deadline</td>
                            <td>Large orders may need courier, freight, pallet or custom transport quotes. Urgent orders can change what is practical.</td>
                        </tr>
                        <tr>
                            <td>Resale or internal use</td>
                            <td>Retail buyers may need labelling, allergens, barcodes, shelf presentation and repeat supply. Internal buyers may only need bulk cartons.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="cb-service-columns">
                <div class="cb-service-section">
                    <h2>Wholesale supply options</h2>
                    <ul>
                        <li>Bulk cartons or bags for food service, baking and pantry use</li>
                        <li>Standard CandyBird packs for resale or corporate distribution</li>
                        <li>Custom pack sizes for events, hampers or recurring business orders</li>
                        <li>Mixed product bundles for offices, gifting and retail shelves</li>
                        <li>Branded labels or private-label planning where volumes justify it</li>
                    </ul>
                </div>

                <div class="cb-service-section">
                    <h2>Practical minimums</h2>
                    <ul>
                        <li>Small wholesale enquiries can start with mixed cartons or larger pack quantities.</li>
                        <li>Custom packed retail sizes usually need higher quantities because labour and packaging costs increase.</li>
                        <li>Private-label printed packaging is a major project and usually needs very large volume, often around 5 to 10 tons or more.</li>
                        <li>Products prepared fresh, flavoured or packed specially may have longer lead times.</li>
                    </ul>
                </div>
            </div>

            <div class="cb-service-section">
                <h2>Product categories available</h2>
                <ul>
                    <li>Nuts: cashews, almonds, macadamias, pecans, walnuts, pistachios and seasonal lines</li>
                    <li>Dried fruit: raisins, dates, apricots, mango, figs and mixed fruit where available</li>
                    <li>Snack mixes: trail mixes, sweet mixes, savoury mixes and custom blends where practical</li>
                    <li>Sweets and treats: selected candy, chocolate-coated items and gifting-friendly products</li>
                    <li>Hampers and gifts: staff gifting, client gifting, Eid/Ramadan gifting and year-end ranges</li>
                </ul>
                <p>Availability can change with harvests, imports, exchange rates and stock cycles. A quote confirms current availability, pricing and lead time.</p>
            </div>

            <div class="cb-service-section">
                <h2>How to get a useful quote quickly</h2>
                <p>Send us your product list, approximate quantity, preferred pack size, delivery area, deadline and whether you need branding. If you are comparing suppliers, tell us whether your priority is lowest landed cost, premium grade, consistent monthly supply, retail presentation or speed.</p>
                <div class="cb-service-actions">
                    <a class="primary" href="contact">Send wholesale details</a>
                    <a class="secondary" href="products">Browse products</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
