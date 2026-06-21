<?php
// Start or resume the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
    $redirect_url = "manage_order" . ($order_id ? "?order_id=" . urlencode($order_id) : "");
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

include 'dbh.inc.php';
require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/admin_order_totals.php';
ensureCandybirdOrderItemSnapshotColumns($conn);
cbAdminEnsureOrderDiscountColumns($conn);
cbAdminEnsureOrderItemDiscountColumns($conn);

// Fetch order details
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

$no_order_found = false;

if (!$order_id) {
    echo "Order ID is missing.";
    header("Location: manage_orders?notfound");
    exit();
}

include 'header.php';

?>

<?php


$query = "
    SELECT 
        oi.id AS row_id, 
        oi.product_id AS id, 
        oi.product_title AS title,
        oi.product_image_url AS product_image_url,
        oi.product_weight AS product_weight,
        oi.price AS price, 
        oi.discount_amount AS discount_amount, 
        oi.admin_custom_discount_type,
        oi.admin_custom_discount_value,
        (oi.price - oi.discount_amount) AS discounted_price, 
        oi.quantity AS quantity,
        o.user_id,
        o.guest_identifier,
        o.order_date,
        o.coupon_id AS coupon_id,
		o.coupon_amount AS coupon_amount
    FROM 
        order_items oi
    JOIN 
        orders o ON oi.order_id = o.id
    WHERE oi.order_id = ?";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
// Return Order items as an array
$orderItems = $result->fetch_all(MYSQLI_ASSOC);

// Format cart items
$cart_table = "";
$coupon_amount = 0;
$subtotals = 0;
$discounts = 0;
$taxes = 0;
$tax = 0;
$order_total = 0;
$user_id = null;
$guest_identifier = null;

foreach ($orderItems as $item) {

	$user_id = $item['user_id'];
	$guest_identifier = $item['guest_identifier'];

    $displaySnapshot = getCandybirdOrderItemDisplaySnapshot($conn, $item, $item['order_date'] ?? null);
    $image_url = $displaySnapshot['image_url'];
    $displayTitle = $displaySnapshot['title'];
    $sheetProduct = getSheetProductById($item['id']);
    $productHref = '../' . ($sheetProduct ? getSheetProductUrl($sheetProduct) : ('product-' . rawurlencode((string) $item['id'])));

    $price = $displaySnapshot['price'];
    $quantity = $item['quantity'];
    $discount = $displaySnapshot['discount_amount'];
    $tax = !empty($item['tax_amount']) ? $item['tax_amount'] : 0;
    $coupon_amount = $item['coupon_amount'];
    $coupon_id = $item['coupon_id'];

    // Calculate discount amount based on discount rate
    // $discount = 0;
    // if ($discount_rate > 0) {
    //     $discount = ($price * $discount_rate) / 100;
    // }

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
    // if (isset($_SESSION['coupon']['code'])) {
    //   $coupon_amount = $_SESSION['coupon']['coupon_savings'];
    // }
    


    $cart_table .= '<tr class="product-row" data-product-id="' . $item['id'] . '" data-product-price="'.$price.'"data-product-discount="'.$discount.' "data-product-tax="'.$tax.'">';
    $cart_table .= '<th class="text-center" scope="row">';
    $cart_table .= '<img src="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') . '" />';
    $cart_table .= '</th>';
    $cart_table .= '<td>';
    $cart_table .= '<span class="whish-title"><a href="' . htmlspecialchars($productHref, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') . '</a></span>';
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
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<div class="admin-line-discount">';
    $cart_table .= '<select class="form-control form-control-sm item-discount-type mb-1">';
    $cart_table .= '<option value=""' . (empty($item['admin_custom_discount_type']) ? ' selected' : '') . '>No custom</option>';
    $cart_table .= '<option value="fixed"' . (($item['admin_custom_discount_type'] ?? '') === 'fixed' ? ' selected' : '') . '>Fixed R</option>';
    $cart_table .= '<option value="percentage"' . (($item['admin_custom_discount_type'] ?? '') === 'percentage' ? ' selected' : '') . '>% off</option>';
    $cart_table .= '</select>';
    $cart_table .= '<input type="number" min="0" step="0.01" class="form-control form-control-sm item-discount-value" value="' . htmlspecialchars((string) ((float) ($item['admin_custom_discount_value'] ?? 0) ?: ''), ENT_QUOTES, 'UTF-8') . '" placeholder="0">';
    $cart_table .= '<button type="button" class="btn btn-outline-primary btn-sm mt-1 save-item-discount">Save</button>';
    $cart_table .= '</div>';
    $cart_table .= '</td>';
    // $cart_table .= '<td class="text-center">';
    // $cart_table .= '<span class="dynamic-tax">R' . number_format($tax, 2) . ' ('. $item['tax_rate'] .'%)</span>';
    // $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<span class="dynamic-subtotal">R' . number_format($subtotal, 2) . '</span>';
    $cart_table .= '<div class="row-save-status small text-muted mt-1"></div>';
    $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<a href="#" class="removeFromOrder" data-product-id="' . $item['id'] . '" data-order-id="' . $order_id . '"><span class="trash" ><i class="fas fa-trash-alt"></i></span></a>';
    $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<button type="button" class="btn btn-outline-danger btn-sm cancel-order-item" data-product-id="' . $item['id'] . '" data-order-id="' . $order_id . '" data-title="' . htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') . '">Cancel qty</button>';
    $cart_table .= '</td>';
    $cart_table .= '</tr>';
}



