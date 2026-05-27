<?php
include 'session_logins.php';
include 'header.php';


// Fetch compare items based on user or guest
$compareItems = getCompareItems($userId, $guestIdentifier);

// Format compare items
$compare_header = '<tr>
                    <th scope="col">product info</th>';
$compare_body = "";

foreach ($compareItems as $item) {
    $image_url = isset($item['image_url']) ? $item['image_url'] : 'assets/img/product/1.png';

    $compare_header .= '<th scope="col" class="text-center">
                          <img src="' . $image_url . '" alt="img" />
                          <span class="sub-title d-block">' . $item['title'] . '</span>
                          <a href="#" class="btn btn-dark btn--lg add-to-cart"
                              data-toggle="modal"
                              data-target="#add-to-cart"
                              data-quantity="1"
                              data-product-id="' . $item['id'] . '">
                              add to cart
                          </a>
                      </th>';
}

$compare_header .= '</tr>';

?>

<?php
$page_url_canonical = "https://www.candybird.co.za/compare";
$title_og = 'Compare Items - CandyBird';
$page_url_og = "https://www.candybird.co.za/compare"
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

<title>Compare Items - CandyBird</title>

<?php
include 'page_menues.php';
?>

<!-- product tab start -->
<section class="compare-section theme1 pt-80 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <h3 class="title mb-30 pb-25 text-capitalize">compare</h3>
        <div class="table-responsive">
          <table class="table">
            <thead class="thead-light">
              
              <?=$compare_header?>

            </thead>
            <tbody>

              <tr>
        <th scope="row">Price</th>
        <?php foreach ($compareItems as $item): ?>

          <?php

          $price = $item['price'];
          $discount_rate = $item['discount_rate'];
          $discount = $item['discount_amount'];

          if ($discount == 0 && $discount_rate > 0) {
              $discount = ($price * $discount_rate) / 100;
          }

          $discounted_price = $price - $discount;

          ?>
            <td class="text-center">
                <?php
                    echo "<span class='whish-list-price'>";

                    if ($item['discount_rate'] > 0) {
                        echo "<del class='del'>R".number_format($item['price'], 2)."</del>";
                        echo "<span class='onsale'>R".number_format($discounted_price, 2)."</span>";
                    } else {
                        echo "R".number_format($item['price'], 2);
                    }

                    echo "</span>";
                ?>

            </td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Description</th>
        <?php foreach ($compareItems as $item): ?>
          <?php
          $limitedDescription = strlen($item['description']) > 200 ? trim(strip_tags(substr($item['description'], 0, 200) . '...')) : trim(strip_tags($item['description']));
          ?>
            <td class="text-center" style="max-width:200px !important">
                <p><?=$limitedDescription?></p>
            </td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Availability</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center">
                <span class="badge badge-danger position-static">In Stock</span>
            </td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Weight</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center"><?=$item['weight']?></td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Other Info</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center"><?=$item['other_info']?></td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Properties</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center"><?=$item['features']?></td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <th scope="row">Actions</th>
        <?php foreach ($compareItems as $item): ?>
            <td class="text-center">
            <a href="#" class="btn btn--lg remove-from-compare"
                  data-product-id="<?= $item['id'] ?>">
                  Remove from Compare
              </a>
          </td>
        <?php endforeach; ?>
    </tr>

            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- product tab end -->


<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
     $('body').on('click', '.remove-from-compare', function (e) {
      e.preventDefault();
      // Retrieve product ID from data attribute
      var productId = $(this).data('product-id');
      console.log('Product ID:', productId); // Log to console for debugging

      // Perform AJAX request
      $.ajax({
          type: 'POST',
          url: 'remove_from_compare.php', // Specify the path to your PHP script
          data: {
              productId: productId
          },
          success: function (response) {
              // Handle the response from the server (optional)
              console.log(response);
              updateBadgeCounts();
              // You can update the UI or show a success message here
              showNotification(response.success, response.message);
          },
          error: function (error) {
              // Handle errors (optional)
              console.error('Error:', error);
          }
      });
  });

</script>

<?php
include 'footer.php';
?>