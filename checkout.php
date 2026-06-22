<?php
include 'session_logins.php';
include 'header.php';
require_once __DIR__ . '/google_integrations_helpers.php';

$page_url_canonical = "https://www.fishgelatine.co.za/v2/checkout";
$title_og = 'Checkout - Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/checkout"
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

<title>Secure Checkout - Sir Francis - Dried Fruit, Nuts, Sweets</title>

<?php
include 'page_menues.php';

echo renderCandybirdSiteFlags('checkout');

// Fetch cart items based on user or guest
$checkoutItems = getcartItems($userId, $guestIdentifier);

// Format cart items
$coupon_amount = 0;
$subtotals = 0;
$discounts = 0;
$order_total = 0;
$tax = 0;
$taxes = 0;
$checkout_products = "";
$checkout_weight_kg = 0;
$checkout_lead_time_notes = [];
$checkout_free_shipping_basis_total = 0;

foreach ($checkoutItems as $item) {
    
    $product_id = $item['id'];
    $product_name = $item['title'];
    $product_weight = $item['product_weight'];
    $product_display_title = trim($product_name . ' ' . $product_weight);
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

    if (isset($_SESSION['coupon'])) {
      $coupon_amount = $_SESSION['coupon']['coupon_savings'] ?? 0;
    }


    $isClearance = !empty($item['is_clearance']) && $item['is_clearance'] === 'yes';
    $sheetProduct = $isClearance ? getSheetProductById($item['source_product_id'] ?? $item['product_id'] ?? $product_id) : getSheetProductById($product_id);
    $freeDeliveryExcluded = (!empty($item['free_delivery_excluded']) && $item['free_delivery_excluded'] === 'yes') || isCandybirdFreeDeliveryExcluded($sheetProduct);
    if ($sheetProduct) {
      $product_display_title = $isClearance ? $item['title'] : getSheetProductDisplayTitle($sheetProduct);
      $checkout_weight_kg += getSheetProductWeightKg($sheetProduct) * $quantity;
      $itemLeadTime = trim((string)($sheetProduct['delivery_estimate'] ?? $sheetProduct['product_delivery_estimate'] ?? $sheetProduct['dispatch_estimate'] ?? $sheetProduct['lead_time'] ?? ''));
      if ($itemLeadTime !== '') {
        $checkout_lead_time_notes[] = [
          'title' => $product_display_title,
          'lead_time' => $itemLeadTime,
        ];
      }
    }

    $checkout_products .= '
    <li>
      <span class="order-middle-left"><a href="'.htmlspecialchars($item['product_url'] ?? ('product?id='.urlencode((string) $product_id)), ENT_QUOTES, 'UTF-8').'" target="_blank">'.htmlspecialchars($product_display_title, ENT_QUOTES, 'UTF-8').'</a>'.($isClearance ? ' <small class="text-danger font-weight-bold">Clearance</small>' : '').' (Qty: '.$quantity.')</span>';
    if ($freeDeliveryExcluded) {
      $checkout_products .= '<small class="d-block text-muted" style="font-size:11px;line-height:1.35;margin-top:3px;">Free shipping does not apply to this item.</small>';
    } else {
      $checkout_free_shipping_basis_total += $subtotal;
    }
    $checkout_products .= "<span class='product-price'>";

    if ($discount > 0) {
        $checkout_products .= "<small class='d-block text-muted'>Each</small>";
        $checkout_products .= "<del class='del' style='font-weight:normal;'>R".number_format($price, 2)."</del>";
        $checkout_products .= "<span class='onsale d-block'>R".number_format($discounted_price, 2)."</span>";
        $checkout_products .= "<small class='d-block mt-1'>Line: R".number_format($discounted_price * $quantity, 2)."</small>";
    } else {
        $checkout_products .= "<span>R".number_format($price, 2)."</span>";
        if ((int) $quantity > 1) {
            $checkout_products .= "<small class='d-block mt-1'>Line: R".number_format($price * $quantity, 2)."</small>";
        }
    }

    $checkout_products .= "</span>";


    $checkout_products .= '
    </li>
    ';
}

$order_total += $subtotals;
$order_total += $taxes;
$order_total -= $discounts;
$order_total -= $coupon_amount;
$checkout_free_shipping_basis = max(0, $checkout_free_shipping_basis_total - $coupon_amount);
// echo 'i am here! '.$order_total;
$delivery_options = getCandybirdDeliveryOptions();
$enabled_delivery_options = getCandybirdEnabledDeliveryOptions($delivery_options);
$default_delivery_method = getCandybirdDefaultDeliveryMethod($delivery_options);
$default_delivery_quote = getCandybirdDeliveryQuote($default_delivery_method, $checkout_weight_kg, $checkout_free_shipping_basis, $free_shipping_amount);
$google_places_api_key = sfGooglePlacesBrowserKey($conn ?? null);


$billing_first_name = '';
$billing_last_name = '';
$billing_company_name = '';
$billing_street_address_1 = '';
$billing_street_address_2 = '';
$billing_city = '';
$billing_country = '';
$billing_province = '';
$billing_post_code = '';
$billing_phone_number = '';
$billing_email_address = '';
$user_email = '';
$ship_to_different_address = '';
$shipping_first_name = '';
$shipping_last_name = '';
$shipping_company_name = '';
$shipping_street_address_1 = '';
$shipping_street_address_2 = '';
$shipping_city = '';
$shipping_country = '';
$shipping_province = '';
$shipping_post_code = '';
$shipping_phone_number = '';
$shipping_email_address = '';

// Output the checkbox with the appropriate state
$checkedAttribute = ($ship_to_different_address == 1) ? 'checked' : '';

