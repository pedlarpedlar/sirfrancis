<?php
include 'session_logins.php';
require_once __DIR__ . '/pricelist_helpers.php';

date_default_timezone_set('Africa/Johannesburg');

$productsByCategory = cbPricelistProductsByCategory();
$productCount = cbPricelistProductCount($productsByCategory);
$updatedAt = date('d M Y');
$validMonth = date('F Y');

include 'header.php';

$page_url_canonical = "https://www.candybird.co.za/pricelist";
$title_og = 'Pricelist - CandyBird';
$page_url_og = "https://www.candybird.co.za/pricelist";
$description_og = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');
$description_meta = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');

include 'page_menues.php';
?>

<style>
  .pricelist-page { background: #f7f4ef; padding: 28px 0 44px; }
  .pricelist-hero {
    background: #2d1739;
    color: #fff;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 14px;
  }
  .pricelist-hero h1 { color: #fcb42f; font-size: 30px; margin: 0 0 4px; }
  .pricelist-hero p { margin: 0; color: #f8ecff; font-size: 14px; }
  .pricelist-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
  .pricelist-note {
    background: #fff;
    border: 1px solid #eadfd2;
    border-radius: 8px;
    padding: 10px 14px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 16px;
    font-size: 13px;
    color: #51475a;
    margin-bottom: 14px;
  }
  .pricelist-shell {
    background: #fff;
    border: 1px solid #eadfd2;
    border-radius: 8px;
    overflow: hidden;
  }
  .pricelist-table { margin: 0; color: #2c2926; font-size: 13px; }
  .pricelist-table thead th {
    background: #f0e8f4;
    border-bottom: 1px solid #decbe7;
    color: #4b185f;
    font-size: 12px;
    letter-spacing: .02em;
    padding: 8px 10px;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .pricelist-table td { border-top: 1px solid #f0ebe4; padding: 6px 10px; vertical-align: middle; }
  .pricelist-table tbody tr:hover td { background: #fffaf2; }
  .pricelist-category td {
    background: #5b1178 !important;
    color: #fcb42f;
    font-weight: 800;
    padding: 7px 10px;
  }
  .product-link { color: #2c2926; font-weight: 700; text-decoration: none; }
  .product-link:hover { color: #6b0099; }
  .price-cell { color: #5b1178; font-weight: 800; white-space: nowrap; }
  .price-cell del { color: #8a7d8f; display: block; font-size: 11px; font-weight: 600; margin-bottom: 1px; }
  .price-cell .sale-price { color: #1d7d38; display: inline-block; }
  .price-cell .saving-pill {
    background: #e5361f;
    border-radius: 999px;
    color: #fff;
    display: inline-block;
    font-size: 10px;
    font-weight: 800;
    line-height: 1;
    margin-left: 5px;
    padding: 3px 5px;
    vertical-align: middle;
  }
  .valid-cell { color: #6d6270; font-size: 12px; white-space: nowrap; }
  .valid-cell .special-until { color: #9b2b19; font-weight: 700; }
  .id-cell, .size-cell { color: #6d6270; white-space: nowrap; }
  .cart-cell { text-align: center; width: 52px; }
  .cart-cell a { color: #5b1178; font-size: 18px; }
  .pricelist-footnote {
    color: #6d6270;
    font-size: 12px;
    line-height: 1.6;
    margin-top: 14px;
  }
  @media (max-width: 767px) {
    .pricelist-hero { align-items: flex-start; flex-direction: column; }
    .pricelist-actions { justify-content: flex-start; }
    .pricelist-note { grid-template-columns: 1fr; }
    .pricelist-table { font-size: 12px; }
    .pricelist-table td, .pricelist-table thead th { padding: 6px 7px; }
    .id-cell { display: none; }
  }
  @media print {
    .no-print, header, footer, .breadcrumb-section, .main-menu, .mobile-header { display: none !important; }
    .pricelist-page { background: #fff; padding: 0; }
    .container { max-width: 100% !important; width: 100% !important; }
    .pricelist-hero, .pricelist-note, .pricelist-shell { border-radius: 0; border: 0; }
    .pricelist-hero { background: #fff; color: #111; padding: 0 0 8px; }
    .pricelist-hero h1 { color: #111; font-size: 22px; }
    .pricelist-hero p { color: #333; }
    .pricelist-table { font-size: 8px; }
    .pricelist-table td, .pricelist-table thead th { padding: 2px 4px; }
    .cart-cell { display: none; }
  }
</style>

<main class="pricelist-page">
  <div class="container">
    <div class="pricelist-hero">
      <div>
        <h1>CandyBird Pricelist</h1>
        <p><?= number_format($productCount) ?> products | Valid for <?= cbPricelistText($validMonth) ?> | Updated <?= cbPricelistText($updatedAt) ?></p>
      </div>
      <div class="pricelist-actions no-print">
        <a href="pricelist-download" class="btn btn-warning" target="_blank" rel="noopener noreferrer"><i class="fas fa-print mr-1"></i> Print / Save PDF</a>
        <a href="products" class="btn btn-light"><i class="fas fa-shopping-basket mr-1"></i> Shop online</a>
      </div>
    </div>

    <div class="pricelist-note">
      <div><strong>Delivery:</strong> checkout online for live shipping and free-shipping qualification.</div>
      <div><strong>Specials:</strong> website coupons and specials apply online only.</div>
      <div><strong>Validity:</strong> prices are intended for <?= cbPricelistText($validMonth) ?> and may change without notice when stock is refilled or supplier costs change.</div>
      <div><strong>Latest prices:</strong> the live website pricelist is always the final reference.</div>
    </div>

    <div class="pricelist-shell">
      <div class="table-responsive">
        <table class="table pricelist-table">
          <thead>
            <tr>
              <th class="id-cell">ID</th>
              <th>Product</th>
              <th class="size-cell">Size</th>
              <th>Price</th>
              <th class="valid-cell">Special Ends</th>
              <th class="cart-cell no-print">Cart</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($productsByCategory)): ?>
              <tr><td colspan="6">Products are still loading. Please refresh in a moment.</td></tr>
            <?php endif; ?>

            <?php foreach ($productsByCategory as $categoryName => $products): ?>
              <tr class="pricelist-category">
                <td colspan="6"><?= cbPricelistText($categoryName) ?></td>
              </tr>
              <?php foreach ($products as $product): ?>
                <?php
                  $id = (string) ($product['id'] ?? '');
                  $name = (string) ($product['name'] ?? '');
                  $size = getSheetProductDisplaySize($product);
                  $pricing = cbPricelistPricing($product);
                  $productLink = 'product?id=' . rawurlencode($id);
                ?>
                <tr>
                  <td class="id-cell"><?= cbPricelistText($id) ?></td>
                  <td><a class="product-link" href="<?= cbPricelistText($productLink) ?>"><?= cbPricelistText($name) ?></a></td>
                  <td class="size-cell"><?= cbPricelistText($size) ?></td>
                  <td class="price-cell">
                    <?php if ($pricing['is_special']): ?>
                      <del>R<?= cbPricelistText(number_format($pricing['normal_price'], 2)) ?></del>
                      <span class="sale-price">R<?= cbPricelistText(number_format($pricing['sale_price'], 2)) ?></span>
                      <span class="saving-pill">-<?= cbPricelistText($pricing['saving_percent']) ?>%</span>
                    <?php else: ?>
                      R<?= cbPricelistText(number_format($pricing['normal_price'], 2)) ?>
                    <?php endif; ?>
                  </td>
                  <td class="valid-cell">
                    <?php if ($pricing['is_special'] && $pricing['valid_until'] !== ''): ?>
                      <span class="special-until"><?= cbPricelistText($pricing['valid_until']) ?></span>
                    <?php elseif ($pricing['is_special']): ?>
                      While stocks last
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td class="cart-cell no-print"><a href="#" class="add-to-cart" data-toggle="modal" data-target="#add-to-cart" data-quantity="1" data-product-id="<?= cbPricelistText($id) ?>" title="Add to cart"><i class="icon-basket"></i></a></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <p class="pricelist-footnote">
      WhatsApp or email your order for an invoice, or checkout online for specials and convenience.
      Stock is subject to availability. Personalized/custom orders usually require 3-7 days.
      Prices are intended for <?= cbPricelistText($validMonth) ?>, but may change without notice due to stock refills, supplier changes, and seasonal availability.
      View the latest list at www.candybird.co.za/pricelist.
    </p>
  </div>
</main>

<script>
window.CANDYBIRD_PRODUCTS = <?= json_encode(array_merge(...array_values($productsByCategory ?: [[]])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>

<?php include 'footer.php'; ?>
