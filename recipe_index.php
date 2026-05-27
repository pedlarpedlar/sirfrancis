<?php
include 'session_logins.php';
header('Location: recipes', true, 302);
exit;
include 'header.php';
?>
    <?php
    $page_url_canonical = "https://www.candybird.co.za/products";
    $title_og = 'Products - CandyBird';
    $page_url_og = "https://www.candybird.co.za/products"
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

    <title>Products - CandyBird</title>
<?php
include 'page_menues.php';
?>

<?php

// Initialize search term variable
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Check if category filter is set
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : null;

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

$pagination_section = "";

// Set the number of products to display per page
$productsPerPage = 12;

// Get the selected sort option, default to 'relevance' if not set
$selectedSort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortOptions) ? $_GET['sort'] : $defaultSort;

// Determine the current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($current_page - 1) * $productsPerPage;


include 'fetch_all_products.php';

include 'pagination.php';

include 'products_breadcrumbs.php';

?>

<?php

generateProductsBreadcrumbs($conn, $categoryFilter, $searchTerm);

?>

<!-- product tab start -->
<div class="product-tab bg-white pt-0 pb-50">
  <div class="container">
    <div class="row">
      <div class="col-lg-9 mb-30">
        <div class="grid-nav-wraper bg-lighten2 mb-30">
          <div class="row align-items-center">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
              <nav class="shop-grid-nav">
            <ul
              class="nav nav-pills align-items-center"
              id="pills-tab"
              role="tablist"
            >
              <li class="nav-item">
                <a
                  class="nav-link active"
                  id="pills-home-tab"
                  data-toggle="pill"
                  href="#pills-home"
                  role="tab"
                  aria-controls="pills-home"
                  aria-selected="true"
                >
                  <i class="fa fa-th"></i>
                </a>
              </li>
              <li class="nav-item mr-0">
                <a
                  class="nav-link"
                  id="pills-profile-tab"
                  data-toggle="pill"
                  href="#pills-profile"
                  role="tab"
                  aria-controls="pills-profile"
                  aria-selected="false"
                  ><i class="fa fa-list"></i
                ></a>
              </li>
              <li>
              </li>
            </ul>
          </nav>
        </div>
        <div class="col-12 col-md-6 position-relative">
          <div class="shop-grid-button d-flex align-items-center">
            <span class="sort-by">Sort by:</span>
                <button
                    class="d-flex justify-content-between"
                    type="button"
                    id="dropdownMenuButton"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <span id="selectedSort" style="font-size: inherit;">Relevance</span> <span class="ion-android-arrow-dropdown"></span>
                </button>
                <div
                    class="dropdown-menu shop-grid-menu"
                    aria-labelledby="dropdownMenuButton"
                >
                    <a class="dropdown-item sort-option" data-sort="relevance" href="#">Relevance</a>
                    <a class="dropdown-item sort-option" data-sort="name_asc" href="#">Name, A to Z</a>
                    <a class="dropdown-item sort-option" data-sort="name_desc" href="#">Name, Z to A</a>
                    <a class="dropdown-item sort-option" data-sort="price_low_high" href="#">Price, low to high</a>
                    <a class="dropdown-item sort-option" data-sort="price_high_low" href="#">Price, high to low</a>
                </div>
          </div>
        </div>
      </div>
    </div>
    <!-- product-tab-nav end -->
    <div class="tab-content" id="pills-tabContent">
      <!-- first tab-pane -->
      <div
        class="tab-pane fade show active"
        id="pills-home"
        role="tabpanel"
        aria-labelledby="pills-home-tab"
      >
        <div class="row grid-view theme1" id="filtered_products_grid_view">
          <?=$productHtml?>
        </div>
      </div>
      <!-- second tab-pane -->
      <div
        class="tab-pane fade"
        id="pills-profile"
        role="tabpanel"
        aria-labelledby="pills-profile-tab"
      >
        <div class="row grid-view-list theme1"  id="filtered_products_list_view">
          <?=$productHtmlListView?>
        </div>
      </div>
    </div>
    
    </div>

