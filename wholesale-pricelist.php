<?php
include 'session_logins.php';
require_once __DIR__ . '/wholesale_pricelist_helpers.php';

$rowsByCategory = getCandybirdWholesaleRowsByCategory();
$rowCount = count(getCandybirdWholesaleRows());
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
      <div><strong>Pack-down:</strong> when shown, pack-down fees are separate from the bulk product price.</div>
      <div><strong>Final quote:</strong> stock, packing, delivery and lead time are confirmed before invoicing.</div>
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
                    <?php if (!empty($row['moq'])): ?><strong>MOQ:</strong> <?= cbWholesaleText($row['moq']) ?>. <?php endif; ?>
                    <?php if (!empty($row['lead_time'])): ?><strong>Lead time:</strong> <?= cbWholesaleText($row['lead_time']) ?>. <?php endif; ?>
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

<?php include 'footer.php'; ?>
