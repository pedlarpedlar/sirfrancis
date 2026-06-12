<?php
include 'session_logins.php';

$productPageId = trim((string) ($_GET['id'] ?? ''));
$productPageSlug = normalizeCandybirdProductSlug($_GET['slug'] ?? '');
$metaProduct = null;
if ($productPageId !== '') {
    if (stripos($productPageId, 'CLR:') === 0) {
        $metaProduct = buildCandybirdClearanceProduct(getSheetClearanceRowById(substr($productPageId, 4)));
    } else {
        $metaProduct = getSheetProductById($productPageId);
    }
} elseif ($productPageSlug !== '') {
    $metaProduct = getSheetProductBySlug($productPageSlug);
    if ($metaProduct && !empty($metaProduct['id'])) {
        $productPageId = (string) $metaProduct['id'];
    }
}
$reviewLoginRedirect = $metaProduct ? getSheetProductUrl($metaProduct) . '#pills-contact' : 'product#pills-contact';
$reviewLoginHref = 'login?redirect=' . rawurlencode($reviewLoginRedirect);
$productWholesaleWhatsappDigits = preg_replace('/\D+/', '', (string) (($hotline ?? '') ?: ($tel ?? '')));
if (strpos($productWholesaleWhatsappDigits, '0') === 0) {
    $productWholesaleWhatsappDigits = '27' . substr($productWholesaleWhatsappDigits, 1);
}

function cbProductMetaText($value, $limit = 180) {
    $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8')));
    if (strlen($text) > $limit) {
        $text = rtrim(substr($text, 0, $limit - 3)) . '...';
    }
    return $text;
}

function cbProductAbsoluteUrl($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return 'https://www.candybird.co.za/assets/img/product/1.png';
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    return 'https://www.candybird.co.za/' . ltrim($url, '/');
}

function cbProductSocialImageUrl($product) {
    $rawImage = trim((string) (
        $product['img_url']
        ?? $product['image_url']
        ?? $product['image_urls']
        ?? $product['image']
        ?? ''
    ));
    $productId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($product['id'] ?? 'product'));

    if ($rawImage === '') {
        return 'https://www.candybird.co.za/assets/img/product/1.png?v=' . rawurlencode($productId);
    }

    $firstImage = trim(explode(',', $rawImage)[0] ?? '');
    $absolute = cbProductAbsoluteUrl($firstImage);
    if (strpos($absolute, 'candybird.co.za/') !== false && strpos($absolute, '?') === false) {
        $absolute .= '?v=' . rawurlencode($productId);
    }

    return $absolute;
}

function cbProductSocialImageUrls($product) {
    $rawImage = trim((string) (
        $product['img_url']
        ?? $product['image_url']
        ?? $product['image_urls']
        ?? $product['image']
        ?? ''
    ));
    $productId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($product['id'] ?? 'product'));
    $urls = [];

    foreach (array_filter(array_map('trim', explode(',', $rawImage))) as $image) {
        $absolute = cbProductAbsoluteUrl($image);
        if (strpos($absolute, 'candybird.co.za/') !== false && strpos($absolute, '?') === false) {
            $absolute .= '?v=' . rawurlencode($productId);
        }
        $urls[] = $absolute;
    }

    $urls[] = 'https://www.candybird.co.za/assets/img/product/1.png?v=' . rawurlencode($productId);
    return array_values(array_unique(array_filter($urls)));
}

function cbProductSocialImageType($url) {
    $path = parse_url((string) $url, PHP_URL_PATH) ?: '';
    if (preg_match('/\.jpe?g$/i', $path)) return 'image/jpeg';
    if (preg_match('/\.webp$/i', $path)) return 'image/webp';
    if (preg_match('/\.gif$/i', $path)) return 'image/gif';
    if (preg_match('/\.png$/i', $path)) return 'image/png';
    return '';
}

$metaTitle = 'Product - CandyBird';
$metaDescription = 'Shop premium nuts, dried fruit, sweets and healthy snacks from CandyBird.';
$metaImage = 'https://www.candybird.co.za/assets/img/product/1.png';
$metaImages = [$metaImage];
$metaUrl = $metaProduct ? getSheetProductUrl($metaProduct, true) : 'https://www.candybird.co.za/product';

if ($metaProduct) {
    $metaProductTitle = getSheetProductDisplayTitle($metaProduct);
    $metaPrice = getSheetProductPrice($metaProduct);
    $metaTitle = trim($metaProductTitle . ' - R' . number_format($metaPrice, 2) . ' | CandyBird');
    $metaDescription = cbProductMetaText(($metaProduct['html_description'] ?? $metaProduct['description'] ?? '') ?: $metaProductTitle);
    $metaImages = cbProductSocialImageUrls($metaProduct);
    $metaImage = $metaImages[0] ?? $metaImage;
}

$page_url_canonical = $metaUrl;
$page_url_og = $metaUrl;
$title_og = $metaTitle;
$description_og = $metaDescription;
$description_meta = $metaDescription;
$image_url_og = $metaImage;
$image_type_og = cbProductSocialImageType($metaImage) ?: 'image/png';
$image_width_og = '1200';
$image_height_og = '630';
$og_type = 'product';

include 'header.php';
?>

<link rel="canonical" href="<?=htmlspecialchars($metaUrl, ENT_QUOTES, 'UTF-8')?>" id="canonical-link">
<meta name="description" content="<?=htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8')?>" id="meta-description">
<meta property="og:title" content="<?=htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8')?>" id="og-title">
<meta property="og:description" content="<?=htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8')?>" id="og-description">
<?php foreach ($metaImages as $imageIndex => $socialImage): ?>
<meta property="og:image" content="<?=htmlspecialchars($socialImage, ENT_QUOTES, 'UTF-8')?>"<?= $imageIndex === 0 ? ' id="og-image"' : '' ?>>
<meta property="og:image:secure_url" content="<?=htmlspecialchars($socialImage, ENT_QUOTES, 'UTF-8')?>">
<?php if (cbProductSocialImageType($socialImage) !== ''): ?>
<meta property="og:image:type" content="<?=cbProductSocialImageType($socialImage)?>">
<?php endif; ?>
<?php endforeach; ?>
<meta property="og:image:alt" content="<?=htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:url" content="<?=htmlspecialchars($metaUrl, ENT_QUOTES, 'UTF-8')?>" id="og-url">
<meta property="og:type" content="product">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?=htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8')?>">
<meta name="twitter:description" content="<?=htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8')?>">
<meta name="twitter:image" content="<?=htmlspecialchars($metaImage, ENT_QUOTES, 'UTF-8')?>">
<title id="page-title"><?=htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8')?></title>
<?php if ($metaProduct): ?>
<?php
    $productStockQty = function_exists('getSheetProductStockQty') ? getSheetProductStockQty($metaProduct) : null;
    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $metaProductTitle,
        'description' => $metaDescription,
        'image' => $metaImages,
        'sku' => (string) ($metaProduct['id'] ?? ''),
        'brand' => [
            '@type' => 'Brand',
            'name' => 'CandyBird',
        ],
        'category' => trim(implode(' > ', array_filter([
            $metaProduct['parent_category'] ?? '',
            $metaProduct['child_category_1'] ?? '',
            $metaProduct['child_category_2'] ?? '',
        ]))),
        'offers' => [
            '@type' => 'Offer',
            'url' => $metaUrl,
            'priceCurrency' => 'ZAR',
            'price' => number_format((float) $metaPrice, 2, '.', ''),
            'availability' => $productStockQty === 0 ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => [
                '@id' => 'https://www.candybird.co.za/#organization',
            ],
        ],
    ];
?>
<script type="application/ld+json"><?= json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>

