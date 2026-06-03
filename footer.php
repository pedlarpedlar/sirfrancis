
<?php
if (strpos($_SERVER['PHP_SELF'], '/admin-cb/') !== false) {
    $userId = null;
    $guestIdentifier = null;
}
$footerWhatsappNumber = trim((string) ($hotline ?? ''));
if ($footerWhatsappNumber === '') {
    $footerWhatsappNumber = trim((string) ($tel ?? ''));
}
$footerWhatsappDigits = preg_replace('/\D+/', '', $footerWhatsappNumber);
if (strpos($footerWhatsappDigits, '0') === 0) {
    $footerWhatsappDigits = '27' . substr($footerWhatsappDigits, 1);
}
?>

<style>
  
  .footer-rope {
      width: 100%;
      height: 20px;
      margin-bottom: -2px;
      //background-image: url('<?=$home_directory?>assets/img/rope.png'); /* Set the background image */
      background-repeat: repeat-x; /* Repeat horizontally */
      background-position: bottom left; /* Align at the bottom-left corner */
      position: relative;
      z-index: 2;
    }

</style>


<div class="footer-rope"></div>

<!-- footer start -->
<footer class="bg-dark theme1 theme2 position-relative compass text-center no-print">

  <!-- footer bottom start -->
  <div class="footer-bottom pt-80 pb-100">
    <div class="container container1">
      <div class="row">
        <div class="col-12 col-sm-6 col-lg-6 mb-30">
          <div class="footer-widget mx-w-400">
<div class="footer-logo mb-25">
    <a href="./">
      <img src="<?=$home_directory?>assets/img/footer-image1.png" alt="footer logo" width="200px" />
    </a>
  </div>

  <div class="social-network">
    <ul class="d-flex justify-content-center"> <!-- Added justify-content-center class -->
      <li>
        <a class="social-link-click" href="https://www.facebook.com/candybirdnuts" target="_blank"><span class="icon-social-facebook"></span></a>
      </li>
      <li class="mr-0">
        <a class="social-link-click" href="https://www.instagram.com/candybirdnuts" target="_blank"><span class="icon-social-instagram"></span></a>
      </li>
    </ul>
  </div>

  <div class="payment-trust-strip" aria-label="Payment methods and checkout security">
    <span><i class="fas fa-lock"></i> Secure checkout</span>
    <span><i class="fab fa-cc-visa"></i> Visa</span>
    <span><i class="fab fa-cc-mastercard"></i> Mastercard</span>
    <span>PayFast</span>
    <span>EFT</span>
    <span>Ozow coming soon</span>
    <span>Buy now, pay later coming soon</span>
  </div>

  <div class="footer-link-groups" aria-label="Footer links">
    <div>
      <h3>Shop</h3>
      <a href="<?=$home_directory?>products">Online Shop</a>
      <a href="<?=$home_directory?>pricelist">Pricelist</a>
      <a href="<?=$home_directory?>wholesale-pricelist">Wholesale Pricelist</a>
      <a href="<?=$home_directory?>gifting">Shop Gifts</a>
      <a href="<?=$home_directory?>recipes">Recipes</a>
    </div>
    <div>
      <h3>Business</h3>
      <a href="<?=$home_directory?>bulk_ordering">Bulk Ordering</a>
      <a href="<?=$home_directory?>wholesale">Wholesale</a>
      <a href="<?=$home_directory?>wholesale-pricelist">Bulk Pricelist</a>
      <a href="<?=$home_directory?>private_labelling">Private Labelling</a>
      <a href="<?=$home_directory?>contact">Custom Quote</a>
    </div>
    <div>
      <h3>Help</h3>
      <a href="<?=$home_directory?>contact">Contact Us</a>
      <a href="https://www.google.com/maps/search/?api=1&amp;query=18%20Babiana%20Rd%2C%20Malabar%2C%20Port%20Elizabeth%2C%20South%20Africa" target="_blank" rel="noopener noreferrer">Find us on Google Maps</a>
      <a href="<?=$home_directory?>delivery_policy">Delivery Policy</a>
      <a href="<?=$home_directory?>return_policy">Buyer Protection and Returns</a>
      <a href="<?=$home_directory?>terms">Terms and Conditions</a>
      <a href="<?=$home_directory?>privacypolicy">Privacy Policy</a>
    </div>
  </div>





          </div>

        </div>

        <div class="col-12 col-sm-6 col-lg-6 mb-30 text-left">
          <div class="footer-widget">
            
            <div class="mb-100">
                <h2 class="">Contact Us</h2>
                <img src="<?=$home_directory?>assets/img/break.svg" alt="wave" class="mt-2 mb-5">
              
                <ul class="mt-2 custom">
                  <li><a href="contact"><i class="fas fa-envelope mr-2"></i> <?=$website_email?></a></li>
                  <li><a href="contact"><i class="fas fa-phone mr-2"></i> <?=$tel?></a></li>
                  <?php if (!empty($footerWhatsappDigits)): ?>
                    <li><a href="https://wa.me/<?=$footerWhatsappDigits?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp mr-2"></i> <?=$footerWhatsappNumber?></a></li>
                  <?php endif; ?>
                  <li><a href="contact"><i class="fas fa-map mr-2"></i> <?=$website_address?></a></li>
                </ul>
            </div>


            <div style="width:300px">
              <h2 >Subscribe</h2>
              <p>Enter your email below to never miss out on our flash sales & special coupons just for you.</p>
                <img src="<?=$home_directory?>assets/img/break.svg" alt="wave" class="mt-2 mb-5">
              <div class="nletter-form mt-20">
                <form
                  class="form-inline position-relative"
                  action="https://www.candybird.co.za/subscribe.inc.php"
                  target="_blank"
                  method="post"
                  id="subscribe_form"
                >
                  <input
                    class="form-control"
                    type="text"
                    id="subscribe_email"
                    name="subscribe_email"
                    placeholder="Your email"
                  />
                  <button class="btn news-letter-btn text-capitalize" type="submit">Subscribe</button>
                </form>
              </div>
            </div>


          </div>
        </div>


      </div>
    </div>
  </div>
  <!-- footer bottom end -->
  <!-- coppy-right start -->
