<?php
include 'dbh.inc.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch the action from POST data
    $action = $_POST['action']; // 'create', 'edit', or 'delete'
    $couponId = $_POST['coupon_id'] ?? null; // Coupon ID for update or delete

    // Common form fields
    $couponCode = mysqli_real_escape_string($conn, $_POST['coupon_code']);
    $discountType = mysqli_real_escape_string($conn, $_POST['discount_type']);
    $discountValue = mysqli_real_escape_string($conn, $_POST['discount_value']);
    $scope = mysqli_real_escape_string($conn, $_POST['scope']);
    $product_ids = mysqli_real_escape_string($conn, $_POST['product_ids']);
    $category_ids = mysqli_real_escape_string($conn, $_POST['category_ids']);
    $maxUsages = mysqli_real_escape_string($conn, $_POST['max_usages']);
    $usagePerUser = mysqli_real_escape_string($conn, $_POST['usage_per_user']);
    $minOrderValue = mysqli_real_escape_string($conn, $_POST['min_order_value']) ?? 0;
    $startDate = mysqli_real_escape_string($conn, $_POST['start_date']);
    $expiryDate = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    $applicableUsers = mysqli_real_escape_string($conn, $_POST['applicable_users']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);


    // Validate required fields
    if (empty($couponCode) || empty($discountType) || empty($discountValue) || empty($scope) || empty($maxUsages) || empty($usagePerUser) || empty($startDate) || empty($expiryDate)) {
        echo "Error: Missing required fields.";
        exit;
    }

    // Validate date formats
    $startDate = DateTime::createFromFormat('Y-m-d', $startDate);
    $expiryDate = DateTime::createFromFormat('Y-m-d', $expiryDate);
    if (!$startDate || !$expiryDate) {
        echo "Error: Invalid date format. Use YYYY-MM-DD.";
        exit;
    }

    // Check if the coupon exists for 'edit' and 'delete' actions
    if (($action === 'edit' || $action === 'delete') && !$couponId) {
        echo "Error: Coupon ID is required for editing or deleting.";
        exit;
    }

    if ($action === 'edit' || $action === 'delete') {
        $stmt = $conn->prepare("SELECT id FROM coupons WHERE id = ?");
        $stmt->bind_param("i", $couponId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo "Error: Coupon does not exist.";
            $stmt->close();
            exit;
        }
        $stmt->close();
    }

    if ($action === 'create') {
        // Prepare and bind for insert
        $stmt = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, scope, product_ids, category_ids, max_usages, usage_per_user, min_order_value, start_date, expiry_date, applicable_users, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsdiiisssss", $couponCode, $discountType, $discountValue, $scope, $product_ids, $category_ids, $maxUsages, $usagePerUser, $minOrderValue, $startDate->format('Y-m-d'), $expiryDate->format('Y-m-d'), $applicableUsers, $note);


        if ($stmt->execute()) {
            echo "Coupon created successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();

    } elseif ($action === 'edit') {
        // Prepare and bind for update
        $stmt = $conn->prepare("UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, scope = ?, product_ids = ?, category_ids = ?, max_usages = ?, usage_per_user = ?, min_order_value = ?, start_date = ?, expiry_date = ?, applicable_users = ?, note = ? WHERE id = ?");
        $stmt->bind_param("ssdsdiiisssssi", $couponCode, $discountType, $discountValue, $scope, $product_ids, $category_ids, $maxUsages, $usagePerUser, $minOrderValue, $startDate->format('Y-m-d'), $expiryDate->format('Y-m-d'), $applicableUsers, $note, $couponId);


        if ($stmt->execute()) {
            echo "Coupon updated successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();

    } elseif ($action === 'delete') {
        // Prepare and bind for delete
        $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->bind_param("i", $couponId);

        if ($stmt->execute()) {
            echo "Coupon deleted successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>