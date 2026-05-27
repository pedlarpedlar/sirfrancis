<?php
include 'dbh.inc.php'; // Database connection

$type = $_GET['type'] ?? '';
$searchTerm = $_GET['term'] ?? '';

if ($type == 'category') {
    $stmt = $conn->prepare("SELECT id, name AS name FROM categories WHERE name LIKE ? LIMIT 10");
    $searchTerm = '%' . $searchTerm . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();
    echo json_encode($categories);

} elseif ($type == 'product') {
    $stmt = $conn->prepare("SELECT id, title AS name FROM product WHERE title LIKE ? LIMIT 10");
    $searchTerm = '%' . $searchTerm . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
    echo json_encode($products);
} else {
    echo json_encode([]);
}
?>
