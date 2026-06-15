<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("visitor_activity"));
    exit();
}

include 'dbh.inc.php';
include 'header.php';
include 'page_menues.php';

date_default_timezone_set('Africa/Johannesburg');

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
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function cbVaText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbVaTimeAgo($timestamp) {
    if (!$timestamp) return 'unknown';
    try {
        $then = new DateTime($timestamp);
        $now = new DateTime();
    } catch (Exception $e) {
        return (string) $timestamp;
    }
    $diff = max(0, $now->getTimestamp() - $then->getTimestamp());
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return floor($diff / 86400) . ' days ago';
}

function cbVaIsPrivateIp($ip) {
    $ip = trim((string) $ip);
    if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') return true;
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    $long = ip2long($ip);
    if ($long === false) return false;
    foreach ([['10.0.0.0', '10.255.255.255'], ['172.16.0.0', '172.31.255.255'], ['192.168.0.0', '192.168.255.255']] as $range) {
        if ($long >= ip2long($range[0]) && $long <= ip2long($range[1])) return true;
    }
    return false;
}

function cbVaLooksLikeBot($userAgent) {
    return (bool) preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp|telegrambot|curl|wget|monitor|uptime|headless|python|httpclient/i', (string) $userAgent);
}

function cbVaSqlInStrings($conn, $values) {
    $clean = [];
    foreach ($values as $value) {
        $value = trim((string) $value);
        if ($value !== '') $clean[] = "'" . $conn->real_escape_string($value) . "'";
    }
    return $clean ? implode(',', array_unique($clean)) : "''";
}

function cbVaSqlInInts($values) {
    $clean = [];
    foreach ($values as $value) {
        $value = (int) $value;
        if ($value > 0) $clean[] = $value;
    }
    return $clean ? implode(',', array_unique($clean)) : "0";
}

function cbVaVisitorKey($row) {
    $uid = (int) ($row['user_id'] ?? 0);
    if ($uid > 0) return 'user:' . $uid;
    return 'guest:' . strtolower((string) ($row['ip_address'] ?? '')) . '|' . md5((string) ($row['user_agent'] ?? ''));
}

function cbVaExtractAfter($text, $prefix) {
    $pos = stripos((string) $text, $prefix);
    if ($pos === false) return '';
    $value = substr((string) $text, $pos + strlen($prefix));
    $value = preg_split('/\s+\|\s+/', $value)[0] ?? $value;
    return trim($value);
}

function cbVaProductLabel($details) {
    $details = (string) $details;
    if (preg_match('/Title:\s*([^|]+)/i', $details, $m)) return trim($m[1]);
    if (preg_match('/product\s+id:\s*([A-Za-z0-9:.-]+)/i', $details, $m)) return 'product #' . trim($m[1]);
    if (preg_match('/Product:\s*([^|]+)/i', $details, $m)) return 'product #' . trim($m[1]);
    if (preg_match('/item\s+([A-Za-z0-9:.-]+)/i', $details, $m)) return 'product #' . trim($m[1]);
    return 'a product';
}

