<?php
include 'session_logins.php';
require_once __DIR__ . '/wholesale_pricelist_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$requestedIds = preg_split('/[,|]+/', (string) ($_GET['product_id'] ?? $_GET['id'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
$checkedIds = [];
$matchedId = '';

foreach ($requestedIds as $rawId) {
    $productId = trim((string) $rawId);
    if ($productId === '') {
        continue;
    }
    if (stripos($productId, 'CLR:') === 0) {
        $clearance = getSheetClearanceRowById(substr($productId, 4));
        $productId = trim((string) ($clearance['product_id'] ?? ''));
    }
    if ($productId === '' || isset($checkedIds[$productId])) {
        continue;
    }
    $checkedIds[$productId] = true;
    if (hasCandybirdWholesaleOption($productId)) {
        $matchedId = $productId;
        break;
    }
}

$available = $matchedId !== '';

echo json_encode([
    'success' => true,
    'available' => $available,
    'product_id' => $matchedId,
    'checked_ids' => array_keys($checkedIds),
    'count' => $available ? 1 : 0,
    'url' => 'wholesale-pricelist',
]);
?>
