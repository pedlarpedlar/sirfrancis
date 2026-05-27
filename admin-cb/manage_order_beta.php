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
        p.id AS id, 
        oi.product_title AS title,
        p.weight AS weight,
        oi.price AS price, 
        oi.discount_amount AS discount_amount, 
        (oi.price - oi.discount_amount) AS discounted_price, 
        i.image_url AS image_url, 
        oi.quantity AS quantity,
        o.user_id,
        o.guest_identifier,
        o.coupon_id AS coupon_id,
		o.coupon_amount AS coupon_amount
    FROM 
        order_items oi
    JOIN 
        orders o ON oi.order_id = o.id
    JOIN 
        product p ON oi.product_id = p.id
    LEFT JOIN (
        SELECT product_id, MIN(image_url) AS image_url  -- Select only one image per product
        FROM images
        GROUP BY product_id
    ) i ON p.id = i.product_id
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

foreach ($orderItems as $item) {
    $image_url = isset($item['image_url']) ? $item['image_url'] : '../assets/img/product/1.png';

    $price = $item['price'];
    $quantity = $item['quantity'];
    $discount = $item['discount_amount'];
    $tax = !empty($item['tax_amount']) ? $item['tax_amount'] : 0;
    $coupon_amount = $item['coupon_amount'];

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
    $cart_table .= '<img src="' . $image_url . '" alt="img" />';
    $cart_table .= '</th>';
    $cart_table .= '<td>';
    $cart_table .= '<span class="whish-title"><a href="../product?id=' . $item['id'] . '">' . $item['title'] . '</a></span>';
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
    $cart_table .= '<a href="#" class="removeFromOrder" data-product-id="' . $item['id'] . '" data-order-id="' . $order_id . '"><span class="trash" ><i class="fas fa-trash-alt"></i></span></a>';
    $cart_table .= '</td>';
    $cart_table .= '<td class="text-center">';
    $cart_table .= '<a href="#" class="btn btn-dark btn--lg update-order-quantity" data-product-id="' . $item['id'] . '" data-order-id="' . $order_id . '">Update Order</a>';
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
?>


<?php 
include 'page_menues.php';
?>

<div class="my-account pt-20 pb-50">
  <div class="container">

  	<a href="manage_orders" class="no-print btn btn-secondary my-2">Back to Orders</a>
  	

<?php
  if (!empty($cart_table)) {
      // Display the table if $cart_table is not empty
      echo '
      <!-- product tab start -->
<section class="whish-list-section theme1 pt-80 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-12">
      <h3 class="title mb-30 pb-25 text-capitalize">Order #'.$order_id.' items</h3>
        <div class="table-responsive">
          <table class="table">
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


            <!-- Form to add items to the order -->
            <div class="row mt-4">
              <div class="col-12">
                <form id="addToOrderForm" method="POST" action="add_to_order.php">
                  <div class="form-row align-items-center">
                    <div class="col-auto">
                      <label class="sr-only" for="productId">Product ID</label>
                      <input type="number" class="form-control mb-2" id="productId" name="product_id" placeholder="Product ID" required>
                    </div>
                    <div class="col-auto">
                      <label class="sr-only" for="quantity">Quantity</label>
                      <input type="number" class="form-control mb-2" id="quantity" name="quantity" placeholder="Quantity" min="1" required>
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

<?php
  }
?>


<!-- product tab end -->
<div class="check-out-section pb-80">
  <div class="container">
    <div class="row">
      <div class="col-lg-7">
        <div class="billing-info-wrap">
          <h3 class="title">calculate shipping</h3>
        
            <div class="row">
            <!-- <div class="col-lg-6 col-md-6">
                <div class="billing-select mb-20px">
                    <select name="country" id="inputCountry" class="form-select mb-3">
                        <option value="">Select country</option>
                        <?=$countries?>
                    </select>
                </div>
            </div>
            <div class="col-lg-6 col-md-6">
                <div class="billing-select mb-20px">
                    <select name="state" id="inputState" class="form-select mb-3">
                        <option value="">Select Province/State</option>
                    </select>
                </div>
            </div> -->
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
                  <li>R<?=number_format($subtotals, 2)?></li>
                </ul>
              </div>


              <div class="your-order-top">
                <ul>
                  <li>Discounts</li>
                  <li>-R<?=number_format($discounts, 2)?></li>
                </ul>
              </div>

<?php if (isset($_SESSION['coupon']['code'])) : ?>
    <div class="your-order-top">
        <ul>
            <li>COUPON <?=$_SESSION['coupon']['code']?></li>
            <li>- R<?= number_format($_SESSION['coupon']['coupon_savings'], 2) ?> <a href="#" class="remove-coupon" data-couponid="<?=$_SESSION['coupon']['id']?>" title="Remove Coupon">[X]</a></li>
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
                <!-- <ul>
                  <li class="your-order-shipping">Shipping Estimate</li>
                  <li>Select Province</li>
                </ul> -->
              </div>
              <div class="your-order-total mb-0">
                <ul>
                  <li class="order-total">Total</li>
                  <li id="order-total">R<?=number_format($order_total, 2)?></li>
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

	$('#addToOrderForm').submit(function(event) {
        event.preventDefault(); // Prevent the default form submission

        // Gather form data
        var formData = $(this).serialize();

        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: 'add_to_order.php',
            data: formData,
            success: function(response) {
                // Handle the response from the server
                var jsonResponse = JSON.parse(response);
                if (jsonResponse.success) {
                    alert('Item added to order successfully.');
                    // Optionally, refresh or update parts of the page
                } else {
                    alert('Error: ' + jsonResponse.message);
                }
            },
            error: function(xhr, status, error) {
                // Handle errors
                alert('An error occurred: ' + error);
            }
        });
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

              	//window.location.href = "?";
              	console.log(response);

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

              	//window.location.href = "?";
              	console.log(response);

              }
          },
          error: function (xhr, status, error) {
              // Handle errors, display an error message, etc.
              console.error(error);
          }
      });
  });

  $('body').on('click', '.count-btn', function () {
      var $productRow = $(this).closest('.product-row');
      var subtotalElement = $productRow.find('.dynamic-subtotal');
      // Update the subtotal display
      subtotalElement.html('<span style="text-decoration: italic; color: grey">Update Order</span>');
   });

  $('body').on('change keyup', 'input.quantity', function () {
      var $productRow = $(this).closest('.product-row');
      var subtotalElement = $productRow.find('.dynamic-subtotal');
      // Update the subtotal display
      subtotalElement.html('<span style="text-decoration: italic; color: grey">Update Order</span>');
   });


});

</script>

<?php
include '../footer.php';
?>