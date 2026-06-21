</head>

<body class="admin-sf">

<?php
$adminCurrentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '.php');
function cbAdminMenuOpen($pages, $currentPage) {
  return in_array($currentPage, (array) $pages, true) ? ' open' : '';
}
function cbAdminMenuActive($page, $currentPage) {
  return $page === $currentPage ? ' class="active"' : '';
}
?>

<style>
  .admin-sidebar {
    background: var(--sf-navy);
    bottom: 0;
    box-shadow: 10px 0 30px rgba(23, 34, 53, .18);
    color: #fff;
    left: 0;
    overflow-y: auto;
    padding: 18px 16px;
    position: fixed;
    top: 0;
    width: 270px;
    z-index: 1040;
  }
  .admin-sidebar-logo {
    background: var(--sf-white);
    border-radius: 8px;
    display: block;
    margin-bottom: 18px;
    padding: 12px;
  }
  .admin-sidebar-logo img { display: block; max-width: 165px; width: 100%; }
  .admin-sidebar-title {
    color: var(--sf-gold);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .08em;
    margin: 0 0 10px;
    text-transform: uppercase;
  }
  .admin-sidebar-nav,
  .admin-sidebar-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
  }
  .admin-sidebar-nav a,
  .admin-sidebar-nav summary {
    align-items: center;
    border-radius: 8px;
    color: rgba(255,255,255,.9);
    cursor: pointer;
    display: flex;
    font-size: 14px;
    font-weight: 700;
    justify-content: space-between;
    line-height: 1.25;
    padding: 10px 11px;
    text-decoration: none;
  }
  .admin-sidebar-nav a:hover,
  .admin-sidebar-nav summary:hover,
  .admin-sidebar-nav a.active {
    background: rgba(206, 189, 136, .16);
    color: var(--sf-gold);
  }
  .admin-sidebar-nav details {
    margin: 3px 0;
  }
  .admin-sidebar-nav summary::-webkit-details-marker { display: none; }
  .admin-sidebar-nav summary::after {
    content: "+";
    color: var(--sf-gold);
    font-weight: 900;
  }
  .admin-sidebar-nav details[open] > summary::after {
    content: "-";
  }
  .admin-sidebar-nav details ul {
    border-left: 1px solid rgba(206, 189, 136, .32);
    margin: 4px 0 8px 12px;
    padding-left: 8px;
  }
  .admin-sidebar-nav details ul a {
    color: rgba(255,255,255,.78);
    font-size: 13px;
    padding: 8px 10px;
  }
  .admin-sidebar-footer {
    border-top: 1px solid rgba(255,255,255,.12);
    margin-top: 16px;
    padding-top: 12px;
  }
  .admin-mobile-menu details {
    border-bottom: 1px solid #eee;
    padding: 6px 0;
  }
  .admin-mobile-menu summary {
    cursor: pointer;
    font-weight: 800;
    list-style: none;
    padding: 8px 0;
  }
  .admin-mobile-menu summary::-webkit-details-marker { display: none; }
  .admin-mobile-menu summary::after {
    content: "+";
    float: right;
  }
  .admin-mobile-menu details[open] summary::after { content: "-"; }
  .admin-mobile-menu details ul { padding-left: 14px; }
  @media (min-width: 992px) {
    body { padding-left: 270px; }
    header.no-print { display: none; }
  }
</style>