function cbVaEventSentence($event) {
    $type = (string) ($event['type'] ?? '');
    $details = (string) ($event['details'] ?? '');
    $label = (string) ($event['label'] ?? '');

    if ($type === 'UX search submitted') {
        $query = cbVaExtractAfter($details, 'Query:');
        return $query !== '' ? 'searched for "' . $query . '"' : 'searched the site';
    }
    if ($type === 'UX search result click') {
        $query = cbVaExtractAfter($details, 'Query:');
        return $query !== '' ? 'clicked a search result for "' . $query . '"' : 'clicked a search result';
    }
    if ($type === 'UX product viewed') {
        return 'viewed ' . cbVaProductLabel($details);
    }
    if (stripos($type, 'Added item') === 0 || stripos($type, 'Clicked on add-to-cart') === 0) {
        return 'added ' . cbVaProductLabel($type . ' ' . $details) . ' to cart';
    }
    if (stripos($type, 'Removed item') === 0 || stripos($type, 'removeFromCart') !== false) {
        return 'removed an item from cart';
    }
    if (stripos($type, 'update-cart-quantity') !== false || stripos($type, 'updated item') !== false) {
        return 'changed cart quantities';
    }
    if ($type === 'Checkout Success') {
        if (preg_match('/order\s+(\d+)/i', $details, $m)) return 'checked out successfully with order #' . $m[1];
        return 'checked out successfully';
    }
    if ($type === 'Checkout Error') return 'tried to checkout but hit an error';
    if (stripos($label, 'checkout') !== false || stripos($label, '/checkout') !== false) return 'opened checkout';
    if (stripos($label, 'cart') !== false || stripos($label, '/cart') !== false) return 'opened cart';
    if (stripos($type, 'UX category click') === 0) {
        $to = cbVaExtractAfter($details, 'To:');
        return $to !== '' ? 'browsed category ' . $to : 'browsed a category';
    }
    if ($type === 'Page view') {
        $path = parse_url((string) $label, PHP_URL_PATH) ?: (string) $label;
        if ($path === '/' || $path === '') return 'visited the homepage';
        if (stripos($path, 'product') !== false) return 'opened a product page';
        if (stripos($path, 'products') !== false) return 'browsed products';
    }

    return '';
}

function cbVaBuildStory($events, $hasCart, $hasOrder) {
    $sentences = [];
    foreach ($events as $event) {
        $sentence = cbVaEventSentence($event);
        if ($sentence === '') continue;
        $last = end($sentences);
        if ($last !== $sentence) $sentences[] = $sentence;
        if (count($sentences) >= 9) break;
    }

    if (!$sentences) return 'No meaningful activity captured yet.';

    $story = implode(', then ', $sentences);
    $checkedOut = false;
    foreach ($events as $event) {
        if (($event['type'] ?? '') === 'Checkout Success') {
            $checkedOut = true;
            break;
        }
    }
    if (!$checkedOut && $hasCart) $story .= ', and currently has an active cart';
    if (!$checkedOut && !$hasOrder && $hasCart) $story .= ' that looks abandoned for now';
    return ucfirst($story) . '.';
}

function cbVaRangeDate($key, $fallback) {
    $raw = trim((string) ($_GET[$key] ?? ''));
    if ($raw === '') return $fallback;
    try {
        return new DateTime($raw);
    } catch (Exception $e) {
        return $fallback;
    }
}

$defaultTo = new DateTime();
$defaultFrom = (clone $defaultTo)->modify('-24 hours');
$fromDate = cbVaRangeDate('from', $defaultFrom);
$toDate = cbVaRangeDate('to', $defaultTo);
if ($fromDate > $toDate) {
    $tmp = $fromDate;
    $fromDate = $toDate;
    $toDate = $tmp;
}
$fromSql = $conn instanceof mysqli ? $conn->real_escape_string($fromDate->format('Y-m-d H:i:s')) : '';
$toSql = $conn instanceof mysqli ? $conn->real_escape_string($toDate->format('Y-m-d H:i:s')) : '';
$fromInput = $fromDate->format('Y-m-d\TH:i');
$toInput = $toDate->format('Y-m-d\TH:i');

$hasSessions = cbVaTableExists($conn, 'sessions');
$hasUsers = cbVaTableExists($conn, 'users');
$hasAddresses = cbVaTableExists($conn, 'user_addresses');
$hasPageViews = cbVaTableExists($conn, 'page_views');
$hasActions = cbVaTableExists($conn, 'action_logs');
$hasCart = cbVaTableExists($conn, 'cart');
$hasOrders = cbVaTableExists($conn, 'orders');
$hasGeo = cbVaTableExists($conn, 'ip_geolocation');
$hasGeoHistory = cbVaTableExists($conn, 'ip_geolocation_history');

