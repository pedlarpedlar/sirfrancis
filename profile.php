<?php
include 'session_logins.php';

$order_id = $user_id = $fetched_billing_first_name = $fetched_billing_last_name = $fetched_billing_email_address = $cartTotal = null;
date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2
include 'payNowForm.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $redirect_url = "profile";
    header("Location: login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}



    $select_orders_sql = "SELECT o.*, o.id AS order_id, ua.*
                      FROM orders AS o
                      LEFT JOIN user_addresses AS ua ON o.user_id = ua.user_id
                      WHERE o.user_id = ?
                      ORDER BY o.order_date DESC";
    $select_orders_stmt = mysqli_prepare($conn, $select_orders_sql);
    mysqli_stmt_bind_param($select_orders_stmt, "i", $userId);
    mysqli_stmt_execute($select_orders_stmt);
    $select_orders_result = mysqli_stmt_get_result($select_orders_stmt);

    $orders_table = "";
    // Initialize a variable to keep track of the total number of orders
    $totalOrders = mysqli_num_rows($select_orders_result);

    // Check if there are any orders
    if ($totalOrders > 0) {
        // Initialize the order number to the total number of orders
        $orderNumber = $totalOrders;

        while ($row = mysqli_fetch_assoc($select_orders_result)) {

          if ($row['payment_status'] == 0) {
              $statusText = "Unpaid";
              $payNowButton = '<span class="mx-3"><a href="order_details?order_id=' . $row['order_id'] . '" class="btn btn-success">Pay Now</a></span>';
              // $payNowButton = '<span class="mx-3"><input type="submit" form="payNowForm' . $row['order_id'] . '" class="btn btn-success" value="Pay Now" /></span>';
              
              $order_status = '';
          } elseif ($row['payment_status'] == 1) {
              $statusText = "Paid";
              $payNowButton = ''; // No button needed for Paid status
              $order_status = ' | ' . $row['order_status'];
          } elseif ($row['payment_status'] == 2) {
              $statusText = "Paid (EFT Confirmed)";
              $payNowButton = ''; // No button needed for Paid status
              $order_status = '';
          } else {
              $statusText = ""; // Optional: Handle unexpected values
              $payNowButton = ''; // No button needed for unknown status
              $order_status = ' | ' . $row['order_status'];
          }


          // Generate data for the payNowForm
          $data = array(
              'merchant_id' => '14090292', //'10000100', // Replace with your merchant ID
              'merchant_key' => '5ksggz4e5rru2', //'46f0cd694581a', // Replace with your merchant key
              'return_url' => 'https://www.candybird.co.za/order_details?order_id=' . $row['order_id'] . '&thankyou',
              'cancel_url' => 'https://www.candybird.co.za/checkout',
              'notify_url' => 'https://www.candybird.co.za/notify?order_id=' . $row['order_id'] . '&user_id=' . $userId,
              'name_first' => $row['billing_first_name'],
              'name_last'  => $row['billing_last_name'],
              'email_address' => $row['billing_email_address'],
              'm_payment_id' => $row['order_id'],
              'amount' => number_format( sprintf( '%.2f', $row['grand_total_amount'] ), 2, '.', '' ),
              'item_name' => str_pad($row['order_id'], 7, '0', STR_PAD_LEFT)
          );

          // Generate the signature for this form
          $signature = generateSignature($data, $passphrase); // Replace with your actual passphrase
          $data['signature'] = $signature;

          // Form HTML for this order
          $payNowForm = '<form action="https://'.$pfHost.'/eng/process" method="post" id="payNowForm' . $row['order_id'] . '">';
          foreach ($data as $name => $value) {
              $payNowForm .= '<input name="' . $name . '" type="hidden" value="' . htmlspecialchars($value) . '" />';
          }
          $payNowForm .= '</form>';




            $orders_table .= '<tr>';
            $orders_table .= '<td>' . $orderNumber . '</td>';
            $orders_table .= '<td>Order ' . $row['order_id'] . '</td>';
            $orders_table .= '<td>' . date('M d, Y', strtotime($row['order_date'])) . '</td>';
            $orders_table .= '<td>' . $statusText . ' ' . $payNowForm . $payNowButton . ' ' . $order_status . ' </td>';
            $orders_table .= '<td>R' . number_format($row['grand_total_amount'], 2) . '</td>';
            $orders_table .= '<td><a href="order_details?order_id=' . $row['order_id'] . '" class="ht-btn black-btn">View</a></td>';
            $orders_table .= '</tr>';

            // Increment order number
            $orderNumber--;
        }

    } else {
        $orders_table .= '<tr><td colspan="6">No orders found.</td></tr>';
    }

    // Close the statement
    mysqli_stmt_close($select_orders_stmt);

    $username = '';
    $email = '';
    $billing_first_name = '';
    $billing_last_name = '';
    $fullName = '';
    $address1 = '';
    $address2 = '';
    $city = '';
    $country = '';
    $province = '';
    $postCode = '';
    $phoneNumber = '';
    $address_section = '';
    $shipping_address_section = '';
    $emailAddress = '';
    $s_emailAddress = '';
    // Fetch user details from the database
    $sql_fetch_user = "SELECT u.username, u.email, ua.*, ua.id AS billing_id FROM users u
                       LEFT JOIN user_addresses ua ON u.id = ua.user_id
                       WHERE u.id = ?";
    $stmt_fetch_user = mysqli_prepare($conn, $sql_fetch_user);
    mysqli_stmt_bind_param($stmt_fetch_user, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt_fetch_user);

    // Get the result set from the prepared statement
    $result = mysqli_stmt_get_result($stmt_fetch_user);

    // Fetch the data into an associative array
    $user_data = mysqli_fetch_assoc($result);

    // Close the statement
    mysqli_stmt_close($stmt_fetch_user);

    if ($user_data) {
        // Assign variables from the associative array
        $billing_id = $user_data['billing_id'];
        $username = $user_data['username'];
        $email = $user_data['email'];

        // Check if the user has a billing address
        $hasBillingAddress = !empty($billing_id) ? true : false;
        $hasShippingAddress = !empty($user_data['shipping_first_name']) ? true : false;
        

        // Display address or a message based on whether the user has a billing address
        if ($hasBillingAddress) {
                $billing_first_name = $user_data['billing_first_name'];
                $billing_last_name = $user_data['billing_last_name'];
                $fullName = htmlspecialchars($billing_first_name . ' ' . $billing_last_name);
                $address1 = htmlspecialchars($user_data['billing_street_address_1']);
                $address2 = htmlspecialchars($user_data['billing_street_address_2']);
                $city = htmlspecialchars($user_data['billing_city']);
                $country = htmlspecialchars($user_data['billing_country']);
                $province = htmlspecialchars($user_data['billing_province']);
                $postCode = htmlspecialchars($user_data['billing_post_code']);
                $phoneNumber = htmlspecialchars($user_data['billing_phone_number']);
                $emailAddress = htmlspecialchars($user_data['billing_email_address']);
            
                $address_section = '<address id="billing_address">
                    <p><strong id="billing-full-name">'.$fullName.'</strong></p>
                    <p id="billing-address">
                        <span class="b-address-line-1">'.$address1.'</span>
                        <span class="b-address-line-2">'.$address2.'</span>
                        <br />
                        <span class="b-city">'.$city.'</span>
                        <span class="b-province">'.$province.'</span>
                        <span class="b-post-code">'.$postCode.'</span>
                        <br />
                        <span class="b-country">'.$country.'</span>
                    </p>
                    <p id="billing-phone">Phone: <span class="b-phone-number">'.$phoneNumber.'</span></p>
                    <p id="billing-email">Email: <span class="b-email-address">'.$emailAddress.'</span></p>
                </address>
                <a href="#" class="ht-btn black-btn d-inline-block edit-address-btn"><i class="fa fa-edit"></i>Edit Address</a>
                ';


                
                
        } else {
            // Display a message if the user doesn't have a billing address
            $address_section = '<p>You do not have any billing address saved.</p>
            <a href="#" class="ht-btn black-btn d-inline-block edit-address-btn"><i class="fa fa-edit"></i>Add Address</a>';
        }

        // Display address or a message based on whether the user has a billing address
        if ($hasShippingAddress) {

                $shipping_first_name = $user_data['shipping_first_name'];
                $shipping_last_name = $user_data['shipping_last_name'];
                $s_fullName = htmlspecialchars($shipping_first_name . ' ' . $shipping_last_name);
                $s_address1 = htmlspecialchars($user_data['shipping_street_address_1']);
                $s_address2 = htmlspecialchars($user_data['shipping_street_address_2']);
                $s_city = htmlspecialchars($user_data['shipping_city']);
                $s_country = htmlspecialchars($user_data['shipping_country']);
                $s_province = htmlspecialchars($user_data['shipping_province']);
                $s_postCode = htmlspecialchars($user_data['shipping_post_code']);
                $s_phoneNumber = htmlspecialchars($user_data['shipping_phone_number']);
                $s_emailAddress = htmlspecialchars($user_data['shipping_email_address']);
            

                $shipping_address_section = '<address id="shipping_address">
                    <p><strong id="shipping-full-name">'.$s_fullName.'</strong></p>
                    <p id="shipping-address">
                        <span class="s-address-line-1">'.$s_address1.'</span>
                        <span class="s-address-line-2">'.$s_address2.'</span>
                        <br />
                        <span class="s-city">'.$s_city.'</span>
                        <span class="s-province">'.$s_province.'</span>
                        <span class="s-post-code">'.$s_postCode.'</span>
                        <br />
                        <span class="s-country">'.$s_country.'</span>
                    </p>
                    <p id="shipping-phone">Phone: <span class="s-phone-number">'.$s_phoneNumber.'</span></p>
                    <p id="shipping-email">Email: <span class="s-email-address">'.$s_emailAddress.'</span></p>
                </address>
                <a href="#" class="ht-btn black-btn d-inline-block edit-s-address-btn"><i class="fa fa-edit"></i>Edit Address</a>
                ';
                                
        } else {
            $shipping_address_section = '<p>You do not have any shipping address saved.</p>
            <a href="#" class="ht-btn black-btn d-inline-block edit-s-address-btn"><i class="fa fa-edit"></i>Add Address</a>';
        }





    }


// Function to fetch shipping zones from the database
function getShippingZones() {
    global $conn;

    $sql = "SELECT DISTINCT country FROM shipping_zones";
    $result = mysqli_query($conn, $sql);

    $shippingZones = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $shippingZones[] = $row['country'];
    }

    return $shippingZones;
}

