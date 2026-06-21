<?php
require_once __DIR__ . '/product_sheet_helpers.php';
?>
</head>

<body>
<?php
if (function_exists('renderCandybirdSiteFlags')) {
  $cbNoticePlacement = function_exists('getCandybirdCurrentSiteFlagPlacement') ? getCandybirdCurrentSiteFlagPlacement() : 'all';
  echo renderCandybirdSiteFlags($cbNoticePlacement, true);
}
?>
    
<!-- offcanvas-overlay start -->
<div class="offcanvas-overlay no-print"></div>
<!-- offcanvas-overlay end -->
<!-- offcanvas-mobile-menu start -->
<div id="offcanvas-mobile-menu" class="offcanvas theme1 offcanvas-mobile-menu no-print">
  <div class="inner">
    <div class="border-bottom mb-4 pb-4 text-right">
      <button class="offcanvas-close" type="button" aria-label="Close menu">×</button>
    </div>
    <div class="offcanvas-head mb-4">
      <nav class="offcanvas-top-nav">
        <ul class="d-flex flex-wrap">
          <li class="my-2 mx-2">
            <a href="cart">
              <i class="icon-bag"></i> Cart <?= $cartCount > 0 ? '<span>(' . $cartCount . ')</span>' : '(0)' ?></a
            >
          </li>
          <li class="my-2 mx-2">
            <a href="wishlist">
              <i class="ion-android-favorite-outline"></i> Wishlist
              <?= $wishlistCount > 0 ? '<span>(' . $wishlistCount . ')</span>' : '(0)' ?></a
            >
          </li>
          <li class="my-2 mx-2">
            <a href="compare"
              ><i class="ion-ios-loop-strong"></i> Compare <?= $compareCount > 0 ? '<span>(' . $compareCount . ')</span>' : '(0)' ?></span>
            </a>
          </li>
          <li class="my-2 mx-2">
            <a class="search search-toggle" href="javascript:void(0)" aria-label="Open search">
              <i class="icon-magnifier"></i> Search</a
            >
          </li>
        </ul>
      </nav>
    </div>
    <nav class="offcanvas-menu">
      <ul>
        <li><a href="./">Home</a></li>
        <li><a href="products">Retail Shop</a></li>


<?php

function sheetMenuCleanCategory($value) {
    return trim((string) $value);
}

