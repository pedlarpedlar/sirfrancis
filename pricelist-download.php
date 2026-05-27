<?php
include 'session_logins.php';
require_once __DIR__ . '/pricelist_helpers.php';

$productsByCategory = cbPricelistProductsByCategory();
$productCount = cbPricelistProductCount($productsByCategory);
$updatedAt = date('d M Y');
$validMonth = date('F Y');
$downloadTitle = 'CandyBird Pricelist ' . date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= cbPricelistText($downloadTitle) ?></title>
  <style>
    * { box-sizing: border-box; }
    body { color: #111; font-family: Arial, sans-serif; margin: 18px; }
    .topbar { align-items: flex-start; border-bottom: 2px solid #111; display: flex; justify-content: space-between; gap: 16px; padding-bottom: 8px; margin-bottom: 10px; }
    h1 { font-size: 22px; margin: 0 0 4px; }
    .meta { color: #444; font-size: 11px; line-height: 1.4; }
    .actions { display: flex; gap: 8px; }
    button, a.button { background: #111; border: 0; color: #fff; cursor: pointer; display: inline-block; font-size: 12px; padding: 8px 10px; text-decoration: none; }
    .note { border: 1px solid #bbb; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 7px; font-size: 10px; margin-bottom: 10px; }
    table { border-collapse: collapse; font-size: 9px; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 2px 4px; text-align: left; vertical-align: top; }
    th { background: #eee; font-size: 8px; text-transform: uppercase; }
    .category td { background: #222; color: #fff; font-weight: bold; padding: 3px 4px; }
    .id { width: 42px; color: #555; }
    .size { width: 60px; color: #333; }
    .price { width: 92px; font-weight: bold; white-space: nowrap; }
    .price del { color: #666; display: block; font-size: 8px; font-weight: normal; }
    .sale { color: #111; font-weight: bold; }
    .saving { border: 1px solid #111; display: inline-block; font-size: 7px; margin-left: 3px; padding: 0 2px; }
    .valid { width: 70px; color: #333; font-size: 8px; white-space: nowrap; }
    .footer-note { color: #333; font-size: 9px; line-height: 1.4; margin-top: 10px; }
    @media print {
      body { margin: 8mm; }
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
      <a class="button" href="pricelist">Back</a>
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
        <tr class="category"><td colspan="5"><?= cbPricelistText($categoryName) ?></td></tr>
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
