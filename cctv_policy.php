<?php
include 'session_logins.php';
$page_url_canonical = 'https://www.fishgelatine.co.za/v2/cctv-policy';
$title_og = 'CCTV Policy | Sir Francis';
$page_url_og = $page_url_canonical;
$description_meta = 'Sir Francis CCTV policy for stores, collection points, packing areas and customer-facing premises.';
$description_og = $description_meta;
$image_url_og = 'https://www.fishgelatine.co.za/v2/assets/img/pricelist.jpg';
$image_type_og = 'image/jpeg';
include 'header.php';
?>
<title>CCTV Policy - <?=$website_company_name?></title>
<?php include 'page_menues.php'; ?>

<style>
    .cb-cctv-shell { background: #fbfaf7; color: #251d18; }
    .cb-cctv-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 46px 0 36px; }
    .cb-cctv-updated { background: #fff; border: 1px solid #eadfd2; border-radius: 999px; color: #6b4b36; display: inline-flex; font-size: .9rem; margin-bottom: 18px; padding: 8px 13px; }
    .cb-cctv-hero h1 { color: #251d18; font-size: clamp(2.1rem, 5vw, 4.2rem); line-height: 1.05; margin: 0 0 14px; }
    .cb-cctv-hero p { color: #5d514b; font-size: 1.08rem; line-height: 1.75; max-width: 820px; }
    .cb-cctv-wrap { padding: 34px 0 64px; }
    .cb-cctv-grid { align-items: start; display: grid; gap: 24px; grid-template-columns: minmax(0, 270px) minmax(0, 1fr); }
    .cb-cctv-nav, .cb-cctv-card { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; box-shadow: 0 16px 40px rgba(71,44,22,.07); }
    .cb-cctv-nav { padding: 16px; position: sticky; top: 96px; }
    .cb-cctv-nav a { border-radius: 6px; color: #46332a; display: block; font-weight: 700; padding: 10px 12px; }
    .cb-cctv-nav a:hover { background: #fff4e8; color: #9f4e22; }
    .cb-cctv-card { padding: clamp(20px, 4vw, 38px); }
    .cb-cctv-section { border-bottom: 1px solid #f0e5da; padding: 8px 0 24px; }
    .cb-cctv-section:last-child { border-bottom: 0; padding-bottom: 0; }
    .cb-cctv-section h2 { color: #271817; font-size: 1.45rem; margin: 0 0 12px; }
    .cb-cctv-section p, .cb-cctv-section li { color: #5d514b; line-height: 1.75; }
    .cb-cctv-section ul { padding-left: 20px; }
    .cb-cctv-callout { background: #eef8f1; border: 1px solid #cce7d4; border-radius: 8px; color: #284432; margin: 18px 0; padding: 16px 18px; }
    .cb-cctv-warning { background: #fff4e8; border: 1px solid #ecd0ad; border-radius: 8px; color: #5d351e; margin: 18px 0; padding: 16px 18px; }
    .cb-cctv-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .cb-cctv-links a { background: #2a1b1b; border-radius: 6px; color: #fff; font-weight: 700; padding: 11px 15px; }
    @media (max-width: 767px) {
        .cb-cctv-hero { padding: 40px 0 34px; }
        .cb-cctv-grid { grid-template-columns: 1fr; }
        .cb-cctv-nav { position: static; }
    }
</style>

<main class="cb-cctv-shell">
    <section class="cb-cctv-hero">
        <div class="container">
            <span class="cb-cctv-updated">Last updated: 11 May 2026</span>
            <h1>CCTV Policy</h1>
            <p>This policy explains how Sir Francis uses closed-circuit television and security camera footage at our stores, collection points, packing areas and customer-facing premises. CCTV is used for safety, security, order protection and incident review.</p>
        </div>
    </section>

    <section class="cb-cctv-wrap">
        <div class="container cb-cctv-grid">
            <nav class="cb-cctv-nav" aria-label="CCTV policy sections">
                <a href="#where">Where cameras may be used</a>
                <a href="#why">Why footage is recorded</a>
                <a href="#what">What may be captured</a>
                <a href="#access">Who may access footage</a>
                <a href="#retention">How long footage is kept</a>
                <a href="#requests">Customer requests</a>
                <a href="#contact">Contact</a>
            </nav>

            <article class="cb-cctv-card">
                <section class="cb-cctv-section" id="where">
                    <h2>1. Where cameras may be used</h2>
                    <p>CCTV may be used at Sir Francis stores, collection counters, packing areas, dispatch points, receiving areas, entrances, exits, parking or loading areas, and other places where products, customers, staff, visitors, vehicles or parcels need reasonable protection.</p>
                    <div class="cb-cctv-callout">Where practical, CCTV notices or signs will be displayed so customers, visitors, staff and service providers know that recording may take place.</div>
                </section>

                <section class="cb-cctv-section" id="why">
                    <h2>2. Why we use CCTV</h2>
                    <ul>
                        <li>To help protect customers, staff, visitors, products, parcels, equipment and premises.</li>
                        <li>To deter theft, fraud, vandalism, unauthorised access or unsafe behaviour.</li>
                        <li>To investigate incidents, disputes, delivery or collection queries, damaged parcels, missing items or security concerns.</li>
                        <li>To support health and safety, operational control and lawful business interests.</li>
                        <li>To provide footage where required by law, court order, insurer, regulator or law-enforcement request.</li>
                    </ul>
                </section>

                <section class="cb-cctv-section" id="what">
                    <h2>3. What may be captured</h2>
                    <p>CCTV footage may show a person's image, movement, clothing, vehicle, collection activity, parcel handover, order-related interaction or other activity visible to the camera. Unless a specific notice states otherwise, Sir Francis CCTV is intended to record video footage and not private conversations.</p>
                    <div class="cb-cctv-warning">Cameras are positioned for legitimate security and operational purposes. They should not be used in private areas where people reasonably expect privacy.</div>
                </section>

                <section class="cb-cctv-section" id="access">
                    <h2>4. Who may access footage</h2>
                    <p>Access to CCTV footage is limited to authorised Sir Francis management, appointed security or technical service providers, and other authorised persons who need access for a valid business, safety, legal, insurance or security reason. Footage is not used for entertainment, public posting or unnecessary monitoring.</p>
                </section>

                <section class="cb-cctv-section" id="retention">
                    <h2>5. How long footage is kept</h2>
                    <p>CCTV footage is normally kept for up to 30 days, depending on camera system capacity and operational needs. Footage may be kept for longer where it is needed for an incident, investigation, order dispute, insurance claim, legal request, disciplinary process, safety concern or law-enforcement matter.</p>
                </section>

                <section class="cb-cctv-section" id="requests">
                    <h2>6. Customer and visitor requests</h2>
                    <p>You may ask whether footage relating to you is available, or ask us to review footage connected to a specific collection, delivery, payment, parcel or safety incident. To protect other people, footage may be refused, blurred, limited, summarised or released only where lawful and practical.</p>
                    <p>Requests should include the date, approximate time, location, order number if relevant, and a clear reason for the request. We may need to verify your identity before reviewing or sharing information.</p>
                </section>

                <section class="cb-cctv-section" id="contact">
                    <h2>7. Contact</h2>
                    <p>For CCTV, privacy or access requests, contact us at <a href="mailto:<?=$support_email?>"><?=$support_email?></a>. For complaints or compliments, you may also use <a href="mailto:info@fishgelatine.co.za">info@fishgelatine.co.za</a>.</p>
                    <div class="cb-cctv-links">
                        <a href="privacypolicy">Privacy Policy</a>
                        <a href="terms">Terms</a>
                        <a href="contact">Contact Us</a>
                    </div>
                </section>
            </article>
        </div>
    </section>
</main>

<?php include "footer.php"; ?>
