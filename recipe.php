<?php
include 'session_logins.php';
// Include the file with $blogPosts array

include 'recipe_posts.php';

include 'header.php';
date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2

// Get the recipe ID from the URL
$recipeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Find the recipe in the array
$recipe = null;
foreach ($blogPosts as $post) {
    if ($post['id'] === $recipeId) {
        $recipe = $post;
        break;
    }
}

// If no recipe found, show an error message
if (!$recipe) {
    // echo '<p>Recipe not found.</p>';
    header("Location: recipes");
    exit();
}

$title = $recipe['title'];
$url_title = strip_tags($recipe['title']);

$intro = strip_tags($recipe['intro']);
$recipe_id = htmlspecialchars($recipe['id']);
$author = htmlspecialchars($recipe['author']);
$date = htmlspecialchars($recipe['date']);
$cook_time = htmlspecialchars($recipe['cook_time']);
$servings = htmlspecialchars($recipe['servings']);
$instructions = $recipe['instructions'];
$ingredients = $recipe['ingredients'];
$image = $recipe['img'];
$category = $recipe['category'];

$imageFile = basename((string) $image);
$largeImagePath = __DIR__ . '/assets/img/blog-post/large-blog/' . $imageFile;
$thumbImagePath = __DIR__ . '/assets/img/blog-post/' . $imageFile;
$fallbackImageNumber = (($recipe_id - 1) % 5) + 1;
$displayImage = is_file($largeImagePath)
    ? "assets/img/blog-post/large-blog/" . rawurlencode($imageFile)
    : (is_file($thumbImagePath) ? "assets/img/blog-post/" . rawurlencode($imageFile) : "assets/img/blog-post/large-blog/" . $fallbackImageNumber . ".png");

$image_url_og = "https://www.candybird.co.za/" . $displayImage;

$page_url_canonical = "https://www.candybird.co.za/recipe?id=".$recipe_id;
$title_og = $url_title . ' - CandyBird';
$page_url_og = "https://www.candybird.co.za/recipe?id=".$recipe_id;
$description_og = htmlspecialchars($intro, ENT_QUOTES, 'UTF-8');
$description_meta = htmlspecialchars($intro, ENT_QUOTES, 'UTF-8');
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

<style>
    
    .blog-section ul li {
        list-style-type: disc !important;
    }

    .blog-section ol li {
        list-style-type: bullet !important;
    }

    .blog-section {
        color: black;
    }

    .fraction {
        font-size: 0.8em; /* Makes the fraction smaller */
        line-height: 1.2em; /* Adjusts line height for better spacing */
    }
    .fraction sup {
        font-size: 0.8em; /* Smaller font size for the numerator */
    }
    .fraction sub {
        font-size: 0.8em; /* Smaller font size for the denominator */
    }

.ingredients-columns {
    column-count: 2; /* Number of columns */
    column-gap: 20px; /* Space between columns */
}

/* Optional: Make sure the list items break correctly in the columns */
.ingredients-columns ul {
    padding-left: 15px;
/*    list-style: none;*/
}

.ingredients-columns li {
    margin-bottom: 10px; /* Adjust spacing as needed */
}


@media screen {
    .print-only {
        display: none !important;
    }

}

@media print {

    .blog-image {
        width: 70mm;
        text-align: center;
    }

    body {
        //font-size: 0.7em !important; /* Reduce body font size */
        padding: 0;
        margin: 0;
    }

    h1, h2, h3, h4, h5, h6 {
        font-size: 1.5em !important; /* Reduce heading font sizes */
    }

    p, li, a {
        font-size: 0.7em !important; /* Reduce paragraph font sizes */
    }

    .no-print {
        display: none !important;
    }
}

</style>


<title><?=$url_title?> - CandyBird</title>

<?php

include 'page_menues.php';



// Display the breadcrumbs in the HTML
$breadcrumbs = '<nav class="breadcrumb-section theme1 bg-lighten2 pt-50 pb-50 no-print">';
$breadcrumbs .= '<div class="container">';
$breadcrumbs .= '<div class="row">';
$breadcrumbs .= '<div class="col-12">';
$breadcrumbs .= '<ol class="breadcrumb bg-transparent m-0 p-0 align-items-center justify-content-center">';

