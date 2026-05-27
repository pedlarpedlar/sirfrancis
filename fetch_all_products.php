<?php

// Function to fetch all descendant categories recursively
function getDescendantCategories($conn, $categoryId) {
    $categories = [];

    // Fetch direct children of the current category
    $sql = "SELECT id FROM categories WHERE parent_id = $categoryId";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $childId = $row['id'];
            $categories[] = $childId;

            // Recursively fetch grandchildren and further descendants
            $categories = array_merge($categories, getDescendantCategories($conn, $childId));
        }
    }

    return $categories;
}

// Function to convert rating to HTML stars
function convertToStars($rating) {
    $fullStars = floor($rating);
    $halfStar = $rating % 1 !== 0;

    $starsHTML = '';

    for ($i = 0; $i < $fullStars; $i++) {
        $starsHTML .= '<span class="star-on"><i class="ion-ios-star"></i></span> ';
    }

    if ($halfStar) {
        $starsHTML .= '<span class="star-on"><i class="ion-ios-star-half"></i></span> ';
    }

    // Add remaining empty stars if needed
    for ($j = $fullStars + ($halfStar ? 1 : 0); $j < 5; $j++) {
        $starsHTML .= '<span class="star-on de-selected" style="color: lightgrey"><i class="ion-ios-star de-selected"></i></span> ';
    }

    return $starsHTML;
}

function convertToStarsAndroid($rating) {
    $fullStars = floor($rating);
    $halfStar = $rating % 1 !== 0;

    $starsHTML = '';

    for ($i = 0; $i < $fullStars; $i++) {
        $starsHTML .= '<span class="star-on"><i class="ion-android-star"></i></span> ';
    }

    if ($halfStar) {
        $starsHTML .= '<span class="star-on"><i class="ion-android-star-half"></i></span> ';
    }

    // Add remaining empty stars if needed
    for ($j = $fullStars + ($halfStar ? 1 : 0); $j < 5; $j++) {
        $starsHTML .= '<span class="star-on de-selected"><i class="ion-android-star de-selected"></i></span> ';
    }

    return $starsHTML;
}


// Sorting options
$sortOptions = [
    'relevance' => 'p.created_at DESC',
    'name_asc' => 'p.title ASC',
    'name_desc' => 'p.title DESC',
    'price_low_high' => 'p.price ASC',
    'price_high_low' => 'p.price DESC',
];
// Default sorting
$defaultSort = 'relevance';

// Get the selected sort option, default to 'relevance' if not set
$selectedSort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions) ? $_GET['sort'] : $defaultSort;


// Fetch products from the database for the current page with sorting
$sql = "SELECT 
    p.id,
    p.title,
    c.name AS category_name,
    p.price,
    p.discount_rate,
    p.discount_amount,
    p.tax_rate,
    p.tax_amount,
    p.description,
    p.weight,
    p.dimensions,
    p.other_info,
    p.label,
    p.product_label_exp,
    p.created_at,
    COALESCE(AVG(r.rating), 0) AS rating,
    GROUP_CONCAT(DISTINCT i.image_url) AS image_url";

    // Apply search term filter if set
if (!empty($searchTerm)) {
    // Escape and sanitize the search term (consider using prepared statements for production use)
    $escapedSearchTerm = $conn->real_escape_string($searchTerm);
    
    // Build the SQL condition for full-text search
    $searchCondition1 = ", (CASE 
        WHEN p.title = '$escapedSearchTerm' THEN 100
        WHEN p.title LIKE '%$escapedSearchTerm%' THEN 90
        WHEN p.title LIKE '%${escapedSearchTerm}s%' THEN 90
        WHEN p.title LIKE '%${escapedSearchTerm} %' THEN 90
        WHEN p.title LIKE '% $escapedSearchTerm%' THEN 90
        WHEN p.weight = '$escapedSearchTerm' THEN 80
        WHEN p.weight LIKE '%$escapedSearchTerm%' THEN 70
        WHEN p.description = '$escapedSearchTerm' THEN 60
        WHEN p.description LIKE '%$escapedSearchTerm%' THEN 50
        WHEN p.other_info = '$escapedSearchTerm' THEN 40
        WHEN p.other_info LIKE '%$escapedSearchTerm%' THEN 30
        WHEN p.features = '$escapedSearchTerm' THEN 20
        WHEN p.features LIKE '%$escapedSearchTerm%' THEN 10
        ELSE 0
    END) AS match_score";
    
    $sql .= " $searchCondition1";
}

