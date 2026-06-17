<?php
date_default_timezone_set('Africa/Johannesburg');

//<!-- Variables for admin assets -->
$home_directory = '../';

// Check if a session is not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_session_id = 0;
// Now PHP will use GMT+2 timezone for date and time functions
// $currentTime = date('Y-m-d H:i:s'); // Example of getting current time in GMT+2 timezone

// echo $currentTime;

// Use $currentTime in your SQL queries or wherever necessary


include __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

// Fetch website configurations from the database
$getWebsiteSettings = "SELECT * FROM admin_website_settings";
$resultWebsiteSettings = $conn->query($getWebsiteSettings);

if ($resultWebsiteSettings) {
    // Fetch the row as an associative array
    $settings = $resultWebsiteSettings->fetch_assoc();

    // Assign values to variables
    $free_shipping_amount = getCandybirdFreeShippingAmount($settings['free_shipping_amount'] ?? null);
    $tel = $settings['tel'];
    $website_email = $settings['email_1'];
    $website_address = $settings['address'];
    // Convert the products_on_homepage string to an array
    $productIds = explode(',', $settings['products_on_homepage']);
} else {
    // Handle the case where the query fails
    echo "Error fetching website configurations: " . $conn->error;
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="description" content="" />
    <title>ADMIN PANEL - Sir Francis</title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?=$home_directory?>assets/img/favicon.png" />

    <!--********************************** 
        all css files 
    *************************************-->

    <!--*************************************************** 
       fontawesome,bootstrap,plugins and main style css
     ***************************************************-->
    <!-- cdn links -->

    <link rel="stylesheet" href="<?=$home_directory?>assets/css/fontawesome.min.css" />
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/ionicons.min.css" />
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/simple-line-icons.css" />
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/plugins/jquery-ui.min.css">
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/plugins/plugins.css" />
    <!-- <link rel="stylesheet" href="<?=$home_directory?>assets/css/plugins/aos.css" /> -->
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/style.css" />
    <link rel="stylesheet" href="admin-theme.css" />
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <!-- Use the minified version files listed below for better performance and remove the files listed above -->

    <!--**************************** 
         Minified  css 
    ****************************-->

    <!--*********************************************** 
       vendor min css,plugins min css,style min css
     ***********************************************-->
    <!-- <link rel="stylesheet" href="<?=$home_directory?>assets/css/vendor/vendor.min.css" />
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/plugins/plugins.min.css" />
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/style.min.css" /> -->

