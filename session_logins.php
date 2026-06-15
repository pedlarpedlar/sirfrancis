<?php
date_default_timezone_set('Africa/Johannesburg');

if (session_status() === PHP_SESSION_NONE) {
    ob_start();           // Start output buffering
    if (!headers_sent($sessionHeaderFile, $sessionHeaderLine)) {
        session_start();      // Safely start the session
    }
}

if (isset($_SESSION['cart_total'])) {
    // echo "cart total: " . $_SESSION['cart_total'];
}

// Include your database connection file
include_once __DIR__ . "/dbh.inc.php";
include_once __DIR__ . "/product_sheet_helpers.php";

$username = "Guest";
$user_email = "";
$userId = null;
$guestIdentifier = null;
$current_session_id = null;
$dbAvailable = isset($conn) && $conn instanceof mysqli && !$conn->connect_error;
$load_shopping_nav = $load_shopping_nav ?? true;

include __DIR__ . '/log_action_function.php';

if (!function_exists('setSessionDefaultsWithoutDatabase')) {
    function setSessionDefaultsWithoutDatabase() {
        global $username, $user_email, $userId, $guestIdentifier, $current_session_id;
        global $wishlistCount, $cartCount, $compareCount, $cartItems, $wishlistItems, $compareItems;
        global $free_shipping_amount, $tel, $website_email, $website_email2, $website_address;
        global $headquarters, $hotline, $banking_details, $productIds, $support_email, $website_company_name;
        global $return_window, $limitedDescription, $description_og, $description_meta, $title_og, $image_url_og, $page_url_og, $page_url_canonical;

        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $username = isset($_SESSION['username']) ? $_SESSION['username'] : "Guest";
            $user_email = isset($_SESSION['email']) ? $_SESSION['email'] : "";
        }

        if (!isset($_SESSION['guest_identifier'])) {
            $_SESSION['guest_identifier'] = bin2hex(random_bytes(16));
        }

        if (!isset($_SESSION['session_id'])) {
            $_SESSION['session_id'] = session_id();
        }

        $guestIdentifier = $_SESSION['guest_identifier'];
        $current_session_id = isset($_SESSION['current_session_id']) ? $_SESSION['current_session_id'] : null;

        $wishlistCount = 0;
        $cartCount = 0;
        $compareCount = 0;
        $cartItems = [];
        $wishlistItems = [];
        $compareItems = [];

        $free_shipping_amount = getCandybirdFreeShippingAmount();
        $tel = "";
        $website_email = "info@fishgelatine.co.za";
        $website_email2 = "";
        $website_address = "";
        $headquarters = "";
        $hotline = "";
        $banking_details = "";
        $productIds = [];
        $support_email = $website_email;
        $website_company_name = "Sir Francis";
        $return_window = "14 days";
        $limitedDescription = "Sir Francis offers quality nuts, dried fruit, snacks, candy, gifting, wholesale and delivery services.";
        $description_og = $limitedDescription;
        $description_meta = $limitedDescription;
        $title_og = "Sir Francis";
        $image_url_og = "https://www.fishgelatine.co.za/v2/assets/img/product/1.png";
        $page_url_og = "https://www.fishgelatine.co.za/v2";
        $page_url_canonical = "https://www.fishgelatine.co.za/v2";

        $_SESSION['cart_total'] = isset($_SESSION['cart_total']) ? $_SESSION['cart_total'] : 0;
        $_SESSION['cart_discounts'] = isset($_SESSION['cart_discounts']) ? $_SESSION['cart_discounts'] : 0;
        $_SESSION['cart_before_discounts'] = isset($_SESSION['cart_before_discounts']) ? $_SESSION['cart_before_discounts'] : 0;
    }
}

if (!$dbAvailable) {
    setSessionDefaultsWithoutDatabase();

    if (!function_exists('getCompareItems')) {
        function getCompareItems($userId, $guestIdentifier) {
            return [];
        }
    }

    if (!function_exists('getWishlistItems')) {
        function getWishlistItems($userId, $guestIdentifier) {
            return [];
        }
    }

    if (!function_exists('getCartItems')) {
        function getCartItems($userId, $guestIdentifier) {
            return [];
        }
    }

    if (isset($_REQUEST['getBadgeCounts'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'wishlistCount' => $wishlistCount,
            'cartCount' => $cartCount,
            'compareCount' => $compareCount
        ]);
        exit();
    }

    return;
}

