<script>
// Function to initialize Slick Slider
function initSlickSlider1() {
  $(".custom_allowed_products").slick({
    autoplay: false,
    autoplaySpeed: 10000,
    dots: false,
    infinite: false,
    arrows: true,
    speed: 1000,
    slidesToShow: 4,
    slidesToScroll: 1,
    prevArrow: '<button class="slick-prev"><i class="ion-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next"><i class="ion-chevron-right"></i></button>',
    responsive: [{
      breakpoint: 1199,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        infinite: true,
        dots: false
      }
    }, {
      breakpoint: 1024,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true
      }
    }, {
      breakpoint: 768,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    }, {
      breakpoint: 480,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true
      }
    } // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
    ]
  });
}

// Function to display products on the webpage
function displayProducts(products) {
  var homeContainer = $('#generated_products');

  // Accumulate the HTML for each section
  var homeHtml = "";

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

  function getProductImage(product) {
    var imageValue = product.img_url || product.image_url || product.image_urls || product.image || '';
    var images = String(imageValue).split(',').map(function(image) {
      return image.trim().replace(/ /g, '%20');
    }).filter(Boolean);
    return images.length ? images[0] : 'assets/img/product/1.png';
  }

  function getProductUrl(product) {
    if (product.product_url) {
      return product.product_url;
    }
    if (product.slug) {
      return '/' + encodeURIComponent(product.slug);
    }
    return 'product?id=' + encodeURIComponent(product.id || '');
  }

  function formatMoney(value) {
    var number = Number(value || 0);
    return number.toFixed(2);
  }

  // Loop through each product and create HTML elements
  $.each(products, function (index, product) {
    var defaultImageUrl = 'assets/img/product/1.png';
    var imageUrl = getProductImage(product);
    var productUrl = getProductUrl(product);
    var originalPrice = Number(product.original_price || product.price || 0);
    var finalPrice = Number(product.final_price || product.discounted_price || (originalPrice - Number(product.discount_amount || 0)) || originalPrice);
    var hasDiscount = originalPrice > 0 && finalPrice > 0 && finalPrice < originalPrice;
    var productTitle = escapeHtml([product.title || product.name || 'Sir Francis product', product.weight || product.size || ''].filter(Boolean).join(' '));

    var productHtml = `
      <div class="slider-item">
        <div class="card product-card">
          <div class="card-body p-0">
            <div class="media flex-column">
              <div class="product-thumbnail position-relative">
                <div class="product-thumbnail position-relative">
                    ${product.label ? `<span class="badge badge-danger top-right">${product.label}</span>` : ''}
                    ${product.discount_rate > 0 ? `<span class="badge badge-success top-left">-${Math.floor(product.discount_rate)}%</span>` : ''}
                </div>
                <a href="${escapeHtml(productUrl)}">
                  <img
                    class="first-img"
                    src="${escapeHtml(imageUrl)}"
                    onerror="this.onerror=null;this.src='${defaultImageUrl}';"
                    alt="${productTitle}"
                    width="450"
                    height="450"
                    loading="lazy"
                    decoding="async"
                  />
                </a>
                <!-- product links -->
                <ul class="actions d-flex justify-content-center">
                  <li>
                    <a class="action add-to-wishlist" href="#" data-product-id="${product.id}">
                      <span
                        data-toggle="tooltip"
                        data-placement="bottom"
                        title="add to wishlist"
                        class="icon-heart"
                      >
                      </span>
                    </a>
                  </li>
                  <li>
                    <a
                      class="action add-to-compare" data-product-id="${product.id}"
                      href="#"
                    >
                      <span
                        data-toggle="tooltip"
                        data-placement="bottom"
                        title="Add to compare"
                        class="icon-shuffle"
                      ></span>
                    </a>
                  </li>
                  <li>
                    <a
                      class="action open-quick-view"
                      data-product-id="${product.id}"
                      href="#"
                      data-toggle="modal"
                      data-target="#quick-view"
                    >
                      <span
                        data-toggle="tooltip"
                        data-placement="bottom"
                        title="Quick view"
                        class="icon-magnifier"
                      ></span>
                    </a>
                  </li>
                </ul>
                <!-- product links end-->
              </div>
              <div class="media-body">
                <div class="product-desc">
                  <h3 class="title">
                    <a style="width:210px;display:block;" href="${escapeHtml(productUrl)}">${escapeHtml(product.title || product.name || 'Sir Francis product')} ${escapeHtml(product.weight || product.size || '')}</a>
                  </h3>
                  <div class="star-rating">
                    ${generateStarRating(product.avg_rating)}
                  </div>
                  <div
                    class="d-flex align-items-center justify-content-between"
                  >
                    <span class="product-price">
                      ${hasDiscount ? `<del class="del">R${formatMoney(originalPrice)}</del>` : ''}
                      <span class="onsale">R${formatMoney(finalPrice)}</span>
                    </span>
                    <button
                      class="pro-bt add-to-cart"
                      data-toggle="modal"
                      data-target="#add-to-cart"
                      data-quantity="1"
                      data-product-id="${product.id}"
                    >
                      <i class="icon-basket"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;

    homeHtml += productHtml;    

  });

  // Replace the existing HTML inside each container
  homeContainer.html(`<div class="product-slider-init theme1 slick-nav custom_allowed_products">${homeHtml}</div>`);

  initSlickSlider1();
}

// Function to generate star rating HTML
function generateStarRating(avgRating) {
  var ratingHtml = '';
  var fullStars = Math.floor(avgRating);
  var halfStar = avgRating % 1 !== 0;

  for (var i = 0; i < fullStars; i++) {
    ratingHtml += '<span class="ion-ios-star"></span>';
  }

  if (halfStar) {
    ratingHtml += '<span class="ion-ios-star-half"></span>';
  }

  return ratingHtml;
}

$(document).ready(function () {
  if (!$('#generated_products').length) {
    return;
  }

// Array of allowed product IDs
  var allowedProductIds = []; //<?=json_encode($productIds)?>;

  // AJAX call to fetch products
  $.ajax({
    url: 'fetch_homepage_products.php', // Replace with your backend script handling the database query
    method: 'POST',
    data: { productIds: allowedProductIds },
    dataType: 'json',
    success: function (response) {
      // Handle the response and populate the product container
      if (response.success) {
        var products = response.products;
        displayProducts(products);
      } else {
        console.error('Error fetching products: ' + response.message);
      }
    },
    error: function (xhr, status, error) {
      console.error('AJAX Error: ' + status + ' - ' + error);
    }
  });


});
</script>