$sql .= "
FROM 
    product p
LEFT JOIN 
    reviews r ON p.id = r.product_id
LEFT JOIN 
    images i ON p.id = i.product_id
LEFT JOIN 
    categories c ON p.category_id = c.id
WHERE 
    p.enabled = 1";

// Add category filter if set (including all descendants)
if ($categoryFilter !== null) {
    // Fetch all descendant categories recursively
    $descendantCategories = getDescendantCategories($conn, $categoryFilter);

    // Include the selected category and all its descendants
    $categoryList = array_merge([$categoryFilter], $descendantCategories);

    // Build the SQL condition for category filtering
    $categoryCondition = "p.category_id IN (" . implode(',', $categoryList) . ")";
    
    $sql .= " AND $categoryCondition";
}

// Apply search term filter if set
if (!empty($searchTerm)) {
    // Escape and sanitize the search term (consider using prepared statements for production use)
    $escapedSearchTerm = $conn->real_escape_string($searchTerm);
    
    $searchCondition2 = "
        AND MATCH(p.title, p.description, p.other_info, p.features, p.weight) 
        AGAINST ('$escapedSearchTerm' IN NATURAL LANGUAGE MODE)
    ";
    
    $sql .= " $searchCondition2";
}


$sql .= " GROUP BY p.id";

$sql .= " ORDER BY ";

// Apply search term filter if set
if (!empty($searchTerm)) {    
    $sql .= " match_score DESC, ";
}


$sql .= "{$sortOptions[$selectedSort]}";

$resultCountQuery = $conn->query("SELECT COUNT(*) as total FROM ($sql) as total_products");
$resultCountRow = $resultCountQuery->fetch_assoc();
$result_count = $resultCountRow['total'];

$total_filtered_products = $result_count; // for pagination

// Determine the current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($current_page - 1) * $productsPerPage;

// Fetch products for the current page with sorting and pagination
$sql .= " LIMIT $start_from, $productsPerPage";

// echo $sql;
$result = $conn->query($sql);


if ($total_filtered_products == 0) {
    $productHtml = '<p>Uh oh... Try again later or change your search terms.</p>';
    $productHtmlListView = '<p>Uh oh... Try again later or change your search terms.</p>';
} else {
    // Initialize an empty variable to store the product HTML
    $productHtml = '';
    $productHtmlListView = '';
}

