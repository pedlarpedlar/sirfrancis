<?php
include 'session_logins.php';
require_once __DIR__ . '/wholesale_pricelist_helpers.php';

$wholesaleRows = getCandybirdWholesaleRows();
$rowsByCategory = getCandybirdWholesaleRowsByCategory();
$rowCount = count($wholesaleRows);
$updatedAt = date('d M Y');
$validMonth = date('F Y');
$limitedDescription = 'Sir Francis wholesale and bulk pricelist for resellers, food service, gifting buyers and larger repeat orders.';
$page_url_canonical = 'https://sirfrancis.co.za/wholesale-pricelist';
$title_og = 'Wholesale Pricelist - Sir Francis';
$image_url_og = 'https://sirfrancis.co.za/assets/img/logo/main.png';
$image_type_og = 'image/png';
$image_width_og = '1200';
$image_height_og = '630';
$page_url_og = $page_url_canonical;
$description_og = $limitedDescription;
$description_meta = $limitedDescription;
$whatsappDigits = preg_replace('/\D+/', '', (string) ($hotline ?? $tel ?? ''));
if (strpos($whatsappDigits, '0') === 0) {
    $whatsappDigits = '27' . substr($whatsappDigits, 1);
}
$whatsappMessage = rawurlencode('Assalamu alaikum / Hello Sir Francis, please send me the current wholesale pricelist and help me with a bulk order.');

include 'header.php';
include 'page_menues.php';
?>

<title>Wholesale Pricelist - <?= cbWholesaleText($website_company_name ?? 'Sir Francis') ?></title>

