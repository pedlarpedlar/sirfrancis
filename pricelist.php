<?php
include 'session_logins.php';
require_once __DIR__ . '/pricelist_helpers.php';

date_default_timezone_set('Africa/Johannesburg');

$sort = isset($_GET['sort']) ? strtolower((string) $_GET['sort']) : 'custom';
$sort = in_array($sort, ['custom', 'id', 'name', 'size', 'price', 'sale'], true) ? $sort : 'custom';
$direction = isset($_GET['dir']) && strtolower((string) $_GET['dir']) === 'desc' ? 'desc' : 'asc';
$filters = cbPricelistFiltersFromRequest($_GET);
$filterOptions = cbPricelistFilterOptions();
$productsByCategory = cbPricelistProductsByCategory($sort, $direction, $filters);
$productCount = cbPricelistProductCount($productsByCategory);
$currentQuery = $_GET;
$currentQuery['sort'] = $sort;
$currentQuery['dir'] = $direction;
$whatsappPricelistUrl = 'whatsapp-pricelist?' . http_build_query($currentQuery);
$printPricelistUrl = 'pricelist-download?' . http_build_query($currentQuery);
$tsvQuery = $currentQuery;
$tsvQuery['format'] = 'tsv';
$tsvPricelistUrl = 'pricelist-download?' . http_build_query($tsvQuery);
$updatedAt = date('d M Y');
$validMonth = date('F Y');
$limitedDescription = 'Compact CandyBird pricelist with current product prices, specials, sizes and online product links.';
$page_url_canonical = "https://www.candybird.co.za/pricelist";
$title_og = 'Pricelist - CandyBird';
$page_url_og = "https://www.candybird.co.za/pricelist";
$description_og = $limitedDescription;
$description_meta = $limitedDescription;
$image_url_og = 'https://www.candybird.co.za/assets/img/pricelist.jpg';
$image_type_og = 'image/jpeg';
$image_width_og = '1200';
$image_height_og = '630';

include 'header.php';

include 'page_menues.php';

function cbPricelistSortLink($key, $label, $currentSort, $currentDirection) {
    $nextDirection = ($currentSort === $key && $currentDirection === 'asc') ? 'desc' : 'asc';
    $icon = $currentSort === $key ? ($currentDirection === 'asc' ? ' ^' : ' v') : '';
    $query = $_GET;
    $query['sort'] = $key;
    $query['dir'] = $nextDirection;
    return '<a class="pricelist-sort-link" href="pricelist?' . http_build_query($query) . '">' . cbPricelistText($label . $icon) . '</a>';
}