if (($conn instanceof mysqli) && isset($_SESSION['user_id'])) {
    // Use prepared statement to fetch user and address details
    $sqlUserAddress = "SELECT * FROM user_addresses WHERE user_id = ?";
    $stmtUserAddress = mysqli_prepare($conn, $sqlUserAddress);
    mysqli_stmt_bind_param($stmtUserAddress, "i", $userId);
    mysqli_stmt_execute($stmtUserAddress);
    $resultUserAddress = mysqli_stmt_get_result($stmtUserAddress);

    if ($resultUserAddress && mysqli_num_rows($resultUserAddress) > 0) {
        $userAddressData = mysqli_fetch_assoc($resultUserAddress);

        // Assign user address details to variables
        $billing_first_name = $userAddressData['billing_first_name'];
        $billing_last_name = $userAddressData['billing_last_name'];
        $billing_company_name = $userAddressData['billing_company_name'];
        $billing_street_address_1 = $userAddressData['billing_street_address_1'];
        $billing_street_address_2 = $userAddressData['billing_street_address_2'];
        $billing_city = $userAddressData['billing_city'];
        $billing_country = $userAddressData['billing_country'];
        $billing_province = $userAddressData['billing_province'];
        $billing_post_code = $userAddressData['billing_post_code'];
        $billing_phone_number = $userAddressData['billing_phone_number'];
        $billing_email_address = $userAddressData['billing_email_address'];

        // Shipping details
        $ship_to_different_address = $userAddressData['ship_to_different_address'];
        $shipping_first_name = $userAddressData['shipping_first_name'];
        $shipping_last_name = $userAddressData['shipping_last_name'];
        $shipping_company_name = $userAddressData['shipping_company_name'];
        $shipping_street_address_1 = $userAddressData['shipping_street_address_1'];
        $shipping_street_address_2 = $userAddressData['shipping_street_address_2'];
        $shipping_city = $userAddressData['shipping_city'];
        $shipping_country = $userAddressData['shipping_country'];
        $shipping_province = $userAddressData['shipping_province'];
        $shipping_post_code = $userAddressData['shipping_post_code'];
        $shipping_phone_number = $userAddressData['shipping_phone_number'];
        $shipping_email_address = $userAddressData['shipping_email_address'];
    }
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
        // Check if the current country matches the user's billing or shipping country
        $selected = "";
        if (isset($billing_country) && $rowCountry['country'] == $billing_country) {
            $selected = "selected";
        } elseif (isset($shipping_country) && $rowCountry['country'] == $shipping_country) {
            $selected = "selected";
        }

        // Output <option> tag for each country
        $countries .= '<option value="' . $rowCountry['country'] . '" ' . $selected . '>' . $rowCountry['country'] . '</option>';
    }
}

$payment_methods = '';

// Fetch payment methods from the database
// Initialize variables
$paymentMethods = array();
$isFirstIteration = true; // Variable to track the first iteration

if ($conn instanceof mysqli) {
    $sql = "SELECT * FROM payment_methods";
    $result = mysqli_query($conn, $sql);
} else {
    $result = false;
}

// Check if there are results
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $paymentLabel = (string) ($row['label'] ?? '');
        $paymentDescription = (string) ($row['description'] ?? '');
        if (stripos($paymentLabel, 'ozow') !== false || stripos($paymentDescription, 'ozow') !== false) {
            continue;
        }
        // Store label and description in an associative array
        $paymentMethods[] = array(
            'id' => $row['id'],
            'label' => $paymentLabel,
            'description' => $paymentDescription
        );
    }
}

// Close the database connection
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}

// Example usage of the variables
foreach ($paymentMethods as $paymentMethod) {
    $payment_methods .= '<div class="panel payment-accordion">
      <div class="panel-heading" id="method-'.$paymentMethod['id'].'">
          <p class="panel-title">
              <input type="radio" form="checkout-form" required name="payment-method" value="'.$paymentMethod['id'].'" id="method-'.$paymentMethod['id'].'-radio" class="hidden-radio-input" '.($isFirstIteration ? 'checked' : '').'>
              <label for="method-'.$paymentMethod['id'].'-radio" data-toggle="collapse" data-parent="#accordion" href="#method'.$paymentMethod['id'].'">
                  '.$paymentMethod['label'].'
              </label>
          </h4>
      </div>
      <div id="method'.$paymentMethod['id'].'" class="panel-collapse collapse '.($isFirstIteration ? 'show' : '').'">
          <div class="panel-body">
              <p>
                  '.$paymentMethod['description'].'
              </p>
          </div>
      </div>
  </div>';

    // Set the flag to false after the first iteration
    $isFirstIteration = false;
}





?>

<form id="checkout-form" class="personal-information" action="checkout.inc.php" method="POST">

