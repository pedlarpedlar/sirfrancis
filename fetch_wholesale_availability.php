<?php
include 'session_logins.php';
require_once __DIR__ . '/wholesale_pricelist_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$productId = trim((string) ($_GET['product_id'] ?? $_GET['id'] ?? ''));
if (stripos($productId, 'CLR:') === 0) {
    $clearance = getSheetClearanceRowById(substr($productId, 4));
    $productId = trim((string) ($clearance['product_id'] ?? ''));
}

$available = $productId !== '' && hasCandybirdWholesaleOption($productId);

echo json_encode([
    'success' => true,
    'available' => $available,
    'product_id' => $productId,
    'count' => $available ? 1 : 0,
    'url' => 'wholesale-pricelist',
]);
?>
