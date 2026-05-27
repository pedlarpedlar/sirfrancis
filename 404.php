<?php
include 'session_logins.php';

// Set response code to 404
http_response_code(404);
?>

<?php
include 'header.php';
?>

<?php
$page_url_canonical = "https://www.candybird.co.za/404";
$title_og = 'Oops... That page does not exist on CandyBird';
$page_url_og = "https://www.candybird.co.za/404"
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


<title>Oops! Let's try that again... Page Not Found - 404 Error</title>

<?php
include 'page_menues.php';
?>

<!-- breadcrumb-section start -->
<nav class="breadcrumb-section theme1 bg-lighten2 pt-110 pb-110">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="section-title text-center">
          <h2 class="title pb-4 text-dark text-capitalize">Oops... Seems like this page doesn't exist.</h2>
        </div>
      </div>
      <div class="col-12">
        <ol
          class="breadcrumb bg-transparent m-0 p-0 align-items-center justify-content-center"
        >
          <li class="breadcrumb-item"><a href="https://www.candybird.co.za">Back Home</a></li>
        </ol>
      </div>
    </div>
  </div>
</nav>
<!-- breadcrumb-section end -->

<?php
include "footer.php";
?>