<!-- checkout area start -->
<div class="check-out-section pt-80 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-lg-7">
        <div class="billing-info-wrap">
          <h3 class="title">Billing Details</h3>

            <div class="row">
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>First Name</label>
                  <input required type="text" id="billing_first_name" name="billing_first_name" value="<?=$billing_first_name?>" />
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>Last Name</label>
                  <input required type="text" id="billing_last_name" name="billing_last_name" value="<?=$billing_last_name?>" />
                </div>
              </div>
              <div class="col-lg-12">
                <div class="billing-info mb-20px">
                  <label>Company Name</label>
                  <input type="text" id="billing_company_name" name="billing_company_name" value="<?=$billing_company_name?>" />
                </div>
              </div>
              <div class="col-lg-12">
                <div class="billing-info mb-20px">
                  <label>Street Address</label>
                  <input required
                    class="billing-address mb-3"
                    placeholder="House number and street name"
                    type="text"
                    id="billing_street_address_1" name="billing_street_address_1" value="<?=$billing_street_address_1?>"
                  />
                  <input
                    placeholder="Suburb, complex, apartment, suite, unit etc."
                    type="text"
                    id="billing_street_address_2" name="billing_street_address_2" value="<?=$billing_street_address_2?>"
                  />
                </div>
              </div>
              <div class="col-lg-12">
                <div class="billing-info mb-20px">
                  <label>Town / City</label>
                  <input required type="text" id="billing_city" name="billing_city" value="<?=$billing_city?>"/>
                </div>
              </div>
              <div class="col-lg-12">
                <div class="billing-info mb-20px">
                  <label for="billing_country" class="form-label">Country</label>
                  <input required type="text" id="billing_country" name="billing_country" value="<?=htmlspecialchars($billing_country ?: 'South Africa', ENT_QUOTES)?>" autocomplete="country-name" />
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>State / Province</label>
                  <input required type="text" id="billing_province" name="billing_province" value="<?=htmlspecialchars($billing_province, ENT_QUOTES)?>" autocomplete="address-level1" />
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>Postcode / ZIP</label>
                  <input required type="text" id="billing_post_code" name="billing_post_code" value="<?=$billing_post_code?>"/>
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>Phone</label>
                  <input required type="tel" id="billing_phone_number" name="billing_phone_number" value="<?=$billing_phone_number?>"/>
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>Email Address</label>
                  <input required type="email" id="billing_email_address" name="billing_email_address" value="<?=$billing_email_address?>"/>
                  <div id="checkout-account-hint" class="checkout-account-hint d-none" role="status"></div>
                </div>
              </div>
            </div>

            <div class="checkout-delivery-card mt-25 mb-30">
              <h3 class="title">Delivery Method</h3>
              <div class="delivery-method-grid">
                <?php foreach ($enabled_delivery_options as $methodKey => $method): ?>
                  <?php
                    $isDefaultMethod = $methodKey === $default_delivery_method;
                    $methodHelp = $method['estimate'] ?? '';
                    if ($methodKey === 'locker') {
                        $methodHelp = 'Free shipping can apply over R' . number_format($free_shipping_amount, 2) . ($methodHelp ? '. Estimate: ' . $methodHelp : '');
                    } elseif ($methodKey === 'collect') {
                        $methodHelp = 'Collect from Sir Francis. ' . ($methodHelp ? 'Estimate: ' . $methodHelp : 'No courier fee.');
                    } elseif ($methodHelp) {
                        $methodHelp = 'Estimate: ' . $methodHelp;
                    }
                  ?>
                  <label class="delivery-method-option <?=$isDefaultMethod ? 'active' : ''?>" for="delivery-<?=htmlspecialchars($methodKey, ENT_QUOTES)?>">
                    <input type="radio" id="delivery-<?=htmlspecialchars($methodKey, ENT_QUOTES)?>" name="delivery_method" value="<?=htmlspecialchars($methodKey, ENT_QUOTES)?>" <?=$isDefaultMethod ? 'checked' : ''?> required>
                    <span>
                      <strong><?=htmlspecialchars($method['label'] ?? ucfirst($methodKey), ENT_QUOTES)?></strong>
                      <small><?=htmlspecialchars($methodHelp, ENT_QUOTES)?></small>
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
              <?php foreach ($enabled_delivery_options as $methodKey => $method): ?>
                <div class="delivery-tier-box <?=$methodKey === $default_delivery_method ? '' : 'd-none'?>" id="<?=htmlspecialchars($methodKey, ENT_QUOTES)?>-tier-box">
                  <strong><?=htmlspecialchars($method['label'] ?? ucfirst($methodKey), ENT_QUOTES)?> details</strong>
                  <?php if ($methodKey === 'collect'): ?>
                    <span>No courier charge.</span>
                    <?php if (!empty($method['collection_address'])): ?><span>Collect from: <?=htmlspecialchars($method['collection_address'], ENT_QUOTES)?></span><?php endif; ?>
                  <?php else: ?>
                    <?php foreach (($method['tiers'] ?? []) as $tier): ?>
                      <span>R<?=number_format((float)($tier['price'] ?? 0), 0)?> <?=htmlspecialchars(strtolower($tier['label'] ?? ''), ENT_QUOTES)?></span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  <?php if (!empty($method['estimate'])): ?><span>Estimate: <?=htmlspecialchars($method['estimate'], ENT_QUOTES)?></span><?php endif; ?>
                </div>
              <?php endforeach; ?>
              <p class="delivery-note mb-0"><?=($checkout_weight_kg <= 0 ? 'Digital product. No courier delivery is needed.' : 'Estimated order weight: ' . number_format($checkout_weight_kg, 2) . 'kg. The correct tier is selected automatically.')?></p>
              <?php if (!empty($checkout_lead_time_notes)): ?>
                <div class="checkout-delay-note" role="status">
                  <strong>Some items may affect dispatch time.</strong>
                  <span>Consider ordering these separately if you need the rest sooner.</span>
                  <ul>
                    <?php foreach ($checkout_lead_time_notes as $leadNote): ?>
                      <li><?=htmlspecialchars($leadNote['title'], ENT_QUOTES)?>: <?=htmlspecialchars($leadNote['lead_time'], ENT_QUOTES)?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
              <input type="hidden" name="shipping_tier" id="shipping_tier" value="<?=$default_delivery_quote['tier_key']?>">
            </div>

          <?php
          // Check if the user is logged in
          if (!isset($_SESSION['user_id'])) {
          ?>
          <div class="checkout-account mb-5">
            <input id="id2" class="checkout-toggle" type="checkbox" name="create_account" value="1" />
            <label for="id2">Create an account?</label>
          </div>
          <?php              
          }

          ?>

          <div class="checkout-account-toggle open-toggle mb-30">
            <input placeholder="Choose a Username" type="text" id="user-name" name="user-name" disabled />
            <input placeholder="Choose a Strong Password" type="password" id="password" name="password" disabled />
          </div>

          <div class="additional-info-wrap">
            <h4 class="title">Additional information</h4>
            <div class="additional-info">
              <label class="mb-2">Order notes</label>
              <textarea
                placeholder="Notes about your order, e.g. special notes for delivery. "
                name="order_notes"
                id="order_notes"
              ></textarea>
            </div>
          </div>
          <div class="checkout-account mt-25 d-none">
              <input id="ship_to_different_address" name="ship_to_different_address" class="checkout-toggle" type="checkbox" <?= $checkedAttribute ?> />
              <label for="ship">Ship to a different address?</label>
          </div>
          <div class="different-address open-toggle mt-30">
            <div class="row">
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>First Name</label>
                  <input type="text" id="shipping_first_name" name="shipping_first_name" value="<?=$shipping_first_name?>" />
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>Last Name</label>
                  <input type="text" id="shipping_last_name" name="shipping_last_name" value="<?=$shipping_last_name?>" />
                </div>
              </div>
              <div class="col-lg-12">
                <div class="billing-info mb-20px">
                  <label>Company Name</label>
                  <input type="text" id="shipping_company_name" name="shipping_company_name" value="<?=$shipping_company_name?>" />
                </div>
              </div>
              <div class="col-lg-12">
                <div class="billing-info mb-20px">
                  <label>Street Address</label>
                  <input
                    class="shipping-address mb-3"
                    placeholder="House number and street name"
                    type="text"
                    id="shipping_street_address_1" name="shipping_street_address_1" value="<?=$shipping_street_address_1?>"
                  />
                  <input
                    placeholder="Suburb, complex, apartment, suite, unit etc."
                    type="text"
                    id="shipping_street_address_2" name="shipping_street_address_2" value="<?=$shipping_street_address_2?>"
                  />
                </div>
              </div>
              <div class="col-lg-12">
                <div class="billing-info mb-20px">
                  <label>Town / City</label>
                  <input type="text" id="shipping_city" name="shipping_city" value="<?=$shipping_city?>"/>
                </div>
              </div>
              <div class="col-lg-12">
                <div class="billing-info mb-20px">
                  <label for="shipping_country" class="form-label">Country</label>
                  <input type="text" id="shipping_country" name="shipping_country" value="<?=htmlspecialchars($shipping_country, ENT_QUOTES)?>" autocomplete="country-name" />
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>State / Province</label>
                  <input type="text" id="shipping_province" name="shipping_province" value="<?=htmlspecialchars($shipping_province, ENT_QUOTES)?>" autocomplete="address-level1" />
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>Postcode / ZIP</label>
                  <input type="text" id="shipping_post_code" name="shipping_post_code" value="<?=$shipping_post_code?>"/>
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>Phone</label>
                  <input type="tel" id="shipping_phone_number" name="shipping_phone_number" value="<?=$shipping_phone_number?>"/>
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <label>Email Address</label>
                  <input type="email" id="shipping_email_address" name="shipping_email_address" value="<?=$shipping_email_address?>"/>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-5 mt-4 mt-lg-0">
        <div class="your-order-area">
          <h3 class="title">Your order</h3>
          <div class="your-order-wrap gray-bg-4">
            <div class="your-order-product-info">
              <div class="your-order-top">
                <ul>
                  <li>Product</li>
                  <li><a href="cart" class="small">Edit cart</a></li>
                </ul>
              </div>
              <div class="your-order-middle">
                <ul>
                  <?=$checkout_products?>
                </ul>
              </div>

              <style>
                .checkout-delivery-card,
                .checkout-coupon-box {
                    border: 1px solid #e6e1d8;
                    background: #fff;
                    padding: 18px;
                    margin-bottom: 20px;
                }

                .delivery-method-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 12px;
                    margin-bottom: 14px;
                }

                .delivery-method-option {
                    display: flex;
                    gap: 10px;
                    align-items: flex-start;
                    border: 1px solid #d8d1c7;
                    padding: 14px;
                    cursor: pointer;
                    background: #faf8f4;
                }

                .delivery-method-option.active {
                    border-color: #28364B;
                    box-shadow: 0 0 0 2px rgba(206, 189, 136, 0.34);
                    background: #fff;
                }

                .delivery-method-option strong,
                .delivery-method-option small {
                    display: block;
                }

                .delivery-tier-box {
                    display: grid;
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 8px;
                    background: #f7f3ee;
                    padding: 12px;
                    color: #333;
                    margin-bottom: 10px;
                }

                .delivery-note,
                .checkout-delay-note,
                .checkout-feedback {
                    color: #4f4a45;
                    font-size: 14px;
                    line-height: 1.5;
                }

                .checkout-delay-note {
                    background: #fff5f1;
                    border: 1px solid #f2c2b2;
                    color: #7a2e18;
                    padding: 12px 14px;
                    margin-top: 10px;
                }

                .checkout-delay-note strong,
                .checkout-delay-note span {
                    display: block;
                }

                .checkout-delay-note ul {
                    margin: 8px 0 0 18px;
                    padding: 0;
                }

                .coupon-input-row {
                    display: flex;
                    gap: 8px;
                }

                .coupon-input-row input {
                    flex: 1;
                    min-width: 0;
                    height: 42px;
                    border: 1px solid #d8d1c7;
                    padding: 0 12px;
                }

                .checkout-error-summary {
                    background: #fff3f0;
                    border: 1px solid #d93025;
                    color: #7a1d14;
                    padding: 14px 16px;
                    margin-bottom: 20px;
                }

                .checkout-error-summary ul {
                    margin: 8px 0 0 18px;
                }

                .checkout-field-error,
                .checkout-account-hint,
                .checkout-feedback.error {
                    color: #b42318;
                    font-size: 13px;
                    margin-top: 6px;
                }

                .checkout-account-hint {
                    background: #fff8e7;
                    border: 1px solid #f1d6a5;
                    color: #5d3b00;
                    line-height: 1.45;
                    padding: 10px 12px;
                }

                .checkout-account-hint a {
                    color: #5b1178;
                    font-weight: 700;
                    text-decoration: underline;
                }

                .checkout-account-hint.success {
                    background: #eefaf1;
                    border-color: #b7e3c1;
                    color: #1d7d38;
                }

                .checkout-feedback.success {
                    color: #087443;
                    font-size: 13px;
                    margin-top: 6px;
                }

                .checkout-payment-trust {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 7px;
                    margin: 0 0 14px;
                }

                .checkout-payment-trust span {
                    align-items: center;
                    background: #fff;
                    border: 1px solid #ddd4ca;
                    color: #3a302b;
                    display: inline-flex;
                    font-size: 12px;
                    font-weight: 700;
                    gap: 5px;
                    line-height: 1.2;
                    padding: 6px 8px;
                }

                .checkout-payment-trust i {
                    color: #5b1178;
                    font-size: 15px;
                }

                .checkout-invalid {
                    border-color: #d93025 !important;
                    box-shadow: 0 0 0 2px rgba(217, 48, 37, 0.12);
                }

                @media screen and (max-width: 767px) {
                    .delivery-method-grid,
                    .delivery-tier-box {
                        grid-template-columns: 1fr;
                    }

                    .coupon-input-row {
                        flex-direction: column;
                    }
                }

                .your-order-coupon {
                    margin-top: 10px;
                    padding: 10px;
                    border: 1px dashed #ccc;
                    border-radius: 5px;
                    background-color: #f9f9f9;
                    text-align: center;
                }

                .your-order-coupon ul {
                    list-style-type: none;
                    padding: 0;
                }

                .your-order-coupon .coupon-label {
                    font-weight: bold;
                }

                .your-order-coupon .coupon-amount {
                    color: red; /* or any color you prefer for discounts */
                    font-size: 1.2em;
                    font-weight: bold;
                }

              </style>
              
                  <div class="your-order-coupon<?=isset($_SESSION['coupon']['code']) && !empty($_SESSION['coupon']['code']) ? '' : ' d-none'?>" id="checkout-coupon-summary">
                      <ul>
                          <li class="coupon-label" id="checkout-coupon-label">COUPON <?=htmlspecialchars($_SESSION['coupon']['code'] ?? '', ENT_QUOTES, 'UTF-8')?></li>
                          <li class="coupon-amount"><span id="coupon_amount_final">- R<?= number_format($_SESSION['coupon']['coupon_savings'] ?? 0, 2) ?></span> <a href="#" id="checkout-remove-coupon" title="Remove Coupon">[X]</a></li>
                      </ul>
                      <span id="coupon_info"></span>
                      <input type="hidden" name="coupon_id" id="coupon_id">
                  </div>



              <div class="your-order-middle shipping">
                <ul>
                  <li class="your-order-shipping">Shipping</li>
                  <li id="shipping-summary">R<?=number_format($default_delivery_quote['payable_shipping_amount'], 2)?></li>
                </ul>
                <input type="hidden" name="shipping_id" id="shipping_id" value="<?=$default_delivery_quote['tier_key']?>">
              </div>


              <!-- <div class="your-order-middle">
                <ul>
                  <li><span class="order-middle-left">VAT</span>R<?=$taxes?><span></span></li>
                </ul>
              </div> -->



              <div class="your-order-total">
                <ul>
                  <li class="order-total">Total</li>
                  <li id="order-total">R<?=number_format($order_total + $default_delivery_quote['payable_shipping_amount'], 2)?></li>
                </ul>
              </div>

              <div class="checkout-coupon-box mt-20">
                <label for="coupon_code">Coupon code</label>
                <div class="coupon-input-row">
                  <input type="text" id="coupon_code" name="coupon_code" placeholder="Enter coupon code">
                  <button type="button" class="btn btn-dark" id="apply-coupon-btn">Apply</button>
                </div>
                <div id="coupon-feedback" class="checkout-feedback" aria-live="polite"></div>
              </div>

            </div>
            <div class="payment-method">
              <h3 class="mb-3">Choose a Payment Method:</h3>
              <div class="checkout-payment-trust" aria-label="Secure payment options">
                <span><i class="fas fa-lock"></i> Secure checkout</span>
                <span><i class="fab fa-cc-visa"></i> Visa</span>
                <span><i class="fab fa-cc-mastercard"></i> Mastercard</span>
                <span>PayFast</span>
                <span>EFT</span>
              </div>
              <div class="payment-accordion element-mrg">
                  <div class="panel-group" id="accordion">
            
                    <?=$payment_methods?>            

                  </div>
              </div>
          </div>

          </div>
          <div class="Place-order mt-25">
            <button class="btn btn--xl btn-block btn-primary" type="submit" name="place_order" id="place_order" 
              >Place Order</button>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

