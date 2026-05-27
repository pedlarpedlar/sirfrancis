<?php
include 'session_logins.php';
include 'header.php';
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

<title>About Us | CandyBird - Nuts, Dried Fruit, Candy</title>

<?php
include 'page_menues.php';
?>


<section class="about-candybird py-5">
  <div class="container">

    <div class="text-center mb-5">
      <span class="section-label">Our Story</span>
      <h1 class="display-5 fw-bold mt-3">
        From a Passion for Baking to a Brand Built on Quality
      </h1>
    </div>

    <div class="story-card mb-5">
      <p class="lead-story">
        CandyBird began with a simple love for baking.
      </p>

      <p>
        What started as a home baking hobby — creating cakes and biscuits for friends and family —
        soon revealed a bigger challenge: finding consistently fresh, high-quality nuts and baking
        ingredients at affordable prices. To solve this, our founder began combining ingredient
        orders with neighbours on a not-for-profit basis, simply to make premium products more
        accessible to everyone.
      </p>

      <p>
        Before long, word spread from neighbour to neighbour, and demand grew rapidly. Orders became
        larger, more frequent, and eventually evolved into something much bigger than originally
        imagined. What started as a small community effort soon became CandyBird.
      </p>

      <p>
        Founded officially in 2020, CandyBird now sources, packs, manufactures, and distributes over
        <strong>150 varieties</strong> of nuts, dried fruit, confectionery, snack foods, health
        products, and treats across South Africa.
      </p>
    </div>

    <div class="story-highlight mb-5">
      <p>
        But the journey was never easy.
      </p>
    </div>

    <div class="story-card mb-5">
      <p>
        While continuing with cake decorating tutoring and home baking, our founder spent countless
        hours researching everything from nut grading and freshness preservation to storage methods
        and quality control. Every aspect of the business was built from the ground up — labels
        sourced from local printers, packaging designed in-house, and logistics personally managed.
      </p>

      <p>
        There were challenges, setbacks, and costly lessons along the way. From unreliable suppliers
        and fluctuating market prices to stock contamination and warehouse infestations, every
        obstacle tested the vision behind the brand. But through persistence, passion, and an
        unwavering commitment to quality, CandyBird continued to grow.
      </p>

      <p>
        Although expansion beyond Port Elizabeth was never the original plan, customers from across
        the country soon began reaching out and placing orders. In the early days, lead times could
        take between two and four weeks while orders were grouped together for purchasing — but
        customers understood they were waiting for something worth waiting for: genuine quality.
      </p>

      <p>
        Today, CandyBird proudly supplies both <strong>B2B and B2C customers</strong> across South
        Africa, while remaining rooted in the same hands-on values that shaped the business from
        day one.
      </p>
    </div>

    <div class="values-section mb-5">
      <span class="section-label light">What We Believe</span>

      <h2>Quality is not just a promise — it is our foundation.</h2>

      <p>
        From sourcing and packing to ordering and delivery, we personally oversee every step to
        ensure our customers receive products that meet the highest standards of freshness, flavour,
        and consistency.
      </p>

      <p>
        We believe in honesty and integrity. Our products are promoted transparently, without
        misleading claims, and our packaging is designed to let the quality of the product speak
        for itself.
      </p>

      <p>
        Most importantly, we believe in people. We strive to create a family atmosphere within our
        business by treating staff, suppliers, and customers with care, respect, and sincerity.
      </p>
    </div>

    <div class="promise-card text-center mb-5">
      <h2>Our Promise to You</h2>

      <p>
        Customer satisfaction has always been at the heart of CandyBird. Guided by the Islamic
        principle of <em>Iqaalah</em>, we proudly offer a
        <strong>100% satisfaction guarantee</strong>.
      </p>

      <p>
        If you are unhappy with a product due to quality, freshness, condition, taste, or if it
        differs from what was advertised, returns and exchanges are always welcomed.
      </p>
    </div>

    <div class="closing-section text-center">
      <span class="section-label">Looking Ahead</span>

      <h2>Rooted in quality. Growing with purpose.</h2>

      <p>
        CandyBird remains proudly home-based while continuing to grow throughout South Africa,
        with future ambitions focused on premium retail markets and franchising.
      </p>

      <h3>
        To bring health, happiness, quality, and exceptional service into people’s lives —
        one product at a time.
      </h3>
    </div>

  </div>
</section>

