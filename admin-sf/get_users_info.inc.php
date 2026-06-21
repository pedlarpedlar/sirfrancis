<?php
// Include your database connection file
include 'dbh.inc.php';

// Fetch user data with additional statistics
$sql = "
    SELECT 
    u.id, u.username, u.email, u.status, u.created_at, 
    MAX(s.start_time) AS last_login,
    u.profile_picture,
    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
    (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.id) AS review_count,
    (SELECT COUNT(*) FROM cart c WHERE c.user_id = u.id) AS cart_count,
    (SELECT COUNT(*) FROM wishlist w WHERE w.user_id = u.id) AS wishlist_count,
    (SELECT COUNT(*) FROM compare cm WHERE cm.user_id = u.id) AS compare_count,
    0 AS comment_count,
    (SELECT s.is_subscribed FROM subscribers s WHERE s.email = u.email LIMIT 1) AS is_subscribed,
    (SELECT GROUP_CONCAT(c.product_id) FROM cart c WHERE c.user_id = u.id) AS cart_product_ids,
    (SELECT GROUP_CONCAT(w.product_id) FROM wishlist w WHERE w.user_id = u.id) AS wishlist_product_ids,
    (SELECT GROUP_CONCAT(cm.product_id) FROM compare cm WHERE cm.user_id = u.id) AS compare_product_ids
FROM 
    users u
LEFT JOIN 
    sessions s ON u.id = s.user_id
GROUP BY 
    u.id, u.username, u.email, u.status, u.created_at, u.profile_picture;
";

$result = $conn->query($sql);
$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    echo json_encode(['error' => 'No users found']);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['data' => $users]);
?>
