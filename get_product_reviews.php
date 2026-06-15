<?php
include 'session_logins.php';
require_once __DIR__ . '/product_sheet_helpers.php';

header('Content-Type: application/json');

$productId = (int) ($_GET['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product is missing.', 'reviews' => [], 'average_rating' => 0, 'review_count' => 0]);
    exit;
}

if (!($conn instanceof mysqli)) {
    echo json_encode(['success' => true, 'reviews' => [], 'average_rating' => 0, 'review_count' => 0, 'user_logged_in' => !empty($_SESSION['user_id'])]);
    exit;
}

function candybirdReviewProductIds($productId) {
    $ids = [$productId];
    $product = getSheetProductById($productId);

    if (!$product) {
        return $ids;
    }

    $productName = normalizeCandybirdSearchText($product['name'] ?? $product['title'] ?? '');
    if ($productName === '') {
        return $ids;
    }

    foreach (getSheetProducts() as $sheetProduct) {
        $sheetId = (int) ($sheetProduct['id'] ?? 0);
        if ($sheetId <= 0) {
            continue;
        }

        $sheetName = normalizeCandybirdSearchText($sheetProduct['name'] ?? $sheetProduct['title'] ?? '');
        if ($sheetName === $productName) {
            $ids[] = $sheetId;
        }
    }

    return array_values(array_unique(array_filter($ids)));
}

$reviewProductIds = candybirdReviewProductIds($productId);
$placeholders = implode(',', array_fill(0, count($reviewProductIds), '?'));

$sql = "SELECT r.id, r.product_id, r.user_id, r.rating, r.comment, r.u_name, r.u_email, u.username, u.email
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.product_id IN ($placeholders)
        ORDER BY r.id DESC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => true, 'reviews' => [], 'average_rating' => 0, 'review_count' => 0, 'user_logged_in' => !empty($_SESSION['user_id'])]);
    exit;
}

$types = str_repeat('i', count($reviewProductIds));
$bindParams = array_merge([$types], $reviewProductIds);
$bindRefs = [];
foreach ($bindParams as $key => $value) {
    $bindRefs[$key] = &$bindParams[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bindRefs);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
$ratingTotal = 0;
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$myReview = null;

while ($row = $result->fetch_assoc()) {
    $rating = (int) $row['rating'];
    $reviewUserId = (int) ($row['user_id'] ?? 0);
    $canManage = $currentUserId > 0 && $reviewUserId === $currentUserId;
    $ratingTotal += $rating;
    $review = [
        'id' => (int) $row['id'],
        'product_id' => (int) $row['product_id'],
        'rating' => $rating,
        'comment' => $row['comment'] ?? '',
        'name' => $row['u_name'] ?: ($row['username'] ?: 'Sir Francis customer'),
        'display_name' => $row['u_name'] ?: ($row['username'] ?: 'Sir Francis customer'),
        'can_manage' => $canManage,
        'created_at' => '',
    ];
    $reviews[] = $review;

    if ($canManage && $myReview === null) {
        $myReview = $review;
        $myReview['admin_user_name'] = $row['username'] ?? '';
        $myReview['admin_user_email'] = $row['email'] ?: ($row['u_email'] ?? '');
    }
}

$reviewCount = count($reviews);
$averageRating = $reviewCount ? round($ratingTotal / $reviewCount, 1) : 0;

echo json_encode([
    'success' => true,
    'reviews' => $reviews,
    'average_rating' => $averageRating,
    'review_count' => $reviewCount,
    'review_product_ids' => $reviewProductIds,
    'my_review' => $myReview,
    'user_logged_in' => !empty($_SESSION['user_id']),
    'user_name' => $_SESSION['username'] ?? '',
    'user_email' => $_SESSION['email'] ?? '',
]);
?>