//this function filters out pages so that requests and unwanted pages don't get logged. user isn't going to these pages.
function isMainContentPage($url) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return false;
    }

    // List of asset file extensions to exclude
    $assetExtensions = ['.css', '.js', '.map', '.png', '.jpg', '.jpeg', '.webp', '.gif', '.svg', '.ico'];
    
    // Check if the URL ends with any of the asset file extensions
    foreach ($assetExtensions as $extension) {
        if (strpos($url, $extension) !== false) {
            return false;
        }
    }

    // Check for specific query parameters or paths to exclude
    $excludePaths = [
        'session_logins.php',
        'track_page_view.php',
        'log_action.php',
        'update_end_time.php',
        'fetch_sheet',
        'fetch_homepage_products',
        'add_to_cart',
        'update_cart',
        'remove_from_cart',
        'add_to_wishlist',
        'add_to_compare',
        'apply_coupon',
        'remove_coupon',
        'get_product_reviews',
        'check_checkout_email'
    ];
    foreach ($excludePaths as $path) {
        if (strpos($url, $path) !== false) {
            return false;
        }
    }

    // Exclude URLs with query parameters typically used for AJAX requests
    if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
        parse_str($_SERVER['QUERY_STRING'], $queryParams);
        // Add more conditions to exclude specific query parameters if needed
        $ajaxParams = ['getBadgeCounts'];
        foreach ($ajaxParams as $param) {
            if (array_key_exists($param, $queryParams)) {
                return false;
            }
        }
    }

    return true;
}

// Construct the URL for logging
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$currentUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];


// Function to fetch compare items for a user or guest
function getCompareItems($userId, $guestIdentifier) {
    global $conn; // Assuming $conn is your database connection
    global $load_shopping_nav;
    if (empty($load_shopping_nav)) {
        return [];
    }
    if (!($conn instanceof mysqli)) {
        return [];
    }

    $query = "SELECT product_id FROM compare WHERE user_id = ? OR guest_identifier = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("ss", $userId, $guestIdentifier);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $storedProductId = trim((string) ($row['product_id'] ?? ''));
        $clearanceId = '';
        if (stripos($storedProductId, 'CLR:') === 0) {
            $clearanceId = substr($storedProductId, 4);
        }

        $item = buildSheetCartItem([
            'product_id' => $storedProductId,
            'clearance_id' => $clearanceId,
            'quantity' => 1,
            'coupon_code' => ''
        ]);

        if ($item) {
            $items[] = $item;
        }
    }

    return $items;
}

// Function to fetch wishlist items for a user or guest
function getWishlistItems($userId, $guestIdentifier) {
    global $conn; // Assuming $conn is your database connection
    global $load_shopping_nav;
    if (empty($load_shopping_nav)) {
        return [];
    }
    if (!($conn instanceof mysqli)) {
        return [];
    }

    $query = "SELECT product_id FROM wishlist WHERE user_id = ? OR guest_identifier = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("ss", $userId, $guestIdentifier);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $item = buildSheetCartItem([
            'product_id' => $row['product_id'],
            'quantity' => 1,
            'coupon_code' => ''
        ]);

        if ($item) {
            $items[] = $item;
        }
    }

    return $items;
}


// Function to fetch wishlist items for a user or guest

function getCartItems($userId, $guestIdentifier) {
    global $conn; // Assuming $conn is your database connection
    global $load_shopping_nav;
    if (empty($load_shopping_nav)) {
        return [];
    }
    if (!($conn instanceof mysqli)) {
        return [];
    }

    ensureCandybirdCartClearanceColumns($conn);
    $hasClearanceColumn = false;
    $hasCouponColumn = false;
    $clearanceColumnCheck = $conn->query("SHOW COLUMNS FROM cart LIKE 'clearance_id'");
    if ($clearanceColumnCheck && $clearanceColumnCheck->num_rows > 0) {
        $hasClearanceColumn = true;
    }
    $couponColumnCheck = $conn->query("SHOW COLUMNS FROM cart LIKE 'coupon_code'");
    if ($couponColumnCheck && $couponColumnCheck->num_rows > 0) {
        $hasCouponColumn = true;
    }

    $selectColumns = ['product_id'];
    $selectColumns[] = $hasClearanceColumn ? 'clearance_id' : "'' AS clearance_id";
    $selectColumns[] = 'quantity';
    $selectColumns[] = $hasCouponColumn ? 'coupon_code' : "'' AS coupon_code";
    $query = "SELECT " . implode(', ', $selectColumns) . " FROM cart WHERE user_id = ? OR guest_identifier = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("ss", $userId, $guestIdentifier);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $item = buildSheetCartItem($row);

        if (!$item || isCandybirdCartItemUnavailable($item)) {
            deleteCandybirdCartRow(
                $conn,
                $userId,
                $guestIdentifier,
                $row['product_id'] ?? '',
                $row['clearance_id'] ?? ''
            );
            continue;
        }

        $items[] = $item;
    }

    return $items;
}

