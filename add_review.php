<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'session_logins.php';

function candybirdSendReviewAdminEmail($sheetProduct, $productId, $reviewId, $displayName, $rating, $reviewText, $userName, $userEmail) {
    global $smtp_server, $smtp_username1, $smtp_username5, $smtp_password, $smtp_port, $smtp_type, $website_company_name;

    if (empty($smtp_server) || empty($smtp_username5) || empty($smtp_password) || empty($smtp_username1)) {
        return;
    }

    require_once __DIR__ . '/PHPMailer/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer/src/SMTP.php';

    $productTitle = function_exists('getSheetProductDisplayTitle')
        ? getSheetProductDisplayTitle($sheetProduct)
        : trim(($sheetProduct['name'] ?? 'Product') . ' ' . ($sheetProduct['size'] ?? ''));
    $productUrl = 'https://www.candybird.co.za/product?id=' . rawurlencode((string) $productId) . '#pills-contact';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username5;
        $mail->Password = $smtp_password;
        if (!empty($smtp_type)) {
            $mail->SMTPSecure = $smtp_type;
        }
        $mail->Port = (int) ($smtp_port ?? 587);
        $mail->setFrom($smtp_username5, $website_company_name ?: 'CandyBird');
        $mail->addAddress($smtp_username1, 'CandyBird Admin');
        $mail->isHTML(true);
        $mail->Subject = 'New product review: ' . $productTitle;
        $mail->Body = '<div style="font-family:Arial,sans-serif;color:#2c2926;line-height:1.6;">'
            . '<h2 style="color:#5b1178;">New product review</h2>'
            . '<p><strong>Product:</strong> ' . htmlspecialchars($productTitle, ENT_QUOTES, 'UTF-8') . ' (#' . (int) $productId . ')</p>'
            . '<p><strong>Public display name:</strong> ' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Logged-in customer:</strong> ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . ' &lt;' . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . '&gt;</p>'
            . '<p><strong>Rating:</strong> ' . (int) $rating . '/5</p>'
            . '<p><strong>Review:</strong><br>' . nl2br(htmlspecialchars($reviewText, ENT_QUOTES, 'UTF-8')) . '</p>'
            . '<p><a href="' . htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8') . '">View product review</a></p>'
            . '<p style="font-size:12px;color:#777;">Review ID: ' . (int) $reviewId . '</p>'
            . '</div>';
        $mail->AltBody = "New product review\nProduct: {$productTitle} (#{$productId})\nPublic display name: {$displayName}\nCustomer: {$userName} <{$userEmail}>\nRating: {$rating}/5\nReview: {$reviewText}\n{$productUrl}";
        $mail->send();
    } catch (Exception $e) {
        error_log('Review admin email failed: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please log in to review this product.']);
        exit;
    }

    if (!($conn instanceof mysqli)) {
        echo json_encode(['success' => false, 'message' => 'Reviews are temporarily unavailable. Please try again shortly.']);
        exit;
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim($_SESSION['email'] ?? $_POST['email'] ?? '');
    $userId = (int) $_SESSION['user_id'];
    $guestIdentifier = '';
    $rating = max(1, min(5, (int) ($_POST['rating'] ?? 0)));
    $review = trim($_POST['review'] ?? '');
    $productId = (int) ($_POST['product_id'] ?? 0);
    $reviewId = (int) ($_POST['review_id'] ?? 0);

    if ($name === '') {
        $name = trim((string) ($_SESSION['username'] ?? 'CandyBird customer'));
    }
    $name = substr($name, 0, 80);

    if ($productId <= 0 || $rating <= 0 || $review === '' || $name === '') {
        echo json_encode(['success' => false, 'message' => 'Please add a display name, rating and review message.']);
        exit;
    }

    require_once __DIR__ . '/product_sheet_helpers.php';
    $sheetProduct = getSheetProductById($productId);
    if (!$sheetProduct) {
        echo json_encode(['success' => false, 'message' => 'This product could not be found.']);
        exit;
    }

    syncSheetProductMirrorToDb($conn, $sheetProduct);

    if ($reviewId > 0) {
        $ownerStmt = $conn->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ? LIMIT 1");
        if (!$ownerStmt) {
            echo json_encode(['success' => false, 'message' => 'Reviews are temporarily unavailable. Please try again shortly.']);
            exit;
        }
        $ownerStmt->bind_param('ii', $reviewId, $userId);
        $ownerStmt->execute();
        $ownerStmt->store_result();
        if ($ownerStmt->num_rows === 0) {
            $ownerStmt->close();
            echo json_encode(['success' => false, 'message' => 'This review could not be updated.']);
            exit;
        }
        $ownerStmt->close();

        $updateSql = "UPDATE reviews SET u_name = ?, rating = ?, comment = ? WHERE id = ? AND user_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            echo json_encode(['success' => false, 'message' => 'Reviews are temporarily unavailable. Please try again shortly.']);
            exit;
        }

        $updateStmt->bind_param('sisii', $name, $rating, $review, $reviewId, $userId);
        $updateStmt->execute();
        $updated = $updateStmt->affected_rows >= 0;
        $updateStmt->close();

        echo json_encode([
            'success' => $updated,
            'message' => $updated ? 'Review updated successfully.' : 'This review could not be updated.'
        ]);
        exit;
    }

    $reviewProductIds = [$productId];
    $productName = normalizeCandybirdSearchText($sheetProduct['name'] ?? $sheetProduct['title'] ?? '');
    if ($productName !== '') {
        foreach (getSheetProducts() as $candidateProduct) {
            $candidateId = (int) ($candidateProduct['id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }

            $candidateName = normalizeCandybirdSearchText($candidateProduct['name'] ?? $candidateProduct['title'] ?? '');
            if ($candidateName === $productName) {
                $reviewProductIds[] = $candidateId;
            }
        }
    }
    $reviewProductIds = array_values(array_unique(array_filter($reviewProductIds)));

    $placeholders = implode(',', array_fill(0, count($reviewProductIds), '?'));
    $existingReviewSql = "SELECT id FROM reviews WHERE product_id IN ($placeholders) AND user_id = ?";
    $existingReviewStmt = $conn->prepare($existingReviewSql);
    if (!$existingReviewStmt) {
        echo json_encode(['success' => false, 'message' => 'Reviews are temporarily unavailable. Please try again shortly.']);
        exit;
    }

    $types = str_repeat('i', count($reviewProductIds)) . 'i';
    $bindParams = array_merge([$types], $reviewProductIds, [$userId]);
    $bindRefs = [];
    foreach ($bindParams as $key => $value) {
        $bindRefs[$key] = &$bindParams[$key];
    }
    call_user_func_array([$existingReviewStmt, 'bind_param'], $bindRefs);
    $existingReviewStmt->execute();
    $existingReviewStmt->store_result();

    if ($existingReviewStmt->num_rows > 0) {
        $existingReviewStmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'You have already submitted a review for this product.']);
        exit;
    }

    $existingReviewStmt->close();

    // Save the new review to your database
    $sql = "INSERT INTO reviews (product_id, user_id, u_name, u_email, guest_identifier, rating, comment) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Reviews are temporarily unavailable. Please try again shortly.']);
        exit;
    }
    $stmt->bind_param("iisssis", $productId, $userId, $name, $email, $guestIdentifier, $rating, $review);

    if ($stmt->execute()) {
        // Insert statement executed successfully
        $newReviewId = $stmt->insert_id;
        candybirdSendReviewAdminEmail($sheetProduct, $productId, $newReviewId, $name, $rating, $review, $_SESSION['username'] ?? '', $email);
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully.']);
        exit;
    } else {
        // Insert statement failed
        $error_message = $stmt->error;
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => "Error submitting the review. Please try again."]);
        exit;
    }

} else {
    // Return an error response for invalid requests
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
