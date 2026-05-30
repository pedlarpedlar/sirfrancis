<?php
include 'session_logins.php';
include 'header.php';
$showSubscribeOffer = empty($_SESSION['user_id']);
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

<title>CandyBird - Nuts | Dried Fruit | Candy</title>

<?php
include 'page_menues.php';
?>


<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>


<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Dancing+Script:wght@400..700&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Dancing+Script:wght@400..700&family=Montserrat+Alternates:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

<style>

.cinzel {
  font-family: "Cinzel", serif;
  font-optical-sizing: auto;
  font-weight: 400;
  font-style: normal;
}

.dancing-script {
  font-family: "Dancing Script", cursive !important;
  font-optical-sizing: auto;
  font-weight: 400;
  font-style: normal;
}

.montserrat {
  font-family: "Montserrat", sans-serif !important;
  font-optical-sizing: auto;
  font-weight: 400;
  font-style: normal;
}

.thingy2 { 
  background: url(assets/img/ocean.jpg) no-repeat center center fixed; 
  -webkit-background-size: cover;
  -moz-background-size: cover;
  -o-background-size: cover;
  background-size: cover;
}

.product-tab .product-card .product-thumbnail {
  aspect-ratio: 1 / 1;
  background: #f7f3ee;
  overflow: hidden;
  width: 100%;
}

.product-tab .product-card .product-thumbnail > a {
  display: block;
  height: 100%;
  width: 100%;
}

.product-tab .product-card .product-thumbnail img.first-img {
  display: block;
  height: 100%;
  object-fit: cover;
  object-position: center;
  width: 100%;
}

.product-tab .product-card .product-thumbnail .product-thumbnail {
  aspect-ratio: auto;
  background: transparent;
  inset: 0;
  overflow: visible;
  pointer-events: none;
  position: absolute !important;
}

</style>

  <style>



    .nut-border {
      width: 100%;
      height: 35px;
      margin-top: -20px;
      //background-image: url('assets/img/nut-border.svg'); /* Set the background image */
      //background-repeat: repeat-x; /* Repeat horizontally */
      background-position: bottom left; /* Align at the bottom-left corner */
      position: relative;
    }

    .walnut {
      width: 355px;
      height: 350px;
      position: absolute;
      bottom: -90px;
      right: 60px;
/*      background-color: blue;*/
      background-image: url('assets/img/walnut.svg'); /* Set the background image */
      background-repeat: no-repeat; /* Do not repeat horizontally or vertically */
      background-position: bottom right; /* Align at the bottom-right corner */
      background-size: cover; /* Cover the entire width of the div */
    }

    @media (max-width: 768px) {
      /* Hide the .walnut element on screens smaller than tablets */
      .walnut {
        display: none;
      }
    }

    #logo-main {
      width: 50px;
      z-index: 2 !important;
      position: absolute;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    /* For big screens */
    @media (min-width: 1200px) {
      #logo-main {
        top: 100px;
      }
    }

    /* For tablets */
    @media (max-width: 1199px) and (min-width: 768px) {
      #logo-main {
        top: 170px;
      }
    }

    /* For phones */
    @media (max-width: 767px) {
      #logo-main {
        top: 110px;
      }
    }

  </style>

<!-- main slider start -->
<!-- <div id="logo-main"></div> -->
<!-- <img id="logo-main" src="assets/img/logo/main.png" alt="logo"> -->
<section class="bg-light">
  <div class="main-slider dots-style theme1">

<?php

include 'slides.php';
include 'recipe_posts.php';