<style>
  .wholesale-price-page { background:#f4eee2; color:#1d293c; padding:28px 0 48px; }
  .wholesale-hero { align-items:center; background:#0b2341; border:1px solid #c9b36d; box-shadow:inset 0 0 0 3px #0b2341, inset 0 0 0 4px rgba(201,179,109,.78); color:#fff; display:grid; gap:20px; grid-template-columns:minmax(0, 1fr) minmax(220px, 320px); margin-bottom:14px; padding:28px; }
  .wholesale-hero h1 { color:#d3bd75; font-family:"Playfair Display", Georgia, serif; font-size:34px; font-weight:700; letter-spacing:0; margin:0 0 6px; }
  .wholesale-hero p { color:#f5ead3; font-family:Raleway, Arial, sans-serif; font-size:14px; line-height:1.7; margin:0; max-width:760px; }
  .wholesale-hero-copy { min-width:0; }
  .wholesale-hero-mark { align-items:center; align-self:stretch; background:rgba(244,238,226,.08); border:1px solid rgba(201,179,109,.65); box-shadow:inset 0 0 0 3px rgba(11,35,65,.9), inset 0 0 0 4px rgba(201,179,109,.36); display:flex; justify-content:center; min-height:180px; padding:20px; }
  .wholesale-hero-logo { display:block; height:auto; max-height:170px; object-fit:contain; width:min(240px, 100%); }
  .wholesale-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:18px; }
  .wholesale-btn { align-items:center; background:#0b2341; border:1px solid #c9b36d; box-shadow:inset 0 0 0 2px #0b2341, inset 0 0 0 3px rgba(201,179,109,.8); color:#d3bd75 !important; display:inline-flex; font-family:Raleway, Arial, sans-serif; font-size:12px; font-weight:800; gap:6px; justify-content:center; min-height:38px; padding:9px 13px; text-decoration:none; text-transform:uppercase; }
  .wholesale-btn:hover { background:#122f52; color:#f5ead3 !important; text-decoration:none; }
  .wholesale-btn-light { background:#f4eee2; box-shadow:inset 0 0 0 2px #f4eee2, inset 0 0 0 3px rgba(201,179,109,.9); color:#0b2341 !important; }
  .wholesale-btn-whatsapp { background:#173f35; box-shadow:inset 0 0 0 2px #173f35, inset 0 0 0 3px rgba(201,179,109,.85); }
  .wholesale-top-grid { display:grid; gap:14px; grid-template-columns:minmax(0, 1.4fr) minmax(320px, .8fr); margin-bottom:14px; }
  .wholesale-note { display:grid; gap:10px; }
  .wholesale-note-card { background:#fffaf2; border:1px solid #d9c98a; color:#344154; font-family:Raleway, Arial, sans-serif; font-size:13px; line-height:1.55; padding:12px 14px; }
  .wholesale-note-card strong { color:#0b2341; }
  .wholesale-private-note { background:#fffaf2; border:1px solid #d9c98a; color:#344154; font-size:13px; line-height:1.6; margin-top:14px; padding:12px 14px; }
  .wholesale-calculator { background:#fffaf2; border:1px solid #d9c98a; padding:14px; }
  .wholesale-calculator h2 { color:#0b2341; font-family:"Playfair Display", Georgia, serif; font-size:19px; margin:0 0 5px; }
  .wholesale-calculator p { color:#526071; font-size:12px; line-height:1.45; margin-bottom:10px; }
  .wholesale-calc-grid { display:grid; gap:9px; grid-template-columns:1fr; }
  .wholesale-calc-field label { color:#0b2341; display:block; font-size:12px; font-weight:800; margin-bottom:5px; text-transform:uppercase; }
  .wholesale-calc-field select,
  .wholesale-calc-field input { background:#fff; border:1px solid #d9c98a; height:38px; padding:7px 9px; width:100%; }
  .wholesale-pack-table { border:1px solid #d9c98a; margin-top:12px; overflow:hidden; }
  .wholesale-pack-row { align-items:end; display:grid; gap:8px; grid-template-columns:1fr 86px 36px; padding:9px; }
  .wholesale-pack-row + .wholesale-pack-row { border-top:1px solid #e3d7a9; }
  .wholesale-pack-row button { align-items:center; background:#0b2341; border:1px solid #c9b36d; box-shadow:inset 0 0 0 2px #0b2341, inset 0 0 0 3px rgba(201,179,109,.85); color:#d3bd75; display:flex; height:38px; justify-content:center; width:36px; }
  .wholesale-calc-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
  .wholesale-calc-result { background:#f4eee2; border:1px solid #d9c98a; display:grid; gap:8px; grid-template-columns:repeat(2, minmax(0, 1fr)); margin-top:12px; padding:10px; }
  .wholesale-calc-result span { color:#526071; display:block; font-size:11px; font-weight:800; text-transform:uppercase; }
  .wholesale-calc-result strong { color:#0b2341; display:block; font-size:16px; margin-top:2px; }
  .wholesale-calc-message { color:#173f35; font-size:13px; font-weight:800; line-height:1.55; margin-top:10px; }
  .wholesale-shell { background:#fffaf2; border:1px solid #d9c98a; overflow:hidden; }
  .wholesale-table { font-size:13px; margin:0; }
  .wholesale-table th { background:#0b2341; border-bottom:1px solid #c9b36d; color:#d3bd75; font-size:11px; padding:8px 10px; text-transform:uppercase; white-space:nowrap; }
  .wholesale-table td { border-top:1px solid #eadfbd; padding:8px 10px; vertical-align:middle; }
  .wholesale-table tbody tr:not(.wholesale-category):nth-child(odd) td { background:#fffdf8; }
  .wholesale-category td { background:#102f52 !important; color:#d3bd75; font-family:"Playfair Display", Georgia, serif; font-weight:800; padding:8px 10px; }
  .wholesale-product { min-width:220px; }
  .wholesale-product a { color:#0b2341; font-weight:800; text-decoration:none; }
  .wholesale-product a:hover { color:#8b6f2b; }
  .wholesale-price { color:#0b2341; font-size:16px; font-weight:900; min-width:145px; }
  .wholesale-muted { color:#526071; font-size:12px; line-height:1.45; }
  .wholesale-mini { color:#526071; display:block; font-size:11px; line-height:1.45; margin-top:3px; }
  .wholesale-footnote { color:#526071; font-size:12px; line-height:1.65; margin-top:14px; }
  @media (max-width:767px) {
    .wholesale-hero { grid-template-columns:1fr; padding:20px; }
    .wholesale-hero-mark { min-height:150px; }
    .wholesale-top-grid { grid-template-columns:1fr; }
    .wholesale-calc-grid,
    .wholesale-calc-result { grid-template-columns:1fr; }
    .wholesale-pack-row { grid-template-columns:1fr 86px 36px; }
    .wholesale-table { font-size:12px; }
    .wholesale-table th, .wholesale-table td { padding:6px 7px; }
  }
</style>

<main class="wholesale-price-page">
  <div class="container">
    <div class="wholesale-hero">
      <div class="wholesale-hero-copy">
        <h1>Sir Francis Wholesale Pricelist</h1>
        <p><?= number_format($rowCount) ?> bulk line<?= $rowCount === 1 ? '' : 's' ?> | Valid for <?= cbWholesaleText($validMonth) ?> | Updated <?= cbWholesaleText($updatedAt) ?>. Wholesale prices are for bulk planning and confirmed by quote before invoicing.</p>
        <div class="wholesale-actions">
          <a href="wholesale-pricelist-download" class="wholesale-btn" target="_blank" rel="noopener noreferrer"><i class="fas fa-print mr-1"></i> Print / Save PDF</a>
          <a href="wholesale-pricelist-download?format=tsv" class="wholesale-btn wholesale-btn-light"><i class="fas fa-file-download mr-1"></i> TSV export</a>
          <?php if ($whatsappDigits !== ''): ?><a href="https://wa.me/<?= cbWholesaleText($whatsappDigits) ?>?text=<?= $whatsappMessage ?>" class="wholesale-btn wholesale-btn-whatsapp" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp mr-1"></i> Request quote</a><?php endif; ?>
        </div>
      </div>
      <div class="wholesale-hero-mark">
        <img class="wholesale-hero-logo" src="assets/img/logo/main.png" alt="Sir Francis" loading="lazy">
      </div>
    </div>

    <div class="wholesale-top-grid">
      <div class="wholesale-note">
        <div class="wholesale-note-card"><strong>Bulk use:</strong> suited to resellers, food service, gifting, offices and larger repeat buyers.</div>
        <div class="wholesale-note-card"><strong>Pack-down:</strong> when shown, fees apply to the requested packing work/pack units, not only the bulk case size.</div>
        <div class="wholesale-note-card"><strong>Delivery:</strong> wholesale orders are quoted with stock, packing, lead time and delivery confirmed before invoicing. Standard retail free-shipping rules may not apply to bulk lines.</div>
      </div>

      <div class="wholesale-calculator">
        <h2>Quick Estimate</h2>
        <p>Choose a product and packing option. The estimate updates automatically.</p>
        <div class="wholesale-calc-grid">
          <div class="wholesale-calc-field">
            <label for="wholesale-calc-product">Product</label>
            <select id="wholesale-calc-product">
              <option value="">Select a wholesale product</option>
              <?php foreach ($wholesaleRows as $index => $row): ?>
                <option value="<?= (int) $index ?>"><?= cbWholesaleText($row['title'] . ' | ' . $row['size']) ?></option>
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
          <button type="button" class="wholesale-btn wholesale-btn-light" id="wholesale-add-pack">Add pack size</button>
          <?php if ($whatsappDigits !== ''): ?><a href="#" class="wholesale-btn wholesale-btn-whatsapp d-none" id="wholesale-calc-whatsapp" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp mr-1"></i> Send estimate</a><?php endif; ?>
        </div>
        <div class="wholesale-calc-result" id="wholesale-calc-result">
          <div><span>Total requested</span><strong id="calc-total-kg">-</strong></div>
          <div><span>Bulk product</span><strong id="calc-product-cost">-</strong></div>
          <div><span>Pack-down</span><strong id="calc-pack-cost">-</strong></div>
          <div><span>Estimate</span><strong id="calc-total-cost">-</strong></div>
        </div>
        <div class="wholesale-calc-message" id="wholesale-calc-message">Select a product to see an estimate.</div>
      </div>
    </div>

    <div class="wholesale-shell">
      <div class="table-responsive">
        <table class="table wholesale-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Price / kg</th>
              <th>Pack-down</th>
              <th>MOQ</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rowsByCategory)): ?>
              <tr><td colspan="4">Wholesale prices are being updated. Please request the current list on WhatsApp.</td></tr>
            <?php endif; ?>
            <?php foreach ($rowsByCategory as $category => $rows): ?>
              <tr class="wholesale-category"><td colspan="4"><?= cbWholesaleText(cbWholesaleDisplayCategoryPath($category)) ?></td></tr>
              <?php foreach ($rows as $row): ?>
                <?php
                  $bulkKg = cbWholesaleSizeToKg($row['size'] ?? '');
                  $casePrice = (float) ($row['price'] ?? 0);
                  $pricePerKg = (float) ($row['price_per_kg'] ?? 0);
                  if ($pricePerKg <= 0 && $bulkKg > 0 && $casePrice > 0) {
                      $pricePerKg = $casePrice / $bulkKg;
                  }
                  $packDownFee = (float) ($row['pack_down_fee'] ?? 0);
                ?>
                <tr>
                  <td class="wholesale-product">
                    <?php if (!empty($row['product_url'])): ?><a href="<?= cbWholesaleText($row['product_url']) ?>"><?= cbWholesaleText($row['title']) ?></a><?php else: ?><strong><?= cbWholesaleText($row['title']) ?></strong><?php endif; ?>
                    <span class="wholesale-mini"><?= cbWholesaleText($row['size']) ?> bulk line<?= !empty($row['description']) ? ' | ' . cbWholesaleText($row['description']) : '' ?></span>
                    <?php if (!empty($row['lead_time'])): ?><span class="wholesale-mini">Lead time: <?= cbWholesaleText($row['lead_time']) ?></span><?php endif; ?>
                  </td>
                  <td>
                    <strong class="wholesale-price"><?= $pricePerKg > 0 ? cbWholesaleText(cbWholesaleFormatMoney($pricePerKg) . ' / kg') : 'By quote' ?></strong>
                    <?php if ($casePrice > 0): ?><span class="wholesale-mini">Works out to <?= cbWholesaleText(cbWholesaleFormatMoney($casePrice)) ?> per <?= cbWholesaleText($row['size']) ?></span><?php endif; ?>
                  </td>
                  <td class="wholesale-muted">
                    <?= $packDownFee > 0 ? cbWholesaleText(cbWholesaleFormatMoney($packDownFee) . ' per unit') : 'No listed fee' ?>
                    <?php if (!empty($row['pack_down_note'])): ?><span class="wholesale-mini"><?= cbWholesaleText($row['pack_down_note']) ?></span><?php endif; ?>
                  </td>
                  <td class="wholesale-muted">
                    <?= cbWholesaleText($row['moq'] ?: 'By quote') ?>
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
    <div class="wholesale-private-note">
      This wholesale pricelist is for items packed according to <?= cbWholesaleText($website_company_name ?? 'Sir Francis') ?> configuration sizes and branding. For private labelling with your own printed packaging, filling and supply, the minimum quantity is one full container, about 15 tons of product.
    </div>
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

  function normalizeSize(size) {
    return String(size || '').toLowerCase().replace(/\s+/g, '');
  }

  function packOptions(row) {
    var options = [];
    var seen = {};
    var bulkSize = row && row.size ? String(row.size) : '';

    function addOption(label, size, noPackFee) {
      var cleanSize = String(size || '').trim();
      if (!cleanSize || sizeToKg(cleanSize) <= 0) return;
      var key = normalizeSize(label + '|' + cleanSize);
      if (seen[key]) return;
      seen[key] = true;
      options.push({label: label, size: cleanSize, noPackFee: !!noPackFee});
    }

    if (bulkSize !== '') {
      addOption('Whole bulk pack / single bag (' + bulkSize + ')', bulkSize, true);
    }

    addOption('5kg bags', '5kg', false);
    allowedSizes(row).forEach(function(size) {
      if (normalizeSize(size) !== '5kg' && normalizeSize(size) !== normalizeSize(bulkSize)) {
        addOption(size, size, false);
      }
    });

    return options.length ? options : [{label: '1kg', size: '1kg', noPackFee: false}];
  }

  function renderPackRow(optionValue, qtyValue) {
    var row = selectedRow();
    var options = packOptions(row);
    var selectedIndex = parseInt(optionValue, 10);
    if (isNaN(selectedIndex) || !options[selectedIndex]) selectedIndex = 0;
    var optionHtml = options.map(function(option, index) {
      return '<option value="' + index + '"' + (index === selectedIndex ? ' selected' : '') + '>' + escapeHtml(option.label || option.size) + '</option>';
    }).join('');
    return '<div class="wholesale-pack-row">' +
      '<div class="wholesale-calc-field"><label>Pack option</label><select class="calc-pack-size">' + optionHtml + '</select></div>' +
      '<div class="wholesale-calc-field"><label>Number of packs</label><input type="number" min="0" step="1" class="calc-pack-qty" value="' + (qtyValue || 0) + '"></div>' +
      '<button type="button" class="calc-remove-pack" title="Remove pack size">×</button>' +
    '</div>';
  }

  function resetPackRows() {
    document.getElementById('wholesale-pack-table').innerHTML = renderPackRow(0, 1);
  }

  function updateSelectedInfo() {
    var row = selectedRow();
    document.getElementById('wholesale-calc-bulk').value = row ? (row.size || '') : '';
    document.getElementById('wholesale-calc-fee').value = row && parseFloat(row.pack_down_fee || 0) > 0 ? money(row.pack_down_fee) + ' per pack/unit' : 'No fee listed';
    resetPackRows();
    calculateEstimate();
  }

  function calculateEstimate() {
    var row = selectedRow();
    if (!row) {
      document.getElementById('calc-total-kg').textContent = '-';
      document.getElementById('calc-product-cost').textContent = '-';
      document.getElementById('calc-pack-cost').textContent = '-';
      document.getElementById('calc-total-cost').textContent = '-';
      document.getElementById('wholesale-calc-message').textContent = 'Select a product to see an estimate.';
      var hiddenWhatsapp = document.getElementById('wholesale-calc-whatsapp');
      if (hiddenWhatsapp) hiddenWhatsapp.classList.add('d-none');
      return;
    }

    var totalKg = 0;
    var totalPacks = 0;
    var packSummary = [];
    var options = packOptions(row);
    document.querySelectorAll('.wholesale-pack-row').forEach(function(packRow) {
      var optionIndex = parseInt(packRow.querySelector('.calc-pack-size').value, 10);
      var option = options[optionIndex] || options[0];
      var size = option.size;
      var qty = parseInt(packRow.querySelector('.calc-pack-qty').value, 10) || 0;
      var kg = sizeToKg(size) * qty;
      if (qty > 0 && kg > 0) {
        totalKg += kg;
        if (!option.noPackFee) {
          totalPacks += qty;
        }
        packSummary.push(qty + ' x ' + (option.label || size));
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
    message += ' Final quote depends on stock, packaging, delivery and confirmation by Sir Francis.';
    document.getElementById('wholesale-calc-message').textContent = message;

    var whatsapp = document.getElementById('wholesale-calc-whatsapp');
    if (whatsapp && whatsappDigits) {
      var text = 'Assalamu alaikum / Hello Sir Francis, please quote this wholesale estimate:%0A' +
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
    document.getElementById('wholesale-pack-table').insertAdjacentHTML('beforeend', renderPackRow(1, 0));
    calculateEstimate();
  });
  document.getElementById('wholesale-pack-table').addEventListener('click', function(event) {
    if (!event.target.classList.contains('calc-remove-pack')) return;
    var rows = document.querySelectorAll('.wholesale-pack-row');
    if (rows.length > 1) {
      event.target.closest('.wholesale-pack-row').remove();
      calculateEstimate();
    }
  });
  document.getElementById('wholesale-pack-table').addEventListener('input', calculateEstimate);
  document.getElementById('wholesale-pack-table').addEventListener('change', calculateEstimate);
  resetPackRows();
  calculateEstimate();
})();
</script>

<?php include 'footer.php'; ?>
