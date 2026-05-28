<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "manage_orders";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';

function cbOrderText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbOrderMoney($value) {
    return 'R' . number_format((float) $value, 2);
}

function cbAdminListOrderHasSuccessfulPayfastPayment($conn, $orderId) {
    if (!($conn instanceof mysqli) || (int) $orderId <= 0) {
        return false;
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'payfast_payment_id'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $stmt = $conn->prepare("SELECT payfast_payment_id FROM orders WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!empty($row['payfast_payment_id'])) {
                return true;
            }
        }
    }

    $tableCheck = $conn->query("SHOW TABLES LIKE 'payment_checks'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->prepare("SELECT id FROM payment_checks WHERE order_id = ? AND check_result = 1 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $hasPaidCheck = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            if ($hasPaidCheck) {
                return true;
            }
        }

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS failed_checks,
                   SUM(CASE WHEN check_name <> 'pfValidIP' THEN 1 ELSE 0 END) AS other_failed_checks
            FROM payment_checks
            WHERE order_id = ? AND check_result = 0
        ");
        if ($stmt) {
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return (int) ($row['failed_checks'] ?? 0) > 0 && (int) ($row['other_failed_checks'] ?? 0) === 0;
        }
    }

    return false;
}

$statusOptions = ['Pending', 'Awaiting EFT payment', 'Processing', 'Packing', 'Ready to collect', 'Shipped', 'Partially delivered', 'Partially collected', 'Complete', 'Cancelled'];

function cbOrderStatusKey($status) {
    $key = strtolower(trim((string) $status));
    return preg_replace('/[^a-z0-9]+/', '-', $key) ?: 'pending';
}

$sql = "SELECT
    o.id AS order_id,
    o.order_date,
    o.grand_total_amount,
    o.subtotal_amount,
    o.discount_amount AS order_discount,
    o.shipping_amount,
    o.shipping_discount_amount,
    o.coupon_amount,
    o.shipping_address,
    o.order_status,
    o.payment_status,
    o.order_notes,
    u.id AS user_id,
    COALESCE(u.username, CONCAT(ua.billing_first_name, ' ', ua.billing_last_name), 'Guest customer') AS customer_name,
    COALESCE(u.email, ua.billing_email_address, '') AS email,
    ua.billing_phone_number,
    ua.guest_identifier AS guest_identifier
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
LEFT JOIN user_addresses ua ON o.guest_identifier = ua.guest_identifier OR o.user_id = ua.user_id
GROUP BY o.id
ORDER BY o.order_date DESC, o.id DESC";

$result = $conn->query($sql);
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ((int) $row['payment_status'] === 0 && cbAdminListOrderHasSuccessfulPayfastPayment($conn, (int) $row['order_id'])) {
            $row['payment_status'] = 1;
            $conn->query("UPDATE orders SET payment_status = 1 WHERE id = " . (int) $row['order_id']);
        }
        $orders[] = $row;
    }
}

$counts = [
    'all' => count($orders),
    'pending' => 0,
    'processing' => 0,
    'paid' => 0,
    'unpaid' => 0,
];

foreach ($orders as $order) {
    $status = cbOrderStatusKey($order['order_status'] ?: 'pending');
    if (isset($counts[$status])) {
        $counts[$status]++;
    }
    if ((int) $order['payment_status'] > 0) {
        $counts['paid']++;
    } else {
        $counts['unpaid']++;
    }
}

$adminBankingDetailsPlain = '';
$settingsResult = $conn->query("SELECT banking_details FROM admin_website_settings ORDER BY id ASC LIMIT 1");
if ($settingsResult && ($settingsRow = $settingsResult->fetch_assoc())) {
    $adminBankingDetailsPlain = trim(strip_tags((string) ($settingsRow['banking_details'] ?? '')));
}

include 'header.php';
include 'page_menues.php';
?>

<title>Manage Orders</title>