// Function to update cart total
function updateCartTotal($conn, $userId, $guestIdentifier) {
    $subtotal = 0;
    $taxes = 0;
    $discounts = 0;

    foreach (getCartItems($userId, $guestIdentifier) as $item) {
        $quantity = (int) $item['quantity'];
        $subtotal += ((float) $item['discounted_price']) * $quantity;
        $taxes += ((float) $item['tax_amount']) * $quantity;
        $discounts += ((float) $item['discount_amount']) * $quantity;
    }

    $cartTotal = $subtotal + $taxes;

    // Update the session variable
    $_SESSION['cart_total'] = $cartTotal;
    $_SESSION['cart_discounts'] = $discounts;
    $_SESSION['cart_before_discounts'] = $cartTotal - $discounts;
    
}

function calculateCouponDiscount($conn, $user_id, $guest_identifier, $couponEmail = '') {
    $coupon_details = isset($_SESSION['coupon']) ? $_SESSION['coupon'] : null;

    if (empty($coupon_details['code'])) {
        return [
            'discount' => 0,
            'original_amount' => 0,
            'discounted_amount' => 0,
            'shipping_savings' => 0
        ];
    }

    $couponContext = ['conn' => $conn];
    if ($couponEmail !== '') {
        $couponContext['email'] = $couponEmail;
    } elseif (!empty($_SESSION['email'])) {
        $couponContext['email'] = $_SESSION['email'];
    }

    $selection = selectBestSheetCouponForCart($coupon_details['code'], getCartItems($user_id, $guest_identifier), $couponContext);

    if (!$selection['valid']) {
        $_SESSION['coupon']['coupon_savings'] = 0;
        $_SESSION['coupon']['coupon_message'] = $selection['message'];
        return [
            'discount' => 0,
            'original_amount' => 0,
            'discounted_amount' => 0,
            'shipping_savings' => 0
        ];
    }

    $coupon = $selection['coupon'];
    $discountDetails = $selection['discount'];

    $_SESSION['coupon'] = array_merge($_SESSION['coupon'], [
        'id' => $coupon['id'] ?? $coupon['coupon_code'],
        'code' => $coupon['coupon_code'],
        'discount_type' => $coupon['discount_type'],
        'discount_value' => (float) $coupon['discount_value'],
        'valid_from' => $coupon['valid_from'] ?? '',
        'valid_until' => $coupon['valid_until'] ?? '',
        'valid_on_sale_items' => $coupon['valid_on_sale_items'] ?? 'no',
        'coupon_savings' => $discountDetails['coupon_savings'],
        'original_amount' => $discountDetails['eligible_amount'],
        'total_after_coupon' => $discountDetails['total_after_coupon'],
        'shipping_coupon' => false,
        'shipping_coupon_value' => 0,
        'shipping_coupon_type' => '',
        'coupon_message' => $discountDetails['message'],
    ]);

    return array(
        'discount' => $discountDetails['coupon_savings'],
        'original_amount' => $discountDetails['eligible_amount'],
        'discounted_amount' => $discountDetails['total_after_coupon'],
        'shipping_savings' => 0
    );
}


