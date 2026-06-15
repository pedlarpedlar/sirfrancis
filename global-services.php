<?php
include 'session_logins.php';
date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2

include 'header.php';

$page_url_canonical = "https://www.fishgelatine.co.za/v2/global-services";
$title_og = 'Global Services and Delivery - Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/global-services";
$description_og = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');
$description_meta = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');

include 'page_menues.php';

?>

  <style>
    .container {
      color: black;
    }

    .global-services-header {
      background-color: #f8f9fa;
      padding: 60px 0;
      text-align: center;
    }
    .global-services-header h2 {
      margin-bottom: 20px;
    }
    .global-services-content {
      padding: 40px 0;
    }
    .global-services-contact {
      background-color: #e9ecef;
      padding: 40px 0;
    }
    .global-services-contact h3 {
      margin-bottom: 20px;
    }
  </style>


<div class="pt-30 pb-50">
  <div class="container">

  <section class="global-services-header">
    <div class="container">
      <h2 class="title">Global Services by Sir Francis</h2>
      <p class="subtitle">Expanding Our Reach to Serve You Worldwide</p>
    </div>
  </section>

  <section class="global-services-content">
    <div class="container">
      <h3 class="py-4">Our Global Reach</h3>
      <div class="row">
        <div class="col-md-12 col-lg-4 mb-4">
          <h5>International Shipping</h5>
          <p>We provide reliable shipping solutions to ensure your orders reach you efficiently, wherever you are.</p>
        </div>
        <div class="col-md-12 col-lg-4 mb-4">
          <h5>Local Partnerships</h5>
          <p>We work with local distributors and partners to provide you with the best service and support in your region.</p>
        </div>
        <div class="col-md-12 col-lg-4 mb-4">
          <h5>Customized Solutions</h5>
          <p>Whether you need specific packaging or tailored products, we offer customization options to meet your unique requirements.</p>
        </div>
      </div>

      <h3 class="py-4">Global Service Highlights</h3>
      <div class="row">
        <div class="col-md-12 col-lg-4 mb-4">
          <h5>Efficient Logistics</h5>
          <p>Our streamlined logistics ensure timely delivery and minimal transit time, no matter your location.</p>
        </div>
        <div class="col-md-12 col-lg-4 mb-4">
          <h5>Comprehensive Support</h5>
          <p>Our global team is available to assist you with all aspects of your order, from product selection to post-sale support.</p>
        </div>
        <div class="col-md-12 col-lg-4 mb-4">
          <h5>Quality Assurance</h5>
          <p>We maintain stringent quality control standards to ensure our products meet international regulations and expectations.</p>
        </div>
      </div>
    </div>
</section>


  <section class="global-services-contact">
    <div class="container">
      <h3 class="py-4">FAQs</h3>
      <div class="row">
        <div class="col-md-6 mb-3">
          <h5>Do you offer product samples?</h5>
          <p>Yes, we provide samples for evaluation purposes. Contact us to request samples of our products.</p>
        </div>
        <div class="col-md-6 mb-3">
          <h5>What are your payment terms for international orders?</h5>
          <p>Payment terms are flexible and vary based on order size and destination. We will discuss terms during the ordering process.</p>
        </div>
      </div>
    </div>
  </section>

</div>
</div>

<?php

include 'footer.php';

?>