<style>
    .orders-shell { padding: 28px 0 60px; }
    .orders-hero { background: #2d1739; color: #fff; border-radius: 8px; padding: 22px; margin-bottom: 18px; }
    .orders-hero h1 { color: #fcb42f; margin-bottom: 6px; }
    .orders-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
    .order-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 18px; }
    .order-stat { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 14px; }
    .order-stat strong { display: block; color: #5b1178; font-size: 24px; line-height: 1.1; }
    .order-stat span { color: #6d6270; font-size: 13px; font-weight: 700; text-transform: uppercase; }
    .orders-panel { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 18px; }
    .orders-toolbar { display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .orders-toolbar input, .orders-toolbar select { max-width: 260px; }
    .orders-table th.sortable { cursor: pointer; user-select: none; }
    .orders-table th.sortable:hover { color: #5b1178; }
    .status-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 10px; font-size: 12px; font-weight: 800; }
    .status-pending { background: #fff4d8; color: #7a4e00; }
    .status-processing { background: #ede2ff; color: #5b1178; }
    .status-packing { background: #e5efff; color: #1f4f90; }
    .status-ready-to-collect { background: #fff0c7; color: #7b4b00; }
    .status-shipped { background: #dff7ff; color: #116177; }
    .status-partially-delivered,
    .status-partially-collected { background: #fff7e6; color: #8a5200; }
    .status-complete { background: #e3f8e8; color: #186f33; }
    .status-cancelled { background: #ffe4e4; color: #9f1d1d; }
    .payment-pill { display: inline-flex; border-radius: 999px; padding: 5px 10px; font-size: 12px; font-weight: 800; background: #f4f1ed; color: #675c69; }
    .payment-paid { background: #e3f8e8; color: #186f33; }
    .order-action-row { display: flex; flex-wrap: wrap; gap: 6px; min-width: 245px; }
    .mobile-order-card { display: none; border: 1px solid #eadfd2; border-radius: 8px; padding: 14px; margin-bottom: 12px; background: #fff; }
    .mobile-order-card h3 { font-size: 18px; color: #5b1178; margin-bottom: 4px; }
    .mobile-order-meta { color: #6d6270; font-size: 13px; margin-bottom: 10px; }
    .modal textarea { min-height: 130px; }
    @media (max-width: 991px) {
        .order-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .orders-table-wrap { display: none; }
        .mobile-order-card { display: block; }
        .orders-toolbar input, .orders-toolbar select { max-width: 100%; width: 100%; }
    }
    @media (max-width: 575px) {
        .order-stats { grid-template-columns: 1fr; }
        .orders-actions .btn, .order-action-row .btn { width: 100%; }
    }
</style>

<div class="container orders-shell">
    <div class="orders-hero">
        <h1>Order Control Center</h1>
        <p class="mb-0">Create orders for customers, edit order carts, update statuses, send friendly client updates, and delete orders when needed.</p>
        <div class="orders-actions">
            <a href="create_order" class="btn btn-warning">Create order for customer</a>
            <a href="index" class="btn btn-light">Dashboard</a>
            <a href="../products" class="btn btn-outline-light" target="_blank" rel="noopener noreferrer">Shop as customer</a>
        </div>
    </div>

    <div class="order-stats">
        <div class="order-stat"><strong><?= number_format($counts['all']) ?></strong><span>Total</span></div>
        <div class="order-stat"><strong><?= number_format($counts['pending']) ?></strong><span>Pending</span></div>
        <div class="order-stat"><strong><?= number_format($counts['processing']) ?></strong><span>Processing</span></div>
        <div class="order-stat"><strong><?= number_format($counts['paid']) ?></strong><span>Paid</span></div>
        <div class="order-stat"><strong><?= number_format($counts['unpaid']) ?></strong><span>Unpaid</span></div>
    </div>

    <div class="orders-panel">
        <div class="alert d-none" id="ordersPageAlert"></div>
        <div class="orders-toolbar">
            <div>
                <h2 class="h4 mb-1">Orders</h2>
                <p class="mb-0 text-muted">Use Edit Cart to change products and quantities. Use Update Client when you want to email a status message.</p>
            </div>
            <div class="d-flex flex-wrap" style="gap: 10px;">
                <input type="search" class="form-control" id="orderSearch" placeholder="Search order, name, email">
                <select class="form-control" id="statusFilter">
                    <option value="">All statuses</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= cbOrderText(strtolower($status)) ?>"><?= cbOrderText($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="orders-table-wrap table-responsive">
            <table class="table orders-table" id="ordersTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="order">Order</th>
                        <th class="sortable" data-sort="customer">Customer</th>
                        <th class="sortable" data-sort="status">Status</th>
                        <th class="sortable" data-sort="payment">Payment</th>
                        <th class="sortable" data-sort="total">Total</th>
                        <th class="sortable" data-sort="date">Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): 
                    $status = $order['order_status'] ?: 'Pending';
                    $statusKey = cbOrderStatusKey($status);
                    $shippingPayable = max(0, (float) $order['shipping_amount'] - (float) $order['shipping_discount_amount']);
                    $searchText = strtolower($order['order_id'] . ' ' . $order['customer_name'] . ' ' . $order['email'] . ' ' . $status);
                ?>
                    <tr class="order-row" data-status="<?= cbOrderText($statusKey) ?>" data-payment="<?= ((int) $order['payment_status'] > 0) ? 'paid' : 'unpaid' ?>" data-search="<?= cbOrderText($searchText) ?>" data-order="<?= cbOrderText($order['order_id']) ?>" data-customer="<?= cbOrderText(strtolower($order['customer_name'])) ?>" data-total="<?= cbOrderText($order['grand_total_amount']) ?>" data-date="<?= cbOrderText(strtotime($order['order_date'])) ?>">
                        <td><a href="order_details?order_id=<?= urlencode($order['order_id']) ?>"><strong>#<?= cbOrderText($order['order_id']) ?></strong></a><br><small><?= cbOrderText($order['billing_phone_number']) ?></small></td>
                        <td>
                            <?php $customerHref = !empty($order['user_id']) ? 'customer_profile?user_id=' . urlencode($order['user_id']) : 'customer_profile?email=' . urlencode($order['email']); ?>
                            <a href="<?= cbOrderText($customerHref) ?>"><strong><?= cbOrderText(trim($order['customer_name']) ?: 'Guest customer') ?></strong></a><br>
                            <a href="mailto:<?= cbOrderText($order['email']) ?>"><?= cbOrderText($order['email'] ?: 'No email') ?></a>
                        </td>
                        <td><span class="status-pill status-<?= cbOrderText($statusKey) ?>"><?= cbOrderText($status) ?></span></td>
                        <td><span class="payment-pill <?= ((int) $order['payment_status'] > 0) ? 'payment-paid' : '' ?>"><?= ((int) $order['payment_status'] > 0) ? 'Paid' : 'Unpaid' ?></span></td>
                        <td>
                            <strong><?= cbOrderMoney($order['grand_total_amount']) ?></strong><br>
                            <small>Ship <?= cbOrderMoney($shippingPayable) ?> · Coupon <?= cbOrderMoney($order['coupon_amount']) ?></small>
                        </td>
                        <td><?= cbOrderText($order['order_date']) ?></td>
                        <td>
                            <div class="order-action-row">
                                <a class="btn btn-outline-primary btn-sm" href="order_details?order_id=<?= urlencode($order['order_id']) ?>">View</a>
                                <a class="btn btn-dark btn-sm" href="manage_order?order_id=<?= urlencode($order['order_id']) ?>">Edit Cart</a>
                                <?php if ((int) $order['payment_status'] === 0): ?>
                                    <button class="btn btn-success btn-sm mark-paid-btn" type="button" data-order-id="<?= cbOrderText($order['order_id']) ?>">Mark Paid</button>
                                <?php endif; ?>
                                <button class="btn btn-warning btn-sm order-status-btn" type="button"
                                    data-order-id="<?= cbOrderText($order['order_id']) ?>"
                                    data-current-status="<?= cbOrderText($status) ?>"
                                    data-customer="<?= cbOrderText(trim($order['customer_name']) ?: 'customer') ?>"
                                    data-total="<?= cbOrderText(number_format((float) $order['grand_total_amount'], 2, '.', '')) ?>">
                                    Update Client
                                </button>
                                <button class="btn btn-outline-danger btn-sm cancel-order-btn" type="button"
                                    data-order-id="<?= cbOrderText($order['order_id']) ?>"
                                    data-total="<?= cbOrderText($order['grand_total_amount']) ?>"
                                    data-paid="<?= ((int) $order['payment_status'] > 0) ? '1' : '0' ?>">
                                    Cancel
                                </button>
                                <button class="btn btn-outline-danger btn-sm delete-order" type="button" data-order-id="<?= cbOrderText($order['order_id']) ?>">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mobile-orders">
            <?php foreach ($orders as $order): 
                $status = $order['order_status'] ?: 'Pending';
                $statusKey = cbOrderStatusKey($status);
                $searchText = strtolower($order['order_id'] . ' ' . $order['customer_name'] . ' ' . $order['email'] . ' ' . $status);
            ?>
                <div class="mobile-order-card order-row" data-status="<?= cbOrderText($statusKey) ?>" data-search="<?= cbOrderText($searchText) ?>">
                    <h3>#<?= cbOrderText($order['order_id']) ?> · <?= cbOrderMoney($order['grand_total_amount']) ?></h3>
                    <div class="mobile-order-meta">
                        <?= cbOrderText(trim($order['customer_name']) ?: 'Guest customer') ?><br>
                        <?= cbOrderText($order['email'] ?: 'No email') ?><br>
                        <?= cbOrderText($order['order_date']) ?>
                    </div>
                    <p>
                        <span class="status-pill status-<?= cbOrderText($statusKey) ?>"><?= cbOrderText($status) ?></span>
                        <span class="payment-pill <?= ((int) $order['payment_status'] > 0) ? 'payment-paid' : '' ?>"><?= ((int) $order['payment_status'] > 0) ? 'Paid' : 'Unpaid' ?></span>
                    </p>
                    <div class="order-action-row">
                        <a class="btn btn-outline-primary btn-sm" href="order_details?order_id=<?= urlencode($order['order_id']) ?>">View</a>
                        <a class="btn btn-dark btn-sm" href="manage_order?order_id=<?= urlencode($order['order_id']) ?>">Edit Cart</a>
                        <button class="btn btn-warning btn-sm order-status-btn" type="button"
                            data-order-id="<?= cbOrderText($order['order_id']) ?>"
                            data-current-status="<?= cbOrderText($status) ?>"
                            data-customer="<?= cbOrderText(trim($order['customer_name']) ?: 'customer') ?>"
                            data-total="<?= cbOrderText(number_format((float) $order['grand_total_amount'], 2, '.', '')) ?>">
                            Update Client
                        </button>
                        <button class="btn btn-outline-danger btn-sm cancel-order-btn" type="button"
                            data-order-id="<?= cbOrderText($order['order_id']) ?>"
                            data-total="<?= cbOrderText($order['grand_total_amount']) ?>"
                            data-paid="<?= ((int) $order['payment_status'] > 0) ? '1' : '0' ?>">
                            Cancel
                        </button>
                        <button class="btn btn-outline-danger btn-sm delete-order" type="button" data-order-id="<?= cbOrderText($order['order_id']) ?>">Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderModalLabel">Cancel order</h5>
                <button type="button" class="close close-modal" data-dismiss="modal" aria-label="Close">x</button>
            </div>
            <div class="modal-body">
                <div class="alert d-none" id="cancelOrderAlert"></div>
                <p>Cancel order <strong id="cancelOrderLabel"></strong> and preserve a refund/audit record.</p>
                <p class="text-danger paid-refund-note d-none">This order is paid. If it was paid by PayFast, process the matching refund in the PayFast dashboard after this cancellation is recorded.</p>
                <label for="cancelOrderReason">Reason shown in records</label>
                <textarea class="form-control" id="cancelOrderReason" placeholder="Example: Customer requested cancellation, item unavailable, duplicate order"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="confirmCancelOrder">Cancel order</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete order?</h5>
                <button type="button" class="close close-modal" data-dismiss="modal" aria-label="Close">x</button>
            </div>
            <div class="modal-body">
                <p>This permanently removes order <strong id="deleteOrderLabel"></strong> and its order items.</p>
                <p class="mb-0 text-danger">Only delete test orders or mistakes you are sure should disappear.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDelete">Delete order</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Update order</h5>
                <button type="button" class="close close-modal" data-dismiss="modal" aria-label="Close">x</button>
            </div>
            <div class="modal-body">
                <div class="alert d-none" id="statusAlert"></div>
                <form id="statusForm">
                    <input type="hidden" id="current_order_id" value="">
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label for="updatedStatus">New status</label>
                            <select class="form-control" id="updatedStatus">
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= cbOrderText($status) ?>"><?= cbOrderText($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-7">
                            <label for="emailSubject">Email subject</label>
                            <input type="text" class="form-control" id="emailSubject">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="emailBody">Friendly message to customer</label>
                        <textarea class="form-control" id="emailBody"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="partialFulfillment">Delivery / collection progress</label>
                        <select class="form-control" id="partialFulfillment">
                            <option value="">No partial delivery/collection note</option>
                            <option value="partially_delivered">Parcel delivered partially; items still outstanding for delivery</option>
                            <option value="partially_collected">Parcel collected partially; items still outstanding for collection</option>
                        </select>
                    </div>
                    <div class="form-group d-none" id="outstandingItemsWrap">
                        <label for="outstandingItems">Outstanding items or notes</label>
                        <textarea class="form-control" id="outstandingItems" placeholder="Example: 2 x 1kg almonds still outstanding for delivery. Customer will be notified when ready."></textarea>
                        <small class="form-text text-muted">This will be saved to the order notes. If you email the client, it will also be included in the message.</small>
                        <small class="form-text text-muted">Use this for tracking details, collection notes, payment reminders, or a warm order update. Choose “Update without email” if this is internal only.</small>
                    </div>
                    <div class="d-flex flex-wrap justify-content-end" style="gap: 10px;">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="button" class="btn btn-outline-dark" id="updateWithoutEmail">Update without email</button>
                        <button type="button" class="btn btn-success" id="confirmSend">Update and email client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
var candybirdBankingDetails = <?= json_encode($adminBankingDetailsPlain, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
var currentOrderCustomer = 'customer';
var currentOrderTotal = '0.00';

function zeroPad(num, places) {
    var stringValue = String(num);
    while (stringValue.length < places) stringValue = '0' + stringValue;
    return stringValue;
}

function showStatusMessage(success, message) {
    var alert = $('#statusAlert');
    alert.removeClass('d-none alert-success alert-danger alert-info').addClass(success ? 'alert-success' : 'alert-danger').text(message);
    var pageAlert = $('#ordersPageAlert');
    pageAlert.removeClass('d-none alert-success alert-danger alert-info').addClass(success ? 'alert-success' : 'alert-danger').text(message);
}

function showWorkingMessage(message) {
    $('#statusAlert').removeClass('d-none alert-success alert-danger').addClass('alert-info').text(message);
    $('#ordersPageAlert').removeClass('d-none alert-success alert-danger').addClass('alert-info').text(message);
}

function ajaxMessage(xhr, fallback) {
    if (xhr && xhr.responseJSON && xhr.responseJSON.message) return xhr.responseJSON.message;
    if (xhr && xhr.responseText) {
        try {
            var parsed = JSON.parse(xhr.responseText);
            if (parsed.message) return parsed.message;
        } catch (e) {}
    }
    return fallback;
}

function setButtonWorking($button, isWorking, text) {
    if (!$button.length) return;
    if (isWorking) {
        $button.data('original-text', $button.text()).prop('disabled', true).text(text || 'Working...');
    } else {
        $button.prop('disabled', false).text($button.data('original-text') || $button.text());
    }
}

function showCancelOrderMessage(success, message) {
    var alert = $('#cancelOrderAlert');
    alert.removeClass('d-none alert-success alert-danger').addClass(success ? 'alert-success' : 'alert-danger').text(message);
}

function filterOrders() {
    var query = ($('#orderSearch').val() || '').toLowerCase();
    var status = $('#statusFilter').val();
    $('.order-row').each(function() {
        var row = $(this);
        var matchesSearch = !query || row.data('search').indexOf(query) !== -1;
        var matchesStatus = !status || row.data('status') === status;
        row.toggle(matchesSearch && matchesStatus);
    });
}

$(function() {
    $('#orderSearch, #statusFilter').on('input change', filterOrders);

    var orderSortState = { key: 'date', dir: 'desc' };
    $('#ordersTable').on('click', 'th.sortable', function() {
        var key = $(this).data('sort');
        orderSortState.dir = orderSortState.key === key && orderSortState.dir === 'asc' ? 'desc' : 'asc';
        orderSortState.key = key;
        var rows = $('#ordersTable tbody tr').get();
        rows.sort(function(a, b) {
            var av = $(a).data(key);
            var bv = $(b).data(key);
            if (key === 'order' || key === 'total' || key === 'date') {
                av = parseFloat(av) || 0;
                bv = parseFloat(bv) || 0;
                return orderSortState.dir === 'asc' ? av - bv : bv - av;
            }
            av = String(av || '').toLowerCase();
            bv = String(bv || '').toLowerCase();
            return orderSortState.dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        $('#ordersTable tbody').append(rows);
    });

    $('body').on('click', '.delete-order', function() {
        var orderId = $(this).data('order-id');
        $('#deleteOrderLabel').text('#' + orderId);
        $('#confirmDelete').attr('href', 'delete_order?id=' + encodeURIComponent(orderId));
        $('#deleteModal').modal('show');
    });

    $('body').on('click', '.cancel-order-btn', function() {
        var orderId = $(this).data('order-id');
        $('#cancelOrderAlert').addClass('d-none').text('');
        $('#cancelOrderLabel').text('#' + orderId);
        $('#cancelOrderReason').val('');
        $('#confirmCancelOrder').data('order-id', orderId);
        $('.paid-refund-note').toggleClass('d-none', String($(this).data('paid')) !== '1');
        $('#cancelOrderModal').modal('show');
    });

    $('#confirmCancelOrder').on('click', function() {
        $.ajax({
            url: 'cancel_order_action.php',
            method: 'POST',
            dataType: 'json',
            data: {
                mode: 'full',
                orderId: $(this).data('order-id'),
                reason: $('#cancelOrderReason').val()
            },
            success: function(response) {
                showCancelOrderMessage(!!response.success, response.message || 'Cancellation recorded.');
                if (response.success) {
                    setTimeout(function() { window.location.reload(); }, 1400);
                }
            },
            error: function() {
                showCancelOrderMessage(false, 'Cancellation could not be recorded right now.');
            }
        });
    });

    $('body').on('click', '.order-status-btn', function() {
        var orderId = $(this).data('order-id');
        var customer = $(this).data('customer') || 'customer';
        var total = parseFloat($(this).data('total') || 0) || 0;
        var currentStatus = $(this).data('current-status') || 'Pending';
        var paddedOrderId = zeroPad(orderId, 7);

        currentOrderCustomer = customer;
        currentOrderTotal = total.toFixed(2);
        $('#statusAlert').addClass('d-none').text('');
        $('#current_order_id').val(orderId);
        $('#updatedStatus').val(currentStatus);
        $('#partialFulfillment').val('');
        $('#outstandingItems').val('');
        $('#outstandingItemsWrap').addClass('d-none');
        $('#emailSubject').val('Update on your Order ' + paddedOrderId + ' | CandyBird');
        $('#emailBody').val('Hi ' + customer + ',\n\nYour order status has been updated to: ' + currentStatus + '.\n\nThank you for shopping with CandyBird.');
        $('#statusModal').modal('show');
    });

    function eftPaymentBody(customer, total) {
        return 'Hi ' + (customer || 'there') + ',\n\n' +
            'Thank you for placing an order with us.\n\n' +
            'Kindly use the banking details below for EFT payments, or if you prefer a secure payment link let me know.\n\n' +
            (candybirdBankingDetails || 'Banking details are available from CandyBird.') + '\n\n' +
            'Total: R' + (parseFloat(total || 0) || 0).toFixed(2) + '\n\n' +
            'Once payment is received we will dispatch your order.\n\n' +
            'Warm Regards\nCandyBird Team.';
    }

    function partialFulfillmentText() {
        var partial = $('#partialFulfillment').val();
        var outstanding = ($('#outstandingItems').val() || '').trim();
        if (!partial) return '';
        var action = partial === 'partially_delivered' ? 'delivered' : 'collected';
        var pending = partial === 'partially_delivered' ? 'delivery' : 'collection';
        var text = 'This order has been partially ' + action + '. Some items are still outstanding for ' + pending + '.';
        if (outstanding) {
            text += '\nOutstanding: ' + outstanding;
        }
        return text;
    }

    $('#partialFulfillment').on('change', function() {
        var partial = $(this).val();
        $('#outstandingItemsWrap').toggleClass('d-none', !partial);
        if (partial === 'partially_delivered') {
            $('#updatedStatus').val('Partially delivered').trigger('change');
        } else if (partial === 'partially_collected') {
            $('#updatedStatus').val('Partially collected').trigger('change');
        }
    });

    $('#outstandingItems').on('input', function() {
        var status = $('#updatedStatus').val();
        var partialText = partialFulfillmentText();
        if (partialText) {
            $('#emailBody').val('Your order status has been updated to: ' + status + '.\n\n' + partialText + '\n\nThank you for shopping with CandyBird.');
        }
    });

    $('#updatedStatus').on('change', function() {
        var status = $(this).val();
        var orderId = $('#current_order_id').val();
        if (status === 'Awaiting EFT payment') {
            $('#emailSubject').val('EFT payment details for Order ' + zeroPad(orderId, 7) + ' | CandyBird');
            $('#emailBody').val(eftPaymentBody(currentOrderCustomer, currentOrderTotal));
            return;
        }
        var partialText = partialFulfillmentText();
        $('#emailSubject').val('Update on your Order ' + zeroPad(orderId, 7) + ' | CandyBird');
        $('#emailBody').val('Your order status has been updated to: ' + status + '.' + (partialText ? '\n\n' + partialText : '') + '\n\nThank you for shopping with CandyBird.');
    });

    $('.close-modal').on('click', function() {
        $('.modal').modal('hide');
    });

    $('#confirmSend').on('click', function() {
        var $button = $(this);
        showWorkingMessage('Updating the order and sending the client email...');
        setButtonWorking($button, true, 'Sending...');
        $.ajax({
            url: 'update_order_status_and_email.php',
            method: 'POST',
            dataType: 'json',
            data: {
                orderId: $('#current_order_id').val(),
                updatedStatus: $('#updatedStatus').val(),
                emailSubject: $('#emailSubject').val(),
                emailBody: $('#emailBody').val(),
                partialFulfillment: $('#partialFulfillment').val(),
                outstandingItems: $('#outstandingItems').val()
            },
            success: function(response) {
                showStatusMessage(response.status === 'success', response.message || 'Order updated.');
                if (response.status === 'success') {
                    setTimeout(function() { window.location.reload(); }, 1800);
                }
            },
            error: function(xhr) {
                showStatusMessage(false, ajaxMessage(xhr, 'The order could not be updated or emailed right now.'));
            },
            complete: function() {
                setButtonWorking($button, false);
            }
        });
    });

    $('#updateWithoutEmail').on('click', function() {
        var $button = $(this);
        showWorkingMessage('Updating the order without emailing the client...');
        setButtonWorking($button, true, 'Updating...');
        $.ajax({
            url: 'update_order_status.php',
            method: 'POST',
            dataType: 'json',
            data: {
                orderId: $('#current_order_id').val(),
                updatedStatus: $('#updatedStatus').val(),
                partialFulfillment: $('#partialFulfillment').val(),
                outstandingItems: $('#outstandingItems').val()
            },
            success: function(response) {
                showStatusMessage(response.status === 'success', response.message || 'Order updated.');
                if (response.status === 'success') {
                    setTimeout(function() { window.location.reload(); }, 700);
                }
            },
            error: function(xhr) {
                showStatusMessage(false, ajaxMessage(xhr, 'The order status could not be updated right now.'));
            },
            complete: function() {
                setButtonWorking($button, false);
            }
        });
    });

    $('body').on('click', '.mark-paid-btn', function() {
        var $button = $(this);
        var orderId = $(this).data('order-id');
        if (!confirm('Mark order #' + orderId + ' as paid?')) return;
        showWorkingMessage('Marking order #' + orderId + ' as paid...');
        setButtonWorking($button, true, 'Saving...');
        $.ajax({
            url: 'mark_order_paid.php',
            method: 'POST',
            dataType: 'json',
            data: { order_id: orderId, payment_status: 2 },
            success: function(response) {
                showStatusMessage(!!response.success, response.message || 'Payment status updated.');
                if (response.success) {
                    setTimeout(function() { window.location.reload(); }, 700);
                }
            },
            error: function(xhr) {
                showStatusMessage(false, ajaxMessage(xhr, 'Payment status could not be updated right now.'));
            },
            complete: function() {
                setButtonWorking($button, false);
            }
        });
    });
});
</script>

<?php include '../footer.php'; ?>
