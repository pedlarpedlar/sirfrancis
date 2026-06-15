<?php
include 'session_logins.php';
include 'header.php';
?>
<?php
$page_url_canonical = "https://www.fishgelatine.co.za/v2/cart";
$title_og = 'Shopping Basket - Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/cart"
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

<title>Shopping Basket - Sir Francis</title>

<?php
include 'page_menues.php';
echo renderCandybirdSiteFlags('cart');
?>

<style>
  @media (min-width: 992px) {
    .cart-items-table {
      table-layout: fixed;
      width: 100%;
      font-size: 13px;
    }
    .cart-items-table th,
    .cart-items-table td {
      padding: 8px 6px;
      vertical-align: middle;
    }
    .cart-items-table thead th:nth-child(1),
    .cart-items-table tbody th {
      width: 70px;
    }
    .cart-items-table thead th:nth-child(2),
    .cart-items-table tbody td:nth-child(2) {
      width: auto;
      text-align: left !important;
    }
    .cart-items-table thead th:nth-child(3),
    .cart-items-table tbody td:nth-child(3) {
      width: 92px;
    }
    .cart-items-table thead th:nth-child(4),
    .cart-items-table tbody td:nth-child(4),
    .cart-items-table thead th:nth-child(5),
    .cart-items-table tbody td:nth-child(5) {
      width: 92px;
    }
    .cart-items-table thead th:nth-child(6),
    .cart-items-table tbody td:nth-child(6) {
      width: 48px;
    }
    .cart-items-table thead th:nth-child(7),
    .cart-items-table tbody td:nth-child(7) {
      width: 68px;
    }
    .cart-items-table img {
      max-width: 52px;
      width: 52px;
      height: 52px;
      object-fit: cover;
    }
    .cart-items-table .whish-title a {
      display: block;
      line-height: 1.25;
    }
    .cart-items-table .product-count.style,
    .cart-items-table .count {
      min-width: 0;
    }
    .cart-items-table input.quantity {
      width: 46px;
      min-width: 46px;
      height: 34px;
      padding: 4px;
      text-align: center;
    }
    .cart-items-table .button-group {
      width: 24px;
    }
    .cart-items-table .count-btn {
      width: 24px;
      height: 17px;
      line-height: 17px;
      padding: 0;
    }
    .cart-items-table .btn-sm {
      font-size: 11px;
      padding: 5px 7px;
    }
    .cart-items-table .trash {
      font-size: 15px;
    }
  }
  .free-delivery-excluded-note {
    color: #8a8178;
    display: block;
    font-size: 11px;
    line-height: 1.35;
    margin-top: 4px;
  }
</style>

<?php
// Fetch cart items based on user or guest
$cartItems = getcartItems($userId, $guestIdentifier);

// Format cart items
$cart_table = "";
$coupon_amount = 0;
$subtotals = 0;
$discounts = 0;
$taxes = 0;
$tax = 0;
$order_total = 0;
$cart_weight_kg = 0;
$free_shipping_basis_total = 0;