$sessionRows = [];
if ($hasSessions) {
    $geoSourceSql = '';
    if ($hasGeoHistory) {
        $geoSourceSql = "(SELECT ip_address,
            MAX(city) AS city,
            MAX(country) AS country,
            MAX(state_prov) AS province,
            MAX(district) AS suburb
            FROM ip_geolocation_history
            GROUP BY ip_address)";
    } elseif ($hasGeo) {
        $geoSourceSql = "(SELECT ip_address, city, country, '' AS province, '' AS suburb FROM ip_geolocation)";
    }
    $geoSelect = $geoSourceSql !== '' ? "COALESCE(g.suburb, '') AS suburb, COALESCE(g.city, '') AS city, COALESCE(g.country, '') AS country" : "'' AS suburb, '' AS city, '' AS country";
    $geoJoin = $geoSourceSql !== '' ? "LEFT JOIN $geoSourceSql g ON s.ip_address = g.ip_address" : "";
    $geoWhere = $geoSourceSql !== '' ? "AND (COALESCE(g.suburb, '') <> '' OR COALESCE(g.city, '') <> '')" : "AND 0 = 1";
    $sessionRows = cbVaRows($conn, "
        SELECT s.id, s.user_id, s.session_id, s.ip_address, s.user_agent, s.start_time, s.end_time,
               COALESCE(s.end_time, s.start_time) AS last_seen,
               CASE WHEN COALESCE(s.end_time, s.start_time) >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 ELSE 0 END AS is_online,
               COALESCE(u.username, '') AS display_name,
               COALESCE(u.email, '') AS email,
               $geoSelect
        FROM sessions s
        LEFT JOIN users u ON s.user_id = u.id
        $geoJoin
        WHERE COALESCE(s.end_time, s.start_time) BETWEEN '$fromSql' AND '$toSql'
          AND s.user_agent NOT REGEXP 'bot|crawl|spider|preview|facebookexternalhit|whatsapp|telegrambot|curl|wget|monitor|uptime|headless|python|httpclient'
          $geoWhere
        ORDER BY is_online DESC, last_seen DESC
        LIMIT 120
    ");
}

$groups = [];
foreach ($sessionRows as $row) {
    $key = cbVaVisitorKey($row);
    $hash = md5($key);
    if (!isset($groups[$hash])) {
        $groups[$hash] = $row;
        $groups[$hash]['visitor_hash'] = $hash;
        $groups[$hash]['session_ids'] = [];
        $groups[$hash]['guest_identifiers'] = [];
        $groups[$hash]['ip_addresses'] = [];
        $groups[$hash]['is_private_ip'] = false;
        $groups[$hash]['bot_suspected'] = false;
        $groups[$hash]['events'] = [];
        $groups[$hash]['cart_count'] = 0;
        $groups[$hash]['orders'] = [];
        $groups[$hash]['story'] = '';
    }
    $groups[$hash]['session_ids'][] = (int) $row['id'];
    $groups[$hash]['guest_identifiers'][] = (string) $row['session_id'];
    $groups[$hash]['ip_addresses'][] = (string) $row['ip_address'];
    $groups[$hash]['is_online'] = ((int) $groups[$hash]['is_online'] === 1 || (int) $row['is_online'] === 1) ? 1 : 0;
    $groups[$hash]['is_private_ip'] = $groups[$hash]['is_private_ip'] || cbVaIsPrivateIp($row['ip_address'] ?? '');
    $groups[$hash]['bot_suspected'] = $groups[$hash]['bot_suspected'] || cbVaLooksLikeBot($row['user_agent'] ?? '');
    if (strtotime((string) $row['last_seen']) > strtotime((string) $groups[$hash]['last_seen'])) {
        foreach (['display_name', 'email', 'suburb', 'city', 'country', 'last_seen', 'ip_address', 'user_agent'] as $field) {
            $groups[$hash][$field] = $row[$field] ?? $groups[$hash][$field];
        }
    }
}

$hourStats = [];
$weekdayStats = [];
$abandonHourStats = [];

$groups = array_values($groups);
usort($groups, function ($a, $b) {
    if ((int) $a['is_online'] !== (int) $b['is_online']) return (int) $b['is_online'] <=> (int) $a['is_online'];
    return strcmp((string) $b['last_seen'], (string) $a['last_seen']);
});
$groups = array_slice($groups, 0, 10);

