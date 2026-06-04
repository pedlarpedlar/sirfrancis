<?php
//<!-- Variables for localhost/live -->
$is_localhost = isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'], true);
$home_directory = $is_localhost ? './' : 'https://www.candybird.co.za/';

date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2
// Now PHP will use GMT+2 timezone for date and time functions
// $currentTime = date('Y-m-d H:i:s'); // Example of getting current time in GMT+2 timezone

// echo $currentTime;

//Set defaults. Pages may define these before including the shared header.
$page_url_canonical = $page_url_canonical ?? 'https://www.candybird.co.za/';
$title_og = $title_og ?? 'CandyBird | Premium Nuts, Dried Fruit, Sweets and Gifting';
$image_url_og = $image_url_og ?? 'https://www.candybird.co.za/assets/img/product/1.png';
$image_type_og = $image_type_og ?? 'image/png';
$image_width_og = $image_width_og ?? '1200';
$image_height_og = $image_height_og ?? '630';
$og_type = $og_type ?? 'website';
$page_url_og = $page_url_og ?? 'https://www.candybird.co.za/';
$description_og = $description_og ?? "Shop CandyBird in Port Elizabeth for premium nut packs, quality nuts, dried fruit, sweets, unique gifting, wholesale and private labelling. Secure online checkout with delivery and collection options across South Africa.";
$description_meta = $description_meta ?? "Shop CandyBird in Port Elizabeth for premium nut packs, quality nuts, dried fruit, sweets, unique gifting, wholesale and private labelling. Secure online checkout with delivery and collection options across South Africa.";


?>

<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <!-- Robots Meta Tag (index/follow or noindex/nofollow) -->
    <meta name="robots" content="index, follow">

    <!-- Author and Keywords Meta Tags -->
    <meta name="author" content="CandyBird">
    <meta name="keywords" content="nut packs, quality nuts South Africa, nuts Port Elizabeth, dried fruit South Africa, unique gifting South Africa, corporate gifting South Africa, gift hampers South Africa, send gifts to South Africa, nuts online South Africa, private labelling nuts">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.candybird.co.za/assets/img/favicon.png">
    <link rel="shortcut icon" type="image/png" href="https://www.candybird.co.za/assets/img/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://www.candybird.co.za/assets/img/favicon.png">
    <meta name="theme-color" content="#2a1b1b">
    <link rel="canonical" href="<?= htmlspecialchars($page_url_canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="description" content="<?= htmlspecialchars($description_meta, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="<?= htmlspecialchars($og_type, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($page_url_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($image_url_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($image_url_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:type" content="<?= htmlspecialchars($image_type_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width" content="<?= htmlspecialchars((string) $image_width_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:height" content="<?= htmlspecialchars((string) $image_height_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:alt" content="<?= htmlspecialchars($title_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($image_url_og, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image:alt" content="<?= htmlspecialchars($title_og, ENT_QUOTES, 'UTF-8') ?>">
    <?php
    $siteSchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => ['Organization', 'Store'],
                '@id' => 'https://www.candybird.co.za/#organization',
                'name' => 'CandyBird',
                'url' => 'https://www.candybird.co.za/',
                'logo' => 'https://www.candybird.co.za/assets/img/logo/logo.png',
                'image' => 'https://www.candybird.co.za/assets/img/product/1.png',
                'description' => 'CandyBird supplies premium nut packs, quality nuts, dried fruit, sweets, unique gifting, wholesale and private labelling from Port Elizabeth, South Africa. Overseas customers can order online to send gifts to family, friends and clients in South Africa.',
                'email' => 'consumer@candybird.co.za',
                'telephone' => '+27410011786',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => '18 Babiana Road, Malabar',
                    'addressLocality' => 'Port Elizabeth',
                    'addressRegion' => 'Eastern Cape',
                    'addressCountry' => 'ZA',
                ],
                'areaServed' => [
                    ['@type' => 'Country', 'name' => 'South Africa'],
                ],
                'knowsAbout' => [
                    'Nut packs',
                    'Quality nuts',
                    'Dried fruit',
                    'Unique gifting',
                    'Corporate gifting',
                    'Wholesale nuts',
                    'Private labelling',
                    'Gifts delivered in South Africa',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => 'https://www.candybird.co.za/#website',
                'name' => 'CandyBird',
                'url' => 'https://www.candybird.co.za/',
                'publisher' => ['@id' => 'https://www.candybird.co.za/#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => 'https://www.candybird.co.za/products?search={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ];
    ?>
    <script type="application/ld+json"><?= json_encode($siteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

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
    <link rel="stylesheet" href="<?=$home_directory?>assets/css/style.css?v=<?=filemtime(__DIR__ . '/assets/css/style.css')?>" />

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


    <!-- <link href="https://db.onlinewebfonts.com/c/e8e8b5680c6dcf4c67b4cdc488d9696f?family=ltx-holamed" rel="stylesheet"> -->

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Antonio:wght@100..700&family=Hanken+Grotesk:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Passion+One:wght@400;700;900&display=swap" rel="stylesheet">

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-770312537">
</script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-770312537');
</script>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17232752521">
</script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-17232752521');
</script>