<?php

// Function to fetch categories and subcategories with product counts
function fetchAllCategories($conn) {
    // Get the selected category ID from the URL parameters
    $selectedCategory = isset($_GET['category']) ? intval($_GET['category']) : null;

    // Query to fetch parent categories with product counts
    $sql = "SELECT c.id, c.name, COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN product p ON c.id = p.category_id AND p.enabled = 1
            WHERE c.parent_id IS NULL
            GROUP BY c.id, c.name";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo '<div class="search-filter">';
        echo '<div class="sidbar-widget pt-0">';
        echo '<h4 class="title">Categories</h4>';
        echo '</div>';
        echo '<ul id="offcanvas-menu2" class="blog-ctry-menu">';
        
        while ($row = $result->fetch_assoc()) {
            // Determine if this category is selected
            $isActive = $row['id'] == $selectedCategory ? ' active' : '';

            echo '<li>';
            echo '<a href="javascript:void(0)" class="category-link' . $isActive . '" data-category-id="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</a>';
            
            // Fetch and display subcategories if they exist
            $subSql = "SELECT c.id, c.name, COUNT(p.id) AS product_count
                       FROM categories c
                       LEFT JOIN product p ON c.id = p.category_id AND p.enabled = 1
                       WHERE c.parent_id = " . $row['id'] . "
                       GROUP BY c.id, c.name";
            $subResult = $conn->query($subSql);
            
            if ($subResult && $subResult->num_rows > 0) {
                echo '<ul class="category-sub-menu">';
                
                while ($subRow = $subResult->fetch_assoc()) {
                    // Determine if this subcategory is selected
                    $isActiveSub = $subRow['id'] == $selectedCategory ? ' active' : '';

                    echo '<li><a href="?category=' . $subRow['id'] . '" class="category-link' . $isActiveSub . '" data-category-id="' . $subRow['id'] . '">' . htmlspecialchars($subRow['name']) . ' (' . $subRow['product_count'] . ')</a></li>';
                }
                
                echo '</ul>';
            }
            
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
}


// Function to fetch price range
function fetchPriceRange($conn) {    
        echo '<div class="sidbar-widget mt-10">';
        echo '<h4 class="sub-title">Price</h4>';
        echo '<div class="price-filter mt-10">';
        echo '<div class="price-slider-amount">';
        echo '<input type="text" id="amount" name="price" readonly placeholder="Add Your Price" />';
        echo '</div>';
        echo '<div id="slider-range"></div>';
        echo '</div>';
        echo '</div>';
}

// Function to fetch sizes (weights) with product count
function fetchSizes($conn) {
    $sql = "SELECT weight, COUNT(*) AS size_count FROM product WHERE enabled = 1 GROUP BY weight";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo '<div class="sidbar-widget mt-10">';
        echo '<h4 class="sub-title">Size</h4>';
        
        while ($row = $result->fetch_assoc()) {
            echo '<div class="widget-check-box">';
            echo '<input type="checkbox" name="filter_size" id="' . $row['weight'] . '" />';
            echo '<label for="' . $row['weight'] . '">' . $row['weight'] . ' <span>(' . $row['size_count'] . ')</span></label>';
            echo '</div>';
        }
        
        echo '</div>';
    }
}