foreach ($groups as &$visitor) {
    $visitor['session_ids'] = array_values(array_unique($visitor['session_ids']));
    $visitor['guest_identifiers'] = array_values(array_unique($visitor['guest_identifiers']));
    $visitor['ip_addresses'] = array_values(array_unique(array_filter($visitor['ip_addresses'])));
    $sessionIdsSql = cbVaSqlInInts($visitor['session_ids']);
    $guestSql = cbVaSqlInStrings($conn, $visitor['guest_identifiers']);
    $uid = (int) ($visitor['user_id'] ?? 0);

    if ($hasAddresses) {
        $addressWhere = $uid > 0 ? "user_id = $uid" : "guest_identifier IN ($guestSql)";
        $addressRows = cbVaRows($conn, "SELECT billing_first_name, billing_last_name, billing_email_address, billing_city, billing_province FROM user_addresses WHERE $addressWhere ORDER BY id DESC LIMIT 1");
        if ($addressRows) {
            $address = $addressRows[0];
            $name = trim(($address['billing_first_name'] ?? '') . ' ' . ($address['billing_last_name'] ?? ''));
            if (($visitor['display_name'] ?? '') === '' && $name !== '') $visitor['display_name'] = $name;
            if (($visitor['email'] ?? '') === '' && !empty($address['billing_email_address'])) $visitor['email'] = $address['billing_email_address'];
            if (($visitor['city'] ?? '') === '' && !empty($address['billing_city'])) $visitor['city'] = $address['billing_city'];
            if (($visitor['country'] ?? '') === '' && !empty($address['billing_province'])) $visitor['country'] = $address['billing_province'];
        }
    }

    $events = [];
    if ($hasPageViews) {
        foreach (cbVaRows($conn, "SELECT 'Page view' AS type, url AS label, referrer_url AS details, timestamp AS happened_at FROM page_views WHERE session_id IN ($sessionIdsSql) AND timestamp BETWEEN '$fromSql' AND '$toSql' ORDER BY timestamp ASC LIMIT 120") as $event) {
            $events[] = $event;
        }
    }
    if ($hasActions) {
        $userClause = $uid > 0 ? " OR user_id = $uid" : "";
        $noise = "action NOT LIKE 'UX scroll depth%' AND action NOT LIKE 'Clicked on grid-view%' AND action NOT LIKE 'Clicked on list-view%' AND action NOT LIKE 'Clicked on search toggler%' AND action NOT LIKE 'Clicked on close off-canvas cart%' AND action NOT LIKE 'Clicked on slider%'";
        foreach (cbVaRows($conn, "SELECT action AS type, action AS label, details, created_at AS happened_at FROM action_logs WHERE (guest_identifier IN ($guestSql) $userClause) AND created_at BETWEEN '$fromSql' AND '$toSql' AND $noise ORDER BY created_at ASC LIMIT 160") as $event) {
            $events[] = $event;
        }
    }
    usort($events, function ($a, $b) {
        return strcmp((string) $a['happened_at'], (string) $b['happened_at']);
    });

    $visitor['events'] = $events;

    if ($hasCart) {
        $userClause = $uid > 0 ? " OR user_id = $uid" : "";
        $rows = cbVaRows($conn, "SELECT COALESCE(SUM(quantity), 0) AS qty FROM cart WHERE (guest_identifier IN ($guestSql) $userClause)");
        $visitor['cart_count'] = (int) ($rows[0]['qty'] ?? 0);
    }
    if ($hasOrders) {
        $userClause = $uid > 0 ? " OR user_id = $uid" : "";
        $visitor['orders'] = cbVaRows($conn, "SELECT id, order_status, payment_status, grand_total_amount, order_date FROM orders WHERE (guest_identifier IN ($guestSql) $userClause) AND order_date BETWEEN '$fromSql' AND '$toSql' ORDER BY order_date DESC LIMIT 5");
    }
    $visitor['story'] = cbVaBuildStory($events, $visitor['cart_count'] > 0, !empty($visitor['orders']));

    foreach ($events as $event) {
        $hour = date('H:00', strtotime((string) $event['happened_at']));
        $day = date('D', strtotime((string) $event['happened_at']));
        $hourStats[$hour] = ($hourStats[$hour] ?? 0) + 1;
        $weekdayStats[$day] = ($weekdayStats[$day] ?? 0) + 1;
    }
    $checkedOut = false;
    $addedCart = false;
    foreach ($events as $event) {
        if (($event['type'] ?? '') === 'Checkout Success') $checkedOut = true;
        if (stripos((string) ($event['type'] ?? ''), 'Added item') === 0 || stripos((string) ($event['type'] ?? ''), 'add-to-cart') !== false) $addedCart = true;
    }
    if ($addedCart && !$checkedOut) {
        $lastHour = date('H:00', strtotime((string) ($visitor['last_seen'] ?? 'now')));
        $abandonHourStats[$lastHour] = ($abandonHourStats[$lastHour] ?? 0) + 1;
    }
}
unset($visitor);

