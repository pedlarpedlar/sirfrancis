
<?php
include 'session_logins.php';



include 'header.php';
?>

<?php
$page_url_canonical = "https://www.fishgelatine.co.za/v2/thankyou";
$title_og = 'Thank you for shopping on Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/thankyou"
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


<title>Thank you for shopping on Sir Francis</title>

<?php
include 'page_menues.php';
?>

<!-- breadcrumb-section start -->
<nav class="breadcrumb-section theme1 bg-lighten2 pt-110 pb-110">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class='pb-30 text-center'>
          <h2>Thank you for shopping with us!</h2>
          <p>We received your order & payment. We will send your order updates and tracking information via email. Consider <a href="login" target="_blank">registering an account with us</a> to see your order history and any other details :-)</p>
        </div>
      </div>
      <div class="col-12">
        <ol
          class="breadcrumb bg-transparent m-0 p-0 align-items-center justify-content-center"
        >
          <li class="breadcrumb-item"><a href="index">Back Home</a></li>
        </ol>
      </div>
    </div>
  </div>
</nav>
<!-- breadcrumb-section end -->

<?php
include "footer.php";
?>