function validateRememberMeToken($token) {
    global $conn; // Assuming $conn is your database connection

    if (!($conn instanceof mysqli) || trim((string) $token) === '') {
        return false;
    }

    // Validate the token and retrieve user information from the database
    $sql = "SELECT id, username, email FROM users WHERE remember_token = ? AND remember_token_expiration > ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    
    // Set the current timestamp
    $currentTimestamp = time();

    mysqli_stmt_bind_param($stmt, "si", $token, $currentTimestamp);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        // Token is valid, fetch user information
        mysqli_stmt_bind_result($stmt, $userId, $username, $user_email);
        mysqli_stmt_fetch($stmt);

        return [
            'id' => $userId,
            'username' => $username,
            'email' => $user_email
        ];
    } else {
        // Token is not valid or has expired
        return false;
    }
}

function attachGuestRecordsToRememberedUser($userId, $guestIdentifier) {
    global $conn;

    if (!($conn instanceof mysqli) || !$userId || !$guestIdentifier) {
        return;
    }

    foreach (['reviews', 'cart', 'wishlist', 'compare', 'orders', 'blog_comments', 'applied_coupons'] as $table) {
        $sqlUpdate = "UPDATE $table SET user_id = ? WHERE guest_identifier = ?";
        $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, "is", $userId, $guestIdentifier);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }
    }
}

