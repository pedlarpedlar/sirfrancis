<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login?redirect=manage_users');
    exit();
}

include 'dbh.inc.php';

function cbCustomersText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbCustomersMoney($value) {
    return 'R' . number_format((float) $value, 2);
}

function cbCustomersColumns($conn, $table) {
    $columns = [];
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $result = $conn->query("SHOW COLUMNS FROM `$safeTable`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = true;
        }
    }
    return $columns;
}

function cbCustomersHas($columns, $column) {
    return isset($columns[$column]);
}

function cbCustomersSqlExpr($columns, $alias, $preferred, $fallback = "''") {
    foreach ((array) $preferred as $column) {
        if (cbCustomersHas($columns, $column)) {
            return $alias . '.`' . $column . '`';
        }
    }
    return $fallback;
}

function cbCustomersAddressExpr($addressColumns, $alias = 'ua') {
    $parts = [
        cbCustomersSqlExpr($addressColumns, $alias, ['billing_street_address_1', 'billing_address']),
        cbCustomersSqlExpr($addressColumns, $alias, ['billing_street_address_2', 'billing_suburb']),
        cbCustomersSqlExpr($addressColumns, $alias, ['billing_city']),
        cbCustomersSqlExpr($addressColumns, $alias, ['billing_province']),
        cbCustomersSqlExpr($addressColumns, $alias, ['billing_post_code', 'billing_postal_code']),
        cbCustomersSqlExpr($addressColumns, $alias, ['billing_country']),
    ];
    return 'TRIM(CONCAT_WS(\', \', ' . implode(', ', $parts) . '))';
}

function cbCustomersRows($conn, $sql) {
    $rows = [];
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$userColumns = cbCustomersColumns($conn, 'users');
$addressColumns = cbCustomersColumns($conn, 'user_addresses');
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['customer_action'] ?? '';
    if ($action === 'save_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'active'));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please add a username and valid email address.';
            $messageType = 'danger';
        } elseif ($userId > 0) {
            $fields = ['username = ?', 'email = ?'];
            $types = 'ss';
            $values = [$username, $email];
            if (cbCustomersHas($userColumns, 'status')) {
                $fields[] = 'status = ?';
                $types .= 's';
                $values[] = $status;
            }
            if ($password !== '') {
                $fields[] = 'password_hash = ?';
                $types .= 's';
                $values[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $types .= 'i';
            $values[] = $userId;
            $stmt = $conn->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param($types, ...$values);
                $ok = $stmt->execute();
                $message = $ok ? 'Customer updated.' : 'Customer could not be updated.';
                $messageType = $ok ? 'success' : 'danger';
                $stmt->close();
            } else {
                $message = 'Customer could not be updated.';
                $messageType = 'danger';
            }
        } else {
            $passwordHash = password_hash($password !== '' ? $password : bin2hex(random_bytes(6)), PASSWORD_DEFAULT);
            $columns = ['username', 'password_hash', 'email'];
            $placeholders = ['?', '?', '?'];
            $types = 'sss';
            $values = [$username, $passwordHash, $email];
            if (cbCustomersHas($userColumns, 'status')) {
                $columns[] = 'status';
                $placeholders[] = '?';
                $types .= 's';
                $values[] = $status;
            }
            $stmt = $conn->prepare('INSERT INTO users (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')');
            if ($stmt) {
                $stmt->bind_param($types, ...$values);
                $ok = $stmt->execute();
                $message = $ok ? 'Customer added.' : 'Customer could not be added. The email or username may already exist.';
                $messageType = $ok ? 'success' : 'danger';
                $stmt->close();
            } else {
                $message = 'Customer could not be added.';
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $ok = $stmt->execute();
                $message = $ok ? 'Customer deleted.' : 'Customer could not be deleted because existing records depend on this account.';
                $messageType = $ok ? 'success' : 'danger';
                $stmt->close();
            }
        }
    }
}

$createdExpr = cbCustomersHas($userColumns, 'created_at') ? 'u.created_at' : 'NULL';
$statusExpr = cbCustomersHas($userColumns, 'status') ? 'u.status' : "'active'";
$lastLoginExpr = cbCustomersHas($userColumns, 'last_login') ? 'u.last_login' : "(SELECT MAX(s.start_time) FROM sessions s WHERE s.user_id = u.id)";
$addressExpr = cbCustomersAddressExpr($addressColumns, 'ua');
$phoneExpr = cbCustomersSqlExpr($addressColumns, 'ua', ['billing_phone_number']);
$firstNameExpr = cbCustomersSqlExpr($addressColumns, 'ua', ['billing_first_name']);
$lastNameExpr = cbCustomersSqlExpr($addressColumns, 'ua', ['billing_last_name']);
$emailExpr = cbCustomersSqlExpr($addressColumns, 'ua', ['billing_email_address']);
$guestExpr = cbCustomersSqlExpr($addressColumns, 'ua', ['guest_identifier']);

