<?php
$is_homepage = true;
$skip_google_fonts = false;
$defer_gtag = true;
$defer_homepage_personalization = true;
$load_shopping_nav = false;
$page_preload_images = ['assets/img/slider/1.optimized.jpg'];
include 'session_logins.php';
require_once __DIR__ . '/google_integrations_helpers.php';
$sfGoogleSettings = sfGoogleIntegrationSettings($conn ?? null);
$sfGoogleReviewsApiKey = sfGooglePlacesBrowserKey($conn ?? null);
$sfGoogleBusinessPlaceId = $sfGoogleSettings['google_business_place_id'] ?? '';
$page_url_canonical = 'https://sirfrancis.co.za/';
$page_url_og = 'https://sirfrancis.co.za/';
$title_og = "Sir Francis | Marine Collagen, Fish Gelatine and Private Labelling";
$description_meta = 'Sir Francis supplies premium fish gelatine, marine collagen, peptides, tripeptides, sea moss and supplement private labelling solutions in South Africa.';
$description_og = $description_meta;
$image_url_og = 'https://sirfrancis.co.za/assets/img/logo/logo.png';
include 'header.php';
$showSubscribeOffer = empty($_SESSION['user_id']);
?>

<title>Sir Francis | Marine Collagen, Fish Gelatine and Private Labelling</title>
<style>

.cinzel {
  font-family: "Playfair Display", Georgia, serif;
  font-optical-sizing: auto;
  font-weight: 400;
  font-style: normal;
}

.dancing-script {
  font-family: "Pinyon Script", cursive !important;
  font-optical-sizing: auto;
  font-weight: 400;
  font-style: normal;
}

