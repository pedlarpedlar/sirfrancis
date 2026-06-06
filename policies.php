<?php
include 'session_logins.php';
$page_url_canonical = 'https://www.candybird.co.za/policies';
$title_og = 'CandyBird Policies | Terms, Delivery, Privacy and Buyer Protection';
$page_url_og = $page_url_canonical;
$description_meta = 'Find CandyBird policies in one place, including terms and conditions, delivery, privacy, cookies, buyer protection and returns.';
$description_og = $description_meta;
$image_url_og = 'https://www.candybird.co.za/assets/img/pricelist.png';
include 'header.php';
?>
<title>CandyBird Policies - <?=$website_company_name?></title>
<?php include 'page_menues.php'; ?>

<style>
  .cb-policies-page { background: #fbfaf7; color: #251d18; }
  .cb-policies-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 48px 0 36px; }
  .cb-policies-kicker { background: #fff; border: 1px solid #eadfd2; border-radius: 999px; color: #744628; display: inline-flex; font-size: .9rem; font-weight: 700; margin-bottom: 16px; padding: 8px 13px; }
  .cb-policies-hero h1 { color: #251d18; font-size: clamp(2.1rem, 5vw, 4.1rem); line-height: 1.05; margin: 0 0 14px; }
  .cb-policies-hero p { color: #5d514b; font-size: 1.08rem; line-height: 1.75; max-width: 780px; }
  .cb-policies-wrap { padding: 34px 0 70px; }
  .cb-policies-grid { display: grid; gap: 16px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .cb-policy-tile { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; box-shadow: 0 14px 34px rgba(71,44,22,.07); color: #2d201a; display: flex; flex-direction: column; min-height: 188px; padding: 22px; transition: transform .18s ease, box-shadow .18s ease; }
  .cb-policy-tile:hover { box-shadow: 0 18px 42px rgba(71,44,22,.11); color: #2d201a; transform: translateY(-2px); }
  .cb-policy-tile span { align-items: center; background: #fff3e6; border-radius: 50%; color: #a85023; display: inline-flex; font-size: 1.1rem; height: 42px; justify-content: center; margin-bottom: 16px; width: 42px; }
  .cb-policy-tile h2 { color: #251d18; font-size: 1.16rem; margin: 0 0 9px; }
  .cb-policy-tile p { color: #665750; line-height: 1.65; margin: 0; }
  .cb-policies-note { background: #2a1b1b; border-radius: 8px; color: #fff; margin-top: 22px; padding: 22px; }
  .cb-policies-note h2 { color: #fff; font-size: 1.25rem; margin: 0 0 8px; }
  .cb-policies-note p { color: rgba(255,255,255,.84); line-height: 1.7; margin: 0; }
  @media (max-width: 991px) {
    .cb-policies-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 575px) {
    .cb-policies-hero { padding: 40px 0 32px; }
    .cb-policies-grid { grid-template-columns: 1fr; }
  }
</style>

<main class="cb-policies-page">
  <section class="cb-policies-hero">
    <div class="container">
      <span class="cb-policies-kicker">Policies and customer information</span>
      <h1>Everything important, in one place.</h1>
      <p>These pages explain how CandyBird handles orders, delivery, payment, privacy, cookies, returns and customer support. We keep them clear so customers can shop with confidence and staff can answer questions consistently.</p>
    </div>
  </section>

  <section class="cb-policies-wrap">
    <div class="container">
      <div class="cb-policies-grid">
        <a class="cb-policy-tile" href="terms">
          <span><i class="fas fa-file-contract"></i></span>
          <h2>Terms and Conditions</h2>
          <p>Ordering, payments, product availability, coupons, accounts and general website terms.</p>
        </a>
        <a class="cb-policy-tile" href="delivery_policy">
          <span><i class="fas fa-truck"></i></span>
          <h2>Delivery Policy</h2>
          <p>Collection, locker delivery, door delivery, free-shipping rules and out-of-SA delivery notes.</p>
        </a>
        <a class="cb-policy-tile" href="return_policy">
          <span><i class="fas fa-shield-alt"></i></span>
          <h2>Buyer Protection and Returns</h2>
          <p>Our iqaalah-inspired buyer protection approach, returns, cancellations and fair order support.</p>
        </a>
        <a class="cb-policy-tile" href="privacypolicy">
          <span><i class="fas fa-user-lock"></i></span>
          <h2>Privacy Policy</h2>
          <p>How customer information is used for orders, accounts, communication, security and service.</p>
        </a>
        <a class="cb-policy-tile" href="cookie_policy">
          <span><i class="fas fa-cookie-bite"></i></span>
          <h2>Cookie Policy</h2>
          <p>How cookies support carts, login sessions, analytics, customer experience and bot protection.</p>
        </a>
        <a class="cb-policy-tile" href="contact">
          <span><i class="fas fa-headset"></i></span>
          <h2>Contact and Complaints</h2>
          <p>Contact details for order help, compliments, complaints, reviews and customer support.</p>
        </a>
      </div>

      <div class="cb-policies-note">
        <h2>Need help understanding a policy?</h2>
        <p>For questions about an order, delivery, refund, payment or product, please contact us before placing the order or as soon as something needs attention. We would rather clarify early than leave a customer uncertain.</p>
      </div>
    </div>
  </section>
</main>

<?php include 'footer.php'; ?>