$registeredSql = "
    SELECT
        u.id,
        u.username,
        u.email,
        $statusExpr AS status,
        $createdExpr AS created_at,
        $lastLoginExpr AS last_login,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
        (SELECT COALESCE(SUM(o.grand_total_amount), 0) FROM orders o WHERE o.user_id = u.id) AS total_spend,
        (SELECT $phoneExpr FROM user_addresses ua WHERE ua.user_id = u.id ORDER BY ua.id DESC LIMIT 1) AS phone,
        (SELECT $addressExpr FROM user_addresses ua WHERE ua.user_id = u.id ORDER BY ua.id DESC LIMIT 1) AS address
    FROM users u
    ORDER BY " . (cbCustomersHas($userColumns, 'created_at') ? 'u.created_at DESC,' : '') . " u.id DESC
    LIMIT 500
";
$registeredCustomers = cbCustomersRows($conn, $registeredSql);

$guestJoin = cbCustomersHas($addressColumns, 'guest_identifier') ? 'LEFT JOIN orders o ON o.guest_identifier = ua.guest_identifier' : 'LEFT JOIN orders o ON 1 = 0';
$guestSql = "
    SELECT
        MAX(ua.id) AS address_id,
        MAX(COALESCE(NULLIF($firstNameExpr, ''), '')) AS first_name,
        MAX(COALESCE(NULLIF($lastNameExpr, ''), '')) AS last_name,
        $emailExpr AS email,
        MAX($phoneExpr) AS phone,
        MAX($addressExpr) AS address,
        MAX($guestExpr) AS guest_identifier,
        COUNT(DISTINCT o.id) AS order_count,
        COALESCE(SUM(o.grand_total_amount), 0) AS total_spend,
        MAX(o.order_date) AS latest_order
    FROM user_addresses ua
    LEFT JOIN users u ON LOWER(u.email) = LOWER($emailExpr)
    $guestJoin
    WHERE ($emailExpr IS NOT NULL AND $emailExpr <> '') AND u.id IS NULL
    GROUP BY $emailExpr
    ORDER BY latest_order DESC, address_id DESC
    LIMIT 500
";
$guestCustomers = cbCustomersRows($conn, $guestSql);

$registeredEmails = array_values(array_unique(array_filter(array_map(static function($row) {
    return trim((string) ($row['email'] ?? ''));
}, $registeredCustomers), static function($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
})));
$guestEmails = array_values(array_unique(array_filter(array_map(static function($row) {
    return trim((string) ($row['email'] ?? ''));
}, $guestCustomers), static function($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
})));

include 'header.php';
include 'page_menues.php';
?>

<title>Customers</title>

