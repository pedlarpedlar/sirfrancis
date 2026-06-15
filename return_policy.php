<?php
include 'session_logins.php';
include 'header.php';
?>
<title>Buyer Protection and Return Policy - <?=$website_company_name?></title>
<?php include 'page_menues.php'; ?>

<style>
    .cb-policy-shell { background: #fbfaf7; color: #251d18; }
    .cb-policy-hero { background: #fff7ed; color: #251d18; padding: 46px 0 36px; border-bottom: 1px solid #eadfd2; }
    .cb-policy-hero h1 { color: #251d18; font-size: clamp(2.1rem, 5vw, 4.2rem); line-height: 1.05; margin: 0 0 14px; }
    .cb-policy-hero p { color: #5d514b; max-width: 780px; font-size: 1.08rem; }
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
    .cb-policy-section ul, .cb-policy-section ol { padding-left: 20px; }
    .cb-policy-callout { background: #fff7ed; border: 1px solid #efd7bd; border-radius: 8px; padding: 16px 18px; margin: 18px 0; color: #4b3426; }
    .cb-policy-badges { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 20px 0; }
    .cb-policy-badge { background: #fff7ed; border: 1px solid #efd7bd; border-radius: 8px; padding: 14px; }
    .cb-policy-badge strong { display: block; color: #2a1b1b; margin-bottom: 4px; }
    .cb-iqaalah-feature { display: grid; grid-template-columns: minmax(170px, 240px) minmax(0, 1fr); gap: 20px; align-items: center; background: #fffaf4; border: 1px solid #efd7bd; border-radius: 8px; padding: 18px; margin: 18px 0 22px; }
    .cb-iqaalah-feature img { width: 100%; max-width: 230px; margin: 0 auto; display: block; }
    .cb-iqaalah-feature p { margin: 0; }
    .cb-policy-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .cb-policy-links a { border-radius: 6px; padding: 11px 15px; background: #2a1b1b; color: #fff; font-weight: 700; }
    @media (max-width: 767px) {
        .cb-policy-hero { padding: 40px 0 34px; }
        .cb-policy-grid, .cb-policy-badges, .cb-iqaalah-feature { grid-template-columns: 1fr; }
        .cb-policy-nav { position: static; }
    }
</style>

<main class="cb-policy-shell">
    <section class="cb-policy-hero">
        <div class="container">
            <span class="cb-policy-updated">Last updated: 19 April 2026</span>
            <h1>Buyer Protection and Returns</h1>
            <p>We want customers to feel comfortable ordering from Sir Francis. If something arrives wrong, damaged, short, stale, unsuitable or not as described, we want to know and we want to fix it fairly.</p>
        </div>
    </section>

    <section class="cb-policy-wrap">
        <div class="container cb-policy-grid">
            <nav class="cb-policy-nav" aria-label="Return policy sections">
                <a href="#promise">Our promise</a>
                <a href="#eligible">Eligibility</a>
                <a href="#freshness">Freshness care</a>
                <a href="#start">Start a return</a>
                <a href="#refunds">Refunds</a>
                <a href="#shipping">Return shipping</a>
                <a href="#contact">Contact</a>
            </nav>

            <article class="cb-policy-card">
                <section class="cb-policy-section" id="promise">
                    <h2>1. Our buyer-protection promise</h2>
                    <div class="cb-iqaalah-feature">
                        <img src="assets/img/iqaalah.png" alt="Iqaalah buyer protection">
                        <p>Iqaalah sits at the heart of this page: a customer should feel safe enough to tell us when a purchase did not sit right, and we should be willing to reverse or resolve it fairly where possible.</p>
                    </div>
                    <p>Returns and feedback are welcome. We would rather know about a problem than leave a customer unhappy. Sir Francis supports a fair, generous approach inspired by Iqaalah, an Arabic term meaning the willing reversal or cancellation of a sale. In practice, we treat it as buyer protection: if a customer is genuinely unsatisfied, regrets the purchase, or received something that did not meet what was promised, we aim to resolve it through refund, replacement, credit or another fair outcome.</p>
                    <div class="cb-policy-callout">
                        Iqaalah is a recognised principle in Islamic commercial ethics: where a concluded sale is reversed by agreement, the buyer returns the goods and the seller returns the price. A hadith, meaning a recorded narration, reports that Nabi Muhammad, the Messenger, peace and blessings be upon him, encouraged accepting the cancellation of a sale for a regretful buyer. Sir Francis applies this as a serious buyer-protection standard, subject to product condition, food safety, lawful consumer rights, and the practical limits set out in this policy.
                    </div>
                    <p>For our customers, this simply means that our returns approach is not limited to technical defects only. Where a fair reversal is possible, we will consider the customer's dissatisfaction, the condition of the goods, the time since delivery, hygiene and perishability, and any applicable South African consumer-law protections.</p>
                    <div class="cb-policy-badges">
                        <div class="cb-policy-badge"><strong>Wrong item?</strong><span>Tell us and we will correct it.</span></div>
                        <div class="cb-policy-badge"><strong>Damaged parcel?</strong><span>Send photos quickly so we can help.</span></div>
                        <div class="cb-policy-badge"><strong>Quality concern?</strong><span>Contact us while the product can still be assessed.</span></div>
                    </div>
                </section>

                <section class="cb-policy-section" id="eligible">
                    <h2>2. What can be returned</h2>
                    <p>Please contact us within <?=$return_window?> of delivery or purchase. Because many Sir Francis products are food items, we may not be able to accept quality-related returns where the product was opened long ago, stored incorrectly, exposed to heat, or kept beyond a reasonable freshness period.</p>
                    <ul>
                        <li>Items must preferably be in original packaging where practical.</li>
                        <li>Damaged, incorrect, missing or defective items should be reported as soon as possible.</li>
                        <li>For missing or short items, a refund or partial refund is usually more practical than replacement.</li>
                        <li>Digital items such as vouchers or e-books may not be returnable once delivered or redeemed, unless required by law or there is a clear issue.</li>
                    </ul>
                </section>

                <section class="cb-policy-section" id="freshness">
                    <h2>3. Freshness and storage care</h2>
                    <p>Nuts, dried fruit and related products should be treated like fresh pantry goods. Heat, light, air and humidity can affect taste and texture. After opening, we recommend consuming within 30 days or storing in the fridge or freezer for longer freshness.</p>
                    <div class="cb-policy-callout">Freshness claims may be declined if products were stored in hot conditions, left open, kept too long after opening, or handled in a way that reasonably affects product quality.</div>
                </section>

                <section class="cb-policy-section" id="start">
                    <h2>4. How to start a return or claim</h2>
                    <ol>
                        <li>Email <a href="mailto:<?=$support_email?>"><?=$support_email?></a> with your order number.</li>
                        <li>Explain what happened and include photos where useful.</li>
                        <li>Wait for our reply before sending anything back, so we can give the correct instructions.</li>
                    </ol>
                </section>

                <section class="cb-policy-section" id="refunds">
                    <h2>5. Refunds, replacements and partial cancellations</h2>
                    <p>Depending on the situation, we may offer a refund, replacement, store credit, partial cancellation or order adjustment. If PayFast was used and a refund is required, the refund process may depend on the PayFast payment record and available refund method.</p>
                    <p>Nothing in this policy is intended to limit any consumer rights that apply under South African law.</p>
                </section>

                <section class="cb-policy-section" id="shipping">
                    <h2>6. Return shipping costs</h2>
                    <p>If the return is due to an error, damage, wrong item or confirmed defect, Sir Francis will help with reasonable return or replacement shipping. If the return is voluntary, such as a change of mind, the customer may be responsible for return shipping.</p>
                    <p>For international returns, mark the parcel clearly as returned goods. Customs duties, taxes or import charges are generally the customer's responsibility unless otherwise agreed.</p>
                </section>

                <section class="cb-policy-section" id="contact">
                    <h2>7. Contact</h2>
                    <p>Email: <a href="mailto:<?=$support_email?>"><?=$support_email?></a><br>Phone: <?=$tel?></p>
                    <div class="cb-policy-links">
                        <a href="delivery_policy">Delivery Policy</a>
                        <a href="terms">Terms</a>
                        <a href="privacypolicy">Privacy Policy</a>
                    </div>
                </section>
            </article>
        </div>
    </section>
</main>

<?php include "footer.php"; ?>
