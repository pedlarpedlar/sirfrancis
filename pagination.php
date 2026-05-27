<?php
// Display pagination links (below is if you want to display all products including the different sizes as single products. More professionally, group the products by their size.)
// $sql = "SELECT COUNT(id) AS total FROM product WHERE enabled = 1";
// $result = $conn->query($sql);
// $row = $result->fetch_assoc();
// $total_pages = ceil($row['total'] / $productsPerPage);

// Count the total number of distinct product groups and products without a product_group
// $sql = "
//     SELECT COUNT(DISTINCT product_group) AS total_groups,
//            (SELECT COUNT(*) AS total_no_group FROM product WHERE product_group IS NULL OR product_group = '') AS total_no_group
//     FROM product
//     WHERE product_group IS NOT NULL AND product_group != '' AND enabled = 1
// ";

// $result = $conn->query($sql);
// $row = $result->fetch_assoc();

// // Calculate total effective pages
// $total_effective_groups =$row['total_groups'] + $row['total_no_group'];

// Calculate total pages based on products per page
$total_pages = ceil($total_filtered_products / $productsPerPage);

$pagination_section .= "<nav class='pagination-section mt-30'>";
$pagination_section .= "<ul class='pagination justify-content-center' id='pagination-container'>";

// Previous button
if ($current_page > 1) {
    $pagination_section .= "<li class='page-item'>";
    $pagination_section .= "<a class='page-link' href='?page=".($current_page - 1)."";//&sort=$selectedSort";
    if ($categoryFilter !== null) {
        $pagination_section .= "&category=$categoryFilter";
    }
    if (!empty($searchTerm)) {
        $pagination_section .= "&search=$searchTerm";
    }
    if (!empty($selectedSort)) {
        $pagination_section .= "&sort=$selectedSort";
    }
    $pagination_section .= "'><i class='ion-chevron-left'></i></a>";
    $pagination_section .= "</li>";
}

// Pagination links
for ($i = 1; $i <= $total_pages; $i++) {
    $activeClass = ($i == $current_page) ? 'active' : '';
    $pagination_section .= "<li class='page-item $activeClass'><a class='page-link' href='?page=$i";//&sort=$selectedSort";
    if ($categoryFilter !== null) {
        $pagination_section .= "&category=$categoryFilter";
    }
    if (!empty($searchTerm)) {
        $pagination_section .= "&search=$searchTerm";
    }
    if (!empty($selectedSort)) {
        $pagination_section .= "&sort=$selectedSort";
    }
    $pagination_section .= "'>$i</a></li>";
}

// Next button
if ($current_page < $total_pages) {
    $pagination_section .= "<li class='page-item'>";
    $pagination_section .= "<a class='page-link' href='?page=".($current_page + 1)."";//&sort=$selectedSort";
    if ($categoryFilter !== null) {
        $pagination_section .= "&category=$categoryFilter";
    }
    if (!empty($searchTerm)) {
        $pagination_section .= "&search=$searchTerm";
    }
    if (!empty($selectedSort)) {
        $pagination_section .= "&sort=$selectedSort";
    }
    $pagination_section .= "'><i class='ion-chevron-right'></i></a>";
    $pagination_section .= "</li>";
}

$pagination_section .= "</ul>";
$pagination_section .= "</nav>";
?>