$breadcrumbs .= '<li class="breadcrumb-item"><a href="https://www.candybird.co.za">Home</a></li>';
$breadcrumbs .= '<li class="breadcrumb-item"><a href="https://www.candybird.co.za/recipes">Recipes</a></li>';
$breadcrumbs .= '<li class="breadcrumb-item"><a href="https://www.candybird.co.za/recipes?category='.urlencode($category).'">'.htmlspecialchars($category, ENT_QUOTES, 'UTF-8').'</a></li>';
$breadcrumbs .= '<li class="breadcrumb-item">' . $url_title . '</li>';

$breadcrumbs .= '</ol>';
$breadcrumbs .= '</div>';
$breadcrumbs .= '</div>';
$breadcrumbs .= '</div>';
$breadcrumbs .= '</nav>';

echo $breadcrumbs;


?>


<!-- product tab start -->
<section class="blog-section pt-10 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-12 col-lg-10 mx-auto">
        <div class="blog-posts">
          <div class="single-blog-post blog-grid-post">
            <div class="blog-post-media">
              <div class="blog-image single-blog">
                <a href="#"
                  ><img
                    class="object-fit-none"
                    src="<?=$displayImage?>"
                    alt="<?=$url_title?>"
                /></a>
              </div>
            </div>
            <div class="blog-post-content-inner">
              <h4 class="blog-title"><?=$title?></h4>
              <ul class="blog-page-meta no-print">
                <li>
                  <a href="#"><i class="ion-person"></i> <?=$author?></a>
                </li>
                <!-- <li>
                  <a href="#"><i class="ion-calendar"></i> <?=$date?></a>
                </li> -->
                <li>
                  <a href="#" class="print_recipe"><i class="icon-printer"></i> Print Recipe</a>
                </li>
              </ul>
              <ul class="blog-page-meta">
                <li>
                  <a href="#"><i class="icon-pie-chart"></i> Cooking Time: <?=$cook_time?></a>
                </li>
                <li>
                  <a href="#"><i class="icon-cup"></i> Servings: <?=$servings?></a>
                </li>
              </ul>
              <!-- <p>
                Lorem
              </p> -->
            </div>

            <div class="single-post-content">
              <p>
                <?=$intro?>
              </p>
                <h4 class="py-2">INGREDIENTS</h4>
                <div class="ingredients-columns">
                    <?=$ingredients?>
                </div>
                <h4 class="py-2">INSTRUCTIONS</h4>
                <?=$instructions?>
            </div>
          </div>

          <!-- <a href="<?php echo htmlspecialchars($recipe['source']); ?>" class="btn btn-primary mt-4" target="_blank">View Full Recipe</a> -->
          
          <!-- single blog post -->
        </div>
        <div class="blog-single-tags-share d-sm-flex justify-content-between no-print">
          <div class="blog-single-tags d-flex">
            <span class="title">Tags: </span>
            <ul class="tag-list">
              <li><a href="#"><?=$category?></a></li>
            </ul>
          </div>
          <div class="blog-single-share d-flex">
            <span class="title">Share:</span>
            <ul class="social">
              <li>
                <a class="share-link-click" href="https://www.facebook.com/sharer/sharer.php?u=https://www.candybird.co.za/recipe?id=<?=$recipe_id?>" target="_blank"><i class="ion-social-facebook"></i></a>
              </li>
              <li>
                <a class="share-link-click" href="https://twitter.com/intent/tweet?url=https://www.candybird.co.za/recipe?id=<?=$recipe_id?>&text=Check out this recipe!" target="_blank"><i class="ion-social-twitter"></i></a>
              </li>
              <!-- <li>
                <a href="#"><i class="ion-social-google"></i></a>
              </li> -->
              <!-- <li>
                <a href="#"><i class="ion-social-instagram"></i></a>
              </li> -->
            </ul>
          </div>
        </div>


        <div class="blog-single-tags-share d-sm-flex justify-content-between print-only mt-15">
          <div class="blog-single-tags d-flex">
            <span class="title">Love this recipe? Find more at www.candybird.co.za</span>
            <!-- <ul class="tag-list">
              <li><a href="#"><?=$category?></a></li>
            </ul> -->
          </div>
        </div>
        
        <!-- <div class="comment-area">
          <h2 class="comment-heading">3 Comments</h2>
          <div class="review-wrapper">
            <div class="single-review">
              <div class="review-img">
                <img src="assets/img/testimonial-image/1.png" alt="" />
              </div>
              <div class="review-content">
                <div class="review-top-wrap">
                  <div class="review-left">
                    <div class="review-name">
                      <h4>White Lewis</h4>
                      <span class="date">Nov 16, 2020 at 1:38 am</span>
                    </div>
                  </div>
                  <div class="review-left">
                    <a href="#">Reply</a>
                  </div>
                </div>
                <div class="review-bottom">
                  <p>
                    Vestibulum ante ipsum primis aucibus orci luctustrices
                    posuere cubilia Curae Suspendisse viverra ed viverra. Mauris
                    ullarper euismod vehicula. Phasellus quam nisi, congue id
                    nulla.
                  </p>
                </div>
              </div>
            </div>
            <div class="single-review child-review">
              <div class="review-img">
                <img src="assets/img/testimonial-image/2.png" alt="" />
              </div>
              <div class="review-content">
                <div class="review-top-wrap">
                  <div class="review-left">
                    <div class="review-name">
                      <h4>White Lewis</h4>
                      <span class="date">Nov 16, 2020 at 1:38 am</span>
                    </div>
                  </div>
                  <div class="review-left">
                    <a href="#">Reply</a>
                  </div>
                </div>
                <div class="review-bottom">
                  <p>
                    Vestibulum ante ipsum primis aucibus orci luctustrices
                    posuere cubilia Curae Sus pen disse viverra ed viverra.
                    Mauris ullarper euismod vehicula.
                  </p>
                </div>
              </div>
            </div>
            <div class="single-review">
              <div class="review-img">
                <img src="assets/img/testimonial-image/1.png" alt="" />
              </div>
              <div class="review-content">
                <div class="review-top-wrap">
                  <div class="review-left">
                    <div class="review-name">
                      <h4>White Lewis</h4>
                      <span class="date">Nov 16, 2020 at 1:38 am</span>
                    </div>
                  </div>
                  <div class="review-left">
                    <a href="#">Reply</a>
                  </div>
                </div>
                <div class="review-bottom">
                  <p>
                    Vestibulum ante ipsum primis aucibus orci luctustrices
                    posuere cubilia Curae Suspendisse viverra ed viverra. Mauris
                    ullarper euismod vehicula. Phasellus quam nisi, congue id
                    nulla.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div> -->


        <!-- <div class="blog-comment-form">
          <h2 class="comment-heading">Leave a Reply</h2>
          <p>
            Your email address will not be published. Required fields are marked
            *
          </p>
          <div class="row">
            <div class="col-md-12">
              <div class="single-form">
                <label>Your Review:</label>
                <textarea placeholder="Write a review"></textarea>
              </div>
            </div>
            <div class="col-md-4">
              <div class="single-form">
                <label>Name:</label>
                <input type="text" placeholder="Name" />
              </div>
            </div>
            <div class="col-md-4">
              <div class="single-form">
                <label>Email:</label>
                <input type="email" placeholder="Email" />
              </div>
            </div>
            <div class="col-md-4">
              <div class="single-form">
                <label>Website:</label>
                <input type="email" placeholder="Website" />
              </div>
            </div>
            <div class="col-md-12">
              <div class="single-form">
                <input type="submit" value="Submit" />
              </div>
            </div>
          </div>
        </div> -->




      </div>
    </div>
  </div>
</section>
<!-- product tab end -->

<!-- product tab start -->
<section class="product-tab bg-white pt-30 pb-30 no-print">
<div class="container">
  <!-- product-tab-nav end -->
  <div class="row">
    <div class="col-12">
        <div class="section-title text-center">
            <h2 class="title pb-3 mb-3">Have a look at these products</h2>
            <p class="text mt-10">Have a look at these products to add your weekly line up</p>
        </div>
    </div>
    <div class="col-12">
        <div class="generated_products" id="generated_products"></div>
    </div>
  </div>
</div>
</section>
<!-- product tab end -->


<?php

include 'footer.php';

?>

<?php
//for generated_products
include 'generate_products_script.php';
?>
