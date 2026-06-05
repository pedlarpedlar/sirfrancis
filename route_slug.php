<?php
require_once __DIR__ . '/product_sheet_helpers.php';

$slug = normalizeCandybirdProductSlug($_GET['slug'] ?? '');
if ($slug === '') {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$product = getSheetProductBySlug($slug);
if ($product) {
    $_GET['slug'] = $slug;
    include __DIR__ . '/product.php';
    exit;
}

$category = getCandybirdCategoryBySlug($slug);
if ($category !== '') {
    $_GET['category'] = $category;
    $_GET['category_slug'] = $slug;
    include __DIR__ . '/products.php';
    exit;
}

http_response_code(404);
include __DIR__ . '/404.php';
