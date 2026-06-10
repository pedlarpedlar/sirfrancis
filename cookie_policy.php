<?php
include 'session_logins.php';
$page_url_canonical = 'https://www.candybird.co.za/cookie_policy';
$title_og = 'Cookie Policy | CandyBird';
$page_url_og = $page_url_canonical;
$description_meta = 'CandyBird cookie policy explaining how cookies support carts, checkout, login sessions, analytics, security and customer experience.';
$description_og = $description_meta;
$image_url_og = 'https://www.candybird.co.za/assets/img/pricelist.jpg';
$image_type_og = 'image/jpeg';
include 'header.php';
?>
<title>Cookie Policy - <?=$website_company_name?></title>
<?php include 'page_menues.php'; ?>

<style>
  .cb-cookie-page { background: #fbfaf7; color: #251d18; }
  .cb-cookie-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 46px 0 36px; }
  .cb-cookie-hero h1 { color: #251d18; font-size: clamp(2.1rem, 5vw, 4.1rem); line-height: 1.05; margin: 0 0 14px; }
  .cb-cookie-hero p { color: #5d514b; font-size: 1.08rem; line-height: 1.75; max-width: 780px; }
  .cb-cookie-updated { background: #fff; border: 1px solid #eadfd2; border-radius: 999px; color: #744628; display: inline-flex; font-size: .9rem; font-weight: 700; margin-bottom: 16px; padding: 8px 13px; }
  .cb-cookie-wrap { padding: 34px 0 66px; }
  .cb-cookie-card { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; box-shadow: 0 16px 40px rgba(71,44,22,.07); padding: clamp(20px, 4vw, 38px); }
  .cb-cookie-section { border-bottom: 1px solid #f0e5da; padding: 8px 0 24px; }
  .cb-cookie-section:last-child { border-bottom: 0; padding-bottom: 0; }
  .cb-cookie-section h2 { color: #271817; font-size: 1.38rem; margin: 0 0 12px; }
  .cb-cookie-section p, .cb-cookie-section li { color: #5d514b; line-height: 1.75; }
  .cb-cookie-section ul { padding-left: 20px; }
  .cb-cookie-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
  .cb-cookie-links a { background: #2a1b1b; border-radius: 6px; color: #fff; font-weight: 700; padding: 11px 15px; }
</style>

<main class="cb-cookie-page">
  <section class="cb-cookie-hero">
    <div class="container">
      <span class="cb-cookie-updated">Last updated: 19 April 2026</span>
      <h1>Cookie Policy</h1>
      <p>Cookies help this website remember carts, keep sessions working, improve customer experience and protect forms from automated spam. This page explains how they are used.</p>
    </div>
  </section>

  <section class="cb-cookie-wrap">
    <div class="container">
      <article class="cb-cookie-card">
        <section class="cb-cookie-section">
          <h2>1. What cookies are</h2>
          <p>Cookies are small pieces of information stored by your browser. They help the website remember useful details such as your cart, login session, preferences and security checks.</p>
        </section>

        <section class="cb-cookie-section">
          <h2>2. How CandyBird uses cookies</h2>
          <ul>
            <li>To keep your cart and checkout working correctly.</li>
            <li>To support customer login, remember-me sessions and account features.</li>
            <li>To understand which pages, searches and products are useful to customers.</li>
            <li>To help identify abandoned carts, website errors and areas where the shopping experience can improve.</li>
            <li>To support form protection and reduce spam or automated abuse.</li>
          </ul>
        </section>

        <section class="cb-cookie-section">
          <h2>3. Analytics and customer experience</h2>
          <p>Website activity may be used to understand general shopping patterns, popular products, searches that need better results, and where customers may need clearer information. This helps us improve the website and customer support.</p>
        </section>

        <section class="cb-cookie-section">
          <h2>4. Managing cookies</h2>
          <p>You can control cookies through your browser settings. Some parts of the website, especially cart, login and checkout features, may not work correctly if necessary cookies are blocked.</p>
        </section>

        <section class="cb-cookie-section">
          <h2>5. Related policies</h2>
          <p>For more detail about customer information and website privacy, please read our privacy policy.</p>
          <div class="cb-cookie-links">
            <a href="privacypolicy">Privacy Policy</a>
            <a href="terms">Terms and Conditions</a>
            <a href="policies">All Policies</a>
          </div>
        </section>
      </article>
    </div>
  </section>
</main>

<?php include 'footer.php'; ?>