</form>
<!-- checkout area end -->

<div class="modal fade" id="pudoLockerConfirmModal" tabindex="-1" aria-labelledby="pudoLockerConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pudoLockerConfirmModalLabel">Confirm Pudo locker delivery</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
      </div>
      <div class="modal-body">
        <p class="mb-2">You selected delivery to your nearest Pudo locker.</p>
        <p class="mb-0">Please make sure you are happy for your parcel to be delivered to a nearby locker based on your address details. If you prefer delivery directly to your door, go back and choose door-to-door delivery before placing the order.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-dark" id="changeToDoorDeliveryBtn">Go back and change delivery</button>
        <button type="button" class="btn btn-primary" id="confirmPudoLockerBtn">Yes, continue with Pudo locker</button>
      </div>
    </div>
  </div>
</div>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
$(document).ready(function () {

var user_session = <?=json_encode($_SESSION['session_id'] ?? session_id())?>;
var checkoutSubtotal = <?=json_encode((float) $order_total)?>;
var checkoutFreeShippingBasis = <?=json_encode((float) $checkout_free_shipping_basis)?>;
var checkoutWeightKg = <?=json_encode((float) $checkout_weight_kg)?>;
var freeShippingAmount = <?=json_encode((float) $free_shipping_amount)?>;
var deliveryOptions = <?=json_encode($delivery_options)?>;
var defaultDeliveryMethod = <?=json_encode($default_delivery_method)?>;
var checkoutCouponAmount = <?=json_encode(isset($_SESSION['coupon']['coupon_savings']) ? (float) $_SESSION['coupon']['coupon_savings'] : 0)?>;
var checkoutBaseSubtotal = checkoutSubtotal + checkoutCouponAmount;
var checkoutFreeShippingBasisBase = checkoutFreeShippingBasis + checkoutCouponAmount;
var pudoLockerConfirmed = false;
var pendingCheckoutSubmit = false;

function formatRand(amount) {
    return 'R' + (parseFloat(amount) || 0).toFixed(2);
}

function checkoutModal(action) {
    var modalEl = document.getElementById('pudoLockerConfirmModal');
    if (!modalEl) return;
    if (window.bootstrap && bootstrap.Modal) {
        var instance = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (action === 'hide') {
            instance.hide();
        } else {
            instance.show();
        }
        return;
    }
    if ($.fn.modal) {
        $('#pudoLockerConfirmModal').modal(action || 'show');
        return;
    }
    modalEl.classList.toggle('show', action !== 'hide');
    modalEl.style.display = action === 'hide' ? 'none' : 'block';
    modalEl.setAttribute('aria-hidden', action === 'hide' ? 'true' : 'false');
}

function isSouthAfricaCountry(value) {
    var normalized = String(value || '').trim().toLowerCase();
    return normalized === '' || normalized === 'south africa' || normalized === 'sa' || normalized === 'za' || normalized === 'zaf';
}

function getCheckoutCountry() {
    var shippingCountry = $('#ship_to_different_address').is(':checked') ? $('#shipping_country').val() : '';
    return shippingCountry || $('#billing_country').val() || 'South Africa';
}

function getDeliveryQuote(method) {
    if (checkoutWeightKg <= 0) {
        return {
            tierKey: 'digital',
            tier: {label: 'Digital delivery'},
            shippingAmount: 0,
            shippingDiscount: 0,
            payableShipping: 0,
            freeEligible: false,
            digital: true
        };
    }

    if (!isSouthAfricaCountry(getCheckoutCountry())) {
        return {
            tierKey: 'international_quote',
            tier: {label: 'Shipping quote to follow'},
            shippingAmount: 0,
            shippingDiscount: 0,
            payableShipping: 0,
            freeEligible: false,
            international: true
        };
    }

    var option = deliveryOptions[method] || deliveryOptions[defaultDeliveryMethod] || {};
    if (option.enabled === false) {
        method = defaultDeliveryMethod;
        option = deliveryOptions[method] || option;
    }
    var tierKey = null;
    var tier = null;

    Object.keys(option.tiers).some(function(key) {
        var candidate = option.tiers[key];
        if (candidate.max_kg === null || checkoutWeightKg <= parseFloat(candidate.max_kg)) {
            tierKey = key;
            tier = candidate;
            return true;
        }
        return false;
    });

    if (!tier) {
        var keys = Object.keys(option.tiers);
        tierKey = keys[keys.length - 1];
        tier = option.tiers[tierKey];
    }

    var shippingAmount = parseFloat(tier.price) || 0;
    var tierMaxKg = tier.max_kg === null ? null : parseFloat(tier.max_kg);
    var shippingDiscount = option.free_shipping_eligible && checkoutFreeShippingBasis >= freeShippingAmount && tierMaxKg !== null && tierMaxKg <= 20 ? shippingAmount : 0;

    return {
        tierKey: tierKey,
        tier: tier,
        shippingAmount: shippingAmount,
        shippingDiscount: shippingDiscount,
        payableShipping: Math.max(0, shippingAmount - shippingDiscount),
        freeEligible: !!option.free_shipping_eligible,
        estimate: option.estimate || '',
        collectionAddress: option.collection_address || ''
    };
}

function updateCheckoutTotals() {
    var method = $('input[name="delivery_method"]:checked').val() || defaultDeliveryMethod;
    var quote = getDeliveryQuote(method);
    var grandTotal = checkoutSubtotal + quote.payableShipping;

    $('#shipping_id').val(quote.tierKey);
    $('#shipping_tier').val(quote.tierKey);
    $('#shipping-summary').text(quote.payableShipping > 0 ? formatRand(quote.payableShipping) : 'Free');
    $('#order-total').text(formatRand(grandTotal));
    $('#orderTotalAmount').val(grandTotal.toFixed(2));
    $('#payTotal').val(grandTotal.toFixed(2));

    if (quote.digital) {
        $('.delivery-note').text('Digital product. No courier delivery is needed.');
        return;
    }

    if (quote.international) {
        $('.delivery-note').text('Shipping outside South Africa will be quoted separately. We will confirm the courier cost by email, WhatsApp, or phone before dispatch.');
        return;
    }

    var note = quote.tier.label + ' selected.';
    if (method === 'collect') {
        note += ' No courier fee.';
        if (quote.collectionAddress) {
            note += ' Collection address: ' + quote.collectionAddress + '.';
        }
    }
    if (quote.shippingDiscount > 0) {
        note += ' Free shipping applied.';
    } else if (quote.freeEligible && freeShippingAmount > checkoutFreeShippingBasis) {
        note += ' Add ' + formatRand(freeShippingAmount - checkoutFreeShippingBasis) + ' more in eligible items for free shipping.';
    } else if (!quote.freeEligible && method !== 'collect') {
        note += ' Free shipping does not apply to this delivery method.';
    }
    if (quote.estimate) {
        note += ' Estimate: ' + quote.estimate + '.';
    }
    $('.delivery-note').text('Estimated order weight: ' + checkoutWeightKg.toFixed(2) + 'kg. ' + note);
}

function setCheckoutCouponSummary(code, amount) {
    checkoutCouponAmount = parseFloat(amount) || 0;
    checkoutSubtotal = Math.max(0, checkoutBaseSubtotal - checkoutCouponAmount);
    checkoutFreeShippingBasis = Math.max(0, checkoutFreeShippingBasisBase - checkoutCouponAmount);

    if (checkoutCouponAmount > 0 && code) {
        $('#checkout-coupon-summary').removeClass('d-none');
        $('#checkout-coupon-label').text('COUPON ' + code);
        $('#coupon_amount_final').text('- ' + formatRand(checkoutCouponAmount));
    } else {
        $('#checkout-coupon-summary').addClass('d-none');
        $('#checkout-coupon-label').text('COUPON');
        $('#coupon_amount_final').text('- R0.00');
    }

    updateCheckoutTotals();
}

function clearCheckoutErrors() {
    $('.checkout-error-summary').remove();
    $('.checkout-field-error').remove();
    $('.checkout-invalid').removeClass('checkout-invalid');
}

function showFieldError($field, message) {
    $field.addClass('checkout-invalid');
    $('<div class="checkout-field-error"></div>').text(message).insertAfter($field);
}

function showCheckoutSummary($form, errors) {
    var $summary = $('<div class="checkout-error-summary" role="alert"></div>');
    $summary.append('<strong>Please check these details:</strong>');
    var $list = $('<ul></ul>');
    errors.forEach(function(error) { $list.append($('<li></li>').text(error)); });
    $summary.append($list);
    $form.find('.billing-info-wrap').prepend($summary);
    $('html, body').animate({ scrollTop: $summary.offset().top - 110 }, 250);
}

function renderAccountHint(exists, message, loginUrl) {
    var $hint = $('#checkout-account-hint');
    if (!$hint.length) {
        return;
    }

    if (!message) {
        $hint.addClass('d-none').removeClass('success').empty();
        return;
    }

    var html = $('<span></span>').text(message).html();
    if (exists) {
        html += ' <a href="' + (loginUrl || 'login?redirect=checkout') + '">Log in before checkout</a>';
    }

    $hint
        .toggleClass('success', !exists)
        .removeClass('d-none')
        .html(html);
}

function validateCheckoutForm($form) {
    clearCheckoutErrors();
    var errors = [];
    $form.find('[required]:visible').each(function() {
        var $field = $(this);
        if (!$field.val()) {
            var label = $field.closest('.billing-info, .billing-select, .shipping-select').find('label').first().text() || 'Required field';
            showFieldError($field, label + ' is required.');
            errors.push(label + ' is required.');
        }
    });

    if (!$('input[name="payment-method"]:checked').length) {
        errors.push('Please choose a payment method.');
    }

    var $createAccount = $('#id2');
    if ($createAccount.length && $createAccount.is(':checked')) {
        var $usernameField = $('#user-name');
        var $passwordField = $('#password');

        if (!$.trim($usernameField.val())) {
            showFieldError($usernameField, 'Choose a username, or untick create account to checkout as a guest.');
            errors.push('Choose a username, or untick create account to checkout as a guest.');
        }

        if (!$.trim($passwordField.val())) {
            showFieldError($passwordField, 'Choose a password, or untick create account to checkout as a guest.');
            errors.push('Choose a password, or untick create account to checkout as a guest.');
        } else if ($.trim($passwordField.val()).length < 8) {
            showFieldError($passwordField, 'Password must be at least 8 characters.');
            errors.push('Password must be at least 8 characters.');
        }
    }

    if (errors.length) {
        showCheckoutSummary($form, errors);
        return false;
    }

    return true;
}

function selectedDeliveryMethod() {
    return $('input[name="delivery_method"]:checked').val() || defaultDeliveryMethod;
}

function focusDeliveryMethods() {
    var $deliveryCard = $('.checkout-delivery-card').first();
    if ($deliveryCard.length) {
        $('html, body').animate({ scrollTop: $deliveryCard.offset().top - 110 }, 250);
    }
}

function submitCheckoutAjax($form) {
    var formData = $form.serialize();
    var $submitButton = $form.find('button[type="submit"]');

    $submitButton.prop('disabled', true);

    $.ajax({
        type: 'POST',
        url: 'checkout.inc.php',
        data: formData,
        dataType: 'json',
        success: function (response) {
            showNotification(response.success, response.message);

            if (response.success) {
                window.location.href = response.redirect_url || ('order_details?order_id=' + response.orderId);
            } else {
                clearCheckoutErrors();
                showCheckoutSummary($form, [response.message || 'Checkout could not be completed. Please check your details and try again.']);
                if (response.account_exists) {
                    renderAccountHint(true, response.message, response.login_url);
                }
            }
        },
        error: function (x, y, z) {
            showNotification(false, 'Checkout could not be completed. Please check the highlighted details and try again.');
            clearCheckoutErrors();
            showCheckoutSummary($form, ['Checkout could not be completed. Please check your details and try again.']);
            console.log('Error:', x, y, z);
        },
        complete: function() {
            $submitButton.prop('disabled', false);
            pendingCheckoutSubmit = false;
        }
    });
}

$('body').on('change', 'input[name="delivery_method"]', function() {
    pudoLockerConfirmed = false;
    $('.delivery-method-option').removeClass('active');
    $(this).closest('.delivery-method-option').addClass('active');
    $('.delivery-tier-box').addClass('d-none');
    $('#' + $(this).val() + '-tier-box').removeClass('d-none');
    updateCheckoutTotals();
});

$('#apply-coupon-btn').on('click', function() {
    var code = $('#coupon_code').val().trim();
    var checkoutEmail = $('#billing_email_address').val().trim();
    var $feedback = $('#coupon-feedback');
    if (!code) {
        $feedback.removeClass('success').addClass('error').text('Enter a coupon code first.');
        return;
    }
    if (!checkoutEmail) {
        $feedback.removeClass('success').addClass('error').text('Enter your email address before applying a coupon.');
        $('#billing_email_address').focus();
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'apply_coupon.php',
        data: { coupon_code: code, billing_email_address: checkoutEmail, billing_phone_number: $('#billing_phone_number').val().trim() },
        dataType: 'json',
        success: function(response) {
            $feedback.toggleClass('success', !!response.success).toggleClass('error', !response.success).text(response.message || 'Coupon checked.');
            if (response.success) {
                setCheckoutCouponSummary(response.coupon_code || code.toUpperCase(), response.coupon_savings || 0);
                $('#coupon_code').val('');
            }
        },
        error: function() {
            $feedback.removeClass('success').addClass('error').text('Coupon could not be checked. Please try again.');
        }
    });
});

$('body').on('click', '#checkout-remove-coupon', function(e) {
    e.preventDefault();
    var $feedback = $('#coupon-feedback');

    $.ajax({
        type: 'POST',
        url: 'remove_coupon.php',
        dataType: 'json',
        success: function(response) {
            $feedback.toggleClass('success', !!response.success).toggleClass('error', !response.success).text(response.message || 'Coupon removed.');
            if (response.success) {
                setCheckoutCouponSummary('', 0);
            }
        },
        error: function() {
            $feedback.removeClass('success').addClass('error').text('Coupon could not be removed. Please try again.');
        }
    });
});

$('body').on('submit', '#checkout-form', function(e){
    e.preventDefault();

    var $form = $(this);

    if (!validateCheckoutForm($form)) {
        return;
    }

    if (selectedDeliveryMethod() === 'locker' && checkoutWeightKg > 0 && !pudoLockerConfirmed) {
        pendingCheckoutSubmit = true;
        checkoutModal('show');
        return;
    }

    submitCheckoutAjax($form);
});

$('#confirmPudoLockerBtn').on('click', function() {
    pudoLockerConfirmed = true;
    checkoutModal('hide');
    if (pendingCheckoutSubmit) {
        $('#checkout-form').trigger('submit');
    }
});

$('#changeToDoorDeliveryBtn').on('click', function() {
    pendingCheckoutSubmit = false;
    checkoutModal('hide');
    if ($('#delivery-door').length) {
        $('#delivery-door').prop('checked', true).trigger('change');
    }
    focusDeliveryMethods();
});



$('body').on('change', 'input[type="radio"]', function () {
    if ($(this).is(':checked')) {
        // Collapse all panels
        $('.panel-collapse').collapse('hide');
        
        // Expand the selected panel
        var targetPanelId = $(this).attr('data-target');
        $(targetPanelId).collapse('show');
    }
});

$('body').on('click', 'input[name="payment-method"]', function() {
    var radioId = $(this).attr("id");
    $("label[for='" + radioId + "']").click();
});

function updateShippingInformation(shippingCost) {
    updateCheckoutTotals();
    return;
    // Retrieve and parse the discount value from the PHP session
    var couponDiscountValue = parseFloat(<?php echo isset($_SESSION['coupon']['discount_value']) ? json_encode($_SESSION['coupon']['discount_value']) : '0'; ?>) || 0;

    // Validate and calculate discount amount
    couponDiscountValue = Math.max(0, Math.min(couponDiscountValue, 100)); // Clamp value to 0-100
    var discountAmount = shippingCost * (couponDiscountValue / 100);
    
    // Retrieve and parse additional data
    var orderTotal = parseFloat(<?=$order_total?>) || 0;
    var freeShippingAmount = parseFloat(<?=$free_shipping_amount?>) || 0;
    var remainingForFreeShipping = freeShippingAmount - orderTotal;
    var grandTotal;

    // Determine if free shipping applies
    if (orderTotal > freeShippingAmount) {
        // Apply free shipping
        grandTotal = orderTotal;
        $('.your-order-middle.shipping ul').html('<li></li><li class="your-order-shipping">Free Shipping!</li>');
        $('#coupon_amount_final').text(''); // Clear the coupon amount display

        // Add message about free shipping
        if ($('.your-order-coupon').length) {
            $('#coupon_info').text('You already have free shipping, so your shipping discount coupon was not applied.');
        }

    } else {
        // Apply shipping cost and coupon discount
        grandTotal = orderTotal + shippingCost - discountAmount;
        if (shippingCost > 0) {
            $('.your-order-middle.shipping ul').html('<li class="your-order-shipping">Shipping <span style="font-size: 1.2em;font-weight:bold;">R' + shippingCost.toFixed(2) + '</span></li>');
            if (remainingForFreeShipping > 0) {
                $('.your-order-middle.shipping ul').append('<li style="color:grey;">Buy for R' + remainingForFreeShipping.toFixed(2) + ' more to get free shipping to your nearest locker!</li>');
            }
            $('#coupon_amount_final').text('-R'+discountAmount.toFixed(2)); // Show the coupon amount if applicable
            $('#coupon_info').text('You save on shipping!');
        } else {
            $('.your-order-middle.shipping ul').html('<li class="your-order-shipping">Select address to calculate shipping costs</li>');
            grandTotal = orderTotal; // No shipping cost to add
            $('#coupon_amount_final').text(''); // Clear the coupon amount display
        }
    }

    // Update order total
    $('#order-total').text("R" + grandTotal.toFixed(2));
    $('#orderTotalAmount').val(grandTotal.toFixed(2));
    $('#payTotal').val(grandTotal.toFixed(2));
}

    $('body').on('input change', '#billing_country, #shipping_country, #ship_to_different_address', updateCheckoutTotals);

  // Initial update on page load
  updateCheckoutTotals();

  function syncCreateAccountFields() {
      var enabled = $('#id2').is(':checked');
      $('#user-name, #password').prop('disabled', !enabled);
      if (!enabled) {
          $('#user-name, #password').val('');
      }
  }

  syncCreateAccountFields();
  $('body').on('change', '#id2', syncCreateAccountFields);

  var accountCheckTimer = null;
  $('body').on('input blur', '#billing_email_address', function () {
      var email = $.trim($(this).val());
      clearTimeout(accountCheckTimer);

      if (!email || !/.+@.+\..+/.test(email)) {
          renderAccountHint(false, '', '');
          return;
      }

      accountCheckTimer = setTimeout(function () {
          $.ajax({
              type: 'POST',
              url: 'check_checkout_email.php',
              dataType: 'json',
              data: { email: email },
              success: function (response) {
                  if (response && response.exists) {
                      renderAccountHint(true, 'This email already has a profile. You can log in to use saved details, or continue and this order will still be saved to that profile.', response.login_url);
                  } else {
                      renderAccountHint(false, '', '');
                  }
              }
          });
      }, 350);
  });


  //Log items
  // $(".checkout-toggle33").on("click", function () {
  //   $(".open-toggle33").slideToggle(1000);
  //   logAction('Clicked on "Ship to a different address?"', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
  // });
  // $(".checkout-toggle55").on("click", function () {
  //   $(".open-toggle55").slideToggle(1000);
  //   logAction('Clicked on "Create an account?"', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
  // });

});

