<?php
include 'session_logins.php';
require_once __DIR__ . '/wholesale_pricelist_helpers.php';

$rows = getCandybirdWholesaleRows();
$rowsByCategory = getCandybirdWholesaleRowsByCategory();
$validMonth = date('F Y');
$updatedAt = date('d M Y');
$format = strtolower(trim((string) ($_GET['format'] ?? 'html')));

if ($format === 'tsv') {
    $filename = 'CandyBird-Wholesale-Pricelist-' . date('F-Y') . '.tsv';
    header('Content-Type: text/tab-separated-values; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['product_id', 'title', 'category', 'bulk_size', 'price', 'price_per_kg', 'retail_price_kg', 'pack_down_fee', 'pack_down_note', 'moq', 'lead_time', 'free_delivery_excluded', 'description'], "\t");
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['product_id'],
            $row['title'],
            $row['category'],
            $row['size'],
            number_format((float) $row['price'], 2, '.', ''),
            (float) ($row['price_per_kg'] ?? 0) > 0 ? number_format((float) $row['price_per_kg'], 2, '.', '') : '',
            (float) ($row['retail_price_kg'] ?? 0) > 0 ? number_format((float) $row['retail_price_kg'], 2, '.', '') : '',
            (float) ($row['pack_down_fee'] ?? 0) > 0 ? number_format((float) $row['pack_down_fee'], 2, '.', '') : '',
            $row['pack_down_note'],
            $row['moq'],
            $row['lead_time'],
            $row['free_delivery_excluded'],
            $row['description'],
        ], "\t");
    }
    fclose($out);
    exit;
}

$downloadTitle = 'CandyBird Wholesale Pricelist ' . date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= cbWholesaleText($downloadTitle) ?></title>
  <style>
    * { box-sizing:border-box; }
    body { color:#111; font-family:Arial, sans-serif; margin:18px; }
    .topbar { align-items:flex-start; border-bottom:2px solid #111; display:flex; gap:16px; justify-content:space-between; margin-bottom:10px; padding-bottom:8px; }
    h1 { font-size:22px; margin:0 0 4px; }
    .meta { color:#444; font-size:11px; line-height:1.4; }
    .actions { display:flex; gap:8px; }
    button, a.button { background:#111; border:0; color:#fff; cursor:pointer; display:inline-block; font-size:12px; padding:8px 10px; text-decoration:none; }
    .note { border:1px solid #bbb; display:grid; font-size:10px; gap:8px; grid-template-columns:1fr 1fr 1fr; margin-bottom:10px; padding:7px; }
    table { border-collapse:collapse; font-size:9px; width:100%; }
    th, td { border:1px solid #ccc; padding:2px 4px; text-align:left; vertical-align:top; }
    th { background:#eee; font-size:8px; text-transform:uppercase; }
    .category td { background:#222; color:#fff; font-weight:bold; padding:3px 4px; }
    .id { width:42px; color:#555; }
    .size { width:72px; }
    .price { width:150px; font-weight:bold; }
    .details { color:#333; font-size:8px; }
    .footer-note { color:#333; font-size:9px; line-height:1.4; margin-top:10px; }
    @media print {
      body { margin:8mm; }
      .actions { display:none; }
      table { font-size:8px; }
      th, td { padding:1.6px 3px; }
      tr { break-inside:avoid; }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div>
      <h1>CandyBird Wholesale Pricelist</h1>
      <div class="meta"><?= number_format(count($rows)) ?> bulk lines | Valid for <?= cbWholesaleText($validMonth) ?> | Updated <?= cbWholesaleText($updatedAt) ?> | www.candybird.co.za/wholesale-pricelist</div>
    </div>
    <div class="actions">
      <button type="button" onclick="window.print()">Print / Save PDF</button>
      <a class="button" href="wholesale-pricelist-download?format=tsv">TSV export</a>
      <a class="button" href="wholesale-pricelist">Back</a>
    </div>
  </div>

  <div class="note">
    <div><strong>Bulk use:</strong> for resellers, food service, gifting, offices and larger repeat buyers.</div>
    <div><strong>Pack-down:</strong> fees apply to requested packing work/pack units, not only the bulk case size.</div>
    <div><strong>Final quote:</strong> stock, packing, delivery and lead time are confirmed before invoicing.</div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="id">ID</th>
        <th>Product</th>
        <th class="size">Bulk Size</th>
        <th class="price">Wholesale Price</th>
        <th>Details</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rowsByCategory as $category => $categoryRows): ?>
        <tr class="category"><td colspan="5"><?= cbWholesaleText(function_exists('getCandybirdCategoryDisplayLabel') ? getCandybirdCategoryDisplayLabel($category) : $category) ?></td></tr>
        <?php foreach ($categoryRows as $row): ?>
          <tr>
            <td class="id"><?= cbWholesaleText($row['product_id']) ?></td>
            <td><?= cbWholesaleText($row['title']) ?></td>
            <td class="size"><?= cbWholesaleText($row['size']) ?></td>
            <td class="price"><?= cbWholesaleText(cbWholesaleDisplayPrice($row)) ?></td>
            <td class="details">
              <?php $retailComparison = cbWholesaleRetailComparison($row); ?>
              <?php if ($retailComparison !== ''): ?><?= cbWholesaleText($retailComparison) ?>. <?php endif; ?>
              <?php if (!empty($row['moq'])): ?>MOQ: <?= cbWholesaleText($row['moq']) ?>. <?php endif; ?>
              <?php if (!empty($row['lead_time'])): ?>Lead time: <?= cbWholesaleText($row['lead_time']) ?>. <?php endif; ?>
              <?php if (!empty($row['pack_down_note'])): ?>Pack-down: <?= cbWholesaleText($row['pack_down_note']) ?>. <?php elseif ((float)($row['pack_down_fee'] ?? 0) > 0): ?>Pack-down fee is calculated against the actual requested packs/units. <?php endif; ?>
              <?php if (!empty($row['free_delivery_excluded']) && $row['free_delivery_excluded'] === 'yes'): ?>Free shipping does not apply to this item. <?php endif; ?>
              <?= cbWholesaleText($row['description']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer-note">
    Prices are intended for <?= cbWholesaleText($validMonth) ?> and may change without notice due to supplier costs, stock refills and seasonal availability.
    Confirm stock, packing, delivery and lead time before payment.
  </div>
</body>
</html>
