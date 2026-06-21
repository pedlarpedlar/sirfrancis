<?php
//<!-- Variables for localhost/live -->
$is_localhost = isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'], true);
$requestHost = $_SERVER['HTTP_HOST'] ?? 'sirfrancis.co.za';
$requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_base_url = $is_localhost ? './' : $requestScheme . '://' . $requestHost . '/';
$home_directory = $site_base_url;

date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2
// Now PHP will use GMT+2 timezone for date and time functions
// $currentTime = date('Y-m-d H:i:s'); // Example of getting current time in GMT+2 timezone

// echo $currentTime;

//Set defaults. Pages may define these before including the shared header.
$page_url_canonical = $page_url_canonical ?? $site_base_url;
$title_og = $title_og ?? "Sir Francis | Marine Collagen, Fish Gelatine and Private Labelling";
$image_url_og = $image_url_og ?? $site_base_url . 'assets/img/logo/logo.png';
$image_type_og = $image_type_og ?? 'image/png';
$image_width_og = $image_width_og ?? '1200';
$image_height_og = $image_height_og ?? '630';
$og_type = $og_type ?? 'website';
$page_url_og = $page_url_og ?? $site_base_url;
$description_og = $description_og ?? "Sir Francis supplies premium fish gelatine, marine collagen, peptides, tripeptides, sea moss and private labelling solutions in South Africa.";
$description_meta = $description_meta ?? "Sir Francis supplies premium fish gelatine, marine collagen, peptides, tripeptides, sea moss and private labelling solutions in South Africa.";


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
    <meta name="author" content="Sir Francis">
    <meta name="keywords" content="fish gelatine South Africa, marine collagen South Africa, collagen peptides, tripeptides, sea moss, supplement private labelling, Sir Francis">
    <title><?= htmlspecialchars($title_og, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?=$home_directory?>assets/img/favicon.png">
    <link rel="shortcut icon" type="image/png" href="<?=$home_directory?>assets/img/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?=$home_directory?>assets/img/favicon.png">
    <meta name="theme-color" content="#28364B">
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
    <?php if (!empty($page_preload_images) && is_array($page_preload_images)): ?>
        <?php foreach ($page_preload_images as $preloadImage): ?>
            <link rel="preload" as="image" href="<?= htmlspecialchars($home_directory . ltrim((string) $preloadImage, '/'), ENT_QUOTES, 'UTF-8') ?>" fetchpriority="high">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
    $siteSchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => ['Organization', 'Store'],
                '@id' => $site_base_url . '#organization',
                'name' => 'Sir Francis',
                'url' => $site_base_url,
                'logo' => $site_base_url . 'assets/img/logo/logo.png',
                'image' => $site_base_url . 'assets/img/logo/logo.png',
                'description' => 'Sir Francis supplies wholesale, retail and private labelling solutions in South Africa.',
                'email' => 'info@sirfrancis.co.za',
                'telephone' => '+27824867685',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Overport',
                    'addressLocality' => 'Berea',
                    'addressRegion' => 'KwaZulu-Natal',
                    'addressCountry' => 'ZA',
                ],
                'areaServed' => [
                    ['@type' => 'Country', 'name' => 'South Africa'],
                ],
                'knowsAbout' => [
                    'Fish gelatine',
                    'Marine collagen',
                    'Collagen peptides',
                    'Tripeptides',
                    'Sea moss',
                    'Supplement private labelling',
                    'Bulk collagen supply',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $site_base_url . '#website',
                'name' => 'Sir Francis',
                'url' => $site_base_url,
                'publisher' => ['@id' => $site_base_url . '#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => $site_base_url . 'products?search={search_term_string}',
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

    <?php if (empty($skip_google_fonts)): ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Pinyon+Script&family=Playfair+Display:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php endif; ?>

<!-- Google tag (gtag.js) -->
<?php if (!empty($defer_gtag)): ?>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  (function() {
    var loaded = false;
    function loadGtag() {
      if (loaded) return;
      loaded = true;
      var gtagScript = document.createElement('script');
      gtagScript.async = true;
      gtagScript.src = 'https://www.googletagmanager.com/gtag/js?id=AW-770312537';
      document.head.appendChild(gtagScript);
      gtag('js', new Date());
      gtag('config', 'AW-770312537');
      gtag('config', 'AW-17232752521');
    }
    ['pointerdown', 'keydown', 'touchstart'].forEach(function(eventName) {
      window.addEventListener(eventName, loadGtag, { once: true, passive: true });
    });
    window.addEventListener('load', function() {
      window.setTimeout(loadGtag, 12000);
    }, { once: true });
  })();
</script>
<?php else: ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-770312537">
</script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-770312537');
  gtag('config', 'AW-17232752521');
</script>
<?php endif; ?>