<!-- offcanvas-overlay start -->
<div class="offcanvas-overlay"></div>
<!-- offcanvas-overlay end -->
<!-- offcanvas-mobile-menu start -->
<div id="offcanvas-mobile-menu" class="offcanvas theme1 offcanvas-mobile-menu">
  <div class="inner">
    <div class="border-bottom mb-4 pb-4 text-right">
      <button class="offcanvas-close">×</button>
    </div>
    <div class="offcanvas-head mb-4">
      <strong>Admin Navigation</strong>
    </div>
    <nav class="offcanvas-menu">
      <div class="admin-mobile-menu">
        <ul>
          <li><a href="dashboard">Admin Dashboard</a></li>
          <li>
            <details<?= cbAdminMenuOpen(['manage_orders', 'create_order', 'manage_order', 'order_details'], $adminCurrentPage) ?>>
              <summary>Orders</summary>
              <ul>
                <li><a href="manage_orders">Manage Orders</a></li>
                <li><a href="create_order">Create Order</a></li>
              </ul>
            </details>
          </li>
          <li><a href="manage_users">Customers</a></li>
          <li><a href="schedule_email">Create Broadcast</a></li>
          <li><a href="email_lists">Email Lists</a></li>
          <li><a href="broadcasts">Broadcast History</a></li>
          <li>
            <details<?= cbAdminMenuOpen(['social_accounts', 'business_documents', 'agents'], $adminCurrentPage) ?>>
              <summary>Business Ops</summary>
              <ul>
                <li><a href="agents">Find Agents</a></li>
                <li><a href="social_accounts">Social Accounts</a></li>
                <li><a href="business_documents">Business Documents</a></li>
              </ul>
            </details>
          </li>
          <li><a href="backups">Website Backups</a></li>
          <li>
            <details<?= cbAdminMenuOpen(['visitor_activity', 'visitor_breakdown'], $adminCurrentPage) ?>>
              <summary>Visitor Analytics</summary>
              <ul>
                <li><a href="visitor_activity">Activity Stories</a></li>
                <li><a href="visitor_breakdown">Visitor Breakdown</a></li>
              </ul>
            </details>
          </li>
          <li>
            <details<?= cbAdminMenuOpen(['manage_website_information', 'shipping_settings', 'google_maps_places', 'google_recaptcha', 'editor_settings', 'site_flags'], $adminCurrentPage) ?>>
              <summary>Website Settings</summary>
              <ul>
                <li><a href="manage_website_information">Contact Info</a></li>
                <li><a href="shipping_settings">Shipping</a></li>
                <li><a href="google_maps_places">Google Maps & Places</a></li>
                <li><a href="google_recaptcha">Google reCAPTCHA</a></li>
                <li><a href="editor_settings">Editor Settings</a></li>
                <li><a href="site_flags">Site Notices</a></li>
              </ul>
            </details>
          </li>
          <li><a href="manage_gallery">Image Gallery</a></li>
          <li>
            <details<?= cbAdminMenuOpen(['products', 'coupons', 'coupon_tester', 'clearance', 'wholesale_pricelist', 'sheets', 'sheet_sources', 'tsv_how_to', 'manage_products', 'sync_sheet_products'], $adminCurrentPage) ?>>
              <summary>Products & Sheets</summary>
              <ul>
                <li><a href="products">Products</a></li>
                <li><a href="coupons">Coupons</a></li>
                <li><a href="coupon_tester">Coupon Tester</a></li>
                <li><a href="clearance">Clearance Basket</a></li>
                <li><a href="wholesale_pricelist">Wholesale Pricelist</a></li>
                <li><a href="sheets">Mega Sync All Sheets</a></li>
                <li><a href="tsv_how_to">TSV How-to</a></li>
              </ul>
            </details>
          </li>
          <li><a href="category_order">Categories</a></li>
        </ul>
      </div>
    </nav>

  </div>
</div>
<!-- offcanvas-mobile-menu end -->

