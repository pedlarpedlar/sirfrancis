<?php

function getParentCategories($conn, $categoryId) {
    $categories = [];

    // Loop to fetch all parent categories
    while ($categoryId) {
        $sql = "SELECT id, name, parent_id FROM categories WHERE id = $categoryId";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $categories[] = $row;
            $categoryId = $row['parent_id'];
        } else {
            break;
        }
    }

    return array_reverse($categories); // Reverse to get the correct order from root to leaf
}

// Function to generate breadcrumbs
function generateBreadcrumbs($conn, $productId) {
    // Fetch the product information
    $sql = "SELECT p.title, c.id AS category_id, c.name AS category_name
            FROM product p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = $productId";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    // Initialize the breadcrumbs array
    $breadcrumbs = [];

    // Home breadcrumb
    $breadcrumbs[] = '<li class="breadcrumb-item"><a href="https://www.candybird.co.za">Home</a></li>';

    // Fetch parent categories
    if (!empty($row['category_id'])) {
        $parentCategories = getParentCategories($conn, $row['category_id']);

        foreach ($parentCategories as $category) {
            $breadcrumbs[] = '<li class="breadcrumb-item"><a href="products?category='.$category['id'].'">' . htmlspecialchars($category['name']) . '</a></li>';
        }
    }

    // Product breadcrumb
    $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($row['title']) . '</li>';

    // Display the breadcrumbs in the HTML
    $breadcrumbHtml = '<nav class="breadcrumb-section theme1 bg-lighten2 pt-50 pb-50">';
    $breadcrumbHtml .= '<div class="container">';
    $breadcrumbHtml .= '<div class="row">';
    $breadcrumbHtml .= '<div class="col-12">';
    $breadcrumbHtml .= '<ol class="breadcrumb bg-transparent m-0 p-0 align-items-center justify-content-center">';
    $breadcrumbHtml .= implode('', $breadcrumbs);
    $breadcrumbHtml .= '</ol>';
    $breadcrumbHtml .= '</div>';
    $breadcrumbHtml .= '</div>';
    $breadcrumbHtml .= '</div>';
    $breadcrumbHtml .= '</nav>';

    echo $breadcrumbHtml;
}


?>