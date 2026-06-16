<?php
include 'session_logins.php';
include 'header.php';

$breadcrumb = "";
$image_slider = "";
$image_slider_thumbs = "";
$dimensionOptions = "";

// Function to get product ID from the URL
function getProductIDFromURL() {
    // Assuming the URL is in the format: http://example.com/your-page?product_id=123
    $url = $_SERVER['REQUEST_URI'];
    $parts = parse_url($url);
    parse_str($parts['query'], $query);
    return isset($query['id']) ? intval($query['id']) : null;
}

// Get the product ID from the URL
$productID = getProductIDFromURL();

if (!is_null($productID)) {
    // Fetch product details, images, categories, and reviews from the database using prepared statement
    $sql = "SELECT 
                p.*,
                GROUP_CONCAT(DISTINCT i.image_url) AS image_urls,
                c.name AS category_name,
                COALESCE(AVG(r.rating), 0) AS rating,
                COUNT(r.id) AS review_count,
                (SELECT GROUP_CONCAT(weight) FROM product WHERE enabled = 1 AND product_group = p.product_group) AS other_weights,
                (SELECT GROUP_CONCAT(id) FROM product WHERE enabled = 1 AND product_group = p.product_group) AS other_products_in_group
            FROM 
                product p
            LEFT JOIN 
                images i ON p.id = i.product_id
            LEFT JOIN 
                categories c ON p.category_id = c.id
            LEFT JOIN 
                reviews r ON p.id = r.product_id
            WHERE 
                p.enabled = 1
                AND p.id = ?
            GROUP BY 
                p.id, c.name";

    // Prepare the statement
    $stmt = mysqli_prepare($conn, $sql);

    // Bind the product ID parameter
    mysqli_stmt_bind_param($stmt, "i", $productID);

    // Execute the statement
    mysqli_stmt_execute($stmt);

    // Get the result set
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Assign values to variables
        $productId = $row['id'];
        $weight = $row['weight'];
        $other_weights = $row['other_weights'];
        $other_products_in_group = $row['other_products_in_group'];
        $dimensions = $row['dimensions'];
        $title = $row['title'];
        $price = $row['price'];
        $discountRate = $row['discount_rate'];
        $discountAmount = $row['discount_amount'];
        $taxRate = $row['tax_rate'];
        $taxAmount = $row['tax_amount'];
        $description = $row['description'];
        $other_info = $row['other_info'];
        $categoryName = $row['category_name'];
        $imageUrls = $row['image_urls'];
        $rating = ($row['review_count'] > 0) ? $row['rating'] : 0;
        $review_count = $row['review_count'];
        $product_label = !empty($row['label']) ? '<span class="badge badge-danger top-right">'.$row['label'].'</span>' : '';

        $related_product_ids = $row['related_products'];

        // echo $related_product_ids; exit();
        $materials = "";
        // Limit the description to a reasonable length
        $limitedDescription = strlen($description) > 300 ? trim(strip_tags(substr($description, 0, 300) . '...')) : trim(strip_tags($description));
        
        // Step 1: Remove HTML tags and convert them to plain <p> tags
        $plainText = strip_tags($description, '<p>');

        // Step 2: Split the content into an array of paragraphs
        $paragraphs = explode('</p>', $plainText);

        // Step 3: Filter out empty paragraphs
        $paragraphs = array_filter($paragraphs, function($paragraph) {
            // Check if the paragraph is not empty after trimming
            return trim($paragraph) !== '';
        });

        // Step 5: Take only the first 5 paragraphs
        $limitedDescriptionHtml = implode('</p>', array_slice($paragraphs, 0, 5));

        $product_url = "https://www.fishgelatine.co.za/v2/product?id=".$productId;

        // Check if the product is on sale
        if ($discountRate > 0) {
            // Calculate the discount amount if it's not provided
            if ($discountAmount == 0) {
                $discountAmount = ($price * $discountRate) / 100;
            }

            // Calculate the discounted price
            $discounted_price = $price - $discountAmount;

            // Build the price section with discounted information
            $price_section = '
                <div class="d-flex align-items-center mb-30">
                    <span class="product-price mr-20">
                        <del class="del">R'.number_format($price, 2).'</del>
                        <span class="onsale">R'.number_format($discounted_price, 2).'</span>
                    </span>
                    <span class="badge position-static bg-dark rounded-0">Save '.number_format($discountRate, 0).'%</span>
                </div>
            ';
        } else {
            // Build the price section without discounted information
            $price_section = '
                <div class="d-flex align-items-center mb-30">
                    <span class="product-price mr-20">R'.$price.'</span>
                </div>
            ';
        }
        

        // Function to convert rating to HTML stars
        function convertToStars($rating) {
            $fullStars = floor($rating);
            $halfStar = $rating % 1 !== 0;

            $starsHTML = '';

            for ($i = 0; $i < $fullStars; $i++) {
                $starsHTML .= '<span class="star-on"><i class="ion-ios-star"></i></span> ';
            }

            if ($halfStar) {
                $starsHTML .= '<span class="star-on"><i class="ion-ios-star-half"></i></span> ';
            }

            // Add remaining empty stars if needed
            for ($j = $fullStars + ($halfStar ? 1 : 0); $j < 5; $j++) {
                $starsHTML .= '<span class="star-on de-selected" style="color: lightgrey"><i class="ion-ios-star de-selected"></i></span> ';
            }

            return $starsHTML;
        }

        function convertToStarsAndroid($rating) {
            $fullStars = floor($rating);
            $halfStar = $rating % 1 !== 0;

            $starsHTML = '';

            for ($i = 0; $i < $fullStars; $i++) {
                $starsHTML .= '<span class="star-on"><i class="ion-android-star"></i></span> ';
            }

            if ($halfStar) {
                $starsHTML .= '<span class="star-on"><i class="ion-android-star-half"></i></span> ';
            }

            // Add remaining empty stars if needed
            for ($j = $fullStars + ($halfStar ? 1 : 0); $j < 5; $j++) {
                $starsHTML .= '<span class="star-on de-selected" style="color: lightgrey"><i class="ion-android-star de-selected"></i></span> ';
            }

            return $starsHTML;
        }

    
        // Check if the product's weight is set and other relevant data is defined
        if (!empty($weight) && !empty($other_weights) && !empty($other_products_in_group)) {
            $dimensionOptions .= '<h3 class="title">selection</h3><select id="select_options_element">';
            // Assume $other_weights and $other_products_in_group are comma-separated strings
            $newWeights = array_map('trim', explode(',', $other_weights));
            $newValues = array_map('trim', explode(',', $other_products_in_group));

            // Generate options for the "dimension" select based on the product's weight
            $dimensionOptions .= '<option value="' . $productId . '">' . $weight . '</option>';

            // Add new weights and values to dimensionOptions, skipping the current $productId
            for ($i = 0; $i < count($newValues); $i++) {
                // Skip adding the current product's ID to the options
                if ($newValues[$i] != $productId) {
                    $dimensionOptions .= '<option value="' . $newValues[$i] . '">' . $newWeights[$i] . '</option>';
                }
            }
            
            $dimensionOptions .= '</select>';
        }

        // Check if $imageUrls is empty and assign the default image URL
        if (empty($imageUrls)) {
            $defaultImageUrl = "assets/img/product/1.png";
            $imageUrls = $defaultImageUrl;
        }

        // Display images in separate boxes
        $imageUrlsArray = explode(',', $imageUrls); // Assuming image URLs are comma-separated in the database

        foreach ($imageUrlsArray as $index => $imageUrl) {
            $image_slider .= '<div class="single-product">';
            $image_slider .= '<div class="product-thumb">';
            $image_slider .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="product-thumb" />';
            $image_slider .= '</div>';
            $image_slider .= '</div>';
            $image_slider_thumbs .= '<div class="single-product"><div class="product-thumb"><a href="javascript:void(0)">';
            $image_slider_thumbs .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="product-thumb" />';
            $image_slider_thumbs .= '</a></div></div>';

            $image_url_encoded = str_replace(' ', '%20', $imageUrl);

            $image_url_og = /*"https://www.fishgelatine.co.za/v2/" .*/ $image_url_encoded;
        }

    } else {
        // header("Location: products");
      echo "No results found!";
        exit();
    }
} else {
    // header("Location: products");
  echo "Item not found!";
    exit();
}
?>

