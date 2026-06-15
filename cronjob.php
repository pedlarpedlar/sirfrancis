<?php
// Execute generate_sitemap.php and save output to sitemap.xml
$xml = file_get_contents('https://www.fishgelatine.co.za/v2/generate_sitemap.php');
file_put_contents('sitemap.xml', $xml);

// Optionally, output a success message
echo 'Sitemap generated successfully and saved to sitemap.xml.';
?>