foreach ($slides as $slide) {
    echo '<div class="slider-item bg-img ' . $slide['bg_img'] . '">';
    echo '  <div class="container container1">';
    echo '    <div class="row align-items-center slider-height">';
    echo '      <div class="col-12 text-center">';
    echo '        <div class="slider-content">';
    echo '          <p class="text animated cinzel" data-animation-in="' . $slide['animation']['title'] . '" data-delay-in="' . $slide['delay_in_title'] . '">';
    echo '            ' . $slide['title'];
    echo '          </p>';
    echo '          <h2 class="title animated pb-3 align-items-center">';
    echo '            <span class="animated d-block" data-animation-in="' . $slide['animation']['title'] . '" data-delay-in="0.900"><img src="assets/img/break.svg" alt="wave" style="margin: 10px auto;"></span>';
    echo '          </h2>';
    echo '          <p class="text text-secondary animated dancing-script" data-animation-in="' . $slide['animation']['description'] . '" data-delay-in="' . $slide['delay_in_description'] . '">';
    echo '            ' . $slide['subtitle'];
    echo '          </p>';
    echo '          <p class="text text-secondary sub-subtitle animated montserrat" data-animation-in="' . $slide['animation']['description'] . '" data-delay-in="' . $slide['delay_in_description'] . '" style="width:350px; margin: 0 auto;">';
    echo '            ' . $slide['description'];
    echo '          </p>';
    echo '          <a href="' . $slide['button_link'] . '" class="btn btn-primary btn--xl animated mt-45 mt-sm-25" data-animation-in="' . $slide['animation']['button'] . '" data-delay-in="' . $slide['delay_in_button'] . '">';
    echo '            ' . $slide['button_text'];
    echo '          </a>';
    echo '        </div>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}
?>

  </div>

</section>
<div class="nut-border">
  <div class="walnut"></div>
</div>
<!-- main slider end -->

<!-- common banner  start -->
<div class="common-banner bg-white pt-100 pb-20 ">
  <div class="container">
    <div class="row">
      <div class="col-md-6 mb-30">
        <div class="banner-thumb">
          <a
            href="products?category=Nuts"
            class="zoom-in d-block overflow-hidden"
          >
            <?php
            $values = [1]; // Keep this list to files that actually exist in assets/img/banner.
            $shuffleBanner = $values[array_rand($values)];
            ?>

            <img src="assets/img/banner/<?=$shuffleBanner?>.png" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Nuts banner" />
          </a>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-30">
        <div class="banner-thumb">
          <a
            href="products?category=Gifting"
            class="zoom-in d-block overflow-hidden"
          >
            <img src="assets/img/banner/2.png" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Gifting banner" />
          </a>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-30">
        <div class="banner-thumb">
          <a
            href="products?category=For%20Resellers"
            class="zoom-in d-block overflow-hidden"
          >
            <img src="assets/img/banner/3.png" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Reseller banner" />
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- common banner  end -->


<!-- customer reviews start -->
<section class="customer-reviews-section bg-white pt-30 pb-50">
  <div class="container">
    <div class="section-title text-center mb-4">
      <h2 class="title mb-3">What Our Customers Say</h2>
      <img class="pb-3" src="assets/img/break.svg" alt="wave">
      <p class="text">
        Real feedback from customers who have experienced CandyBird quality.
      </p>
    </div>

    <div id="customerReviewsCarousel" class="carousel slide" data-ride="carousel">
      <div class="carousel-inner" id="homepage-google-reviews">
        <div class="carousel-item active">
          <div class="review-slide-card text-center">
            <p>Loading customer reviews...</p>
          </div>
        </div>
      </div>

      <a class="carousel-control-prev" href="#customerReviewsCarousel" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      </a>

      <a class="carousel-control-next" href="#customerReviewsCarousel" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
      </a>
    </div>
</section>

<style>
  .customer-reviews-section {
    background: linear-gradient(135deg, #ffffff 0%, #fff9e8 100%);
  }

  .review-slide-card {
    max-width: 850px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 28px;
    padding: 45px 60px;
    box-shadow: 0 18px 45px rgba(59, 20, 95, 0.12);
    border: 2px solid #f6c945;
  }

  .review-stars {
    color: #f6c945;
    font-size: 1.4rem;
    margin-bottom: 15px;
  }

  .review-text {
    color: #4f3d5f;
    font-size: 1.08rem;
    line-height: 1.8;
    font-style: italic;
  }

  .review-author {
    color: #3b145f;
    font-weight: 800;
    margin-top: 20px;
  }

  .carousel-control-prev-icon,
  .carousel-control-next-icon {
    background-color: #4b176f;
    border-radius: 50%;
    padding: 20px;
  }

  @media (max-width: 768px) {
    .review-slide-card {
      padding: 30px 25px;
    }
  }
</style>

<script>
  function shuffleReviews(reviews) {
    return reviews.sort(() => Math.random() - 0.5);
  }

  function initHomepageReviews() {
    const service = new google.maps.places.PlacesService(
      document.createElement("div")
    );

    service.getDetails(
      {
        placeId: "ChIJD-2dz3vReh4R8QE7FCn_Xc4",
        fields: ["name", "rating", "user_ratings_total", "reviews", "url"]
      },
      function (place, status) {
        const container = document.getElementById("homepage-google-reviews");

        if (status !== google.maps.places.PlacesServiceStatus.OK || !place || !place.reviews) {
          container.innerHTML = `
            <div class="carousel-item active">
              <div class="review-slide-card text-center">
                <p>Customer reviews are currently unavailable.</p>
              </div>
            </div>
          `;
          return;
        }

        const shuffledReviews = shuffleReviews(place.reviews);
        container.innerHTML = "";

        shuffledReviews.forEach(function (review, index) {
          const stars = "★".repeat(review.rating) + "☆".repeat(5 - review.rating);

          container.innerHTML += `
            <div class="carousel-item ${index === 0 ? "active" : ""}">
              <div class="review-slide-card text-center">
                <div class="review-stars">${stars}</div>
                <p class="review-text">“${review.text}”</p>
                <div class="review-author">— ${review.author_name}</div>
              </div>
            </div>
          `;
        });
      }
    );
  }
</script>

<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDNYtzAP875aoyTvQnfaK96eizYBJ1jxB8&libraries=places&callback=initHomepageReviews">
</script>
<!-- customer reviews end -->

<!-- product tab start -->
<section class="product-tab bg-white pt-30 pb-30">
  <div class="container">
    <div class="product-tab-nav mb-50">
      <div class="row align-items-center">
        <div class="col-12">
          <div class="section-title text-center">
            <!-- <h3 class="title-fancy">What we do</h2> -->
            <h2 class="title mb-3">Our products</h2>
            <img class="pb-3" src="assets/img/break.svg" alt="wave">
            <p class="text">
              Browse our range of nuts, dried fruit and mixes. Need ideas for gifting? Check out some of our personalised gifting.
            </p>
          </div>
        </div>
        <div class="col-12">
          <nav class="product-tab-menu theme1">
            <ul
              class="nav nav-pills justify-content-center"
              id="pills-tab"
              role="tablist"
            >
              <li class="nav-item">
                <a
                  class="nav-link active"
                  id="pills-home-tab"
                  data-toggle="pill"
                  href="#pills-home"
                  role="tab"
                  aria-controls="pills-home"
                  aria-selected="true"
                  >New</a
                >
              </li>
              <li class="nav-item">
                <a
                  class="nav-link"
                  id="pills-profile-tab"
                  data-toggle="pill"
                  href="#pills-profile"
                  role="tab"
                  aria-controls="pills-profile"
                  aria-selected="false"
                  >Sale</a
                >
              </li>
              <li class="nav-item">
                <a
                  class="nav-link"
                  id="pills-contact-tab"
                  data-toggle="pill"
                  href="#pills-contact"
                  role="tab"
                  aria-controls="pills-contact"
                  aria-selected="false"
                  >Hot</a
                >
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
    <!-- product-tab-nav end -->
    <div class="row">
      <div class="col-12">
        <div class="tab-content" id="pills-tabContent">

          <!-- first tab-pane -->
          <div
            class="tab-pane fade show active"
            id="pills-home"
            role="tabpanel"
            aria-labelledby="pills-home-tab"
          >
            
          </div>

          <!-- second tab-pane -->
          <div
            class="tab-pane fade"
            id="pills-profile"
            role="tabpanel"
            aria-labelledby="pills-profile-tab"
          >
          </div>

          <!-- third tab-pane -->
          <div
            class="tab-pane fade"
            id="pills-contact"
            role="tabpanel"
            aria-labelledby="pills-contact-tab"
          >
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- product tab end -->

<!-- staic media start -->
<section class="static-media-section py-80 bg-white thingy">
  <div class="container">
    <div class="static-media-wrap theme-bg2">
      <div class="row">
        <?php foreach ($staticMedia as $item): ?>
        <div class="col-lg-3 col-sm-6 py-3">
          <div class="d-flex static-media2 flex-column flex-sm-row">
            <img
              class="align-self-center mb-2 mb-sm-0 mr-auto mr-sm-3"
              src="assets/img/icon/<?php echo $item['img']; ?>"
              alt="icon"
            />
            <div class="media-body">
              <h4 class="title"><?php echo $item['title']; ?></h4>
              <p class="text"><?php echo $item['text']; ?></p>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<!-- staic media end -->

<!-- common banner  start -->
<div class="common-banner bg-white pt-30 thingy">
  <div class="container">
    <div class="row">
      <div class="col-lg-3 col-md-6 mb-30">
        <div class="banner-thumb">
          <a
            class="zoom-in d-block overflow-hidden position-relative"
            href="product?id=101"
            ><img src="assets/img/banner/5.png" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Featured product banner"
          /></a>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-30">
        <div class="banner-thumb">
          <a
            class="zoom-in d-block overflow-hidden position-relative"
            href="return_policy"
          >
            <img src="assets/img/banner/6.png" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Return policy banner"
          /></a>
        </div>
      </div>
      <div class="col-lg-6 col-md-12 mb-30">
        <div class="banner-thumb">
          <a
            class="zoom-in d-block overflow-hidden position-relative"
            href="recipes"
          >
            <img src="assets/img/banner/4.png" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Recipes banner"
          /></a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- start recipe section -->
<section class="blog-section theme1 pb-65">
  <div class="container">
    <div class="row">
      <div class="col-12">
          <div class="section-title text-center">
            <h2 class="title mb-3">From our latest recipes</h2>
            <img class="pb-3" src="assets/img/break.svg" alt="wave">
            <p class="text">
              Check out these scrumptious, delicious recipes!
            </p>
          </div>
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        <div class="blog-init slick-nav">
          <?php
          $first_five_posts = array_slice($blogPosts, 0, 5);
          ?>
          <?php foreach ($first_five_posts as $post): ?>
          <div class="slider-item">
            <div class="single-blog">
              <a
                class="blog-thumb mb-20 zoom-in d-block overflow-hidden"
                href="recipe?id=<?php echo $post['id']; ?>"
              >
                <img src="assets/img/blog-post/<?php echo $post['img']; ?>" alt="blog-thumb-naile" />
              </a>
              <div class="blog-post-content">
                <a
                  class="blog-link theme-color d-inline-block mb-10 text-uppercase"
                  href="recipe?id=<?php echo $post['id']; ?>"
                >
                  <?php echo $post['category']; ?>
                </a>
                <h3 class="title mb-15">
                  <a href="recipe?id=<?php echo $post['id']; ?>">
                    <?php echo strip_tags($post['title']); ?>
                  </a>
                </h3>
                <p class="sub-title">
                  Posted by
                  <a
                    class="theme-color d-inline-block mx-1"
                    href="./"
                  >
                    <?php echo $post['author']; ?>
                  </a>
                  <?php echo $post['date']; ?>
                </p>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- end recipe section -->


<!-- brand slider start -->
<div class="brand-slider-section theme1 bg-white">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="brand-init border-top py-35 slick-nav-brand">
          <?php foreach ($brands as $brand): ?>
          <div class="slider-item">
            <div class="single-brand">
              <a href="./" class="brand-thumb">
                <img src="assets/img/brand/<?php echo $brand['img']; ?>" alt="brand-thumb-nail" /> <!-- <?php echo $brand['alt']; ?> -->
              </a>
            </div>
          </div>
          <!-- slider-item end -->
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- brand slider end -->


<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
  

<script>



// Function to initialize Slick Slider
function initSlickSlider1() {
  $(".allowed-products-on-homepage").slick({
    autoplay: false,
    autoplaySpeed: 10000,
    dots: false,
    infinite: false,
    arrows: true,
    speed: 1000,
    slidesToShow: 4,
    slidesToScroll: 1,
    prevArrow: '<button class="slick-prev" data-slider-id="homepage-products prev"><i class="ion-chevron-left"></i></button>',
    nextArrow: '<button class="slick-next" data-slider-id="homepage-products next"><i class="ion-chevron-right"></i></button>',
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
  // initSlickSlider1();




// Function to display products on the webpage
function displayProducts(products) {
  var homeContainer = $('#pills-home');
  var profileContainer = $('#pills-profile');
  var contactContainer = $('#pills-contact');

  function shuffle(items) {
    return items.slice().sort(function() { return 0.5 - Math.random(); });
  }

  function productMatchesTag(product, tag) {
    var text = String([product.label, product.tags, product.tag].join(' ')).toLowerCase();
    return text.indexOf(tag) !== -1;
  }
  function isFeatured(product) {
    return ['yes', 'y', 'true', '1'].indexOf(String(product.homepage_featured || '').toLowerCase().trim()) !== -1;
  }
  function getProductPrice(product) {
    var price = parseFloat(product.price || 0) || 0;
    if (!isProductSpecialActive(product)) return price;
    var discounted = parseFloat(product.discounted_price || 0) || 0;
    var amount = parseFloat(product.discount || product.discount_amount || 0) || 0;
    var rate = parseFloat(product.discount_rate || 0) || 0;
    if (discounted > 0 && discounted < price) return discounted;
    if (amount > 0) return Math.max(0, price - amount);
    if (rate > 0) return Math.max(0, price - (price * rate / 100));
    return price;
  }
  function parseSpecialDate(value, endOfDay) {
    value = String(value || '').trim();
    if (!value) return null;
    var hasTime = /\d{1,2}:\d{2}/.test(value);
    var match = value.match(/^(\d{1,2})[-/](\d{1,2})[-/](\d{4})(?:\s+(\d{1,2}):(\d{2}))?$/);
    if (match) {
      var date = new Date(+match[3], +match[2] - 1, +match[1], +(match[4] || 0), +(match[5] || 0), 0);
      if (endOfDay && !hasTime) date.setHours(23, 59, 59, 999);
      return date;
    }
    var parsed = new Date(value);
    if (isNaN(parsed.getTime())) return null;
    if (endOfDay && !hasTime) parsed.setHours(23, 59, 59, 999);
    return parsed;
  }
  function isProductSpecialActive(product) {
    var from = parseSpecialDate(product.discount_valid_from || product.special_valid_from || product.sale_valid_from, false);
    var until = parseSpecialDate(product.discount_valid_until || product.special_valid_until || product.sale_valid_until, true);
    var now = new Date();
    if (from && now < from) return false;
    if (until && now > until) return false;
    return true;
  }
  function getSalePercent(product) {
    var price = parseFloat(product.price || 0) || 0;
    var discounted = getProductPrice(product);
    if (price <= 0 || discounted >= price) return 0;
    return Math.round(((price - discounted) / price) * 100);
  }
  function getCategoryLink(items) {
    var picked = (items || []).find(function(product) {
      return product.child_category_1 || product.parent_category || product.category;
    }) || {};
    var category = picked.child_category_1 || picked.parent_category || picked.category || '';
    return 'products' + (category ? '?category=' + encodeURIComponent(category) : '');
  }

  var pool = products.filter(isFeatured);
  if (!pool.length) {
    pool = products;
  }

  var newProducts = shuffle(pool.filter(function(product) {
    return productMatchesTag(product, 'new');
  })).slice(0, 10);
  if (!newProducts.length) {
    newProducts = shuffle(pool).slice(0, 10);
  }

  var saleProducts = shuffle(pool.filter(function(product) {
    return parseFloat(product.discount_amount || 0) > 0 || parseFloat(product.discount_rate || 0) > 0 || parseFloat(product.discounted_price || product.price || 0) < parseFloat(product.price || 0) || productMatchesTag(product, 'sale');
  })).slice(0, 10);

  var hotProducts = pool.slice().sort(function(a, b) {
    return (parseInt(b.monthly_sales || 0, 10) || 0) - (parseInt(a.monthly_sales || 0, 10) || 0);
  }).filter(function(product) {
    return (parseInt(product.monthly_sales || 0, 10) || 0) > 0 || productMatchesTag(product, 'hot');
  }).slice(0, 10);
  if (!hotProducts.length) {
    hotProducts = shuffle(pool.filter(function(product) { return productMatchesTag(product, 'hot'); })).slice(0, 10);
  }

  function productsHtml(items, category) {
    var html = "";
    $.each(items, function (index, product) {
    var defaultImageUrl = 'assets/img/product/1.png';
    var salePercent = getSalePercent(product);
    var price = parseFloat(product.price || 0) || 0;
    var productPrice = getProductPrice(product);

    html += `
      <div class="slider-item">
        <div class="card product-card">
          <div class="card-body p-0">
            <div class="media flex-column">
              <div class="product-thumbnail position-relative">
                <div class="product-thumbnail position-relative">
                    ${product.label ? `<span class="badge badge-danger top-left">${product.label}</span>` : ''}
                    ${salePercent > 0 ? `<span class="badge badge-success top-right">${salePercent}% off</span>` : (productMatchesTag(product, 'hot') ? `<span class="badge badge-warning top-right">Hot</span>` : '')}
                </div>
                <a href="product?id=${product.id}">
                  <img
                    class="first-img"
                    src="${product.image_url || defaultImageUrl}"
                    onerror="this.onerror=null;this.src='${defaultImageUrl}';"
                    alt="thumbnail"
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
                    <a style="width:210px;display:block;" href="product?id=${product.id}">${product.title} ${product.weight}</a>
                  </h3>
                  <div class="star-rating">
                    ${generateStarRating(product.avg_rating)}
                  </div>
                  <div
                    class="d-flex align-items-center justify-content-between"
                  >
                    <span class="product-price">
                      ${salePercent > 0 ? `<del class="del">R${price.toFixed(2)}</del>` : ''}
                      <span class="onsale">R${productPrice.toFixed(2)}</span>
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

    });
    html += '<div class="slider-item d-flex align-items-center"><a class="btn btn-dark btn--lg" href="' + (category || getCategoryLink(items)) + '">See more in this category</a></div>';
    return html;
  }

  // Replace the existing HTML inside each container
  homeContainer.html(`<div class="product-slider-init theme1 slick-nav allowed-products-on-homepage">${productsHtml(newProducts, getCategoryLink(newProducts))}</div>`);
  profileContainer.html(`<div class="product-slider-init theme1 slick-nav allowed-products-on-homepage">${productsHtml(saleProducts, getCategoryLink(saleProducts))}</div>`);
  contactContainer.html(`<div class="product-slider-init theme1 slick-nav allowed-products-on-homepage">${productsHtml(hotProducts, getCategoryLink(hotProducts))}</div>`);

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

// Array of allowed product IDs
  var allowedProductIds = [];
  
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
        window.CANDYBIRD_PRODUCTS = products;
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



<?php
include 'footer.php';
?>
