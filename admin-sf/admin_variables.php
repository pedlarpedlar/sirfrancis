<?php
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
    $website_email2 = $settings['email_2'];
    $website_address = $settings['address'];
    $headquarters = $settings['headquarters'];
    $hotline = $settings['hotline'];
    $banking_details = nl2br($settings['banking_details']);
    
    
    $productIdsString = $settings['products_on_homepage'];
    if (!empty($productIdsString)) {
        $productIds = array_map('trim', explode(',', $productIdsString));
    } else {
        $productIds = []; // Set to an empty array if no IDs are found
    }


    $support_email = $settings['email_1'];
    $website_company_name = "Sir Francis"; // For Privacy Policy, Terms, etc
}