<div class="coppy-right bg-dark py-15 ">
  <div class="container container1">
    <div class="row">
      <div class="col-12">
        <div class="d-flex flex-column align-items-center text-center pt-3">
          <p>
            Copyright &copy; <a href="./">CandyBird</a>.
            All Rights Reserved
          </p>
          <img src="<?=$home_directory?>assets/img/logo/logo.png" alt="img" width="150px" class="mt-2"/>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .coppy-right {
    background-image: url('<?=$home_directory?>assets/img/footer.png'); /* Replace with the actual path to your image */
    background-repeat: repeat-x;
    background-position: bottom;
    border-spacing: 0;
  }

  .compass1 {
    background-image: url('<?=$home_directory?>assets/img/compass.png'); /* Replace with the actual path to your image */
    background-size: auto;
    background-position: center;
    background-repeat: no-repeat;
  }

  ul.custom li {
    margin-top: 10px;
  }
  ul.custom li a:not(:hover):not(:focus):not(:active) {
    color: white !important;
  }

  ul.custom li a {
    font-size: 1.2em;
  }

  ul.custom li a i {
    color: #FCB42F !important;
  }

  .footer-link-groups {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
    justify-content: center;
    column-gap: 22px;
    row-gap: 18px;
    margin: 24px auto 0;
    width: min(100%, 560px);
    max-width: 100%;
    text-align: left;
    overflow-wrap: anywhere;
  }

  .payment-trust-strip {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    justify-content: center;
    margin: 18px auto 0;
    max-width: 600px;
  }

  .payment-trust-strip span {
    align-items: center;
    border: 1px solid rgba(255,255,255,.26);
    color: rgba(255,255,255,.9);
    display: inline-flex;
    font-size: 12px;
    font-weight: 700;
    gap: 5px;
    line-height: 1.2;
    padding: 6px 8px;
  }

  .payment-trust-strip i {
    color: #FCB42F;
    font-size: 15px;
  }

  .footer-link-groups h3 {
    color: #FCB42F;
    font-size: 14px;
    margin: 0 0 8px;
    line-height: 1.25;
    text-transform: none;
  }

  .footer-link-groups a {
    display: block;
    color: rgba(255,255,255,.82) !important;
    font-size: 13px;
    line-height: 1.45;
    font-weight: 500;
    text-decoration: none;
    margin: 0 0 5px;
  }

  .footer-link-groups a:hover,
  .footer-link-groups a:focus {
    color: #FCB42F !important;
    text-decoration: underline;
  }

  @media (max-width: 575px) {
    .footer-link-groups {
      grid-template-columns: 1fr;
      text-align: center;
      width: 100%;
    }
  }

  
</style>

  <!-- coppy-right end -->
</footer>
<!-- footer end -->

<!-- modals start -->
<!-- START products page "quick-view" modal -->
<div class="modal fade theme1 style1" id="quick-view" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-8 mx-auto col-lg-5 mb-5 mb-lg-0 quick-view-image-wrapper">
            <!-- dynamic content -->
          </div>
          <div class="col-lg-7">
            <div class="modal-product-info">
              <div class="product-head"></div>
              <div class="product-body"></div>
              <div class="d-flex mt-30">
                <div class="product-size"></div>
              </div>
              <div class="product-footer"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- END products page "quick-view" modal -->

<!-- review modal -->
<div class="modal fade style2" id="review_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button
          type="button"
          class="close"
          data-dismiss="modal"
          aria-label="Close"
        >
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h5 class="title">
          <i class="fa fa-check"></i> Thank you for your rating!
        </h5>
      </div>
    </div>
  </div>
</div>

<!-- second modal -->
<div class="modal fade style2" id="compare" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button
          type="button"
          class="close"
          data-dismiss="modal"
          aria-label="Close"
        >
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h5 class="title">
          <i class="fa fa-check"></i> Product added to compare.
        </h5>
      </div>
    </div>
  </div>
</div>

<!-- add to cart modal -->

<div class="modal fade style3" id="add-to-cart" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header justify-content-center bg-dark">
        <h5 class="modal-title">Product successfully added to your shopping cart</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row align-items-center">
          <div class="col-md-4 text-center mb-4 mb-md-0">
            <img class="product-image img-fluid" src="<?=$home_directory?>assets/img/product/1.png" width="4508" height="5025" onerror="this.onerror=null;this.src='<?=$home_directory?>assets/img/product/1.png';" alt="Product image">
          </div>
          <div class="col-md-8">
            <h5 class="product-name mb-2"></h5>
            <p class="quick-view-price mb-2"></p>
            <p class="cart-products-count mb-2">Your cart is being updated.</p>
            <p class="mb-0">Cart total: <span class="grand_total">R0.00</span></p>
            <div class="d-flex flex-wrap mt-4">
              <a href="cart" class="btn btn-dark btn--md mr-2 mb-2">Take me to cart</a>
              <a href="checkout" class="btn btn-primary btn--md mb-2">Checkout</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- modals end -->