<style>
    .customers-shell { padding: 30px 0 70px; }
    .customers-hero { background:#2d1739; color:#fff; border-radius:8px; padding:24px; margin-bottom:18px; }
    .customers-hero h1 { color:#fcb42f; margin-bottom:6px; }
    .customers-panel { background:#fff; border:1px solid #eadfd2; border-radius:8px; padding:18px; margin-bottom:18px; }
    .customers-panel h2 { color:#5b1178; font-size:21px; margin-bottom:12px; }
    .customer-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .customer-table th { color:#5b1178; font-size:12px; text-transform:uppercase; white-space:nowrap; }
    .customer-table td { vertical-align:top; }
    @media (max-width: 767px) {
        .customers-panel { padding:14px; }
        .customer-actions .btn { width:100%; }
    }
</style>

<div class="container customers-shell">
    <div class="customers-hero">
        <h1>Customers</h1>
        <p class="mb-0">Registered accounts, guest checkout customers, contact details, order history and quick email copying for broadcasts.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= cbCustomersText($messageType) ?>"><?= cbCustomersText($message) ?></div>
    <?php endif; ?>

    <div class="customers-panel">
        <h2>Add or Edit Registered Customer</h2>
        <form method="post" id="customerForm">
            <input type="hidden" name="customer_action" value="save_user">
            <input type="hidden" name="user_id" id="customerUserId" value="">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Username</label>
                    <input type="text" class="form-control" name="username" id="customerUsername" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email" id="customerEmail" required>
                </div>
                <div class="form-group col-md-2">
                    <label>Status</label>
                    <select class="form-control" name="status" id="customerStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Password</label>
                    <input type="password" class="form-control" name="password" id="customerPassword" placeholder="Leave blank to keep">
                </div>
            </div>
            <div class="customer-actions">
                <button class="btn btn-primary" type="submit">Save customer</button>
                <button class="btn btn-outline-secondary" type="button" id="resetCustomerForm">Clear form</button>
            </div>
        </form>
    </div>

    <div class="customers-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3" style="gap:12px;">
            <div>
                <h2>Registered Customers</h2>
                <p class="mb-0 text-muted"><?= number_format(count($registeredCustomers)) ?> accounts, latest first.</p>
            </div>
            <button class="btn btn-outline-primary copy-emails" type="button" data-emails="<?= cbCustomersText(implode(',', $registeredEmails)) ?>">Copy registered emails</button>
        </div>
        <div class="table-responsive">
            <table class="table customer-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Orders</th>
                        <th>Latest</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registeredCustomers as $customer): ?>
                        <tr>
                            <td><strong><?= cbCustomersText($customer['username']) ?></strong><br><small>#<?= (int) $customer['id'] ?> | <?= cbCustomersText($customer['status']) ?></small></td>
                            <td><a href="mailto:<?= cbCustomersText($customer['email']) ?>"><?= cbCustomersText($customer['email']) ?></a><br><small><?= cbCustomersText($customer['phone'] ?: 'No phone captured') ?></small></td>
                            <td><?= number_format((int) $customer['order_count']) ?><br><small><?= cbCustomersMoney($customer['total_spend']) ?></small></td>
                            <td><?= cbCustomersText($customer['created_at'] ?: 'Unknown') ?><br><small>Last login: <?= cbCustomersText($customer['last_login'] ?: 'Not tracked') ?></small></td>
                            <td><?= cbCustomersText($customer['address'] ?: 'No address captured') ?></td>
                            <td>
                                <div class="customer-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="customer_profile?user_id=<?= (int) $customer['id'] ?>">Profile</a>
                                    <button class="btn btn-sm btn-outline-secondary edit-customer" type="button"
                                        data-id="<?= (int) $customer['id'] ?>"
                                        data-username="<?= cbCustomersText($customer['username']) ?>"
                                        data-email="<?= cbCustomersText($customer['email']) ?>"
                                        data-status="<?= cbCustomersText($customer['status']) ?>">Edit</button>
                                    <form method="post" onsubmit="return confirm('Delete this customer account? Orders may prevent deletion.');">
                                        <input type="hidden" name="customer_action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= (int) $customer['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($registeredCustomers)): ?>
                        <tr><td colspan="6">No registered customers found yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="customers-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3" style="gap:12px;">
            <div>
                <h2>Guest Checkout Customers</h2>
                <p class="mb-0 text-muted">People who ordered without registering, grouped by email address.</p>
            </div>
            <button class="btn btn-outline-primary copy-emails" type="button" data-emails="<?= cbCustomersText(implode(',', $guestEmails)) ?>">Copy guest emails</button>
        </div>
        <div class="table-responsive">
            <table class="table customer-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Orders</th>
                        <th>Profile</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guestCustomers as $guest): ?>
                        <tr>
                            <td><strong><?= cbCustomersText(trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? '')) ?: 'Guest customer') ?></strong></td>
                            <td><a href="mailto:<?= cbCustomersText($guest['email']) ?>"><?= cbCustomersText($guest['email']) ?></a></td>
                            <td><?= cbCustomersText($guest['phone'] ?: 'No phone captured') ?></td>
                            <td><?= cbCustomersText($guest['address'] ?: 'No address captured') ?></td>
                            <td><?= number_format((int) $guest['order_count']) ?><br><small><?= cbCustomersMoney($guest['total_spend']) ?></small></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="customer_profile?email=<?= urlencode($guest['email']) ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($guestCustomers)): ?>
                        <tr><td colspan="6">No guest checkout customers found yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-customer').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('customerUserId').value = button.dataset.id || '';
            document.getElementById('customerUsername').value = button.dataset.username || '';
            document.getElementById('customerEmail').value = button.dataset.email || '';
            document.getElementById('customerStatus').value = button.dataset.status || 'active';
            document.getElementById('customerPassword').value = '';
            document.getElementById('customerForm').scrollIntoView({behavior: 'smooth', block: 'start'});
        });
    });
    document.getElementById('resetCustomerForm').addEventListener('click', function() {
        document.getElementById('customerUserId').value = '';
        document.getElementById('customerUsername').value = '';
        document.getElementById('customerEmail').value = '';
        document.getElementById('customerStatus').value = 'active';
        document.getElementById('customerPassword').value = '';
    });
    document.querySelectorAll('.copy-emails').forEach(function(button) {
        button.addEventListener('click', function() {
            var emails = (button.dataset.emails || '').split(',').filter(Boolean).join(', ');
            if (!emails) {
                button.textContent = 'No emails to copy';
                return;
            }
            navigator.clipboard.writeText(emails).then(function() {
                var original = button.textContent;
                button.textContent = 'Copied ' + emails.split(',').length + ' emails';
                setTimeout(function() { button.textContent = original; }, 1800);
            });
        });
    });
});
</script>

<?php include '../footer.php'; ?>
