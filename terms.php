<?php
include 'session_logins.php';
include 'header.php';
?>
<title>Terms and Conditions - <?=$website_company_name?></title>
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
    .cb-policy-callout { background: #fff7ed; border: 1px solid #efd7bd; border-radius: 8px; padding: 16px 18px; margin: 18px 0; color: #4b3426; }
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
            <h1>Terms and Conditions</h1>
            <p>Clear shopping terms for ordering from <?=$website_company_name?>. These terms explain how orders, payments, stock, delivery, coupons, accounts and support are handled.</p>
        </div>
    </section>

    <section class="cb-policy-wrap">
        <div class="container cb-policy-grid">
            <nav class="cb-policy-nav" aria-label="Policy sections">
                <a href="#ordering">Ordering</a>
                <a href="#products">Products and stock</a>
                <a href="#payments">Payments</a>
                <a href="#delivery">Delivery</a>
                <a href="#coupons">Coupons</a>
                <a href="#accounts">Accounts</a>
                <a href="#returns">Returns</a>
                <a href="#contact">Contact</a>
            </nav>

            <article class="cb-policy-card">
                <section class="cb-policy-section" id="ordering">
                    <h2>1. Ordering from Sir Francis</h2>
                    <p>By placing an order on this website, you confirm that the order details, contact details, delivery details and product quantities are correct. You may checkout as a guest or by logging into your customer account.</p>
                    <p>After checkout, an order record is created so you can view the order breakdown and payment status. If payment is not completed immediately, the order may remain unpaid until PayFast or EFT payment is completed or manually confirmed.</p>
                </section>

                <section class="cb-policy-section" id="products">
                    <h2>2. Products, pricing and stock</h2>
                    <p>We work hard to keep product details, prices, sizes, descriptions, stock and images accurate. Because many products are food items, availability can change quickly due to stock movement, freshness, packaging, supplier delays or seasonal demand.</p>
                    <ul>
                        <li>Prices may change without prior notice, especially after new stock refills.</li>
                        <li>Special prices apply only during the shown validity period.</li>
                        <li>If an item has a lead time, it may need preparation or sourcing before dispatch.</li>
                        <li>If an item becomes unavailable after checkout, we will contact you with options such as refund, replacement, partial cancellation or waiting for stock.</li>
                    </ul>
                </section>

                <section class="cb-policy-section" id="payments">
                    <h2>3. Payments</h2>
                    <p>Orders can be paid using the payment methods available at checkout, including PayFast where enabled, EFT where offered, and EFT via Ozow if that option is enabled. PayFast and Ozow payments are processed securely by their payment platforms. Normal EFT payments, while still offered, are manually verified by our team before being marked as paid.</p>
                    <p>An order record may still be created even if payment is not completed immediately, so the customer can return to the order details page and complete payment or request help.</p>
                    <div class="cb-policy-callout">Please use your order number as reference when paying by normal EFT. Orders are only released for packing once payment is confirmed or otherwise approved by Sir Francis.</div>
                </section>

                <section class="cb-policy-section" id="delivery">
                    <h2>4. Delivery and fulfilment</h2>
                    <p>Delivery charges are calculated during checkout based on delivery method, order weight, destination and the current shipping rules. Free shipping, when offered, applies only to the qualifying delivery method and only when the order qualifies after discounts.</p>
                    <p>For full delivery details, courier tiers, locker delivery and out-of-South-Africa handling, please read our <a href="delivery_policy">Delivery Policy</a>.</p>
                </section>

                <section class="cb-policy-section" id="coupons">
                    <h2>5. Coupons and discounts</h2>
                    <p>Coupons may have limits such as validity dates, minimum order values, sale-item exclusions, usage counts, email restrictions or subscriber-only rules. Only one coupon can be used at a time.</p>
                    <p>Discounts are calculated before shipping. If a coupon reduces the cart below the free-shipping threshold, free shipping may no longer apply.</p>
                </section>

                <section class="cb-policy-section" id="accounts">
                    <h2>6. Customer accounts and guest checkout</h2>
                    <p>You may create an account for easier future ordering, or checkout as a guest. If a guest order uses an email address linked to an existing customer account, we may associate that order with the matching email for better order history and support.</p>
                </section>

                <section class="cb-policy-section" id="returns">
                    <h2>7. Returns, refunds and order changes</h2>
                    <p>Returns and refunds are handled according to our <a href="return_policy">Buyer Protection and Return Policy</a>. If an order needs to be cancelled, partially cancelled, refunded or adjusted due to stock, payment or delivery issues, Sir Francis will update the order record and communicate with the customer.</p>
                    <p>Nothing in these terms is intended to exclude or reduce consumer rights that apply under South African law.</p>
                </section>

                <section class="cb-policy-section" id="contact">
                    <h2>8. Contact and support</h2>
                    <p>Questions about an order, payment, product, delivery or return can be sent to <a href="mailto:<?=$support_email?>"><?=$support_email?></a>.</p>
                    <div class="cb-policy-links">
                        <a href="delivery_policy">Delivery Policy</a>
                        <a href="return_policy">Buyer Protection</a>
                        <a href="privacypolicy">Privacy Policy</a>
                    </div>
                </section>
            </article>
        </div>
    </section>
</main>

<?php include "footer.php"; ?>
