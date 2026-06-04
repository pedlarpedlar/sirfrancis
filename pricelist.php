<?php
include 'session_logins.php';
require_once __DIR__ . '/pricelist_helpers.php';

date_default_timezone_set('Africa/Johannesburg');

$sort = isset($_GET['sort']) ? strtolower((string) $_GET['sort']) : 'name';
$sort = in_array($sort, ['id', 'name', 'size', 'price'], true) ? $sort : 'name';
$direction = isset($_GET['dir']) && strtolower((string) $_GET['dir']) === 'desc' ? 'desc' : 'asc';
$productsByCategory = cbPricelistProductsByCategory($sort, $direction);
$productCount = cbPricelistProductCount($productsByCategory);
$updatedAt = date('d M Y');
$validMonth = date('F Y');
$limitedDescription = 'Compact CandyBird pricelist with current product prices, specials, sizes and online product links.';
$page_url_canonical = "https://www.candybird.co.za/pricelist";
$title_og = 'Pricelist - CandyBird';
$page_url_og = "https://www.candybird.co.za/pricelist";
$description_og = $limitedDescription;
$description_meta = $limitedDescription;
$image_url_og = 'https://www.candybird.co.za/assets/img/pricelist.png';

include 'header.php';

include 'page_menues.php';

function cbPricelistSortLink($key, $label, $currentSort, $currentDirection) {
    $nextDirection = ($currentSort === $key && $currentDirection === 'asc') ? 'desc' : 'asc';
    $icon = $currentSort === $key ? ($currentDirection === 'asc' ? ' ^' : ' v') : '';
    return '<a class="pricelist-sort-link" href="pricelist?sort=' . rawurlencode($key) . '&dir=' . rawurlencode($nextDirection) . '">' . cbPricelistText($label . $icon) . '</a>';
}

function cbPricelistCategorySortControls($currentSort, $currentDirection) {
    $links = [
        cbPricelistSortLink('name', 'Name', $currentSort, $currentDirection),
        cbPricelistSortLink('price', 'Price', $currentSort, $currentDirection),
        cbPricelistSortLink('size', 'Size', $currentSort, $currentDirection),
        cbPricelistSortLink('id', 'ID', $currentSort, $currentDirection),
    ];
    return '<span class="pricelist-category-sort no-print"><span>Sort by</span>' . implode('', $links) . '</span>';
}
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
  .pricelist-sort-link { color: inherit; text-decoration: none; }
  .pricelist-sort-link:hover { color: #6b0099; text-decoration: underline; }
  .pricelist-table td { border-top: 1px solid #f0ebe4; padding: 6px 10px; vertical-align: middle; }
  .pricelist-table tbody tr:hover td { background: #fffaf2; }
  .pricelist-category td {
    background: #5b1178 !important;
    color: #fcb42f;
    font-weight: 800;
    padding: 7px 10px;
  }
  .pricelist-category-bar {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 8px 14px;
    justify-content: space-between;
  }
  .pricelist-category-sort {
    align-items: center;
    display: inline-flex;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
  }
  .pricelist-category-sort span { color: #f9e7ff; }
  .pricelist-category-sort .pricelist-sort-link {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.22);
    color: #fff;
    padding: 3px 7px;
  }
  .pricelist-category-sort .pricelist-sort-link:hover {
    background: #fcb42f;
    color: #2d1739;
    text-decoration: none;
  }
  .pricelist-group-row td {
    background: #fffdf8;
    padding: 0;
  }
  .pricelist-group-toggle {
    align-items: center;
    background: transparent;
    border: 0;
    color: #2c2926;
    cursor: pointer;
    display: grid;
    gap: 8px;
    grid-template-columns: 28px minmax(0, 1fr) auto auto;
    padding: 8px 10px;
    text-align: left;
    width: 100%;
  }
  .pricelist-group-icon {
    align-items: center;
    background: #f0e8f4;
    border-radius: 50%;
    color: #5b1178;
    display: inline-flex;
    font-weight: 900;
    height: 22px;
    justify-content: center;
    width: 22px;
  }
  .pricelist-group-title { font-weight: 800; min-width: 0; }
  .pricelist-group-range { color: #5b1178; font-weight: 900; white-space: nowrap; }
  .pricelist-group-count { color: #6d6270; font-size: 12px; white-space: nowrap; }
  .pricelist-size-row[hidden] { display: none !important; }
  .pricelist-size-row td:first-child { padding-left: 38px; }
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
    .pricelist-group-toggle { grid-template-columns: 24px minmax(0, 1fr); }
    .pricelist-group-range, .pricelist-group-count { grid-column: 2; }
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
        <a href="pricelist-download?sort=<?= cbPricelistText($sort) ?>&dir=<?= cbPricelistText($direction) ?>" class="btn btn-warning" target="_blank" rel="noopener noreferrer"><i class="fas fa-print mr-1"></i> Print / Save PDF</a>
        <a href="pricelist-download?format=tsv" class="btn btn-light"><i class="fas fa-file-download mr-1"></i> TSV export</a>
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
              <th class="id-cell"><?= cbPricelistSortLink('id', 'ID', $sort, $direction) ?></th>
              <th><?= cbPricelistSortLink('name', 'Product', $sort, $direction) ?></th>
              <th class="size-cell"><?= cbPricelistSortLink('size', 'Size', $sort, $direction) ?></th>
              <th><?= cbPricelistSortLink('price', 'Price', $sort, $direction) ?></th>
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
                <td colspan="6">
                  <div class="pricelist-category-bar">
                    <span><?= cbPricelistText(function_exists('getCandybirdCategoryDisplayLabel') ? getCandybirdCategoryDisplayLabel($categoryName) : $categoryName) ?></span>
                    <?= cbPricelistCategorySortControls($sort, $direction) ?>
                  </div>
                </td>
              </tr>
              <?php foreach (cbPricelistProductGroups($products, $sort, $direction) as $group): ?>
                <tr class="pricelist-group-row">
                  <td colspan="6">
                    <button type="button" class="pricelist-group-toggle" data-group="<?= cbPricelistText($group['id']) ?>" aria-expanded="false">
                      <span class="pricelist-group-icon">+</span>
                      <span class="pricelist-group-title"><?= cbPricelistText($group['title']) ?></span>
                      <span class="pricelist-group-range"><?= cbPricelistText(cbPricelistPriceRange($group)) ?></span>
                      <span class="pricelist-group-count"><?= count($group['products']) ?> option<?= count($group['products']) === 1 ? '' : 's' ?></span>
                    </button>
                  </td>
                </tr>
                <?php foreach ($group['products'] as $product): ?>
                <?php
                  $id = (string) ($product['id'] ?? '');
                  $name = (string) ($product['name'] ?? '');
                  $size = getSheetProductDisplaySize($product);
                  $pricing = cbPricelistPricing($product);
                  $productLink = getSheetProductUrl($product);
                ?>
                <tr class="pricelist-size-row" data-group-row="<?= cbPricelistText($group['id']) ?>" hidden>
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
document.addEventListener('click', function(event) {
  var button = event.target.closest('.pricelist-group-toggle');
  if (!button) return;
  var group = button.getAttribute('data-group');
  var open = button.getAttribute('aria-expanded') !== 'true';
  button.setAttribute('aria-expanded', open ? 'true' : 'false');
  var icon = button.querySelector('.pricelist-group-icon');
  if (icon) icon.textContent = open ? '-' : '+';
  document.querySelectorAll('[data-group-row="' + group + '"]').forEach(function(row) {
    row.hidden = !open;
  });
});
</script>

<?php include 'footer.php'; ?>