// Function to fetch properties (features) with product count
function fetchProperties($conn) {
    $sql = "SELECT features 
    FROM product 
    WHERE enabled = 1 
    AND (
        features LIKE '%keto%' 
        OR features LIKE '%travel treats%' 
        OR features LIKE '%high in magnesium%'
    ) 
    ORDER BY RAND() 
    LIMIT 3;
    "; /*Later, we limit this to 10 unique properties.*/
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        // Array to store all features
        $allFeatures = array();
        
        while ($row = $result->fetch_assoc()) {
            // Explode features by comma and trim spaces
            $features = explode(',', $row['features']);
            
            foreach ($features as $feature) {
                $trimmedFeature = trim($feature);
                if (!empty($trimmedFeature)) {
                    // Add feature to array (using lowercase for case-insensitive comparison)
                    $allFeatures[strtolower($trimmedFeature)] = isset($allFeatures[strtolower($trimmedFeature)]) ? $allFeatures[strtolower($trimmedFeature)] + 1 : 1;
                }
            }
        }
        
        // Sort features alphabetically by key
        ksort($allFeatures);
        
        echo '<div class="sidebar-widget mt-10">';
        echo '<h4 class="sub-title">Properties</h4>';
        
        // Counter to limit to 10 features
        $counter = 0;
        
        foreach ($allFeatures as $feature => $count) {
            if ($counter >= 3) {
                break;
            }
            
            echo '<div class="widget-check-box">';
            echo '<input type="checkbox" name="filter_property" id="' . $feature . '" />';
            echo '<label for="' . $feature . '">' . ucfirst($feature) . ' <span>(' . $count . ')</span></label>';
            echo '</div>';
            
            $counter++;
        }
        
        echo '</div>';
    }
}


// Function to fetch product tags (labels) with identifiers for jQuery filters
function fetchProductTags($conn) {
    echo '<div class="product-widget mb-60 mt-30">';
    echo '<h3 class="title">Product Tags</h3>';
    echo '<ul class="product-tag d-flex flex-wrap">';
    
    // Print the static "Sale" tag
    echo '<li><a href="#" class="product-tag-link" data-tag="Sale">Sale</a></li>';
    
    $sql = "SELECT DISTINCT label FROM product WHERE label <> '' AND enabled = 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        
        while ($row = $result->fetch_assoc()) {
            $label = $row['label'];
            
            // Print each product label as a clickable link with data attribute
            echo '<li><a href="#" class="product-tag-link" data-tag="' . $label . '">' . $label . '</a></li>';
        }
        
    }
    
    echo '</ul>';
    echo '</div>';
}



?>

<!-- Sidebar HTML structure -->
<div class="col-lg-3 mb-30 order-lg-first">
    <aside class="left-sidebar theme1">
        <?php fetchAllCategories($conn); ?>
        
        <?php fetchPriceRange($conn); ?>
        
        <?php fetchSizes($conn); ?>
        
        <?php fetchProperties($conn); ?>
        
        <?php fetchProductTags($conn); ?>
        
        <!-- Additional sidebar content -->
        <!-- Replace with your additional sidebar content as needed -->
    </aside>
</div>



    


          <!-- Pagination -->
    <?=$pagination_section?>


  </div>
</div>
</div>

<!-- product tab end -->

<?php
// Query to get the minimum and maximum prices
$sqlPriceRange = "SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM product WHERE enabled = 1";

// Execute the query
$resultPriceRange = $conn->query($sqlPriceRange);

// Initialize variables to store min and max prices
$minPrice = 0;
$maxPrice = 0;

// Check if the query was successful
if ($resultPriceRange) {
    // Fetch the result as an associative array
    $rowRange = $resultPriceRange->fetch_assoc();
    
    // Retrieve min and max prices
    $minPrice = intval($rowRange['min_price']);
    $maxPrice = intval($rowRange['max_price']);
} else {
    // Handle query error if needed
    echo "Error retrieving price range: " . $conn->error;
}

?>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>

