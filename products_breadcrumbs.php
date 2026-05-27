<?php
// Include your database connection file if needed
// include 'dbh.inc.php';

function getCategoryName($conn, $categoryId) {
    $sql = "SELECT name FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();
    $stmt->close();
    return $name;
}

function getParentCategoriesNew($conn, $categoryId) {
    $categories = [];
    while ($categoryId) {
        $sql = "SELECT id, name, parent_id FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $stmt->bind_result($id, $name, $parentId);
        if ($stmt->fetch()) {
            $categories[] = ['id' => $id, 'name' => $name];
            $categoryId = $parentId;
        } else {
            break;
        }
        $stmt->close();
    }
    return array_reverse($categories); // Reverse to get from root to leaf
}

function generateProductsBreadcrumbs($conn, $categoryId = null, $searchTerm = null) {
    // Initialize the breadcrumbs array
    $breadcrumbs = [];

    // Home breadcrumb
    $breadcrumbs[] = '<li class="breadcrumb-item"><a href="https://www.candybird.co.za">Home</a></li>';

    // All Products breadcrumb
    $breadcrumbs[] = '<li class="breadcrumb-item"><a href="products">All Products</a></li>';

    // Category breadcrumb
    if ($categoryId !== null) {
        $parentCategories = getParentCategoriesNew($conn, $categoryId);
        foreach ($parentCategories as $category) {
            $breadcrumbs[] = '<li class="breadcrumb-item"><a href="products?category=' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</a></li>';
        }
    }

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
    
    // Display the search term if present
    if (!empty($searchTerm)) {
        $breadcrumbHtml .= '<div class="container mt-3"><h5 class="text-primary">You searched for <strong>"' . htmlspecialchars($searchTerm) . '"</strong>:</h5></div>';
    }

    $breadcrumbHtml .= '</nav>';

    echo $breadcrumbHtml;
}

?>