foreach ($cartItems as $item) {
    $image_url = isset($item['image_url']) ? $item['image_url'] : 'assets/img/product/1.png';

    $price = (float) ($item['original_price'] ?? $item['price'] ?? 0);
    $quantity = $item['quantity'];
    $discount_rate = $item['discount_rate'];
    $item_discount_amount = isset($item['discount_amount']) ? (float) $item['discount_amount'] : 0;
    $sheet_discounted_price = isset($item['final_price']) ? (float) $item['final_price'] : (isset($item['discounted_price']) ? (float) $item['discounted_price'] : 0);
    $tax = !empty($item['tax_amount']) ? $item['tax_amount'] : 0;

    // Calculate discount amount based on discount rate
    $discount = 0;
    if ($sheet_discounted_price > 0 && $sheet_discounted_price < $price) {
        $discount = $price - $sheet_discounted_price;
    } elseif ($item_discount_amount > 0) {
        $discount = $item_discount_amount;
    } elseif ($discount_rate > 0) {
        $discount = ($price * $discount_rate) / 100;
    }

    // Apply discounts to price for further calculations
    $discounted_price = $price - $discount;

    // Calculate subtotal without tax and discounts
    $subtotal = $quantity * $discounted_price;

    // Accumulate taxes and discounts
    $taxes += $quantity * $tax;
    $discounts += $quantity * $discount;

    // Accumulate subtotals
    $subtotals += ($quantity * $price);

    // Apply coupon rate discount if applicable
    if (isset($_SESSION['coupon']['code'])) {
      $coupon_amount = $_SESSION['coupon']['coupon_savings'];
    }

    $isClearance = !empty($item['is_clearance']) && $item['is_clearance'] === 'yes';
    $sheetProduct = $isClearance ? getSheetProductById($item['source_product_id'] ?? $item['product_id'] ?? '') : getSheetProductById($item['id']);
    $freeDeliveryExcluded = (!empty($item['free_delivery_excluded']) && $item['free_delivery_excluded'] === 'yes') || isCandybirdFreeDeliveryExcluded($sheetProduct);
    $displayTitle = $isClearance ? trim((string) $item['title']) : trim($item['title'] . ' ' . ($item['product_weight'] ?? ''));
    $stockQty = isset($item['stock_qty']) && $item['stock_qty'] !== '' ? (int) $item['stock_qty'] : null;
    $isSoldOut = $stockQty !== null && $stockQty <= 0;
    $rowWeightKg = 0;
    if ($sheetProduct) {
      $rowWeightKg = getSheetProductWeightKg($sheetProduct);
      $cart_weight_kg += $rowWeightKg * $quantity;
      if (!$isClearance) {
        $displayTitle = getSheetProductDisplayTitle($sheetProduct);
      }
    }
    


    $cart_table .= '<tr class="product-row" data-product-id="' . htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') . '" data-product-price="'.$price.'" data-product-discount="'.$discount.'" data-product-tax="'.$tax.'" data-product-weight-kg="'.$rowWeightKg.'">';
    $cart_table .= '<th class="text-center" scope="row">';
    $cart_table .= '<img src="' . $image_url . '" alt="img" />';
    $cart_table .= '</th>';
    $cart_table .= '<td>';
    $cartLink = $item['product_url'] ?? ('product?id=' . urlencode((string) $item['id']));
    $cart_table .= '<span class="whish-title"><a href="' . htmlspecialchars($cartLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') . '</a></span>';
    if ($isClearance) {
      $cart_table .= '<div><span style="display:inline-block;margin-top:4px;background:#dc3545;color:#fff;font-size:12px;font-weight:700;padding:3px 6px;border-radius:4px;">Clearance item</span></div>';
    }
    if ($isSoldOut) {
      $cart_table .= '<div><span style="display:inline-block;margin-top:4px;background:#111;color:#fff;font-size:12px;font-weight:700;padding:3px 6px;border-radius:4px;">Sold out - please remove</span></div>';
    }
    if ($freeDeliveryExcluded) {
      $cart_table .= '<span class="free-delivery-excluded-note">Free shipping does not apply to this item.</span>';
    } else {
      $free_shipping_basis_total += $subtotal;
    }
    $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<div class="product-count style">';
    $cart_table .= '<div class="count d-flex justify-content-center">';
    $cart_table .= '<input type="number" min="1" max="999" step="1" value="' . $quantity . '" class="quantity" />';
    $cart_table .= '<div class="button-group">';
    $cart_table .= '<button class="count-btn increment"><i class="fas fa-chevron-up"></i></button>';
    $cart_table .= '<button class="count-btn decrement"><i class="fas fa-chevron-down"></i></button>';
    $cart_table .= '</div>';
    $cart_table .= '</div>';
    $cart_table .= '</div>';
    $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';


    $cart_table .= "<span class='product-price'>";

    if ($discount > 0) {
        $cart_table .= "<del class='del'>R".number_format($item['price'], 2)."</del>";
        $cart_table .= "<span class='onsale'> R".number_format($discounted_price, 2)."</span>";
    } else {
        $cart_table .= "R".number_format($item['price'], 2);
    }

    $cart_table .= "</span>";



    $cart_table .= '</td>';
    // $cart_table .= '<td class="text-center">';
    // $cart_table .= '<span class="dynamic-tax">R' . number_format($tax, 2) . ' ('. $item['tax_rate'] .'%)</span>';
    // $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<span class="dynamic-subtotal">R' . number_format($subtotal, 2) . '</span>';
    $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<a href="#" class="removeFromCart" data-product-id="' . htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') . '"><span class="trash" ><i class="fas fa-trash-alt"></i></span></a>';
    $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<a href="#" class="btn btn-dark btn-sm update-cart-quantity" data-product-id="' . htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') . '">Update</a>';
    $cart_table .= '</td>';
    $cart_table .= '</tr>';
}



// Fetch distinct countries from the shipping_zones table
$countries = "";

// Check if there are results
if ($conn instanceof mysqli) {
    $sqlCountries = "SELECT DISTINCT country FROM shipping_zones";
    $resultCountries = mysqli_query($conn, $sqlCountries);
} else {
    $resultCountries = false;
}

if ($resultCountries && mysqli_num_rows($resultCountries) > 0) {
    while ($rowCountry = mysqli_fetch_assoc($resultCountries)) {
        // Output <option> tag for each country
        $countries .= '<option value="' . $rowCountry['country'] . '">' . $rowCountry['country'] . '</option>';
    }
}

$order_total += $subtotals;
$order_total += $taxes;
$order_total -= $discounts;
$order_total -= $coupon_amount;
$cart_free_shipping_basis = max(0, $free_shipping_basis_total - $coupon_amount);
$delivery_options = getCandybirdDeliveryOptions();
$enabled_delivery_options = getCandybirdEnabledDeliveryOptions($delivery_options);
$default_delivery_method = getCandybirdDefaultDeliveryMethod($delivery_options);
$default_delivery_quote = getCandybirdDeliveryQuote($default_delivery_method, $cart_weight_kg, $cart_free_shipping_basis, $free_shipping_amount);
?>

        
<?php
  if (!empty($cart_table)) {
      // Display the table if $cart_table is not empty
      echo '
      <!-- product tab start -->
<section class="whish-list-section theme1 pt-80 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-12">
      <h3 class="title mb-30 pb-25 text-capitalize">Your cart items</h3>
        <div class="table-responsive">
          <table class="table cart-items-table">
              <thead class="thead-light">
                <tr>
                  <th class="text-center" scope="col">Product Image</th>
                  <th class="text-center" scope="col">Product Name</th>
                  <th class="text-center" scope="col">Qty</th>
                  <th class="text-center" scope="col">Price</th>
                  <!-- <th class="text-center" scope="col">Tax</th> -->
                  <th class="text-center" scope="col">Subtotal</th>
                  <th class="text-center" scope="col">action</th>
                  <th class="text-center" scope="col">Update</th>
                </tr>
              </thead>
              <tbody>
                ' . $cart_table . '
              </tbody>
            </table>
            </div>
        </div>
    </div>
</section>';
  } else {

      ?>

          <!-- product tab start -->
          <section class="product-tab bg-white pt-30 pb-30">
            <div class="container">
              <!-- product-tab-nav end -->
              <div class="row">
                <div class="col-12">
                    <div class="section-title text-center">
                        <h2 class="title pb-3 mb-3">Oh No... Your cart is empty!</h2>
                        <p class="text mt-10">Have a look at these products to add your weekly line up</p>
                    </div>
                </div>
                <div class="col-12">
                    <div class="generated_products" id="generated_products"></div>
                </div>
              </div>
            </div>
          </section>
          <!-- product tab end -->

      <?php
  }
?>


<!-- product tab end -->
<div class="check-out-section pb-80">
  <div class="container">
    <div class="row">
      <div class="col-lg-7">
        <div class="billing-info-wrap cart-delivery-wrap">
          <h3 class="title">Delivery estimate</h3>
        
            <div class="row">
            <div class="col-12">
                <div class="cart-delivery-method-grid mb-20px">
                    <?php foreach ($enabled_delivery_options as $methodKey => $method): ?>
                        <?php
                            $isDefaultMethod = $methodKey === $default_delivery_method;
                            $methodHelp = $method['estimate'] ?? '';
                            if (!empty($method['free_shipping_eligible'])) {
                                $methodHelp = 'Free shipping can apply here' . ($methodHelp ? '. ' . $methodHelp : '');
                            } elseif ($methodKey === 'collect') {
                                $methodHelp = 'No courier fee' . ($methodHelp ? '. ' . $methodHelp : '');
                            } elseif ($methodHelp) {
                                $methodHelp = 'Estimate: ' . $methodHelp;
                            }
                        ?>
                        <label class="cart-delivery-method-option <?=$isDefaultMethod ? 'active' : ''?>" for="cart-delivery-<?=htmlspecialchars($methodKey, ENT_QUOTES)?>">
                            <input type="radio" id="cart-delivery-<?=htmlspecialchars($methodKey, ENT_QUOTES)?>" name="cart_delivery_method" value="<?=htmlspecialchars($methodKey, ENT_QUOTES)?>" <?=$isDefaultMethod ? 'checked' : ''?>>
                            <span><strong><?=htmlspecialchars($method['label'] ?? ucfirst($methodKey), ENT_QUOTES)?></strong><small><?=htmlspecialchars($methodHelp, ENT_QUOTES)?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($enabled_delivery_options as $methodKey => $method): ?>
                    <div class="cart-delivery-tier-box <?=$methodKey === $default_delivery_method ? '' : 'd-none'?>" id="cart-<?=htmlspecialchars($methodKey, ENT_QUOTES)?>-tier-box">
                        <strong><?=htmlspecialchars($method['label'] ?? ucfirst($methodKey), ENT_QUOTES)?> details</strong>
                        <?php if ($methodKey === 'collect'): ?>
                            <span>No courier charge.</span>
                            <?php if (!empty($method['collection_address'])): ?><span>Collect from: <?=htmlspecialchars($method['collection_address'], ENT_QUOTES)?></span><?php endif; ?>
                        <?php else: ?>
                            <?php foreach (($method['tiers'] ?? []) as $tier): ?>
                                <span>R<?=number_format((float)($tier['price'] ?? 0), 0)?> <?=htmlspecialchars(strtolower($tier['label'] ?? ''), ENT_QUOTES)?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <p class="cart-delivery-note"><?=($cart_weight_kg <= 0 ? 'Digital product. No courier delivery is needed.' : 'Estimated order weight: ' . number_format($cart_weight_kg, 2) . 'kg. The exact delivery option is confirmed at checkout.')?></p>
            </div>
              <div class="col-12">
                <h3 class="coupon-title">Discount Coupon Code</h3>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <input placeholder="Coupon Code" type="text" id="coupon-code" />
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <a href="#" class="btn btn-primary check-out-btn apply-coupon-btn" data-order-total="<?=$order_total?>">apply code</a>
              </div>
            </div>
        </div>
      </div>
      <div class="col-lg-5 mt-4 mt-lg-0">
        <div class="your-order-area">
          <div class="your-order-wrap gray-bg-4">
            <div class="your-order-product-info">
              <div class="your-order-top">
                <ul>
                  <li>Subtotal</li>
                  <li id="cart-original-subtotal">R<?=number_format($subtotals, 2)?></li>
                </ul>
              </div>


              <div class="your-order-top">
                <ul>
                  <li>Discounts</li>
                  <li id="cart-line-discounts">-R<?=number_format($discounts, 2)?></li>
                </ul>
              </div>

    <div class="your-order-top<?=isset($_SESSION['coupon']['code']) ? '' : ' d-none'?>" id="cart-coupon-summary">
        <ul>
            <li id="cart-coupon-label">COUPON <?=htmlspecialchars($_SESSION['coupon']['code'] ?? '', ENT_QUOTES, 'UTF-8')?></li>
            <li><span id="cart-coupon-amount">- R<?= number_format($_SESSION['coupon']['coupon_savings'] ?? 0, 2) ?></span> <a href="#" class="remove-coupon" data-couponid="<?=htmlspecialchars($_SESSION['coupon']['id'] ?? '', ENT_QUOTES, 'UTF-8')?>" title="Remove Coupon">[X]</a></li>
        </ul>
    </div>


<!--               <div class="your-order-top">
                <ul>
                  <li>Taxes</li>
                  <li>R<?=number_format($taxes, 2)?></li>
                </ul>
              </div> -->

              <div class="your-order-bottom">
                <ul>
                  <li class="your-order-shipping">Shipping Estimate</li>
                  <li id="cart-shipping-summary">R<?=number_format($default_delivery_quote['payable_shipping_amount'], 2)?></li>
                </ul>
              </div>
              <div class="your-order-total mb-0">
                <ul>
                  <li class="order-total">Total</li>
                  <li id="order-total">R<?=number_format($order_total + $default_delivery_quote['payable_shipping_amount'], 2)?></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="Place-order mt-25">
            <a class="btn btn--lg btn-primary my-2 my-sm-0" href="checkout"
              >proceed to checkout</a
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Include jQuery library -->
<style>
.cart-delivery-method-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.cart-delivery-method-option {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    border: 1px solid #e6e1d8;
    border-radius: 6px;
    padding: 14px;
    cursor: pointer;
}
.cart-delivery-method-option.active {
    border-color: #28364B;
    box-shadow: 0 0 0 2px rgba(107, 0, 153, 0.12);
}
.cart-delivery-method-option strong,
.cart-delivery-method-option small {
    display: block;
}
.cart-delivery-tier-box {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 8px;
    border: 1px solid #ece6dc;
    background: #fffaf2;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 10px;
}
.cart-delivery-note {
    color: #4f4a45;
    margin-bottom: 20px;
}
@media screen and (max-width: 767px) {
    .cart-delivery-method-grid,
    .cart-delivery-tier-box {
        grid-template-columns: 1fr;
    }
}
</style>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
$(document).ready(function () {
  var cartSubtotal = <?=json_encode((float) $order_total)?>;
  var cartFreeShippingBasis = <?=json_encode((float) $cart_free_shipping_basis)?>;
  var cartWeightKg = <?=json_encode((float) $cart_weight_kg)?>;
  var freeShippingAmount = <?=json_encode((float) $free_shipping_amount)?>;
  var cartDeliveryOptions = <?=json_encode($delivery_options)?>;
  var defaultCartDeliveryMethod = <?=json_encode($default_delivery_method)?>;
  var cartCouponAmount = <?=json_encode(isset($_SESSION['coupon']['coupon_savings']) ? (float) $_SESSION['coupon']['coupon_savings'] : 0)?>;
  var cartBaseSubtotal = cartSubtotal + cartCouponAmount;
  var cartFreeShippingBasisBase = cartFreeShippingBasis + cartCouponAmount;

  function formatRand(amount) {
      return 'R' + (parseFloat(amount) || 0).toFixed(2);
  }

  function getCartDeliveryQuote(method) {
      if (cartWeightKg <= 0) {
          return {
              tier: {label: 'Digital delivery'},
          payableShipping: 0,
          shippingDiscount: 0,
          freeEligible: false,
          digital: true
          };
      }

      var option = cartDeliveryOptions[method] || cartDeliveryOptions[defaultCartDeliveryMethod] || cartDeliveryOptions.locker;
      var tierKey = null;
      var tier = null;

      Object.keys(option.tiers).some(function(key) {
          var candidate = option.tiers[key];
          if (candidate.max_kg === null || cartWeightKg <= parseFloat(candidate.max_kg)) {
              tierKey = key;
              tier = candidate;
              return true;
          }
          return false;
      });

      var shippingAmount = parseFloat(tier.price) || 0;
      var shippingDiscount = option.free_shipping_eligible && cartFreeShippingBasis >= freeShippingAmount && tierKey !== 'locker_over_20kg' ? shippingAmount : 0;

      return {
          tier: tier,
          payableShipping: Math.max(0, shippingAmount - shippingDiscount),
          shippingDiscount: shippingDiscount,
          freeEligible: !!option.free_shipping_eligible,
          collectionAddress: option.collection_address || '',
          estimate: option.estimate || ''
      };
  }

  function updateCartDeliveryTotals() {
      var method = $('input[name="cart_delivery_method"]:checked').val() || defaultCartDeliveryMethod;
      var quote = getCartDeliveryQuote(method);
      var grandTotal = cartSubtotal + quote.payableShipping;
      $('#cart-shipping-summary').text(quote.payableShipping > 0 ? formatRand(quote.payableShipping) : 'Free');
      $('#order-total').text(formatRand(grandTotal));

      if (quote.digital) {
          $('.cart-delivery-note').text('Digital product. No courier delivery is needed.');
          return;
      }

      var note = quote.tier.label + ' selected.';
      if (method === 'collect') {
          note += ' No courier fee.';
          if (quote.collectionAddress) {
              note += ' Collection address: ' + quote.collectionAddress + '.';
          }
      } else if (quote.shippingDiscount > 0) {
          note += ' Free shipping applied.';
      } else if (quote.freeEligible && freeShippingAmount > cartFreeShippingBasis) {
          note += ' Add ' + formatRand(freeShippingAmount - cartFreeShippingBasis) + ' more in eligible items for free shipping.';
      } else if (!quote.freeEligible) {
          note += ' Free shipping does not apply to this delivery method.';
      }
      $('.cart-delivery-note').text('Estimated order weight: ' + cartWeightKg.toFixed(2) + 'kg. ' + note);
  }

  function setCartCouponSummary(code, amount) {
      cartCouponAmount = parseFloat(amount) || 0;
      cartSubtotal = Math.max(0, cartBaseSubtotal - cartCouponAmount);
      cartFreeShippingBasis = Math.max(0, cartFreeShippingBasisBase - cartCouponAmount);

      if (cartCouponAmount > 0 && code) {
          $('#cart-coupon-summary').removeClass('d-none');
          $('#cart-coupon-label').text('COUPON ' + code);
          $('#cart-coupon-amount').text('- ' + formatRand(cartCouponAmount));
      } else {
          $('#cart-coupon-summary').addClass('d-none');
          $('#cart-coupon-label').text('COUPON');
          $('#cart-coupon-amount').text('- R0.00');
      }

      updateCartDeliveryTotals();
  }

  function recalculateCartFromRows() {
      var originalSubtotal = 0;
      var lineDiscounts = 0;
      var lineTaxes = 0;
      var weightTotal = 0;

      $('.product-row').each(function() {
          var $row = $(this);
          var quantity = Math.max(1, parseInt($row.find('input.quantity').val(), 10) || 1);
          var unitPrice = parseFloat($row.data('product-price')) || 0;
          var unitDiscount = parseFloat($row.data('product-discount')) || 0;
          var unitTax = parseFloat($row.data('product-tax')) || 0;
          var unitWeight = parseFloat($row.data('product-weight-kg')) || 0;
          var lineSubtotal = Math.max(0, unitPrice - unitDiscount + unitTax) * quantity;

          originalSubtotal += unitPrice * quantity;
          lineDiscounts += unitDiscount * quantity;
          lineTaxes += unitTax * quantity;
          weightTotal += unitWeight * quantity;

          $row.find('.dynamic-subtotal').text(formatRand(lineSubtotal));
      });

      cartWeightKg = weightTotal;
      cartBaseSubtotal = Math.max(0, originalSubtotal - lineDiscounts + lineTaxes);
      cartSubtotal = Math.max(0, cartBaseSubtotal - cartCouponAmount);

      $('#cart-original-subtotal').text(formatRand(originalSubtotal));
      $('#cart-line-discounts').text('-' + formatRand(lineDiscounts));
      $('.apply-coupon-btn').attr('data-order-total', cartSubtotal);
      updateCartDeliveryTotals();
  }

  function setCartItemSaving($button, saving) {
      $button.prop('disabled', saving).toggleClass('disabled', saving);
      $button.text(saving ? 'Saving...' : 'Update');
  }

  $('body').on('change', 'input[name="cart_delivery_method"]', function() {
      $('.cart-delivery-method-option').removeClass('active');
      $(this).closest('.cart-delivery-method-option').addClass('active');
      $('.cart-delivery-tier-box').addClass('d-none');
      $('#cart-' + $(this).val() + '-tier-box').removeClass('d-none');
      updateCartDeliveryTotals();
  });

  $('.remove-coupon').on('click', function (e) {
      e.preventDefault(); // Prevent the default behavior of the anchor tag
      
      var coupon_id = $(this).data('couponid');

      // Make an AJAX request to remove the coupon
      $.ajax({
          url: 'remove_coupon.php', // Replace with the actual path
          method: 'POST',
          data: { coupon_id: coupon_id },
          success: function (response) {
              // Handle the response from the server
              console.log(response);
              // You might update the cart UI or show a success message
              showNotification(response.success, response.message);
              if (response.success) {
                  setCartCouponSummary('', 0);
              }
          },
          error: function (xhr, status, error) {
              // Handle errors, display an error message, etc.
              console.error(error);
          }
      });
  });

  $('.apply-coupon-btn').on('click', function (e) {
      e.preventDefault();
      // Get the coupon code entered by the user
      var userCouponCode = $('#coupon-code').val();

      // Make an AJAX request to your server to apply the coupon
      $.ajax({
          url: 'apply_coupon.php', // Replace with the actual path
          method: 'POST',
          data: { coupon_code: userCouponCode },
          success: function (response) {
              // Handle the response from the server
              console.log(response);
              // You might update the cart UI or show a success message
              showNotification(response.success, response.message);
              if (response.success) {
                  setCartCouponSummary(response.coupon_code || userCouponCode.toUpperCase(), response.coupon_savings || 0);
                  $('#coupon-code').val('');
              }
          },
          error: function (xhr, status, error) {
              // Handle errors, display an error message, etc.
              console.error(error);
          }
      });
  });

  $('body').on('click', '.count-btn', function () {
      setTimeout(recalculateCartFromRows, 0);
  });

  $('body').on('change keyup', 'input.quantity', function () {
      recalculateCartFromRows();
  });

  $('body').on('click', '.update-cart-quantity', function (event) {
      event.preventDefault();
      event.stopImmediatePropagation();

      var $button = $(this);
      var $row = $button.closest('.product-row');
      var productIdCart = $button.data('product-id');
      var inputQuantity = Math.max(1, parseInt($row.find('.quantity').val(), 10) || 1);

      recalculateCartFromRows();
      setCartItemSaving($button, true);

      $.ajax({
          type: 'POST',
          url: 'update_cart.php',
          dataType: 'json',
          data: { product_id: productIdCart, quantity: inputQuantity },
          success: function(response) {
              if (response && response.success) {
                  $('#offcanvas-cart .sub-total .amount').text(response.subtotal);
                  if (typeof response.coupon_savings !== 'undefined') {
                      setCartCouponSummary(response.coupon_code || ($('#cart-coupon-label').text() || '').replace(/^COUPON\s+/i, ''), response.coupon_savings);
                  } else {
                      recalculateCartFromRows();
                  }
                  showNotification(true, response.message || 'Cart updated.');
                  updateBadgeCounts();
                  return;
              }
              showNotification(false, (response && response.message) ? response.message : 'Could not update cart.');
          },
          error: function() {
              showNotification(false, 'Could not update cart. Please try again.');
          },
          complete: function() {
              setCartItemSaving($button, false);
          }
      });
   });

  $('body').on('click', '.removeFromCart', function (event) {
      event.preventDefault();
      event.stopImmediatePropagation();

      var $link = $(this);
      var $row = $link.closest('.product-row');
      var productIdCart = $link.data('product-id');

      $.ajax({
          type: 'POST',
          url: 'remove_from_cart.php',
          dataType: 'json',
          data: { product_id: productIdCart },
          success: function(response) {
              if (response && response.success) {
                  $row.remove();
                  $('#offcanvas-cart .sub-total .amount').text(response.subtotal);
                  if (typeof response.coupon_savings !== 'undefined') {
                      setCartCouponSummary(response.coupon_code || ($('#cart-coupon-label').text() || '').replace(/^COUPON\s+/i, ''), response.coupon_savings);
                  } else {
                      recalculateCartFromRows();
                  }
                  showNotification(true, response.message || 'Product removed from cart.');
                  updateBadgeCounts();
                  return;
              }
              showNotification(false, (response && response.message) ? response.message : 'Could not remove item.');
          },
          error: function() {
              showNotification(false, 'Could not remove item. Please try again.');
          }
      });
   });


    $('#inputCountry').change(function () {
        var country = $(this).val();
        if (country) {
            $.ajax({
                type: 'POST',
                url: 'get_provinces.php',
                data: { country: country },
                dataType: 'json',
                success: function (response) {
                    $('#inputState').html(response.options);
                    updateShippingCost('inputState');
                }
            });
        } else {
            $('#inputState').html('<option value="">Select State</option>');
            updateShippingCost('inputState');
        }
    });

    $('#inputState').change(function () {
        updateShippingCost('inputState');
        logAction('Calculated shipping', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
    });

    function updateShippingCost(stateDropdownId) {
        if (cartWeightKg <= 0) {
            updateCartDeliveryTotals();
            return;
        }

        var order_total = parseFloat("<?=$order_total?>");
        var shippingCost = parseFloat($('#' + stateDropdownId).find(':selected').data('shipping')) || 0;
        var free_shipping_amount = parseFloat(<?=$free_shipping_amount?>);
        var remainingForFreeShipping = free_shipping_amount - order_total;
        
        if (shippingCost !== undefined) {

            var grand_total = order_total;

            if (order_total > <?=$free_shipping_amount?>) {
                $('.your-order-bottom ul').html('<li></li><li class="your-order-shipping">Free Shipping!</li>');
            } else {
                grand_total += shippingCost;
                $('.your-order-bottom ul').html('<li class="your-order-shipping">Shipping</li><li>R' + shippingCost.toFixed(2) + '</li>');
                if (remainingForFreeShipping > 0) {
                  $('.your-order-bottom ul').prepend('<li ><span class="text-primary small" style="text-align:right;">Buy for R' + remainingForFreeShipping.toFixed(2) + ' more to get free shipping!</li>');
                }
            }

            $('#order-total').text("R" + grand_total.toFixed(2));
        } else {
            // Handle undefined shipping cost
            console.log("Shipping cost is undefined.");
        }
    }

    // Initial update on page load
    updateCartDeliveryTotals();

});

</script>

<?php
include 'generate_products_script.php';
include 'footer.php';
?>