<aside class="admin-sidebar d-none d-lg-block no-print" aria-label="Admin navigation">
  <a class="admin-sidebar-logo" href="dashboard">
    <img src="<?=$home_directory?>assets/img/logo/logo.png" alt="Sir Francis">
  </a>
  <p class="admin-sidebar-title">Admin Panel</p>
  <nav>
    <ul class="admin-sidebar-nav">
      <li><a href="dashboard"<?= cbAdminMenuActive('dashboard', $adminCurrentPage) ?>>Admin Dashboard</a></li>
      <li>
        <details<?= cbAdminMenuOpen(['manage_orders', 'create_order', 'manage_order', 'order_details'], $adminCurrentPage) ?>>
          <summary>Orders</summary>
          <ul>
            <li><a href="manage_orders"<?= cbAdminMenuActive('manage_orders', $adminCurrentPage) ?>>Manage Orders</a></li>
            <li><a href="create_order"<?= cbAdminMenuActive('create_order', $adminCurrentPage) ?>>Create Order</a></li>
          </ul>
        </details>
      </li>
      <li><a href="manage_users"<?= cbAdminMenuActive('manage_users', $adminCurrentPage) ?>>Customers</a></li>
      <li>
        <details<?= cbAdminMenuOpen(['schedule_email', 'email_lists', 'broadcasts'], $adminCurrentPage) ?>>
          <summary>Newsletter Broadcaster / Email Scheduler</summary>
          <ul>
            <li><a href="schedule_email"<?= cbAdminMenuActive('schedule_email', $adminCurrentPage) ?>>Create Broadcast</a></li>
            <li><a href="email_lists"<?= cbAdminMenuActive('email_lists', $adminCurrentPage) ?>>Email Lists</a></li>
            <li><a href="broadcasts"<?= cbAdminMenuActive('broadcasts', $adminCurrentPage) ?>>Broadcast History</a></li>
          </ul>
        </details>
      </li>
      <li><a href="backups"<?= cbAdminMenuActive('backups', $adminCurrentPage) ?>>Website Backups</a></li>
      <li>
        <details<?= cbAdminMenuOpen(['social_accounts', 'business_documents', 'agents'], $adminCurrentPage) ?>>
          <summary>Business Ops</summary>
          <ul>
            <li><a href="agents"<?= cbAdminMenuActive('agents', $adminCurrentPage) ?>>Find Agents</a></li>
            <li><a href="social_accounts"<?= cbAdminMenuActive('social_accounts', $adminCurrentPage) ?>>Social Accounts</a></li>
            <li><a href="business_documents"<?= cbAdminMenuActive('business_documents', $adminCurrentPage) ?>>Business Documents</a></li>
          </ul>
        </details>
      </li>
      <li>
        <details<?= cbAdminMenuOpen(['visitor_activity', 'visitor_breakdown'], $adminCurrentPage) ?>>
          <summary>Visitor Analytics</summary>
          <ul>
            <li><a href="visitor_activity"<?= cbAdminMenuActive('visitor_activity', $adminCurrentPage) ?>>Activity Stories</a></li>
            <li><a href="visitor_breakdown"<?= cbAdminMenuActive('visitor_breakdown', $adminCurrentPage) ?>>Visitor Breakdown</a></li>
          </ul>
        </details>
      </li>
      <li>
        <details<?= cbAdminMenuOpen(['manage_website_information', 'shipping_settings', 'google_maps_places', 'google_recaptcha', 'editor_settings', 'site_flags'], $adminCurrentPage) ?>>
          <summary>Website Settings</summary>
          <ul>
            <li><a href="manage_website_information">Contact Info</a></li>
            <li><a href="shipping_settings">Shipping</a></li>
            <li><a href="google_maps_places"<?= cbAdminMenuActive('google_maps_places', $adminCurrentPage) ?>>Google Maps & Places</a></li>
            <li><a href="google_recaptcha">Google reCAPTCHA</a></li>
            <li><a href="editor_settings"<?= cbAdminMenuActive('editor_settings', $adminCurrentPage) ?>>Editor Settings</a></li>
            <li><a href="site_flags"<?= cbAdminMenuActive('site_flags', $adminCurrentPage) ?>>Site Notices</a></li>
          </ul>
        </details>
      </li>
      <li><a href="manage_gallery"<?= cbAdminMenuActive('manage_gallery', $adminCurrentPage) ?>>Image Gallery</a></li>
      <li>
        <details<?= cbAdminMenuOpen(['products', 'coupons', 'coupon_tester', 'clearance', 'wholesale_pricelist', 'sheets', 'sheet_sources', 'tsv_how_to', 'manage_products', 'sync_sheet_products'], $adminCurrentPage) ?>>
          <summary>Products & Sheets</summary>
          <ul>
            <li><a href="products"<?= cbAdminMenuActive('products', $adminCurrentPage) ?>>Products</a></li>
            <li><a href="coupons"<?= cbAdminMenuActive('coupons', $adminCurrentPage) ?>>Coupons</a></li>
            <li><a href="coupon_tester"<?= cbAdminMenuActive('coupon_tester', $adminCurrentPage) ?>>Coupon Tester</a></li>
            <li><a href="clearance"<?= cbAdminMenuActive('clearance', $adminCurrentPage) ?>>Clearance Basket</a></li>
            <li><a href="wholesale_pricelist"<?= cbAdminMenuActive('wholesale_pricelist', $adminCurrentPage) ?>>Wholesale Pricelist</a></li>
            <li><a href="sheets">Mega Sync All Sheets</a></li>
            <li><a href="tsv_how_to"<?= cbAdminMenuActive('tsv_how_to', $adminCurrentPage) ?>>TSV How-to</a></li>
          </ul>
        </details>
      </li>
      <li><a href="category_order"<?= cbAdminMenuActive('category_order', $adminCurrentPage) ?>>Categories</a></li>
    </ul>
  </nav>
  <div class="admin-sidebar-footer">
    <ul class="admin-sidebar-nav">
      <li><a href="index">Admin Sitemap</a></li>
      <li><a href="<?php echo isset($_SESSION['admin_id']) ? 'logout' : 'admin_login'; ?>"><?php echo isset($_SESSION['admin_id']) ? 'Sign Out' : 'Sign In'; ?></a></li>
    </ul>
  </div>