// Display the products
while ($row = $result->fetch_assoc()) {
    $productId = $row['id']; // Adjust the field name based on your database structure
    $title = $row['title']; // Adjust the field name based on your database structure
    $weight = $row['weight']; // Adjust the field name based on your database structure
    $image_url = $row['image_url']; // Assuming 'image_url' is the field in your database for image URLs
    $price = $row['price'];
    $discount_rate = number_format($row['discount_rate'], 0);
    $discount_amount = $row['discount_amount'];
    $last_price = $row['price'] - $row['discount_amount'];


    if ($discount_amount == 0 && $discount_rate > 0) {
        $discount_amount = ($price * $discount_rate) / 100;
    }

    $discounted_price = $price - $discount_amount;


    $label = $row['label'];
    $rating = $row['rating'];
    $description = $row['description'];
    // Limit the description to a reasonable length
    $limitedDescription = strlen($description) > 300 ? trim(strip_tags(substr($description, 0, 300) . '...')) : trim(strip_tags($description));



    // Explode the string into an array using comma as delimiter
    $image_urls_array = explode(',', $image_url);

    // Check if there are any image URLs
    if (!empty($image_urls_array)) {
        // Get the first image URL
        $image_url = $image_urls_array[0];

    }

    // Check if $imageUrls is empty and assign the default image URL
    if (empty($image_url)) {
        $defaultImageUrl = "assets/img/product/1.png";
        $image_url = $defaultImageUrl;
    }

    // Concatenate the HTML code for each product
    $productHtml .= "<div class='col-sm-6 col-md-4 mb-30'>";
    $productHtml .= "<div class='card product-card'>";
    $productHtml .= "<div class='card-body'>";
    $productHtml .= "<div class='product-thumbnail position-relative'>";
    // Add the discount badge if discount_rate is greater than 0
    if ($discount_rate > 0) {
        $productHtml .= "<span class='badge badge-success top-left'>-$discount_rate%</span>";
    }

    // Add the label badge if label is not empty
    if (!empty($label)) {
        $productHtml .= "<span class='badge badge-danger top-right'>$label</span>";
    }

    $productHtml .= "<a href='product?id=$productId'>";
    $productHtml .= "<img class='first-img' src='$image_url' alt='$title' style='max-width: 300px; max-height: 300px;' />";
    $productHtml .= "</a>";
    $productHtml .= "<ul class='actions d-flex justify-content-center'>";
    $productHtml .= "<li>";
    $productHtml .= "<a class='action add-to-wishlist' data-product-id='$productId' href='#'>";
    $productHtml .= "<span data-toggle='tooltip' data-placement='bottom' title='add to wishlist' class='icon-heart'></span>";
    $productHtml .= "</a>";
    $productHtml .= "</li>";
    $productHtml .= "<li>";
    $productHtml .= "<a class='action add-to-compare' data-product-id='$productId' href='#' data-toggle='modal' data-target='#compare'>";
    $productHtml .= "<span data-toggle='tooltip' data-placement='bottom' title='Add to compare' class='icon-shuffle'></span>";
    $productHtml .= "</a>";
    $productHtml .= "</li>";
    $productHtml .= "<li>";
    $productHtml .= "<a class='action open-quick-view' data-product-id='$productId' href='#' data-toggle='modal' data-target='#quick-view'>";
    $productHtml .= "<span data-toggle='tooltip' data-placement='bottom' title='Quick view' class='icon-magnifier'></span>";
    $productHtml .= "</a>";
    $productHtml .= "</li>";
    $productHtml .= "</ul>";
    $productHtml .= "</div>";
    $productHtml .= "<div class='product-desc py-0 px-0'>";
    $productHtml .= "<h3 class='title'>";
    $productHtml .= "<a href='product?id=$productId'>$title $weight</a>"; // Use the product title here
    $productHtml .= "</h3>";
    $productHtml .= "<div class='star-rating'>";
    $productHtml .= convertToStars($rating);
    $productHtml .= "</div>";
    $productHtml .= "<div class='d-flex align-items-center justify-content-between'>";
    
    $productHtml .= "<span class='product-price'>";

    if ($discount_rate > 0) {
        $productHtml .= "<del class='del'>R".number_format($price, 2)."</del>";
        $productHtml .= "<span class='onsale'>R".number_format($discounted_price, 2)."</span>";
    } else {
        $productHtml .= "R".number_format($price, 2);
    }

    $productHtml .= "</span>";

    $productHtml .= "<button class='pro-btn add-to-cart' data-toggle='modal' data-target='#add-to-cart' data-product-id='$productId'>";
    $productHtml .= "<i class='icon-basket'></i>";
    $productHtml .= "</button>";
    $productHtml .= "</div>";
    $productHtml .= "</div>";
    $productHtml .= "</div>";
    $productHtml .= "</div>";
    $productHtml .= "</div>";

    // Concatenate the HTML code for each product in list view
    $productHtmlListView .= "<div class='col-12 mb-30'>";
    $productHtmlListView .= "<div class='card product-card'>";
    $productHtmlListView .= "<div class='card-body'>";
    $productHtmlListView .= "<div class='media flex-column flex-md-row'>";
    $productHtmlListView .= "<div class='product-thumbnail position-relative'>";
    // Add the discount badge if discount_rate is greater than 0
    if ($discount_rate > 0) {
        $productHtmlListView .= "<span class='badge badge-success top-left'>-$discount_rate%</span>";
    }
    // Add the label badge if label is not empty
    if (!empty($label)) {
        $productHtmlListView .= "<span class='badge badge-danger top-right'>$label</span>";
    }
    $productHtmlListView .= "<a href='product?id=$productId'>";
    // Set the maximum width for the image to 300px
    $productHtmlListView .= "<img class='first-img' src='$image_url' alt='$title' style='max-width: 300px; max-height: 300px;' />";
    $productHtmlListView .= "</a>";
    $productHtmlListView .= "<ul class='actions d-flex justify-content-center'>";
    $productHtmlListView .= "<li>";
    $productHtmlListView .= "<a class='action add-to-wishlist' data-product-id='$productId' href='#'>";
    $productHtmlListView .= "<span data-toggle='tooltip' data-placement='bottom' title='add to wishlist' class='icon-heart'></span>";
    $productHtmlListView .= "</a>";
    $productHtmlListView .= "</li>";
    $productHtmlListView .= "<li>";
    $productHtmlListView .= "<a class='action add-to-compare' data-product-id='$productId' href='#' data-toggle='modal' data-target='#compare'>";
    $productHtmlListView .= "<span data-toggle='tooltip' data-placement='bottom' title='Add to compare' class='icon-shuffle'></span>";
    $productHtmlListView .= "</a>";
    $productHtmlListView .= "</li>";
    $productHtmlListView .= "<li>";
    $productHtmlListView .= "<a class='action open-quick-view' data-product-id='$productId' href='#' data-toggle='modal' data-target='#quick-view'>";
    $productHtmlListView .= "<span data-toggle='tooltip' data-placement='bottom' title='Quick view' class='icon-magnifier'></span>";
    $productHtmlListView .= "</a>";
    $productHtmlListView .= "</li>";
    $productHtmlListView .= "</ul>";
    $productHtmlListView .= "</div>";
    $productHtmlListView .= "<div class='media-body pl-md-4'>";
    $productHtmlListView .= "<div class='product-desc py-0 px-0'>";
    $productHtmlListView .= "<h3 class='title'>";
    $productHtmlListView .= "<a href='product?id=$productId'>$title</a>"; // Use the product title here
    $productHtmlListView .= "</h3>";
    $productHtmlListView .= "<div class='star-rating mb-10'>";
    $productHtmlListView .= "<span class='ion-ios-star'></span>";
    $productHtmlListView .= "<span class='ion-ios-star'></span>";
    $productHtmlListView .= "<span class='ion-ios-star'></span>";
    $productHtmlListView .= "<span class='ion-ios-star'></span>";
    $productHtmlListView .= "<span class='ion-ios-star de-selected'></span>";
    $productHtmlListView .= "</div>";
    
    $productHtmlListView .= "<span class='product-price'>";

    if ($discount_rate > 0) {
        $productHtmlListView .= "<del class='del'>R".number_format($price, 2)."</del>";
        $productHtmlListView .= "<span class='onsale'>R".number_format($discounted_price, 2)."</span>";
    } else {
        $productHtmlListView .= "R".number_format($price, 2);
    }

    $productHtmlListView .= "</span>";


    $productHtmlListView .= "</div>";
    $productHtmlListView .= "<ul class='product-list-des'>";
    $productHtmlListView .= "<li>$limitedDescription</li>";
    $productHtmlListView .= "</ul>";
    // $productHtmlListView .= "<div class='availability-list mb-20'>";
    // $productHtmlListView .= "<p>Availability: <span>1200 In Stock</span></p>";
    // $productHtmlListView .= "</div>";
    $productHtmlListView .= "<button class='btn btn-dark btn--xl add-to-cart' data-toggle='modal' data-target='#add-to-cart' data-product-id='$productId'>";
    $productHtmlListView .= "Add to cart";
    $productHtmlListView .= "</button>";
    $productHtmlListView .= "</div>";
    $productHtmlListView .= "</div>";
    $productHtmlListView .= "</div>";
    $productHtmlListView .= "</div>";
    $productHtmlListView .= "</div>";

}



if (!empty($searchTerm)) {
    logAction('Search', 'Term: "'.$searchTerm.'", result count: '.$result_count, $userId, $guestIdentifier);
}



?>