.montserrat {
  font-family: "Raleway", Arial, sans-serif !important;
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

.homepage-info-link {
  color: inherit;
  height: 100%;
  text-decoration: none;
}

.homepage-seo-intro {
  border-bottom: 1px solid #ded6c6;
}

.homepage-seo-intro h1 {
  color: #28364B;
  font-family: "Playfair Display", Georgia, serif;
  font-size: clamp(28px, 4vw, 46px);
  font-weight: 800;
  letter-spacing: 0;
  line-height: 1.08;
  margin-bottom: 16px;
}

.homepage-seo-intro p {
  color: #344359;
  font-size: 16px;
  line-height: 1.7;
  margin-bottom: 18px;
}

.homepage-seo-links {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.homepage-seo-links a {
  background: #F1F0E8;
  border: 3px double #CEBD88;
  border-radius: 0;
  color: #28364B;
  display: inline-flex;
  font-size: 14px;
  font-weight: 700;
  padding: 9px 13px;
  text-decoration: none;
}

.homepage-seo-links a:hover,
.homepage-seo-links a:focus {
  background: #28364B;
  border-color: #28364B;
  color: #fff;
}

.home-promo-banner .banner-thumb a {
  align-items: flex-end;
  background: #28364B;
  display: flex;
  height: clamp(190px, 24vw, 330px);
  isolation: isolate;
  position: relative;
  text-decoration: none;
}

.home-promo-banner .banner-thumb img {
  display: block;
  height: 100%;
  inset: 0;
  object-fit: cover;
  position: absolute;
  width: 100%;
  z-index: -2;
}

.home-promo-banner .banner-thumb a::after {
  background: linear-gradient(180deg, rgba(40, 54, 75, 0.1), rgba(40, 54, 75, 0.92));
  content: "";
  inset: 0;
  position: absolute;
  z-index: -1;
}

.home-pathway-copy {
  color: #fff;
  padding: 22px;
}

.home-pathway-copy h2 {
  color: #CEBD88;
  font-size: clamp(22px, 2.2vw, 32px);
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 8px;
}

.home-pathway-copy p {
  color: #fff;
  font-size: 15px;
  line-height: 1.45;
  margin: 0;
}

.brand-slider-section .single-brand {
  padding: 0 8px;
}

.brand-slider-section .brand-thumb {
  align-items: center;
  background: #F1F0E8;
  border: 3px double #CEBD88;
  color: #28364B;
  display: flex;
  height: 100px;
  justify-content: center;
  overflow: hidden;
  position: relative;
  text-decoration: none;
}

.brand-slider-section .brand-thumb:hover,
.brand-slider-section .brand-thumb:focus {
  background: #28364B;
  color: #CEBD88;
}

.brand-slider-section .brand-icon-fallback {
  align-items: center;
  background-position: center;
  background-repeat: no-repeat;
  background-size: contain;
  display: flex;
  height: 100%;
  justify-content: center;
  padding: 12px;
  width: 100%;
}

.brand-slider-section .brand-icon-fallback.sf-has-uploaded-image i {
  display: none;
}

.brand-slider-section .brand-icon-fallback i {
  color: currentColor;
  font-size: 34px;
}

.product-tab,
.common-banner.thingy,
.blog-section {
  display: none !important;
}

</style>

  <style>



    .rope {
      width: 100%;
      height: 20px;
      margin-top: -4px;
      background-image: url('assets/img/rope.png');
      background-repeat: repeat-x;
      background-position: bottom left; /* Align at the bottom-left corner */
      position: relative;
    }

    .anchor {
      width: 235px;
      height: 320px;
      position: absolute;
      bottom: -50px;
      right: 60px;
      background-image: url('assets/img/anchor-main.svg');
      background-repeat: no-repeat;
      background-position: bottom right;
      background-size: contain;
      pointer-events: none;
    }

    @media (max-width: 768px) {
      .anchor {
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

    .hero-crest-logo {
      display: block;
      filter: drop-shadow(0 8px 22px rgba(0, 0, 0, .28));
      height: auto;
      margin: 0 auto 18px;
      max-width: min(420px, 86vw);
      width: 420px;
    }

    .slider-content .hero-crest-logo + .text {
      margin-top: 4px;
    }

    @media (max-width: 767px) {
      .hero-crest-logo {
        margin-bottom: 12px;
        max-width: min(300px, 88vw);
      }
    }

  </style>

<?php
include 'page_menues.php';
?>

<!-- main slider start -->
<!-- <div id="logo-main"></div> -->
<!-- <img id="logo-main" src="assets/img/logo/main.png" alt="logo"> -->
<section class="bg-light">
  <div class="main-slider dots-style theme1">

<?php

include 'slides.php';

$slideFallbackImages = [
    'bg-img1' => 'assets/img/slider/slider.jpg',
    'bg-img2' => 'assets/img/slider/slide1.jpg',
    'bg-img3' => 'assets/img/slider/slide2.jpg',
    'bg-img4' => 'assets/img/slider/slide3.png',
    'bg-img5' => 'assets/img/slider/slider.jpg',
    'bg-img6' => 'assets/img/slider/slide1.jpg',
    'bg-img7' => 'assets/img/slider/slide2.jpg',
    'bg-img8' => 'assets/img/slider/slide3.png',
];

foreach ($slides as $index => $slide) {
    $slideBgClass = $index === 0 ? $slide['bg_img'] : '';
    $deferredBgAttribute = $index === 0 ? '' : ' data-bg-img-class="' . htmlspecialchars($slide['bg_img'], ENT_QUOTES, 'UTF-8') . '"';
    $slideImageKey = 'homepage.slider.' . $index;
    $slideImagePath = sfSiteImagePath($slideImageKey, $slideFallbackImages[$slide['bg_img']] ?? 'assets/img/ocean.jpg');
    echo '<div class="slider-item bg-img ' . $slideBgClass . '"' . $deferredBgAttribute . sfSiteBackgroundStyle($slideImagePath) . sfSiteEditableBackgroundAttrs($slideImageKey, $slideImagePath, '1600 x 900px or larger, landscape') . '>';
    echo '  <div class="container container1">';
    echo '    <div class="row align-items-center slider-height">';
    echo '      <div class="col-12 text-center">';
    echo '        <div class="slider-content">';
    echo '          <img class="hero-crest-logo animated" src="assets/img/logo/sir-francis-crest.png" alt="Sir Francis" width="420" height="345" data-animation-in="' . $slide['animation']['title'] . '" data-delay-in="0.200">';
    echo '          <p class="text animated cinzel" data-animation-in="' . $slide['animation']['title'] . '" data-delay-in="' . $slide['delay_in_title'] . '">';
    echo '            ' . $slide['title'];
    echo '          </p>';
    echo '          <h2 class="title animated pb-3 align-items-center">';
    echo '            <span class="sf-anchor-divider animated" aria-hidden="true" data-animation-in="' . $slide['animation']['title'] . '" data-delay-in="0.900"><i class="fas fa-anchor"></i></span>';
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
<script>
  window.addEventListener('load', function() {
    window.setTimeout(function() {
      document.querySelectorAll('[data-bg-img-class]').forEach(function(slide) {
        var bgClass = slide.getAttribute('data-bg-img-class');
        if (!bgClass) return;
        slide.classList.add(bgClass);
        slide.removeAttribute('data-bg-img-class');
      });
    }, 2500);
  }, { once: true });
</script>
<div class="rope">
  <div class="anchor"></div>
</div>
<!-- main slider end -->

<!-- landing pathways start -->
<div class="common-banner home-promo-banner bg-white pt-100 pb-20 ">
  <div class="container">
    <div class="row">
      <div class="col-md-6 mb-30">
        <div class="banner-thumb">
          <a
            href="wholesale-pricelist"
            class="zoom-in d-block overflow-hidden"
          >
            <img src="<?= htmlspecialchars(sfSiteImagePath('homepage.pathway.bulk', 'assets/img/ocean.jpg'), ENT_QUOTES, 'UTF-8') ?>" alt="Bulk marine collagen and fish gelatine" width="570" height="330" loading="eager" decoding="async"<?= sfSiteEditableImageAttrs('homepage.pathway.bulk', '1140 x 660px, landscape') ?> />
            <span class="home-pathway-copy">
              <h2>Buy Bulk</h2>
              <p>Marine collagen, fish gelatine and wellness ingredients for wholesale buyers and businesses.</p>
            </span>
          </a>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-30">
        <div class="banner-thumb">
          <a
            href="products"
            class="zoom-in d-block overflow-hidden"
          >
            <img src="<?= htmlspecialchars(sfSiteImagePath('homepage.pathway.retail', 'assets/img/slider/1.optimized.jpg'), ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='assets/img/ocean.jpg';" alt="Sir Francis retail shop" width="270" height="330" loading="lazy" decoding="async"<?= sfSiteEditableImageAttrs('homepage.pathway.retail', '540 x 660px, portrait') ?> />
            <span class="home-pathway-copy">
              <h2>Retail Shop</h2>
              <p>Shop the retail range direct to the public.</p>
            </span>
          </a>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 mb-30">
        <div class="banner-thumb">
          <a
            href="private_labelling"
            class="zoom-in d-block overflow-hidden"
          >
            <img src="<?= htmlspecialchars(sfSiteImagePath('homepage.pathway.private_label', 'assets/img/slider/2.optimized.jpg'), ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='assets/img/ocean.jpg';" alt="Sir Francis private labelling" width="270" height="330" loading="lazy" decoding="async"<?= sfSiteEditableImageAttrs('homepage.pathway.private_label', '540 x 660px, portrait') ?> />
            <span class="home-pathway-copy">
              <h2>Private Labelling</h2>
              <p>Create your own supplement brand.</p>
            </span>
          </a>
        </div>
      </div>
      <div class="col-md-6 mb-30">
        <div class="banner-thumb">
          <a
            href="find-agent"
            class="zoom-in d-block overflow-hidden"
          >
            <img src="<?= htmlspecialchars(sfSiteImagePath('homepage.pathway.agent', 'assets/img/slider/3.optimized.jpg'), ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='assets/img/ocean.jpg';" alt="Find a Sir Francis agent" width="570" height="330" loading="lazy" decoding="async"<?= sfSiteEditableImageAttrs('homepage.pathway.agent', '1140 x 660px, landscape') ?> />
            <span class="home-pathway-copy">
              <h2>Find an Agent</h2>
              <p>Locate regional Sir Francis support or suggest an agent for your area.</p>
            </span>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- landing pathways end -->

<section class="homepage-seo-intro bg-white pt-25 pb-35">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8 mb-3 mb-lg-0">
        <h1>Marine collagen, fish gelatine and private labelling in South Africa</h1>
        <p>
          Sir Francis supplies premium fish gelatine, marine collagen, peptides, tripeptides, sea moss and custom supplement solutions. We support wholesale buyers, private-label brands, regional agents and retail customers across South Africa.
        </p>
      </div>
      <div class="col-lg-4">
        <div class="homepage-seo-links">
          <a href="products">Shop online</a>
          <a href="pricelist">Pricelist</a>
          <a href="find-agent">Find an agent</a>
          <a href="wholesale-pricelist">Wholesale pricelist</a>
          <a href="private_labelling">Private labelling</a>
          <a href="contact">Contact us</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- customer reviews start -->
<section class="customer-reviews-section bg-white pt-30 pb-50">
  <div class="container">
    <div class="section-title text-center mb-4">
      <h2 class="title mb-3">What Our Customers Say</h2>
      <span class="sf-anchor-divider pb-3" aria-hidden="true"><i class="fas fa-anchor"></i></span>
      <p class="text">
        Real feedback from customers who have experienced Sir Francis quality.
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
    background: linear-gradient(135deg, #ffffff 0%, #F1F0E8 100%);
  }

  .review-slide-card {
    max-width: 850px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 28px;
    padding: 45px 60px;
    box-shadow: 0 18px 45px rgba(40, 54, 75, 0.14);
    border: 2px solid #CEBD88;
  }

  .review-stars {
    color: #CEBD88;
    font-size: 1.4rem;
    margin-bottom: 15px;
  }

  .review-text {
    color: #28364B;
    font-size: 1.08rem;
    line-height: 1.8;
    font-style: italic;
  }

  .review-author {
    color: #28364B;
    font-weight: 800;
    margin-top: 20px;
  }

  .carousel-control-prev-icon,
  .carousel-control-next-icon {
    background-color: #28364B;
    border: 3px double #CEBD88;
    border-radius: 0;
    padding: 20px;
  }

  @media (max-width: 768px) {
    .review-slide-card {
      padding: 30px 25px;
    }
  }
</style>

<script>
  const sfHomepageReviewsConfig = {
    apiKey: <?= json_encode($sfGoogleReviewsApiKey) ?>,
    placeId: <?= json_encode($sfGoogleBusinessPlaceId) ?>
  };

  function shuffleReviews(reviews) {
    return reviews.sort(() => Math.random() - 0.5);
  }

  function initHomepageReviews() {
    const service = new google.maps.places.PlacesService(
      document.createElement("div")
    );

    service.getDetails(
      {
        placeId: sfHomepageReviewsConfig.placeId,
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

<script>
  (function() {
    var reviewsLoaded = false;
    var loadReviews = function() {
      if (reviewsLoaded) return;
      reviewsLoaded = true;
      var container = document.getElementById("homepage-google-reviews");
      if (!sfHomepageReviewsConfig.apiKey || !sfHomepageReviewsConfig.placeId) {
        if (container) {
          container.innerHTML = `
            <div class="carousel-item active">
              <div class="review-slide-card text-center">
                <p>Customer reviews are currently unavailable.</p>
              </div>
            </div>
          `;
        }
        return;
      }
      if (window.google && google.maps && google.maps.places) {
        initHomepageReviews();
        return;
      }

      var script = document.createElement('script');
      script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(sfHomepageReviewsConfig.apiKey) + '&libraries=places&callback=initHomepageReviews&loading=async';
      script.async = true;
      script.defer = true;
      document.head.appendChild(script);
    };

    var reviewsSection = document.querySelector('.customer-reviews-section');
    if ('IntersectionObserver' in window && reviewsSection) {
      var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (!entry.isIntersecting) return;
          observer.disconnect();
          loadReviews();
        });
      }, { rootMargin: '250px 0px' });
      observer.observe(reviewsSection);
    } else {
      window.addEventListener('load', function() {
        window.setTimeout(loadReviews, 10000);
      }, { once: true });
    }
  })();
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
            <span class="sf-anchor-divider pb-3" aria-hidden="true"><i class="fas fa-anchor"></i></span>
            <p class="text">
              Browse Sir Francis marine collagen, fish gelatine, peptides, tripeptides, sea moss and private labelling options.
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
          <a href="<?php echo htmlspecialchars($item['link'] ?? './', ENT_QUOTES, 'UTF-8'); ?>" class="d-flex static-media2 flex-column flex-sm-row homepage-info-link">
            <img
              class="align-self-center mb-2 mb-sm-0 mr-auto mr-sm-3"
              src="assets/img/icon/<?php echo $item['img']; ?>"
              alt=""
              width="64"
              height="64"
              loading="lazy"
              decoding="async"
            />
            <div class="media-body">
              <h4 class="title"><?php echo $item['title']; ?></h4>
              <p class="text"><?php echo $item['text']; ?></p>
            </div>
          </a>
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
            ><img src="<?= htmlspecialchars(sfSiteImagePath('homepage.banner.featured', 'assets/img/banner/5.png'), ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Featured product banner" width="370" height="270" loading="lazy" decoding="async"<?= sfSiteEditableImageAttrs('homepage.banner.featured', '740 x 540px, landscape') ?>
          /></a>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-30">
        <div class="banner-thumb">
          <a
            class="zoom-in d-block overflow-hidden position-relative"
            href="return_policy"
          >
            <img src="<?= htmlspecialchars(sfSiteImagePath('homepage.banner.returns', 'assets/img/banner/6.png'), ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Return policy banner" width="370" height="270" loading="lazy" decoding="async"<?= sfSiteEditableImageAttrs('homepage.banner.returns', '740 x 540px, landscape') ?>
          /></a>
        </div>
      </div>
      <div class="col-lg-6 col-md-12 mb-30">
        <div class="banner-thumb">
          <a
            class="zoom-in d-block overflow-hidden position-relative"
            href="find-agent"
          >
            <img src="<?= htmlspecialchars(sfSiteImagePath('homepage.banner.agent-network', 'assets/img/banner/4.png'), ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='assets/img/banner/1.png';" alt="Regional agent network banner" width="770" height="270" loading="lazy" decoding="async"<?= sfSiteEditableImageAttrs('homepage.banner.agent-network', '1540 x 540px, wide landscape') ?>
          /></a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- brand slider start -->
<div class="brand-slider-section theme1 bg-white">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="brand-init border-top py-35 slick-nav-brand">
          <?php foreach ($brands as $index => $brand): ?>
          <?php
            $brandImageKey = 'homepage.brand.' . $index;
            $brandImagePath = sfSiteImagePath($brandImageKey, '');
            $brandIconClass = $brand['icon'] ?? 'fas fa-anchor';
            $brandHasImage = $brandImagePath !== '';
          ?>
          <div class="slider-item">
            <div class="single-brand">
              <a href="<?php echo htmlspecialchars($brand['link'] ?? './', ENT_QUOTES, 'UTF-8'); ?>" class="brand-thumb" aria-label="<?php echo htmlspecialchars($brand['alt'], ENT_QUOTES, 'UTF-8'); ?>">
                <span
                  class="brand-icon-fallback<?= $brandHasImage ? ' sf-has-uploaded-image' : '' ?>"
                  role="img"
                  aria-label="<?php echo htmlspecialchars($brand['alt'], ENT_QUOTES, 'UTF-8'); ?>"
                  <?= sfSiteBackgroundStyle($brandImagePath) ?><?= sfSiteEditableBackgroundAttrs($brandImageKey, $brandImagePath, '340 x 200px, transparent PNG or clean logo on light background') ?>
                >
                  <i class="<?php echo htmlspecialchars($brandIconClass, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                </span>
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


<?php $jquery_loaded_early = true; ?>
<script src="<?=$home_directory?>assets/js/vendor/jquery-3.5.1.min.js"></script>
<script>



// Function to initialize Slick Slider
function initSlickSlider1() {
  $(".allowed-products-on-homepage:not(.slick-initialized)").slick({
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
  function escapeAttr(value) {
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

  var pool = products.filter(isFeatured);
  if (!pool.length) {
    pool = products;
  }

  var newProducts = shuffle(pool.filter(function(product) {
    return productMatchesTag(product, 'new');
  })).slice(0, 4);
  if (!newProducts.length) {
    newProducts = shuffle(pool).slice(0, 4);
  }

  var saleProducts = shuffle(pool.filter(function(product) {
    return parseFloat(product.discount_amount || 0) > 0 || parseFloat(product.discount_rate || 0) > 0 || parseFloat(product.discounted_price || product.price || 0) < parseFloat(product.price || 0) || productMatchesTag(product, 'sale');
  })).slice(0, 4);

  var hotProducts = pool.slice().sort(function(a, b) {
    return (parseInt(b.monthly_sales || 0, 10) || 0) - (parseInt(a.monthly_sales || 0, 10) || 0);
  }).filter(function(product) {
    return (parseInt(product.monthly_sales || 0, 10) || 0) > 0 || productMatchesTag(product, 'hot');
  }).slice(0, 4);
  if (!hotProducts.length) {
    hotProducts = shuffle(pool.filter(function(product) { return productMatchesTag(product, 'hot'); })).slice(0, 4);
  }

  function productsHtml(items, category) {
    var html = "";
    $.each(items, function (index, product) {
    var defaultImageUrl = 'assets/img/product/1.png';
    var salePercent = getSalePercent(product);
    var price = parseFloat(product.price || 0) || 0;
    var productPrice = getProductPrice(product);
    var productTitle = escapeAttr([product.title, product.weight].filter(Boolean).join(' ') || 'Sir Francis product');

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
                    <a class="action add-to-wishlist" href="#" data-product-id="${product.id}" aria-label="Add ${productTitle} to wishlist">
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
                      aria-label="Compare ${productTitle}"
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
                      aria-label="Quick view ${productTitle}"
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
                      aria-label="Add ${productTitle} to cart"
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

  var tabSets = {
    home: { container: homeContainer, items: newProducts, rendered: false },
    profile: { container: profileContainer, items: saleProducts, rendered: false },
    contact: { container: contactContainer, items: hotProducts, rendered: false }
  };

  function renderProductSet(key) {
    var set = tabSets[key];
    if (!set || set.rendered) return;
    set.container.html(`<div class="product-slider-init theme1 slick-nav allowed-products-on-homepage">${productsHtml(set.items, getCategoryLink(set.items))}</div>`);
    set.rendered = true;
    initSlickSlider1();
  }

  renderProductSet('home');
  $('#pills-profile-tab').one('shown.bs.tab', function() {
    renderProductSet('profile');
  });
  $('#pills-contact-tab').one('shown.bs.tab', function() {
    renderProductSet('contact');
  });
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
        window.SIRFRANCIS_PRODUCTS = products;
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