<?php
$page_url_canonical = "https://www.fishgelatine.co.za/v2/product?id=".$productId;
$title_og = $title . ' ' . $weight . ' - Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/product?id=".$productId;
$description_og = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');
$description_meta = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');
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

<title><?=$title . ' ' . $weight?> - Sir Francis</title>

<?php
include 'page_menues.php';

include 'breadcrumbs.php';
?>

<?=generateBreadcrumbs($conn, $productId);?>

<!-- product-single start -->
<section class="product-single theme1">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 mb-5 mb-lg-0">
        <div>
          <div class="position-relative">
            <?=$product_label?>
          </div>
          <div class="product-sync-init mb-20">
            <?=$image_slider?>
          </div>
        </div>
        <div class="product-sync-nav single-product">
          <?=$image_slider_thumbs?>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="single-product-info">
          <div class="single-product-head">
            <h2 class="title mb-20"><?=$title?></h2>
            <div class="star-content mb-20">
              <?=convertToStars($rating)?>

              <a href="#" id="write-comment"
                ><span class="ml-2"><i class="far fa-comment-dots"></i></span>
                Read reviews <span>(<?=$review_count?>)</span></a
              >
              <a href="#" data-toggle="modal" data-target="#writeReviewModal"
                ><span class="edite"><i class="far fa-edit"></i></span> Write a
                review</a
              >
            </div>
          </div>
          <div class="product-body mb-40">
            <?=$price_section?>
            
            <?=$limitedDescriptionHtml?>
            
          </div>
          <div class="product-footer">
            <div class="d-flex">
              <div class="product-size mr-5" id="product_selection">
                  <?=$dimensionOptions?>
              </div>

              <!-- <div class="check-box ml-5">
                <h4 class="title">color</h4>
                <div class="d-flex check-box-wrap-list">
                  <div class="widget-check-box color-white">
                    <input type="checkbox" id="test13" />
                    <label for="test13"></label>
                  </div>
                  <div class="widget-check-box color-black">
                    <input type="checkbox" id="test14" />
                    <label for="test14"></label>
                  </div>
                </div>
              </div> -->
            </div>
            <div class="product-count style d-flex flex-column flex-sm-row mt-30 mb-20">
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
                <button type="button" class="btn btn-dark btn--xl mt-5 mt-sm-0 add-to-cart" 
                data-toggle="modal"
                data-target="#add-to-cart"
                data-quantity="1"
                data-product-id="<?=$productId?>">
                  <span class="mr-2"><i class="ion-android-add"></i></span>
                  Add to cart
                </button>
              </div>




            </div>
            <div class="addto-whish-list">
              <a href="#" class="add-to-wishlist" data-product-id="<?=$productId?>" ><i class="icon-heart"></i> Add to wishlist</a>
              <a href="#" class="add-to-compare" data-product-id="<?=$productId?>" ><i class="icon-shuffle"></i> Add to compare</a>
            </div>
            <div class="pro-social-links mt-10">
              <ul class="d-flex align-items-center">
                <li class="share">Share</li>
                <li>
                  <a href="https://www.facebook.com/sharer/sharer.php?u=<?=$product_url?>" target="_blank" rel="noopener noreferrer"><i class="ion-social-facebook"></i></a>
                </li>
                <li>
                  <a href="https://twitter.com/intent/tweet?url=<?=$product_url?>&text=Check out this amazing product!" target="_blank" rel="noopener noreferrer"><i class="ion-social-twitter"></i></a>
                </li>
                <li>
                  <a target="_blank" href="https://www.pinterest.com/pin/create/button/"
                   data-pin-do="buttonBookmark"
                   data-pin-custom="true"
                   data-pin-save="true"
                   data-pin-url="<?=$product_url?>"
                   data-pin-media="<?=$image_url_og?>"
                   >
                   <i class="ion-social-pinterest"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- product-single end -->







