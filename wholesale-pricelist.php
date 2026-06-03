<?php
include 'session_logins.php';
require_once __DIR__ . '/wholesale_pricelist_helpers.php';

$wholesaleRows = getCandybirdWholesaleRows();
$rowsByCategory = getCandybirdWholesaleRowsByCategory();
$rowCount = count($wholesaleRows);
$updatedAt = date('d M Y');
$validMonth = date('F Y');
$limitedDescription = 'CandyBird wholesale and bulk pricelist for resellers, food service, gifting buyers and larger repeat orders.';
$page_url_canonical = 'https://www.candybird.co.za/wholesale-pricelist';
$title_og = 'Wholesale Pricelist - CandyBird';
$page_url_og = $page_url_canonical;
$description_og = $limitedDescription;
$description_meta = $limitedDescription;
$whatsappDigits = preg_replace('/\D+/', '', (string) ($hotline ?? $tel ?? ''));
if (strpos($whatsappDigits, '0') === 0) {
    $whatsappDigits = '27' . substr($whatsappDigits, 1);
}
$whatsappMessage = rawurlencode('Assalamu alaikum / Hello CandyBird, please send me the current wholesale pricelist and help me with a bulk order.');

include 'header.php';
include 'page_menues.php';
?>

<title>Wholesale Pricelist - <?= cbWholesaleText($website_company_name ?? 'CandyBird') ?></title>