<style>
  .about-candybird {
    background: linear-gradient(135deg, #fbf7ff 0%, #fff9e8 100%);
    color: #2f173f;
    font-family: 'Poppins', Arial, sans-serif;
  }

  .about-candybird h1,
  .about-candybird h2,
  .about-candybird h3 {
    color: #3b145f;
    line-height: 1.2;
  }

  .about-candybird p {
    color: #4f3d5f;
    font-size: 1.05rem;
    line-height: 1.9;
  }

  .section-label {
    display: inline-block;
    background: #f6c945;
    color: #3b145f;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-size: 0.78rem;
    padding: 8px 18px;
    border-radius: 50px;
  }

  .section-label.light {
    background: #fff;
    color: #6b2c91;
    margin-bottom: 20px;
  }

  .story-card,
  .promise-card {
    background: #ffffff;
    border-radius: 28px;
    padding: 45px;
    box-shadow: 0 18px 45px rgba(59, 20, 95, 0.12);
    border: 1px solid rgba(107, 44, 145, 0.08);
  }

  .story-card {
    border-left: 7px solid #f6c945;
  }

  .lead-story {
    font-size: 1.7rem !important;
    font-weight: 700;
    color: #4b176f !important;
    line-height: 1.4 !important;
  }

  .story-highlight {
    background: linear-gradient(135deg, #4b176f 0%, #7a35a3 100%);
    border-radius: 28px;
    padding: 35px;
    text-align: center;
    box-shadow: 0 20px 45px rgba(59, 20, 95, 0.2);
  }

  .story-highlight p {
    color: #ffffff;
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
  }

  .values-section {
    background: linear-gradient(135deg, #5b1f83 0%, #35104f 100%);
    border-radius: 32px;
    padding: 45px;
    box-shadow: 0 22px 55px rgba(59, 20, 95, 0.22);
  }

  .values-section h2,
  .values-section p {
    color: #ffffff;
  }

  .values-section h2 {
    margin-bottom: 25px;
    font-weight: 800;
  }

  .promise-card {
    background: #fff8df;
    border: 2px solid #f6c945;
  }

  .promise-card h2 {
    color: #4b176f;
    font-weight: 800;
    margin-bottom: 20px;
  }

  .closing-section h2 {
    font-size: 2.3rem;
    font-weight: 800;
    margin-top: 18px;
  }

  .closing-section p {
    max-width: 850px;
    margin: 18px auto 0;
  }

  .closing-section h3 {
    color: #6b2c91;
    font-weight: 700;
    max-width: 950px;
    margin: 30px auto 0;
  }

  @media (max-width: 768px) {
    .story-card,
    .promise-card,
    .values-section {
      padding: 28px;
    }

    .lead-story,
    .story-highlight p {
      font-size: 1.4rem !important;
    }

    .closing-section h2 {
      font-size: 1.8rem;
    }
  }
</style>

<section class="google-reviews-section py-5">
  <div class="container">
    <div class="google-review-card text-center">
      <span class="section-label">Customer Reviews</span>
      <h2 class="mt-3">What Our Customers Say</h2>

      <div id="candybird-rating" class="rating-box mt-4"></div>
      <div id="candybird-reviews" class="reviews-grid mt-4"></div>

      <a href="https://www.google.com/search?q=CandyBird+reviews"
         target="_blank"
         class="btn google-review-btn mt-4">
        View More Reviews on Google
      </a>
    </div>
  </div>
</section>

<style>
  .google-reviews-section {
    background: linear-gradient(135deg, #fff9e8 0%, #fbf7ff 100%);
  }

  .google-review-card {
    background: #ffffff;
    border-radius: 32px;
    padding: 45px;
    box-shadow: 0 18px 45px rgba(59, 20, 95, 0.12);
    border: 2px solid #f6c945;
  }

  .google-review-card h2 {
    color: #3b145f;
    font-weight: 800;
  }

  .rating-box {
    color: #4b176f;
    font-size: 1.25rem;
    font-weight: 700;
  }

  .reviews-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
  }

  .review-item {
    background: #fbf7ff;
    border-radius: 22px;
    padding: 25px;
    text-align: left;
    border-left: 5px solid #f6c945;
  }

  .review-item h4 {
    color: #3b145f;
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 8px;
  }

  .review-stars {
    color: #f6c945;
    font-size: 1.1rem;
    margin-bottom: 12px;
  }

  .review-item p {
    color: #4f3d5f;
    font-size: 0.95rem;
    line-height: 1.7;
  }

  .google-review-btn {
    background: #4b176f;
    color: #ffffff;
    border-radius: 50px;
    padding: 14px 32px;
    font-weight: 700;
    text-decoration: none;
  }

  .google-review-btn:hover {
    background: #f6c945;
    color: #3b145f;
  }

  @media (max-width: 992px) {
    .reviews-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<script>
  function initCandyBirdReviews() {
    const service = new google.maps.places.PlacesService(
      document.createElement("div")
    );

    service.getDetails(
      {
        placeId: "ChIJD-2dz3vReh4R8QE7FCn_Xc4",
        fields: ["name", "rating", "user_ratings_total", "reviews", "url"]
      },
      function (place, status) {
        if (status !== google.maps.places.PlacesServiceStatus.OK || !place) {
          document.getElementById("candybird-reviews").innerHTML =
            "<p>Google reviews are currently unavailable.</p>";
          return;
        }

        document.getElementById("candybird-rating").innerHTML =
          `${place.rating || ""} ★ from ${place.user_ratings_total || 0} Google reviews`;

        const reviewsContainer = document.getElementById("candybird-reviews");
        reviewsContainer.innerHTML = "";

        if (place.reviews && place.reviews.length) {
          place.reviews.slice(0, 3).forEach(function (review) {
            const stars = "★".repeat(review.rating) + "☆".repeat(5 - review.rating);

            reviewsContainer.innerHTML += `
              <div class="review-item">
                <h4>${review.author_name}</h4>
                <div class="review-stars">${stars}</div>
                <p>${review.text}</p>
              </div>
            `;
          });
        } else {
          reviewsContainer.innerHTML = "<p>No reviews available at the moment.</p>";
        }
      }
    );
  }
</script>

<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDNYtzAP875aoyTvQnfaK96eizYBJ1jxB8&libraries=places&callback=initCandyBirdReviews">
</script>
  


<?php
include 'footer.php';
?>