function buildSheetMenuCategories() {
    $cacheDir = __DIR__ . '/sheet_cache';
    $cacheFile = $cacheDir . '/menu_categories_v3.json';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 1800) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $tree = [];
    $products = function_exists('getSheetProductsWithClearance') ? getSheetProductsWithClearance() : (function_exists('getSheetProducts') ? getSheetProducts() : []);
    $specialCount = 0;

    foreach ($products as $product) {
        $id = trim($product['id'] ?? '');
        $enabled = strtolower(trim($product['enabled'] ?? '1'));
        if ($id === '' || in_array($enabled, ['0', 'false', 'no', 'disabled'], true)) {
            continue;
        }
        if (function_exists('isCandybirdProductOnSpecial') && isCandybirdProductOnSpecial($product)) {
            $specialCount++;
        }

        $paths = function_exists('getCandybirdProductCategoryPaths')
            ? getCandybirdProductCategoryPaths($product)
            : [[
                $product['parent_category'] ?? '',
                $product['child_category_1'] ?? '',
                $product['child_category_2'] ?? '',
            ]];

        foreach ($paths as $path) {
            $parts = array_values(array_filter(array_map('sheetMenuCleanCategory', (array) $path)));
            if (empty($parts)) {
                continue;
            }
            $parent = $parts[0];
            if (function_exists('isCandybirdCategoryVisible') && !isCandybirdCategoryVisible($parent)) {
                continue;
            }

            if (!isset($tree[$parent])) {
                $tree[$parent] = ['category_param' => $parent, 'display_name' => function_exists('getCandybirdCategoryDisplayLabel') ? getCandybirdCategoryDisplayLabel($parent) : $parent, 'count' => 0, 'children' => []];
            }
            $tree[$parent]['count']++;

            $node =& $tree[$parent];
            for ($i = 1; $i < count($parts); $i++) {
                $part = $parts[$i];
                if ($part === '' || in_array($part, array_slice($parts, 0, $i), true)) {
                    continue;
                }
                if (!isset($node['children'][$part])) {
                    $node['children'][$part] = ['category_param' => $part, 'count' => 0, 'children' => []];
                }
                $node['children'][$part]['count']++;
                $node =& $node['children'][$part];
            }
            unset($node);
        }
    }
    if ($specialCount > 0 && function_exists('isCandybirdCategoryVisible') && isCandybirdCategoryVisible('Specials')) {
        $tree['Specials'] = [
            'category_param' => 'Specials',
            'display_name' => 'Specials',
            'count' => $specialCount,
            'children' => []
        ];
    }

    $order = [];
    foreach (getCandybirdCategoryDisplayOrder() as $index => $categoryName) {
        $order[$categoryName] = $index;
        if (function_exists('getCandybirdCategoryDisplayLabel')) {
            $order[getCandybirdCategoryDisplayLabel($categoryName)] = $index;
        }
        if (function_exists('getCandybirdCategorySlug')) {
            $order[getCandybirdCategorySlug($categoryName)] = $index;
            if (function_exists('getCandybirdCategoryDisplayLabel')) {
                $order[getCandybirdCategorySlug(getCandybirdCategoryDisplayLabel($categoryName))] = $index;
            }
        }
    }

    $sortNodes = function($nodes) use (&$sortNodes, $order) {
        uksort($nodes, function($a, $b) use ($order) {
            $keyA = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($a) : $a;
            $keyB = function_exists('getCandybirdCategorySlug') ? getCandybirdCategorySlug($b) : $b;
            if ($keyA === 'specials' && $keyB === 'clearance-basket') {
                return -1;
            }
            if ($keyA === 'clearance-basket' && $keyB === 'specials') {
                return 1;
            }
            $posA = function_exists('getCandybirdCategoryDisplayPosition') ? getCandybirdCategoryDisplayPosition($a) : PHP_INT_MAX;
            $posB = function_exists('getCandybirdCategoryDisplayPosition') ? getCandybirdCategoryDisplayPosition($b) : PHP_INT_MAX;
            if ($posA === PHP_INT_MAX) {
                $posA = $order[$a] ?? ($order[$keyA] ?? PHP_INT_MAX);
            }
            if ($posB === PHP_INT_MAX) {
                $posB = $order[$b] ?? ($order[$keyB] ?? PHP_INT_MAX);
            }
            if ($posA === $posB) {
                return strnatcasecmp($a, $b);
            }
            return $posA <=> $posB;
        });

        foreach ($nodes as $name => $node) {
            $displayName = $node['display_name'] ?? $name;
            $nodes[$name]['name'] = $displayName . ' (' . $node['count'] . ')';
            if (!empty($node['children'])) {
                $nodes[$name]['children'] = $sortNodes($node['children']);
            }
        }

        return array_values($nodes);
    };

    $builtTree = $sortNodes($tree);
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    if (is_dir($cacheDir) && is_writable($cacheDir)) {
        @file_put_contents($cacheFile, json_encode($builtTree), LOCK_EX);
    }

    return $builtTree;
}