$visitors = array_values($groups);

arsort($hourStats);
arsort($weekdayStats);
arsort($abandonHourStats);
$topHour = $hourStats ? key($hourStats) : 'No data';
$topDay = $weekdayStats ? key($weekdayStats) : 'No data';
$topAbandonHour = $abandonHourStats ? key($abandonHourStats) : 'No data';
?>

<title>Visitor Activity - Sir Francis Admin</title>

<style>
    .visitor-wrap { padding: 28px 0 50px; }
    .visitor-hero { background: #2d1739; color: #fff; padding: 22px; border-radius: 8px; margin-bottom: 16px; }
    .visitor-hero h1 { color: #CEBD88; font-size: 26px; margin: 0 0 6px; }
    .visitor-hero p { color: #f7e9ff; margin: 0; }
    .visitor-filters, .visitor-card, .insight-card { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; }
    .visitor-filters { align-items: end; display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 16px; padding: 14px; }
    .visitor-filters label { color: #4b3d46; font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .visitor-filters .form-control { border-radius: 6px; min-height: 42px; }
    .insight-grid { display: grid; gap: 12px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 16px; }
    .insight-card { padding: 14px; }
    .insight-card span { color: #746979; display: block; font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .insight-card strong { color: #2d1739; display: block; font-size: 22px; margin-top: 4px; }
    .visitor-list { display: grid; gap: 12px; }
    .visitor-card { padding: 16px; }
    .visitor-top { align-items: flex-start; display: flex; gap: 12px; justify-content: space-between; }
    .visitor-name { color: #2d1739; font-size: 17px; font-weight: 900; }
    .visitor-meta { color: #6d6270; font-size: 13px; margin-top: 3px; }
    .visitor-story { color: #2f2924; font-size: 15px; line-height: 1.6; margin: 12px 0; }
    .status-pill { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 800; padding: 4px 9px; white-space: nowrap; }
    .status-pill.online { background: #e7f8ed; color: #15783a; }
    .status-pill.human { background: #eef4ff; color: #244a8f; }
    .status-pill.warning { background: #fff3dc; color: #9b5a00; }
    .visitor-events { border-top: 1px solid #f0ebe4; display: grid; gap: 7px; margin-top: 12px; padding-top: 12px; }
    .visitor-event { color: #5e5361; font-size: 13px; }
    .visitor-event strong { color: #2d1739; }
    .visitor-orders { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .visitor-orders a { background: #f7f0fb; border-radius: 999px; color: #28364B; font-weight: 800; padding: 5px 10px; }
    @media (max-width: 991px) {
        .visitor-filters, .insight-grid { grid-template-columns: 1fr; }
        .visitor-top { display: block; }
        .status-pill { margin-top: 8px; }
    }
</style>

<div class="container visitor-wrap">
    <div class="visitor-hero">
        <h1>Visitor Activity</h1>
        <p>Last 10 active guests/users with usable suburb or city data, written as simple shopping journeys instead of raw tracking noise.</p>
    </div>

    <form class="visitor-filters" method="get" action="visitor_activity">
        <div>
            <label for="from">From</label>
            <input class="form-control" type="datetime-local" id="from" name="from" value="<?= cbVaText($fromInput) ?>">
        </div>
        <div>
            <label for="to">To</label>
            <input class="form-control" type="datetime-local" id="to" name="to" value="<?= cbVaText($toInput) ?>">
        </div>
        <div>
            <label>Quick View</label>
            <div class="d-flex flex-wrap" style="gap:8px;">
                <a class="btn btn-light btn-sm" href="visitor_activity">24 hours</a>
                <a class="btn btn-light btn-sm" href="visitor_activity?from=<?= urlencode((new DateTime('-7 days'))->format('Y-m-d\TH:i')) ?>&to=<?= urlencode((new DateTime())->format('Y-m-d\TH:i')) ?>">7 days</a>
                <a class="btn btn-light btn-sm" href="visitor_activity?from=<?= urlencode((new DateTime('-4 weeks'))->format('Y-m-d\TH:i')) ?>&to=<?= urlencode((new DateTime())->format('Y-m-d\TH:i')) ?>">4 weeks</a>
            </div>
        </div>
        <button class="btn btn-warning" type="submit">Show activity</button>
    </form>

    <div class="insight-grid">
        <div class="insight-card"><span>Most active hour</span><strong><?= cbVaText($topHour) ?></strong></div>
        <div class="insight-card"><span>Most active day</span><strong><?= cbVaText($topDay) ?></strong></div>
        <div class="insight-card"><span>Most abandoned around</span><strong><?= cbVaText($topAbandonHour) ?></strong></div>
    </div>

    <div class="visitor-list">
        <?php if (empty($visitors)): ?>
            <div class="visitor-card">No geo-qualified visitor activity found for this period.</div>
        <?php else: foreach ($visitors as $visitor):
            $name = trim((string) ($visitor['display_name'] ?? ''));
            $email = trim((string) ($visitor['email'] ?? ''));
            $ip = implode(', ', array_slice($visitor['ip_addresses'] ?? [], 0, 2));
            $label = $name ?: ($email ?: ($ip ?: 'Guest visitor'));
            $areaParts = array_filter([trim((string) ($visitor['suburb'] ?? '')), trim((string) ($visitor['city'] ?? '')), trim((string) ($visitor['country'] ?? ''))]);
            $area = implode(', ', array_unique($areaParts));
            $realStatus = !empty($visitor['bot_suspected']) ? 'Suspicious' : (!empty($visitor['is_private_ip']) ? 'Local/test' : 'Likely human');
            $realClass = !empty($visitor['bot_suspected']) || !empty($visitor['is_private_ip']) ? 'warning' : 'human';
        ?>
            <article class="visitor-card">
                <div class="visitor-top">
                    <div>
                        <div class="visitor-name"><?= cbVaText($label) ?></div>
                        <div class="visitor-meta">
                            <?= (int) ($visitor['user_id'] ?? 0) > 0 ? 'User #' . (int) $visitor['user_id'] : 'Guest' ?>
                            | IP <?= cbVaText($ip ?: 'unknown') ?>
                            | <?= cbVaText($area ?: 'Unknown location') ?>
                            | Last seen <?= cbVaText(cbVaTimeAgo($visitor['last_seen'] ?? '')) ?>
                        </div>
                    </div>
                    <div>
                        <?php if ((int) ($visitor['is_online'] ?? 0) === 1): ?><span class="status-pill online">Online now</span><?php endif; ?>
                        <span class="status-pill <?= cbVaText($realClass) ?>"><?= cbVaText($realStatus) ?></span>
                    </div>
                </div>

                <p class="visitor-story"><?= cbVaText($visitor['story']) ?></p>

                <?php if (!empty($visitor['orders'])): ?>
                    <div class="visitor-orders">
                        <?php foreach ($visitor['orders'] as $order): ?>
                            <a href="order_details?order_id=<?= (int) $order['id'] ?>">Order #<?= (int) $order['id'] ?> | R<?= number_format((float) $order['grand_total_amount'], 2) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="visitor-events">
                    <?php
                    $shown = 0;
                    foreach ($visitor['events'] as $event):
                        $sentence = cbVaEventSentence($event);
                        if ($sentence === '') continue;
                        $shown++;
                        if ($shown > 6) break;
                    ?>
                        <div class="visitor-event"><strong><?= cbVaText(date('d M H:i', strtotime((string) $event['happened_at']))) ?></strong> - <?= cbVaText($sentence) ?></div>
                    <?php endforeach; ?>
                    <?php if ($shown === 0): ?><div class="visitor-event">No meaningful timeline items yet.</div><?php endif; ?>
                </div>
            </article>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
