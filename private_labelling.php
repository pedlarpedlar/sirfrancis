<?php
include 'session_logins.php';
include 'header.php';
$page_url_canonical = "https://www.candybird.co.za/private_labelling";
$title_og = 'Private Labelling - CandyBird';
$page_url_og = $page_url_canonical;
$description_meta = 'Private labelling, custom snack packs, branded gifting, white-label retail supply and serious bulk packaging support from CandyBird.';
include 'page_menues.php';
?>

<title>Private Labelling - <?=$website_company_name?></title>

<style>
    .cb-service { background: #fbfaf7; color: #251d18; }
    .cb-service-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 48px 0 36px; }
    .cb-service-hero h1 { color: #251d18; font-size: clamp(2.2rem, 5vw, 4rem); line-height: 1.05; margin: 0 0 12px; }
    .cb-service-hero p { color: #5d514b; max-width: 780px; font-size: 1.08rem; line-height: 1.7; }
    .cb-service-wrap { padding: 36px 0 66px; }
    .cb-service-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin: 24px 0; }
    .cb-service-card, .cb-service-section { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; box-shadow: 0 12px 34px rgba(71,44,22,.06); }
    .cb-service-card { padding: 20px; }
    .cb-service-section { padding: clamp(20px, 4vw, 34px); margin-top: 18px; }
    .cb-service-card h2, .cb-service-section h2 { color: #251d18; font-size: 1.25rem; margin: 0 0 10px; }
    .cb-service-card p, .cb-service-section p, .cb-service-section li { color: #5d514b; line-height: 1.75; }
    .cb-service-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .cb-service-actions a { border-radius: 6px; padding: 12px 16px; font-weight: 700; }
    .cb-service-actions .primary { background: #2a1b1b; color: #fff; }
    .cb-service-actions .secondary { background: #FCB42F; color: #251d18; }
    .cb-service-note { background: #2a1b1b; color: #fff; border-radius: 8px; padding: 22px; margin: 22px 0; }
    .cb-service-note h2 { color: #fff; margin: 0 0 8px; }
    .cb-service-note p { color: #f7e9db; margin: 0; line-height: 1.75; }
    .cb-service-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .cb-service-table th, .cb-service-table td { border-bottom: 1px solid #eee1d4; padding: 12px 10px; vertical-align: top; color: #5d514b; }
    .cb-service-table th { color: #251d18; background: #fbfaf7; }
    .cb-service-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
    .cb-service-kicker { color: #c96f38; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; font-size: .78rem; margin-bottom: 6px; }
    @media (max-width: 767px) { .cb-service-columns { grid-template-columns: 1fr; } .cb-service-table { display: block; overflow-x: auto; } }
    @media (max-width: 767px) { .cb-service-grid { grid-template-columns: 1fr; } .cb-service-hero { padding: 38px 0 30px; } }
</style>

<main class="cb-service">
    <section class="cb-service-hero">
        <div class="container">
            <h1>Private Labelling</h1>
            <p>Build a branded snack, gifting or retail range with CandyBird support. We help serious buyers understand the right route: simple branded labels for smaller runs, or full custom packaging when the volume is large enough to justify it.</p>
            <div class="cb-service-actions">
                <a class="primary" href="contact">Start a private-label enquiry</a>
                <a class="secondary" href="wholesale">View wholesale</a>
            </div>
        </div>
    </section>

    <section class="cb-service-wrap">
        <div class="container">
            <div class="cb-service-grid">
                <div class="cb-service-card">
                    <h2>Retail packs</h2>
                    <p>Nuts, dried fruit, mixes and sweets packed into agreed sizes for shops, resellers and brand owners.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Branding routes</h2>
                    <p>Choose between labelled CandyBird-packed goods, branded finishing, or full custom printed packaging for major volumes.</p>
                </div>
                <div class="cb-service-card">
                    <h2>Repeat supply</h2>
                    <p>Plan the range, stock cycle, freshness, lead time, dispatch method and reorder rhythm before committing.</p>
                </div>
            </div>

            <div class="cb-service-note">
                <h2>Important volume guidance</h2>
                <p>Full custom branded packaging usually only becomes practical at very large volumes. As a working guide, buyers should expect custom printed packaging projects to exceed roughly 5 tons to 10 tons, depending on the product, packaging material, print method, factory minimums and whether the range has multiple SKUs. Smaller runs are usually better handled with branded labels, sleeves, inserts, gift boxes or branded finishing.</p>
            </div>

            <div class="cb-service-section">
                <div class="cb-service-kicker">Which route fits?</div>
                <h2>Private labelling options</h2>
                <table class="cb-service-table">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Best for</th>
                            <th>Typical considerations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Branded label on stocked products</td>
                            <td>Testing a market, corporate gifting, limited campaigns and small retail launches.</td>
                            <td>Fastest route. Works well when you can use existing product sizes, standard bags, jars or boxes.</td>
                        </tr>
                        <tr>
                            <td>Custom pack sizes or blends</td>
                            <td>Brands needing a specific gram size, mix ratio, ingredient preference or shelf-ready range.</td>
                            <td>Requires product testing, costing, stock planning and lead time. Minimums depend on ingredients.</td>
                        </tr>
                        <tr>
                            <td>Fully printed branded packaging</td>
                            <td>Established brands, chain retail, export buyers or buyers with forecasted recurring volumes.</td>
                            <td>Usually needs high volumes, often around 5 to 10 tons or more, because packaging manufacturers have their own minimum runs.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="cb-service-columns">
                <div class="cb-service-section">
                    <h2>What serious buyers should prepare</h2>
                    <ul>
                        <li>Product list or target category, for example cashews, almonds, dried fruit or trail mixes</li>
                        <li>Pack size per SKU, such as 50g, 100g, 250g, 500g or 1kg</li>
                        <li>Estimated launch order quantity and expected monthly reorder quantity</li>
                        <li>Packaging preference: pouch, jar, box, tub, sachet or gift packaging</li>
                        <li>Whether your brand needs barcode, batch, allergen, ingredient and nutritional label support</li>
                        <li>Delivery location, required launch date and whether this is once-off or recurring</li>
                    </ul>
                </div>

                <div class="cb-service-section">
                    <h2>Commercial details to expect</h2>
                    <ul>
                        <li>Pricing is quote-based because raw material prices, packaging and freight move often.</li>
                        <li>Lead times depend on stock availability, packaging route, artwork approval and packing capacity.</li>
                        <li>Custom products may require deposits before materials or packaging are reserved.</li>
                        <li>Perishable and food products need careful stock rotation, storage and realistic sell-through planning.</li>
                        <li>Full custom packaging usually needs artwork approval, print proofs and longer lead times.</li>
                    </ul>
                </div>
            </div>

            <div class="cb-service-section">
                <h2>Good fits for private labelling</h2>
                <ul>
                    <li>Retail-ready snack ranges for shops, farms stalls, health stores and online brands</li>
                    <li>Corporate snack packs, launch gifts and recurring staff pantry packs</li>
                    <li>Hospitality, guesthouse and lodge welcome packs</li>
                    <li>Wedding, event and seasonal favour packs</li>
                    <li>Health, pantry and gifting brands needing a reliable supply partner</li>
                </ul>
                <p>To move quickly, send your product list, target pack sizes, expected quantity, branding route and deadline. If the quantity is not yet large enough for full printed packaging, we can still suggest a practical branded-label route.</p>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