function refreshRememberMeCookie($userId) {
    global $conn;

    if (!($conn instanceof mysqli) || !$userId) {
        return;
    }

    $token = bin2hex(random_bytes(32));
    $expiration = time() + (30 * 24 * 60 * 60);
    $stmt = mysqli_prepare($conn, "UPDATE users SET remember_token = ?, remember_token_expiration = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sii", $token, $expiration, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('remember_token', $token, [
            'expires' => $expiration,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
// Check if the user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    // Check if the remember_me cookie is set
    if (isset($_COOKIE['remember_token'])) {
        // Validate and fetch user information based on the remember-me cookie
        $token = $_COOKIE['remember_token'];
        // Add additional validation and verification steps as needed
        $user = validateRememberMeToken($token);

        if ($user) {
            // The user is remembered, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $userId = $user['id'];
            attachGuestRecordsToRememberedUser($userId, $_SESSION['guest_identifier'] ?? null);
            refreshRememberMeCookie($userId);
        }
    }

    // Check if the guest_identifier is set in the session
    if (!isset($_SESSION['guest_identifier'])) {
        // Generate a new session ID for a guest user
        $guestIdentifier = bin2hex(random_bytes(16)); // 16 bytes = 128 bits
        $_SESSION['guest_identifier'] = $guestIdentifier;
    } else {
        // Use the existing guest identifier
        $guestIdentifier = $_SESSION['guest_identifier'];
    }
} else {
    // Fetch user id from the session
    if (session_status() == PHP_SESSION_NONE) {
        // Start or resume the session
        session_start();
    }
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $user_email = $_SESSION['email'];
}

// Function to get the count of items in a specific list (wishlist, cart, or compare)
function getListItemCount($listTable, $userId, $guestIdentifier)
{
    global $conn;
    $allowedTables = ['wishlist', 'cart', 'compare'];

    if (!($conn instanceof mysqli) || !in_array($listTable, $allowedTables, true)) {
        return 0;
    }

    // Prepare the SQL statement
    $sql = "SELECT COUNT(*) FROM $listTable WHERE user_id = ? OR guest_identifier = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    // Bind parameters and execute the statement
    mysqli_stmt_bind_param($stmt, "ss", $userId, $guestIdentifier);
    mysqli_stmt_execute($stmt);

    // Bind the result variable
    mysqli_stmt_bind_result($stmt, $itemCount);

    // Fetch the result
    mysqli_stmt_fetch($stmt);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Return the count of items
    return $itemCount;
}

// Check if the "getBadgeCounts" parameter is set in the POST or GET request
if (isset($_REQUEST['getBadgeCounts'])) {
    $wishlistCount = getListItemCount("wishlist", $userId, $guestIdentifier);
    $cartCount = getListItemCount("cart", $userId, $guestIdentifier);
    $compareCount = getListItemCount("compare", $userId, $guestIdentifier);

    // Create an associative array with the counts
    $responseArray = [
        'wishlistCount' => $wishlistCount,
        'cartCount' => $cartCount,
        'compareCount' => $compareCount
    ];

    // Encode the array into JSON
    $jsonResponse = json_encode($responseArray);

    // Output the JSON response
    header('Content-Type: application/json');
    echo $jsonResponse;

    // Stop further execution to avoid unwanted output
    exit();
}

// Get the count of items in the wishlist, cart, and compare lists only for pages
// that need shopping state in the first response.
if (!empty($load_shopping_nav)) {
    $wishlistCount = getListItemCount("wishlist", $userId, $guestIdentifier);
    $cartCount = getListItemCount("cart", $userId, $guestIdentifier);
    $compareCount = getListItemCount("compare", $userId, $guestIdentifier);
} else {
    $wishlistCount = 0;
    $cartCount = 0;
    $compareCount = 0;
}

// Fetch website configurations from the database
$getWebsiteSettings = "SELECT * FROM admin_website_settings";
$resultWebsiteSettings = $conn->query($getWebsiteSettings);

if ($resultWebsiteSettings) {
    // Fetch the row as an associative array
    $settings = $resultWebsiteSettings->fetch_assoc();

    // Assign values to variables
    $free_shipping_amount = getCandybirdFreeShippingAmount($settings['free_shipping_amount'] ?? null);
    $tel = $settings['tel'];
    $website_email = $settings['email_1'];
    $website_email2 = $settings['email_2'];
    $website_address = $settings['address'];
    $headquarters = $settings['headquarters'];
    $hotline = $settings['hotline'];
    $banking_details = nl2br($settings['banking_details']);
    
    
    $productIdsString = $settings['products_on_homepage'];
    if (!empty($productIdsString)) {
        $productIds = array_map('trim', explode(',', $productIdsString));
    } else {
        $productIds = []; // Set to an empty array if no IDs are found
    }


    $support_email = $settings['email_1'];
    $website_company_name = "Sir Francis"; // For Privacy Policy, Terms, etc
} else {
    // Handle the case where the query fails
    echo "Error fetching website configurations: " . $conn->error;
    exit();
}


// $return_window = "48 hours";
$return_window = "14 days";

$limitedDescription = $limitedDescription ?? "Sir Francis offers quality nuts, dried fruit, snacks, candy, gifting, wholesale and delivery services.";
$description_og = $description_og ?? $limitedDescription;
$description_meta = $description_meta ?? $limitedDescription;
$title_og = $title_og ?? "Sir Francis";
$image_url_og = $image_url_og ?? "https://www.fishgelatine.co.za/v2/assets/img/product/1.png";
$page_url_og = $page_url_og ?? "https://www.fishgelatine.co.za/v2";
$page_url_canonical = $page_url_canonical ?? "https://www.fishgelatine.co.za/v2";



/* FOR ANALYTICAL TRACKING start*/

if (!isset($_SESSION['session_id'])) {
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

    $bot_agents = ['Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot', 'Sogou', 'facebookexternalhit', 'WhatsApp', 'TelegramBot', 'curl', 'wget'];
    foreach ($bot_agents as $bot_agent) {
        if (stripos($user_agent, $bot_agent) !== false) {
            $_SESSION['tracking_bot'] = true;
            break;
        }
    }

    $_SESSION['session_id'] = session_id();
}

if (!isset($_SESSION['guest_identifier']) || $_SESSION['guest_identifier'] === '') {
    $_SESSION['guest_identifier'] = $_SESSION['session_id'];
}
$guestIdentifier = $_SESSION['guest_identifier'];
$current_session_id = isset($_SESSION['current_session_id']) ? $_SESSION['current_session_id'] : null;
/* FOR ANALYTICAL TRACKING end */

// Main page views are recorded in page_views. Keep action_logs for meaningful clicks,
// cart activity, search misses, errors, and other UX events so browsing stays fast.

// echo $current_session_id; exit();

// Keep coupon totals fresh only where totals are shown or changed. Running this on every
// normal page view slows first-byte time because it reads cart items and coupon sheets.
$couponCalculationPath = strtolower(trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/'));
$couponCalculationPages = [
    'cart',
    'checkout',
    'checkout_pay',
    'checkout-pay',
    'order_details',
    'order-details',
    'thankyou',
    'apply_coupon',
    'apply-coupon',
    'remove_coupon',
    'remove-coupon',
    'update_cart',
    'update-cart',
    'add_to_cart',
    'add-to-cart',
    'remove_from_cart',
    'remove-from-cart',
];
if (isset($_SESSION['coupon']) || in_array($couponCalculationPath, $couponCalculationPages, true)) {
    calculateCouponDiscount($conn, $userId, $guestIdentifier);
}
