</head>

<body>

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
      <ul>
        <li><a href="dashboard">Admin Dashboard</a></li>
        <li><a href="manage_orders">Orders</a></li>
        <li><a href="manage_users">Customers</a></li>
        <li><a href="manage_website_information#contact-info">Contact Info</a></li>
        <li><a href="manage_website_information#shipping-settings">Shipping</a></li>
        <li><a href="sheets#sheet-products">Products</a></li>
        <li><a href="sheets#sheet-coupons">Coupons and Clearance</a></li>
        <li><a href="category_order">Categories</a></li>


      </ul>






    </nav>

  </div>
</div>
<!-- offcanvas-mobile-menu end -->
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
        <div class="col-6 col-lg-10 col-xl-10 d-none d-lg-block">
          <style>
            .admin-nav-menu {
              align-items: center;
              gap: 10px;
              justify-content: flex-end;
              margin: 0;
              width: 100%;
            }
            .admin-nav-menu > li > a {
              font-size: 12px;
              font-weight: 700;
              line-height: 1.2;
              padding: 12px 0;
              white-space: nowrap;
            }
            .admin-nav-menu > li.active > a,
            .admin-nav-menu > li > a:hover {
              color: #5b1178;
            }
            @media (min-width: 1200px) {
              .admin-nav-menu { gap: 16px; }
              .admin-nav-menu > li > a { font-size: 13px; }
            }
          </style>
          <ul class="main-menu admin-nav-menu d-flex">
            <li class="active ml-0"><a href="dashboard">Admin Dashboard</a></li>
            <li><a href="manage_orders">Orders</a></li>
            <li><a href="manage_users">Customers</a></li>
            <li><a href="manage_website_information#contact-info">Contact Info</a></li>
            <li><a href="manage_website_information#shipping-settings">Shipping</a></li>
            <li><a href="sheets#sheet-products">Products</a></li>
            <li><a href="sheets#sheet-coupons">Coupons and Clearance</a></li>
            <li><a href="category_order">Categories</a></li>


          </ul>
        </div>
      </div>
    </div>
  </div>
  <!-- header-middle end -->
</header>
<!-- header end -->
