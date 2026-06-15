<?php
header('Content-Type: application/json');
// Database connection
include('dbh.inc.php'); // Assuming you have your database connection here

$sql = "SELECT
    o.id AS order_id,
    o.order_date,
    o.grand_total_amount,
    o.subtotal_amount,
    o.discount_amount AS order_discount,
    o.shipping_amount,
    o.coupon_amount,
    o.order_status,
    u.id AS user_id,
    u.username,
    u.email
FROM
    orders o
    LEFT JOIN users u ON o.user_id = u.id
ORDER BY
    o.order_date DESC";

$result = mysqli_query($conn, $sql);

$data = array();
if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
} else {
    echo json_encode(['error' => 'No users found']);
    exit;
}

echo json_encode(array('data' => $data));
?>