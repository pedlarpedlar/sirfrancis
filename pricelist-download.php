<?php
include 'session_logins.php';
require_once __DIR__ . '/pricelist_helpers.php';

$sort = isset($_GET['sort']) ? strtolower((string) $_GET['sort']) : 'custom';
$sort = in_array($sort, ['custom', 'id', 'name', 'size', 'price'], true) ? $sort : 'custom';
$direction = isset($_GET['dir']) && strtolower((string) $_GET['dir']) === 'desc' ? 'desc' : 'asc';
$productsByCategory = cbPricelistProductsByCategory($sort, $direction);
$productCount = cbPricelistProductCount($productsByCategory);
$updatedAt = date('d M Y');
$validMonth = date('F Y');
$downloadTitle = 'CandyBird Pricelist ' . date('F Y');
$format = strtolower(trim((string) ($_GET['format'] ?? 'html')));

if ($format === 'tsv') {
    $filename = 'CandyBird-Pricelist-' . date('F-Y') . '.tsv';
    header('Content-Type: text/tab-separated-values; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id', 'product', 'category', 'size', 'normal_price', 'sale_price', 'discount_percent', 'special_valid_until', 'product_url'], "\t");
    foreach ($productsByCategory as $categoryName => $products) {
        foreach ($products as $product) {
            $pricing = cbPricelistPricing($product);
            fputcsv($out, [
                $product['id'] ?? '',
                $product['name'] ?? '',
                cbPricelistDisplayCategoryPath($categoryName),
                getSheetProductDisplaySize($product),
                number_format((float) $pricing['normal_price'], 2, '.', ''),
                number_format((float) $pricing['sale_price'], 2, '.', ''),
                $pricing['is_special'] ? (string) $pricing['saving_percent'] : '',
                $pricing['valid_until'],
                getSheetProductUrl($product, true),
            ], "\t");
        }
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= cbPricelistText($downloadTitle) ?></title>
  <style>
    * { box-sizing: border-box; }
    body { background: #f7f4ef; color: #2c2926; font-family: Arial, sans-serif; margin: 18px; }
    .topbar { align-items: flex-start; background: #2d1739; border-bottom: 4px solid #fcb42f; color: #fff; display: flex; justify-content: space-between; gap: 16px; padding: 12px 14px; margin-bottom: 10px; }
    h1 { color: #fcb42f; font-size: 22px; margin: 0 0 4px; }
    .meta { color: #f8ecff; font-size: 11px; line-height: 1.4; }
    .actions { display: flex; gap: 8px; }
    button, a.button { background: #fcb42f; border: 0; color: #2d1739; cursor: pointer; display: inline-block; font-size: 12px; font-weight: bold; padding: 8px 10px; text-decoration: none; }
    a.button.secondary { background: #fff; color: #5b1178; }
    .note { background: #fff; border: 1px solid #eadfd2; border-left: 5px solid #fcb42f; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 8px 10px; font-size: 10px; margin-bottom: 10px; }
    table { border-collapse: collapse; font-size: 9px; width: 100%; }
    th, td { border: 1px solid #e6dccf; padding: 2px 4px; text-align: left; vertical-align: top; }
    th { background: #f0e8f4; color: #4b185f; font-size: 8px; text-transform: uppercase; }
    tbody tr:nth-child(even):not(.category) td { background: #fffdf8; }
    .category td { background: #5b1178; color: #fcb42f; font-weight: bold; padding: 4px 5px; }
    .id { width: 42px; color: #555; }
    .size { width: 60px; color: #333; }
    .price { width: 92px; font-weight: bold; white-space: nowrap; }
    .price del { color: #666; display: block; font-size: 8px; font-weight: normal; }
    .sale { color: #1d7d38; font-weight: bold; }
    .saving { background: #e5361f; color: #fff; display: inline-block; font-size: 7px; margin-left: 3px; padding: 1px 3px; }
    .valid { width: 70px; color: #333; font-size: 8px; white-space: nowrap; }
    .footer-note { color: #333; font-size: 9px; line-height: 1.4; margin-top: 10px; }
    @media print {
      body { background: #fff; margin: 8mm; }
      .actions { display: none; }
      table { font-size: 8px; }
      th, td { padding: 1.6px 3px; }
      .category { break-inside: avoid; }
      tr { break-inside: avoid; }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div>
      <h1>CandyBird Pricelist</h1>
      <div class="meta"><?= number_format($productCount) ?> products | Valid for <?= cbPricelistText($validMonth) ?> | Updated <?= cbPricelistText($updatedAt) ?> | www.candybird.co.za/pricelist</div>
    </div>
    <div class="actions">
      <button type="button" onclick="window.print()">Print / Save PDF</button>
      <a class="button secondary" href="pricelist-download?format=tsv">TSV export</a>
      <a class="button secondary" href="pricelist">Back</a>
    </div>
  </div>

  <div class="note">
    <div><strong>Delivery:</strong> checkout online for live shipping and free-shipping qualification.</div>
    <div><strong>Specials:</strong> website coupons and specials apply online only.</div>
    <div><strong>Validity:</strong> prices are intended for <?= cbPricelistText($validMonth) ?> and may change without notice when stock is refilled or supplier costs change.</div>
    <div><strong>Latest prices:</strong> the live website pricelist is always the final reference.</div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="id">ID</th>
        <th>Product</th>
        <th class="size">Size</th>
        <th class="price">Price</th>
        <th class="valid">Special Ends</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($productsByCategory as $categoryName => $products): ?>
        <tr class="category"><td colspan="5"><?= cbPricelistText(cbPricelistDisplayCategoryPath($categoryName)) ?></td></tr>
        <?php foreach ($products as $product): ?>
          <?php
            $id = (string) ($product['id'] ?? '');
            $name = (string) ($product['name'] ?? '');
            $size = getSheetProductDisplaySize($product);
            $pricing = cbPricelistPricing($product);
          ?>
          <tr>
            <td class="id"><?= cbPricelistText($id) ?></td>
            <td><?= cbPricelistText($name) ?></td>
            <td class="size"><?= cbPricelistText($size) ?></td>
            <td class="price">
              <?php if ($pricing['is_special']): ?>
                <del>R<?= cbPricelistText(number_format($pricing['normal_price'], 2)) ?></del>
                <span class="sale">R<?= cbPricelistText(number_format($pricing['sale_price'], 2)) ?></span>
                <span class="saving">-<?= cbPricelistText($pricing['saving_percent']) ?>%</span>
              <?php else: ?>
                R<?= cbPricelistText(number_format($pricing['normal_price'], 2)) ?>
              <?php endif; ?>
            </td>
            <td class="valid">
              <?php if ($pricing['is_special'] && $pricing['valid_until'] !== ''): ?>
                <?= cbPricelistText($pricing['valid_until']) ?>
              <?php elseif ($pricing['is_special']): ?>
                While stocks last
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer-note">
    Stock is subject to availability. Personalized/custom orders usually require 3-7 days.
    Prices are intended for <?= cbPricelistText($validMonth) ?>, but may change without notice due to stock refills, supplier changes, and seasonal availability.
    The website pricelist is the live reference.
  </div>
</body>
</html>