<style>
  .wholesale-price-page { background:#f7f4ef; color:#2c2926; padding:28px 0 48px; }
  .wholesale-hero { align-items:center; background:#2d1739; border-radius:8px; color:#fff; display:flex; gap:18px; justify-content:space-between; margin-bottom:14px; padding:22px; }
  .wholesale-hero h1 { color:#fcb42f; font-size:30px; margin:0 0 5px; }
  .wholesale-hero p { color:#f8ecff; font-size:14px; line-height:1.6; margin:0; max-width:760px; }
  .wholesale-actions { display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; }
  .wholesale-note { background:#fff; border:1px solid #eadfd2; border-radius:8px; display:grid; gap:9px 16px; grid-template-columns:repeat(3, minmax(0, 1fr)); margin-bottom:14px; padding:12px 14px; }
  .wholesale-note div { color:#51475a; font-size:13px; line-height:1.55; }
  .wholesale-calculator { background:#fff; border:1px solid #eadfd2; border-radius:8px; margin-bottom:14px; padding:18px; }
  .wholesale-calculator h2 { color:#5b1178; font-size:20px; margin:0 0 6px; }
  .wholesale-calculator p { color:#6d6270; font-size:13px; line-height:1.55; margin-bottom:14px; }
  .wholesale-calc-grid { display:grid; gap:12px; grid-template-columns:1.4fr .8fr .8fr; }
  .wholesale-calc-field label { color:#4b185f; display:block; font-size:12px; font-weight:800; margin-bottom:5px; text-transform:uppercase; }
  .wholesale-calc-field select,
  .wholesale-calc-field input { border:1px solid #decbe7; border-radius:6px; height:42px; padding:8px 10px; width:100%; }
  .wholesale-pack-table { border:1px solid #f0ebe4; border-radius:8px; margin-top:12px; overflow:hidden; }
  .wholesale-pack-row { align-items:end; display:grid; gap:10px; grid-template-columns:1fr 1fr 42px; padding:10px; }
  .wholesale-pack-row + .wholesale-pack-row { border-top:1px solid #f0ebe4; }
  .wholesale-pack-row button { align-items:center; background:#f6f1ea; border:1px solid #eadfd2; border-radius:6px; color:#5b1178; display:flex; height:42px; justify-content:center; width:42px; }
  .wholesale-calc-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
  .wholesale-calc-result { background:#fffaf2; border:1px solid #eadfd2; border-radius:8px; display:grid; gap:8px; grid-template-columns:repeat(4, minmax(0, 1fr)); margin-top:14px; padding:12px; }
  .wholesale-calc-result span { color:#6d6270; display:block; font-size:11px; font-weight:800; text-transform:uppercase; }
  .wholesale-calc-result strong { color:#2c2926; display:block; font-size:16px; margin-top:2px; }
  .wholesale-calc-message { color:#6d6270; font-size:12px; line-height:1.55; margin-top:10px; }
  .wholesale-shell { background:#fff; border:1px solid #eadfd2; border-radius:8px; overflow:hidden; }
  .wholesale-table { font-size:13px; margin:0; }
  .wholesale-table th { background:#f0e8f4; border-bottom:1px solid #decbe7; color:#4b185f; font-size:11px; padding:8px 10px; text-transform:uppercase; white-space:nowrap; }
  .wholesale-table td { border-top:1px solid #f0ebe4; padding:7px 10px; vertical-align:middle; }
  .wholesale-category td { background:#5b1178 !important; color:#fcb42f; font-weight:800; padding:7px 10px; }
  .wholesale-product { align-items:center; display:flex; gap:9px; min-width:230px; }
  .wholesale-product img { aspect-ratio:1; border:1px solid #eadfd2; border-radius:6px; object-fit:cover; width:42px; }
  .wholesale-product a { color:#2c2926; font-weight:800; text-decoration:none; }
  .wholesale-product a:hover { color:#6b0099; }
  .wholesale-price { color:#5b1178; font-weight:900; min-width:150px; }
  .wholesale-muted { color:#6d6270; font-size:12px; line-height:1.45; }
  .wholesale-footnote { color:#6d6270; font-size:12px; line-height:1.65; margin-top:14px; }
  @media (max-width:767px) {
    .wholesale-hero { align-items:flex-start; flex-direction:column; }
    .wholesale-actions { justify-content:flex-start; }
    .wholesale-note { grid-template-columns:1fr; }
    .wholesale-calc-grid,
    .wholesale-calc-result { grid-template-columns:1fr; }
    .wholesale-pack-row { grid-template-columns:1fr 1fr 42px; }
    .wholesale-table { font-size:12px; }
    .wholesale-table th, .wholesale-table td { padding:6px 7px; }
  }
</style>

<main class="wholesale-price-page">
  <div class="container">
    <div class="wholesale-hero">
      <div>
        <h1>CandyBird Wholesale Pricelist</h1>
        <p><?= number_format($rowCount) ?> bulk line<?= $rowCount === 1 ? '' : 's' ?> | Valid for <?= cbWholesaleText($validMonth) ?> | Updated <?= cbWholesaleText($updatedAt) ?>. Wholesale prices are for bulk planning and confirmed by quote before invoicing.</p>
      </div>
      <div class="wholesale-actions">
        <a href="wholesale-pricelist-download" class="btn btn-warning" target="_blank" rel="noopener noreferrer"><i class="fas fa-print mr-1"></i> Print / Save PDF</a>
        <a href="wholesale-pricelist-download?format=tsv" class="btn btn-light"><i class="fas fa-file-download mr-1"></i> TSV export</a>
        <?php if ($whatsappDigits !== ''): ?><a href="https://wa.me/<?= cbWholesaleText($whatsappDigits) ?>?text=<?= $whatsappMessage ?>" class="btn btn-success" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp mr-1"></i> Request quote</a><?php endif; ?>
      </div>
    </div>

    <div class="wholesale-note">
      <div><strong>Bulk use:</strong> suited to resellers, food service, gifting, offices and larger repeat buyers.</div>
      <div><strong>Pack-down:</strong> when shown, fees apply to the requested packing work/pack units, not only the bulk case size.</div>
      <div><strong>Final quote:</strong> stock, packing, delivery and lead time are confirmed before invoicing.</div>
    </div>

    <div class="wholesale-calculator">
      <h2>Quick Wholesale Estimate</h2>
      <p>Choose a wholesale line, add the pack sizes you want, and get a rough estimate before requesting a final quote.</p>
      <div class="wholesale-calc-grid">
        <div class="wholesale-calc-field">
          <label for="wholesale-calc-product">Product</label>
          <select id="wholesale-calc-product">
            <option value="">Select a wholesale product</option>
            <?php foreach ($wholesaleRows as $index => $row): ?>
              <option value="<?= (int) $index ?>"><?= cbWholesaleText($row['title'] . ' | ' . $row['size'] . ' | ' . cbWholesaleDisplayPrice($row)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="wholesale-calc-field">
          <label>Bulk line</label>
          <input type="text" id="wholesale-calc-bulk" value="" readonly>
        </div>
        <div class="wholesale-calc-field">
          <label>Pack-down fee</label>
          <input type="text" id="wholesale-calc-fee" value="" readonly>
        </div>
      </div>
      <div class="wholesale-pack-table" id="wholesale-pack-table"></div>
      <div class="wholesale-calc-actions">
        <button type="button" class="btn btn-light" id="wholesale-add-pack">Add another pack size</button>
        <button type="button" class="btn btn-dark" id="wholesale-calc-button">Calculate estimate</button>
        <?php if ($whatsappDigits !== ''): ?><a href="#" class="btn btn-success d-none" id="wholesale-calc-whatsapp" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp mr-1"></i> Send estimate on WhatsApp</a><?php endif; ?>
      </div>
      <div class="wholesale-calc-result" id="wholesale-calc-result">
        <div><span>Total requested</span><strong id="calc-total-kg">-</strong></div>
        <div><span>Bulk product</span><strong id="calc-product-cost">-</strong></div>
        <div><span>Pack-down</span><strong id="calc-pack-cost">-</strong></div>
        <div><span>Estimate</span><strong id="calc-total-cost">-</strong></div>
      </div>
      <div class="wholesale-calc-message" id="wholesale-calc-message">Estimate only. Final quote depends on stock, packaging, delivery and confirmation by CandyBird.</div>
    </div>

    <div class="wholesale-shell">
      <div class="table-responsive">
        <table class="table wholesale-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Product</th>
              <th>Bulk Size</th>
              <th>Wholesale Price</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rowsByCategory)): ?>
              <tr><td colspan="5">Wholesale prices are being updated. Please request the current list on WhatsApp.</td></tr>
            <?php endif; ?>
            <?php foreach ($rowsByCategory as $category => $rows): ?>
              <tr class="wholesale-category"><td colspan="5"><?= cbWholesaleText(function_exists('getCandybirdCategoryDisplayLabel') ? getCandybirdCategoryDisplayLabel($category) : $category) ?></td></tr>
              <?php foreach ($rows as $row): ?>
                <tr>
                  <td class="wholesale-muted"><?= cbWholesaleText($row['product_id']) ?></td>
                  <td>
                    <div class="wholesale-product">
                      <?php if (!empty($row['image'])): ?><img src="<?= cbWholesaleText($row['image']) ?>" alt="<?= cbWholesaleText($row['title']) ?>" onerror="this.src='assets/img/product/1.png'"><?php endif; ?>
                      <?php if (!empty($row['product_url'])): ?><a href="<?= cbWholesaleText($row['product_url']) ?>"><?= cbWholesaleText($row['title']) ?></a><?php else: ?><strong><?= cbWholesaleText($row['title']) ?></strong><?php endif; ?>
                    </div>
                  </td>
                  <td><?= cbWholesaleText($row['size']) ?></td>
                  <td class="wholesale-price"><?= cbWholesaleText(cbWholesaleDisplayPrice($row)) ?></td>
                  <td class="wholesale-muted">
                    <?php $retailComparison = cbWholesaleRetailComparison($row); ?>
                    <?php if ($retailComparison !== ''): ?><strong><?= cbWholesaleText($retailComparison) ?></strong>. <?php endif; ?>
                    <?php if (!empty($row['moq'])): ?><strong>MOQ:</strong> <?= cbWholesaleText($row['moq']) ?>. <?php endif; ?>
                    <?php if (!empty($row['lead_time'])): ?><strong>Lead time:</strong> <?= cbWholesaleText($row['lead_time']) ?>. <?php endif; ?>
                    <?php if (!empty($row['pack_down_note'])): ?><strong>Pack-down:</strong> <?= cbWholesaleText($row['pack_down_note']) ?>. <?php elseif ((float)($row['pack_down_fee'] ?? 0) > 0): ?><strong>Pack-down:</strong> fee is calculated against the actual requested packs/units. <?php endif; ?>
                    <?= cbWholesaleText($row['description']) ?>
                    <?php if (!empty($row['free_delivery_excluded']) && $row['free_delivery_excluded'] === 'yes'): ?><span class="d-block text-muted" style="font-size:11px;">Free shipping does not apply to this item.</span><?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <p class="wholesale-footnote">
      Prices are intended for <?= cbWholesaleText($validMonth) ?> and may change without notice where supplier costs, exchange rates, availability or stock refills change.
      Wholesale pricing is not displayed on individual product pages; product pages only indicate when an item has a bulk option.
    </p>
  </div>
</main>

<script>
(function() {
  var wholesaleRows = <?= json_encode($wholesaleRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var whatsappDigits = <?= json_encode($whatsappDigits) ?>;
  var defaultSizes = ['1kg', '500g', '340g', '100g', '29g'];

  function money(amount) {
    return 'R' + (parseFloat(amount) || 0).toFixed(2);
  }

  function sizeToKg(size) {
    var match = String(size || '').toLowerCase().match(/(\d+(?:[.,]\d+)?)\s*(kg|g)/);
    if (!match) return 0;
    var value = parseFloat(match[1].replace(',', '.')) || 0;
    return match[2] === 'kg' ? value : value / 1000;
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function(char) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
  }

  function selectedRow() {
    var index = document.getElementById('wholesale-calc-product').value;
    return index !== '' && wholesaleRows[index] ? wholesaleRows[index] : null;
  }

  function allowedSizes(row) {
    return row && Array.isArray(row.allowed_pack_sizes) && row.allowed_pack_sizes.length ? row.allowed_pack_sizes : defaultSizes;
  }

  function renderPackRow(sizeValue, qtyValue) {
    var row = selectedRow();
    var sizes = allowedSizes(row);
    var options = sizes.map(function(size) {
      return '<option value="' + escapeHtml(size) + '"' + (size === sizeValue ? ' selected' : '') + '>' + escapeHtml(size) + '</option>';
    }).join('');
    return '<div class="wholesale-pack-row">' +
      '<div class="wholesale-calc-field"><label>Pack size</label><select class="calc-pack-size">' + options + '</select></div>' +
      '<div class="wholesale-calc-field"><label>Number of packs</label><input type="number" min="0" step="1" class="calc-pack-qty" value="' + (qtyValue || 0) + '"></div>' +
      '<button type="button" class="calc-remove-pack" title="Remove pack size">×</button>' +
    '</div>';
  }

  function resetPackRows() {
    var row = selectedRow();
    var firstSize = allowedSizes(row)[0] || '1kg';
    document.getElementById('wholesale-pack-table').innerHTML = renderPackRow(firstSize, 0);
  }

  function updateSelectedInfo() {
    var row = selectedRow();
    document.getElementById('wholesale-calc-bulk').value = row ? (row.size || '') : '';
    document.getElementById('wholesale-calc-fee').value = row && parseFloat(row.pack_down_fee || 0) > 0 ? money(row.pack_down_fee) + ' per pack/unit' : 'No fee listed';
    resetPackRows();
  }

  function calculateEstimate() {
    var row = selectedRow();
    if (!row) {
      document.getElementById('wholesale-calc-message').textContent = 'Choose a product first.';
      return;
    }

    var totalKg = 0;
    var totalPacks = 0;
    var packSummary = [];
    document.querySelectorAll('.wholesale-pack-row').forEach(function(packRow) {
      var size = packRow.querySelector('.calc-pack-size').value;
      var qty = parseInt(packRow.querySelector('.calc-pack-qty').value, 10) || 0;
      var kg = sizeToKg(size) * qty;
      if (qty > 0 && kg > 0) {
        totalKg += kg;
        totalPacks += qty;
        packSummary.push(qty + ' x ' + size);
      }
    });

    var bulkKg = sizeToKg(row.size);
    var price = parseFloat(row.price || 0) || 0;
    var perKg = parseFloat(row.price_per_kg || 0) || 0;
    var packFee = parseFloat(row.pack_down_fee || 0) || 0;
    var casesNeeded = bulkKg > 0 && totalKg > 0 ? Math.ceil(totalKg / bulkKg) : 0;
    var productCost = casesNeeded > 0 && price > 0 ? casesNeeded * price : (perKg > 0 ? totalKg * perKg : price);
    var packCost = totalPacks * packFee;
    var total = productCost + packCost;
    var purchasedKg = casesNeeded > 0 ? casesNeeded * bulkKg : totalKg;
    var leftoverKg = Math.max(0, purchasedKg - totalKg);

    document.getElementById('calc-total-kg').textContent = totalKg.toFixed(2) + 'kg';
    document.getElementById('calc-product-cost').textContent = money(productCost);
    document.getElementById('calc-pack-cost').textContent = money(packCost);
    document.getElementById('calc-total-cost').textContent = money(total);

    var message = casesNeeded > 0
      ? 'Estimate uses ' + casesNeeded + ' bulk case' + (casesNeeded === 1 ? '' : 's') + ' of ' + row.size + '. Approx leftover/unpacked: ' + leftoverKg.toFixed(2) + 'kg.'
      : 'Estimate uses the listed per-kg/product price.';
    message += ' Final quote depends on stock, packaging, delivery and confirmation by CandyBird.';
    document.getElementById('wholesale-calc-message').textContent = message;

    var whatsapp = document.getElementById('wholesale-calc-whatsapp');
    if (whatsapp && whatsappDigits) {
      var text = 'Assalamu alaikum / Hello CandyBird, please quote this wholesale estimate:%0A' +
        'Product: ' + encodeURIComponent(row.title + ' | ' + row.size) + '%0A' +
        'Packing: ' + encodeURIComponent(packSummary.join(', ') || 'Not specified') + '%0A' +
        'Total requested: ' + encodeURIComponent(totalKg.toFixed(2) + 'kg') + '%0A' +
        'Estimate: ' + encodeURIComponent(money(total)) + '%0A' +
        'Note: ' + encodeURIComponent(message);
      whatsapp.href = 'https://wa.me/' + encodeURIComponent(whatsappDigits) + '?text=' + text;
      whatsapp.classList.remove('d-none');
    }
  }

  document.getElementById('wholesale-calc-product').addEventListener('change', updateSelectedInfo);
  document.getElementById('wholesale-add-pack').addEventListener('click', function() {
    var row = selectedRow();
    var firstSize = allowedSizes(row)[0] || '1kg';
    document.getElementById('wholesale-pack-table').insertAdjacentHTML('beforeend', renderPackRow(firstSize, 0));
  });
  document.getElementById('wholesale-pack-table').addEventListener('click', function(event) {
    if (!event.target.classList.contains('calc-remove-pack')) return;
    var rows = document.querySelectorAll('.wholesale-pack-row');
    if (rows.length > 1) {
      event.target.closest('.wholesale-pack-row').remove();
    }
  });
  document.getElementById('wholesale-calc-button').addEventListener('click', calculateEstimate);
  resetPackRows();
})();
</script>

<?php include 'footer.php'; ?>