function generateMenu($categories) {
    $html = '<ul class="offcanvas-submenu">';
    foreach ($categories as $category) {
        $categoryParam = $category['category_param'];
        $html .= '<li>';
        $html .= '<a class="navmenu-click-mobile" href="' . htmlspecialchars(function_exists('getCandybirdCategoryUrl') ? getCandybirdCategoryUrl($categoryParam) : ('products?category=' . urlencode($categoryParam))) . '"><span class="menu-text">' . htmlspecialchars($category['name']) . '</span></a>';
        if (!empty($category['children'])) {
            $html .= generateMenu($category['children']);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

function generateMenuMainNodes($categories) {
    if (empty($categories)) {
        return '';
    }

    $menu_html = '<ul class="sub-menu">';
    foreach ($categories as $category) {
        $menu_html .= '<li>';
        $menu_html .= '<a class="navmenu-click" href="' . htmlspecialchars(function_exists('getCandybirdCategoryUrl') ? getCandybirdCategoryUrl($category['category_param']) : ('products?category=' . urlencode($category['category_param']))) . '">' . htmlspecialchars($category['name']) . '</a>';
        $menu_html .= generateMenuMainNodes($category['children'] ?? []);
        $menu_html .= '</li>';
    }
    $menu_html .= '</ul>';

    return $menu_html;
}

function generateMenuMain($parent_id, $conn) {
    global $categories;
    return generateMenuMainNodes($categories);
}

$categories = buildSheetMenuCategories();
?>

        <li>
            <a href="products"><span class="menu-text">Categories</span></a>
            <?= generateMenu($categories) ?>
        </li>
        <li><a href="wholesale-pricelist">Buy Bulk</a></li>
        <li>
            <a href="#"><span class="menu-text">More</span></a>
            <ul class="offcanvas-submenu">
                <li><a class="navmenu-click-mobile" href="private_labelling">Private Labelling</a></li>
                <li><a class="navmenu-click-mobile" href="resellers">Become a Stockist</a></li>
                <li><a class="navmenu-click-mobile" href="contact">Contact Us</a></li>
                <li><a class="navmenu-click-mobile" href="recipes">Knowledge Centre</a></li>
                <li><a class="navmenu-click-mobile" href="global-services">Global Services</a></li>
                <li><a class="navmenu-click-mobile" href="pricelist">Pricelist</a></li>
                <li><a class="navmenu-click-mobile" href="wholesale-pricelist">Wholesale Pricelist</a></li>
                <li><a class="navmenu-click-mobile" href="return_policy">Buyer Protection</a></li>
                <li><a class="navmenu-click-mobile" href="about">About Sir Francis</a></li>
                <li><a class="navmenu-click-mobile" href="policies">Policies</a></li>
            </ul>
        </li>
      </ul>
    </nav>
    <div class="offcanvas-social py-30">
      <ul>
        <li>
          <a class="social-link-click" href="https://www.facebook.com/marinecollagenSA" aria-label="Sir Francis on Facebook"><i class="icon-social-facebook"></i></a>
        </li>
        <li>
          <a class="social-link-click" href="https://www.instagram.com/fishgelatine" aria-label="Sir Francis on Instagram"><i class="icon-social-instagram"></i></a>
        </li>
      </ul>
    </div>
  </div>
</div>
<!-- offcanvas-mobile-menu end -->

<?php
// Fetch wishlist items based on user or guest
$offCanvasWishlistItems = getWishlistItems($userId, $guestIdentifier);

// Format wishlist items for the off-canvas wishlist
$offcanvas_wishlist = "";

foreach ($offCanvasWishlistItems as $item) {
    $image_url = isset($item['image_url']) ? $item['image_url'] : 'assets/img/product/1.png';
    $wishlistTitle = trim($item['title'] . ' ' . ($item['weight'] ?? ''));
    $wishlistSheetProduct = getSheetProductById($item['id']);
    if ($wishlistSheetProduct) {
        $wishlistTitle = getSheetProductDisplayTitle($wishlistSheetProduct);
    }

    $offcanvas_wishlist .= '<li>';
    $wishlistItemLink = 'product?id=' . urlencode((string) $item['id']);
    $offcanvas_wishlist .= '<a href="' . htmlspecialchars($wishlistItemLink, ENT_QUOTES, 'UTF-8') . '" class="image">';
    $offcanvas_wishlist .= '<img src="' . $image_url . '" alt="' . htmlspecialchars($wishlistTitle, ENT_QUOTES, 'UTF-8') . '" width="80" height="80" loading="lazy" decoding="async"/>';
    $offcanvas_wishlist .= '</a>';
    $offcanvas_wishlist .= '<div class="content">';
    $offcanvas_wishlist .= '<a href="' . htmlspecialchars($wishlistItemLink, ENT_QUOTES, 'UTF-8') . '" class="title">' . htmlspecialchars($wishlistTitle, ENT_QUOTES, 'UTF-8') .'</a>';
    $offcanvas_wishlist .= '<span class="quantity-price">1 x <span class="amount">R' . $item['price'] . '</span>';
    $offcanvas_wishlist .= '<span><a href="#" class="remove removeFromWishlist" data-product-id="' . $item['id'] . '" aria-label="Remove ' . htmlspecialchars($wishlistTitle, ENT_QUOTES, 'UTF-8') . ' from wishlist">×</a></span>';
    $offcanvas_wishlist .= '</div>';
    $offcanvas_wishlist .= '</li>';
}

?>
<!-- OffCanvas Wishlist Start -->
<div id="offcanvas-wishlist" class="offcanvas offcanvas-wishlist theme1 no-print">
  <div class="inner">
    <div class="head d-flex flex-wrap justify-content-between">
      <span class="title">Wishlist</span>
      <button class="offcanvas-close" type="button" aria-label="Close wishlist">×</button>
    </div>
    <ul class="minicart-product-list">
      <?=$offcanvas_wishlist;?>
    </ul>
    <a
      href="wishlist"
      class="btn btn-secondary btn--lg d-block d-sm-inline-block mt-30"
      >view wishlist</a
    >
  </div>
</div>
<!-- OffCanvas Wishlist End -->

<?php
// Fetch cart items based on user or guest
$offCanvasCartItems = getCartItems($userId, $guestIdentifier);

// Format Cart items for the off-canvas Cart
$offcanvas_cart = "";
$cart_subtotal = 0;
$taxes = 0;
$discounts = 0;

foreach ($offCanvasCartItems as $item) {

  $coupon_code = $item['coupon_code'];
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
    $cart_subtotal += ($quantity * $discounted_price);

    $image_url = isset($item['image_url']) ? $item['image_url'] : 'assets/img/product/1.png';
    $cartItemTitle = trim($item['title'] . ' ' . ($item['product_weight'] ?? ''));
    $isCartClearance = !empty($item['is_clearance']) && $item['is_clearance'] === 'yes';
    $cartSheetProduct = $isCartClearance ? getSheetProductById($item['source_product_id'] ?? $item['product_id'] ?? '') : getSheetProductById($item['id']);
    if ($cartSheetProduct) {
        $cartItemTitle = $isCartClearance ? trim((string) $item['title']) : getSheetProductDisplayTitle($cartSheetProduct);
    }

    $offcanvas_cart .= '<li>';
    $cartItemLink = $item['product_url'] ?? ('product?id=' . urlencode((string) $item['id']));
    $offcanvas_cart .= '<a href="' . htmlspecialchars($cartItemLink, ENT_QUOTES, 'UTF-8') . '" class="image">';
    $offcanvas_cart .= '<img src="' . $image_url . '" alt="' . htmlspecialchars($cartItemTitle, ENT_QUOTES, 'UTF-8') . '" width="80" height="80" loading="lazy" decoding="async"/>';
    $offcanvas_cart .= '</a>';
    $offcanvas_cart .= '<div class="content">';
    $offcanvas_cart .= '<a href="' . htmlspecialchars($cartItemLink, ENT_QUOTES, 'UTF-8') . '" class="title">' . htmlspecialchars($cartItemTitle, ENT_QUOTES, 'UTF-8') .'</a>';
    if ((!empty($item['free_delivery_excluded']) && $item['free_delivery_excluded'] === 'yes') || isCandybirdFreeDeliveryExcluded($cartSheetProduct)) {
        $offcanvas_cart .= '<small style="display:block;color:#8a8178;font-size:11px;line-height:1.35;margin-top:3px;">Free shipping does not apply to this item.</small>';
    }
    $offcanvas_cart .= '<span class="quantity-price">' . $item['quantity'] . ' x <span class="amount">R' . number_format($discounted_price, 2) . '</span>';
    $offcanvas_cart .= '<span><a href="#" class="remove removeFromCart" data-product-id="' . $item['id'] . '" aria-label="Remove ' . htmlspecialchars($cartItemTitle, ENT_QUOTES, 'UTF-8') . ' from cart">×</a></span>';
    $offcanvas_cart .= '</div>';
    $offcanvas_cart .= '</li>';
}

?>

<!-- OffCanvas Cart Start -->
<div id="offcanvas-cart" class="offcanvas offcanvas-cart theme1 no-print">
  <div class="inner">
    <div class="head d-flex flex-wrap justify-content-between">
      <span class="title">Cart</span>
      <button class="offcanvas-close" type="button" aria-label="Close cart">×</button>
    </div>
    <ul class="minicart-product-list">
      <?=$offcanvas_cart;?>
    </ul>
    <div class="sub-total d-flex flex-wrap justify-content-between">
      <strong>Subtotal :</strong>
      <span class="amount"><?=number_format($cart_subtotal, 2)?></span>
    </div>
    <a
      href="cart"
      class="btn btn-secondary btn--lg d-block d-sm-inline-block mr-sm-2"
      >view cart</a
    >
    <a
      href="checkout"
      class="btn btn-dark btn--lg d-block d-sm-inline-block mt-4 mt-sm-0"
      >checkout</a
    >
    <p class="minicart-message">Free Shipping on All Orders Over R<?=$free_shipping_amount?>!</p>
  </div>
</div>
<!-- OffCanvas Cart End -->

<!-- header start -->
<header class="no-print">
  <!-- header top start -->
  <div class="header-top theme1 bg-dark py-15">
    <div class="container container1">
      <div class="row align-items-center">
        <div class="col-lg-6 col-sm-6 order-last order-sm-first">
          <div
            class="d-flex justify-content-center justify-content-sm-start align-items-center"
          >
            <div class="social-network2">
              <ul class="d-flex">
                <li>
                  <a href="https://www.facebook.com/marinecollagenSA" target="_blank" aria-label="Sir Francis on Facebook"
                    ><span class="icon-social-facebook"></span
                  ></a>
                </li>
                <li class="mr-0">
                  <a href="https://www.instagram.com/fishgelatine" target="_blank" aria-label="Sir Francis on Instagram"
                    ><span class="icon-social-instagram"></span
                  ></a>
                </li>
              </ul>
            </div>
            <div class="media static-media ml-4 d-flex align-items-center">
              <div class="media-body">
                <div class="phone">
                  <a href="tel:<?=$tel?>" class="text-white"
                    ><i class="icon-call-out mr-1"></i> <?=$tel?></a
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 col-sm-6">
          <nav class="navbar-top pb-2 pb-sm-0 position-relative">
            <ul
              class="d-flex justify-content-center justify-content-md-end align-items-center"
            >
              <li>
                  <a href="#" id="dropdown1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Welcome, <?=$username?>! <i class="ion ion-ios-arrow-down"></i></a>
                  <ul class="topnav-submenu dropdown-menu" aria-labelledby="dropdown1">

                      <li><a href="profile#orders">My Orders</a></li>
                      <!-- <li><a href="checkout">Checkout</a></li> -->
                      <?php
                          // Check if the user is logged in
                          if (isset($_SESSION['user_id'])) {
                              echo '<li><a href="profile">My account</a></li>';
                              echo '<li><a href="logout">Sign out</a></li>';
                          } else {
                              echo '<li><a href="login">Sign in</a></li>';
                          }
                      ?>
                    </ul>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
  <!-- header top end -->
  
  <!-- header-middle satrt -->
  <div id="sticky" class="header-middle theme1 py-15 py-lg-0">
    <div class="container container1 position-relative">
      <div class="row align-items-center">
        <div class="col-6 col-lg-2 col-xl-2">
          <div class="logo">
            <a href="./"
              ><img src="<?=$home_directory?>assets/img/logo/logo.png" alt="Sir Francis"
            /></a>
          </div>
        </div>
        <div class="col-xl-8 col-lg-7 d-none d-lg-block">
          <ul class="main-menu d-flex justify-content-center">

            <li class="active ml-0">
              <a class="navmenu-click" href="./">Home</a>
            </li>
            <li class="ml-0">
              <a class="navmenu-click" href="products">Retail Shop</a>
            </li>
            <li>
              <a class="navmenu-click" href="products"
                >Categories <i class="ion-ios-arrow-down"></i
              ></a>
              <?= generateMenuMainNodes($categories) ?>
            </li>
            <li>
              <a class="navmenu-click" href="wholesale-pricelist">Buy Bulk</a>
            </li>

            <li>
              <a href="#"
                >More <i class="ion-ios-arrow-down"></i
              ></a>
              <ul class="sub-menu">
                <li><a class="navmenu-click" href="private_labelling">Private Labelling</a></li>
                <li><a class="navmenu-click" href="resellers">Become a Stockist</a></li>
                <li><a class="navmenu-click" href="contact">Contact Us</a></li>
                <li><a class="navmenu-click" href="recipes">Knowledge Centre</a></li>
                <li><a class="navmenu-click" href="pricelist">Pricelist</a></li>
                <li><a class="navmenu-click" href="wholesale-pricelist">Wholesale Pricelist</a></li>
                <li>
                  <a class="navmenu-click" href="return_policy">Buyer Protection</a>
                </li>
                <li>
                  <a class="navmenu-click" href="about">About Sir Francis</a>
                </li>
                <li><a class="navmenu-click" href="global-services">Global Services</a></li>
                <li><a class="navmenu-click" href="policies">Policies</a></li>
              </ul>
            </li>







          </ul>
        </div>
        <div class="col-6 col-lg-3 col-xl-2">
          <!-- search-form end -->
          <div class="d-flex align-items-center justify-content-end">
            <!-- static-media end -->
            <div class="cart-block-links theme1 d-none d-sm-block">
              <ul class="d-flex">
                <li>
                  <a href="javascript:void(0)" class="search search-toggle" aria-label="Open search">
                    <i class="icon-magnifier"></i>
                  </a>
                </li>
                <li>
                  <a href="compare" aria-label="Compare products">
                    <span class="position-relative" id="compareBadge">
                      <i class="icon-shuffle"></i>
                      <?= $compareCount > 0 ? '<span class="badge cbdg1">' . $compareCount . '</span>' : '' ?>
                    </span>
                  </a>
                </li>
                <li>
                  <a class="<?= !empty($load_shopping_nav) ? 'offcanvas-toggle' : '' ?>" href="<?= !empty($load_shopping_nav) ? '#offcanvas-wishlist' : 'wishlist' ?>" aria-label="Open wishlist">
                    <span class="position-relative" id="wishlistBadge">
                      <i class="icon-heart"></i>
                      <?= $wishlistCount > 0 ? '<span class="badge cbdg1">' . $wishlistCount . '</span>' : '' ?>
                    </span>
                  </a>
                </li>
                <li class="mr-xl-0 cart-block position-relative">
                  <a class="<?= !empty($load_shopping_nav) ? 'offcanvas-toggle' : '' ?>" href="<?= !empty($load_shopping_nav) ? '#offcanvas-cart' : 'cart' ?>" aria-label="Open cart">
                    <span class="position-relative" id="cartBadge">
                      <i class="icon-bag"></i>
                      <?= $cartCount > 0 ? '<span class="badge cbdg1">' . $cartCount . '</span>' : '' ?>
                    </span>
                  </a>
                </li>
                <!-- cart block end -->
              </ul>
            </div>
            <div class="mobile-menu-toggle theme1 d-lg-none">
              <a href="#offcanvas-mobile-menu" class="offcanvas-toggle">
                <svg viewbox="0 0 700 550">
                  <path
                    d="M300,220 C300,220 520,220 540,220 C740,220 640,540 520,420 C440,340 300,200 300,200"
                    id="top"
                  ></path>
                  <path d="M300,320 L540,320" id="middle"></path>
                  <path
                    d="M300,210 C300,210 520,210 540,210 C740,210 640,530 520,410 C440,330 300,190 300,190"
                    id="bottom"
                    transform="translate(480, 320) scale(1, -1) translate(-480, -318)"
                  ></path>
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- header-middle end -->
</header>
<!-- header end -->

<?php
include 'breadcrumbs.php';
?>