<style>
  .product-gallery-main {
    background: #fff;
    border: 1px solid #eee7dc;
    border-radius: 8px;
    cursor: zoom-in;
    display: grid;
    min-height: 420px;
    overflow: hidden;
    place-items: center;
    padding: 8px;
    position: relative;
  }

  .product-gallery-main img {
    display: block;
    max-height: 520px;
    object-fit: contain;
    width: 100%;
    max-width: 100%;
  }

  .product-gallery-thumbs {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(auto-fill, minmax(78px, 1fr));
    margin-top: 12px;
  }

  .product-gallery-thumb {
    background: #fff;
    border: 1px solid #e8dfd4;
    border-radius: 6px;
    cursor: pointer;
    height: 82px;
    padding: 6px;
  }

  .product-gallery-thumb.active {
    border-color: #6b0099;
    box-shadow: 0 0 0 2px rgba(107, 0, 153, 0.12);
  }

  .product-gallery-thumb img {
    height: 100%;
    object-fit: contain;
    width: 100%;
  }

  .clearance-corner-flag {
    border-right: 118px solid transparent;
    border-top: 118px solid #d5001f;
    height: 0;
    left: 0;
    position: absolute;
    top: 0;
    width: 0;
    z-index: 6;
  }

  .clearance-corner-flag span {
    color: #fff;
    display: block;
    font-size: 10px;
    font-weight: 900;
    left: 5px;
    letter-spacing: 0;
    line-height: 1.12;
    position: absolute;
    text-align: center;
    text-transform: uppercase;
    top: -95px;
    transform: rotate(-45deg);
    width: 90px;
  }

  .sold-out-button {
    background: #8f8f8f;
    border: 1px solid #8f8f8f;
    color: #fff;
    cursor: not-allowed;
    font-weight: 800;
    letter-spacing: .02em;
    padding: 10px 16px;
    text-align: center;
    text-transform: uppercase;
  }

  .clearance-price-note {
    background: #fff6f4;
    border: 1px solid #f0b4ac;
    border-left: 5px solid #d5001f;
    border-radius: 8px;
    color: #552017;
    font-size: 14px;
    font-weight: 800;
    line-height: 1.45;
    margin: -14px 0 18px;
    padding: 12px 14px;
  }

  .clearance-price-note span {
    color: #d5001f;
  }

  .product-image-lightbox {
    align-items: center;
    background: rgba(0, 0, 0, 0.84);
    display: none;
    inset: 0;
    justify-content: center;
    padding: 28px;
    position: fixed;
    z-index: 9999;
  }

  .product-image-lightbox.open {
    display: flex;
  }

  .product-image-lightbox img {
    background: #fff;
    border-radius: 8px;
    max-height: 92vh;
    max-width: 94vw;
    object-fit: contain;
    padding: 10px;
  }

  .product-image-lightbox button {
    background: #fff;
    border: 0;
    border-radius: 50%;
    font-size: 28px;
    height: 42px;
    line-height: 1;
    position: absolute;
    right: 20px;
    top: 20px;
    width: 42px;
  }

  .tab-content {
    color: black;
  }

  .tab-content .single-description ul li {
    list-style-type: disc !important;
  }

  .tab-content .single-description ol li {
    list-style-type: decimal !important;
  }

  .product-size select {
    min-width: 180px;
  }

  .product-page-state {
    padding: 80px 0;
    text-align: center;
  }

  .product-size-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .product-size-option {
    border: 1px solid #ddd4c8;
    border-radius: 6px;
    color: #2c2926;
    display: inline-flex;
    align-items: center;
    min-height: 42px;
    padding: 8px 12px;
  }

  .product-size-option.active,
  .product-size-option:hover {
    border-color: #6b0099;
    color: #6b0099;
    box-shadow: 0 0 0 2px rgba(107, 0, 153, 0.1);
  }

  .product-info-panel {
    background: #fffaf2;
    border: 1px solid #ece4d8;
    border-radius: 8px;
    padding: 22px;
  }

  .product-description-panel {
    background: #fff;
    border: 1px solid #eee7dc;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(44, 41, 38, 0.07);
    padding: 28px;
  }

  .product-info-panel h1,
  .product-info-panel h2,
  .product-info-panel h3,
  .product-description-panel h1,
  .product-description-panel h2,
  .product-description-panel h3 {
    font-size: 1.25rem;
    margin-bottom: 12px;
  }

  .product-info-panel p,
  .product-description-panel p {
    line-height: 1.75;
    margin-bottom: 14px;
  }

  .product-description-panel ul,
  .product-description-panel ol {
    margin-bottom: 16px;
    padding-left: 22px;
  }

  .product-description-panel img {
    height: auto;
    max-width: 100%;
  }

  .product-review-login {
    background: #fffaf2;
    border: 1px solid #ece4d8;
    border-radius: 8px;
    padding: 18px;
  }

  .review-actions {
    display: flex;
    gap: 8px;
    margin-top: 10px;
  }

  .review-actions button {
    background: transparent;
    border: 0;
    color: #6b0099;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
  }

  .pair-product-card {
    height: 100%;
  }

  .pair-product-card img {
    aspect-ratio: 1 / 1;
    object-fit: cover;
    width: 100%;
  }

  .pair-product-card .pair-price {
    color: #1d7d38;
    font-weight: 800;
  }

  .pair-product-card .pair-price del {
    color: #8a7d8f;
    display: inline-block;
    font-size: 12px;
    font-weight: 600;
    margin-right: 4px;
  }

  .badge.badge-warning.position-static {
    color: #2c2926;
  }

  .star-on.de-selected i,
  .rating-product .de-selected i,
  .star-content .de-selected i {
    color: #d7d0c6 !important;
  }

  .product-special-window {
    align-items: center;
    color: #7a2d00;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    margin-top: -18px;
    margin-bottom: 18px;
  }

  .product-special-countdown {
    background: #fff1df;
    border: 1px solid #f2c18d;
    border-radius: 999px;
    color: #7a2d00;
    display: inline-flex;
    line-height: 1;
    padding: 6px 10px;
  }

  .product-special-countdown.urgent {
    background: #b00020;
    border-color: #b00020;
    color: #fff;
  }

  .lead-time-required {
    background: #fff4d6;
    border: 1px solid #e8b23c;
    border-radius: 6px;
    color: #6f4a00;
    display: inline-flex;
    font-weight: 700;
    line-height: 1.25;
    padding: 8px 10px;
  }

  .product-payment-methods {
    background: #fff;
    border: 1px solid #eadfd4;
    border-radius: 8px;
    box-shadow: 0 8px 22px rgba(55, 38, 22, .06);
    margin: 18px 0 8px;
    padding: 14px;
  }

  .product-payment-methods__title {
    align-items: center;
    color: #251810;
    display: flex;
    font-size: 13px;
    font-weight: 800;
    gap: 8px;
    line-height: 1.3;
    margin: 0 0 10px;
  }

  .product-payment-methods__grid {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
  }

  .payment-method-logo {
    align-items: center;
    background: #fff;
    border: 1px solid #eee5dc;
    border-radius: 6px;
    display: inline-flex;
    height: 34px;
    justify-content: center;
    min-width: 58px;
    padding: 5px 9px;
  }

  .payment-method-logo img {
    display: block;
    max-height: 21px;
    max-width: 74px;
    object-fit: contain;
  }

  .payment-method-logo span {
    color: #1d1510;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .01em;
    line-height: 1;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .payment-method-logo--wide {
    min-width: 82px;
  }

  .payment-method-logo--soon {
    background: #faf7f3;
  }

  .payment-method-logo--soon span {
    color: #6a287e;
    text-transform: none;
  }

  .product-payment-methods__note {
    color: #7a6a5c;
    font-size: 12px;
    line-height: 1.4;
    margin: 10px 0 0;
  }

  .product-page-state {
    min-height: 680px;
  }

  .product-page-state .container {
    align-items: center;
    display: flex;
    min-height: 620px;
  }

  .product-page-state p {
    background: #fff7ef;
    border: 1px solid #ead7c9;
    border-radius: 6px;
    color: #4d2620;
    font-weight: 800;
    margin: 0;
    padding: 14px 16px;
    width: 100%;
  }

  .product-wholesale-note {
    background: #fffaf0;
    border: 1px solid #ecd5a1;
    border-radius: 8px;
    color: #4b3820;
    font-size: 13px;
    line-height: 1.5;
    margin: -4px 0 18px;
    padding: 11px 13px;
  }

  .product-wholesale-note strong {
    color: #5b1178;
  }

  .product-wholesale-note a {
    color: #1d6f37;
    font-weight: 800;
    text-decoration: underline;
  }

  .product-free-delivery-note {
    color: #8a8178;
    display: inline-block;
    font-size: 12px;
    line-height: 1.4;
    margin-top: 6px;
  }

  @media (max-width: 575px) {
    .product-payment-methods {
      padding: 12px;
    }

    .payment-method-logo {
      height: 32px;
      min-width: 52px;
      padding: 5px 7px;
    }

    .payment-method-logo img {
      max-height: 19px;
      max-width: 64px;
    }
  }
</style>

<?php include 'page_menues.php'; ?>

<?= renderCandybirdSiteFlags('product') ?>

<div id="breadcrumb-container"></div>

<div id="product-page-state" class="product-page-state">
  <div class="container">
    <p>Loading product...</p>
  </div>
</div>

<section class="product-single theme1 d-none" id="product-detail-section">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 mb-5 mb-lg-0">
        <div>
          <div class="position-relative" id="product-label-container"></div>
          <div class="product-sync-init mb-20" id="product-images-container"></div>
        </div>
        <div class="product-sync-nav single-product" id="product-thumbs-container"></div>
      </div>

      <div class="col-lg-6">
        <div class="single-product-info">
          <div class="single-product-head">
            <h2 class="title mb-20" id="product-title"></h2>
            <div class="star-content mb-20" id="product-stars"></div>
          </div>

          <div class="product-body mb-40">
            <div id="price-section"></div>
            <div id="product-short-description"></div>
            <a href="#pills-home" class="read-more-link d-none" id="read-more-link">(...Read more)</a>
          </div>

          <div class="d-flex mt-30">
            <div class="product-size mr-5" id="product-selection"></div>
          </div>

          <div class="product-footer">
            <div class="product-count style d-flex flex-column flex-sm-row my-4">
              <div class="count d-flex">
                <input type="number" min="1" max="999" step="1" value="1" class="add-to-cart-quantity" />
                <div class="button-group">
                  <button class="count-btn increment" type="button">
                    <i class="fas fa-chevron-up"></i>
                  </button>
                  <button class="count-btn decrement" type="button">
                    <i class="fas fa-chevron-down"></i>
                  </button>
                </div>
              </div>

              <div>
                <button type="button"
                        class="btn btn-dark btn--xl mt-5 mt-sm-0 add-to-cart"
                        data-toggle="modal"
                        data-target="#add-to-cart"
                        data-quantity="1"
                        id="add-to-cart-btn">
                  <span class="mr-2"><i class="ion-android-add"></i></span>
                  Add to cart
                </button>
              </div>
            </div>

            <div class="addto-whish-list">
              <a href="#" class="add-to-wishlist" id="wishlist-link"><i class="icon-heart"></i> Add to wishlist</a>
              <a href="#" class="add-to-compare" id="compare-link"><i class="icon-shuffle"></i> Add to compare</a>
            </div>

            <div class="product-payment-methods" aria-label="Secure checkout payment methods">
              <p class="product-payment-methods__title"><i class="fas fa-lock"></i> Secure checkout powered by PayFast</p>
              <div class="product-payment-methods__grid">
                <span class="payment-method-logo" title="Visa">
                  <img src="https://cdn.simpleicons.org/visa/1a1f71" alt="Visa" width="72" height="24" loading="lazy" onerror="this.remove(); this.parentElement.insertAdjacentHTML('beforeend','<span>Visa</span>');">
                </span>
                <span class="payment-method-logo" title="Mastercard">
                  <img src="https://cdn.simpleicons.org/mastercard/eb001b" alt="Mastercard" width="72" height="24" loading="lazy" onerror="this.remove(); this.parentElement.insertAdjacentHTML('beforeend','<span>Mastercard</span>');">
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="Apple Pay">
                  <img src="https://cdn.simpleicons.org/applepay/000000" alt="Apple Pay" width="92" height="24" loading="lazy" onerror="this.remove(); this.parentElement.insertAdjacentHTML('beforeend','<span>Apple Pay</span>');">
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="Google Pay">
                  <img src="https://cdn.simpleicons.org/googlepay/4285F4" alt="Google Pay" width="92" height="24" loading="lazy" onerror="this.remove(); this.parentElement.insertAdjacentHTML('beforeend','<span>Google Pay</span>');">
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="Samsung Pay">
                  <img src="https://cdn.simpleicons.org/samsungpay/1428A0" alt="Samsung Pay" width="92" height="24" loading="lazy" onerror="this.remove(); this.parentElement.insertAdjacentHTML('beforeend','<span>Samsung Pay</span>');">
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="PayFast">
                  <span>PayFast</span>
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="Instant EFT">
                  <span>Instant EFT</span>
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="SnapScan">
                  <span>SnapScan</span>
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="Zapper">
                  <span>Zapper</span>
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="Scan to Pay">
                  <span>Scan to Pay</span>
                </span>
                <span class="payment-method-logo payment-method-logo--wide" title="Ozow Instant EFT">
                  <span>Ozow EFT</span>
                </span>
              </div>
              <p class="product-payment-methods__note">Available options may depend on the device and PayFast checkout screen.</p>
            </div>

            <div class="pro-social-links mt-10">
              <ul class="d-flex align-items-center">
                <li class="share">Share</li>
                <li><a href="#" class="share-link-click" id="facebook-share" target="_blank" rel="noopener noreferrer"><i class="ion-social-facebook"></i></a></li>
                <li><a href="#" class="share-link-click" id="twitter-share" target="_blank" rel="noopener noreferrer"><i class="ion-social-twitter"></i></a></li>
                <li><a href="#" class="share-link-click" id="pinterest-share" target="_blank" rel="noopener noreferrer"><i class="ion-social-pinterest"></i></a></li>
                <li><a href="#" id="copy-product-link" title="Copy product link"><i class="ion-link"></i></a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="product-image-lightbox" id="product-image-lightbox" aria-hidden="true">
  <button type="button" id="product-image-lightbox-close" aria-label="Close image">&times;</button>
  <img src="assets/img/product/1.png" alt="">
</div>

<div class="product-tab theme1 bg-white pt-60 pb-80 d-none" id="product-tabs-section">
  <div class="container">
    <div class="product-tab-nav">
      <div class="row align-items-center">
        <div class="col-12">
          <nav class="product-tab-menu single-product">
            <ul class="nav nav-pills justify-content-center" id="pills-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">Description</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="pills-profile-tab" data-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="false">Product Details</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="pills-contact-tab" data-toggle="pill" href="#pills-contact" role="tab" aria-controls="pills-contact" aria-selected="false">Reviews</a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="tab-content" id="pills-tabContent">
          <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
            <div class="single-product-desc single-description product-description-panel" id="product-full-description"></div>
          </div>

          <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
            <div class="single-product-desc">
              <div class="product-anotherinfo-wrapper">
                <ul id="product-details-list"></ul>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
            <div class="single-product-desc">
              <div class="row">
                <div class="col-lg-7">
                  <div class="review-wrapper" id="reviews-list">Be the first to review!</div>
                </div>
                <div class="col-lg-5">
                    <div class="product-review-login <?=empty($_SESSION['user_id']) ? '' : 'd-none'?>" id="review-login-message">
                      <h3>Want to leave a review?</h3>
                      <p class="mb-3">Please log in first so ratings stay genuine and attached to real CandyBird customers.</p>
                      <a href="<?=htmlspecialchars($reviewLoginHref, ENT_QUOTES, 'UTF-8')?>" class="btn btn-dark btn--md">Log in to review</a>
                    </div>
                    <div class="ratting-form-wrapper <?=empty($_SESSION['user_id']) ? 'd-none' : ''?>" id="review-form-wrapper">
                    <h3>Add a Review</h3>
                    <div class="ratting-form">
                      <form id="ratingForm" action="#" method="post">
                        <input type="hidden" name="rating" id="rating-input" value="5" required>
                        <input type="hidden" name="product_id" id="review-product-id" value="" required>
                        <input type="hidden" name="review_id" id="review-id" value="">
                        <div class="star-box">
                          <span>Your rating:</span>
                          <div class="rating-product-custom" style="cursor:pointer;">
                            <span class="star-on" data-rating="1" role="button" tabindex="0" aria-label="1 star"><i style="color: gold" class="ion-ios-star"></i></span>
                            <span class="star-on" data-rating="2" role="button" tabindex="0" aria-label="2 stars"><i style="color: gold" class="ion-ios-star"></i></span>
                            <span class="star-on" data-rating="3" role="button" tabindex="0" aria-label="3 stars"><i style="color: gold" class="ion-ios-star"></i></span>
                            <span class="star-on" data-rating="4" role="button" tabindex="0" aria-label="4 stars"><i style="color: gold" class="ion-ios-star"></i></span>
                            <span class="star-on" data-rating="5" role="button" tabindex="0" aria-label="5 stars"><i style="color: gold" class="ion-ios-star"></i></span>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="rating-form-style mb-10">
                              <input placeholder="Display name e.g. A.K." type="text" name="name" value="<?=htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8')?>" maxlength="80" required />
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="rating-form-style mb-10">
                              <input placeholder="Email" type="email" name="email" value="<?=htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8')?>" readonly required />
                            </div>
                          </div>
                          <div class="col-md-12">
                            <div class="rating-form-style form-submit">
                              <textarea name="review" placeholder="Message"></textarea>
                              <input type="submit" id="review-submit-button" value="Submit" />
                            </div>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<section class="product-tab bg-white pt-30 pb-30">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="section-title text-center mb-30">
          <h2 class="title">Pair this with...</h2>
        </div>
      </div>
      <div class="col-12">
        <div class="row" id="paired-products"></div>
      </div>
    </div>
  </div>
</section>

<script src="assets/js/vendor/jquery-3.5.1.min.js"></script>
<script src="assets/js/plugins/plugins.js"></script>

<script>
$(function() {
  let ALL_PRODUCTS = [];
  let specialCountdownTimer = null;
  const urlParams = new URLSearchParams(window.location.search);
  const serverProductID = <?=json_encode((string) $productPageId)?>;
  const serverProductSlug = <?=json_encode((string) $productPageSlug)?>;
  const productID = String(urlParams.get('id') || serverProductID || '').trim();
  const productSlug = String(urlParams.get('slug') || serverProductSlug || '').trim();
  const defaultImage = 'assets/img/product/1.png';
  const imageFallback = ' onerror="this.onerror=null;this.src=\'' + defaultImage + '\';"';
  const wholesaleWhatsappDigits = <?=json_encode($productWholesaleWhatsappDigits)?>;

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function(char) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char];
    });
  }

  function stripHtml(value) {
    return $('<div>').html(value || '').text();
  }

  function safeSheetHtml(value) {
    const $wrap = $('<div>').html(value || '');
    $wrap.find('script, iframe, object, embed, style').remove();
    $wrap.find('*').each(function() {
      $.each(this.attributes, function() {
        if (!this) return;
        const attrName = this.name.toLowerCase();
        const attrValue = String(this.value || '').trim().toLowerCase();
        if (attrName.indexOf('on') === 0 || attrValue.indexOf('javascript:') === 0) {
          $(this.ownerElement).removeAttr(this.name);
        }
      });
    });
    return $wrap.html();
  }

  function firstProductStockValue(product) {
    const fields = ['qty_available', 'stock_qty', 'qty_in_stock', 'quantity_available', 'available_qty', 'inventory', 'stock'];
    for (let i = 0; i < fields.length; i++) {
      if (Object.prototype.hasOwnProperty.call(product || {}, fields[i])) {
        const value = product[fields[i]];
        if (value !== null && value !== undefined && String(value).trim() !== '') {
          return value;
        }
      }
    }
    return '';
  }

  function normalizeProduct(product) {
    const price = parseFloat(product.price) || 0;
    const isClearance = String(product.is_clearance || '').toLowerCase() === 'yes';
    const specialActive = isProductSpecialActive(product);
    const rawDiscountValue = parseFloat(product.discount || product.discount_amount || 0) || 0;
    const rawDiscountRate = parseFloat(product.discount_rate || 0) || 0;
    const discountValue = (specialActive || isClearance) ? rawDiscountValue : 0;
    const discountRate = (specialActive || isClearance) ? rawDiscountRate : 0;
    const rawDiscountedPrice = parseFloat(product.discounted_price);
    const discountedPrice = !isNaN(rawDiscountedPrice) && rawDiscountedPrice > 0
      ? rawDiscountedPrice
      : (discountValue > 0 ? price - discountValue : (discountRate > 0 ? price - (price * discountRate / 100) : price));

    return {
      id: String(product.id || '').trim(),
      name: product.name || product.title || '',
      size: product.size || product.weight || '',
      price: price,
      discount: discountValue,
      discountRate: discountRate,
      discountedPrice: (specialActive || isClearance) ? discountedPrice : price,
      description: safeSheetHtml(product.html_description || product.description || ''),
      shortDescription: product.short_description || product.description || stripHtml(product.html_description || ''),
      images: String(product.img_url || product.image_url || product.image_urls || '').split(',').map(function(img) {
        return img.trim();
      }).filter(Boolean),
      rating: parseFloat(product.rating) || 0,
      reviewCount: parseInt(product.review_count || 0, 10) || 0,
      label: product.label || '',
      parentCategory: product.parent_category || '',
      childCategory1: product.child_category_1 || '',
      childCategory2: product.child_category_2 || '',
      dimensions: product.dimensions || '',
      otherInfo: product.other_info || '',
      disclaimers: product.disclaimers || product.disclaimer || product.product_disclaimers || '',
      productType: product.product_type || product.type || product.delivery_type || '',
      stockQty: firstProductStockValue(product),
      leadTime: product.lead_time || product.leadtime || product.preparation_time || '',
      freeDeliveryExcluded: String(product.free_delivery_excluded || product.free_shipping_excluded || product.exclude_free_delivery || '').trim().toLowerCase(),
      slug: product.slug || '',
      is_clearance: product.is_clearance || '',
      clearance_id: product.clearance_id || '',
      source_product_id: product.source_product_id || '',
      raw: product
    };
  }

  function parseSpecialDate(value, endOfDay) {
    value = String(value || '').trim();
    if (!value) return null;
    const hasTime = /\d{1,2}:\d{2}/.test(value);
    const match = value.match(/^(\d{1,2})[-/](\d{1,2})[-/](\d{4})(?:\s+(\d{1,2}):(\d{2}))?$/);
    if (match) {
      const date = new Date(+match[3], +match[2] - 1, +match[1], +(match[4] || 0), +(match[5] || 0), 0);
      if (endOfDay && !hasTime) date.setHours(23, 59, 59, 999);
      return date;
    }
    const parsed = new Date(value);
    if (isNaN(parsed.getTime())) return null;
    if (endOfDay && !hasTime) parsed.setHours(23, 59, 59, 999);
    return parsed;
  }

  function isProductSpecialActive(product) {
    const from = parseSpecialDate(product.discount_valid_from || product.special_valid_from || product.sale_valid_from, false);
    const until = parseSpecialDate(product.discount_valid_until || product.special_valid_until || product.sale_valid_until, true);
    const now = new Date();
    if (from && now < from) return false;
    if (until && now > until) return false;
    return true;
  }

  function formatSpecialDate(value) {
    const date = parseSpecialDate(value, true);
    if (!date) return '';
    return date.toLocaleDateString('en-ZA', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  }

  function formatCountdown(milliseconds) {
    if (milliseconds <= 0) return 'ending now';
    const totalMinutes = Math.floor(milliseconds / 60000);
    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const minutes = totalMinutes % 60;

    if (days > 0) {
      return 'ends in ' + days + ' day' + (days === 1 ? '' : 's') + (hours > 0 ? ' ' + hours + 'h' : '');
    }

    if (hours > 0) {
      return 'ends in ' + hours + 'h ' + minutes + 'm';
    }

    return 'ends in ' + Math.max(1, minutes) + 'm';
  }

  function startSpecialCountdown(untilValue) {
    if (specialCountdownTimer) {
      clearInterval(specialCountdownTimer);
      specialCountdownTimer = null;
    }

    const endDate = parseSpecialDate(untilValue, true);
    const $badge = $('#product-special-countdown');
    if (!endDate || !$badge.length || endDate.getTime() <= Date.now()) {
      $badge.removeClass('urgent').text('');
      return;
    }

    function tick() {
      const remaining = endDate.getTime() - Date.now();
      if (remaining <= 0) {
        $badge.removeClass('urgent').text('');
        clearInterval(specialCountdownTimer);
        specialCountdownTimer = null;
        return;
      }

      $badge
        .toggleClass('urgent', remaining <= 12 * 60 * 60 * 1000)
        .text(formatCountdown(remaining));
    }

    tick();
    specialCountdownTimer = setInterval(tick, 60000);
  }

  function getSalePercent(product) {
    if (!product || product.price <= 0 || product.discountedPrice >= product.price) return 0;
    return Math.round(((product.price - product.discountedPrice) / product.price) * 100);
  }

  function productMatchesTag(product, tag) {
    const text = String([product.label, product.raw && product.raw.tags, product.raw && product.raw.tag].join(' ')).toLowerCase();
    return text.indexOf(tag) !== -1;
  }

  function isDigitalProduct(product) {
    const size = String(product.size || '').trim().toLowerCase();
    const type = String(product.productType || '').trim().toLowerCase();
    const text = [product.name, product.parentCategory, product.childCategory1, product.childCategory2].join(' ').toLowerCase();
    return size === '0' || size === '0g' || size === '0 g' || size === '0kg' || size === '0 kg' ||
      type.includes('digital') || type.includes('ebook') || type.includes('e-book') || type.includes('voucher') ||
      text.includes('voucher') || text.includes('ebook') || text.includes('e-book');
  }

  function displaySize(product) {
    return isDigitalProduct(product) ? '' : String(product.size || '').trim();
  }

  function displayTitle(product) {
    const size = displaySize(product);
    return product.name + (size ? ' ' + size : '');
  }

  function getProductUrl(productId) {
    const product = ALL_PRODUCTS.map(normalizeProduct).find(function(item) {
      return item.id === String(productId);
    });
    if (product && product.slug) {
      return 'https://www.candybird.co.za/' + encodeURIComponent(product.slug);
    }
    return 'https://www.candybird.co.za/product?id=' + encodeURIComponent(productId);
  }

  function getProductPath(product) {
    if (product && product.slug) {
      return encodeURIComponent(product.slug);
    }

    const isClearance = product && String(product.is_clearance || product.raw?.is_clearance || '').toLowerCase() === 'yes';
    if (isClearance) {
      const name = String(product.name || '').replace(/\bclearance\b/ig, '');
      const text = [name, displaySize(product), 'clearance'].join(' ');
      const slug = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
      if (slug) return encodeURIComponent(slug);
    }

    return product ? 'product?id=' + encodeURIComponent(product.id) : 'products';
  }

  function convertToStars(rating) {
    let html = '';
    const full = Math.floor(rating);
    const half = rating % 1 !== 0;

    for (let i = 0; i < full; i++) {
      html += '<span class="star-on"><i class="ion-ios-star"></i></span> ';
    }

    if (half) {
      html += '<span class="star-on"><i class="ion-ios-star-half"></i></span> ';
    }

    for (let j = full + (half ? 1 : 0); j < 5; j++) {
      html += '<span class="star-on de-selected"><i class="ion-ios-star"></i></span> ';
    }

    return html;
  }

  function setReviewRating(rating) {
    rating = parseInt(rating, 10);
    if (!rating || rating < 1) rating = 1;
    if (rating > 5) rating = 5;

    $('#rating-input').val(rating);
    $('.rating-product-custom .star-on').attr('aria-pressed', function() {
      return parseInt($(this).data('rating'), 10) <= rating ? 'true' : 'false';
    });
    $('.rating-product-custom .star-on i').css('color', function() {
      return parseInt($(this).closest('.star-on').data('rating'), 10) <= rating ? 'gold' : '#d7d0c6';
    });
  }

  function renderBreadcrumb(product) {
    const crumbs = [
      '<li class="breadcrumb-item"><a href="https://www.candybird.co.za">Home</a></li>',
      '<li class="breadcrumb-item"><a href="products">All Products</a></li>'
    ];

    [product.parentCategory, product.childCategory1, product.childCategory2].filter(Boolean).forEach(function(category) {
      const categorySlug = String(category).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
      crumbs.push('<li class="breadcrumb-item"><a href="' + (categorySlug || ('products?category=' + encodeURIComponent(category))) + '">' + escapeHtml(category) + '</a></li>');
    });

    crumbs.push('<li class="breadcrumb-item active" aria-current="page">' + escapeHtml(displayTitle(product)) + '</li>');

    $('#breadcrumb-container').html(
      '<nav class="breadcrumb-section theme1 bg-lighten2 pt-50 pb-50">' +
        '<div class="container"><div class="row"><div class="col-12">' +
          '<ol class="breadcrumb bg-transparent m-0 p-0 align-items-center justify-content-center">' + crumbs.join('') + '</ol>' +
        '</div></div></div>' +
      '</nav>'
    );
  }

  function renderImages(product) {
    const images = product.images.length ? product.images : [defaultImage];
    const mainImage = images[0] || defaultImage;
    const isClearance = String(product.is_clearance || product.raw?.is_clearance || '').toLowerCase() === 'yes';
    const clearanceFlag = isClearance ? '<span class="clearance-corner-flag"><span>Clearance<br>to go</span></span>' : '';
    const thumbHtml = images.map(function(image, index) {
      return '<button type="button" class="product-gallery-thumb' + (index === 0 ? ' active' : '') + '" data-image="' + escapeHtml(image) + '">' +
        '<img src="' + escapeHtml(image) + '" alt="' + escapeHtml(product.name) + '"' + imageFallback + '>' +
      '</button>';
    }).join('');

    $('#product-images-container')
      .removeClass('slick-initialized slick-slider')
      .html('<div class="product-gallery-main" data-image="' + escapeHtml(mainImage) + '">' + clearanceFlag + '<img src="' + escapeHtml(mainImage) + '" alt="' + escapeHtml(product.name) + '"' + imageFallback + '></div>');
    $('#product-thumbs-container')
      .removeClass('slick-initialized slick-slider')
      .html('<div class="product-gallery-thumbs">' + thumbHtml + '</div>');
  }

  function renderPrice(product) {
    const hasDiscount = product.discount > 0 || product.discountRate > 0 || product.discountedPrice < product.price;
    const isClearance = String(product.is_clearance || product.raw?.is_clearance || '').toLowerCase() === 'yes';
    const specialActive = isProductSpecialActive(product.raw || product);
    const salePercent = getSalePercent(product);
    const saveText = (salePercent > 0) ? '<span class="badge position-static ' + (isClearance ? 'badge-danger' : 'bg-dark') + ' rounded-0">' + (isClearance ? salePercent + '% off' : 'Save ' + salePercent + '%') + '</span>' : '';
    const discountUntil = product.raw.discount_valid_until || product.raw.special_valid_until || product.raw.sale_valid_until || '';
    const endsText = (!isClearance && hasDiscount && specialActive && discountUntil) ? '<div class="product-special-window"><span>Special ends ' + escapeHtml(formatSpecialDate(discountUntil)) + '</span><span class="product-special-countdown" id="product-special-countdown"></span></div>' : '';
    const clearanceNote = (isClearance && hasDiscount)
      ? '<div class="clearance-price-note">Clearance price: <span>R' + product.discountedPrice.toFixed(2) + '</span>. Original product price R' + product.price.toFixed(2) + '.</div>'
      : '';

    $('#price-section').html(
      '<div class="d-flex align-items-center mb-30">' +
        '<span class="product-price mr-20">' +
          (hasDiscount
            ? '<del class="del">R' + product.price.toFixed(2) + '</del><span class="onsale">R' + product.discountedPrice.toFixed(2) + '</span>'
            : 'R' + product.price.toFixed(2)) +
        '</span>' +
        saveText +
      '</div>' +
      clearanceNote +
      endsText
    );

    if (!isClearance && hasDiscount && specialActive && discountUntil) {
      startSpecialCountdown(discountUntil);
    } else if (specialCountdownTimer) {
      clearInterval(specialCountdownTimer);
      specialCountdownTimer = null;
      $('#product-special-countdown').removeClass('urgent').text('');
    }
  }

  function renderAvailability(product) {
    const stockValue = String(product.stockQty !== '' && product.stockQty !== null && product.stockQty !== undefined ? product.stockQty : firstProductStockValue(product.raw || {})).trim();
    const stockNumber = stockValue !== '' && !isNaN(parseFloat(stockValue)) ? parseFloat(stockValue) : null;
    const leadTime = String(product.leadTime || '').trim();
    const isClearance = String(product.is_clearance || product.raw?.is_clearance || '').toLowerCase() === 'yes';
    let html = '';
    const freeDeliveryExcluded = ['yes', 'true', '1', 'y'].indexOf(String(product.freeDeliveryExcluded || '').toLowerCase()) !== -1;
    $('#product-free-delivery-note').remove();

    if (leadTime !== '') {
      html += '<span class="lead-time-required mr-2">Lead-time required: ' + escapeHtml(leadTime) + '</span>';
      if (stockNumber !== null && stockNumber <= 0) {
        html += '<span class="badge badge-secondary position-static mr-2">' + (isClearance ? 'Sold out' : 'Out of stock') + '</span>';
        $('.add-to-cart-quantity').attr('max', 0).val(1);
        $('#add-to-cart-btn').prop('disabled', true).addClass('disabled sold-out-button').removeClass('btn-dark add-to-cart').html('Sold Out');
      } else {
        $('.add-to-cart-quantity').attr('max', stockNumber !== null ? Math.floor(stockNumber) : 999);
        $('#add-to-cart-btn').prop('disabled', false).removeClass('disabled sold-out-button').addClass('btn-dark add-to-cart').html('<span class="mr-2"><i class="ion-android-add"></i></span>Add to cart');
      }
    } else if (stockNumber !== null) {
      if (stockNumber > 0) {
        html += '<span class="badge badge-success position-static mr-2">In stock: ' + escapeHtml(stockNumber) + '</span>';
        $('.add-to-cart-quantity').attr('max', Math.floor(stockNumber));
        $('#add-to-cart-btn').prop('disabled', false).removeClass('disabled sold-out-button').addClass('btn-dark add-to-cart').html('<span class="mr-2"><i class="ion-android-add"></i></span>Add to cart');
      } else {
        html += '<span class="badge badge-secondary position-static mr-2">' + (isClearance ? 'Sold out' : 'Out of stock') + '</span>';
        $('.add-to-cart-quantity').attr('max', 0).val(1);
        $('#add-to-cart-btn').prop('disabled', true).addClass('disabled sold-out-button').removeClass('btn-dark add-to-cart').html('Sold Out');
      }
    } else {
      $('.add-to-cart-quantity').attr('max', 999);
      $('#add-to-cart-btn').prop('disabled', false).removeClass('disabled sold-out-button').addClass('btn-dark add-to-cart').html('<span class="mr-2"><i class="ion-android-add"></i></span>Add to cart');
    }

    $('#product-availability').remove();
    if (html !== '') {
      $('#price-section').after('<div id="product-availability" class="mb-20">' + html + '</div>');
    }
    if (freeDeliveryExcluded) {
      const note = '<div id="product-free-delivery-note" class="product-free-delivery-note">Free shipping does not apply to this item.</div>';
      $('#product-availability').length ? $('#product-availability').append(note) : $('#price-section').after(note);
    } else {
      $('#product-free-delivery-note').remove();
    }
  }

  function renderSelection(product) {
    function groupName(name) {
      return String(name || '').trim().toLowerCase();
    }

    const group = ALL_PRODUCTS
      .map(normalizeProduct)
      .filter(function(item) {
        return groupName(item.name) === groupName(product.name);
      })
      .sort(function(a, b) {
        return parseFloat(a.discountedPrice) - parseFloat(b.discountedPrice);
      });

    if (group.length <= 1) {
      $('#product-selection').empty();
      return;
    }

    const options = group.map(function(item) {
      return '<a class="product-size-option' + (item.id === product.id ? ' active' : '') + '" href="' + getProductPath(item) + '">' +
        '<span>' + escapeHtml(displaySize(item) || item.name) + '</span>' +
      '</a>';
    }).join('');

    $('#product-selection').html(
      '<h3 class="title">Size</h3>' +
      '<div class="product-size-options">' + options + '</div>'
    );
  }

  function renderReviews(productId) {
    if (window.CANDYBIRD_CURRENT_PRODUCT && String(window.CANDYBIRD_CURRENT_PRODUCT.is_clearance || '').toLowerCase() === 'yes') {
      $('#product-stars').html('<span class="badge badge-danger">Clearance / dated stock</span>');
      $('#reviews-list').html('<p class="mb-0">Reviews are not collected for clearance items because they are separate limited batches.</p>');
      return;
    }

    $.getJSON('get_product_reviews.php', { product_id: productId })
      .done(function(response) {
        const reviews = response.reviews || [];
        const average = parseFloat(response.average_rating || 0);
        const reviewCount = parseInt(response.review_count || 0, 10);
        const myReview = response.my_review || null;
        window.CANDYBIRD_CURRENT_REVIEWS = reviews;

        $('#product-stars').html(convertToStars(average) + '<a href="#pills-contact-tab" id="read-reviews-link"><span class="ml-2"><i class="far fa-comment-dots"></i></span> Read reviews <span>(' + reviewCount + ')</span></a>');

        if (response.user_logged_in) {
          $('#review-login-message').addClass('d-none');
          $('#review-form-wrapper').removeClass('d-none');
          $('#review-form-wrapper h3').text(myReview ? 'Edit Your Review' : 'Add a Review');
          $('#review-id').val(myReview ? myReview.id : '');
          $('#ratingForm input[name="name"]').val(myReview ? myReview.name : (response.user_name || ''));
          $('#ratingForm input[name="email"]').val(response.user_email || '');
          $('#ratingForm textarea[name="review"]').val(myReview ? myReview.comment : '');
          $('#review-submit-button').val(myReview ? 'Update Review' : 'Submit');
          setReviewRating(myReview ? myReview.rating : $('#rating-input').val());
        } else {
          $('#review-login-message').removeClass('d-none');
          $('#review-form-wrapper').addClass('d-none');
        }

        if (!reviews.length) {
          $('#reviews-list').html('<p class="mb-0">No reviews yet. Logged-in customers can be the first to review this product.</p>');
          return;
        }

        $('#reviews-list').html(reviews.map(function(review) {
          const actions = review.can_manage
            ? '<div class="review-actions">' +
                '<button type="button" class="edit-review" data-review-id="' + escapeHtml(review.id) + '">Edit</button>' +
                '<button type="button" class="delete-review" data-review-id="' + escapeHtml(review.id) + '">Delete</button>' +
              '</div>'
            : '';
          return '<div class="single-review mb-30">' +
            '<div class="review-content">' +
              '<div class="review-top-wrap">' +
                '<div class="review-left">' +
                  '<div class="review-name"><h4>' + escapeHtml(review.name) + '</h4></div>' +
                  '<div class="rating-product">' + convertToStars(parseFloat(review.rating || 0)) + '</div>' +
                '</div>' +
              '</div>' +
              '<div class="review-bottom"><p>' + escapeHtml(review.comment) + '</p>' + actions + '</div>' +
            '</div>' +
          '</div>';
        }).join(''));
      });
  }

  function renderPairProducts(product) {
    const category = product.childCategory2 || product.childCategory1 || product.parentCategory;
    const candidates = ALL_PRODUCTS
      .map(normalizeProduct)
      .filter(function(item) {
        if (item.id === product.id) return false;
        const stockValue = String(item.stockQty !== '' && item.stockQty !== null && item.stockQty !== undefined ? item.stockQty : firstProductStockValue(item.raw || {})).trim();
        const stockNumber = stockValue !== '' && !isNaN(parseFloat(stockValue)) ? parseFloat(stockValue) : null;
        if (stockNumber !== null && stockNumber <= 0) return false;
        return item.childCategory2 === category || item.childCategory1 === category || item.parentCategory === category || item.childCategory1 === product.childCategory1 || item.parentCategory === product.parentCategory;
      })
      .sort(function() { return 0.5 - Math.random(); })
      .slice(0, 4);

    if (!candidates.length) {
      $('#paired-products').html('');
      return;
    }

    $('#paired-products').html(candidates.map(function(item) {
      const image = item.images.length ? item.images[0] : defaultImage;
      const title = displayTitle(item);
      const salePercent = getSalePercent(item);
      const isClearance = String(item.is_clearance || item.raw?.is_clearance || '').toLowerCase() === 'yes';
      const stockValue = String(item.stockQty !== '' && item.stockQty !== null && item.stockQty !== undefined ? item.stockQty : firstProductStockValue(item.raw || {})).trim();
      const stockNumber = stockValue !== '' && !isNaN(parseFloat(stockValue)) ? parseFloat(stockValue) : null;
      const isSoldOut = stockNumber !== null && stockNumber <= 0;
      const priceHtml = salePercent > 0
        ? '<p class="mb-3 pair-price"><del>R' + item.price.toFixed(2) + '</del> <span>R' + item.discountedPrice.toFixed(2) + '</span></p>'
        : '<p class="mb-3 pair-price">R' + item.discountedPrice.toFixed(2) + '</p>';
      const badges = (isClearance ? '<span class="clearance-corner-flag"><span>Clearance<br>to go</span></span>' : '') +
        (isSoldOut ? '<span class="badge badge-secondary top-right">Sold out</span>' : (salePercent > 0 ? '<span class="badge badge-success top-right">' + salePercent + '% off</span>' : ''));
      const clearanceAttr = isClearance && item.clearance_id ? ' data-clearance-id="' + escapeHtml(item.clearance_id) + '"' : '';
      const cartControl = isSoldOut
        ? '<span class="sold-out-button d-inline-block">Sold Out</span>'
        : '<button type="button" class="btn btn-dark btn--sm add-to-cart" data-toggle="modal" data-target="#add-to-cart" data-product-id="' + escapeHtml(item.id) + '"' + clearanceAttr + ' data-quantity="1">Add to cart</button>';
      return '<div class="col-sm-6 col-lg-3 mb-30">' +
        '<div class="card pair-product-card">' +
          '<a class="position-relative d-block" href="' + getProductPath(item) + '">' + badges + '<img src="' + escapeHtml(image) + '"' + imageFallback + ' alt="' + escapeHtml(title) + '"></a>' +
          '<div class="card-body">' +
            '<h3 class="h6"><a href="' + getProductPath(item) + '">' + escapeHtml(title) + '</a></h3>' +
            priceHtml +
            cartControl +
          '</div>' +
        '</div>' +
      '</div>';
    }).join(''));
  }

  function renderDetails(product) {
    const details = [
      ['Size', displaySize(product)],
      ['Lead Time', product.leadTime],
      ['Stock', product.stockQty],
      ['Parent Category', product.parentCategory],
      ['Category', product.childCategory1],
      ['Subcategory', product.childCategory2],
      ['Dimensions', product.dimensions],
      ['Other Info', product.otherInfo],
      ['Disclaimer', product.disclaimers]
    ].filter(function(row) {
      return row[1];
    }).map(function(row) {
      return '<li><span>' + escapeHtml(row[0]) + '</span> ' + escapeHtml(row[1]) + '</li>';
    }).join('');

    $('#product-details-list').html(details || '<li><span>Product ID</span> ' + escapeHtml(product.id) + '</li>');
  }

  function renderWholesaleNotice(product) {
    $('#product-wholesale-note').remove();
    const isClearance = String(product.is_clearance || product.raw?.is_clearance || '').toLowerCase() === 'yes';
    if (isClearance) return;

    const candidateIds = [
      product.source_product_id,
      product.id,
      product.raw && product.raw.source_product_id,
      product.raw && product.raw.product_id,
      product.raw && product.raw.id
    ].map(function(value) {
      return String(value || '').trim();
    }).filter(function(value, index, list) {
      return value !== '' && list.indexOf(value) === index;
    });
    if (!candidateIds.length) return;
    const sourceId = candidateIds[0];

    $.getJSON('fetch_wholesale_availability.php', { product_id: candidateIds.join(',') })
      .done(function(response) {
        if (!response || !response.available) return;
        const title = displayTitle(product);
        const matchedId = response.product_id || sourceId;
        const message = encodeURIComponent('Assalamu alaikum / Hello CandyBird, please send me the wholesale/bulk options for ' + title + ' (Product ID ' + matchedId + ').');
        const whatsapp = wholesaleWhatsappDigits ? ' <a href="https://wa.me/' + encodeURIComponent(wholesaleWhatsappDigits) + '?text=' + message + '" target="_blank" rel="noopener noreferrer">WhatsApp for the bulk list</a>.' : '';
        $('#product-availability').length
          ? $('#product-availability').after('<div id="product-wholesale-note" class="product-wholesale-note"><strong>Available in wholesale/bulk.</strong>' + whatsapp + ' <a href="wholesale-pricelist">View wholesale pricelist</a>.</div>')
          : $('#price-section').after('<div id="product-wholesale-note" class="product-wholesale-note"><strong>Available in wholesale/bulk.</strong>' + whatsapp + ' <a href="wholesale-pricelist">View wholesale pricelist</a>.</div>');
      });
  }

  function renderProduct(product) {
    window.CANDYBIRD_CURRENT_PRODUCT = product;
    const pageUrl = getProductUrl(product.id);
    const images = product.images.length ? product.images : [defaultImage];
    const plainDescription = stripHtml(product.description);
    const shortDescription = plainDescription.length > 260 ? plainDescription.slice(0, 260).trim() + '...' : plainDescription;
    const title = displayTitle(product);

    $('#canonical-link').attr('href', pageUrl);
    $('#meta-description').attr('content', shortDescription);
    $('#og-title').attr('content', title + ' R' + product.discountedPrice.toFixed(2));
    $('#og-description').attr('content', shortDescription);
    $('#og-image').attr('content', images[0]);
    $('#og-url').attr('content', pageUrl);
    $('#page-title').text(title + ' R' + product.discountedPrice.toFixed(2) + ' - CandyBird');

    renderBreadcrumb(product);
    renderImages(product);
    renderPrice(product);
    renderAvailability(product);
    renderWholesaleNotice(product);
    renderSelection(product);
    renderDetails(product);
    const isClearance = String(product.is_clearance || product.raw?.is_clearance || '').toLowerCase() === 'yes';
    if (!isClearance) {
      renderReviews(product.id);
    }
    renderPairProducts(product);

    $('#product-title').text(title);
    $('#product-short-description').text(isClearance ? '' : shortDescription);
    $('#product-full-description').html(product.description || escapeHtml(shortDescription));
    const salePercent = getSalePercent(product);
    const labels = [];
    if (!isClearance && product.label) labels.push('<span class="badge badge-danger top-left">' + escapeHtml(product.label) + '</span>');
    if (salePercent > 0) labels.push('<span class="badge badge-success top-right">' + salePercent + '% off</span>');
    else if (!isClearance && productMatchesTag(product, 'hot')) labels.push('<span class="badge badge-warning top-right">Hot</span>');
    $('#product-label-container').html(labels.join(''));
    if (isClearance) {
      $('#product-stars').html('<span class="badge badge-danger">Clearance / dated stock</span>');
      $('#pills-contact-tab').closest('li').hide();
      $('.addto-whish-list').addClass('d-none');
      $('#review-login-message, #review-form-wrapper').addClass('d-none');
      $('#reviews-list').html('<p class="mb-0">Reviews are not collected for clearance items because they are separate limited batches.</p>');
    } else {
      $('#pills-contact-tab').closest('li').show();
      $('.addto-whish-list').removeClass('d-none');
      $('#product-stars').html(convertToStars(product.rating) + '<a href="#pills-contact-tab" id="read-reviews-link"><span class="ml-2"><i class="far fa-comment-dots"></i></span> Read reviews <span>(' + product.reviewCount + ')</span></a>');
    }

    $('#add-to-cart-btn, #wishlist-link, #compare-link').attr('data-product-id', product.id);
    $('#add-to-cart-btn').attr('data-clearance-id', isClearance ? (product.clearance_id || product.raw?.clearance_id || '') : '');
    $('#review-product-id').val(product.id);

    $('#facebook-share').attr('href', 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(pageUrl));
    $('#twitter-share').attr('href', 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(pageUrl) + '&text=' + encodeURIComponent('Check out ' + title + ' on CandyBird'));
    $('#pinterest-share').attr('href', 'https://www.pinterest.com/pin/create/button/').attr('data-pin-url', pageUrl).attr('data-pin-media', images[0]);
    $('#copy-product-link').attr('data-url', pageUrl);

    $('#read-more-link').toggleClass('d-none', plainDescription.length <= 260);
    $('#product-page-state').addClass('d-none');
    $('#product-detail-section, #product-tabs-section').removeClass('d-none');

    (function logProductView() {
      if (window.CANDYBIRD_PRODUCT_VIEW_LOGGED === product.id) return;
      if (typeof logAction !== 'function') {
        setTimeout(logProductView, 500);
        return;
      }
      window.CANDYBIRD_PRODUCT_VIEW_LOGGED = product.id;
      logAction(
        'UX product viewed',
        'Product: ' + product.id + ' | Title: ' + title + ' | Clearance: ' + (isClearance ? 'yes' : 'no'),
        <?= json_encode($userId ?? null) ?>,
        <?= json_encode($guestIdentifier ?? '') ?>
      );
    })();
  }

  function showState(message) {
    $('#product-page-state').removeClass('d-none').find('p').text(message);
    $('#product-detail-section, #product-tabs-section').addClass('d-none');
  }

  $.getJSON('fetch_sheet_data.php')
    .done(function(data) {
      ALL_PRODUCTS = Array.isArray(data) ? data : [];
      window.CANDYBIRD_PRODUCTS = ALL_PRODUCTS;

      const currentProduct = ALL_PRODUCTS.map(normalizeProduct).find(function(product) {
        return product.id === productID || (productSlug && product.slug === productSlug);
      });

      if (!productID && !productSlug) {
        showState('No product selected.');
        return;
      }

      if (!currentProduct) {
        showState('Product not found.');
        return;
      }

      renderProduct(currentProduct);
    })
    .fail(function() {
      showState('Product information could not be loaded.');
    });

  $('body').on('click', '.read-more-link, #read-reviews-link', function(event) {
    event.preventDefault();
    const target = $(this).attr('href') === '#pills-contact-tab' ? '#pills-contact-tab' : '#pills-home-tab';
    $(target).tab('show');
    $('html, body').animate({
      scrollTop: $('#product-tabs-section').offset().top
    }, 500);
  });

  $('body').on('submit', '#ratingForm', function(event) {
    event.preventDefault();
    const selectedRating = $('.rating-product-custom .star-on[aria-pressed="true"]').last().data('rating') || $('#rating-input').val();
    setReviewRating(selectedRating);

    $.ajax({
      type: 'POST',
      url: 'add_review.php',
      data: $(this).serialize(),
      dataType: 'json',
      success: function(response) {
        if (typeof showNotification === 'function') {
          showNotification(response.success, response.message);
        }
        if (response.success) {
          renderReviews($('#review-product-id').val());
        }
      },
      error: function() {
        if (typeof showNotification === 'function') {
          showNotification(false, 'Review could not be submitted right now.');
        }
      }
    });
  });

  $('body').on('click mousedown touchstart', '.rating-product-custom .star-on', function(event) {
    event.preventDefault();
    setReviewRating($(this).data('rating'));
  });

  $('body').on('keydown', '.rating-product-custom .star-on', function(event) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      setReviewRating($(this).data('rating'));
    }
  });

  $('body').on('click', '.product-gallery-thumb', function() {
    const image = $(this).data('image') || defaultImage;
    $('.product-gallery-thumb').removeClass('active');
    $(this).addClass('active');
    $('.product-gallery-main').attr('data-image', image).find('img').attr('src', image);
  });

  $('body').on('click', '.product-gallery-main', function() {
    const image = $(this).attr('data-image') || $(this).find('img').attr('src') || defaultImage;
    $('#product-image-lightbox img').attr('src', image);
    $('#product-image-lightbox').addClass('open').attr('aria-hidden', 'false');
  });

  $('body').on('click', '#product-image-lightbox, #product-image-lightbox-close', function(event) {
    if (event.target !== this && event.currentTarget.id !== 'product-image-lightbox-close') return;
    $('#product-image-lightbox').removeClass('open').attr('aria-hidden', 'true');
  });

  $('body').on('click', '#copy-product-link', function(event) {
    event.preventDefault();
    const url = $(this).attr('data-url') || window.location.href;

    function copied() {
      if (typeof showNotification === 'function') {
        showNotification(true, 'Product link copied.');
      }
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(copied).catch(function() {
        const input = $('<input type="text" readonly>').val(url).appendTo('body').select();
        document.execCommand('copy');
        input.remove();
        copied();
      });
    } else {
      const input = $('<input type="text" readonly>').val(url).appendTo('body').select();
      document.execCommand('copy');
      input.remove();
      copied();
    }
  });

  $(document).on('keydown', function(event) {
    if (event.key === 'Escape') {
      $('#product-image-lightbox').removeClass('open').attr('aria-hidden', 'true');
    }
  });

  $('body').on('click', '.edit-review', function() {
    const reviewId = String($(this).data('review-id'));
    const matchedReview = window.CANDYBIRD_CURRENT_REVIEWS ? window.CANDYBIRD_CURRENT_REVIEWS.find(function(item) {
      return String(item.id) === reviewId;
    }) : null;

    if (!matchedReview) return;

    $('#review-id').val(matchedReview.id);
    $('#rating-input').val(matchedReview.rating);
    $('#ratingForm input[name="name"]').val(matchedReview.name);
    $('#ratingForm textarea[name="review"]').val(matchedReview.comment);
    $('#review-form-wrapper h3').text('Edit Your Review');
    $('#review-submit-button').val('Update Review');
    setReviewRating(matchedReview.rating || 5);
    $('html, body').animate({ scrollTop: $('#review-form-wrapper').offset().top - 100 }, 400);
  });

  $('body').on('click', '.delete-review', function() {
    if (!confirm('Delete this review?')) return;

    $.ajax({
      type: 'POST',
      url: 'delete_review.php',
      data: { review_id: $(this).data('review-id') },
      dataType: 'json',
      success: function(response) {
        if (typeof showNotification === 'function') {
          showNotification(response.success, response.message);
        }
        if (response.success) {
          $('#review-id').val('');
          $('#ratingForm textarea[name="review"]').val('');
          renderReviews($('#review-product-id').val());
        }
      },
      error: function() {
        if (typeof showNotification === 'function') {
          showNotification(false, 'Review could not be deleted right now.');
        }
      }
    });
  });
});
</script>

<?php
include 'footer.php';
?>
