<?php
include 'session_logins.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if (empty($_SESSION['user_id']) && empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to manage this review.']);
    exit;
}

if (!($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Reviews are temporarily unavailable. Please try again shortly.']);
    exit;
}

$reviewId = (int) ($_POST['review_id'] ?? 0);
if ($reviewId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Review is missing.']);
    exit;
}

if (!empty($_SESSION['admin_id'])) {
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $reviewId);
    }
} else {
    $userId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $reviewId, $userId);
    }
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Review could not be deleted right now.']);
    exit;
}

$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();

echo json_encode([
    'success' => $deleted,
    'message' => $deleted ? 'Review deleted.' : 'This review could not be deleted.'
]);
?>