$(document).ready(function() {
    
    // Define a mapping of sort parameters to display text
    var sortMapping = {
        relevance: "Relevance",
        name_asc: "Name, A to Z",
        name_desc: "Name, Z to A",
        price_low_high: "Price, low to high",
        price_high_low: "Price, high to low"
    };

    // Get the current sort parameter from the URL
    var urlParams = new URLSearchParams(window.location.search);
    var currentSort = urlParams.get('sort') || 'relevance'; // Default to 'relevance' if not set

    // Update the button text to reflect the current sort option
    $('#selectedSort').text(sortMapping[currentSort]);

    $('body').on('click', '.sort-option', function(event) {
        event.preventDefault();
        var sortParam = $(this).data('sort');
        var url = new URL(window.location.href);
        url.searchParams.set('sort', sortParam);
        window.location.href = url.toString();
    });

    // Call applyFilters() if there is a "sort" parameter in the URL
    if (urlParams.has('sort')) {
        applyFilters();
    }
    
    // Function to handle checkbox change events
    $('body').on('change', '.widget-check-box input[type="checkbox"]', function() {
        applyFilters();
    });

    

    // Function to handle price slider change events
    $('#slider-range').slider({
        range: true,
        min: <?=$minPrice?>,
        max: <?=$maxPrice?>, // Adjust the max value based on your price range
        values: [0, 500], // Initial range values
        slide: function(event, ui) {
            $('#amount').val('R' + ui.values[0] + ' - R' + ui.values[1]);
        },
        change: function(event, ui) {
            applyFilters(); // Apply filters when slider values change
        }
    });

    // Initialize price range display
    $('#amount').val('R' + $('#slider-range').slider('values', 0) +
        ' - R' + $('#slider-range').slider('values', 1));


    // Function to handle product tag clicks
    $('body').on('click', '.product-tag-link', function(e) {
        e.preventDefault();

        // Toggle class for visual indication (optional)
        $(this).toggleClass('selected');

        // Apply filters whenever a tag is clicked
        applyFilters();
    });

    // Function to apply filters and update product list via AJAX
        function applyFilters() {
            var sorter = "<?=$selectedSort?>";
            var category = "<?=$categoryFilter?>";
            var searchTerm = "<?=$searchTerm?>";
            console.log("category: " + category);
            var filters = {
                sizes: [],
                properties: [],
                tags: [],
                price_min: $('#slider-range').slider('values', 0), // Get minimum price from slider
                price_max: $('#slider-range').slider('values', 1) // Get maximum price from slider
            };

            // Gather selected sizes
            $('input[type="checkbox"][name="filter_size"]:checked').each(function() {
                filters.sizes.push($(this).attr('id'));
            });

            // Gather selected properties
            $('input[type="checkbox"][name="filter_property"]:checked').each(function() {
                filters.properties.push($(this).attr('id'));
            });

            // Gather selected tags
            $('.product-tag-link.selected').each(function() {
                filters.tags.push($(this).data('tag'));
            });

            console.log(filters);

            // AJAX request to send filters to server-side script
            $.ajax({
                url: 'filter_products.php',
                type: 'POST',
                data: { 
                    filters: filters,
                    category: category,
                    sort: sorter, 
                    searchTerm: searchTerm
                }, // Ensure 'filters' matches $_POST['filters'] in PHP
                dataType: 'json',
                success: function(response) {
                    console.log(response);
                    // $('#filtered_products_grid_view').html(response.QUERY);

                    // Update grid view
                    $('#filtered_products_grid_view').html(response.gridView);

                    // Update list view
                    $('#filtered_products_list_view').html(response.listView);

                },
                error: function(xhr, status, error) {
                    console.error('Error applying filters:', error);
                }
            });
        }

});


// Function to update URL parameters
function updateUrlParameter(param, value) {
    var url = new URL(window.location.href);
    url.searchParams.set(param, value);
    window.history.pushState({}, '', url);
    highlightActiveCategory(value);
}

// Function to highlight the active category
function highlightActiveCategory(categoryId) {
    $('.category-link').each(function() {
        if ($(this).data('category-id') == categoryId) {
            $(this).addClass('active');
        } else {
            $(this).removeClass('active');
        }
    });
}

// Attach event listeners to category links
$(document).ready(function() {
    $('.category-link').click(function() {
        var categoryId = $(this).data('category-id');
        updateUrlParameter('category', categoryId);
    });

    // Highlight the active category on page load
    var urlParams = new URLSearchParams(window.location.search);
    var categoryId = urlParams.get('category');
    if (categoryId) {
        highlightActiveCategory(categoryId);
    }
});

</script>


<?php
include 'footer.php';
?>


