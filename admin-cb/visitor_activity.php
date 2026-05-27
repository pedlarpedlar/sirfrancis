<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "visitor_activity";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';
include 'header.php';
include 'page_menues.php';

function cbVaTableExists($conn, $table) {
    if (!($conn instanceof mysqli)) return false;
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '$safeTable'");
    return $result && $result->num_rows > 0;
}

function cbVaRows($conn, $sql) {
    if (!($conn instanceof mysqli)) return [];
    $result = $conn->query($sql);
    if (!$result) return [];
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function cbVaText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbVaTimeAgo($timestamp) {
    if (!$timestamp) return 'Unknown';
    try {
        $then = new DateTime($timestamp);
        $now = new DateTime();
    } catch (Exception $e) {
        return cbVaText($timestamp);
    }
    $diff = $now->getTimestamp() - $then->getTimestamp();
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return floor($diff / 86400) . ' days ago';
}

function cbVaShortUrl($url) {
    $url = (string) $url;
    $url = str_replace(['https://www.candybird.co.za', 'http://www.candybird.co.za'], '', $url);
    return $url !== '' ? $url : '/';
}

function cbVaLinkifyProducts($text) {
    $safe = cbVaText($text);
    $safe = preg_replace('/(product(?:\s*id)?[:#\s]+)(\d+)/i', '$1<a href="../product?id=$2" target="_blank">#$2</a>', $safe);
    $safe = preg_replace('/(product\?id=)(\d+)/i', '<a href="../product?id=$2" target="_blank">product #$2</a>', $safe);
    return $safe;
}

function cbVaIsPrivateIp($ip) {
    $ip = trim((string) $ip);
    if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') return true;
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    $long = ip2long($ip);
    if ($long === false) return false;
    $ranges = [
        ['10.0.0.0', '10.255.255.255'],
        ['172.16.0.0', '172.31.255.255'],
        ['192.168.0.0', '192.168.255.255'],
    ];
    foreach ($ranges as $range) {
        if ($long >= ip2long($range[0]) && $long <= ip2long($range[1])) return true;
    }
    return false;
}

function cbVaSqlInStrings($conn, $values) {
    $clean = [];
    foreach ($values as $value) {
        $value = (string) $value;
        if ($value !== '') {
            $clean[] = "'" . $conn->real_escape_string($value) . "'";
        }
    }
    return !empty($clean) ? implode(',', array_unique($clean)) : "''";
}

function cbVaSqlInInts($values) {
    $clean = [];
    foreach ($values as $value) {
        $value = (int) $value;
        if ($value > 0) $clean[] = $value;
    }
    return !empty($clean) ? implode(',', array_unique($clean)) : "0";
}

function cbVaVisitorGroupKey($session) {
    $uid = (int) ($session['user_id'] ?? 0);
    if ($uid > 0) return 'user:' . $uid;
    return 'device:' . strtolower(trim((string) ($session['ip_address'] ?? ''))) . '|' . md5((string) ($session['user_agent'] ?? ''));
}

$hasSessions = cbVaTableExists($conn, 'sessions');
$hasUsers = cbVaTableExists($conn, 'users');
$hasAddresses = cbVaTableExists($conn, 'user_addresses');
$hasPageViews = cbVaTableExists($conn, 'page_views');
$hasActions = cbVaTableExists($conn, 'action_logs');
$hasCart = cbVaTableExists($conn, 'cart');
$hasOrders = cbVaTableExists($conn, 'orders');
$hasGeo = cbVaTableExists($conn, 'ip_geolocation');
$hasGeoHistory = cbVaTableExists($conn, 'ip_geolocation_history');

$selectedVisitorHash = isset($_GET['visitor']) ? preg_replace('/[^a-f0-9]/i', '', (string) $_GET['visitor']) : '';
$selectedSessionId = isset($_GET['session_id']) && is_numeric($_GET['session_id']) ? (int) $_GET['session_id'] : 0;

$sessionRows = [];
if ($hasSessions) {
    $geoHistorySelect = "COALESCE(g.city, '') AS city,
            '' AS province,
            COALESCE(g.country, '') AS country,
            '' AS suburb,
            '' AS provider,
            '' AS organization,";
    $geoHistoryJoin = "";
    $humanSessionFilter = "s.user_agent NOT REGEXP 'bot|crawl|spider|preview|facebookexternalhit|whatsapp|telegrambot|curl|wget|monitor|uptime'";

    $sessionRows = cbVaRows($conn, "
        SELECT
            s.id,
            s.user_id,
            s.session_id,
            s.ip_address,
            s.user_agent,
            s.start_time,
            s.end_time,
            COALESCE(s.end_time, s.start_time) AS last_seen,
            CASE WHEN COALESCE(s.end_time, s.start_time) >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 ELSE 0 END AS is_online,
            COALESCE(u.username, '') AS display_name,
            COALESCE(u.email, '') AS email,
            '' AS phone,
            $geoHistorySelect
            0 AS page_views,
            0 AS actions,
            0 AS cart_items,
            0 AS order_count,
            NULL AS latest_order_id
        FROM sessions s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN ip_geolocation g ON s.ip_address = g.ip_address
        $geoHistoryJoin
        WHERE $humanSessionFilter
          AND COALESCE(s.end_time, s.start_time) >= DATE_SUB(NOW(), INTERVAL 2 DAY)
        ORDER BY is_online DESC, last_seen DESC
        LIMIT 260
    ");
}

$visitorGroups = [];
foreach ($sessionRows as $session) {
    $groupKey = cbVaVisitorGroupKey($session);
    $hash = md5($groupKey);
    if (!isset($visitorGroups[$hash])) {
        $visitorGroups[$hash] = $session;
        $visitorGroups[$hash]['visitor_hash'] = $hash;
        $visitorGroups[$hash]['session_ids'] = [];
        $visitorGroups[$hash]['guest_identifiers'] = [];
        $visitorGroups[$hash]['ip_addresses'] = [];
        $visitorGroups[$hash]['page_views'] = 0;
        $visitorGroups[$hash]['actions'] = 0;
        $visitorGroups[$hash]['is_private_ip'] = false;
        $visitorGroups[$hash]['noise_flags'] = [];
    }

    $visitorGroups[$hash]['session_ids'][] = (int) $session['id'];
    $visitorGroups[$hash]['guest_identifiers'][] = (string) $session['session_id'];
    if (!empty($session['ip_address'])) {
        $visitorGroups[$hash]['ip_addresses'][] = (string) $session['ip_address'];
    }
    $visitorGroups[$hash]['page_views'] += (int) ($session['page_views'] ?? 0);
    $visitorGroups[$hash]['actions'] += (int) ($session['actions'] ?? 0);
    $visitorGroups[$hash]['is_online'] = ((int) $visitorGroups[$hash]['is_online'] === 1 || (int) $session['is_online'] === 1) ? 1 : 0;
    $visitorGroups[$hash]['is_private_ip'] = $visitorGroups[$hash]['is_private_ip'] || cbVaIsPrivateIp($session['ip_address'] ?? '');

    if (strtotime((string) $session['last_seen']) > strtotime((string) $visitorGroups[$hash]['last_seen'])) {
        $keep = [
            'visitor_hash' => $visitorGroups[$hash]['visitor_hash'],
            'session_ids' => $visitorGroups[$hash]['session_ids'],
            'guest_identifiers' => $visitorGroups[$hash]['guest_identifiers'],
            'ip_addresses' => $visitorGroups[$hash]['ip_addresses'],
            'page_views' => $visitorGroups[$hash]['page_views'],
            'actions' => $visitorGroups[$hash]['actions'],
            'is_online' => $visitorGroups[$hash]['is_online'],
            'is_private_ip' => $visitorGroups[$hash]['is_private_ip'],
            'noise_flags' => $visitorGroups[$hash]['noise_flags'],
        ];
        $visitorGroups[$hash] = array_merge($session, $keep);
    }
}

$visitors = array_values($visitorGroups);
foreach ($visitors as &$visitor) {
    $visitor['session_ids'] = array_values(array_unique($visitor['session_ids']));
    $visitor['guest_identifiers'] = array_values(array_unique($visitor['guest_identifiers']));
    $visitor['ip_addresses'] = array_values(array_unique($visitor['ip_addresses']));
    $visitor['session_count'] = count($visitor['session_ids']);
    if ($visitor['session_count'] > 1) $visitor['noise_flags'][] = $visitor['session_count'] . ' sessions grouped';
    if (!empty($visitor['is_private_ip'])) $visitor['noise_flags'][] = 'Local/test traffic';
    if ((int) ($visitor['user_id'] ?? 0) > 0) $visitor['noise_flags'][] = 'Logged-in user';
    if (count($visitor['ip_addresses']) > 1) $visitor['noise_flags'][] = count($visitor['ip_addresses']) . ' IPs';
    $visitor['noise_flags'] = array_values(array_unique($visitor['noise_flags']));
    $visitor['ip_address'] = implode(', ', array_slice($visitor['ip_addresses'], 0, 3));
    if (count($visitor['ip_addresses']) > 3) $visitor['ip_address'] .= ' +' . (count($visitor['ip_addresses']) - 3);
}
unset($visitor);

usort($visitors, function ($a, $b) {
    if ((int) $a['is_online'] !== (int) $b['is_online']) {
        return (int) $b['is_online'] <=> (int) $a['is_online'];
    }
    return strcmp((string) $b['last_seen'], (string) $a['last_seen']);
});
$visitors = array_slice($visitors, 0, 40);

if ($selectedSessionId && !$selectedVisitorHash) {
    foreach ($visitors as $visitor) {
        if (in_array($selectedSessionId, $visitor['session_ids'], true)) {
            $selectedVisitorHash = $visitor['visitor_hash'];
            break;
        }
    }
}

if (!$selectedVisitorHash && !empty($visitors)) {
    $selectedVisitorHash = $visitors[0]['visitor_hash'];
}

$selectedVisitor = null;
foreach ($visitors as $visitor) {
    if ($visitor['visitor_hash'] === $selectedVisitorHash) {
        $selectedVisitor = $visitor;
        break;
    }
}

$timeline = [];
$cartLines = [];
$orders = [];

if ($selectedVisitor) {
    $sessionIdsSql = cbVaSqlInInts($selectedVisitor['session_ids'] ?? []);
    $guestSql = cbVaSqlInStrings($conn, $selectedVisitor['guest_identifiers'] ?? []);
    $uid = is_numeric($selectedVisitor['user_id']) ? (int) $selectedVisitor['user_id'] : 0;

    if ($hasAddresses) {
        $addressWhere = $uid > 0 ? "user_id = $uid" : "guest_identifier IN ($guestSql)";
        $addressRows = cbVaRows($conn, "SELECT billing_first_name, billing_last_name, billing_email_address, billing_phone_number, billing_city, billing_province FROM user_addresses WHERE $addressWhere ORDER BY id DESC LIMIT 1");
        if (!empty($addressRows)) {
            $address = $addressRows[0];
            $addressName = trim(($address['billing_first_name'] ?? '') . ' ' . ($address['billing_last_name'] ?? ''));
            if ($selectedVisitor['display_name'] === '' && $addressName !== '') {
                $selectedVisitor['display_name'] = $addressName;
            }
            if ($selectedVisitor['email'] === '' && !empty($address['billing_email_address'])) {
                $selectedVisitor['email'] = $address['billing_email_address'];
            }
            if ($selectedVisitor['phone'] === '' && !empty($address['billing_phone_number'])) {
                $selectedVisitor['phone'] = $address['billing_phone_number'];
            }
            if ($selectedVisitor['city'] === '' && !empty($address['billing_city'])) {
                $selectedVisitor['city'] = $address['billing_city'];
            }
            if ($selectedVisitor['province'] === '' && !empty($address['billing_province'])) {
                $selectedVisitor['province'] = $address['billing_province'];
            }
        }
    }

    if ($hasPageViews) {
        foreach (cbVaRows($conn, "SELECT 'Page view' AS type, url AS label, referrer_url AS details, timestamp AS happened_at FROM page_views WHERE session_id IN ($sessionIdsSql) AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY timestamp DESC LIMIT 80") as $row) {
            $row['label'] = cbVaShortUrl($row['label']);
            $timeline[] = $row;
        }
    }
    if ($hasActions) {
        $userClause = $uid > 0 ? " OR user_id = $uid" : "";
        foreach (cbVaRows($conn, "SELECT action AS type, action AS label, details, created_at AS happened_at FROM action_logs WHERE (guest_identifier IN ($guestSql) $userClause) AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 100") as $row) {
            $timeline[] = $row;
        }
    }
    usort($timeline, function ($a, $b) {
        return strcmp((string) $b['happened_at'], (string) $a['happened_at']);
    });
    $timeline = array_slice($timeline, 0, 120);

    if ($hasCart) {
        $userClause = $uid > 0 ? " OR c.user_id = $uid" : "";
        $cartLines = cbVaRows($conn, "SELECT c.product_id, SUM(c.quantity) AS quantity FROM cart c WHERE (c.guest_identifier IN ($guestSql) $userClause) GROUP BY c.product_id ORDER BY MAX(c.id) DESC LIMIT 40");
    }
    if ($hasOrders) {
        $userClause = $uid > 0 ? " OR user_id = $uid" : "";
        $orders = cbVaRows($conn, "SELECT id, order_status, payment_status, grand_total_amount, order_date FROM orders WHERE (guest_identifier IN ($guestSql) $userClause) ORDER BY order_date DESC LIMIT 8");
    }
}
?>

<title>Visitor Activity - CandyBird Admin</title>

<style>
    .visitor-wrap { padding: 28px 0 50px; }
    .visitor-hero { background: #2d1739; color: #fff; padding: 22px; border-radius: 8px; margin-bottom: 18px; }
    .visitor-hero h1 { font-size: 26px; margin: 0 0 6px; }
    .visitor-grid { display: grid; grid-template-columns: minmax(300px, 0.95fr) minmax(0, 1.6fr); gap: 18px; align-items: start; }
    .visitor-panel { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 14px; }
    .visitor-list { display: grid; gap: 10px; max-height: 78vh; overflow: auto; padding-right: 4px; }
    .visitor-card { display: block; border: 1px solid #eadfd2; border-radius: 8px; padding: 12px; color: #2c2926; text-decoration: none; background: #fffdf9; }
    .visitor-card.active { border-color: #5b1178; box-shadow: 0 0 0 2px rgba(91,17,120,.12); }
    .visitor-card:hover { text-decoration: none; color: #2c2926; }
    .visitor-name { font-weight: 700; color: #2d1739; }
    .status-pill { display: inline-flex; align-items: center; gap: 5px; border-radius: 99px; padding: 2px 8px; font-size: 12px; font-weight: 700; background: #eee; color: #555; }
    .status-pill.online { background: #e7f8ed; color: #15783a; }
    .meta-line { color: #6d6270; font-size: 12px; margin-top: 4px; }
    .activity-line { border-left: 3px solid #eadfd2; padding: 8px 0 8px 12px; margin-left: 4px; }
    .activity-line strong { color: #2d1739; }
    .activity-line small { color: #746979; display: block; margin-top: 3px; overflow-wrap: anywhere; }
    .mini-table td, .mini-table th { padding: 6px 8px; font-size: 13px; }
    @media (max-width: 991px) { .visitor-grid { grid-template-columns: 1fr; } .visitor-list { max-height: none; } }
</style>

<div class="container visitor-wrap">
    <div class="visitor-hero">
        <h1>Visitor Activity</h1>
        <p class="mb-0">See grouped visitor/device activity, with noisy repeat sessions folded into one clearer story.</p>
    </div>

    <div class="visitor-grid">
        <div class="visitor-panel">
            <h2 class="h5 mb-3">Live / Recent Visitors</h2>
            <div class="visitor-list">
                <?php if (empty($visitors)): ?>
                    <p class="text-muted mb-0">No real visitor activity recorded yet.</p>
                <?php else: foreach ($visitors as $visitor):
                    $isOnline = (int) $visitor['is_online'] === 1;
                    $name = trim((string) ($visitor['display_name'] ?? ''));
                    $email = trim((string) ($visitor['email'] ?? ''));
                    $label = $name ?: ($email ?: 'Guest visitor');
                    $status = $isOnline ? 'online' : 'offline';
                ?>
                    <a class="visitor-card <?= $visitor['visitor_hash'] === $selectedVisitorHash ? 'active' : '' ?>" href="visitor_activity?visitor=<?= cbVaText($visitor['visitor_hash']) ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="visitor-name"><?= cbVaText($label) ?></div>
                                <div class="meta-line"><?= $visitor['user_id'] ? 'User #' . (int)$visitor['user_id'] : 'Guest' ?><?= $email ? ' | ' . cbVaText($email) : '' ?></div>
                            </div>
                            <span class="status-pill <?= $isOnline ? 'online' : '' ?>"><?= $isOnline ? 'Online' : 'Offline' ?></span>
                        </div>
                        <div class="meta-line">Last seen <?= cbVaText(cbVaTimeAgo($visitor['last_seen'])) ?> | <?= cbVaText(trim(($visitor['city'] ?? '') . ' ' . ($visitor['province'] ?? '')) ?: ($visitor['country'] ?: 'Unknown area')) ?></div>
                        <div class="meta-line"><?= (int)$visitor['session_count'] ?> session<?= (int)$visitor['session_count'] === 1 ? '' : 's' ?> grouped | IP <?= cbVaText($visitor['ip_address'] ?: 'unknown') ?></div>
                        <?php if (!empty($visitor['noise_flags'])): ?>
                            <div class="meta-line"><?= cbVaText(implode(' | ', $visitor['noise_flags'])) ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="visitor-panel">
            <?php if (!$selectedVisitor): ?>
                <p class="text-muted mb-0">Select a visitor to see their activity.</p>
            <?php else:
                $name = trim((string) ($selectedVisitor['display_name'] ?? ''));
                $email = trim((string) ($selectedVisitor['email'] ?? ''));
                $label = $name ?: ($email ?: 'Guest visitor');
            ?>
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="h4 mb-1"><?= cbVaText($label) ?></h2>
                        <div class="text-muted"><?= $selectedVisitor['user_id'] ? 'Logged-in user #' . (int)$selectedVisitor['user_id'] : 'Guest visitor/device' ?> | Last seen <?= cbVaText(cbVaTimeAgo($selectedVisitor['last_seen'])) ?></div>
                        <div class="text-muted"><?= cbVaText($email ?: 'No email captured yet') ?><?= $selectedVisitor['phone'] ? ' | ' . cbVaText($selectedVisitor['phone']) : '' ?></div>
                        <div class="text-muted">
                            <?= cbVaText($selectedVisitor['ip_address'] ?: 'No IP') ?>
                            <?= $selectedVisitor['city'] ? ' | ' . cbVaText($selectedVisitor['city']) : '' ?>
                            <?= $selectedVisitor['suburb'] ? ' | ' . cbVaText($selectedVisitor['suburb']) : '' ?>
                            <?= $selectedVisitor['provider'] ? ' | ' . cbVaText($selectedVisitor['provider']) : '' ?>
                            <?= $selectedVisitor['organization'] ? ' | ' . cbVaText($selectedVisitor['organization']) : '' ?>
                        </div>
                        <?php if (!empty($selectedVisitor['noise_flags'])): ?>
                            <div class="text-muted"><?= cbVaText(implode(' | ', $selectedVisitor['noise_flags'])) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="status-pill <?= (int)$selectedVisitor['is_online'] === 1 ? 'online' : '' ?>"><?= (int)$selectedVisitor['is_online'] === 1 ? 'Online now' : 'Offline' ?></span>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <h3 class="h6">Current Cart</h3>
                        <div class="table-responsive">
                            <table class="table table-sm mini-table">
                                <thead><tr><th>Product</th><th>Qty</th></tr></thead>
                                <tbody>
                                <?php if (empty($cartLines)): ?>
                                    <tr><td colspan="2">No active cart items.</td></tr>
                                <?php else: foreach ($cartLines as $line): ?>
                                    <tr>
                                        <td><a href="../product?id=<?= (int)$line['product_id'] ?>" target="_blank">Product #<?= (int)$line['product_id'] ?></a></td>
                                        <td><?= (int)$line['quantity'] ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <h3 class="h6">Recent Orders</h3>
                        <div class="table-responsive">
                            <table class="table table-sm mini-table">
                                <thead><tr><th>Order</th><th>Status</th><th>Total</th></tr></thead>
                                <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="3">No orders found for this visitor.</td></tr>
                                <?php else: foreach ($orders as $order): ?>
                                    <tr>
                                        <td><a href="order_details?id=<?= (int)$order['id'] ?>">#<?= (int)$order['id'] ?></a></td>
                                        <td><?= cbVaText($order['order_status']) ?> / <?= ((int)$order['payment_status'] === 0 ? 'Unpaid' : 'Paid') ?></td>
                                        <td>R<?= number_format((float)$order['grand_total_amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <h3 class="h5 mt-2">Timeline</h3>
                <?php if (empty($timeline)): ?>
                    <p class="text-muted">No page views or actions recorded for this visitor yet.</p>
                <?php else: foreach ($timeline as $event): ?>
                    <div class="activity-line">
                        <strong><?= cbVaText($event['type']) ?></strong>
                        <span class="text-muted">
                            | <?= cbVaText($label) ?>
                            | <?= cbVaText($selectedVisitor['city'] ?: 'Unknown city') ?><?= $selectedVisitor['suburb'] ? ', ' . cbVaText($selectedVisitor['suburb']) : '' ?>
                            | IP <?= cbVaText($selectedVisitor['ip_address']) ?>
                            <?= $selectedVisitor['provider'] ? ' | ' . cbVaText($selectedVisitor['provider']) : '' ?>
                            | <?= cbVaText(cbVaTimeAgo($event['happened_at'])) ?>
                            | <?= cbVaText($event['happened_at']) ?>
                        </span>
                        <small><?= cbVaLinkifyProducts($event['label']) ?></small>
                        <?php if (!empty($event['details'])): ?>
                            <small><?= cbVaLinkifyProducts($event['details']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