function cbPricelistCategorySortControls($currentSort, $currentDirection) {
    $links = [
        cbPricelistSortLink('custom', 'Custom', $currentSort, $currentDirection),
        cbPricelistSortLink('name', 'Name', $currentSort, $currentDirection),
        cbPricelistSortLink('price', 'Price', $currentSort, $currentDirection),
        cbPricelistSortLink('size', 'Size', $currentSort, $currentDirection),
        cbPricelistSortLink('sale', 'Sale', $currentSort, $currentDirection),
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
  .pricelist-hero-copy { min-width: 0; }
  .pricelist-hero-media {
    aspect-ratio: 1.9 / 1;
    border-radius: 8px;
    max-width: 420px;
    object-fit: cover;
    width: min(420px, 38vw);
  }
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
  .pricelist-search {
    align-items: center;
    background: #fff;
    border: 1px solid #eadfd2;
    border-radius: 8px;
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
    padding: 10px 12px;
  }
  .pricelist-search label {
    color: #4b185f;
    font-size: 12px;
    font-weight: 800;
    margin: 0;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .pricelist-search input {
    border: 1px solid #decbe7;
    border-radius: 6px;
    flex: 1;
    min-width: 0;
    padding: 9px 10px;
  }
  .pricelist-search-count {
    color: #6d6270;
    font-size: 12px;
    white-space: nowrap;
  }
  .pricelist-filter-panel {
    background: #fff;
    border: 1px solid #eadfd2;
    border-radius: 8px;
    margin-bottom: 14px;
    padding: 14px;
  }
  .pricelist-filter-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: 1.3fr 1fr 1fr 1fr;
  }
  .pricelist-filter-panel label {
    color: #4b185f;
    display: block;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 5px;
    text-transform: uppercase;
  }
  .pricelist-filter-panel .form-control { border-color: #decbe7; border-radius: 6px; font-size: 13px; }
  .pricelist-filter-panel select[multiple] { min-height: 92px; }
  .pricelist-filter-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
  .pricelist-mini-fields { display: grid; gap: 8px; grid-template-columns: 1fr; }
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
    .pricelist-hero-media { max-width: 100%; width: 100%; }
    .pricelist-actions { justify-content: flex-start; }
    .pricelist-note { grid-template-columns: 1fr; }
    .pricelist-filter-grid { grid-template-columns: 1fr; }
    .pricelist-search { align-items: stretch; flex-direction: column; }
    .pricelist-search-count { white-space: normal; }
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
    .pricelist-hero-media { display: none; }
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
      <div class="pricelist-hero-copy">
        <h1>CandyBird Pricelist</h1>
        <p><?= number_format($productCount) ?> products | Valid for <?= cbPricelistText($validMonth) ?> | Updated <?= cbPricelistText($updatedAt) ?></p>
      </div>
      <img class="pricelist-hero-media no-print" src="https://www.candybird.co.za/assets/img/pricelist.jpg" alt="CandyBird pricelist product range" loading="lazy">
      <div class="pricelist-actions no-print">
        <a href="<?= cbPricelistText($whatsappPricelistUrl) ?>" class="btn btn-success"><i class="fab fa-whatsapp mr-1"></i> WhatsApp pricelist</a>
        <a href="<?= cbPricelistText($printPricelistUrl) ?>" class="btn btn-warning" target="_blank" rel="noopener noreferrer"><i class="fas fa-print mr-1"></i> Print / Save PDF</a>
        <a href="<?= cbPricelistText($tsvPricelistUrl) ?>" class="btn btn-light"><i class="fas fa-file-download mr-1"></i> TSV export</a>
        <a href="products" class="btn btn-light"><i class="fas fa-shopping-basket mr-1"></i> Shop online</a>
      </div>
    </div>

    <div class="pricelist-note">
      <div><strong>Delivery:</strong> checkout online for live shipping and free-shipping qualification.</div>
      <div><strong>Specials:</strong> website coupons and specials apply online only.</div>
      <div><strong>Validity:</strong> prices are intended for <?= cbPricelistText($validMonth) ?> and may change without notice when stock is refilled or supplier costs change.</div>
      <div><strong>Latest prices:</strong> the live website pricelist is always the final reference.</div>
    </div>

    <form class="pricelist-filter-panel no-print" method="get" action="pricelist">
      <div class="pricelist-filter-grid">
        <div>
          <label for="pl-q">Find item</label>
          <input class="form-control" type="search" id="pl-q" name="q" value="<?= cbPricelistText($filters['q']) ?>" placeholder="Try: 100g pecan plain, pistachio 1kg">
        </div>
        <div>
          <label for="pl-categories">Categories</label>
          <select class="form-control" id="pl-categories" name="categories[]" multiple>
            <?php foreach ($filterOptions['categories'] as $categoryPath => $categoryLabel): ?>
              <option value="<?= cbPricelistText($categoryPath) ?>" <?= in_array($categoryPath, $filters['categories'], true) ? 'selected' : '' ?>><?= cbPricelistText($categoryLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="pl-sizes">Sizes</label>
          <select class="form-control" id="pl-sizes" name="sizes[]" multiple>
            <?php foreach ($filterOptions['sizes'] as $size): ?>
              <option value="<?= cbPricelistText($size) ?>" <?= in_array($size, $filters['sizes'], true) ? 'selected' : '' ?>><?= cbPricelistText($size) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Price and rows</label>
          <div class="pricelist-mini-fields">
            <select class="form-control" name="price_range">
              <?php
                $rangeOptions = [
                  'all' => 'All prices',
                  '0-50' => 'Up to R50',
                  '50-100' => 'R50 to R100',
                  '100-250' => 'R100 to R250',
                  '250-500' => 'R250 to R500',
                  '500-1000' => 'R500 to R1,000',
                  '1000-' => 'R1,000 and above',
                ];
                $selectedRange = $filters['price_range'] ?: 'all';
              ?>
              <?php foreach ($rangeOptions as $rangeValue => $rangeLabel): ?>
                <option value="<?= cbPricelistText($rangeValue) ?>" <?= $selectedRange === $rangeValue ? 'selected' : '' ?>><?= cbPricelistText($rangeLabel) ?></option>
              <?php endforeach; ?>
            </select>
            <input class="form-control" type="number" min="1" max="1000" step="1" name="limit" value="<?= cbPricelistText($filters['limit'] ?: '') ?>" placeholder="Limit rows">
            <select class="form-control" name="sale">
              <option value="all" <?= $filters['sale'] === 'all' ? 'selected' : '' ?>>All prices</option>
              <option value="sale" <?= $filters['sale'] === 'sale' ? 'selected' : '' ?>>Specials only</option>
              <option value="regular" <?= $filters['sale'] === 'regular' ? 'selected' : '' ?>>Non-sale only</option>
            </select>
          </div>
        </div>
      </div>
      <div class="pricelist-filter-actions">
        <select class="form-control" name="sort" style="max-width:180px;">
          <option value="custom" <?= $sort === 'custom' ? 'selected' : '' ?>>Custom order</option>
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Alphabetical</option>
          <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Price</option>
          <option value="size" <?= $sort === 'size' ? 'selected' : '' ?>>Size</option>
          <option value="sale" <?= $sort === 'sale' ? 'selected' : '' ?>>Sale status</option>
          <option value="id" <?= $sort === 'id' ? 'selected' : '' ?>>Product ID</option>
        </select>
        <select class="form-control" name="dir" style="max-width:150px;">
          <option value="asc" <?= $direction === 'asc' ? 'selected' : '' ?>>Low to high / A-Z</option>
          <option value="desc" <?= $direction === 'desc' ? 'selected' : '' ?>>High to low / Z-A</option>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i> Apply filters</button>
        <a href="pricelist" class="btn btn-light">Clear</a>
        <a href="<?= cbPricelistText($whatsappPricelistUrl) ?>" class="btn btn-success"><i class="fab fa-whatsapp mr-1"></i> WhatsApp text</a>
        <span class="pricelist-search-count" id="pricelist-search-count"><?= number_format($productCount) ?> matching options</span>
      </div>
    </form>

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
                    <span><?= cbPricelistText(cbPricelistDisplayCategoryPath($categoryName)) ?></span>
                    <?= cbPricelistCategorySortControls($sort, $direction) ?>
                  </div>
                </td>
              </tr>
              <?php foreach (cbPricelistProductGroups($products, $sort, $direction) as $group): ?>
                <?php
                  $groupSearchParts = [$categoryName, $group['title']];
                  foreach ($group['products'] as $groupSearchProduct) {
                      $groupSearchParts[] = $groupSearchProduct['id'] ?? '';
                      $groupSearchParts[] = $groupSearchProduct['name'] ?? '';
                      $groupSearchParts[] = getSheetProductDisplaySize($groupSearchProduct);
                  }
                  $groupSearchText = implode(' ', array_filter($groupSearchParts));
                ?>
                <tr class="pricelist-group-row" data-group-search="<?= cbPricelistText($groupSearchText) ?>">
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
                <tr class="pricelist-size-row" data-group-row="<?= cbPricelistText($group['id']) ?>" data-row-search="<?= cbPricelistText(implode(' ', [$categoryName, $id, $name, $size])) ?>" hidden>
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

(function() {
  var searchInput = document.getElementById('pricelist-search-input');
  var countEl = document.getElementById('pricelist-search-count');
  if (!searchInput) return;

  function normalize(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/&amp;/g, '&')
      .replace(/[^a-z0-9]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function tokens(value) {
    var expanded = [];
    normalize(value).split(' ').filter(Boolean).forEach(function(token) {
      expanded.push(token);
      if (token.length > 3 && token.slice(-1) === 's') {
        expanded.push(token.slice(0, -1));
      }
    });
    return Array.from(new Set(expanded));
  }

  function tokenMatch(haystack, queryTokens) {
    if (!queryTokens.length) return true;
    return queryTokens.every(function(token) {
      return haystack.indexOf(token) !== -1;
    });
  }

  function updatePricelistSearch() {
    var queryTokens = tokens(searchInput.value);
    var searching = queryTokens.length > 0;
    var visibleOptions = 0;
    var visibleGroups = 0;

    document.querySelectorAll('.pricelist-category').forEach(function(categoryRow) {
      var categoryHasVisible = false;
      var row = categoryRow.nextElementSibling;

      while (row && !row.classList.contains('pricelist-category')) {
        if (row.classList.contains('pricelist-group-row')) {
          var groupId = row.querySelector('.pricelist-group-toggle') ? row.querySelector('.pricelist-group-toggle').getAttribute('data-group') : '';
          var groupHasVisible = false;

          document.querySelectorAll('[data-group-row="' + groupId + '"]').forEach(function(sizeRow) {
            var rowText = normalize(sizeRow.getAttribute('data-row-search') || '');
            var rowMatches = tokenMatch(rowText, queryTokens);
            if (searching) {
              sizeRow.hidden = !rowMatches;
            } else {
              var button = row.querySelector('.pricelist-group-toggle');
              sizeRow.hidden = !(button && button.getAttribute('aria-expanded') === 'true');
            }
            if (rowMatches) {
              groupHasVisible = true;
              visibleOptions++;
            }
          });

          row.style.display = (!searching || groupHasVisible) ? '' : 'none';
          var button = row.querySelector('.pricelist-group-toggle');
          var icon = row.querySelector('.pricelist-group-icon');
          if (searching && button && groupHasVisible) {
            button.setAttribute('aria-expanded', 'true');
            if (icon) icon.textContent = '-';
          } else if (!searching && button && button.getAttribute('aria-expanded') !== 'true' && icon) {
            icon.textContent = '+';
          }
          if (!searching || groupHasVisible) {
            categoryHasVisible = true;
            if (groupHasVisible) visibleGroups++;
          }
        }
        row = row.nextElementSibling;
      }

      categoryRow.style.display = (!searching || categoryHasVisible) ? '' : 'none';
    });

    if (countEl) {
      countEl.textContent = searching
        ? visibleOptions + ' matching option' + (visibleOptions === 1 ? '' : 's')
        : '<?= number_format($productCount) ?> options';
    }
  }

  searchInput.addEventListener('input', updatePricelistSearch);
})();
</script>

<?php include 'footer.php'; ?>