// Fetch shipping zones
$shippingZones = getShippingZones();

// Function to fetch provinces grouped by country from shipping_zones table
function getProvincesByCountry() {
    global $conn;

    $sql = "SELECT country, province FROM shipping_zones";
    $result = mysqli_query($conn, $sql);

    $provincesByCountry = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $country = $row['country'];
        $province = $row['province'];

        // Group provinces by country
        $provincesByCountry[$country][] = $province;
    }

    return $provincesByCountry;
}

// Fetch provinces grouped by country
$provincesByCountry = getProvincesByCountry();

include 'header.php';
?>

<?php
$page_url_canonical = "https://www.candybird.co.za/profile";
$title_og = 'My Account - CandyBird';
$page_url_og = "https://www.candybird.co.za/profile"
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

<title>My Profile - CandyBird</title>

<?php
include 'page_menues.php';
?>

<div class="my-account pt-80 pb-50">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <h3 class="title text-capitalize mb-30 pb-25">my account</h3>
      </div>
      <!-- My Account Tab Menu Start -->
      <div class="col-lg-3 col-12 mb-30">
        <div class="myaccount-tab-menu nav" role="tablist">
          <a class="nav-link" href="#dashboad" id="tab_dashboad" data-toggle="tab"
            ><i class="fas fa-tachometer-alt"></i> Dashboard</a
          >

          <a class="nav-link" href="#orders" id="tab_orders" data-toggle="tab"
            ><i class="fa fa-cart-arrow-down"></i> Orders</a
          >

          <!-- <a class="nav-link" href="#download" id="tab_download" data-toggle="tab"
            ><i class="fas fa-cloud-download-alt"></i> Download</a
          > -->

          <a class="nav-link" href="#payment-method" id="tab_payment_method" data-toggle="tab"
            ><i class="fa fa-credit-card"></i> Payment Method</a
          >

          <a class="nav-link" href="#address-edit" id="tab_address_edit" data-toggle="tab"
            ><i class="fa fa-map-marker"></i> address</a
          >

          <a class="nav-link" href="#account-info" id="tab_account_info" data-toggle="tab" class="active"
            ><i class="fa fa-user"></i> Account Details</a
          >

          <a href="logout"><i class="fa fa-sign-out"></i> Logout</a>
        </div>
      </div>
      <!-- My Account Tab Menu End -->

      <!-- My Account Tab Content Start -->
      <div class="col-lg-9 col-12 mb-30">
        <div class="tab-content" id="myaccountContent">
          <!-- Single Tab Content Start -->
          <div class="tab-pane fade" id="dashboad" role="tabpanel">
            <div class="myaccount-content">
              <h3>Dashboard</h3>

              <div class="welcome mb-20">
                <?php
                // Assuming $username, $billing_first_name, and $billing_last_name are available

                echo '<p>Hello, ' . ((!empty($billing_first_name) ? $billing_first_name : '') . (!empty($billing_last_name) ? ' ' . $billing_last_name : '') ?: $username) . '! <strong>Not you?</strong> <a href="logout" class="logout">Logout</a></p>';
                ?>
              </div>

              <p class="mb-0">
                From your account dashboard. you can easily check &amp; view
                your recent orders, manage your shipping and billing addresses
                and edit your password and account details.
              </p>
            </div>
          </div>
          <!-- Single Tab Content End -->

          <!-- Single Tab Content Start -->
          <div class="tab-pane fade" id="orders" role="tabpanel">
            <div class="myaccount-content">
              <h3>Orders</h3>

              <div class="myaccount-table table-responsive text-center">
                <table class="table table-bordered">
                  <thead class="thead-light">
                    <tr>
                      <th></th>
                      <th>Order</th>
                      <th>Date</th>
                      <th>Status</th>
                      <th>Total</th>
                      <th>Action</th>
                    </tr>
                  </thead>

                  <tbody>

                    <?=$orders_table?>

                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Single Tab Content End -->

          <!-- Single Tab Content Start -->
          <div class="tab-pane fade" id="download" role="tabpanel">
            <div class="myaccount-content">
              <h3>Downloads</h3>

              <div class="myaccount-table table-responsive text-center">
                <table class="table table-bordered">
                  <thead class="thead-light">
                    <tr>
                      <th>Product</th>
                      <th>Date</th>
                      <th>Expire</th>
                      <th>Download</th>
                    </tr>
                  </thead>

                  <tbody>
                    <tr>
                      <td>Mostarizing Oil</td>
                      <td>Aug 22, 2018</td>
                      <td>Yes</td>
                      <td>
                        <a href="#" class="ht-btn black-btn">Download File</a>
                      </td>
                    </tr>
                    <tr>
                      <td>Katopeno Altuni</td>
                      <td>Sep 12, 2018</td>
                      <td>Never</td>
                      <td>
                        <a href="#" class="ht-btn black-btn">Download File</a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Single Tab Content End -->

          <!-- Single Tab Content Start -->
          <div class="tab-pane fade" id="payment-method" role="tabpanel">
            <div class="myaccount-content">
              <h3>Payment Method</h3>

              <p class="saved-message">
                You haven't got any saved payment methods yet.
              </p>
            </div>
          </div>
          <!-- Single Tab Content End -->

          <!-- Single Tab Content Start -->