</aside>
<?php include __DIR__ . '/admin_help.php'; ?>
<!-- OffCanvas Wishlist Start -->
<div id="offcanvas-wishlist" class="offcanvas offcanvas-wishlist theme1">
  <div class="inner">
    <div class="head d-flex flex-wrap justify-content-between">
      <span class="title">Wishlist</span>
      <button class="offcanvas-close">×</button>
    </div>
    <ul class="minicart-product-list">
      <li>
        <a href="single-product" class="image"
          ><img src="<?=$home_directory?>assets/img/mini-cart/4.png" alt="Cart product Image"
        /></a>
        <div class="content">
          <a href="single-product" class="title"
            >orginal Age Defying Cosmetics Makeup</a
          >
          <span class="quantity-price"
            >1 x <span class="amount">$100.00</span></span
          >
          <a href="#" class="remove">×</a>
        </div>
      </li>
      <li>
        <a href="single-product" class="image"
          ><img src="<?=$home_directory?>assets/img/mini-cart/5.png" alt="Cart product Image"
        /></a>
        <div class="content">
          <a href="single-product" class="title"
            >On Trend Makeup and Beauty Cosmetics</a
          >
          <span class="quantity-price"
            >1 x <span class="amount">$35.00</span></span
          >
          <a href="#" class="remove">×</a>
        </div>
      </li>
      <li>
        <a href="single-product" class="image"
          ><img src="<?=$home_directory?>assets/img/mini-cart/6.png" alt="Cart product Image"
        /></a>
        <div class="content">
          <a href="single-product" class="title"
            >orginal Age Defying Cosmetics Makeup</a
          >
          <span class="quantity-price"
            >1 x <span class="amount">$9.00</span></span
          >
          <a href="#" class="remove">×</a>
        </div>
      </li>
    </ul>
    <a
      href="wishlist"
      class="btn btn-secondary btn--lg d-block d-sm-inline-block mt-30"
      >view wishlist</a
    >
  </div>
</div>
<!-- OffCanvas Wishlist End -->

<!-- OffCanvas Cart Start -->
<div id="offcanvas-cart" class="offcanvas offcanvas-cart theme1">
  <div class="inner">
    <div class="head d-flex flex-wrap justify-content-between">
      <span class="title">Cart</span>
      <button class="offcanvas-close">×</button>
    </div>
    <ul class="minicart-product-list">
      <li>
        <a href="single-product" class="image"
          ><img src="<?=$home_directory?>assets/img/mini-cart/1.png" alt="Cart product Image"
        /></a>
        <div class="content">
          <a href="single-product" class="title"
            >orginal Age Defying Cosmetics Makeup</a
          >
          <span class="quantity-price"
            >1 x <span class="amount">$100.00</span></span
          >
          <a href="#" class="remove">×</a>
        </div>
      </li>
      <li>
        <a href="single-product" class="image"
          ><img src="<?=$home_directory?>assets/img/mini-cart/2.png" alt="Cart product Image"
        /></a>
        <div class="content">
          <a href="single-product" class="title"
            >On Trend Makeup and Beauty Cosmetics</a
          >
          <span class="quantity-price"
            >1 x <span class="amount">$35.00</span></span
          >
          <a href="#" class="remove">×</a>
        </div>
      </li>
      <li>
        <a href="single-product" class="image"
          ><img src="<?=$home_directory?>assets/img/mini-cart/3.png" alt="Cart product Image"
        /></a>
        <div class="content">
          <a href="single-product" class="title"
            >orginal Age Defying Cosmetics Makeup</a
          >
          <span class="quantity-price"
            >1 x <span class="amount">$9.00</span></span
          >
          <a href="#" class="remove">×</a>
        </div>
      </li>
    </ul>
    <div class="sub-total d-flex flex-wrap justify-content-between">
      <strong>Subtotal :</strong>
      <span class="amount">$144.00</span>
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
    <p class="minicart-message">Free Shipping on All Orders Over R<?=$free_shipping_amount?>!</p></p>
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
                  <p class="text-white">ADMIN PANEL</p>
                </li>
              </ul>
            </div>
            <div class="media static-media ml-4 d-flex align-items-center">
              <div class="media-body">
                <div class="phone">
                  
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
                  <a href="#" id="dropdown1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Account
                      <i class="ion ion-ios-arrow-down"></i>
                  </a>
                  <ul class="topnav-submenu dropdown-menu" aria-labelledby="dropdown1">
                      <li><a href="settings">Settings</a></li>
                      <li><a href="<?php echo isset($_SESSION['admin_id']) ? 'logout' : 'admin_login'; ?>"><?php echo isset($_SESSION['admin_id']) ? 'Sign Out' : 'Sign In'; ?></a></li>
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
            <a href="index"
              ><img src="<?=$home_directory?>assets/img/logo/logo.png" alt="logo"
            /></a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- header-middle end -->
</header>
<!-- header end -->
