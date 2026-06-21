<?php
include 'session_logins.php';

$contactWhatsappNumber = trim((string) ($hotline ?? ''));
if ($contactWhatsappNumber === '') {
    $contactWhatsappNumber = trim((string) ($tel ?? ''));
}
$contactWhatsappDigits = preg_replace('/\D+/', '', $contactWhatsappNumber);
if (strpos($contactWhatsappDigits, '0') === 0) {
    $contactWhatsappDigits = '27' . substr($contactWhatsappDigits, 1);
}
$consumerEmail = 'info@sirfrancis.co.za';
$contactFallbackAddress = 'Sir Francis, 1000 Example Avenue, Manhattan, New York, NY 10001, USA';
function sfContactPublicAddress($value, $fallback) {
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }
    if (preg_match('/babiana|malabar|port\s*elizabeth/i', $value)) {
        return $fallback;
    }
    return $value;
}
$contactGoogleAddress = sfContactPublicAddress($website_address ?? '', $contactFallbackAddress);
$contactHeadOffice = sfContactPublicAddress($headquarters ?: ($website_address ?? ''), 'Manhattan, New York, USA');
$googleReviewUrl = 'https://g.page/r/CfEBOxQp_13OEBE/review';
$googleMapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($contactGoogleAddress);
$googleEmbedUrl = 'https://www.google.com/maps?q=' . rawurlencode($contactGoogleAddress) . '&output=embed';
$limitedDescription = 'Contact Sir Francis for product support, retail orders, wholesale supply, bulk buying and private labelling enquiries.';
$page_url_canonical = 'https://sirfrancis.co.za/contact';
$title_og = 'Contact Us - Sir Francis';
$page_url_og = 'https://sirfrancis.co.za/contact';
$description_og = $limitedDescription;
$description_meta = $limitedDescription;
$contactRecaptchaEnabled = false;
$contactRecaptchaSiteKey = '';
$contactRecaptchaType = 'v3';
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $recaptchaColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'contact_recaptcha_site_key'");
    if ($recaptchaColumnCheck && $recaptchaColumnCheck->num_rows > 0) {
        $recaptchaTypeColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'contact_recaptcha_type'");
        $recaptchaColumns = $recaptchaTypeColumnCheck && $recaptchaTypeColumnCheck->num_rows > 0
            ? "contact_recaptcha_enabled, contact_recaptcha_type, contact_recaptcha_site_key"
            : "contact_recaptcha_enabled, contact_recaptcha_site_key";
        $recaptchaResult = $conn->query("SELECT {$recaptchaColumns} FROM admin_website_settings LIMIT 1");
        if ($recaptchaResult && ($recaptchaSettings = $recaptchaResult->fetch_assoc())) {
            $contactRecaptchaEnabled = !empty($recaptchaSettings['contact_recaptcha_enabled']);
            $contactRecaptchaSiteKey = trim((string) ($recaptchaSettings['contact_recaptcha_site_key'] ?? ''));
            $contactRecaptchaType = in_array($recaptchaSettings['contact_recaptcha_type'] ?? 'v3', ['v3', 'v2_checkbox'], true) ? $recaptchaSettings['contact_recaptcha_type'] : 'v3';
        }
    }
}
$contactStartedAt = time();
$_SESSION['contact_form_started_at'] = $contactStartedAt;
include 'header.php';
?>
<?php include 'page_menues.php'; ?>

