<?php
include 'session_logins.php';
require_once __DIR__ . '/wholesale_pricelist_helpers.php';

$rows = getCandybirdWholesaleRows();
$rowsByCategory = getCandybirdWholesaleRowsByCategory();
$validMonth = date('F Y');
$updatedAt = date('d M Y');
$format = strtolower(trim((string) ($_GET['format'] ?? 'html')));
$isAdminPricelist = !empty($_SESSION['admin_id']);

if ($format === 'tsv') {
    if (!$isAdminPricelist) {
        http_response_code(403);
        echo 'TSV export is available to admin users only.';
        exit;
    }
    $filename = 'Sir Francis-Wholesale-Pricelist-' . date('F-Y') . '.tsv';
    header('Content-Type: text/tab-separated-values; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['product_id', 'title', 'category', 'bulk_size', 'case_price', 'price_per_kg', 'pack_down_fee', 'pack_down_note', 'allowed_pack_sizes', 'moq', 'lead_time', 'description'], "\t");
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['product_id'],
            $row['title'],
            cbWholesaleDisplayCategoryPath($row['category']),
            $row['size'],
            number_format((float) $row['price'], 2, '.', ''),
            (float) ($row['price_per_kg'] ?? 0) > 0 ? number_format((float) $row['price_per_kg'], 2, '.', '') : '',
            (float) ($row['pack_down_fee'] ?? 0) > 0 ? number_format((float) $row['pack_down_fee'], 2, '.', '') : '',
            $row['pack_down_note'],
            implode(',', $row['allowed_pack_sizes'] ?? []),
            $row['moq'],
            $row['lead_time'],
            $row['description'],
        ], "\t");
    }
    fclose($out);
    exit;
}

