<?php
include 'dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $country = $_POST['country'];

    // Use prepared statement to avoid SQL injection
    $query = "SELECT DISTINCT id, province, shipping_cost FROM shipping_zones WHERE country = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $country);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $shippingId, $province, $shippingCost);

    $options = '<option value="" disabled selected>Select Province</option>';
    while (mysqli_stmt_fetch($stmt)) {
        $options .= '<option value="' . htmlspecialchars($province, ENT_QUOTES, 'UTF-8') . '" data-shipping="' . htmlspecialchars($shippingCost, ENT_QUOTES, 'UTF-8') . '" data-shippingid="' . htmlspecialchars($shippingId, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($province, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    header('Content-Type: application/json');
    echo json_encode(['options' => $options]);
    exit();
}
?>
