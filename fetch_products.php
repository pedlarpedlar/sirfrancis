<?php
include 'session_logins.php';

// Number of items per page
$itemsPerPage = 2;

// Get the total number of products
$totalProductsQuery = "SELECT COUNT(*) AS total FROM product WHERE enabled = 1";
$totalProductsResult = mysqli_query($conn, $totalProductsQuery);
$totalProducts = mysqli_fetch_assoc($totalProductsResult)['total'];

// Calculate the total number of pages
$totalPages = ceil($totalProducts / $itemsPerPage);

// Get the current page from the URL parameter
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Validate the page number
if ($page < 1 || $page > $totalPages) {
    $page = 1;
}

// Calculate the offset
$offset = ($page - 1) * $itemsPerPage;

// Fetch products for the specified page
$productsQuery = "SELECT * FROM product WHERE enabled = 1 LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $productsQuery);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $offset, $itemsPerPage);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Fetch the products
    $products = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_stmt_close($stmt);
} else {
    // Handle error
    $products = false;
}

// Output the results
if ($products) {
    foreach ($products as $product) {
        // Output the product details here (modify as needed)
        echo '<div class="product-item">';
        echo '<h2>' . $product['title'] . '</h2>';
        // Add more details as needed
        echo '</div>';
    }
} else {
    echo 'No products found.';
}

// Pagination links
echo '<div class="pagination">';
for ($i = 1; $i <= $totalPages; $i++) {
    // Append the current page as a query parameter
    $activeClass = ($i == $page) ? 'active' : '';
    echo '<a class="pagination-link ' . $activeClass . '" href="?page=' . $i . '">' . $i . '</a>';
}
echo '</div>';
?>