$downloadTitle = 'Sir Francis Wholesale Pricelist ' . date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= cbWholesaleText($downloadTitle) ?></title>
  <style>
    * { box-sizing:border-box; }
    body { background:#f4eee2; color:#1d293c; font-family:Raleway, Arial, sans-serif; margin:18px; }
    .topbar { align-items:flex-start; background:#0b2341; border:1px solid #c9b36d; box-shadow:inset 0 0 0 3px #0b2341, inset 0 0 0 4px rgba(201,179,109,.75); color:#fff; display:flex; gap:16px; justify-content:space-between; margin-bottom:10px; padding:14px 16px; }
    h1 { color:#d3bd75; font-family:"Playfair Display", Georgia, serif; font-size:22px; margin:0 0 4px; }
    .meta { color:#f5ead3; font-size:11px; line-height:1.4; }
    .actions { display:flex; gap:8px; }
    button, a.button { background:#0b2341; border:1px solid #c9b36d; box-shadow:inset 0 0 0 2px #0b2341, inset 0 0 0 3px rgba(201,179,109,.85); color:#d3bd75; cursor:pointer; display:inline-block; font-size:12px; font-weight:bold; padding:8px 10px; text-decoration:none; text-transform:uppercase; }
    a.button.secondary { background:#f4eee2; box-shadow:inset 0 0 0 2px #f4eee2, inset 0 0 0 3px rgba(201,179,109,.9); color:#0b2341; }
    .note { background:#fffaf2; border:1px solid #d9c98a; border-left:5px solid #c9b36d; display:grid; font-size:10px; gap:8px; grid-template-columns:1fr 1fr 1fr; margin-bottom:10px; padding:8px 10px; }
    table { border-collapse:collapse; font-size:9px; width:100%; }
    th, td { border:1px solid #eadfbd; padding:2px 4px; text-align:left; vertical-align:top; }
    th { background:#0b2341; color:#d3bd75; font-size:8px; text-transform:uppercase; }
    tbody tr:nth-child(even):not(.category) td { background:#fffdf8; }
    .category td { background:#102f52; color:#d3bd75; font-family:"Playfair Display", Georgia, serif; font-weight:bold; padding:4px 5px; }
    .id { width:42px; color:#526071; }
    .size { width:72px; }
    .price { color:#0b2341; width:150px; font-weight:bold; }
    .details { color:#344154; font-size:8px; }
    .tag { background:#f4eee2; border:1px solid #c9b36d; color:#0b2341; display:inline-block; font-size:7px; font-weight:bold; margin:1px 2px 1px 0; padding:1px 3px; }
    .footer-note { color:#344154; font-size:9px; line-height:1.4; margin-top:10px; }
    @media print {
      body { background:#fff; margin:8mm; }
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
      <h1>Sir Francis Wholesale Pricelist</h1>
      <div class="meta"><?= number_format(count($rows)) ?> bulk lines | Valid for <?= cbWholesaleText($validMonth) ?> | Updated <?= cbWholesaleText($updatedAt) ?> | sirfrancis.co.za/wholesale-pricelist</div>
    </div>
    <div class="actions">
      <button type="button" onclick="window.print()">Print / Save PDF</button>
      <?php if ($isAdminPricelist): ?><a class="button secondary" href="wholesale-pricelist-download?format=tsv">TSV export</a><?php endif; ?>
      <a class="button secondary" href="wholesale-pricelist">Back</a>
    </div>
  </div>

  <div class="note">
    <div><strong>Bulk use:</strong> for resellers, food service, gifting, offices and larger repeat buyers.</div>
    <div><strong>Pack-down:</strong> fees apply to requested packing work/pack units, not only the bulk case size.</div>
    <div><strong>Final quote:</strong> stock, packing, delivery and lead time are confirmed before invoicing. Retail free-shipping rules may not apply to bulk lines.</div>
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
        <tr class="category"><td colspan="5"><?= cbWholesaleText(cbWholesaleDisplayCategoryPath($category)) ?></td></tr>
        <?php foreach ($categoryRows as $row): ?>
          <tr>
            <td class="id"><?= cbWholesaleText($row['product_id']) ?></td>
            <td><?= cbWholesaleText($row['title']) ?></td>
            <td class="size"><?= cbWholesaleText($row['size']) ?></td>
            <td class="price">
              <?php
                $bulkKg = cbWholesaleSizeToKg($row['size'] ?? '');
                $casePrice = (float) ($row['price'] ?? 0);
                $pricePerKg = (float) ($row['price_per_kg'] ?? 0);
                if ($pricePerKg <= 0 && $bulkKg > 0 && $casePrice > 0) {
                    $pricePerKg = $casePrice / $bulkKg;
                }
              ?>
              <?= $pricePerKg > 0 ? cbWholesaleText(cbWholesaleFormatMoney($pricePerKg) . ' / kg') : cbWholesaleText(cbWholesaleFormatMoney($casePrice)) ?>
              <?php if ($casePrice > 0): ?><br><span class="details">Case: <?= cbWholesaleText(cbWholesaleFormatMoney($casePrice)) ?> / <?= cbWholesaleText($row['size']) ?></span><?php endif; ?>
            </td>
            <td class="details">
              <?php if ((float)($row['pack_down_fee'] ?? 0) > 0): ?><span class="tag">Pack-down <?= cbWholesaleText(cbWholesaleFormatMoney($row['pack_down_fee'])) ?> / unit</span> <?php endif; ?>
              <?php if (!empty($row['moq'])): ?>MOQ: <?= cbWholesaleText($row['moq']) ?>. <?php endif; ?>
              <?php if (!empty($row['lead_time'])): ?>Lead time: <?= cbWholesaleText($row['lead_time']) ?>. <?php endif; ?>
              <?php if (!empty($row['pack_down_note'])): ?>Pack-down: <?= cbWholesaleText($row['pack_down_note']) ?>. <?php elseif ((float)($row['pack_down_fee'] ?? 0) > 0): ?>Pack-down fee is calculated against the actual requested packs/units. <?php endif; ?>
              <?= cbWholesaleText($row['description']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer-note">
    Prices are intended for <?= cbWholesaleText($validMonth) ?> and may change without notice due to supplier costs, stock refills and seasonal availability.
    Confirm stock, packing, delivery and lead time before payment. Bulk order delivery is confirmed by quote rather than retail free-shipping rules.
  </div>
</body>
</html>