// Fetch distinct countries from the shipping_zones table
$sqlCountries = "SELECT DISTINCT country FROM shipping_zones";
$resultCountries = mysqli_query($conn, $sqlCountries);

$countries = "";

// Check if there are results
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

$sheetProducts = getSheetProducts();
$productOptions = '';
foreach ($sheetProducts as $sheetProduct) {
    if (empty($sheetProduct['id'])) {
        continue;
    }
    $originalPrice = isset($sheetProduct['price']) ? candybirdParseSheetMoney($sheetProduct['price']) : getSheetProductPrice($sheetProduct);
    $finalPrice = getSheetProductPrice($sheetProduct);
    $priceLabel = $originalPrice > $finalPrice ? ' - R' . number_format($finalPrice, 2) . ' (was R' . number_format($originalPrice, 2) . ')' : ' - R' . number_format($finalPrice, 2);
    $label = trim(($sheetProduct['name'] ?? $sheetProduct['title'] ?? 'Product') . ' ' . ($sheetProduct['size'] ?? $sheetProduct['weight'] ?? '') . $priceLabel);
    $productOptions .= '<option value="' . htmlspecialchars($sheetProduct['id'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
}

$orderSummary = null;
$summaryStmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
if ($summaryStmt) {
    $summaryStmt->bind_param("i", $order_id);
    $summaryStmt->execute();
    $orderSummary = $summaryStmt->get_result()->fetch_assoc();
    $summaryStmt->close();
}
$adminOrderWeightKg = cbAdminOrderWeightKg($conn, (int) $order_id);
$adminDeliveryMethod = $orderSummary ? cbAdminInferDeliveryMethod($orderSummary) : 'locker';
if ($adminOrderWeightKg <= 0) {
    $adminDeliveryMethod = 'digital';
}
$adminDeliveryOptions = getCandybirdDeliveryOptions();
$adminShippingPayable = $orderSummary ? max(0, (float) $orderSummary['shipping_amount'] - (float) $orderSummary['shipping_discount_amount']) : 0;
$adminCustomDiscountType = trim((string) ($orderSummary['admin_custom_discount_type'] ?? ''));
$adminCustomDiscountValue = (float) ($orderSummary['admin_custom_discount_value'] ?? 0);
$adminCustomDiscountAmount = (float) ($orderSummary['admin_custom_discount_amount'] ?? 0);
?>


<?php 
include 'page_menues.php';
?>

<style>
  @media (min-width: 992px) {
    .admin-order-items-table {
      table-layout: fixed;
      width: 100%;
      font-size: 13px;
    }
    .admin-order-items-table th,
    .admin-order-items-table td {
      padding: 8px 5px;
      vertical-align: middle;
    }
    .admin-order-items-table thead th:nth-child(1),
    .admin-order-items-table tbody th {
      width: 66px;
    }
    .admin-order-items-table thead th:nth-child(2),
    .admin-order-items-table tbody td:nth-child(2) {
      width: auto;
      text-align: left !important;
    }
    .admin-order-items-table thead th:nth-child(3),
    .admin-order-items-table tbody td:nth-child(3) {
      width: 88px;
    }
    .admin-order-items-table thead th:nth-child(4),
    .admin-order-items-table tbody td:nth-child(4) {
      width: 92px;
    }
    .admin-order-items-table thead th:nth-child(5),
    .admin-order-items-table tbody td:nth-child(5) {
      width: 132px;
    }
    .admin-order-items-table thead th:nth-child(6),
    .admin-order-items-table tbody td:nth-child(6) {
      width: 92px;
    }
    .admin-order-items-table thead th:nth-child(7),
    .admin-order-items-table tbody td:nth-child(7) {
      width: 48px;
    }
    .admin-order-items-table thead th:nth-child(8),
    .admin-order-items-table tbody td:nth-child(8) {
      width: 82px;
    }
    .admin-order-items-table .admin-line-discount select,
    .admin-order-items-table .admin-line-discount input {
      font-size: 12px;
      height: 30px;
      padding: 3px 6px;
    }
    .admin-order-items-table img {
      max-width: 50px;
      width: 50px;
      height: 50px;
      object-fit: cover;
    }
    .admin-order-items-table .whish-title a {
      display: block;
      line-height: 1.25;
    }
    .admin-order-items-table .product-count.style,
    .admin-order-items-table .count {
      min-width: 0;
    }
    .admin-order-items-table input.quantity {
      width: 44px;
      min-width: 44px;
      height: 34px;
      padding: 4px;
      text-align: center;
    }
    .admin-order-items-table .button-group {
      width: 24px;
    }
    .admin-order-items-table .count-btn {
      width: 24px;
      height: 17px;
      line-height: 17px;
      padding: 0;
    }
    .admin-order-items-table .btn-sm {
      font-size: 11px;
      line-height: 1.2;
      padding: 5px 6px;
      white-space: normal;
    }
    .admin-order-items-table .trash {
      font-size: 15px;
    }
  }
</style>

<div class="my-account pt-20 pb-50">
  <div class="container">

  	<a href="manage_orders" class="no-print btn btn-secondary my-2">Back to Orders</a>
    <a href="../products" target="_blank" rel="noopener noreferrer" class="no-print btn btn-outline-primary my-2">Browse products</a>

    <?php if (!empty($_GET['created'])): ?>
      <div class="alert alert-success">Order #<?=htmlspecialchars($order_id, ENT_QUOTES, 'UTF-8')?> created. Add products below to build the order.</div>
    <?php endif; ?>

    <datalist id="adminProductList"><?=$productOptions?></datalist>
  	

<?php
  if (!empty($cart_table)) {
      // Display the table if $cart_table is not empty
      echo '
      <!-- product tab start -->
<section class="whish-list-section theme1 pt-80 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-12">
      <h3 class="title mb-30 pb-25 text-capitalize">Order #'.$order_id.'</h3>



        <div class="table-responsive">
          <table class="table admin-order-items-table">
              <thead class="thead-light">
                <tr>
                  <th class="text-center" scope="col">Product Image</th>
                  <th class="text-center" scope="col">Product Name</th>
                  <th class="text-center" scope="col">Qty</th>
                  <th class="text-center" scope="col">Price</th>
                  <th class="text-center" scope="col">Custom discount</th>
                  <!-- <th class="text-center" scope="col">Tax</th> -->
                  <th class="text-center" scope="col">Subtotal</th>
                  <th class="text-center" scope="col">Remove</th>
                  <th class="text-center" scope="col">Cancel stock</th>
                </tr>
              </thead>
              <tbody>
                ' . $cart_table . '
              </tbody>
            </table>
            </div>


            <!-- Form to add items to the order -->
            <div class="row mt-4">
              <div class="col-12">
                <form id="addToOrderForm" method="POST" action="add_to_order.php">
                  <div class="form-row align-items-center">
                    <div class="col-auto">
                      <label class="sr-only" for="productId">Product ID</label>
                      <input type="text" list="adminProductList" class="form-control mb-2" id="productId" name="product_id" placeholder="Product ID from sheet, e.g. 101" required>
                    </div>
                    <div class="col-auto">
                      <label class="sr-only" for="quantity">Quantity</label>
                      <input type="number" class="form-control mb-2" id="quantity" name="quantity" placeholder="Quantity" min="1" value="1">
                    </div>
                    <div class="col-auto">
                      <input type="hidden" name="orderId" value="'.$order_id.'">
                      <button type="submit" class="btn btn-primary mb-2">Add to Order</button>
                    </div>
                  </div>
                </form>
              </div>
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
                        <h2 class="title pb-3 mb-3">Order does not have any items!</h2>
                    </div>
                </div>
              </div>
            </div>
          </section>
          <!-- product tab end -->

          <section class="whish-list-section theme1 pt-30 pb-50">
            <div class="container">
              <div class="row">
                <div class="col-12">
                  <div class="bg-white border p-4">
                    <h3 class="title mb-20">Add products to this order</h3>
                    <p class="text-muted">Use the sheet product ID. Start typing an ID to see the matching sheet product.</p>
                    <form id="addToOrderForm" method="POST" action="add_to_order.php">
                      <div class="form-row align-items-center">
                        <div class="col-md-6">
                          <label for="productId">Product</label>
                          <input type="text" list="adminProductList" class="form-control mb-2" id="productId" name="product_id" placeholder="Product ID from sheet, e.g. 101" required>
                        </div>
                        <div class="col-md-3">
                          <label for="quantity">Quantity</label>
                          <input type="number" class="form-control mb-2" id="quantity" name="quantity" placeholder="Quantity" min="1" value="1">
                        </div>
                        <div class="col-md-3">
                          <input type="hidden" name="orderId" value="<?=$order_id?>">
                          <button type="submit" class="btn btn-primary mb-2 mt-md-4">Add to Order</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </section>

<?php
  }
?>


<!-- product tab end -->
<div class="check-out-section pb-80">
  <div class="container">
    <div class="row">
      <div class="col-lg-7">
        <div class="billing-info-wrap">
          <h3 class="title">Delivery and coupon</h3>
        
            <div class="row">
              <div class="col-12 mb-3">
                <label for="adminDeliveryMethod">Delivery method</label>
                <select id="adminDeliveryMethod" class="form-control" <?=$adminDeliveryMethod === 'digital' ? 'disabled' : ''?>>
                  <option value="locker" <?=$adminDeliveryMethod === 'locker' ? 'selected' : ''?>>Pudo locker</option>
                  <option value="door" <?=$adminDeliveryMethod === 'door' ? 'selected' : ''?>>Door-to-door</option>
                  <option value="collect" <?=$adminDeliveryMethod === 'collect' ? 'selected' : ''?>>Collection</option>
                  <?php if ($adminDeliveryMethod === 'digital'): ?>
                    <option value="digital" selected>Digital delivery</option>
                  <?php endif; ?>
                </select>
                <small class="form-text text-muted">Estimated weight: <?=number_format($adminOrderWeightKg, 2)?>kg. Locker free shipping follows the website threshold.</small>
              </div>
              <div class="col-12">
                <div class="alert alert-light border">
                  <strong>Current delivery:</strong>
                  Shipping R<?=number_format((float)($orderSummary['shipping_amount'] ?? 0), 2)?>,
                  discount R<?=number_format((float)($orderSummary['shipping_discount_amount'] ?? 0), 2)?>,
                  payable R<?=number_format($adminShippingPayable, 2)?>
                </div>
              </div>
              <div class="col-12">
                <h3 class="coupon-title">Discount Coupon Code</h3>
              </div>
              <div class="col-lg-6 col-md-6">
                <div class="billing-info mb-20px">
                  <input placeholder="Coupon code from sheet" type="text" id="coupon-code" />
                </div>
              </div>
              <div class="col-lg-6 col-md-6">
                <a href="#" class="btn btn-primary check-out-btn apply-coupon-btn" data-order-total="<?=$order_total?>" data-user-id="<?=$user_id?>" data-order-id="<?=$order_id?>" data-guest-id="<?=$guest_identifier?>">apply code</a>
                <a href="#" class="btn btn-outline-dark check-out-btn remove-order-coupon" data-order-id="<?=$order_id?>">remove coupon</a>
              </div>
              <div class="col-12"><div id="adminOrderTotalsMessage" class="small mt-2"></div></div>
              <div class="col-12 mt-4">
                <h3 class="coupon-title">Custom Order Discount</h3>
              </div>
              <div class="col-md-4">
                <select id="orderCustomDiscountType" class="form-control mb-2">
                  <option value="" <?=$adminCustomDiscountType === '' ? 'selected' : ''?>>No custom discount</option>
                  <option value="fixed" <?=$adminCustomDiscountType === 'fixed' ? 'selected' : ''?>>Fixed rand amount</option>
                  <option value="percentage" <?=$adminCustomDiscountType === 'percentage' ? 'selected' : ''?>>Percentage</option>
                </select>
              </div>
              <div class="col-md-4">
                <input id="orderCustomDiscountValue" type="number" min="0" step="0.01" class="form-control mb-2" value="<?=htmlspecialchars((string) ($adminCustomDiscountValue ?: ''), ENT_QUOTES, 'UTF-8')?>" placeholder="0">
              </div>
              <div class="col-md-4">
                <button type="button" id="saveOrderCustomDiscount" class="btn btn-outline-primary mb-2" data-order-id="<?=$order_id?>">Save discount</button>
              </div>
              <div class="col-12">
                <small class="form-text text-muted">Applies to the order before shipping. Current custom order discount: R<?=number_format($adminCustomDiscountAmount, 2)?></small>
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
                  <li>R<?=number_format($subtotals, 2)?></li>
                </ul>
              </div>


              <div class="your-order-top">
                <ul>
                  <li>Discounts</li>
                  <li>-R<?=number_format($discounts, 2)?></li>
                </ul>
              </div>

<?php if ($adminCustomDiscountAmount > 0) : ?>
              <div class="your-order-top">
                <ul>
                  <li>Custom order discount</li>
                  <li>-R<?=number_format($adminCustomDiscountAmount, 2)?></li>
                </ul>
              </div>
<?php endif; ?>

<?php if (!empty($coupon_id)) : ?>
    <div class="your-order-top">
        <ul>
            <li>Coupon</li>
            <li>- R<?= number_format($coupon_amount, 2) ?> <a href="#" class="remove-order-coupon" data-order-id="<?=$order_id?>" title="Remove coupon">[X]</a></li>
        </ul>
    </div>
<?php endif; ?>


<!--               <div class="your-order-top">
                <ul>
                  <li>Taxes</li>
                  <li>R<?=number_format($taxes, 2)?></li>
                </ul>
              </div> -->

              <div class="your-order-bottom">
                <ul>
                  <li class="your-order-shipping">Shipping</li>
                  <li>R<?=number_format($adminShippingPayable, 2)?></li>
                </ul>
              </div>
              <div class="your-order-total mb-0">
                <ul>
                  <li class="order-total">Total</li>
                  <li id="order-total">R<?=number_format((float)($orderSummary['grand_total_amount'] ?? $order_total), 2)?></li>
                </ul>
              </div>
            </div>
          </div>
          <!-- <div class="Place-order mt-25">
            <a class="btn btn--lg btn-primary my-2 my-sm-0" href="checkout"
              >UPDATE ORDER</a
            >
          </div> -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
$(document).ready(function () {
  var quantitySaveTimers = {};

  function showRowStatus(row, message, isError) {
      row.find('.row-save-status')
          .toggleClass('text-danger', !!isError)
          .toggleClass('text-success', !isError)
          .text(message || '');
  }

  function autosaveOrderQuantity(row) {
      var orderId = row.find('input.quantity').closest('.product-row').find('.cancel-order-item').data('order-id');
      var productId = row.data('product-id');
      var quantity = parseInt(row.find('input.quantity').val(), 10) || 1;
      row.find('input.quantity').val(quantity);
      showRowStatus(row, 'Saving...', false);

      $.ajax({
          url: 'update_order.php',
          method: 'POST',
          dataType: 'json',
          data: {
              orderId: orderId,
              product_id: productId,
              quantity: quantity
          },
          success: function(response) {
              showRowStatus(row, response.message || (response.success ? 'Saved.' : 'Could not save.'), !response.success);
              if (response.success) {
                  window.setTimeout(function() { window.location.reload(); }, 450);
              }
          },
          error: function() {
              showRowStatus(row, 'Order item could not be saved right now.', true);
          }
      });
  }

  function queueQuantitySave(row) {
      var productId = row.data('product-id');
      window.clearTimeout(quantitySaveTimers[productId]);
      showRowStatus(row, 'Unsaved change...', false);
      quantitySaveTimers[productId] = window.setTimeout(function() {
          autosaveOrderQuantity(row);
      }, 650);
  }

	$('#addToOrderForm').submit(function(event) {
        event.preventDefault(); // Prevent the default form submission
        var qtyInput = $(this).find('input[name="quantity"]');
        if (!qtyInput.val()) {
            qtyInput.val(1);
        }

        // Gather form data
        var formData = $(this).serialize();
        $('#adminOrderTotalsMessage').removeClass('text-danger').addClass('text-success').text('Adding product...');

        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: 'add_to_order.php',
            data: formData,
            dataType: 'json',
            success: function(jsonResponse) {
                if (jsonResponse.success) {
                    $('#adminOrderTotalsMessage').removeClass('text-danger').addClass('text-success').text(jsonResponse.message || 'Item added to order successfully.');
                    window.location.reload();
                } else {
                    $('#adminOrderTotalsMessage').removeClass('text-success').addClass('text-danger').text(jsonResponse.message || 'Product could not be added.');
                }
            },
            error: function(xhr, status, error) {
                $('#adminOrderTotalsMessage').removeClass('text-success').addClass('text-danger').text('Product could not be added right now.');
            }
        });
    });

  $('body').on('click', '.save-item-discount', function(e) {
      e.preventDefault();
      var row = $(this).closest('.product-row');
      showRowStatus(row, 'Saving discount...', false);
      $.ajax({
          url: 'update_order_discount.php',
          method: 'POST',
          dataType: 'json',
          data: {
              mode: 'item',
              orderId: <?=json_encode((int) $order_id)?>,
              product_id: row.data('product-id'),
              discountType: row.find('.item-discount-type').val(),
              discountValue: row.find('.item-discount-value').val() || 0,
              deliveryMethod: $('#adminDeliveryMethod').val() || 'locker'
          },
          success: function(response) {
              showRowStatus(row, response.message || (response.success ? 'Discount saved.' : 'Discount could not be saved.'), !response.success);
              if (response.success) window.location.reload();
          },
          error: function() {
              showRowStatus(row, 'Discount could not be saved right now.', true);
          }
      });
  });

  $('#saveOrderCustomDiscount').on('click', function(e) {
      e.preventDefault();
      $('#adminOrderTotalsMessage').removeClass('text-danger').addClass('text-success').text('Saving order discount...');
      $.ajax({
          url: 'update_order_discount.php',
          method: 'POST',
          dataType: 'json',
          data: {
              mode: 'order',
              orderId: $(this).data('order-id'),
              discountType: $('#orderCustomDiscountType').val(),
              discountValue: $('#orderCustomDiscountValue').val() || 0,
              deliveryMethod: $('#adminDeliveryMethod').val() || 'locker'
          },
          success: function(response) {
              $('#adminOrderTotalsMessage').toggleClass('text-success', !!response.success).toggleClass('text-danger', !response.success).text(response.message || 'Order discount checked.');
              if (response.success) window.location.reload();
          },
          error: function() {
              $('#adminOrderTotalsMessage').removeClass('text-success').addClass('text-danger').text('Order discount could not be saved right now.');
          }
      });
  });

  $('.apply-coupon-btn').on('click', function (e) {
      e.preventDefault();
      $.ajax({
          url: 'apply_coupon_order.php',
          method: 'POST',
          dataType: 'json',
          data: {
              orderId: $(this).data('order-id'),
              couponCode: $('#coupon-code').val(),
              deliveryMethod: $('#adminDeliveryMethod').val() || 'locker'
          },
          success: function (response) {
              $('#adminOrderTotalsMessage').toggleClass('text-success', !!response.success).toggleClass('text-danger', !response.success).text(response.message || 'Coupon checked.');
              if (response.success) window.location.reload();
          },
          error: function () {
              $('#adminOrderTotalsMessage').addClass('text-danger').text('Coupon could not be applied right now.');
          }
      });
  });

  $('.remove-order-coupon').on('click', function(e) {
      e.preventDefault();
      $.ajax({
          url: 'apply_coupon_order.php',
          method: 'POST',
          dataType: 'json',
          data: {
              orderId: $(this).data('order-id'),
              couponCode: '',
              deliveryMethod: $('#adminDeliveryMethod').val() || 'locker'
          },
          success: function(response) {
              $('#adminOrderTotalsMessage').toggleClass('text-success', !!response.success).toggleClass('text-danger', !response.success).text(response.message || 'Coupon removed.');
              if (response.success) window.location.reload();
          },
          error: function() {
              $('#adminOrderTotalsMessage').addClass('text-danger').text('Coupon could not be removed right now.');
          }
      });
  });

  $('#adminDeliveryMethod').on('change', function() {
      $.ajax({
          url: 'update_order_delivery.php',
          method: 'POST',
          dataType: 'json',
          data: {
              orderId: <?=json_encode((int) $order_id)?>,
              deliveryMethod: $(this).val()
          },
          success: function(response) {
              $('#adminOrderTotalsMessage').toggleClass('text-success', !!response.success).toggleClass('text-danger', !response.success).text(response.message || 'Delivery updated.');
              if (response.success) window.location.reload();
          },
          error: function() {
              $('#adminOrderTotalsMessage').addClass('text-danger').text('Delivery could not be updated right now.');
          }
      });
  });

  $('body').on('click', '.count-btn', function () {
      var $productRow = $(this).closest('.product-row');
      var subtotalElement = $productRow.find('.dynamic-subtotal');
      subtotalElement.html('<span style="color: grey">Saving...</span>');
      window.setTimeout(function() {
          queueQuantitySave($productRow);
      }, 120);
   });

  $('body').on('change keyup', 'input.quantity', function () {
      var $productRow = $(this).closest('.product-row');
      var subtotalElement = $productRow.find('.dynamic-subtotal');
      subtotalElement.html('<span style="color: grey">Saving...</span>');
      queueQuantitySave($productRow);
  });

  $('body').on('click', '.removeFromOrder', function(e) {
      e.preventDefault();
      if (!confirm('Remove this product from the order?')) return;
      $.ajax({
          url: 'remove_from_order.php',
          method: 'POST',
          dataType: 'json',
          data: {
              orderId: $(this).data('order-id'),
              product_id: $(this).data('product-id')
          },
          success: function(response) {
              $('#adminOrderTotalsMessage').toggleClass('text-success', !!response.success).toggleClass('text-danger', !response.success).text(response.message || (response.success ? 'Product removed.' : 'Product could not be removed.'));
              if (response.success) {
                  window.location.reload();
              }
          },
          error: function() {
              $('#adminOrderTotalsMessage').addClass('text-danger').text('Product could not be removed right now.');
          }
      });
  });

  $('body').on('click', '.cancel-order-item', function(e) {
      e.preventDefault();
      var row = $(this).closest('.product-row');
      var qty = parseInt(row.find('input.quantity').val(), 10) || 1;
      var reason = prompt('Reason for cancelling ' + qty + ' of this item?', 'Out of stock');
      if (reason === null) return;

      $.ajax({
          url: 'cancel_order_action.php',
          method: 'POST',
          dataType: 'json',
          data: {
              mode: 'item',
              orderId: $(this).data('order-id'),
              product_id: $(this).data('product-id'),
              quantity: qty,
              reason: reason
          },
          success: function(response) {
              $('#adminOrderTotalsMessage').toggleClass('text-success', !!response.success).toggleClass('text-danger', !response.success).text(response.message || (response.success ? 'Cancellation recorded.' : 'Cancellation failed.'));
              if (response.success) {
                  window.location.reload();
              }
          },
          error: function() {
              $('#adminOrderTotalsMessage').addClass('text-danger').text('Cancellation could not be recorded right now.');
          }
      });
  });


});

</script>

<?php
include '../footer.php';
?>