<!-- product tab start -->
<div class="product-tab theme1 bg-white pt-60 pb-80">
  <div class="container">
    <div class="product-tab-nav">
      <div class="row align-items-center">
        <div class="col-12">
          <nav class="product-tab-menu single-product">
            <ul
              class="nav nav-pills justify-content-center"
              id="pills-tab"
              role="tablist"
            >
              <li class="nav-item">
                <a
                  class="nav-link"
                  id="pills-home-tab"
                  data-toggle="pill"
                  href="#pills-home"
                  role="tab"
                  aria-controls="pills-home"
                  aria-selected="true"
                  >Description</a
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
                  >Product Details</a
                >
              </li>
              <li class="nav-item">
                <a
                  class="nav-link active"
                  id="pills-contact-tab"
                  data-toggle="pill"
                  href="#pills-contact"
                  role="tab"
                  aria-controls="pills-contact"
                  aria-selected="false"
                  >Reviews</a
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
            class="tab-pane fade"
            id="pills-home"
            role="tabpanel"
            aria-labelledby="pills-home-tab"
          >
            <div class="single-product-desc">
              <?=$description?>
            </div>
          </div>
          <!-- second tab-pane -->
          <div
            class="tab-pane fade"
            id="pills-profile"
            role="tabpanel"
            aria-labelledby="pills-profile-tab"
          >
            <div class="single-product-desc">
              <div class="product-anotherinfo-wrapper">
                <ul>
                  <li><span>Weight</span> <?=$weight?></li>
                  <li><span>Dimensions</span><?=$dimensions?></li>
                  <li><span>Materials</span> <?=$materials?></li>
                  <li>
                    <span>Other Info</span> <?=$other_info?>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <!-- third tab-pane -->
          <div
            class="tab-pane fade show active"
            id="pills-contact"
            role="tabpanel"
            aria-labelledby="pills-contact-tab"
          >
            <div class="single-product-desc">
              <div class="row">
                <div class="col-lg-7">
                    <?php
                      $noReviews = "";
                      $reviews = [];
                      $sql = "SELECT r.*, u.username
                              FROM reviews r
                              LEFT JOIN users u ON r.user_id = u.id
                              WHERE r.product_id = $productId
                              ORDER BY r.id DESC"; // Change the ORDER BY clause as needed

                      $result = $conn->query($sql);

                      if ($result->num_rows > 0) {
                          $imageIndex = 1;

                          while ($row = $result->fetch_assoc()) {
                              $reviewerName = $row['username'] ?? 'Guest'; // Use 'Guest' if not logged in
                              $rating = $row['rating'];
                              $comment = $row['comment'];
                              $profileImage = "assets/img/testimonial-image/" . ($imageIndex % 2 + 1) . ".png";

                              // Create an array with review details
                              $reviews[] = [
                                  'reviewerName' => $reviewerName,
                                  'rating' => $rating,
                                  'comment' => $comment,
                                  'profileImage' => $profileImage,
                              ];

                              $imageIndex++;
                          }

                          $conn->close();
                      } else {
                          $noReviews = 'Be the first to review!';
                      }
                      ?>
                  <div class="review-wrapper">
                    <?=$noReviews?>

                  </div>

                  <!-- Pagination -->
                <div class="pagination-section mt-30">
                    <ul class="pagination justify-content-center" id="pagination-container"></ul>
                </div>

                </div>
                <div class="col-lg-5">
                  <div class="ratting-form-wrapper">
                    <h3>Add a Review</h3>
                    <div class="ratting-form">
                      <form id="ratingForm" action="#" method="post">
                        <!-- Add this input in your form -->
                        <input type="hidden" name="rating" id="rating-input1" value="5" required>
                        <input type="hidden" name="product_id" value="<?=$productId?>" required>
                        <div class="star-box">
                            <span>Your rating:</span>
                            <div class="rating-product-custom1" style="cursor:pointer;">
                                <span class="star-on"><i style="color: gold" class="ion-ios-star"></i></span>
                                <span class="star-on"><i style="color: gold" class="ion-ios-star"></i></span>
                                <span class="star-on"><i style="color: gold" class="ion-ios-star"></i></span>
                                <span class="star-on"><i style="color: gold" class="ion-ios-star"></i></span>
                                <span class="star-on"><i style="color: gold" class="ion-ios-star"></i></span>
                            </div>
                        </div>
                        <div class="row">
                          <?php if (isset($_SESSION['user_id'])) : ?>
                            <!-- If user is logged in, pre-populate with session username and email -->
                            <div class="col-md-6">
                                <div class="rating-form-style mb-10">
                                    <input placeholder="Name" type="text" value="<?= $_SESSION['username'] ?>" name="name" readonly required />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="rating-form-style mb-10">
                                    <input placeholder="Email" type="email" value="<?= $_SESSION['email'] ?>" name="email" readonly required />
                                </div>
                            </div>
                          <?php else : ?>
                            <!-- If user is not logged in, provide inputs for name and email -->
                            <div class="col-md-6">
                                <div class="rating-form-style mb-10">
                                    <input placeholder="Name" type="text" name="name" required />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="rating-form-style mb-10">
                                    <input placeholder="Email" type="email" name="email" required />
                                </div>
                            </div>
                        <?php endif; ?>
                          <div class="col-md-12">
                            <div class="rating-form-style form-submit">
                              <textarea
                                name="review"
                                placeholder="Message"
                              ></textarea>
                              <input type="submit" value="Submit" />
                            </div>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- product tab end -->