window.initCandybirdAddressAutocomplete = function initCandybirdAddressAutocomplete() {
    if (!window.google || !google.maps || !google.maps.places) return;

    function setField(id, value, overwrite) {
        var field = document.getElementById(id);
        if (!field || (!overwrite && field.value)) return;
        field.value = value || field.value;
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function applyAddress(prefix, place) {
        var fields = {
            street_number: '',
            route: '',
            locality: '',
            postal_town: '',
            administrative_area_level_1: '',
            sublocality: '',
            sublocality_level_1: '',
            neighborhood: '',
            country: '',
            postal_code: ''
        };

        (place.address_components || []).forEach(function(component) {
            (component.types || []).forEach(function(type) {
                if (Object.prototype.hasOwnProperty.call(fields, type)) {
                    fields[type] = component.long_name;
                }
            });
        });

        var street = ((fields.street_number + ' ' + fields.route).replace(/\s+/g, ' ')).trim();
        var suburb = fields.sublocality_level_1 || fields.sublocality || fields.neighborhood || '';
        if (street) setField(prefix + '_street_address_1', street, true);
        if (suburb) setField(prefix + '_street_address_2', suburb, false);
        setField(prefix + '_city', fields.locality || fields.postal_town, false);
        setField(prefix + '_province', fields.administrative_area_level_1, false);
        setField(prefix + '_country', fields.country, false);
        setField(prefix + '_post_code', fields.postal_code, false);
        if (typeof updateCheckoutTotals === 'function') {
            updateCheckoutTotals();
        }
    }

    function attachAutocomplete() {
        ['billing', 'shipping'].forEach(function(prefix) {
            var input = document.getElementById(prefix + '_street_address_1');
            if (!input || input.dataset.mapsReady === '1') return;
            input.dataset.mapsReady = '1';
            input.setAttribute('autocomplete', 'off');
            var autocomplete = new google.maps.places.Autocomplete(input, {
                fields: ['address_components', 'formatted_address'],
                types: ['address'],
                componentRestrictions: { country: 'za' }
            });
            autocomplete.addListener('place_changed', function() {
                applyAddress(prefix, autocomplete.getPlace());
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachAutocomplete);
    } else {
        attachAutocomplete();
    }
};

window.gm_authFailure = function() {
    <?php if (isset($_SESSION['admin_id'])): ?>
    function showCheckoutMapsAdminNote() {
        var input = document.getElementById('billing_street_address_1');
        if (!input) return;
        if (document.getElementById('checkout_maps_admin_note')) return;
        var note = document.createElement('div');
        note.id = 'checkout_maps_admin_note';
        note.className = 'checkout-field-error';
        note.style.marginTop = '6px';
        note.textContent = 'Admin note: Google address autocomplete could not start. Check the Maps JavaScript API key in admin settings. In Google Cloud, allow https://www.sirfrancis.co.za/* and https://sirfrancis.co.za/* under HTTP referrers, and enable Maps JavaScript API plus Places API on that same key.';
        input.parentNode.appendChild(note);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showCheckoutMapsAdminNote);
    } else {
        showCheckoutMapsAdminNote();
    }
    <?php endif; ?>
};

</script>

<?php if ($google_places_api_key !== ''): ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?=htmlspecialchars($google_places_api_key, ENT_QUOTES)?>&libraries=places&callback=initCandybirdAddressAutocomplete"></script>
<?php elseif (isset($_SESSION['admin_id'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('billing_street_address_1');
    if (!input) return;
    var note = document.createElement('div');
    note.className = 'checkout-field-error';
    note.style.marginTop = '6px';
    note.textContent = 'Admin note: Google address autocomplete is off because no Google Places API key is saved in Website Settings.';
    input.parentNode.appendChild(note);
});
</script>
<?php endif; ?>

<?php
include 'footer.php';
?>