<style>
  .cb-contact { background: #fbfaf7; color: #251d18; }
  .cb-contact-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 44px 0 34px; }
  .cb-contact-hero h1 { color: #251d18; font-size: clamp(2.2rem, 5vw, 4rem); line-height: 1.05; margin: 0 0 12px; }
  .cb-contact-hero p { color: #5d514b; max-width: 760px; font-size: 1.08rem; line-height: 1.7; }
  .cb-contact-wrap { padding: 34px 0 64px; }
  .cb-contact-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 430px); gap: 22px; align-items: start; }
  .cb-contact-card { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; padding: clamp(18px, 4vw, 30px); box-shadow: 0 14px 36px rgba(71,44,22,.07); }
  .cb-contact-card h2 { color: #251d18; font-size: 1.35rem; margin: 0 0 16px; }
  .cb-contact-item { padding: 14px 0; border-bottom: 1px solid #f0e5da; }
  .cb-contact-item:last-child { border-bottom: 0; }
  .cb-contact-item h3 { color: #2a1b1b; font-size: 1rem; margin: 0 0 6px; display: flex; gap: 8px; align-items: center; }
  .cb-contact-item h3 i { color: #c96f38; }
  .cb-contact-item p, .cb-contact-item a { color: #5d514b; line-height: 1.65; }
  .cb-contact-highlight { background: #fff8e6; border: 1px solid #f1d79f; border-radius: 8px; margin-top: 12px; padding: 14px; }
  .cb-review-panel { background: #2a1b1b; border-radius: 8px; color: #fff; margin-top: 16px; padding: 16px; }
  .cb-review-panel h3 { color: #CEBD88; display: flex; align-items: center; gap: 8px; font-size: 1.05rem; margin: 0 0 8px; }
  .cb-review-panel p { color: rgba(255,255,255,.86); line-height: 1.55; margin-bottom: 12px; }
  .cb-review-panel a { background: #CEBD88; border-radius: 6px; color: #251d18; display: inline-flex; align-items: center; gap: 8px; font-weight: 800; padding: 10px 13px; text-decoration: none; }
  .cb-hours-list { list-style: none; margin: 0; padding: 0; }
  .cb-hours-list li { align-items: flex-start; border-top: 1px solid #f0e5da; display: flex; justify-content: space-between; gap: 14px; padding: 9px 0; }
  .cb-hours-list li:first-child { border-top: 0; padding-top: 0; }
  .cb-hours-list strong { color: #2a1b1b; }
  .cb-hours-list span { color: #5d514b; text-align: right; }
  .cb-contact-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
  .cb-contact-actions a { border-radius: 6px; padding: 11px 15px; font-weight: 700; }
  .cb-contact-actions .dark { background: #2a1b1b; color: #fff; }
  .cb-contact-actions .gold { background: #CEBD88; color: #251d18; }
  .cb-contact-form input, .cb-contact-form textarea { width: 100%; border: 1px solid #e5d6c7; border-radius: 6px; padding: 12px 13px; background: #fff; }
  .cb-contact-form label { color: #3f332e; font-weight: 700; }
  .cb-contact-form .contact-website-field { position: absolute; left: -10000px; opacity: 0; height: 1px; width: 1px; overflow: hidden; }
  .contact-security-note { color: #6a5c54; font-size: 12px; line-height: 1.45; margin-top: 10px; }
  .cb-contact-map iframe { width: 100%; height: 340px; border: 0; display: block; }
  .cb-contact-map { border-radius: 8px; overflow: hidden; border: 1px solid #eee1d4; margin-top: 22px; }
  @media (max-width: 991px) { .cb-contact-grid { grid-template-columns: 1fr; } }
</style>

<main class="cb-contact">
  <section class="cb-contact-hero">
    <div class="container">
      <h1>Contact Sir Francis</h1>
      <p>Questions, compliments, complaints, delivery help, bulk orders, gifting, private labelling or customer support. Use the details below and we will route your message to the right person.</p>
      <div class="cb-contact-actions">
        <?php if (!empty($contactWhatsappDigits)): ?>
          <a class="dark" href="https://wa.me/<?=$contactWhatsappDigits?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp mr-2"></i><?=$contactWhatsappNumber?></a>
        <?php endif; ?>
        <a class="gold" href="<?=$googleMapsUrl?>" target="_blank" rel="noopener noreferrer">Find us on Google Maps</a>
      </div>
    </div>
  </section>

  <section class="cb-contact-wrap">
    <div class="container cb-contact-grid">
      <div>
        <div class="cb-contact-card">
          <h2>Customer care details</h2>

          <div class="cb-contact-item">
            <h3><i class="fas fa-map-marker-alt"></i>Head office</h3>
            <p><?=htmlspecialchars($contactHeadOffice, ENT_QUOTES, 'UTF-8')?></p>
          </div>

          <div class="cb-contact-item">
            <h3><i class="fas fa-warehouse"></i>Operational address</h3>
            <p><?=htmlspecialchars($contactGoogleAddress, ENT_QUOTES, 'UTF-8')?></p>
            <p class="cb-contact-highlight mb-0">Use this exact address in Google Maps for the Sir Francis location: <strong><?=htmlspecialchars($contactGoogleAddress, ENT_QUOTES, 'UTF-8')?></strong>.</p>
          </div>

          <div class="cb-contact-item">
            <h3><i class="fas fa-phone"></i>Phone numbers</h3>
            <?php if (!empty($tel)): ?><p><a href="tel:<?=htmlspecialchars($tel, ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($tel, ENT_QUOTES, 'UTF-8')?></a></p><?php endif; ?>
            <?php if (!empty($contactWhatsappDigits)): ?><p><a href="https://wa.me/<?=$contactWhatsappDigits?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp mr-1"></i><?=htmlspecialchars($contactWhatsappNumber, ENT_QUOTES, 'UTF-8')?></a></p><?php endif; ?>
          </div>

          <div class="cb-contact-item">
            <h3><i class="fas fa-envelope"></i>Email addresses</h3>
            <p><a href="mailto:<?=$website_email?>">Sales and support: <?=$website_email?></a></p>
            <?php if (!empty($website_email2)): ?><p><a href="mailto:<?=$website_email2?>">Secondary email: <?=$website_email2?></a></p><?php endif; ?>
            <p><a href="mailto:<?=$consumerEmail?>">Compliments or complaints: <?=$consumerEmail?></a></p>
          </div>

          <div class="cb-contact-item">
            <h3><i class="fab fa-google"></i>Public transparency</h3>
            <p>Customers are welcome to leave a public Google review so others can see real feedback before ordering.</p>
            <div class="cb-review-panel">
              <h3><i class="fas fa-star"></i>Rate Sir Francis on Google</h3>
              <p>Share your experience with our products, delivery, collection, gifting, or customer service.</p>
              <a href="<?=$googleReviewUrl?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-google"></i>Post a Google review</a>
            </div>
          </div>

          <div class="cb-contact-item">
            <h3><i class="far fa-clock"></i>Trading hours</h3>
            <ul class="cb-hours-list">
              <li><strong>Monday to Thursday</strong><span>8:30am - 4:30pm</span></li>
              <li><strong>Friday</strong><span>8:30am - 4:30pm<br>Closed 11:00am - 2:00pm</span></li>
              <li><strong>Saturday</strong><span>8:30am - 2:00pm</span></li>
              <li><strong>Sunday</strong><span>8:30am - 2:00pm</span></li>
            </ul>
          </div>
        </div>

        <div class="cb-contact-map">
          <iframe src="<?=$googleEmbedUrl?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
      </div>

      <div class="cb-contact-card">
        <h2>Send a message</h2>
        <form id="contact-form" class="cb-contact-form" action="contact_submit" method="POST">
          <input type="hidden" name="contact_started_at" value="<?=htmlspecialchars((string) $contactStartedAt, ENT_QUOTES, 'UTF-8')?>">
          <input type="hidden" name="contact_recaptcha_action" value="contact_form">
          <?php if ($contactRecaptchaEnabled && $contactRecaptchaSiteKey !== '' && $contactRecaptchaType === 'v3'): ?>
            <input type="hidden" name="g-recaptcha-response" id="contact_recaptcha_response" value="">
          <?php endif; ?>
          <div class="contact-website-field" aria-hidden="true">
            <label>Website</label>
            <input type="text" name="website_url" tabindex="-1" autocomplete="off">
          </div>
          <div class="form-group">
            <label>Your Name <span class="required">*</span></label>
            <input type="text" name="name" id="name" />
          </div>
          <div class="form-group">
            <label>Your Email <span class="required">*</span></label>
            <input type="email" name="email" id="email" />
          </div>
          <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject" id="subject" />
          </div>
          <div class="form-group">
            <label>Your Message</label>
            <textarea name="contactMessage" class="pb-10" id="contactMessage"></textarea>
          </div>
          <?php if ($contactRecaptchaEnabled && $contactRecaptchaSiteKey !== '' && $contactRecaptchaType === 'v2_checkbox'): ?>
            <div class="form-group">
              <div class="g-recaptcha" data-sitekey="<?=htmlspecialchars($contactRecaptchaSiteKey, ENT_QUOTES, 'UTF-8')?>"></div>
            </div>
          <?php endif; ?>
          <div class="form-group mb-0">
            <button type="submit" value="submit" id="submit" class="btn btn-dark btn--lg" name="submit">Submit</button>
          </div>
          <?php if ($contactRecaptchaEnabled && $contactRecaptchaSiteKey !== '' && $contactRecaptchaType === 'v3'): ?>
            <p class="contact-security-note">This form is protected by reCAPTCHA to help make sure messages come from real people.</p>
          <?php endif; ?>
        </form>
        <p class="form-message mt-10"></p>
      </div>
    </div>
  </section>
</main>

<?php if ($contactRecaptchaEnabled && $contactRecaptchaSiteKey !== '' && $contactRecaptchaType === 'v2_checkbox'): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php if ($contactRecaptchaEnabled && $contactRecaptchaSiteKey !== '' && $contactRecaptchaType === 'v3'): ?>
  <script src="https://www.google.com/recaptcha/api.js?render=<?=htmlspecialchars($contactRecaptchaSiteKey, ENT_QUOTES, 'UTF-8')?>"></script>
  <script>
  (function() {
    var form = document.getElementById('contact-form');
    var tokenField = document.getElementById('contact_recaptcha_response');
    var submitButton = form ? form.querySelector('button[type="submit"]') : null;
    var siteKey = '<?=htmlspecialchars($contactRecaptchaSiteKey, ENT_QUOTES, 'UTF-8')?>';
    var tokenPromise = null;

    function setMessage(message, isError) {
      var messageBox = document.querySelector('.form-message');
      if (!messageBox) {
        return;
      }
      messageBox.textContent = message || '';
      messageBox.style.color = isError ? '#b42318' : '#1d7d38';
    }

    function getToken() {
      if (!window.grecaptcha || !tokenField) {
        return Promise.reject(new Error('Security check is still loading.'));
      }
      if (tokenField.value) {
        return Promise.resolve(tokenField.value);
      }
      if (tokenPromise) {
        return tokenPromise;
      }
      tokenPromise = new Promise(function(resolve, reject) {
        grecaptcha.ready(function() {
          grecaptcha.execute(siteKey, {action: 'contact_form'}).then(function(token) {
            tokenField.value = token;
            tokenPromise = null;
            resolve(token);
          }).catch(function(error) {
            tokenPromise = null;
            reject(error);
          });
        });
      });
      return tokenPromise;
    }

    if (!form || !tokenField) {
      return;
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Preparing...';
    }

    function enableSubmit() {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Submit';
      }
    }

    function primeToken(attemptsLeft) {
      getToken().then(enableSubmit).catch(function() {
        if (attemptsLeft > 0) {
          window.setTimeout(function() { primeToken(attemptsLeft - 1); }, 700);
        } else {
          enableSubmit();
          setMessage('The automatic security check is slow to load. Please wait a moment and try again.', true);
        }
      });
    }

    primeToken(8);

    form.addEventListener('submit', function(event) {
      if (tokenField.value) {
        return;
      }
      event.preventDefault();
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Checking...';
      }
      setMessage('Running the automatic security check...', false);
      getToken().then(function(token) {
          tokenField.value = token;
          form.submit();
      }).catch(function() {
        enableSubmit();
        setMessage('The automatic security check could not load. Please refresh the page or email us directly.', true);
      });
    });
  })();
  </script>
<?php endif; ?>

<?php include 'footer.php'; ?>
