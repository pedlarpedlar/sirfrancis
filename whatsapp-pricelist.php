<?php
include 'session_logins.php';
require_once __DIR__ . '/pricelist_helpers.php';

date_default_timezone_set('Africa/Johannesburg');

$sort = isset($_GET['sort']) ? strtolower((string) $_GET['sort']) : 'custom';
$sort = in_array($sort, ['custom', 'id', 'name', 'size', 'price', 'sale'], true) ? $sort : 'custom';
$direction = isset($_GET['dir']) && strtolower((string) $_GET['dir']) === 'desc' ? 'desc' : 'asc';
$filters = cbPricelistFiltersFromRequest($_GET);
$productsByCategory = cbPricelistProductsByCategory($sort, $direction, $filters);
$products = cbPricelistFlattenProducts($productsByCategory);
$validMonth = date('F Y');
$displayPhone = trim((string) ($hotline ?? ''));
if ($displayPhone === '') {
    $displayPhone = trim((string) ($tel ?? ''));
}
if ($displayPhone === '') {
    $displayPhone = '0842319326';
}
$singleFilteredSize = count($filters['sizes'] ?? []) === 1 ? (string) $filters['sizes'][0] : '';
$waPhone = preg_replace('/\D+/', '', $displayPhone);
if (strpos($waPhone, '0') === 0) {
    $waPhone = '27' . substr($waPhone, 1);
}

$lines = [];
$lines[] = 'Pricelist ' . $validMonth;
$lines[] = '';
foreach ($products as $product) {
    $lines[] = cbPricelistWhatsappLine($product, $singleFilteredSize !== '');
}
if ($singleFilteredSize !== '') {
    $lines[] = '';
    $lines[] = 'Prices above are for ' . $singleFilteredSize . ' packets. Other sizes available: See website.';
}
$lines[] = '';
$lines[] = 'WhatsApp your order to ' . $displayPhone . ' and collect in Malabar, or:';
$lines[] = 'Shop securely online www.candybird.co.za';
$lines[] = '';
$lines[] = '_Delivery available from only R55! Free delivery options also available_';
$whatsappText = implode("\n", $lines);

$limitedDescription = 'Copy-ready CandyBird WhatsApp pricelist text.';
$page_url_canonical = "https://www.candybird.co.za/whatsapp-pricelist";
$title_og = 'WhatsApp Pricelist - CandyBird';
$page_url_og = "https://www.candybird.co.za/whatsapp-pricelist";
$description_og = $limitedDescription;
$description_meta = $limitedDescription;
$image_url_og = 'https://www.candybird.co.za/assets/img/pricelist.jpg';
$image_type_og = 'image/jpeg';
$image_width_og = '1200';
$image_height_og = '630';

include 'header.php';
include 'page_menues.php';
?>

<style>
  .whatsapp-pricelist-page { background: #f7f4ef; padding: 28px 0 50px; }
  .whatsapp-pricelist-hero {
    background: #2d1739;
    border-radius: 8px;
    color: #fff;
    margin-bottom: 14px;
    padding: 20px;
  }
  .whatsapp-pricelist-hero h1 { color: #fcb42f; font-size: 30px; margin: 0 0 6px; }
  .whatsapp-pricelist-hero p { color: #f8ecff; margin: 0; }
  .whatsapp-pricelist-card {
    background: #fff;
    border: 1px solid #eadfd2;
    border-radius: 8px;
    padding: 16px;
  }
  .whatsapp-pricelist-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
  .whatsapp-pricelist-text {
    border: 1px solid #decbe7;
    border-radius: 8px;
    color: #241b14;
    font-family: Consolas, Monaco, monospace;
    font-size: 14px;
    line-height: 1.55;
    min-height: 520px;
    padding: 14px;
    resize: vertical;
    width: 100%;
  }
  .whatsapp-pricelist-note {
    color: #6d6270;
    font-size: 13px;
    margin-top: 10px;
  }
  @media (max-width: 767px) {
    .whatsapp-pricelist-hero h1 { font-size: 24px; }
    .whatsapp-pricelist-text { min-height: 430px; }
  }
</style>

<main class="whatsapp-pricelist-page">
  <div class="container">
    <div class="whatsapp-pricelist-hero">
      <h1>WhatsApp Pricelist</h1>
      <p><?= number_format(count($products)) ?> matching item<?= count($products) === 1 ? '' : 's' ?>. Use the filters on the main pricelist, then copy this text into WhatsApp.</p>
    </div>

    <div class="whatsapp-pricelist-card">
      <div class="whatsapp-pricelist-toolbar">
        <button type="button" class="btn btn-success" id="copy-whatsapp-pricelist"><i class="fas fa-copy mr-1"></i> Copy text</button>
        <?php if ($waPhone !== ''): ?>
          <a class="btn btn-warning" href="https://wa.me/<?= cbPricelistText($waPhone) ?>?text=<?= rawurlencode($whatsappText) ?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp mr-1"></i> Open WhatsApp</a>
        <?php endif; ?>
        <a class="btn btn-light" href="pricelist?<?= cbPricelistText(http_build_query($_GET)) ?>"><i class="fas fa-filter mr-1"></i> Back to filters</a>
      </div>

      <textarea class="whatsapp-pricelist-text" id="whatsapp-pricelist-text" spellcheck="false"><?= cbPricelistText($whatsappText) ?></textarea>
      <p class="whatsapp-pricelist-note" id="copy-whatsapp-pricelist-status">WhatsApp uses ~text~ for strikethrough and _text_ for italics.</p>
    </div>
  </div>
</main>

<script>
document.getElementById('copy-whatsapp-pricelist')?.addEventListener('click', function() {
  var textBox = document.getElementById('whatsapp-pricelist-text');
  var status = document.getElementById('copy-whatsapp-pricelist-status');
  if (!textBox) return;
  textBox.focus();
  textBox.select();
  function done() {
    if (status) status.textContent = 'Copied. You can paste this directly into WhatsApp.';
  }
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(textBox.value).then(done).catch(function() {
      document.execCommand('copy');
      done();
    });
  } else {
    document.execCommand('copy');
    done();
  }
});
</script>

<?php include 'footer.php'; ?>