<!-- product tab start -->
<section class="product-tab bg-white pt-30 pb-30">
  <div class="container">
    <!-- product-tab-nav end -->
    <div class="row">
      <div class="col-12">
          <div class="section-title text-center">
              <h2 class="title pb-3 mb-3">You might also like</h2>
              <p class="text mt-10">Add these products to your weekly line up</p>
          </div>
      </div>
      <div class="col-12">
          <div class="generated_products" id="generated_products"></div>
      </div>
    </div>
  </div>
</section>
<!-- product tab end -->

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
  

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

  // Loop through each product and create HTML elements
  $.each(products, function (index, product) {
    var defaultImageUrl = 'assets/img/product/1.png';

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
                <a href="product?id=${product.id}">
                  <img
                    class="first-img"
                    src="${product.image_url || defaultImageUrl}"
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
                      data-toggle="modal"
                      data-target="#compare"
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
                      data-target="#quick-view123"
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
                      ${product.discount_rate > 0 ? `<del class="del">R${product.price}</del>` : ''}
                      <span class="onsale">R${(product.price - product.discount_amount).toFixed(2)}</span>
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





<!-- modals start -->
<!-- first modal -->
<div
  class="modal fade theme1 style1"
  id="quick-view123"
  tabindex="-1"
  role="dialog"
>
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
        <div class="row">
          <div class="col-md-8 mx-auto col-lg-5 mb-5 mb-lg-0">
            <div class="product-sync-init mb-20">
              <div class="single-product">
                <div class="product-thumb">
                  <img
                    src="assets/img/slider/thumb/1.jpg"
                    alt="product-thumb"
                  />
                </div>
              </div>
              <!-- single-product end -->
              <div class="single-product">
                <div class="product-thumb">
                  <img
                    src="assets/img/slider/thumb/2.jpg"
                    alt="product-thumb"
                  />
                </div>
              </div>
              <!-- single-product end -->
              <div class="single-product">
                <div class="product-thumb">
                  <img
                    src="assets/img/slider/thumb/3.jpg"
                    alt="product-thumb"
                  />
                </div>
              </div>
              <!-- single-product end -->
              <div class="single-product">
                <div class="product-thumb">
                  <img
                    src="assets/img/slider/thumb/4.jpg"
                    alt="product-thumb"
                  />
                </div>
              </div>
              <!-- single-product end -->
            </div>

            <div class="product-sync-nav">
              <div class="single-product">
                <div class="product-thumb">
                  <a href="javascript:void(0)">
                    <img
                      src="assets/img/slider/thumb/1.1.jpg"
                      alt="product-thumb"
                  /></a>
                </div>
              </div>
              <!-- single-product end -->
              <div class="single-product">
                <div class="product-thumb">
                  <a href="javascript:void(0)">
                    <img
                      src="assets/img/slider/thumb/2.1.jpg"
                      alt="product-thumb"
                  /></a>
                </div>
              </div>
              <!-- single-product end -->
              <div class="single-product">
                <div class="product-thumb">
                  <a href="javascript:void(0)"
                    ><img
                      src="assets/img/slider/thumb/3.1.jpg"
                      alt="product-thumb"
                  /></a>
                </div>
              </div>
              <!-- single-product end -->
              <div class="single-product">
                <div class="product-thumb">
                  <a href="javascript:void(0)"
                    ><img
                      src="assets/img/slider/thumb/4.1.jpg"
                      alt="product-thumb"
                  /></a>
                </div>
              </div>
              <!-- single-product end -->
            </div>
          </div>
          <div class="col-lg-7">
            <div class="modal-product-info">
              <div class="product-head">
                <h2 class="title">
                  New Balance Running Arishi trainers in triple
                </h2>
                <h4 class="sub-title">Reference: demo_5</h4>
                <div class="star-content mb-20">
                  <span class="star-on"><i class="fas fa-star"></i> </span>
                  <span class="star-on"><i class="fas fa-star"></i> </span>
                  <span class="star-on"><i class="fas fa-star"></i> </span>
                  <span class="star-on"><i class="fas fa-star"></i> </span>
                  <span class="star-on de-selected"
                    ><i class="fas fa-star"></i>
                  </span>
                </div>
              </div>
              <div class="product-body">
                <span class="product-price text-center">
                  <span class="new-price">$29.00</span>
                </span>
                <p>
                  Break old records and make new goals in the New Balance®
                  Arishi Sport v1.
                </p>
                <ul>
                  <li>Predecessor: None.</li>
                  <li>Support Type: Neutral.</li>
                  <li>Cushioning: High energizing cushioning.</li>
                </ul>
              </div>
              <div class="d-flex mt-30">
                <div class="product-size">
                  <h3 class="title">Dimension</h3>
                  <select>
                    <option value="0">40x60cm</option>
                    <option value="1">60x90cm</option>
                    <option value="2">80x120cm</option>
                  </select>
                </div>
              </div>
              <div class="product-footer">
                <div
                  class="product-count style d-flex flex-column flex-sm-row my-4"
                >
                  <div class="count d-flex">
                    <input type="number" min="1" max="10" step="1" value="1" />
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
                    <button class="btn btn-dark btn--xl mt-5 mt-sm-0">
                      <span class="mr-2"><i class="ion-android-add"></i></span>
                      Add to cart
                    </button>
                  </div>
                </div>
                <div class="addto-whish-list">
                  <a href="#"><i class="icon-heart"></i> Add to wishlist</a>
                  <a href="#"><i class="icon-shuffle"></i> Add to compare</a>
                </div>
                <div class="pro-social-links mt-10">
                  <ul class="d-flex align-items-center">
                    <li class="share">Share</li>
                    <li>
                      <a href="#"><i class="ion-social-facebook"></i></a>
                    </li>
                    <li>
                      <a href="#"><i class="ion-social-twitter"></i></a>
                    </li>
                    <li>
                      <a href="#"><i class="ion-social-google"></i></a>
                    </li>
                    <li>
                      <a href="#"><i class="ion-social-pinterest"></i></a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
  

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
<footer class="bg-dark theme1 theme2 position-relative compass text-center">

  <!-- footer bottom start -->
  <div class="footer-bottom pt-80 pb-100">
    <div class="container container1">
      <div class="row">
        <div class="col-12 col-sm-6 col-lg-6 mb-30">
          <div class="footer-widget mx-w-400">
<div class="footer-logo mb-25">
    <a href="https://www.fishgelatine.co.za/v2">
      <img src="<?=$home_directory?>assets/img/footer-image1.png" alt="footer logo" width="200px" />
    </a>
  </div>

 <div class="social-network">
    <ul class="d-flex justify-content-center"> <!-- Added justify-content-center class -->
      <li>
        <a href="https://www.facebook.com/marinecollagenSA" target="_blank" aria-label="Sir Francis on Facebook"><span class="icon-social-facebook"></span></a>
      </li>
      <li class="mr-0">
        <a href="https://www.instagram.com/fishgelatine" target="_blank" aria-label="Sir Francis on Instagram"><span class="icon-social-instagram"></span></a>
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
<div class="coppy-right bg-dark py-15 ">
  <div class="container container1">
    <div class="row">
      <div class="col-12">
        <div class="d-flex flex-column align-items-center text-center pt-3">
          <p>
            Copyright &copy; <a href="https://www.fishgelatine.co.za/v2">Sir Francis</a>.
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
    color: #CEBD88 !important;
  }

  
</style>

  <!-- coppy-right end -->
</footer>
<!-- footer end -->

<!-- modals start -->
<!-- first modal -->
<div
  class="modal fade theme1 style1"
  id="quick-view"
  tabindex="-1"
  role="dialog"
>
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
        <div class="row">
          <div class="col-md-8 mx-auto col-lg-5 mb-5 mb-lg-0 quick-view-image-wrapper">
            <!-- dynamic content -->
          </div>
          <div class="col-lg-7">
            <div class="modal-product-info">
              <div class="product-head">
                  <!-- dynamic content -->
              </div>
              <div class="product-body">
                <!-- dynamic content -->
              </div>
              <div class="d-flex mt-30">
                <div class="product-size">
                </div>
              </div>
              <div class="product-footer">
                <!-- dynamic content -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
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
<!-- second modal -->
<div class="modal fade style3" id="add-to-cart" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header justify-content-center bg-dark">
        <h5 class="modal-title" id="add-to-cartCenterTitle">
          Product successfully added to your shopping cart
        </h5>
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
        <div class="row">
          <div class="col-lg-5 divide-right">
            <div class="row">
              <div class="col-md-6">
                <img class="product-image" src="<?=$home_directory?>assets/img/modal/1.jpg" alt="img" />
              </div>
              <div class="col-md-6 mb-2 mb-md-0">
                <h4 class="product-name">
                  Item
                </h4>
                <h5 class="price">R0.00</h5>
              </div>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="modal-cart-content">
              <p class="cart-products-count">There is 1 item in your cart.</p>
              <p><strong>Total:</strong>&nbsp;<span class="grand_total">R0.00</span></p>
              <p><strong>Shipping calculated at checkout</strong></p>
              <div class="cart-content-btn">
                <button
                  type="button"
                  class="btn btn-dark btn--md mt-4"
                  data-dismiss="modal"
                >
                  Continue shopping
                </button>
                <a href="checkout" class="btn btn-dark btn--md mt-4">
                  Proceed to checkout
                </a>
              </div>
            </div>
          </div>
        </div>
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
    <input type="text" name="search" placeholder="Search products..." />
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

  // Loop through each product and create HTML elements
  $.each(products, function (index, product) {
    var defaultImageUrl = 'assets/img/product/1.png';

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
                <a href="product?id=${product.id}">
                  <img
                    class="first-img"
                    src="${product.image_url || defaultImageUrl}"
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
                      data-toggle="modal"
                      data-target="#compare"
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
                      ${product.discount_rate > 0 ? `<del class="del">R${product.price}</del>` : ''}
                      <span class="onsale">R${(product.price - product.discount_amount).toFixed(2)}</span>
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


<script>



$(document).ready(function () {

    $('#subscribe_form').on('submit', function(e){
      e.preventDefault();
        var email = $('#subscribe_email').val();

        // AJAX Request using jQuery
        $.ajax({
            type: 'POST',
            url: ' https://www.fishgelatine.co.za/v2/subscribe.inc.php',
            data: { email: email },
            dataType: 'json',
            success: function (response) {
                // Handle the JSON response
                showNotification(response.success, response.message);
            },
            error: function () {
                console.log('Error: Unable to process your request.');
            }
        });
    });

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






/*QUICK VIEW*/

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
        prevArrow: '<button class="slick-prev"><i class="fas fa-arrow-left"></i></button>',
        nextArrow: '<button class="slick-next"><i class="fas fa-arrow-right"></i></button>',
        slidesToShow: 4,
        slidesToScroll: 1,
        asNavFor: ".product-sync-init",
        focusOnSelect: true,
        draggable: false
    });
}

$(document).ready(function () {
    $('body').on('click', '.open-quick-view', function (e) {
        e.preventDefault(); // Prevent the default link behavior

        var productId = $(this).data('product-id');
        console.log("productId: " + productId);
        loadProductDetails(productId);
    });


    // Add event listener for change event on #changeModalProduct
    $('#quick-view123').on('change', '#changeModalProduct', function () {
        var selectedProductId = $(this).val();
        loadProductDetails(selectedProductId);
    });

    // Add a click event listener for closing the modal
    $('#quick-view123').on('hidden.bs.modal', function () {
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
          console.log(data);
            // Update the modal with the received data
            updateModal(data);
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


    $('#quick-view123 .modal-body .quick-view-image-wrapper').html(`
          <div class="product-sync-init mb-20">
            ${productImagesHtml}
          </div>

          <div class="product-sync-nav">
            ${productThumbnailImagesHtml}
          </div>
    `);

    initializeSlick();

    // Convert price to a number and then format it
    var formattedPrice = parseFloat(productData.price).toFixed(2);
    var price1 = parseFloat(productData.price).toFixed(2);
    var discount_amount = parseFloat(productData.discount_amount).toFixed(2);
    var discountedprice1 = (price1 - discount_amount).toFixed(2);

    
    var productTitle = productData.title;
    var productId = productData.id;
    var productCategory = productData.category_name;
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
    $('#quick-view123 h2.title').text(productData.title);

    $('#quick-view123 .product-head').html(`
        <h2 class="title">
          ${productTitle}
        </h2>
        <h4 class="sub-title">${productCategory}</h4>
        <div class="star-content mb-20">
          ${convertToStars(rating)}
        </div>
    `);

    $('#quick-view123 .product-footer').html(`
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
              <a href="https://www.facebook.com/sharer/sharer.php?u=https://www.fishgelatine.co.za/v2/product?id=${productId}" target="_blank" rel="noopener noreferrer"><i class="ion-social-facebook"></i></a>
            </li>
            <li>
              <a href="https://twitter.com/intent/tweet?url=https://www.fishgelatine.co.za/v2/product?id=${productId}&text=Check out this amazing product!" target="_blank" rel="noopener noreferrer"><i class="ion-social-twitter"></i></a>
            </li>
            <li>
              <a target="_blank" href="https://www.pinterest.com/pin/create/button/"
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

    var priceDisplay = '';

    if (productData.discounted_rate > 0) {
        priceDisplay = `
            <span class="new-price mr-20">
                <del class="del">R${price1}</del>
                <span class="onsale">R${discountedprice1}</span>
            </span>
        `;
    } else {
        priceDisplay = `<span class="new-price">R${price1}</span>`;
    }

    $('#quick-view123 .product-body').html(`
        <span class="product-price text-center">
            ${priceDisplay}
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

        $('#quick-view123 .product-size').html(`
            <h3 class="title" style="font-weight:900">SELECT PRODUCT SIZE:</h3>
            <select id="changeModalProduct">
                ${dimensionOptions}
            </select>
        `);
    }
}

function clearModalContent() {
    // Clear or reset the content of your modal elements
    $('#quick-view123 h2.title').text('');
    $('#quick-view123 .product-head').html('');
    $('#quick-view123 .product-footer').html('');
    $('#quick-view123 .product-body').html('');
    $('#quick-view123 .product-size').html('');
}
</script>

</body>

</html>
