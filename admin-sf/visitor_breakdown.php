<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("visitor_breakdown"));
    exit();
}

include 'dbh.inc.php';
include 'header.php';
include 'page_menues.php';

date_default_timezone_set('Africa/Johannesburg');

function cbVbText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbVbTableExists($conn, $table) {
    if (!($conn instanceof mysqli)) return false;
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '$safeTable'");
    return $result && $result->num_rows > 0;
}

function cbVbRows($conn, $sql) {
    if (!($conn instanceof mysqli)) return [];
    $result = $conn->query($sql);
    if (!$result) return [];
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function cbVbDeviceType($userAgent) {
    $ua = strtolower((string) $userAgent);
    if (preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp|telegrambot|curl|wget|monitor|uptime|headless|python|httpclient/', $ua)) return 'Bot / preview';
    if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) return 'Tablet';
    if (strpos($ua, 'mobi') !== false || strpos($ua, 'iphone') !== false || strpos($ua, 'android') !== false) return 'Mobile';
    return 'Desktop';
}

function cbVbBrowser($userAgent) {
    $ua = (string) $userAgent;
    if (stripos($ua, 'Edg/') !== false || stripos($ua, 'Edge/') !== false) return 'Edge';
    if (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) return 'Opera';
    if (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) return 'Chrome';
    if (stripos($ua, 'Firefox/') !== false) return 'Firefox';
    if (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false) return 'Safari';
    if (stripos($ua, 'Trident/') !== false || stripos($ua, 'MSIE') !== false) return 'Internet Explorer';
    if (trim($ua) === '') return 'Unknown';
    return 'Other';
}

function cbVbIsPrivateIp($ip) {
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

function cbVbLooksLikeBot($userAgent) {
    return (bool) preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp|telegrambot|curl|wget|monitor|uptime|headless|python|httpclient/i', (string) $userAgent);
}

$range = $_GET['range'] ?? 'today';
$allowedRanges = ['today', '24h', '7d'];
if (!in_array($range, $allowedRanges, true)) {
    $range = 'today';
}

$dateWhere = "s.start_time >= CURDATE()";
$actionDateWhere = "al.created_at >= CURDATE()";
$rangeLabel = 'Today';
if ($range === '24h') {
    $dateWhere = "s.start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $actionDateWhere = "al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $rangeLabel = 'Last 24 hours';
} elseif ($range === '7d') {
    $dateWhere = "s.start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $actionDateWhere = "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $rangeLabel = 'Last 7 days';
}

$hasSessions = cbVbTableExists($conn, 'sessions');
$hasPageViews = cbVbTableExists($conn, 'page_views');
$hasActions = cbVbTableExists($conn, 'action_logs');
$hasUsers = cbVbTableExists($conn, 'users');
$hasGeo = cbVbTableExists($conn, 'ip_geolocation');
$hasGeoHistory = cbVbTableExists($conn, 'ip_geolocation_history');

$pageJoin = $hasPageViews ? "LEFT JOIN page_views pv ON pv.session_id = s.id" : "";
$pageSelect = $hasPageViews ? "COUNT(DISTINCT CONCAT(COALESCE(pv.url, ''), '|', COALESCE(pv.timestamp, ''))) AS page_views" : "0 AS page_views";
$actionJoin = $hasActions ? "LEFT JOIN action_logs al ON al.ip_address = s.ip_address AND al.user_agent = s.user_agent AND $actionDateWhere" : "";
$actionSelect = $hasActions ? "COUNT(DISTINCT al.id) AS actions" : "0 AS actions";
$userJoin = $hasUsers ? "LEFT JOIN users u ON s.user_id = u.id" : "";
$userSelect = $hasUsers ? "COALESCE(u.username, '') AS username, COALESCE(u.email, '') AS email" : "'' AS username, '' AS email";

$geoJoin = "";
$geoSelect = "'' AS suburb, '' AS city, '' AS province, '' AS country, '' AS provider";
if ($hasGeoHistory) {
    $geoJoin = "LEFT JOIN (
        SELECT ip_address,
               MAX(city) AS city,
               MAX(country) AS country,
               MAX(state_prov) AS province,
               MAX(district) AS suburb,
               MAX(COALESCE(NULLIF(organization, ''), NULLIF(isp, ''))) AS provider
        FROM ip_geolocation_history
        GROUP BY ip_address
    ) geo ON geo.ip_address = s.ip_address";
    $geoSelect = "COALESCE(geo.suburb, '') AS suburb, COALESCE(geo.city, '') AS city, COALESCE(geo.province, '') AS province, COALESCE(geo.country, '') AS country, COALESCE(geo.provider, '') AS provider";
} elseif ($hasGeo) {
    $geoJoin = "LEFT JOIN ip_geolocation geo ON geo.ip_address = s.ip_address";
    $geoSelect = "'' AS suburb, COALESCE(geo.city, '') AS city, '' AS province, COALESCE(geo.country, '') AS country, '' AS provider";
}

$visitorRows = [];
if ($hasSessions) {
    $visitorRows = cbVbRows($conn, "
        SELECT
            COALESCE(NULLIF(s.ip_address, ''), 'Unknown') AS ip_address,
            COALESCE(NULLIF(s.user_agent, ''), 'Unknown') AS user_agent,
            COALESCE(s.user_id, 0) AS user_id,
            $userSelect,
            $geoSelect,
            COUNT(DISTINCT s.id) AS raw_sessions,
            COUNT(DISTINCT s.session_id) AS browser_sessions,
            $pageSelect,
            $actionSelect,
            MIN(s.start_time) AS first_seen,
            MAX(COALESCE(s.end_time, s.start_time)) AS last_seen
        FROM sessions s
        $userJoin
        $geoJoin
        $pageJoin
        $actionJoin
        WHERE $dateWhere
        GROUP BY s.ip_address, s.user_agent, s.user_id, username, email, suburb, city, province, country, provider
        ORDER BY last_seen DESC
    ");
}

$locationRowCount = 0;
foreach ($visitorRows as $visitorRow) {
    if (trim((string) ($visitorRow['suburb'] ?? '')) !== '' || trim((string) ($visitorRow['city'] ?? '')) !== '') {
        $locationRowCount++;
    }
}

?>

<title>Visitor Breakdown - Sir Francis Admin</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    .visitor-breakdown-wrap { padding: 28px 0 50px; }
    .visitor-breakdown-hero { background: var(--sf-navy); color: #fff; border-radius: 8px; padding: 22px; margin-bottom: 18px; }
    .visitor-breakdown-hero h1 { color: var(--sf-gold); font-size: 26px; margin: 0 0 6px; }
    .visitor-breakdown-hero p { color: #f7e9ff; margin: 0; }
    .visitor-toolbar { align-items: center; background: #fff; border: 1px solid var(--sf-border); border-radius: 8px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; margin-bottom: 16px; padding: 14px; }
    .visitor-toolbar .btn { border-radius: 6px; }
    .visitor-location-filter { align-items: center; display: inline-flex; gap: 8px; margin: 0 0 0 8px; vertical-align: middle; }
    .visitor-location-filter input { margin: 0; }
    .visitor-count-pill { background: #f7f1e8; border: 1px solid var(--sf-border); border-radius: 999px; color: #4b3528; display: inline-flex; font-size: 12px; font-weight: 800; padding: 6px 10px; }
    .visitor-table-card { background: #fff; border: 1px solid var(--sf-border); border-radius: 8px; padding: 14px; }
    .visitor-status { border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 800; padding: 5px 8px; white-space: nowrap; }
    .visitor-status.human { background: #edf8ed; color: #24713a; }
    .visitor-status.warning { background: #fff3cd; color: #7a5700; }
    .visitor-status.bot { background: #fde2e2; color: #a91f1f; }
    .ua-text { color: #6d6270; display: block; font-size: 11px; max-width: 420px; white-space: normal; }
    table.dataTable td { vertical-align: top; }
</style>

<div class="container visitor-breakdown-wrap">
    <div class="visitor-breakdown-hero">
        <h1>Visitor Breakdown</h1>
        <p><?= cbVbText($rangeLabel) ?> grouped by IP address, user account and device fingerprint. This makes duplicate sessions, repeated page refreshes and likely bots easier to audit.</p>
    </div>

    <div class="visitor-toolbar">
        <div>
            <a class="btn btn<?= $range === 'today' ? '' : '-outline' ?>-primary btn-sm" href="visitor_breakdown?range=today">Today</a>
            <a class="btn btn<?= $range === '24h' ? '' : '-outline' ?>-primary btn-sm" href="visitor_breakdown?range=24h">24 hours</a>
            <a class="btn btn<?= $range === '7d' ? '' : '-outline' ?>-primary btn-sm" href="visitor_breakdown?range=7d">7 days</a>
            <label class="visitor-location-filter">
                <input type="checkbox" id="visitor-location-only" checked>
                <span>Only with suburb/city</span>
            </label>
        </div>
        <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
            <span class="visitor-count-pill" id="visitor-visible-count"><?= number_format(count($visitorRows)) ?> shown</span>
            <span class="text-muted small"><?= number_format($locationRowCount) ?> with suburb/city · <?= number_format(count($visitorRows)) ?> total grouped row<?= count($visitorRows) === 1 ? '' : 's' ?></span>
        </div>
    </div>

    <div class="visitor-table-card">
        <div class="table-responsive">
            <table id="visitor-breakdown-table" class="table table-striped table-bordered table-sm nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>IP address</th>
                        <th>Suburb</th>
                        <th>City</th>
                        <th>Province</th>
                        <th>Country</th>
                        <th>Provider</th>
                        <th>User</th>
                        <th>Device</th>
                        <th>Browser</th>
                        <th>Raw sessions</th>
                        <th>Browser sessions</th>
                        <th>Page views</th>
                        <th>Actions</th>
                        <th>First seen</th>
                        <th>Last seen</th>
                        <th>User agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visitorRows as $row):
                        $ua = (string) ($row['user_agent'] ?? '');
                        $isBot = cbVbLooksLikeBot($ua);
                        $isPrivate = cbVbIsPrivateIp($row['ip_address'] ?? '');
                        $statusClass = $isBot ? 'bot' : ($isPrivate ? 'warning' : 'human');
                        $statusText = $isBot ? 'Likely bot' : ($isPrivate ? 'Local/test' : 'Likely human');
                        $userLabel = trim((string) ($row['username'] ?? ''));
                        $email = trim((string) ($row['email'] ?? ''));
                        if ($userLabel === '') $userLabel = ((int) ($row['user_id'] ?? 0) > 0 ? 'User #' . (int) $row['user_id'] : 'Guest');
                        if ($email !== '') $userLabel .= '<br><small>' . cbVbText($email) . '</small>';
                    ?>
                        <tr data-has-location="<?= (trim((string) ($row['suburb'] ?? '')) !== '' || trim((string) ($row['city'] ?? '')) !== '') ? '1' : '0' ?>">
                            <td><span class="visitor-status <?= cbVbText($statusClass) ?>"><?= cbVbText($statusText) ?></span></td>
                            <td><?= cbVbText($row['ip_address'] ?? '') ?></td>
                            <td><?= cbVbText($row['suburb'] ?? '') ?></td>
                            <td><?= cbVbText($row['city'] ?? '') ?></td>
                            <td><?= cbVbText($row['province'] ?? '') ?></td>
                            <td><?= cbVbText($row['country'] ?? '') ?></td>
                            <td><?= cbVbText($row['provider'] ?? '') ?></td>
                            <td><?= $userLabel ?></td>
                            <td><?= cbVbText(cbVbDeviceType($ua)) ?></td>
                            <td><?= cbVbText(cbVbBrowser($ua)) ?></td>
                            <td><?= number_format((int) ($row['raw_sessions'] ?? 0)) ?></td>
                            <td><?= number_format((int) ($row['browser_sessions'] ?? 0)) ?></td>
                            <td><?= number_format((int) ($row['page_views'] ?? 0)) ?></td>
                            <td><?= number_format((int) ($row['actions'] ?? 0)) ?></td>
                            <td><?= cbVbText($row['first_seen'] ?? '') ?></td>
                            <td><?= cbVbText($row['last_seen'] ?? '') ?></td>
                            <td><span class="ua-text"><?= cbVbText($ua) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script>
$(function () {
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'visitor-breakdown-table') {
            return true;
        }
        if (!$('#visitor-location-only').is(':checked')) {
            return true;
        }
        var rowNode = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        return rowNode && $(rowNode).attr('data-has-location') === '1';
    });

    var table = $('#visitor-breakdown-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[15, 'desc']],
        columnDefs: [
            { targets: '_all', orderable: true }
        ],
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']]
    });

    function updateVisibleCount() {
        $('#visitor-visible-count').text(table.rows({ filter: 'applied' }).count().toLocaleString() + ' shown');
    }

    $('#visitor-location-only').on('change', function () {
        table.draw();
        updateVisibleCount();
    });

    table.on('draw', updateVisibleCount);
    updateVisibleCount();
});
</script>

<?php include '../footer.php'; ?>
