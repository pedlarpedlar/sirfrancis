<?php
include 'session_logins.php';
require_once __DIR__ . '/product_sheet_helpers.php';
include 'header.php';

$deliveryOptions = function_exists('getCandybirdDeliveryOptions') ? getCandybirdDeliveryOptions() : [];
$lockerTiers = $deliveryOptions['locker']['tiers'] ?? [
    'locker_2kg' => ['label' => 'Up to 2kg', 'max_kg' => 2, 'price' => 50],
    'locker_5kg' => ['label' => 'Up to 5kg', 'max_kg' => 5, 'price' => 80],
    'locker_20kg' => ['label' => 'Up to 20kg', 'max_kg' => 20, 'price' => 180],
    'locker_over_20kg' => ['label' => 'Over 20kg flat rate', 'max_kg' => null, 'price' => 350],
];
$doorTiers = $deliveryOptions['door']['tiers'] ?? [
    'door_2kg' => ['label' => 'Up to 2kg', 'max_kg' => 2, 'price' => 89],
    'door_5kg' => ['label' => 'Up to 5kg', 'max_kg' => 5, 'price' => 130],
    'door_20kg' => ['label' => 'Up to 20kg', 'max_kg' => 20, 'price' => 250],
    'door_over_20kg' => ['label' => 'Over 20kg flat rate', 'max_kg' => null, 'price' => 350],
];

function cbPolicyMoney($amount) {
    return 'R' . number_format((float) $amount, 2);
}
?>
<title>Delivery Policy - <?=$website_company_name?></title>
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
    .cb-policy-section ul { padding-left: 20px; }
    .cb-policy-rate-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin: 20px 0; }
    .cb-policy-rate { border: 1px solid #ecdccc; border-radius: 8px; overflow: hidden; background: #fff; }
    .cb-policy-rate h3 { background: #2a1b1b; color: #fff; padding: 14px 16px; margin: 0; font-size: 1.05rem; }
    .cb-policy-rate-row { display: flex; justify-content: space-between; gap: 12px; padding: 12px 16px; border-top: 1px solid #f1e7dd; color: #5d514b; }
    .cb-policy-rate-row strong { color: #2a1b1b; }
    .cb-policy-callout { background: #eef5fb; border: 1px solid #cfe0ed; border-radius: 8px; padding: 16px 18px; margin: 18px 0; color: #273f52; }
    .cb-policy-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .cb-policy-links a { border-radius: 6px; padding: 11px 15px; background: #2a1b1b; color: #fff; font-weight: 700; }
    @media (max-width: 767px) {
        .cb-policy-hero { padding: 40px 0 34px; }
        .cb-policy-grid, .cb-policy-rate-grid { grid-template-columns: 1fr; }
        .cb-policy-nav { position: static; }
    }
</style>

<main class="cb-policy-shell">
    <section class="cb-policy-hero">
        <div class="container">
            <span class="cb-policy-updated">Last updated: 19 April 2026</span>
            <h1>Delivery Policy</h1>
            <p>How Sir Francis calculates delivery, handles Pudo lockers, door-to-door courier, digital products, lead times and addresses outside South Africa.</p>
        </div>
    </section>

    <section class="cb-policy-wrap">
        <div class="container cb-policy-grid">
            <nav class="cb-policy-nav" aria-label="Delivery sections">
                <a href="#methods">Delivery methods</a>
                <a href="#rates">Rates</a>
                <a href="#free">Free shipping</a>
                <a href="#weight">Weight calculation</a>
                <a href="#lead-time">Lead times</a>
                <a href="#outside-sa">Outside South Africa</a>
                <a href="#digital">Digital items</a>
                <a href="#contact">Contact</a>
            </nav>

            <article class="cb-policy-card">
                <section class="cb-policy-section" id="methods">
                    <h2>1. Delivery methods</h2>
                    <p>At checkout, customers can choose from the delivery methods available for the order. The current standard options are Pudo locker and door-to-door courier. The checkout page calculates the correct tier automatically using the order weight and selected method.</p>
                </section>

                <section class="cb-policy-section" id="rates">
                    <h2>2. Current South African delivery rates</h2>
                    <div class="cb-policy-rate-grid">
                        <div class="cb-policy-rate">
                            <h3>Pudo locker</h3>
                            <?php foreach ($lockerTiers as $tier): ?>
                                <div class="cb-policy-rate-row"><span><?=htmlspecialchars($tier['label'], ENT_QUOTES, 'UTF-8')?></span><strong><?=cbPolicyMoney($tier['price'])?></strong></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="cb-policy-rate">
                            <h3>Door-to-door</h3>
                            <?php foreach ($doorTiers as $tier): ?>
                                <div class="cb-policy-rate-row"><span><?=htmlspecialchars($tier['label'], ENT_QUOTES, 'UTF-8')?></span><strong><?=cbPolicyMoney($tier['price'])?></strong></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p>Rates can change when courier costs or service rules change. The checkout total is the amount that applies to the order at the time of checkout.</p>
                </section>

                <section class="cb-policy-section" id="free">
                    <h2>3. Free shipping</h2>
                    <p>Free shipping can apply to qualifying Pudo locker orders over R<?=number_format((float) $free_shipping_amount, 2)?>. It does not apply to door-to-door delivery unless Sir Francis specifically offers that promotion.</p>
                    <div class="cb-policy-callout">Coupon discounts are applied before shipping. If a coupon reduces the order below the free-shipping threshold, the order may no longer qualify for free shipping.</div>
                </section>

                <section class="cb-policy-section" id="weight">
                    <h2>4. How order weight is calculated</h2>
                    <p>Shipping weight is calculated from the product sheet. If a product has an actual shipping weight, that is used. If not, the customer-facing size such as 100g or 1kg is used. If the system cannot understand the size, the admin default item weight is used.</p>
                    <p>This helps with items like liquids, boxes or units where the displayed size may not be the actual parcel weight.</p>
                </section>

                <section class="cb-policy-section" id="lead-time">
                    <h2>5. Lead times and dispatch</h2>
                    <p>Some products may show a lead time because they are prepared fresh, sourced on request, or not always immediately available. Lead time affects when the order is ready to dispatch, not the courier's delivery speed after collection.</p>
                </section>

                <section class="cb-policy-section" id="outside-sa">
                    <h2>6. Addresses outside South Africa</h2>
                    <p>If the delivery address is outside South Africa, checkout may set shipping to R0 and show that a special shipping quote is required. Sir Francis will contact you by email, WhatsApp or phone before dispatch to confirm the international shipping cost and next steps.</p>
                </section>

                <section class="cb-policy-section" id="digital">
                    <h2>7. Digital products</h2>
                    <p>Digital items such as vouchers or e-books do not require courier delivery. If an order contains only digital products, delivery should calculate as R0.</p>
                </section>

                <section class="cb-policy-section" id="contact">
                    <h2>8. Delivery questions</h2>
                    <p>If you need help with an address, locker delivery, courier quote or order weight, contact us at <a href="mailto:<?=$support_email?>"><?=$support_email?></a>.</p>
                    <div class="cb-policy-links">
                        <a href="terms">Terms</a>
                        <a href="return_policy">Buyer Protection</a>
                        <a href="contact">Contact Us</a>
                    </div>
                </section>
            </article>
        </div>
    </section>
</main>

<?php include "footer.php"; ?>