<?php if (!empty($showSubscribeOffer) && empty($_SESSION['user_id'])): ?>
<style>
  .subscribe-offer-modal .modal-dialog { max-width: 460px; }
  .subscribe-offer-modal .modal-content { border: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 22px 55px rgba(45, 23, 57, .28); }
  .subscribe-offer-modal .offer-head { background: #2d1739; color: #fff; padding: 28px 30px 22px; position: relative; }
  .subscribe-offer-modal .offer-head h3 { color: #fcb42f; font-size: 28px; line-height: 1.15; margin-bottom: 8px; }
  .subscribe-offer-modal .offer-head p { color: #f8ecff; font-size: 15px; line-height: 1.55; max-width: 360px; }
  .subscribe-offer-modal .offer-badge { background: #fcb42f; color: #2d1739; display: inline-block; font-weight: 900; letter-spacing: .02em; padding: 6px 10px; border-radius: 999px; margin-bottom: 12px; }
  .subscribe-offer-modal .close { position: absolute; right: 14px; top: 10px; opacity: .9; }
  .subscribe-offer-modal .offer-body { background: #fffaf2; padding: 26px 30px 30px; }
  .subscribe-offer-modal label { color: #2d1739; font-weight: 800; }
  .subscribe-offer-modal .form-control { background: #fff; border: 1px solid #dfd2c4; border-radius: 6px; min-height: 46px; padding: 10px 12px; }
  .subscribe-offer-modal .offer-fineprint { color: #6d6270; font-size: 12px; line-height: 1.45; margin: 12px 0 0; }
  .subscribe-offer-modal .coupon-result { background: #fff; border: 1px dashed #5b1178; border-radius: 8px; padding: 16px; margin-top: 16px; display: none; }
  .subscribe-offer-modal .coupon-code { color: #5b1178; font-size: 25px; font-weight: 900; letter-spacing: .04em; }
</style>
<div class="modal fade subscribe-offer-modal" id="subscribe-offer-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="offer-head">
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <span class="offer-badge">R100 off</span>
        <h3>Save R100 on your first subscribed order</h3>
        <p class="mb-0">Join the CandyBird mailing list and get R100 off your order over R500.</p>
      </div>
      <div class="offer-body">
        <form id="subscribe-offer-form">
          <input type="hidden" name="source" value="subscribe_offer">
          <input type="hidden" name="coupon_code" value="SUBSCRIBENOW">
          <div class="form-group">
            <label for="subscribe-offer-email">Email address</label>
            <input type="email" class="form-control" id="subscribe-offer-email" name="email" placeholder="you@example.com" required>
          </div>
          <button type="submit" class="btn btn-dark btn--md">Subscribe</button>
          <button type="button" class="btn btn-link" id="subscribe-offer-dismiss">Maybe later</button>
          <p class="offer-fineprint">New subscribers only. Coupon validity comes from the live coupon sheet.</p>
        </form>
        <div class="coupon-result" id="subscribe-offer-result">
          <p class="mb-1" id="subscribe-offer-message"></p>
          <div class="coupon-code" id="subscribe-offer-code"></div>
          <button type="button" class="btn btn-primary btn-sm mt-2" id="subscribe-offer-copy">Copy code</button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="notification-container" style="position: fixed; top: 10px; right: 10px; z-index: 9999;"></div>

<!-- search-box and overlay start -->
<div class="overlay">
  <div class="scale"></div>
  <form class="search-box" action="products" method="get">
    <input type="text" name="search" id="search-input" placeholder="Search pistachios, almonds, 1kg, chocolate..." autocomplete="off" />
    <button id="close" type="submit">
      <i class="ion-ios-search-strong"></i>
    </button>
    <div id="global-search-results" class="global-search-results" aria-live="polite"></div>
  </form>
  <button class="close"><i class="ion-android-close"></i></button>
</div>

<!-- search-box and overlay end -->
<style>
.global-search-results {
  display: none;
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 12px);
  background: #fff;
  border: 1px solid #e6e1d8;
  border-radius: 6px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.16);
  max-height: 420px;
  overflow-y: auto;
  text-align: left;
  z-index: 10001;
}
.global-search-results.is-visible {
  display: block;
}
.global-search-item,
.global-search-all {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 10px 12px;
  color: #222;
  border-bottom: 1px solid #f1ece4;
}
.global-search-item:hover,
.global-search-all:hover {
  background: #fff8ed;
  color: #6b0099;
}
.global-search-item img {
  width: 52px;
  height: 52px;
  object-fit: cover;
  border-radius: 4px;
  background: #f7f2ea;
}
.global-search-item strong,
.global-search-item small {
  display: block;
}
.global-search-item small {
  color: #6f675e;
}
.global-search-clearance-label {
  background: #d5001f;
  border-radius: 999px;
  color: #fff;
  display: inline-block;
  font-size: 10px;
  font-weight: 800;
  line-height: 1;
  margin-top: 4px;
  padding: 4px 6px;
  text-transform: uppercase;
}
.global-search-price {
  margin-left: auto;
  font-weight: 700;
  white-space: nowrap;
  text-align: right;
}
.global-search-price del {
  color: #8a8178;
  display: block;
  font-size: 12px;
  font-weight: 500;
}
.global-search-price .onsale {
  color: #b42318;
  display: block;
}
.global-search-empty {
  padding: 14px;
  color: #5f574f;
}
@media screen and (max-width: 767px) {
  .global-search-results {
    max-height: 60vh;
  }
  .global-search-price {
    display: none;
  }
}
</style>
    <!--*********************** 
        all js files
     ***********************-->

    <!--****************************************************** 
        jquery,modernizr ,poppe,bootstrap,plugins and main js
     ******************************************************-->

    <script src="<?=$home_directory?>assets/js/vendor/jquery-3.5.1.min.js"></script>
    <script src="<?=$home_directory?>assets/js/vendor/modernizr-3.7.1.min.js"></script>
    <script src="<?=$home_directory?>assets/js/popper.min.js"></script>
    <script src="<?=$home_directory?>assets/js/plugins/jquery-ui.min.js"></script>
    <script src="<?=$home_directory?>assets/js/bootstrap.min.js"></script>
    <script src="<?=$home_directory?>assets/js/plugins/plugins.js"></script>
    <script src="<?=$home_directory?>assets/js/plugins/ajax-contact.js"></script>
    <!-- <script src="<?=$home_directory?>assets/js/plugins/aos.js"></script> -->
    <script src="<?=$home_directory?>assets/js/main.js"></script>



<script>
// Function to add or remove the badge span based on count
function toggleBadge(id, count) {
    var badgeSpan = $("#" + id);

    // Add new badge span if count is greater than 0
    if (count > 0) {
        var newBadge = $('<span>', {
            class: 'badge cbdg1',
            text: count
        });
        badgeSpan.append(newBadge);
    }
}

// Function to update badge counts
function updateBadgeCounts() {
  $.ajax({
      url: "<?=$home_directory?>session_logins.php",
      type: "GET",
      data: {
          getBadgeCounts: true
      },
      dataType: "json",
      success: function (data) {
          // Update badge counts and add/remove badge spans
          toggleBadge("compareBadge", data.compareCount);
          toggleBadge("wishlistBadge", data.wishlistCount);
          toggleBadge("cartBadge", data.cartCount);
          console.log(data);
      },
      error: function (error) {
          console.log("Error fetching badge counts: ", error);
      }
  });
}

function updateModalContent(response) {
    response = response || {};
    response.cart = response.cart || {};
    var productDetails = response.cart.product || {};

    var defaultImageUrl = $('#add-to-cart .product-image').attr('src');

    $('#add-to-cart .modal-title').text(response.success === false ? 'Cart could not be updated' : 'Product successfully added to your shopping cart');
    $('#add-to-cart .product-name').text(productDetails.title || 'Selected product');
    var originalPrice = parseFloat(productDetails.original_price || productDetails.price || 0);
    var discountedPrice = parseFloat(productDetails.discounted_price || productDetails.price || 0);
    var hasDiscount = originalPrice > 0 && discountedPrice > 0 && discountedPrice < originalPrice;
    $('#add-to-cart .quick-view-price').html(
        discountedPrice > 0
            ? (hasDiscount
                ? '<del class="del mr-2">R' + originalPrice.toFixed(2) + '</del><span class="onsale">R' + discountedPrice.toFixed(2) + '</span>'
                : '<span>R' + discountedPrice.toFixed(2) + '</span>')
            : ''
    );
    
    var imageUrl = productDetails.image_url || defaultImageUrl;
    $('#add-to-cart .product-image').attr('src', imageUrl);
    
    $('#add-to-cart .grand_total').text('R' + (response.cart.subtotal || '0.00'));

    $('#add-to-cart .cart-products-count').text(response.success === false ? (response.message || 'Your cart could not be updated.') : (response.cart.item_quantity ? 'There is ' + response.cart.item_quantity + ' item(s) in your cart.' : 'Your cart is being updated.'));
}

function getSheetProduct(productId) {
    var products = window.CANDYBIRD_PRODUCTS || [];
    return products.find(function(product) {
        return String(product.id) === String(productId);
    }) || null;
}

function getSheetProductPrice(product) {
    var price = parseFloat(product.price) || 0;
    var discounted = parseFloat(product.discounted_price);
    var discount = parseFloat(product.discount || product.discount_amount || 0) || 0;

    if (!isNaN(discounted) && discounted > 0) {
        return discounted;
    }

    if (discount > 0) {
        return price - discount;
    }

    return price;
}

function getSheetProductImages(product) {
    var imageValue = product.img_url || product.image_url || product.image_urls || product.image || '';
    var images = String(imageValue).split(',').map(function(image) {
        return image.trim();
    }).filter(Boolean);

    return images.length ? images : ['<?=$home_directory?>assets/img/product/1.png'];
}

function buildSheetCartResponse(productId, quantity, response) {
    var product = getSheetProduct(productId);
    response = response || {};
    response.cart = response.cart || {};

    if (product) {
        var productName = product.name || product.title || '';
        var productSize = product.size || product.weight || '';
        var title = productName + (productSize ? ' ' + productSize : '');
        var originalPrice = parseFloat(product.price || 0) || 0;
        var discountedPrice = getSheetProductPrice(product);
        response.cart.product = $.extend({}, response.cart.product || {}, {
            title: title,
            price: discountedPrice.toFixed(2),
            original_price: (originalPrice > 0 ? originalPrice : discountedPrice).toFixed(2),
            discounted_price: discountedPrice.toFixed(2),
            has_discount: originalPrice > 0 && discountedPrice < originalPrice,
            image_url: getSheetProductImages(product)[0]
        });
    }

    response.cart.item_quantity = response.cart.item_quantity || (response.success === false ? 0 : (quantity || 1));
    response.cart.subtotal = response.cart.subtotal || (response.success === false ? '0.00' : (product ? (getSheetProductPrice(product) * (quantity || 1)).toFixed(2) : '0.00'));
    response.message = response.message || (response.success === false ? 'This item could not be added to cart.' : 'Product added to cart');
    return response;
}

function getLiveProductId($element) {
    var clearanceId = $element.attr('data-clearance-id') || $element.data('clearance-id');
    if (clearanceId) {
        return String(clearanceId).indexOf('CLR:') === 0 ? String(clearanceId) : 'CLR:' + clearanceId;
    }
    return $element.attr('data-product-id') || $element.data('product-id');
}

function getAddToCartQuantity($button) {
    var selectors = [
        '.product-count .add-to-cart-quantity',
        '.single-product-info .add-to-cart-quantity',
        '.modal-product-info .add-to-cart-quantity',
        '.product-card .add-to-cart-quantity',
        '.product-list .add-to-cart-quantity',
        '.cart-plus-minus .add-to-cart-quantity',
        'tr .add-to-cart-quantity'
    ];
    var $input = $button.closest('.product-count').find('.add-to-cart-quantity').first();

    if (!$input.length) {
        for (var i = 0; i < selectors.length; i++) {
            $input = $button.closest('.single-product-info, .modal-product-info, .product-card, .product-list, tr, .product-inner').find(selectors[i]).first();
            if ($input.length) {
                break;
            }
        }
    }

    if (!$input.length && $button.attr('id') === 'add-to-cart-btn') {
        $input = $('.single-product-info .add-to-cart-quantity').first();
    }

    var rawQuantity = $input.length ? $input.val() : ($button.attr('data-quantity') || $button.data('quantity') || 1);
    var quantity = parseInt(rawQuantity, 10);
    var max = $input.length ? parseInt($input.attr('max'), 10) : NaN;

    if (isNaN(quantity) || quantity < 1) {
        quantity = 1;
    }
    if (!isNaN(max) && max > 0 && quantity > max) {
        quantity = max;
        $input.val(max);
    }

    return quantity;
}

$(document).ready(function () {

  updateBadgeCounts();

  // Handle click on "Add to Wishlist" icon
  $('body').on('click', '.add-to-cart', function (e) {
      e.preventDefault();


      // Retrieve product ID from data attribute
      var productId = getLiveProductId($(this));
      var clearanceId = $(this).attr('data-clearance-id') || $(this).data('clearance-id') || '';

      logAction('Clicked on add-to-cart (product id: '+productId+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

      var finalQuantity = getAddToCartQuantity($(this));

      // console.log('Product ID:', productId);
      // console.log('Quantity:', finalQuantity);

      // Perform AJAX request
      $.ajax({
          type: 'POST',
          url: 'add_to_cart.php',
          dataType: 'json',
          data: {
              productId: productId,
              clearanceId: clearanceId,
              quantity: finalQuantity
          },
          success: function (response) {
              response = buildSheetCartResponse(productId, finalQuantity, response);

              if (response.offcanvascart) {
                  $('#offcanvas-cart .minicart-product-list').html(response.offcanvascart);
              }

              updateBadgeCounts();
              $('#offcanvas-cart .sub-total .amount').text(response.cart.subtotal || '0.00');

              // Handle the response from the server (optional)
              // console.log(response.cart.subtotal);

              // Access product details
              var productDetails = response.cart.product;
              // console.log(productDetails.title);
              // console.log(productDetails.price);
              // console.log(productDetails.image_url);
              
              // Update the modal content with the product details and cart summary
              updateModalContent(response);

              // You can update the UI or show a success message here
              showNotification(response.success, response.message);

              logAction('Added item '+productId+' to cart', 'from page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

          },
          error: function (error) {
              logAction('Could not add item '+productId+' to cart from page ' + window.location.href, 'Error: ' + error, '<?=$userId?>', '<?=$guestIdentifier?>');
              updateModalContent(buildSheetCartResponse(productId, finalQuantity, {
                  success: false,
                  message: 'This item could not be added to cart right now.'
              }));
              showNotification(false, 'This item could not be added to cart right now.');
              console.error('Error:', error);
          }
      });
  });


  // Handle click on "Add to Wishlist" icon
  $('body').on('click', '.add-to-wishlist', function (e) {
      e.preventDefault();
      // Retrieve product ID from data attribute
      var productId = getLiveProductId($(this));
      // console.log('Product ID:', productId); // Log to console for debugging
      logAction('Clicked on add-to-wishlist (product id: '+productId+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

      // Perform AJAX request
      $.ajax({
          type: 'POST',
          url: 'add_to_wishlist.php', // Specify the path to your PHP script
          dataType: 'json',
          data: {
              productId: productId
          },
          success: function (response) {
              // Handle the response from the server (optional)
              // console.log(response);
              updateBadgeCounts();
              // You can update the UI or show a success message here
              showNotification(response.success, response.message);
              logAction('Added item '+productId+' to wishlist', 'from page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
          },
          error: function (error) {
              // Handle errors (optional)
              logAction('Could not add item '+productId+' to wishlist from page ' + window.location.href, 'Error: ' + error, '<?=$userId?>', '<?=$guestIdentifier?>');
              showNotification(false, 'This item could not be added to wishlist right now.');
              console.error('Error:', error);
          }
      });
  });

  $('body').on('click', '.add-to-compare', function (e) {
      e.preventDefault();
      // Retrieve product ID from data attribute
      var productId = getLiveProductId($(this));
      // console.log('Product ID:', productId); // Log to console for debugging

      logAction('Clicked on add-to-compare (product id: '+productId+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
      // Perform AJAX request
      $.ajax({
          type: 'POST',
          url: 'add_to_compare.php', // Specify the path to your PHP script
          dataType: 'json',
          data: {
              productId: productId
          },
          success: function (response) {
              // Handle the response from the server (optional)
              // console.log(response);
              updateBadgeCounts();
              // You can update the UI or show a success message here
              showNotification(response.success, response.message);
              logAction('Added item '+productId+' to compare', 'from page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
          },
          error: function (error) {
            logAction('Could not add item '+productId+' to compare from page ' + window.location.href, 'Error: ' + error, '<?=$userId?>', '<?=$guestIdentifier?>');
              // Handle errors (optional)
              showNotification(false, 'This item could not be added to compare right now.');
              console.error('Error:', error);
          }
      });
  });

  $('body').on('click', '.removeFromWishlist', function (event) {
      event.preventDefault();
      var productIdWishlist = $(this).data('product-id');
      removeFromWishlist(productIdWishlist);
      removeWishlistItemFromDOM($(this));
      updateBadgeCounts();
      logAction('Clicked on removeFromWishlist (product id: '+productIdWishlist+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
  });

  function removeWishlistItemFromDOM(element) {
      // Remove the closest 'li' if available, otherwise, remove the closest 'tr'
      var closestLi = element.closest('li');
      var closestTr = element.closest('tr');
      
      if (closestLi.length > 0) {
          closestLi.remove();
      } else if (closestTr.length > 0) {
          closestTr.remove();
      }
  }

  function removeFromWishlist(productIdWishlist) {
      // Use AJAX to send a request to remove the item from the wishlist in the database
      $.ajax({
          type: 'POST',
          url: 'remove_from_wishlist.php',
          data: { product_id: productIdWishlist },
          success: function (data) {
              // Handle the response, e.g., show a notification or update the UI
              // console.log(data);
              logAction('Removed item '+productIdWishlist+' from wishlist', 'from page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
          },
          error: function (error) {
              console.error('Error:', error);
              logAction('Could not remove item '+productIdWishlist+' from wishlist from page ' + window.location.href, 'Error: ' + error, '<?=$userId?>', '<?=$guestIdentifier?>');
          }
      });
  }

  //admin panel, updating order quantity
  $('body').on('click', '.update-order-quantity', function (event) {
    console.log('clicked on update order quantity');
      event.preventDefault();
      var orderId = $(this).data('order-id');
      var productIdCart = $(this).data('product-id');
      var input_quantity = $(this).closest('tr').find('.quantity').val();
      updateOrder(productIdCart, input_quantity, orderId);
  });

  $('body').on('click', '.update-cart-quantity', function (event) {
    // console.log('response.subtotal');
      event.preventDefault();
      var productIdCart = $(this).data('product-id');
      var input_quantity = $(this).closest('tr').find('.quantity').val();
      logAction('Clicked on update-cart-quantity (product id: '+productIdCart+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
      updateCart(productIdCart, input_quantity);
  });

  $('body').on('click', '.removeFromCart', function (event) {
      event.preventDefault();
      var productIdCart = $(this).data('product-id');
      var quantity = $(this).data('quantity');
      var input_quantity = $(this).closest('tr').find('.add-to-cart-quantity').val();

      // Use a conditional statement to prioritize quantity from data attribute, or fallback to input value
      var finalQuantity = quantity !== undefined ? quantity : input_quantity;


      logAction('Clicked on removeFromCart (product id: '+productIdCart+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
      removeFromCart(productIdCart, finalQuantity);
      removeCartItemFromDOM($(this));
      updateBadgeCounts();
  });

  //for admin panel
  $('body').on('click', '.removeFromOrder', function (event) {
      event.preventDefault();
      var productIdCart = $(this).data('product-id');
      var orderId = $(this).data('order-id');
      var quantity = $(this).data('quantity');
      var input_quantity = $(this).closest('tr').find('.add-to-order-quantity').val();

      // Use a conditional statement to prioritize quantity from data attribute, or fallback to input value
      var finalQuantity = quantity !== undefined ? quantity : input_quantity;

      removeFromOrder(productIdCart, finalQuantity, orderId);
      removeCartItemFromDOM($(this));
  });

  function removeCartItemFromDOM(element) {
      // Remove the closest 'li' if available, otherwise, remove the closest 'tr'
      var closestLi = element.closest('li');
      var closestTr = element.closest('tr');
      
      if (closestLi.length > 0) {
          closestLi.remove();
      } else if (closestTr.length > 0) {
          closestTr.remove();
      }
  }

  function removeFromCart(productIdCart, quantity) {
      // Use AJAX to send a request to remove the item from the Cart in the database
      $.ajax({
          type: 'POST',
          url: 'remove_from_cart.php',
          data: { product_id: productIdCart, quantity: quantity },
          success: function (response) {
              // Handle the response, e.g., show a notification or update the UI
              // console.log(response.subtotal);
              // Parse the subtotal string to a float
              var subtotal = response.subtotal;

              $('#offcanvas-cart .sub-total .amount').text(subtotal); // Display the formatted subtotal
              // console.error('Invalid subtotal:', response);
              
              logAction('Removed item '+productIdCart+' from cart', 'from page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
              updateBadgeCounts();
          },
          error: function (error) {
              console.error('Error:', error);
              logAction('Could not remove item '+productIdCart+' from cart from page ' + window.location.href, 'Error: ' + error, '<?=$userId?>', '<?=$guestIdentifier?>');
          }
      });
  }

  //for admin panel
  function removeFromOrder(productIdCart, quantity, orderId) {
      // Use AJAX to send a request to remove the item from the Cart in the database
      $.ajax({
          type: 'POST',
          url: 'remove_from_order.php',
          data: { product_id: productIdCart, quantity: quantity, orderId: orderId },
          success: function (response) {
          },
          error: function (error) {
              console.error('Error:', error);
          }
      });
  }

  function updateCart(productIdCart, quantity) {
      // Use AJAX to send a request to remove the item from the Cart in the database
      $.ajax({
          type: 'POST',
          url: 'update_cart.php',
          data: { product_id: productIdCart, quantity: quantity },
          success: function (response) {
              // Handle the response, e.g., show a notification or update the UI
              // console.log(response.subtotal);
              // Parse the subtotal string to a float
              var subtotal = response.subtotal;

              $('#offcanvas-cart .sub-total .amount').text(subtotal); // Display the formatted subtotal
              // console.error('Invalid subtotal:', response);

              logAction('Updated item '+productIdCart+' in cart', 'from page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
              
          },
          error: function (error) {
              console.error('Error:', error);
              logAction('Could not update item '+productIdCart+' in cart from page ' + window.location.href, 'Error: ' + error, '<?=$userId?>', '<?=$guestIdentifier?>');
          }
      });
  }

  //admin panel, updating order quantity
  function updateOrder(productIdCart, quantity, orderId) {
      // Use AJAX to send a request to remove the item from the Cart in the database
      $.ajax({
          type: 'POST',
          url: 'update_order.php',
          data: { product_id: productIdCart, quantity: quantity , orderId: orderId },
          success: function (response) {
              // window.location.href = "?order_id="+orderId;
              console.log(response);
              
          },
          error: function (error) {
              console.error('Error:', error);
          }
      });
  }


});
</script>


<script>



$(document).ready(function () {

    (function(){
      var $modal = $('#subscribe-offer-modal');
      if (!$modal.length || !window.localStorage || !window.sessionStorage) return;

      var now = Date.now();
      var dismissedUntil = parseInt(localStorage.getItem('cb_subscribe_offer_dismissed_until') || '0', 10);
      var subscribedUntil = parseInt(localStorage.getItem('cb_subscribe_offer_subscribed_until') || '0', 10);
      var seenThisSession = sessionStorage.getItem('cb_subscribe_offer_seen') === '1';

      if (seenThisSession || dismissedUntil > now || subscribedUntil > now) return;

      setTimeout(function(){
        sessionStorage.setItem('cb_subscribe_offer_seen', '1');
        $modal.modal('show');
      }, 4500);

      function dismissForDays(days) {
        localStorage.setItem('cb_subscribe_offer_dismissed_until', String(now + (days * 24 * 60 * 60 * 1000)));
      }

      $('#subscribe-offer-dismiss').on('click', function(){
        dismissForDays(14);
        $modal.modal('hide');
      });

      $modal.on('hidden.bs.modal', function(){
        if (!localStorage.getItem('cb_subscribe_offer_subscribed_until')) {
          dismissForDays(7);
        }
      });

      $('#subscribe-offer-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        $button.prop('disabled', true).text('Subscribing...');

        $.ajax({
          type: 'POST',
          url: 'subscribe.inc.php',
          data: $form.serialize(),
          dataType: 'json',
          success: function(response) {
            if (typeof showNotification === 'function') {
              showNotification(response.success, response.message);
            }

            if (response.success) {
              localStorage.setItem('cb_subscribe_offer_subscribed_until', String(Date.now() + (180 * 24 * 60 * 60 * 1000)));
              $('#subscribe-offer-message').text(response.coupon_message || response.message);
              $('#subscribe-offer-code').text(response.coupon_code || '');
              $('#subscribe-offer-copy').toggle(!!response.coupon_code);
              $('#subscribe-offer-result').show();
              if (!response.coupon_code) {
                setTimeout(function(){ $modal.modal('hide'); }, 1800);
              }
            }
          },
          error: function() {
            if (typeof showNotification === 'function') {
              showNotification(false, 'Subscription could not be saved right now.');
            }
          },
          complete: function() {
            $button.prop('disabled', false).text('Subscribe');
          }
        });
      });

      $('#subscribe-offer-copy').on('click', function(){
        var code = $('#subscribe-offer-code').text();
        if (!code) return;
        if (navigator.clipboard) {
          navigator.clipboard.writeText(code);
        }
        if (typeof showNotification === 'function') {
          showNotification(true, 'Coupon code copied.');
        }
      });
    })();

    $('#subscribe_form').on('submit', function(e){
      e.preventDefault();
        var email = $('#subscribe_email').val();
        logAction('Submitted form to subscribe ('+email+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

        // AJAX Request using jQuery
        $.ajax({
            type: 'POST',
            url: ' https://www.candybird.co.za/subscribe.inc.php',
            data: { email: email },
            dataType: 'json',
            success: function (response) {
                //clear form field
                $('#subscribe_email').val('');
                // Handle the JSON response
                showNotification(response.success, response.message);
            },
            error: function (error) {
                console.log('Error: Unable to process your request.');
                logAction('Could not subscribe ('+email+') from page ' + window.location.href, 'Error: '+error, '<?=$userId?>', '<?=$guestIdentifier?>');
            }
        });
    });

    var searchToggle = $(".search-toggle"),
        closeA = $(".scale"),
        closeB = $(".searching button"),
        cBody = $("body"),
        closeScale = closeA.add(closeB);

    if (searchToggle.length > 0) {
      searchToggle.on("click", function () {
        cBody.toggleClass("open");
        setTimeout(function() {
          $('#search-input').trigger('focus').select();
        }, 80);
        logAction('Clicked on search toggler', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
        return false;
      });
    }

    if (closeScale.length > 0) {
      closeScale.on("click", function () {
        cBody.removeClass("open");
        return false;
      });
    }

    var searchTimer = null;
    var $searchInput = $('#search-input');
    var $searchResults = $('#global-search-results');

    function escapeHtml(value) {
      return String(value || '').replace(/[&<>"']/g, function(match) {
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        }[match];
      });
    }

    function renderSearchResults(query, response) {
      var results = response && response.results ? response.results : [];
      if (!query || query.length < 2) {
        $searchResults.removeClass('is-visible').empty();
        return;
      }

      if (!results.length) {
        $searchResults
          .html('<div class="global-search-empty">No products found. Try a simpler word like pistachio, almond, salted, chocolate, 500g, or 1kg.</div>')
          .addClass('is-visible');
        return;
      }

      var html = results.map(function(item) {
        var title = [item.name, item.size].filter(Boolean).join(' ');
        var originalPrice = parseFloat(item.original_price || item.price || 0) || 0;
        var finalPrice = parseFloat(item.discounted_price || item.price || 0) || 0;
        var hasDiscount = originalPrice > 0 && finalPrice > 0 && finalPrice < originalPrice;
        var isClearance = String(item.is_clearance || '').toLowerCase() === 'yes';
        var priceHtml = hasDiscount
          ? '<del>R' + originalPrice.toFixed(2) + '</del><span class="onsale">R' + finalPrice.toFixed(2) + '</span>'
          : 'R' + finalPrice.toFixed(2);
        var metaHtml = '<small>' + escapeHtml(item.category || '') + '</small>' +
          (isClearance ? '<span class="global-search-clearance-label">Clearance</span>' : '');
        return '<a class="global-search-item" href="' + escapeHtml(item.url) + '">' +
          '<img src="' + escapeHtml(item.image_url || 'assets/img/product/1.png') + '" onerror="this.onerror=null;this.src=\'assets/img/product/1.png\';" alt="">' +
          '<span><strong>' + escapeHtml(title) + '</strong>' + metaHtml + '</span>' +
          '<span class="global-search-price">' + priceHtml + '</span>' +
        '</a>';
      }).join('');

      html += '<a class="global-search-all" href="products?search=' + encodeURIComponent(query) + '">See all results for "' + escapeHtml(query) + '"</a>';
      $searchResults.html(html).addClass('is-visible');
    }

    $searchInput.on('input', function() {
      var query = $(this).val().trim();
      clearTimeout(searchTimer);

      if (query.length < 2) {
        $searchResults.removeClass('is-visible').empty();
        return;
      }

      searchTimer = setTimeout(function() {
        $.ajax({
          url: 'search.php',
          method: 'GET',
          dataType: 'json',
          data: { query: query, limit: 8 },
          success: function(response) {
            renderSearchResults(query, response);
          },
          error: function() {
            $searchResults
              .html('<div class="global-search-empty">Search is taking longer than usual. Press enter to view results.</div>')
              .addClass('is-visible');
          }
        });
      }, 180);
    });

    $('.search-box').on('submit', function(e) {
      var query = $searchInput.val().trim();
      if (!query) {
        e.preventDefault();
        $searchInput.focus();
      } else if (typeof logAction === 'function') {
        logAction('UX search submitted', 'Query: ' + query + ' | From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
      }
    });

    $('body').on('click', '.global-search-item, .global-search-all', function() {
      if (typeof logAction === 'function') {
        logAction('UX search result click', 'Query: ' + ($searchInput.val() || '') + ' | To: ' + ($(this).attr('href') || ''), '<?=$userId?>', '<?=$guestIdentifier?>');
      }
    });

    /*---------------------------------
            Off Canvas toggler Function
        -----------------------------------*/

    var $offCanvasToggle = $(".offcanvas-toggle"),
        $offCanvas = $(".offcanvas"),
        $offCanvasOverlay = $(".offcanvas-overlay"),
        $mobileMenuToggle = $(".mobile-menu-toggle");
    $offCanvasToggle.on("click", function (e) {
      e.preventDefault();
      var $this = $(this),
          $target = $this.attr("href");
      $("body").addClass("offcanvas-open");
      $($target).addClass("offcanvas-open");
      $offCanvasOverlay.fadeIn();

      logAction('Clicked on cart toggler', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

      if ($this.parent().hasClass("mobile-menu-toggle")) {
        $this.addClass("close");
      }
    });
    $(".offcanvas-close, .offcanvas-overlay").on("click", function (e) {
      e.preventDefault();
      $("body").removeClass("offcanvas-open");
      $offCanvas.removeClass("offcanvas-open");
      $offCanvasOverlay.fadeOut();
      $mobileMenuToggle.find("a").removeClass("close");
      logAction('Clicked on close off-canvas cart', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
    });


    // $(".search-box #close").on("click", function () {
    //     var searchQuery = $(".search-box input").val();
    //     if (searchQuery !== "") {
    //         // Make an AJAX request to the products file
    //         $.ajax({
    //             url: "products.php",
    //             type: "GET",
    //             data: { query: searchQuery },
    //             dataType: "json", // Expect JSON response
    //             success: function (response) {
    //                 // Check the status in the response
    //                 if (response.status === "success") {
    //                      // Construct the URL with the product IDs and redirect
    //                     var productIds = response.results.map(result => result.id).join(",");
    //                     window.location.href = "products?search=" + encodeURIComponent(searchQuery);
    //                 } else {
    //                     // Handle the error
    //                     console.error(response.message);
    //                     // You can also redirect with an error parameter if needed
    //                     window.location.href = "products?error=" + encodeURIComponent(response.message);
    //                 }
    //             },
    //             error: function (xhr, status, error) {
    //                 // Handle AJAX error
    //                 console.error("AJAX Error:", status, error);
    //             }
    //         });
    //     }
    //     return false;
    // });
});


// Function to show Bootstrap notification
function showNotification(success, message) {
    var notificationContainer = $('#notification-container');
    var notificationClass = success ? 'alert-success' : 'alert-danger';
    var notificationStyle = success ? 'background-color: #FCB42F; color: #6e4cb2;' : 'background-color: #6e4cb2; color: #FCB42F;';

    var notification = $('<div style="transition: background 0.3s, color 0.3s, box-shadow 0.3s; box-shadow: 0 0 5px rgba(206, 189, 136, 0.5);' + notificationStyle + '" class="alert ' + notificationClass + ' alert-dismissible fade show" role="alert">' +
        message +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>');

    notificationContainer.append(notification);

    // Automatically close the notification after a few seconds
    setTimeout(function () {
        notification.alert('close');
    }, 5000);
}







// Function to initialize Slick slider
function initializeSlick() {
    $(".product-sync-init").slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: true,
        draggable: false,
        arrows: false,
        dots: false,
        fade: true,
        asNavFor: ".product-sync-nav"
    });
    $(".product-sync-nav").slick({
        dots: false,
        arrows: false,
        infinite: true,
        prevArrow: '<button class="slick-prev" data-slider-id="homepage slider prev"><i class="fas fa-arrow-left"></i></button>',
        nextArrow: '<button class="slick-next" data-slider-id="homepage slider next"><i class="fas fa-arrow-right"></i></button>',
        slidesToShow: 4,
        slidesToScroll: 1,
        asNavFor: ".product-sync-init",
        focusOnSelect: true,
        draggable: false
    });
}


</script>


<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<script>
$(document).ready(function() {
    if ($('.manageProductTables').length) {
        $('.manageProductTables').DataTable({
            "ordering": true,  // Enable sorting
            "paging": true,    // Enable pagination
            "searching": true  // Enable search
        });
    }
    

    // This is a custom function to make the "order status" (admin manage orders page) sortable by labels. I did this because currently there's a select form in that cell and that is why the jquery sorter doesn't sort that field. So I am manually inputting it.
    $.fn.dataTable.ext.order['status-sort'] = function (settings, col) {
        // Define status order
        var statusOrder = {
            'Cancelled': 1,
            'Pending': 2,
            'Processing': 3,
            'Packing': 4,
            'Shipped': 5,
            'Complete': 6
        };
        
        return this.api().column(col, {order: 'index'}).nodes().map(function (td, i) {
            // Get the text from the data attribute
            return statusOrder[$(td).data('order-status')];
        });
    };

    if ($('.manageOrderTables').length) {
        $('.manageOrderTables').DataTable({
            "ordering": true,     // Enable sorting
            "paging": true,       // Enable pagination
            "searching": true,    // Enable search
            "order": [
                [8, 'asc'], // Order by status (assuming status is in column index 8)
                // [9, 'desc'] // Then order by date (assuming date is in column index 9)
            ],
            "columnDefs": [
                { 
                    "orderDataType": "status-sort", 
                    "targets": 8 // Assuming the status column is at index 8
                },
                { 
                    "orderable": false, 
                    "targets": [10] // Disable ordering for the actions column
                }
            ]


        });
    }


    $('#userTable').DataTable({
        "ajax": "get_users_info.inc.php", // Replace with the actual URL to your PHP script
        "columns": [
            { 
                "data": "id",
                "render": function(data, type, row) {
                    return `<a href="users?id=${data}">${data}</a>`;
                }
            },
            { 
                "data": "username",
                "render": function(data, type, row) {
                    return `<a href="users?id=${row.id}">${row.username}</a>`;
                }
            },
            { 
                "data": "email",
                "render": function(data, type, row) {
                    return `<a href="users?id=${row.id}">${row.email}</a>`;
                }
            },
            { 
                "data": "last_login",
                "render": function(data, type, row) {
                    return data ? new Date(data).toLocaleString() : 'Never logged in';
                }
            },
            { "data": "order_count" },
            { 
                "data": "cart_count",
                "render": function(data, type, row) {
                    if (row.cart_product_ids) {
                        const productIds = row.cart_product_ids.split(',');
                        const productLinks = productIds.map(id => `<a href="product?id=${id}">${id}</a>`).join(', ');
                        return `${data}<br>(Items: ${productLinks})`;
                    } else {
                        return `No items in cart`;
                    }
                }
            },
            { 
                "data": "wishlist_count",
                "render": function(data, type, row) {
                    if (row.wishlist_product_ids) {
                        const productIds = row.wishlist_product_ids.split(',');
                        const productLinks = productIds.map(id => `<a href="product?id=${id}">${id}</a>`).join(', ');
                        return `${data}<br>(Items: ${productLinks})`;
                    } else {
                        return `No items in wishlist`;
                    }
                }
            },
            { 
                "data": "compare_count",
                "render": function(data, type, row) {
                    if (row.compare_product_ids) {
                        const productIds = row.compare_product_ids.split(',');
                        const productLinks = productIds.map(id => `<a href="product?id=${id}">${id}</a>`).join(', ');
                        return `${data}<br>(Items: ${productLinks})`;
                    } else {
                        return `No items in compare list`;
                    }
                }
            },
            { "data": "review_count" },
            { "data": "comment_count" },
            { 
                "data": "is_subscribed",
                "render": function(data, type, row) {
                    return data == 1 ? 'Yes' : 'No';
                }
            },
            { "data": "status" },
            { "data": "created_at" },
            { 
                "data": "profile_picture",
                "render": function(data, type, row) {
                    return data ? `<img src="${data}" alt="Profile Picture" width="50" height="50">` : '';
                }
            },
        ],
        "responsive": true // Enable responsive extension
    });
});
</script>


<!-- DateTime Picker JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
<script>
  $(function() {
      $('#scheduled_at').datetimepicker({
          dateFormat: 'yy-mm-dd',
          timeFormat: 'HH:mm:ss'
      });
  });
</script>


<script>

// document.addEventListener('DOMContentLoaded', function() {
//     var guestIdentifier = '<?= $guestIdentifier ?>'; // Replace this with actual guest identifier logic
//     var userId = '<?= $userId ?>'; // Replace this with actual user ID logic

//     function logAction(action, details = '', userId = null, guestIdentifier = '') {
//         var xhr = new XMLHttpRequest();
//         xhr.open('POST', 'log_action.php', true);
//         xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
//         xhr.onreadystatechange = function () {
//             if (xhr.readyState === 4 && xhr.status === 200) {
//                 console.log('Action logged:', action);
//             } else if (xhr.readyState === 4) {
//                 console.error('Error logging action:', xhr.statusText);
//             }
//         };

//         var data = 'action=' + encodeURIComponent(action) +
//                    '&details=' + encodeURIComponent(details) +
//                    '&user_id=' + encodeURIComponent(userId) +
//                    '&guest_identifier=' + encodeURIComponent(guestIdentifier);

//         xhr.send(data);
//     }
// });

function logAction(action, details = '', userId = null, guestIdentifier = '') {
    var endpoint = '<?=$home_directory?>log_action.php';
    if (navigator.sendBeacon && window.FormData) {
        var payload = new FormData();
        payload.append('action', action);
        payload.append('details', details);
        payload.append('user_id', userId || '');
        payload.append('guest_identifier', guestIdentifier || '');
        if (navigator.sendBeacon(endpoint, payload)) {
            return;
        }
    }
    $.ajax({
        url: endpoint,
        method: 'POST',
        data: {
            action: action,
            details: details,
            user_id: userId,
            guest_identifier: guestIdentifier
        },
        success: function(response) {
            // console.log('Action logged:', action);
        },
        error: function(xhr, status, error) {
            // console.error('Error logging action:', error);
        }
    });
}

(function() {
    if (window.CANDYBIRD_LIGHT_ANALYTICS === false) return;
    var userId = <?=json_encode($userId ?? null)?>;
    var guestIdentifier = <?=json_encode($guestIdentifier ?? '')?>;
    var currentSessionId = <?=json_encode($current_session_id ?? null)?>;
    var pageUrl = window.location.href;
    var maxScrollLogged = 0;
    var scrollMilestones = [50, 100];
    var lastUxLogAt = 0;

    function logUx(action, details) {
        var now = Date.now();
        if (now - lastUxLogAt < 1200) return;
        lastUxLogAt = now;
        logAction(action, details, userId, guestIdentifier);
    }

    function pagePath(href) {
        try {
            var parsed = new URL(href, window.location.origin);
            return parsed.pathname + parsed.search;
        } catch (e) {
            return href || '';
        }
    }

    function sendHeartbeat() {
        if (!currentSessionId) return;
        $.ajax({
            url: '<?=$home_directory?>update_end_time.php',
            method: 'POST',
            data: { session_id: currentSessionId }
        });
    }

    setTimeout(sendHeartbeat, 8000);
    setInterval(sendHeartbeat, 180000);

    $(window).on('scroll', function() {
        var scrollable = Math.max(1, $(document).height() - $(window).height());
        var percent = Math.min(100, Math.round(($(window).scrollTop() / scrollable) * 100));
        scrollMilestones.forEach(function(mark) {
            if (percent >= mark && maxScrollLogged < mark) {
                maxScrollLogged = mark;
                logUx('UX scroll depth ' + mark + '%', 'Page: ' + pageUrl);
            }
        });
    });

    $('body').on('click', 'a[href]', function() {
        var $link = $(this);
        var href = $link.attr('href') || '';
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
        var text = $.trim($link.text()).replace(/\s+/g, ' ').slice(0, 80);
        var classes = String($link.attr('class') || '');
        var type = 'UX link click';
        if (href.indexOf('product') !== -1 || classes.indexOf('product') !== -1) {
            type = 'UX product click';
        } else if (href.indexOf('category=') !== -1 || classes.indexOf('category') !== -1 || classes.indexOf('navmenu') !== -1) {
            type = 'UX category click';
        }
        logUx(type, 'From: ' + pagePath(pageUrl) + ' | To: ' + pagePath(href) + ' | Text: ' + text);
    });
})();

$('body').on('click', '.print_recipe', function(event) {
    event.preventDefault();
    window.print();
    logAction('Clicked on print recipe', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
});

$('body').on('click', '.list-view-products', function(event) {
    logAction('Clicked on list-view', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
});

$('body').on('click', '.grid-view-products', function(event) {
    logAction('Clicked on grid-view', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
});


$('body').on('click', '.share-link-click', function(event) {
    // Prevent the default action (navigation) for a short period
    event.preventDefault();

    logAction('Clicked on share link ('+$(event.currentTarget).attr('href')+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

    window.location.href = $(event.currentTarget).attr('href');
});

$('body').on('click', '.social-link-click', function(event) {
    // Prevent the default action (navigation) for a short period
    event.preventDefault();

    logAction('Clicked on social link ('+$(event.currentTarget).attr('href')+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

    window.location.href = $(event.currentTarget).attr('href');
});

$('body').on('click', '.navmenu-click', function(event) {
    // Prevent the default action (navigation) for a short period
    event.preventDefault();

    logAction('Clicked on link from nav menu ('+$(event.currentTarget).attr('href')+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

    window.location.href = $(event.currentTarget).attr('href');
});

$('body').on('click', '.navmenu-click-mobile', function(event) {
    // Prevent the default action (navigation) for a short period
    event.preventDefault();

    logAction('Clicked on link from nav menu on mobile ('+$(event.currentTarget).attr('href')+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

    window.location.href = $(event.currentTarget).attr('href');
});


// // Example usage:
// $(document).ready(function() {
//     var guestIdentifier = <?=$guestIdentifier?>; // Replace this with actual guest identifier logic
//     var userId = <?=$userId?>; // Replace this with actual guest identifier logic
    
//     // Log a button click action
//     $('body').on('click', '#myButton', function() {
//         logAction('button_click', 'User clicked the button', userId, guestIdentifier);
//     });

//     // Log a page load action from Jquery
//     logAction('page_load', 'User loaded the page', userId, guestIdentifier);
// });


    $('body').on('click', '.slick-prev, .slick-next', function() {
        var sliderId = $(this).data('slider-id');
        logAction('Clicked on slider next/prev button, slider ID: ' + sliderId, 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
    });
</script>
<script id="merchantWidgetScript" src="https://www.gstatic.com/shopping/merchant/merchantwidget.js" defer></script>
<script>
  (function() {
    var merchantWidgetScript = document.getElementById('merchantWidgetScript');
    if (!merchantWidgetScript) return;

    merchantWidgetScript.addEventListener('load', function() {
      if (!window.merchantwidget || typeof window.merchantwidget.start !== 'function') return;
      window.merchantwidget.start({
        merchant_id: 5312147848,
        position: 'BOTTOM_RIGHT',
        region: 'ZA'
      });
    });
  })();
</script>
    <!-- Use the minified version files listed below for better performance and remove the files listed above -->

    <!--*************************** 
          Minified  js 
     ***************************-->

    <!--*********************************** 
         vendor,plugins and main js
      ***********************************-->

    <!-- <script src="<?=$home_directory?>assets/js/vendor/vendor.min.js"></script>
    <script src="<?=$home_directory?>assets/js/plugins/plugins.min.js"></script>
    <script src="<?=$home_directory?>assets/js/main.js"></script> -->



</body>

</html>
