<?php
include 'session_logins.php';
include 'header.php';


// Fetch compare items based on user or guest
$compareItems = getCompareItems($userId, $guestIdentifier);

function cbCompareMoney($value) {
    if (function_exists('candybirdParseSheetMoney')) {
        return candybirdParseSheetMoney($value);
    }
    return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
}

function cbCompareIsClearance($item) {
    return strtolower((string) ($item['is_clearance'] ?? '')) === 'yes' || stripos((string) ($item['id'] ?? ''), 'CLR:') === 0;
}

function cbComparePriceHtml($item) {
    $originalPrice = cbCompareMoney($item['original_price'] ?? $item['price'] ?? 0);
    $finalPrice = cbCompareMoney($item['final_price'] ?? $item['discounted_price'] ?? 0);
    $discountAmount = cbCompareMoney($item['discount_amount'] ?? 0);
    $discountRate = cbCompareMoney($item['discount_rate'] ?? 0);

    if ($finalPrice <= 0) {
        if ($discountAmount > 0 && $originalPrice > 0) {
            $finalPrice = max(0, $originalPrice - $discountAmount);
        } else {
            $finalPrice = $originalPrice;
        }
    }

    $hasDiscount = $originalPrice > 0 && $finalPrice > 0 && $finalPrice < $originalPrice;
    $percentSaved = $hasDiscount ? round((($originalPrice - $finalPrice) / $originalPrice) * 100) : 0;

    $html = '<span class="compare-price-display">';
    if ($hasDiscount) {
        $html .= '<del class="del">R' . number_format($originalPrice, 2) . '</del>';
        $html .= '<span class="onsale">R' . number_format($finalPrice, 2) . '</span>';
        if ($percentSaved > 0) {
            $html .= '<span class="compare-save-badge">Save ' . (int) $percentSaved . '%</span>';
        }
    } else {
        $html .= '<span>R' . number_format($originalPrice, 2) . '</span>';
    }
    if (cbCompareIsClearance($item)) {
        $html .= '<span class="compare-clearance-badge">Clearance</span>';
    }
    $html .= '</span>';

    return $html;
}

// Format compare items
$compare_header = '<tr>
                    <th scope="col">product info</th>';
$compare_body = "";

foreach ($compareItems as $item) {
    $image_url = isset($item['image_url']) ? $item['image_url'] : 'assets/img/product/1.png';
    $isClearance = cbCompareIsClearance($item);
    $clearanceId = trim((string) ($item['clearance_id'] ?? ''));

    $compare_header .= '<th scope="col" class="text-center">
                          <div class="compare-product-image-wrap">
                            <img src="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($item['title'] ?? 'CandyBird product', ENT_QUOTES, 'UTF-8') . '" />
                            ' . ($isClearance ? '<span class="compare-clearance-ribbon">Clearance</span>' : '') . '
                          </div>
                          <span class="sub-title d-block">' . htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>
                          <a href="#" class="btn btn-dark btn--lg add-to-cart"
                              data-toggle="modal"
                              data-target="#add-to-cart"
                              data-quantity="1"
                              data-product-id="' . htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8') . '"
                              ' . ($clearanceId !== '' ? 'data-clearance-id="' . htmlspecialchars($clearanceId, ENT_QUOTES, 'UTF-8') . '"' : '') . '>
                              add to cart
                          </a>
                      </th>';
}

$compare_header .= '</tr>';

?>

<?php
$page_url_canonical = "https://www.candybird.co.za/compare";
$title_og = 'Compare Items - CandyBird';
$page_url_og = "https://www.candybird.co.za/compare"
?>

<!-- Canonical URL to Avoid Duplicate Content Issues -->
<link rel="canonical" href="<?=$page_url_canonical?>">

<!-- Meta Description Tag -->
<meta name="description" content="<?=$description_meta?>">

<!-- Open Graph Meta Tags for Facebook, Twitter, etc. -->
<meta property="og:title" content="<?=$title_og?>">
<meta property="og:description" content="<?=$description_og?>">
<meta property="og:image" content="<?=$image_url_og?>">
<meta property="og:url" content="<?=$page_url_og?>">
<meta property="og:type" content="website">

<title>Compare Items - CandyBird</title>

<?php
include 'page_menues.php';
?>

<style>
  .compare-product-image-wrap {
    background: #f7f2ea;
    display: inline-block;
    margin-bottom: 10px;
    max-width: 145px;
    overflow: hidden;
    position: relative;
    width: 100%;
  }
  .compare-product-image-wrap img {
    aspect-ratio: 1 / 1;
    display: block;
    object-fit: cover;
    width: 100%;
  }
  .compare-clearance-ribbon {
    background: #d5001f;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    left: 0;
    letter-spacing: 0;
    line-height: 1;
    padding: 6px 9px;
    position: absolute;
    text-transform: uppercase;
    top: 0;
  }
  .compare-price-display {
    align-items: center;
    display: inline-flex;
    flex-direction: column;
    gap: 4px;
  }
  .compare-price-display .del {
    color: #8b8079;
    font-size: .9rem;
  }
  .compare-price-display .onsale {
    color: #b33818;
    font-size: 1.05rem;
    font-weight: 800;
  }
  .compare-save-badge,
  .compare-clearance-badge {
    border-radius: 999px;
    display: inline-flex;
    font-size: 11px;
    font-weight: 800;
    line-height: 1;
    padding: 6px 8px;
    text-transform: uppercase;
  }
  .compare-save-badge {
    background: #edf8ed;
    color: #24713a;
  }
  .compare-clearance-badge {
    background: #d5001f;
    color: #fff;
  }
</style>

<!-- product tab start -->
<section class="compare-section theme1 pt-80 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <h3 class="title mb-30 pb-25 text-capitalize">compare</h3>
        <div class="table-responsive">
          <table class="table">
            <thead class="thead-light">
              
              <?=$compare_header?>

            </thead>
            <tbody>

              <tr>
        <th scope="row">Price</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center">
                <?=cbComparePriceHtml($item)?>
            </td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Description</th>
        <?php foreach ($compareItems as $item): ?>
          <?php
          $limitedDescription = strlen($item['description']) > 200 ? trim(strip_tags(substr($item['description'], 0, 200) . '...')) : trim(strip_tags($item['description']));
          ?>
            <td class="text-center" style="max-width:200px !important">
                <p><?=$limitedDescription?></p>
            </td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Availability</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center">
                <span class="badge badge-danger position-static">In Stock</span>
            </td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Weight</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center"><?=$item['weight']?></td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Other Info</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center"><?=$item['other_info']?></td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Properties</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center"><?=$item['features']?></td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Actions</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center">
            <a href="#" class="btn btn--lg remove-from-compare"
                  data-product-id="<?= htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  Remove from Compare
              </a>
          </td>
        <?php endforeach; ?>
    </tr>

            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- product tab end -->


<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
     $('body').on('click', '.remove-from-compare', function (e) {
      e.preventDefault();
      // Retrieve product ID from data attribute
      var productId = $(this).data('product-id');
      console.log('Product ID:', productId); // Log to console for debugging

      // Perform AJAX request
      $.ajax({
          type: 'POST',
          url: 'remove_from_compare.php', // Specify the path to your PHP script
          data: {
              productId: productId
          },
          success: function (response) {
              // Handle the response from the server (optional)
              console.log(response);
              updateBadgeCounts();
              // You can update the UI or show a success message here
              showNotification(response.success, response.message);
          },
          error: function (error) {
              // Handle errors (optional)
              console.error('Error:', error);
          }
      });
  });

</script>

<?php
include 'footer.php';
?>
