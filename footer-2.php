
<?php
if (strpos($_SERVER['PHP_SELF'], '/admin-sf/') !== false) {
    $userId = null;
    $guestIdentifier = null;
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
    <a href="https://www.fishgelatine.co.za/v2">
      <img src="<?=$home_directory?>assets/img/logo/sir-francis-crest.png" alt="Sir Francis" class="footer-brand-logo" width="300" height="246" />
    </a>
  </div>

 <div class="social-network">
    <ul class="d-flex justify-content-center"> <!-- Added justify-content-center class -->
      <li>
        <a class="social-link-click" href="https://www.facebook.com/marinecollagenSA" target="_blank" aria-label="Sir Francis on Facebook"><span class="icon-social-facebook"></span></a>
      </li>
      <li class="mr-0">
        <a class="social-link-click" href="https://www.instagram.com/fishgelatine" target="_blank" aria-label="Sir Francis on Instagram"><span class="icon-social-instagram"></span></a>
      </li>
    </ul>
  </div>





          </div>

        </div>

        <div class="col-12 col-sm-6 col-lg-6 mb-30 text-left">
          <div class="footer-widget">
            
            <div class="mb-100">
                <h2 class="">Contact Us</h2>
                <span class="sf-anchor-divider mt-2 mb-5" aria-hidden="true"><i class="fas fa-anchor"></i></span>
              
                <ul class="mt-2 custom">
                  <li><a href="contact"><i class="fas fa-envelope mr-2"></i> <?=$website_email?></a></li>
                  <li><a href="contact"><i class="fas fa-phone mr-2"></i> <?=$tel?></a></li>
                  <li><a href="contact"><i class="fas fa-map mr-2"></i> <?=$website_address?></a></li>
                </ul>
            </div>


            <div style="width:300px">
              <h2 >Subscribe</h2>
              <p>Enter your email below to never miss out on our flash sales & special coupons just for you.</p>
                <span class="sf-anchor-divider mt-2 mb-5" aria-hidden="true"><i class="fas fa-anchor"></i></span>
              <div class="nletter-form mt-20">
                <form
                  class="form-inline position-relative"
                  action="https://www.fishgelatine.co.za/v2/subscribe.inc.php"
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
<div class="coppy-right py-15 ">
  <div class="container container1">
    <div class="row">
      <div class="col-12">
        <div class="d-flex flex-column align-items-center text-center pt-3">
          <p>
            Copyright &copy; <a href="https://www.fishgelatine.co.za/v2">Sir Francis</a>.
            All Rights Reserved
          </p>
          <img src="<?=$home_directory?>assets/img/logo/sir-francis-crest.png" alt="Sir Francis" width="150" height="123" class="mt-2 footer-copyright-logo"/>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .coppy-right {
    background: #172235 !important;
    border-spacing: 0;
    border-top: 1px solid rgba(206, 189, 136, .28);
  }

  .coppy-right p {
    color: #F1F0E8 !important;
  }

  .coppy-right a {
    color: #CEBD88 !important;
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
    color: #CEBD88 !important;
  }

  .footer-brand-logo {
    display: block;
    height: auto;
    max-width: min(300px, 100%);
    object-fit: contain;
    width: 300px;
  }

  .footer-copyright-logo {
    display: block;
    height: auto;
    object-fit: contain;
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
        <!-- content remains the same -->
      </div>
    </div>
  </div>
</div>


<!-- modals end -->

<div id="notification-container" style="position: fixed; top: 10px; right: 10px; z-index: 9999;"></div>

<!-- search-box and overlay start -->
<div class="overlay">
  <div class="scale"></div>
  <form class="search-box" action="products" method="get">
    <input type="text" name="search" id="search-input" placeholder="Search products..." />
    <button id="close" type="submit">
      <i class="ion-ios-search-strong"></i>
    </button>
  </form>
  <button class="close"><i class="ion-android-close"></i></button>
</div>

<!-- search-box and overlay end -->
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
    // Access product details from the response
    var productDetails = response.cart.product;

    // Set a default image URL if none is found
    var defaultImageUrl = $('#add-to-cart .product-image').attr('src');

    // Update modal content with product details
    $('#add-to-cartCenterTitle').text('Product successfully added to your shopping cart');
    $('#add-to-cart .product-name').text(productDetails.title);
    $('#add-to-cart .quick-view-price').text('R' + productDetails.price);

    // console.log('price:'+productDetails.price);
    
    // Use the default image URL if none is found in the response
    var imageUrl = productDetails.image_url || defaultImageUrl;
    $('#add-to-cart .product-image').attr('src', imageUrl);

    // $('#add-to-cart .quantity').text('Quantity: ' + response.cart.item_quantity);
    
    $('#add-to-cart .grand_total').text('R' + response.cart.subtotal);

    // Update cart summary
    $('#add-to-cart .cart-products-count').text('There is ' + response.cart.item_quantity + ' item(s) in your cart.');
}

$(document).ready(function () {

  updateBadgeCounts();

  // Handle click on "Add to Wishlist" icon
  $('body').on('click', '.add-to-cart', function (e) {
      e.preventDefault();


      // Retrieve product ID from data attribute
      var productId = $(this).data('product-id');

      logAction('Clicked on add-to-cart (product id: '+productId+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

      var quantity = $(this).data('quantity');
      var input_quantity = $(this).closest('.product-count').find('.add-to-cart-quantity').val();

      var inputValue = $(this).closest('.product-count').find('.add-to-cart-quantity').length ? $(this).closest('.product-count').find('.add-to-cart-quantity') : $(this).closest('tr').find('.product-count .add-to-cart-quantity');

      var input_quantity = inputValue.val();


      // Use a conditional statement to prioritize quantity from data attribute, or fallback to input value
      var finalQuantity = input_quantity !== undefined ? input_quantity : quantity;

      // console.log('Product ID:', productId);
      // console.log('Quantity:', finalQuantity);

      // Perform AJAX request
      $.ajax({
          type: 'POST',
          url: 'add_to_cart.php',
          data: {
              productId: productId,
              quantity: finalQuantity
          },
          success: function (response) {

              $('#offcanvas-cart .minicart-product-list').html(response.offcanvascart);

              updateBadgeCounts();
              $('#offcanvas-cart .sub-total .amount').text(response.cart.subtotal);

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
              // Handle errors (optional)
              console.error('Error:', error);
          }
      });
  });


  // Handle click on "Add to Wishlist" icon
  $('body').on('click', '.add-to-wishlist', function (e) {
      e.preventDefault();
      // Retrieve product ID from data attribute
      var productId = $(this).data('product-id');
      // console.log('Product ID:', productId); // Log to console for debugging
      logAction('Clicked on add-to-wishlist (product id: '+productId+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

      // Perform AJAX request
      $.ajax({
          type: 'POST',
          url: 'add_to_wishlist.php', // Specify the path to your PHP script
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
              console.error('Error:', error);
          }
      });
  });

  $('body').on('click', '.add-to-compare', function (e) {
      e.preventDefault();
      // Retrieve product ID from data attribute
      var productId = $(this).data('product-id');
      // console.log('Product ID:', productId); // Log to console for debugging

      logAction('Clicked on add-to-compare (product id: '+productId+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
      // Perform AJAX request
      $.ajax({
          type: 'POST',
          url: 'add_to_compare.php', // Specify the path to your PHP script
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

              window.location.href = "?";
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

    $('#subscribe_form').on('submit', function(e){
      e.preventDefault();
        var email = $('#subscribe_email').val();
        logAction('Submitted form to subscribe ('+email+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');

        // AJAX Request using jQuery
        $.ajax({
            type: 'POST',
            url: ' https://www.fishgelatine.co.za/v2/subscribe.inc.php',
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
        $('#search-input').focus();
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
    var notificationStyle = success ? 'background-color: #CEBD88; color: #28364B;' : 'background-color: #28364B; color: #CEBD88;';

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


// Event handler for the custom event to initialize Slick sliders
// $(document).on('slickInitEvent', function() {
//   console.log("I was triggered!");
//     // Initialize Slick sliders on elements with the class `product-sync-init`
//     $(".product-sync-init").slick({
//         slidesToShow: 1,
//         slidesToScroll: 1,
//         infinite: true,
//         draggable: false,
//         arrows: false,
//         dots: false,
//         fade: true,
//         asNavFor: ".product-sync-nav"
//     });

//     $(".product-sync-nav").slick({
//         dots: false,
//         arrows: false,
//         infinite: true,
//         prevArrow: '<button class="slick-prev"><i class="fas fa-arrow-left"></i></button>',
//         nextArrow: '<button class="slick-next"><i class="fas fa-arrow-right"></i></button>',
//         slidesToShow: 4,
//         slidesToScroll: 1,
//         asNavFor: ".product-sync-init",
//         focusOnSelect: true,
//         draggable: false
//     });
// });

$(document).ready(function () {
    $('body').on('click', '.open-quick-view', function (e) {
        e.preventDefault(); // Prevent the default link behavior

        var productId = $(this).data('product-id');
        // console.log("i opened the modal contents for ID " + productId);
        loadProductDetails(productId);

        logAction('Clicked on quick-view (product id: '+productId+')', 'From page ' + window.location.href, '<?=$userId?>', '<?=$guestIdentifier?>');
    });


    // Add event listener for change event on #changeModalProduct
    $('#quick-view').on('change', '#changeModalProduct', function () {
        var selectedProductId = $(this).val();
        // console.log("i am changing the modal contents for ID " + selectedProductId);
        loadProductDetails(selectedProductId);
    });

    // Add a click event listener for closing the modal
    $('#quick-view').on('hidden.bs.modal', function () {
      // console.log("i closed the modal!");
        // Call a function to clear/reset the modal content
        clearModalContent();
    });

});

function loadProductDetails(productId) {
    // Perform AJAX call to fetch product details from the server
    $.ajax({
        url: 'fetch_single_product.php',
        method: 'POST',
        data: { productId: productId },
        success: function (data) {
          // console.log(data);
            // Update the modal with the received data
            updateModal(data);
            
            $("#quick-view .product-sync-init").slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                draggable: false,
                arrows: false,
                dots: false,
                fade: true,
                asNavFor: ".product-sync-nav"
            });
            $("#quick-view .product-sync-nav").slick({
                dots: false,
                arrows: false,
                infinite: true,
                prevArrow: '<button class="slick-prev"><i class="fas fa-arrow-left"></i></button>',
                nextArrow: '<button class="slick-next"><i class="fas fa-arrow-right"></i></button>',
                slidesToShow: 4,
                slidesToScroll: 1,
                asNavFor: ".product-sync-init",
                focusOnSelect: true,
                draggable: false
            });

        },
        error: function (error) {
            console.log('Error fetching product details:', error);
        }
    });
}

function updateModal(productData) {
    // Generate product images HTML
    var product_url_og = 'https://www.fishgelatine.co.za/v2/assets/img/favicon.png';
    var productImagesHtml = '';
    var productThumbnailImagesHtml = '';

    var imageUrls = productData.image_urls;

    // Set default image URL
    var defaultImageUrl = 'assets/img/product/1.png';

    // Check if imageUrls array is empty
    if (imageUrls.length === 0) {
        imageUrls.push(defaultImageUrl);
    }

    imageUrls.forEach((imageUrl, index) => {
        productImagesHtml += `
              <div class="single-product">
                <div class="product-thumb">
                  <img
                    src="${imageUrl}"
                    alt="product-thumb"
                  />
                </div>
              </div>
        `;
        productThumbnailImagesHtml += `
           <div class="single-product">
                <div class="product-thumb">
                  <a href="javascript:void(0)">
                    <img
                      src="${imageUrl}"
                      alt="product-thumb"
                  /></a>
                </div>
              </div>
        `;

        product_url_og = "https://www.fishgelatine.co.za/v2/" + encodeURIComponent(imageUrl);
    });



    $('#quick-view .modal-body .quick-view-image-wrapper').html(`
          <div class="product-sync-init mb-20">
            ${productImagesHtml}
          </div>

          <div class="product-sync-nav">
            ${productThumbnailImagesHtml}
          </div>
    `);


    // initializeSlick();

    // Convert price to a number and then format it
    var formattedPrice = parseFloat(productData.price).toFixed(2);
    var price1 = parseFloat(productData.price).toFixed(2);
    var discount_amount = parseFloat(productData.discount_amount).toFixed(2);
    var discountedprice1 = (price1 - discount_amount).toFixed(2);

    
    var productTitle = productData.title;
    var productId = productData.id;
    var productCategory = productData.category_name;

    // Assuming productData.category_breadcrumb is a string like "Parent > SubCategory"
    var categoryBreadcrumb = productData.category_breadcrumb.split(' > ');
    var categoryLinksHtml = categoryBreadcrumb.map((item, index) => {
        var [categoryId, categoryName] = item.split(':');
        var categoryPath = categoryBreadcrumb.slice(0, index + 1).join(' > ');
        return `<a href="products?category=${categoryId}">${categoryName}</a>`;
    }).join(' > ');

    // Append the product title as the last breadcrumb
    categoryLinksHtml += ` > <span>${productTitle}</span>`;


    

    // Assume productData.rating is a number between 0 and 5
    var rating = parseFloat(productData.rating);

    // Function to convert rating to HTML stars
    function convertToStars(rating) {
        var fullStars = Math.floor(rating);
        var halfStar = rating % 1 !== 0;

        var starsHTML = '';

        for (var i = 0; i < fullStars; i++) {
            starsHTML += '<span class="star-on"><i class="fas fa-star"></i></span> ';
        }

        if (halfStar) {
            starsHTML += '<span class="star-on"><i class="fas fa-star-half-alt"></i></span> ';
        }

        // Add remaining empty stars if needed
        for (var j = fullStars + (halfStar ? 1 : 0); j < 5; j++) {
            starsHTML += '<span class="star-on de-selected"><i class="fas fa-star"></i></span> ';
        }

        return starsHTML;
    }
    // Update modal fields with the received product data
    // For example, you can use jQuery to set values like:
    $('#quick-view h2.title').text(productData.title);

    $('#quick-view .product-head').html(`
        <h2 class="title">
            ${productTitle}
        </h2>
        <h4 class="sub-title">${categoryLinksHtml}</h4>
        <div class="star-content mb-20">
            ${convertToStars(rating)}
        </div>
    `);

    $('#quick-view .product-footer').html(`
        <div
          class="product-count style d-flex flex-column flex-sm-row my-4"
        >
          <div class="count d-flex">
            <input type="number" min="1" max="999" step="1" value="1" class="add-to-cart-quantity" />
            <div class="button-group">
              <button class="count-btn increment">
                <i class="fas fa-chevron-up"></i>
              </button>
              <button class="count-btn decrement">
                <i class="fas fa-chevron-down"></i>
              </button>
            </div>
          </div>
          <div>
            <button class="btn btn-dark btn--xl mt-5 mt-sm-0 add-to-cart" data-product-id=${productId} data-toggle="modal"
                      data-target="#add-to-cart">
              <span class="mr-2"><i class="ion-android-add"></i></span>
              Add to cart
            </button>
          </div>
        </div>
        <div class="addto-whish-list">
          <a href="#" class="add-to-wishlist" data-product-id="${productId}"><i class="icon-heart"></i> Add to wishlist</a>
          <a href="#" class="add-to-compare" data-product-id="${productId}"><i class="icon-shuffle"></i> Add to compare</a>
        </div>
        <div class="pro-social-links mt-10">
          <ul class="d-flex align-items-center">
            <li class="share">Share</li>
            <li>
              <a class="share-link-click" href="https://www.facebook.com/sharer/sharer.php?u=https://www.fishgelatine.co.za/v2/product?id=${productId}" target="_blank" rel="noopener noreferrer"><i class="ion-social-facebook"></i></a>
            </li>
            <li>
              <a class="share-link-click" href="https://twitter.com/intent/tweet?url=https://www.fishgelatine.co.za/v2/product?id=${productId}&text=Check out this amazing product!" target="_blank" rel="noopener noreferrer"><i class="ion-social-twitter"></i></a>
            </li>
            <li>
              <a target="_blank" class="share-link-click" href="https://www.pinterest.com/pin/create/button/"
               data-pin-do="buttonBookmark"
               data-pin-custom="true"
               data-pin-save="true"
               data-pin-url="https://www.fishgelatine.co.za/v2/product?id=${productId}"
               data-pin-media="${product_url_og}"
               >
               <i class="ion-social-pinterest"></i>
              </a>
            </li>
          </ul>
        </div>
    `);
                    
    // Add product description with a maximum limit of 300 characters
    var limitedDescription = productData.description.length > 300
        ? productData.description.substring(0, 300) + '...'
        : productData.description;

    $('#quick-view .product-body').html(`
        <span class="product-price">
          ${productData.discount_rate > 0 ? `<del class="del">R${productData.price}</del>` : ''}
          <span class="onsale">R${(productData.price - productData.discount_amount).toFixed(2)}</span>
        </span>
        </br>
        ${limitedDescription}
    `);
    
    // Check if the product's weight is set and other relevant data is defined
    if (productData.weight && productData.other_weights && productData.other_products_in_group) {
        // Assume productData.other_weights and productData.other_products_in_group are comma-separated strings
        var newWeights = productData.other_weights.split(',').map(weight => weight.trim());
        var newValues = productData.other_products_in_group.split(',').map(value => value.trim());

        // Generate options for the "dimension" select based on the product's weight
        var dimensionOptions = `<option value="${productData.id}">${productData.weight}</option>`;

        // Add new weights and values to dimensionOptions
        for (var i = 0; i < newValues.length; i++) {
            dimensionOptions += `<option value="${newValues[i]}">${newWeights[i]}</option>`;
        }

        $('#quick-view .product-size').html(`
            <h3 class="title" style="font-weight:900">SELECT PRODUCT SIZE:</h3>
            <select id="changeModalProduct">
                ${dimensionOptions}
            </select>
        `);
    }
}

function clearModalContent() {
    // Clear or reset the content of your modal elements
    $('#quick-view h2.title').text('');
    $('#quick-view .product-head').html('');
    $('#quick-view .product-footer').html('');
    $('#quick-view .product-body').html('');
    $('#quick-view .product-size').html('');
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
    $.ajax({
        url: 'log_action.php',
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
