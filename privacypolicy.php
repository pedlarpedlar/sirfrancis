<?php
include 'session_logins.php';
include 'header.php';
?>
<title>Privacy Policy - <?=$website_company_name?></title>
<?php include 'page_menues.php'; ?>

<style>
    .cb-policy-shell { background: #fbfaf7; color: #251d18; }
    .cb-policy-hero { background: #fff7ed; color: #251d18; padding: 46px 0 36px; border-bottom: 1px solid #eadfd2; }
    .cb-policy-hero h1 { color: #251d18; font-size: clamp(2.1rem, 5vw, 4.2rem); line-height: 1.05; margin: 0 0 14px; }
    .cb-policy-hero p { color: #5d514b; max-width: 760px; font-size: 1.08rem; }
    .cb-policy-updated { display: inline-flex; background: #fff; border: 1px solid #eadfd2; color: #6b4b36; border-radius: 999px; padding: 8px 13px; margin-bottom: 18px; font-size: .9rem; }
    .cb-policy-wrap { padding: 34px 0 64px; }
    .cb-policy-grid { display: grid; grid-template-columns: minmax(0, 260px) minmax(0, 1fr); gap: 24px; align-items: start; }
    .cb-policy-nav, .cb-policy-card { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; box-shadow: 0 16px 40px rgba(71,44,22,.07); }
    .cb-policy-nav { padding: 16px; position: sticky; top: 96px; }
    .cb-policy-nav a { display: block; padding: 10px 12px; color: #46332a; border-radius: 6px; font-weight: 700; }
    .cb-policy-nav a:hover { background: #fff4e8; color: #9f4e22; }
    .cb-policy-card { padding: clamp(20px, 4vw, 38px); }
    .cb-policy-section { padding: 8px 0 24px; border-bottom: 1px solid #f0e5da; }
    .cb-policy-section:last-child { border-bottom: 0; padding-bottom: 0; }
    .cb-policy-section h2 { color: #271817; font-size: 1.45rem; margin: 0 0 12px; }
    .cb-policy-section p, .cb-policy-section li { color: #5d514b; line-height: 1.75; }
    .cb-policy-section ul { padding-left: 20px; }
    .cb-policy-callout { background: #eef8f1; border: 1px solid #cce7d4; border-radius: 8px; padding: 16px 18px; margin: 18px 0; color: #284432; }
    .cb-policy-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .cb-policy-links a { border-radius: 6px; padding: 11px 15px; background: #2a1b1b; color: #fff; font-weight: 700; }
    @media (max-width: 767px) {
        .cb-policy-hero { padding: 40px 0 34px; }
        .cb-policy-grid { grid-template-columns: 1fr; }
        .cb-policy-nav { position: static; }
    }
</style>

<main class="cb-policy-shell">
    <section class="cb-policy-hero">
        <div class="container">
            <span class="cb-policy-updated">Last updated: 19 April 2026</span>
            <h1>Privacy Policy</h1>
            <p>How CandyBird collects, uses and protects customer information when you browse, subscribe, create an account, checkout or contact us.</p>
        </div>
    </section>

    <section class="cb-policy-wrap">
        <div class="container cb-policy-grid">
            <nav class="cb-policy-nav" aria-label="Privacy sections">
                <a href="#what">What we collect</a>
                <a href="#why">How we use it</a>
                <a href="#payments">Payments</a>
                <a href="#delivery">Delivery partners</a>
                <a href="#marketing">Marketing</a>
                <a href="#analytics">Website analytics</a>
                <a href="#cctv">CCTV</a>
                <a href="#rights">Your choices</a>
                <a href="#contact">Contact</a>
            </nav>

            <article class="cb-policy-card">
                <section class="cb-policy-section" id="what">
                    <h2>1. Information we collect</h2>
                    <p>We collect information needed to run the online store and support customers. This may include your name, email address, phone number, billing address, delivery address, order details, payment status, coupon usage, account details, review activity, subscription preferences and customer support messages.</p>
                </section>

                <section class="cb-policy-section" id="why">
                    <h2>2. How we use your information</h2>
                    <ul>
                        <li>To process orders, payments, refunds, delivery and customer support.</li>
                        <li>To show order history, cart, wishlist, reviews and account details.</li>
                        <li>To send order emails, payment links, delivery updates and important service notices.</li>
                        <li>To apply coupons correctly and prevent coupon abuse.</li>
                        <li>To improve the website, product range and customer experience.</li>
                    </ul>
                </section>

                <section class="cb-policy-section" id="payments">
                    <h2>3. Payments</h2>
                    <p>Card and online payments may be handled by PayFast or other payment providers shown at checkout. CandyBird records order and payment status, but sensitive card details are processed by the payment provider and are not stored by CandyBird.</p>
                </section>

                <section class="cb-policy-section" id="delivery">
                    <h2>4. Delivery and address information</h2>
                    <p>We use delivery details to calculate shipping, pack orders, arrange courier or locker delivery, and contact you if delivery needs clarification. We may share only the necessary delivery information with courier, locker, fulfilment or support partners.</p>
                </section>

                <section class="cb-policy-section" id="marketing">
                    <h2>5. Email marketing and coupons</h2>
                    <p>If you subscribe, we may send product updates, coupon codes, special offers and seasonal campaigns. You can unsubscribe using the unsubscribe link in emails or by contacting us.</p>
                    <div class="cb-policy-callout">Subscriber-only coupons may be checked against the subscribed email address to make sure the offer is used fairly.</div>
                </section>

                <section class="cb-policy-section" id="analytics">
                    <h2>6. Website analytics and cookies</h2>
                    <p>We use cookies and website activity information to keep carts working, remember sessions, understand which pages and products are useful, improve search, identify abandoned carts and reduce bot noise. Some parts of the website may not work correctly if cookies are disabled.</p>
                </section>

                <section class="cb-policy-section" id="cctv">
                    <h2>7. CCTV at stores and collection points</h2>
                    <p>CandyBird may use CCTV at stores, collection points, packing areas and customer-facing premises for safety, security, parcel protection and incident review. CCTV footage may include a person's image, movement, vehicle or collection activity. For more detail, please read our <a href="cctv-policy">CCTV Policy</a>.</p>
                </section>

                <section class="cb-policy-section" id="rights">
                    <h2>8. Your choices</h2>
                    <p>You may ask us to update, correct or review personal information linked to your customer account or order. We may need to keep certain order, tax, accounting, fraud-prevention or legal records where required for normal business operations.</p>
                </section>

                <section class="cb-policy-section" id="contact">
                    <h2>9. Contact us</h2>
                    <p>For privacy questions, account questions or data requests, contact us at <a href="mailto:<?=$support_email?>"><?=$support_email?></a>.</p>
                    <div class="cb-policy-links">
                        <a href="terms">Terms</a>
                        <a href="delivery_policy">Delivery Policy</a>
                        <a href="return_policy">Buyer Protection</a>
                    </div>
                </section>
            </article>
        </div>
    </section>
</main>

<?php include "footer.php"; ?>