<div class="tab-pane fade" id="address-edit" role="tabpanel">
    <div class="myaccount-content">
        <h3>Billing Address</h3>

        <?=$address_section?>

        <!-- Editable fields -->
        <div class="editable-fields" style="display: none;">
            <form id="billing-address-form">
                <div class="form-group">
                    <label for="billing-first-name">First Name</label>
                    <input type="text" id="billing-first-name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="billing-last-name">Last Name</label>
                    <input type="text" id="billing-last-name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="billing-street-address-1">Address Line 1</label>
                    <input type="text" id="billing-street-address-1" class="form-control">
                </div>
                <div class="form-group">
                    <label for="billing-street-address-2">Address Line 2</label>
                    <input type="text" id="billing-street-address-2" class="form-control">
                </div>
                <div class="form-group">
                    <label for="billing-city">City</label>
                    <input type="text" id="billing-city" class="form-control">
                </div>
                <div class="form-group">
                    <label for="billing-country">Country</label>
                    <select id="billing-country" class="form-control">
                        <option value="">Select Country</option>
                        <?php if (is_array($shippingZones)) : ?>
                            <?php foreach ($shippingZones as $zone) : ?>
                                <option value="<?php echo $zone; ?>"><?php echo $zone; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="billing-province">Province</label>
                    <select id="billing-province" class="form-control" <?php echo ($country == '') ? 'disabled' : ''; ?>>
                        <option value="">Select Country First</option>
                        <?php
                        if (!empty($country)) {
                            $provinces = $provincesByCountry[$country] ?? [];
                            foreach ($provinces as $selected_province) {
                                echo '<option value="' . $selected_province . '" ' . (($selected_province == $province) ? 'selected' : '') . '>' . $selected_province . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="billing-post-code">Post Code</label>
                    <input type="text" id="billing-post-code" class="form-control">
                </div>
                <div class="form-group">
                    <label for="billing-email-address">Email Address</label>
                    <input type="email" id="billing-email-address" class="form-control">
                </div>
                <div class="form-group">
                    <label for="billing-phone-number">Phone Number</label>
                    <input type="text" id="billing-phone-number" class="form-control">
                </div>
                <button id="save-address-btn" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
    <div class="myaccount-content">
        <h3>Shipping Address</h3>

        <?=$shipping_address_section?>

        <!-- Editable fields -->
        <div class="shipping-editable-fields" style="display: none;">
            <form id="shipping-address-form">
                <div class="form-group">
                    <label for="shipping-first-name">First Name</label>
                    <input type="text" id="shipping-first-name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="shipping-last-name">Last Name</label>
                    <input type="text" id="shipping-last-name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="shipping-street-address-1">Address Line 1</label>
                    <input type="text" id="shipping-street-address-1" class="form-control">
                </div>
                <div class="form-group">
                    <label for="shipping-street-address-2">Address Line 2</label>
                    <input type="text" id="shipping-street-address-2" class="form-control">
                </div>
                <div class="form-group">
                    <label for="shipping-city">City</label>
                    <input type="text" id="shipping-city" class="form-control">
                </div>
                <div class="form-group">
                    <label for="shipping-country">Country</label>
                    <select id="shipping-country" class="form-control">
                        <option value="">Select Country</option>
                        <?php if (is_array($shippingZones)) : ?>
                            <?php foreach ($shippingZones as $zone) : ?>
                                <option value="<?php echo $zone; ?>"><?php echo $zone; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="shipping-province">Province</label>
                    <select id="shipping-province" class="form-control" <?php echo (isset($s_country) && $s_country == '') ? 'disabled' : ''; ?>>
                        <option value="">Select Country First</option>
                        <?php
                        if (!empty($s_country)) {
                            $provinces = $provincesByCountry[$s_country] ?? [];
                            foreach ($provinces as $selected_province) {
                                echo '<option value="' . $selected_province . '" ' . (($selected_province == $s_province) ? 'selected' : '') . '>' . $selected_province . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="shipping-post-code">Post Code</label>
                    <input type="text" id="shipping-post-code" class="form-control">
                </div>
                <div class="form-group">
                    <label for="shipping-phone-number">Phone Number</label>
                    <input type="text" id="shipping-phone-number" class="form-control">
                </div>
                <div class="form-group">
                    <label for="shipping-email-address">Email Address</label>
                    <input type="text" id="shipping-email-address" class="form-control">
                </div>
                <button id="save-shipping-address-btn" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>


          <!-- Single Tab Content End -->

          <!-- Single Tab Content Start -->
          <div
            class="tab-pane fade active show"
            id="account-info"
            role="tabpanel"
          >
            <div class="myaccount-content">
              <h3>Account Details</h3>

              <div class="account-details-form">
                <form id="update-profile-form" method="post" action="update-profile-form.inc.php">
                    <div class="row">
                        <div class="col-lg-6 col-12 mb-30">
                            <input id="first-name" placeholder="First Name" type="text" value="<?php echo $billing_first_name; ?>">
                        </div>

                        <div class="col-lg-6 col-12 mb-30">
                            <input id="last-name" placeholder="Last Name" type="text" value="<?php echo $billing_last_name; ?>">
                        </div>

                        <div class="col-12 mb-30">
                            <input id="display-name" placeholder="Display Name" type="text" value="<?php echo $username; ?>">
                        </div>

                        <div class="col-12 mb-30">
                            <input id="email" placeholder="Email Address" type="email" value="<?php echo $email; ?>" disabled>
                        </div>

                        <div class="col-12 mb-30">
                            <h4>Password change</h4>
                        </div>

                        <div class="col-12 mb-30">
                            <input id="current-pwd" placeholder="Current Password" type="password" required>
                        </div>

                        <div class="col-lg-6 col-12 mb-30">
                            <input id="new-pwd" placeholder="New Password" type="password" required>
                        </div>

                        <div class="col-lg-6 col-12 mb-30">
                            <input id="confirm-pwd" placeholder="Confirm Password" type="password" required>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-dark btn--md">Save Changes</button>
                        </div>
                    </div>
                </form>
              </div>
            </div>
          </div>
          <!-- Single Tab Content End -->
        </div>
      </div>
      <!-- My Account Tab Content End -->
    </div>
  </div>
</div>


<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {

// Get the hash from the URL
var hash = window.location.hash;

// If there is a hash, and it matches a tab pane ID
if (hash) {
    // Activate the corresponding tab
    $('a.nav-link[href="' + hash + '"]').tab('show');
}



// Initial data for provinces
var provincesByCountry = <?php echo json_encode($provincesByCountry); ?>;

function handleCountryChange(countrySelector, provinceSelector) {
    var selectedCountry = $(countrySelector).val();
    var provinces = provincesByCountry[selectedCountry] || [];

    // Clear previous options
    $(provinceSelector).empty();

    // Populate provinces
    if (provinces.length > 0) {
        $(provinceSelector).prop('disabled', false);
        $.each(provinces, function (index, province) {
            $(provinceSelector).append('<option value="' + province + '">' + province + '</option>');
        });
    } else {
        // No provinces for the selected country
        $(provinceSelector).prop('disabled', true);
        $(provinceSelector).append('<option value="">No Provinces Available</option>');
    }
}

// Country select change event for billing
$('#billing-country').on('change', function () {
    handleCountryChange('#billing-country', '#billing-province');
});

// Country select change event for shipping
$('#shipping-country').on('change', function () {
    handleCountryChange('#shipping-country', '#shipping-province');
});




// Store initial values
var initialFullName = $('#billing-full-name').text();
var initialAddressLine1 = $('.b-address-line-1').text();
var initialAddressLine2 = $('.b-address-line-2').text();
var initialCity = $('.b-city').text();
var initialCountry = $('.b-country').text();
var initialProvince = $('.b-province').text();
var initialPostCode = $('.b-post-code').text();
var initialPhoneNumber = $('.b-phone-number').text();
var initialEmailAddress = $('.b-email-address').text();

$('.edit-address-btn').click(function (e) {
    e.preventDefault();

    // Toggle between view and edit modes
    $('#billing_address').toggle();
    $('.editable-fields').toggle();

    // Populate editable fields
    $('#billing-first-name').val(initialFullName.split(' ')[0]);
    $('#billing-last-name').val(initialFullName.split(' ')[1]);

    // Populate address components
    $('#billing-street-address-1').val(initialAddressLine1);
    $('#billing-street-address-2').val(initialAddressLine2);
    // Additional address components
    $('#billing-city').val(initialCity);
    $('#billing-country').val(initialCountry);
    $('#billing-province').val(initialProvince);
    $('#billing-post-code').val(initialPostCode);

    $('#billing-phone-number').val(initialPhoneNumber);
    $('#billing-email-address').val(initialEmailAddress);
});

// Store initial values
var s_initialFullName = $('#shipping-full-name').text();
var s_initialAddressLine1 = $('.s-address-line-1').text();
var s_initialAddressLine2 = $('.s-address-line-2').text();
var s_initialCity = $('.s-city').text();
var s_initialCountry = $('.s-country').text();
var s_initialProvince = $('.s-province').text();
var s_initialPostCode = $('.s-post-code').text();
var s_initialPhoneNumber = $('.s-phone-number').text();
var s_initialEmailAddress = $('.s-email-address').text();

$('.edit-s-address-btn').click(function (e) {
    e.preventDefault();

    // Toggle between view and edit modes
    $('#shipping_address').toggle();
    $('.shipping-editable-fields').toggle();

    // Populate editable fields
    $('#shipping-first-name').val(s_initialFullName.split(' ')[0]);
    $('#shipping-last-name').val(s_initialFullName.split(' ')[1]);

    // Populate address components
    $('#shipping-street-address-1').val(s_initialAddressLine1);
    $('#shipping-street-address-2').val(s_initialAddressLine2);
    // Additional address components
    $('#shipping-city').val(s_initialCity);
    $('#shipping-country').val(s_initialCountry);
    $('#shipping-province').val(s_initialProvince);
    $('#shipping-post-code').val(s_initialPostCode);

    $('#shipping-phone-number').val(s_initialPhoneNumber);
    $('#shipping-email-address').val(s_initialEmailAddress);
});





















$('#save-shipping-address-btn').click(function (e) {
    e.preventDefault();

    // Prepare data for AJAX
    var formData = {
        s_first_name: $('#shipping-first-name').val(),
        s_last_name: $('#shipping-last-name').val(),
        s_street_address_1: $('#shipping-street-address-1').val(),
        s_street_address_2: $('#shipping-street-address-2').val(),
        s_city: $('#shipping-city').val(),
        s_province: $('#shipping-province').val(),
        s_country: $('#shipping-country').val(),
        s_post_code: $('#shipping-post-code').val(),
        s_phone_number: $('#shipping-phone-number').val(),
        s_email_address: $('#shipping-email-address').val(),
    };

    // Perform the save operation using AJAX
    $.ajax({
        type: 'POST',
        url: 'save-s-address.inc.php',
        data: formData,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                // Update view fields with edited values
                $('#shipping-full-name').text(formData.s_first_name + ' ' + formData.s_last_name);
                $('.s-address-line-1').text(formData.s_street_address_1);
                $('.s-address-line-2').text(formData.s_street_address_2);
                // Additional address components
                $('.s-city').text(formData.s_city);
                $('.s-province').text(formData.s_province);
                $('.s-country').text(formData.s_country);
                $('.s-post-code').text(formData.s_post_code);
                $('.s-phone-number').text('Phone: ' + formData.s_phone_number);
                $('.s-email-address').text(formData.s_email_address);

                // Toggle back to view mode after saving
                $('#shipping_address').toggle();
                $('.shipping-editable-fields').toggle();

                // Check if the user inserted a new address
                if (response.isNewAddress) {
                    // Reload the page with AJAX
                    location.reload(true);
                }
            }
            // Handle success, e.g., show a success message
            showNotification(response.success, response.message);
        },
        error: function (x, y, z) {
            console.log(x);
            console.log(y);
            console.log(z);
            // Handle AJAX error
            alert('An error occurred during the AJAX request.');
        }
    });
});
















$('#save-address-btn').click(function (e) {
    e.preventDefault();

    // Prepare data for AJAX
    var formData = {
        first_name: $('#billing-first-name').val(),
        last_name: $('#billing-last-name').val(),
        street_address_1: $('#billing-street-address-1').val(),
        street_address_2: $('#billing-street-address-2').val(),
        city: $('#billing-city').val(),
        province: $('#billing-province').val(),
        country: $('#billing-country').val(),
        post_code: $('#billing-post-code').val(),
        phone_number: $('#billing-phone-number').val(),
        email_address: $('#billing-email-address').val(),
    };

    // Perform the save operation using AJAX
    $.ajax({
        type: 'POST',
        url: 'save-address.inc.php',
        data: formData,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                // Update view fields with edited values
                $('#billing-full-name').text(formData.first_name + ' ' + formData.last_name);
                $('.address-line-1').text(formData.street_address_1);
                $('.address-line-2').text(formData.street_address_2);
                // Additional address components
                $('.city').text(formData.city);
                $('.province').text(formData.province);
                $('.country').text(formData.country);
                $('.post-code').text(formData.post_code);
                $('.phone-number').text('Phone: ' + formData.phone_number);
                $('.email-address').text(formData.email_address);

                // Toggle back to view mode after saving
                $('#billing_address').toggle();
                $('.editable-fields').toggle();

                // Check if the user inserted a new address
                if (response.isNewAddress) {
                    // Reload the page with AJAX
                    location.reload(true);
                }
            }
            // Handle success, e.g., show a success message
            showNotification(response.success, response.message);
        },
        error: function (x, y, z) {
            console.log(x);
            console.log(y);
            console.log(z);
            // Handle AJAX error
            alert('An error occurred during the AJAX request.');
        }
    });
});



    // Function to update user profile using AJAX
    $('body').on('submit', '#update-profile-form', function(e){
        e.preventDefault();
        var formData = {
            first_name: $('#first-name').val(),
            last_name: $('#last-name').val(),
            display_name: $('#display-name').val(),
            current_pwd: $('#current-pwd').val(),
            new_pwd: $('#new-pwd').val(),
            confirm_pwd: $('#confirm-pwd').val()
        };

        $.ajax({
            type: 'POST',
            url: 'update-profile-form.inc.php', // Adjust the filename as needed
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // Reset password fields
                    $('#current-pwd').val('');
                    $('#new-pwd').val('');
                    $('#confirm-pwd').val('');
                }
                showNotification(response.success, response.message);
            },
            error: function (x, y, z) {
                console.log(x);
                console.log(y);
                console.log(z);
                // Handle AJAX error
                alert('An error occurred during the AJAX request.');
            }
        });
    });
});

</script>

<?php
include "footer.php";
?>