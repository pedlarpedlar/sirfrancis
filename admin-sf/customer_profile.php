<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    $redirect_url = 'customer_profile';
    header('Location: admin_login?redirect=' . urlencode($redirect_url));
    exit;
}

include 'dbh.inc.php';

function cbCustomerProfileText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbCustomerProfileMoney($value) {
    return 'R' . number_format((float) $value, 2);
}

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$email = trim((string) ($_GET['email'] ?? ''));

$customer = null;
if ($userId > 0) {
    $stmt = $conn->prepare("
        SELECT u.id AS user_id, u.username, u.email, u.created_at, u.last_login,
               ua.billing_first_name, ua.billing_last_name, ua.billing_phone_number,
               ua.billing_address, ua.billing_suburb, ua.billing_city, ua.billing_province, ua.billing_postal_code
        FROM users u
        LEFT JOIN user_addresses ua ON ua.user_id = u.id
        WHERE u.id = ?
        ORDER BY ua.id DESC
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$customer && $email !== '') {
    $stmt = $conn->prepare("
        SELECT u.id AS user_id, u.username, COALESCE(u.email, ua.billing_email_address) AS email, u.created_at, u.last_login,
               ua.billing_first_name, ua.billing_last_name, ua.billing_phone_number,
               ua.billing_address, ua.billing_suburb, ua.billing_city, ua.billing_province, ua.billing_postal_code
        FROM user_addresses ua
        LEFT JOIN users u ON u.email = ua.billing_email_address OR u.id = ua.user_id
        WHERE ua.billing_email_address = ? OR u.email = ?
        ORDER BY ua.id DESC
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('ss', $email, $email);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$resolvedEmail = trim((string) ($customer['email'] ?? $email));
$resolvedName = trim((string) (($customer['billing_first_name'] ?? '') . ' ' . ($customer['billing_last_name'] ?? '')));
if ($resolvedName === '') {
    $resolvedName = trim((string) ($customer['username'] ?? ''));
}
$orders = [];
$statementTotal = 0;
if ($resolvedEmail !== '' || $userId > 0) {
    $stmt = $conn->prepare("
        SELECT o.id, o.order_date, o.order_status, o.payment_status, o.grand_total_amount,
               COALESCE(u.email, ua.billing_email_address) AS order_email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN user_addresses ua ON o.guest_identifier = ua.guest_identifier OR o.user_id = ua.user_id
        WHERE (? > 0 AND o.user_id = ?) OR (? <> '' AND (u.email = ? OR ua.billing_email_address = ?))
        GROUP BY o.id
        ORDER BY o.order_date DESC
    ");
    if ($stmt) {
        $stmt->bind_param('iisss', $userId, $userId, $resolvedEmail, $resolvedEmail, $resolvedEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
            $statementTotal += (float) ($row['grand_total_amount'] ?? 0);
        }
        $stmt->close();
    }
}

include 'header.php';
include 'page_menues.php';
?>

<title>Customer Profile</title>

<style>
    .customer-profile-shell { padding: 28px 0 60px; }
    .customer-profile-hero { background: var(--sf-navy); color: #fff; border-radius: 8px; padding: 22px; margin-bottom: 18px; }
    .customer-profile-hero h1 { color: var(--sf-gold); margin-bottom: 6px; }
    .customer-profile-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); gap: 18px; }
    .customer-profile-card { background: #fff; border: 1px solid var(--sf-border); border-radius: 8px; padding: 18px; }
    .customer-profile-card h2 { color: #28364B; font-size: 20px; margin-bottom: 12px; }
    .customer-profile-line { border-bottom: 1px solid #f0e7de; padding: 8px 0; }
    .customer-profile-line span { color: #75675d; display: block; font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .customer-profile-table th { color: #28364B; font-size: 12px; text-transform: uppercase; }
    @media (max-width: 991px) { .customer-profile-grid { grid-template-columns: 1fr; } }
</style>

<div class="container customer-profile-shell">
    <div class="customer-profile-hero">
        <h1><?= cbCustomerProfileText(trim(($customer['billing_first_name'] ?? '') . ' ' . ($customer['billing_last_name'] ?? '')) ?: ($customer['username'] ?? 'Customer profile')) ?></h1>
        <p class="mb-0">Contact details, delivery details, and order history in one place.</p>
        <?php if ($resolvedEmail !== ''): ?>
            <a class="btn btn-warning mt-3" href="customer_email?email=<?= urlencode($resolvedEmail) ?>&name=<?= urlencode($resolvedName) ?>">Send email to customer</a>
        <?php endif; ?>
    </div>

    <?php if (!$customer && empty($orders)): ?>
        <div class="alert alert-warning">Customer details could not be found.</div>
    <?php endif; ?>

    <div class="customer-profile-grid">
        <div class="customer-profile-card">
            <h2>Customer Details</h2>
            <div class="customer-profile-line"><span>Email</span><a href="mailto:<?= cbCustomerProfileText($resolvedEmail) ?>"><?= cbCustomerProfileText($resolvedEmail ?: 'Not captured') ?></a></div>
            <div class="customer-profile-line"><span>Phone</span><?= cbCustomerProfileText($customer['billing_phone_number'] ?? 'Not captured') ?></div>
            <div class="customer-profile-line"><span>Username</span><?= cbCustomerProfileText($customer['username'] ?? 'Guest checkout') ?></div>
            <div class="customer-profile-line"><span>Last login</span><?= cbCustomerProfileText($customer['last_login'] ?? 'Not available') ?></div>
            <div class="customer-profile-line"><span>Created</span><?= cbCustomerProfileText($customer['created_at'] ?? 'Not available') ?></div>
            <h2 class="mt-4">Delivery</h2>
            <p class="mb-0">
                <?= cbCustomerProfileText($customer['billing_address'] ?? '') ?><br>
                <?= cbCustomerProfileText($customer['billing_suburb'] ?? '') ?> <?= cbCustomerProfileText($customer['billing_city'] ?? '') ?><br>
                <?= cbCustomerProfileText($customer['billing_province'] ?? '') ?> <?= cbCustomerProfileText($customer['billing_postal_code'] ?? '') ?>
            </p>
        </div>

        <div class="customer-profile-card">
            <h2>Orders</h2>
            <p><strong><?= count($orders) ?></strong> orders | Lifetime spend <?= cbCustomerProfileMoney($statementTotal) ?></p>
            <div class="table-responsive">
                <table class="table customer-profile-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><a href="order_details?order_id=<?= urlencode($order['id']) ?>">#<?= cbCustomerProfileText($order['id']) ?></a></td>
                                <td><?= cbCustomerProfileText($order['order_date']) ?></td>
                                <td><?= cbCustomerProfileText($order['order_status']) ?></td>
                                <td><?= ((int) $order['payment_status'] > 0) ? 'Paid' : 'Unpaid' ?></td>
                                <td><?= cbCustomerProfileMoney($order['grand_total_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="5">No orders found for this customer yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
