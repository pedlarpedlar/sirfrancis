<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "dashboard";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';
include 'header.php';
include 'page_menues.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

$dashboardMessage = '';
$dashboardSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['dashboard_action'] ?? '') === 'refresh_coupons') {
    $coupons = getSheetCoupons(true);
    $dashboardSuccess = !empty($coupons);
    $dashboardMessage = $dashboardSuccess
        ? 'Coupon cache refreshed from Google Sheets. Found ' . number_format(count($coupons)) . ' coupon code group' . (count($coupons) === 1 ? '' : 's') . '.'
        : 'Coupon cache refresh could not load any coupons. Please check the coupon sheet link and headers.';
}

function cbAdminMoney($value) {
    return 'R' . number_format((float) $value, 2);
}

function cbAdminTableExists($conn, $table) {
    if (!($conn instanceof mysqli)) {
        return false;
    }
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '$safeTable'");
    return $result && $result->num_rows > 0;
}

function cbAdminScalar($conn, $sql, $fallback = 0) {
    if (!($conn instanceof mysqli)) {
        return $fallback;
    }
    $result = $conn->query($sql);
    if (!$result) {
        return $fallback;
    }
    $row = $result->fetch_row();
    return $row ? $row[0] : $fallback;
}

function cbAdminRows($conn, $sql) {
    if (!($conn instanceof mysqli)) {
        return [];
    }
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function cbAdminPercentChange($current, $previous) {
    $current = (float) $current;
    $previous = (float) $previous;
    if ($previous <= 0 && $current > 0) {
        return 'New activity';
    }
    if ($previous <= 0) {
        return 'No previous data';
    }
    $change = (($current - $previous) / $previous) * 100;
    return ($change >= 0 ? '+' : '') . number_format($change, 1) . '% vs previous period';
}

function cbAdminMetricLabel($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return 'No data yet';
    }
    return trim($value) !== '' ? trim($value) : 'No data yet';
}

function cbAdminTrackingParts($value) {
    $parts = [];
    foreach (explode('|', (string) $value) as $piece) {
        $piece = trim($piece);
        if (strpos($piece, ':') === false) {
            continue;
        }
        [$key, $detail] = array_map('trim', explode(':', $piece, 2));
        $parts[strtolower($key)] = $detail;
    }
    return $parts;
}

function cbAdminCleanTrackingPath($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $path = parse_url($value, PHP_URL_PATH);
    if (is_string($path) && $path !== '') {
        $value = $path;
    }
    $value = trim($value, '/');
    if ($value === '') {
        return 'Homepage';
    }
    $value = preg_replace('/[-_]+/', ' ', $value);
    return ucwords($value);
}

function cbAdminWeeklyReportValue($kind, $value) {
    $value = trim((string) $value);
    if ($value === '') {
        return 'No data yet';
    }

    if ($kind === 'search') {
        return '"' . preg_replace('/^Query:\s*/i', '', $value) . '"';
    }

    $parts = cbAdminTrackingParts($value);
    if ($kind === 'product_click' || $kind === 'category_click') {
        $text = trim((string) ($parts['text'] ?? ''));
        $to = cbAdminCleanTrackingPath($parts['to'] ?? '');
        if ($text !== '') {
            $text = preg_replace('/\s*\(\d+\s*$/', '', $text);
            return $to !== '' && strcasecmp($text, $to) !== 0 ? $text . ' -> ' . $to : $text;
        }
        return $to !== '' ? $to : cbAdminMetricLabel($value);
    }

    if ($kind === 'add_to_cart') {
        if (preg_match('/product id:\s*([^)]+)/i', $value, $match)) {
            $productId = trim($match[1]);
            if (function_exists('getSheetProductById') && ctype_digit($productId)) {
                $product = getSheetProductById($productId);
                if ($product && function_exists('getSheetProductDisplayTitle')) {
                    return getSheetProductDisplayTitle($product);
                }
            }
            return 'Product #' . $productId;
        }
        if (!empty($parts['from page'])) {
            return cbAdminCleanTrackingPath($parts['from page']) . ' add-to-cart';
        }
        if (preg_match('/From page\s+(.+)$/i', $value, $match)) {
            return cbAdminCleanTrackingPath($match[1]) . ' add-to-cart';
        }
    }

    return cbAdminMetricLabel($value);
}

function cbAdminWeeklyReportCaption($kind, $count) {
    $count = (int) $count;
    $noun = 'qualified action';
    if ($kind === 'search') {
        $noun = 'qualified search';
    } elseif ($kind === 'product_click') {
        $noun = 'qualified product click';
    } elseif ($kind === 'category_click') {
        $noun = 'qualified category click';
    } elseif ($kind === 'add_to_cart') {
        $noun = 'qualified add-to-cart click';
    }
    return number_format($count) . ' ' . $noun . ($count === 1 ? '' : 's') . ' in 7 days';
}

function cbAdminReconcilePayfastPayments($conn) {
    if (!($conn instanceof mysqli) || !cbAdminTableExists($conn, 'orders')) {
        return;
    }

    $payfastColumn = $conn->query("SHOW COLUMNS FROM orders LIKE 'payfast_payment_id'");
    if ($payfastColumn && $payfastColumn->num_rows > 0) {
        $conn->query("UPDATE orders SET payment_status = 1 WHERE COALESCE(payment_status, 0) = 0 AND COALESCE(payfast_payment_id, '') <> ''");
    }

    if (cbAdminTableExists($conn, 'payment_checks')) {
        $conn->query("UPDATE orders o INNER JOIN payment_checks pc ON pc.order_id = o.id SET o.payment_status = 1 WHERE COALESCE(o.payment_status, 0) = 0 AND pc.check_result = 1");
        $conn->query("
            UPDATE orders o
            INNER JOIN (
                SELECT order_id,
                       COUNT(*) AS failed_checks,
                       SUM(CASE WHEN check_name <> 'pfValidIP' THEN 1 ELSE 0 END) AS other_failed_checks
                FROM payment_checks
                WHERE check_result = 0
                GROUP BY order_id
            ) pc ON pc.order_id = o.id
            SET o.payment_status = 1
            WHERE COALESCE(o.payment_status, 0) = 0
              AND pc.failed_checks > 0
              AND COALESCE(pc.other_failed_checks, 0) = 0
        ");
    }
}

$hasOrders = cbAdminTableExists($conn, 'orders');
$hasOrderItems = cbAdminTableExists($conn, 'order_items');
$hasPaymentChecks = cbAdminTableExists($conn, 'payment_checks');
$hasUsers = cbAdminTableExists($conn, 'users');
$hasSubscribers = cbAdminTableExists($conn, 'subscribers');
$hasReviews = cbAdminTableExists($conn, 'reviews');
$hasScheduledEmails = cbAdminTableExists($conn, 'scheduled_emails');
$hasCronjobs = cbAdminTableExists($conn, 'cronjobs');
$hasActionLogs = cbAdminTableExists($conn, 'action_logs');
$hasPageViews = cbAdminTableExists($conn, 'page_views');
$hasSessions = cbAdminTableExists($conn, 'sessions');
$hasSearchTerms = cbAdminTableExists($conn, 'search_terms');
$hasCart = cbAdminTableExists($conn, 'cart');
$hasUserAddresses = cbAdminTableExists($conn, 'user_addresses');
$hasIpGeolocation = cbAdminTableExists($conn, 'ip_geolocation');
$hasIpGeolocationHistory = cbAdminTableExists($conn, 'ip_geolocation_history');

cbAdminReconcilePayfastPayments($conn);

$sheetProductCount = 0;
$sheetCacheFile = __DIR__ . '/../sheet_cache/products.tsv';
if (is_file($sheetCacheFile)) {
    $sheetLines = file($sheetCacheFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $sheetProductCount = max(0, count($sheetLines) - 1);
} elseif (cbAdminTableExists($conn, 'product')) {
    $sheetProductCount = cbAdminScalar($conn, "SELECT COUNT(*) FROM product WHERE COALESCE(enabled_product, 1) = 1");
}

$dashboardPeriod = strtolower(trim((string) ($_GET['period'] ?? 'month')));
if ($dashboardPeriod === 'alltime') {
    $dashboardPeriod = 'all';
}
if (!in_array($dashboardPeriod, ['week', 'month', 'all'], true)) {
    $dashboardPeriod = 'month';
}

$currentWeekStartSql = "DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
$periodOptions = [
    'week' => [
        'label' => 'This week',
        'button' => 'Weekly',
        'where' => "order_date >= $currentWeekStartSql",
        'previous_where' => "order_date >= DATE_SUB($currentWeekStartSql, INTERVAL 7 DAY) AND order_date < $currentWeekStartSql",
        'comparison' => 'vs previous week',
    ],
    'month' => [
        'label' => 'This month',
        'button' => 'Monthly',
        'where' => "order_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        'previous_where' => "order_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND order_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        'comparison' => 'vs previous month',
    ],
    'all' => [
        'label' => 'All time',
        'button' => 'All time',
        'where' => '1 = 1',
        'previous_where' => '',
        'comparison' => 'all paid history',
    ],
];
$periodConfig = $periodOptions[$dashboardPeriod];
$periodWhere = $periodConfig['where'];
$previousPeriodWhere = $periodConfig['previous_where'];

$totalSales = $hasOrders ? cbAdminScalar($conn, "SELECT COALESCE(SUM(grand_total_amount), 0) FROM orders WHERE COALESCE(payment_status, 0) IN (1, 2)") : 0;
$monthSales = $hasOrders ? cbAdminScalar($conn, "SELECT COALESCE(SUM(grand_total_amount), 0) FROM orders WHERE order_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND COALESCE(payment_status, 0) IN (1, 2)") : 0;
$previousMonthSales = $hasOrders ? cbAdminScalar($conn, "SELECT COALESCE(SUM(grand_total_amount), 0) FROM orders WHERE order_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND order_date < DATE_FORMAT(CURDATE(), '%Y-%m-01') AND COALESCE(payment_status, 0) IN (1, 2)") : 0;
$periodSales = $hasOrders ? cbAdminScalar($conn, "SELECT COALESCE(SUM(grand_total_amount), 0) FROM orders WHERE $periodWhere AND COALESCE(payment_status, 0) IN (1, 2)") : 0;
$previousPeriodSales = ($hasOrders && $previousPeriodWhere !== '') ? cbAdminScalar($conn, "SELECT COALESCE(SUM(grand_total_amount), 0) FROM orders WHERE $previousPeriodWhere AND COALESCE(payment_status, 0) IN (1, 2)") : 0;
$periodPaidOrders = $hasOrders ? cbAdminScalar($conn, "SELECT COUNT(*) FROM orders WHERE $periodWhere AND COALESCE(payment_status, 0) IN (1, 2)") : 0;
$periodOrderCount = $hasOrders ? cbAdminScalar($conn, "SELECT COUNT(*) FROM orders WHERE $periodWhere") : 0;
$periodPendingOrders = $hasOrders ? cbAdminScalar($conn, "SELECT COUNT(*) FROM orders WHERE $periodWhere AND (LOWER(COALESCE(order_status, '')) IN ('pending', 'processing', '') OR COALESCE(payment_status, 0) = 0)") : 0;
$pendingOrders = $hasOrders ? cbAdminScalar($conn, "SELECT COUNT(*) FROM orders WHERE LOWER(COALESCE(order_status, '')) IN ('pending', 'processing', '') OR COALESCE(payment_status, 0) = 0") : 0;
$paidOrders = $hasOrders ? cbAdminScalar($conn, "SELECT COUNT(*) FROM orders WHERE COALESCE(payment_status, 0) IN (1, 2)") : 0;
$todayOrders = $hasOrders ? cbAdminScalar($conn, "SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()") : 0;
$newClients = $hasUsers ? cbAdminScalar($conn, "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") : 0;
$subscribers = $hasSubscribers ? cbAdminScalar($conn, "SELECT COUNT(*) FROM subscribers WHERE is_subscribed = 1") : 0;
$reviewCount = $hasReviews ? cbAdminScalar($conn, "SELECT COUNT(*) FROM reviews") : 0;
$averageRating = $hasReviews ? cbAdminScalar($conn, "SELECT COALESCE(AVG(rating), 0) FROM reviews") : 0;
$scheduledCampaigns = $hasScheduledEmails ? cbAdminScalar($conn, "SELECT COUNT(*) FROM scheduled_emails WHERE sent = 0") : 0;
$sentCampaigns = $hasScheduledEmails ? cbAdminScalar($conn, "SELECT COUNT(*) FROM scheduled_emails WHERE sent = 1") : 0;
$paymentIssues = $hasPaymentChecks ? cbAdminScalar($conn, "SELECT COUNT(*) FROM payment_checks WHERE check_result = 0 AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)") : 0;
$siteErrors = $hasActionLogs ? cbAdminScalar($conn, "SELECT COUNT(*) FROM action_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND (action LIKE '%error%' OR details LIKE '%error%')") : 0;
$humanSessionFilter = "s.user_agent NOT REGEXP 'bot|crawl|spider|preview|facebookexternalhit|whatsapp|telegrambot|curl|wget|monitor|uptime'";
$humanPageFilter = "pv.url NOT LIKE '%/admin-cb/%' AND pv.url NOT LIKE '%log_action.php%' AND pv.url NOT LIKE '%update_end_time.php%'";
$humanActionFilter = "al.user_agent NOT REGEXP 'bot|crawl|spider|preview|facebookexternalhit|whatsapp|telegrambot|curl|wget|monitor|uptime'";
$geoSourceSql = '';
if ($hasIpGeolocationHistory) {
    $geoSourceSql = "(SELECT ip_address,
        MAX(city) AS city,
        MAX(country) AS country,
        MAX(state_prov) AS province,
        MAX(district) AS suburb,
        MAX(COALESCE(NULLIF(organization, ''), NULLIF(isp, ''))) AS provider
        FROM ip_geolocation_history
        GROUP BY ip_address)";
} elseif ($hasIpGeolocation) {
    $geoSourceSql = "(SELECT ip_address, city, country, '' AS province, '' AS suburb, '' AS provider FROM ip_geolocation)";
}
$sessionGeoJoin = $geoSourceSql !== '' ? "LEFT JOIN $geoSourceSql qgeo ON qgeo.ip_address = s.ip_address" : "";
$actionGeoJoin = $geoSourceSql !== '' ? "LEFT JOIN $geoSourceSql qgeo ON qgeo.ip_address = al.ip_address" : "";
$sessionGeoWhere = $geoSourceSql !== '' ? "(COALESCE(qgeo.suburb, '') <> '' OR COALESCE(qgeo.city, '') <> '')" : "0 = 1";
$actionGeoWhere = $geoSourceSql !== '' ? "(COALESCE(qgeo.suburb, '') <> '' OR COALESCE(qgeo.city, '') <> '')" : "0 = 1";
$sessionGeoSelect = $geoSourceSql !== ''
    ? "COALESCE(qgeo.suburb, '') AS suburb, COALESCE(qgeo.city, '') AS city, COALESCE(qgeo.country, '') AS country"
    : "'' AS suburb, '' AS city, '' AS country";
$onlineVisitors = $hasSessions ? cbAdminScalar($conn, "SELECT COUNT(DISTINCT CONCAT(COALESCE(s.ip_address, ''), '|', COALESCE(s.user_agent, '')))
    FROM sessions s
    $sessionGeoJoin
    WHERE $humanSessionFilter
      AND $sessionGeoWhere
      AND COALESCE(s.end_time, s.start_time) >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)") : 0;
$todayVisitors = $hasSessions ? cbAdminScalar($conn, "SELECT COUNT(DISTINCT CONCAT(COALESCE(s.ip_address, ''), '|', COALESCE(s.user_agent, '')))
    FROM sessions s
    $sessionGeoJoin
    WHERE $humanSessionFilter
      AND $sessionGeoWhere
      AND s.start_time >= CURDATE()") : 0;
$todayRawSessions = $hasSessions ? cbAdminScalar($conn, "SELECT COUNT(*) FROM sessions WHERE DATE(start_time) = CURDATE()") : 0;
$todayPageViews = ($hasPageViews && $hasSessions) ? cbAdminScalar($conn, "SELECT COUNT(*)
    FROM page_views pv
    LEFT JOIN sessions s ON pv.session_id = s.id
    $sessionGeoJoin
    WHERE $humanPageFilter
      AND $humanSessionFilter
      AND $sessionGeoWhere
      AND pv.timestamp >= CURDATE()") : 0;

$recentOrders = $hasOrders ? cbAdminRows($conn, "SELECT id, order_status, payment_status, grand_total_amount, payment_method, order_date FROM orders ORDER BY order_date DESC LIMIT 8") : [];
$topProducts = ($hasOrderItems ? cbAdminRows($conn, "SELECT product_id, product_title, SUM(quantity) AS qty, SUM((price - COALESCE(discount_amount, 0)) * quantity) AS total FROM order_items GROUP BY product_id, product_title ORDER BY qty DESC LIMIT 6") : []);
$recentReviews = $hasReviews ? cbAdminRows($conn, "SELECT product_id, u_name, rating, comment FROM reviews ORDER BY id DESC LIMIT 5") : [];
$recentCampaigns = $hasScheduledEmails ? cbAdminRows($conn, "SELECT id, subject, scheduled_at, sent FROM scheduled_emails ORDER BY scheduled_at DESC LIMIT 5") : [];
$recentCronjobs = $hasCronjobs ? cbAdminRows($conn, "SELECT job_name, description, execution_time FROM cronjobs ORDER BY id DESC LIMIT 12") : [];
$emailLinkedVisitors = ($hasPageViews && $hasSessions) ? cbAdminRows($conn, "SELECT pv.url, pv.referrer_url, pv.timestamp, s.user_id, s.session_id, s.ip_address, COALESCE(u.username, 'Guest') AS visitor_name, u.email
    FROM page_views pv
    LEFT JOIN sessions s ON pv.session_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    $sessionGeoJoin
    WHERE $humanSessionFilter
      AND $sessionGeoWhere
      AND $humanPageFilter
      AND (pv.url LIKE '%utm_source=email%' OR pv.url LIKE '%cb_campaign=%' OR pv.referrer_url LIKE '%utm_source=email%' OR pv.referrer_url LIKE '%cb_campaign=%')
      AND pv.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY pv.timestamp DESC
    LIMIT 10") : [];
$topViewedPages = ($hasPageViews && $hasSessions) ? cbAdminRows($conn, "SELECT SUBSTRING_INDEX(REPLACE(REPLACE(pv.url, 'https://www.candybird.co.za', ''), 'http://www.candybird.co.za', ''), '#', 1) AS page_url, COUNT(*) AS views, MAX(pv.timestamp) AS last_seen
    FROM page_views pv
    LEFT JOIN sessions s ON pv.session_id = s.id
    $sessionGeoJoin
    WHERE $humanPageFilter
      AND $humanSessionFilter
      AND $sessionGeoWhere
      AND pv.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY page_url
    ORDER BY views DESC
    LIMIT 10") : [];
$topReferrers = ($hasPageViews && $hasSessions) ? cbAdminRows($conn, "SELECT COALESCE(NULLIF(pv.referrer_url, ''), pv.referrer) AS source, COUNT(*) AS visits, MAX(pv.timestamp) AS last_seen
    FROM page_views pv
    LEFT JOIN sessions s ON pv.session_id = s.id
    $sessionGeoJoin
    WHERE $humanPageFilter
      AND $humanSessionFilter
      AND $sessionGeoWhere
      AND pv.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY source
    ORDER BY visits DESC
    LIMIT 8") : [];
$topProductClicks = $hasActionLogs ? cbAdminRows($conn, "SELECT details, COUNT(*) AS clicks, MAX(created_at) AS last_seen
    FROM action_logs al
    $actionGeoJoin
    WHERE $humanActionFilter AND $actionGeoWhere AND action = 'UX product click' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY details
    ORDER BY clicks DESC
    LIMIT 10") : [];
$topCategoryClicks = $hasActionLogs ? cbAdminRows($conn, "SELECT details, COUNT(*) AS clicks, MAX(created_at) AS last_seen
    FROM action_logs al
    $actionGeoJoin
    WHERE $humanActionFilter AND $actionGeoWhere AND action = 'UX category click' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY details
    ORDER BY clicks DESC
    LIMIT 10") : [];
$scrollDepths = $hasActionLogs ? cbAdminRows($conn, "SELECT action, COUNT(*) AS sessions
    FROM action_logs al
    $actionGeoJoin
    WHERE $humanActionFilter AND $actionGeoWhere AND action LIKE 'UX scroll depth %' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY action
    ORDER BY FIELD(action, 'UX scroll depth 25%', 'UX scroll depth 50%', 'UX scroll depth 75%', 'UX scroll depth 100%')") : [];
$zeroResultSearches = ($hasSearchTerms && $hasSessions) ? cbAdminRows($conn, "SELECT st.term, COUNT(*) AS misses, MAX(st.timestamp) AS last_seen
    FROM search_terms st
    LEFT JOIN sessions s ON st.session_id = s.id
    $sessionGeoJoin
    WHERE st.results_count = 0
      AND $humanSessionFilter
      AND $sessionGeoWhere
    GROUP BY term
    ORDER BY misses DESC, last_seen DESC
    LIMIT 10") : [];
$openCarts = ($hasCart ? cbAdminRows($conn, "SELECT c.user_id, c.guest_identifier, COUNT(*) AS line_count, SUM(c.quantity) AS item_count,
        COALESCE(u.username, 'Guest customer') AS customer_name,
        COALESCE(u.email, '') AS email,
        '' AS billing_city, '' AS billing_province
    FROM cart c
    LEFT JOIN users u ON c.user_id = u.id
    GROUP BY c.user_id, c.guest_identifier, customer_name, email
    ORDER BY item_count DESC
    LIMIT 10") : []);
$orderMonthParts = $hasOrders ? cbAdminRows($conn, "SELECT
        CASE WHEN DAY(order_date) <= 10 THEN '1st-10th' WHEN DAY(order_date) <= 20 THEN '11th-20th' ELSE '21st-month end' END AS month_part,
        COUNT(*) AS order_count,
        COALESCE(SUM(grand_total_amount), 0) AS revenue
    FROM orders
    GROUP BY month_part
    ORDER BY FIELD(month_part, '1st-10th', '11th-20th', '21st-month end')") : [];
$cartMonthParts = $hasActionLogs ? cbAdminRows($conn, "SELECT
        CASE WHEN DAY(created_at) <= 10 THEN '1st-10th' WHEN DAY(created_at) <= 20 THEN '11th-20th' ELSE '21st-month end' END AS month_part,
        COUNT(*) AS cart_actions
    FROM action_logs al
    $actionGeoJoin
    WHERE $humanActionFilter AND $actionGeoWhere AND action LIKE '%cart%' AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    GROUP BY month_part
    ORDER BY FIELD(month_part, '1st-10th', '11th-20th', '21st-month end')") : [];
$orderCities = ($hasOrders && $hasUserAddresses) ? cbAdminRows($conn, "SELECT COALESCE(NULLIF(ua.billing_city, ''), 'Unknown') AS city, COALESCE(NULLIF(ua.billing_province, ''), 'Unknown') AS province, COUNT(DISTINCT o.id) AS orders, COALESCE(SUM(o.grand_total_amount), 0) AS revenue
    FROM orders o
    LEFT JOIN user_addresses ua ON o.guest_identifier <> '' AND o.guest_identifier = ua.guest_identifier
    WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY city, province
    ORDER BY orders DESC, revenue DESC
    LIMIT 10") : [];
$visitorCities = ($hasActionLogs && $geoSourceSql !== '') ? cbAdminRows($conn, "SELECT COALESCE(NULLIF(qgeo.city, ''), NULLIF(qgeo.suburb, ''), 'Unknown') AS city, COALESCE(NULLIF(qgeo.country, ''), 'Unknown') AS country, COUNT(*) AS actions
    FROM action_logs al
    $actionGeoJoin
    WHERE $actionGeoWhere AND al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY city, country
    ORDER BY actions DESC
    LIMIT 10") : [];
$weeklyVisitorRows = $hasSessions ? cbAdminRows($conn, "SELECT DATE(s.start_time) AS visit_day, COUNT(DISTINCT CONCAT(COALESCE(s.ip_address, ''), '|', COALESCE(s.user_agent, ''))) AS visitors
    FROM sessions s
    $sessionGeoJoin
    WHERE $humanSessionFilter
      AND $sessionGeoWhere
      AND s.start_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(s.start_time)
    ORDER BY visit_day ASC") : [];
$weeklyVisitorsByDay = [];
foreach ($weeklyVisitorRows as $row) {
    $weeklyVisitorsByDay[(string) ($row['visit_day'] ?? '')] = (int) ($row['visitors'] ?? 0);
}
$weeklyVisitors = [];
for ($i = 6; $i >= 0; $i--) {
    $dayKey = date('Y-m-d', strtotime("-{$i} days"));
    $weeklyVisitors[] = [
        'label' => date('D d M', strtotime($dayKey)),
        'count' => $weeklyVisitorsByDay[$dayKey] ?? 0,
    ];
}
$weeklyTopSearch = ($hasSearchTerms && $hasSessions) ? cbAdminRows($conn, "SELECT st.term AS label, COUNT(*) AS total
    FROM search_terms st
    LEFT JOIN sessions s ON st.session_id = s.id
    $sessionGeoJoin
    WHERE st.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND COALESCE(st.term, '') <> ''
      AND $humanSessionFilter
      AND $sessionGeoWhere
    GROUP BY st.term
    ORDER BY total DESC, MAX(st.timestamp) DESC
    LIMIT 1") : [];
if (empty($weeklyTopSearch) && $hasActionLogs) {
    $weeklyTopSearch = cbAdminRows($conn, "SELECT details AS label, COUNT(*) AS total
        FROM action_logs al
        $actionGeoJoin
        WHERE $humanActionFilter
          AND $actionGeoWhere
          AND action = 'UX search submitted'
          AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY details
        ORDER BY total DESC, MAX(created_at) DESC
        LIMIT 1");
}
$weeklyTopProductClick = $hasActionLogs ? cbAdminRows($conn, "SELECT details AS label, COUNT(*) AS total
    FROM action_logs al
    $actionGeoJoin
    WHERE $humanActionFilter
      AND $actionGeoWhere
      AND action = 'UX product click'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY details
    ORDER BY total DESC, MAX(created_at) DESC
    LIMIT 1") : [];
$weeklyTopCategoryClick = $hasActionLogs ? cbAdminRows($conn, "SELECT details AS label, COUNT(*) AS total
    FROM action_logs al
    $actionGeoJoin
    WHERE $humanActionFilter
      AND $actionGeoWhere
      AND action = 'UX category click'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY details
    ORDER BY total DESC, MAX(created_at) DESC
    LIMIT 1") : [];
$weeklyTopAddToCart = $hasActionLogs ? cbAdminRows($conn, "SELECT CASE
        WHEN action LIKE 'Clicked on add-to-cart%' THEN action
        ELSE details
    END AS label, COUNT(*) AS total
    FROM action_logs al
    $actionGeoJoin
    WHERE $humanActionFilter
      AND $actionGeoWhere
      AND (action LIKE '%add-to-cart%' OR action LIKE 'Added item%')
      AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY label
    ORDER BY total DESC, MAX(created_at) DESC
    LIMIT 1") : [];
$weeklyMetricCards = [
    ['label' => 'Top search term', 'kind' => 'search', 'row' => $weeklyTopSearch[0] ?? null],
    ['label' => 'Top product link', 'kind' => 'product_click', 'row' => $weeklyTopProductClick[0] ?? null],
    ['label' => 'Top category link', 'kind' => 'category_click', 'row' => $weeklyTopCategoryClick[0] ?? null],
    ['label' => 'Top add-to-cart source', 'kind' => 'add_to_cart', 'row' => $weeklyTopAddToCart[0] ?? null],
];
$recentActivity = $hasActionLogs ? cbAdminRows($conn, "SELECT al.action, al.details, al.created_at, al.user_id, al.guest_identifier, COALESCE(u.username, 'Guest') AS visitor_name
    FROM action_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $actionGeoJoin
    WHERE $humanActionFilter
      AND $actionGeoWhere
    ORDER BY al.created_at DESC
    LIMIT 12") : [];
$liveVisitorRows = $hasSessions ? cbAdminRows($conn, "SELECT
        s.id,
        s.user_id,
        s.session_id,
        s.ip_address,
        $sessionGeoSelect,
        COALESCE(u.username, 'Guest visitor') AS visitor_name,
        u.email,
        COALESCE(s.end_time, s.start_time) AS last_seen,
        CASE WHEN COALESCE(s.end_time, s.start_time) >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'Online' ELSE 'Recent' END AS status_label,
        0 AS page_views,
        0 AS actions,
        0 AS cart_items
    FROM sessions s
    LEFT JOIN users u ON s.user_id = u.id
    $sessionGeoJoin
    WHERE $humanSessionFilter
      AND $sessionGeoWhere
      AND COALESCE(s.end_time, s.start_time) >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY (COALESCE(s.end_time, s.start_time) >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) DESC, last_seen DESC
    LIMIT 10") : [];
$importantLinks = [
    [
        'label' => 'Products',
        'description' => 'Manage product sheet links, health checks, template and product mirror sync.',
        'url' => 'products',
        'button' => 'Open products'
    ],
    [
        'label' => 'Orders',
        'description' => 'View, edit, print, update and message customer orders.',
        'url' => 'manage_orders',
        'button' => 'Open orders'
    ],
    [
        'label' => 'Customers',
        'description' => 'Registered customers plus guest checkout customers and copyable email lists.',
        'url' => 'manage_users',
        'button' => 'Open customers'
    ],
    [
        'label' => 'Subscribers',
        'description' => 'Create tests, schedule campaigns and send broadcasts to active subscribers.',
        'url' => 'schedule_email',
        'button' => 'Open subscribers'
    ],
    [
        'label' => 'Coupon Tester',
        'description' => 'Test coupon dates, restrictions and basket calculations before customers use a code.',
        'url' => 'coupon_tester',
        'button' => 'Test coupon'
    ],
    [
        'label' => 'Mega Sync All Sheets',
        'description' => 'Force refresh products, coupons, clearance and wholesale caches after sheet edits.',
        'url' => 'sheets',
        'button' => 'Mega sync'
    ],
    [
        'label' => 'View Shop',
        'description' => 'Open the public storefront in a new tab.',
        'url' => '../products',
        'button' => 'View shop'
    ],
    [
        'label' => 'Social Accounts',
        'description' => 'Keep social handles, login details and posting reminders in one place.',
        'url' => 'social_accounts',
        'button' => 'Open socials'
    ],
    [
        'label' => 'Business Documents',
        'description' => 'Open the admin-only document vault for CIPC, SARS, contracts and records.',
        'url' => 'business_documents',
        'button' => 'Open documents'
    ],
];

$sheetSample = [];
if (function_exists('getSheetProducts')) {
    $allSheetProducts = getSheetProducts(false);
    if (is_array($allSheetProducts)) {
        $sheetSample = array_slice(array_values($allSheetProducts), 0, 5);
    }
}

$dashboardCronJobs = [
    [
        'file' => 'generate_google_shopping_items.php',
        'label' => 'Google Shopping Feed',
        'description' => 'Creates the Merchant Center feed file from the product sheet.',
        'result_url' => '../uploads/google_products/google_shopping_feed.txt',
    ],
    [
        'file' => 'generate_sitemap.php',
        'label' => 'Sitemap',
        'description' => 'Refreshes sitemap output from products, categories, and recipes.',
        'result_url' => '../crons/generate_sitemap.php',
    ],
    [
        'file' => 'geolocation.php',
        'label' => 'Geolocation',
        'description' => 'Updates visitor location data for UX intelligence.',
        'result_url' => '',
    ],
    [
        'file' => 'db_backup_and_email.php',
        'label' => 'Full Website Backup',
        'description' => 'Creates a restorable zip with website files and database SQL.',
        'result_url' => '',
    ],
    [
        'file' => 'social_posting_reminder.php',
        'label' => 'Social Posting Reminder',
        'description' => 'Emails the weekly/daily posting nudge when due.',
        'result_url' => '',
    ],
];
?>

<title>Admin Dashboard - CandyBird</title>

<style>
    .admin-dashboard { padding: 28px 0 50px; }
    .dashboard-hero { background: #2d1739; color: #fff; padding: 24px; border-radius: 8px; margin-bottom: 22px; }
    .dashboard-hero h1 { color: #fcb42f; margin-bottom: 6px; }
    .dashboard-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 22px; }
    .dash-card { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 18px; min-height: 132px; box-shadow: 0 10px 28px rgba(45, 23, 57, .06); }
    a.dash-card { color: inherit; display: block; text-decoration: none; transition: transform .18s ease, box-shadow .18s ease; }
    a.dash-card:hover { box-shadow: 0 14px 34px rgba(45, 23, 57, .1); color: inherit; transform: translateY(-2px); }
    .dash-card .label { color: #6d6270; font-size: 13px; font-weight: 700; text-transform: uppercase; }
    .dash-card .value { color: #5b1178; font-size: 28px; font-weight: 800; line-height: 1.25; margin-top: 8px; }
    .dash-card .hint { color: #6d6270; font-size: 13px; margin-top: 8px; }
    .dashboard-period-bar { align-items: center; background: #fff; border: 1px solid #eadfd2; border-radius: 8px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; margin-bottom: 14px; padding: 12px 14px; }
    .dashboard-period-title { color: #2d1739; font-size: 15px; font-weight: 900; }
    .dashboard-period-tabs { display: flex; flex-wrap: wrap; gap: 7px; }
    .dashboard-period-tabs a { border: 1px solid #d9c7b4; border-radius: 999px; color: #5b1178; font-size: 13px; font-weight: 800; padding: 6px 12px; text-decoration: none; }
    .dashboard-period-tabs a.active { background: #5b1178; border-color: #5b1178; color: #fff; }
    .dash-panel { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 18px; height: 100%; }
    .dash-panel h2 { color: #5b1178; font-size: 20px; margin-bottom: 15px; }
    .quick-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .quick-actions form { margin: 0; }
    .link-card { border: 1px solid #eadfd2; border-radius: 8px; padding: 14px; height: 100%; background: #fffaf2; }
    .link-card h3 { color: #5b1178; font-size: 16px; margin-bottom: 6px; }
    .link-card p { color: #6d6270; font-size: 13px; min-height: 40px; margin-bottom: 12px; }
    .order-actions { display: flex; flex-wrap: wrap; gap: 6px; }
    .cron-card { border: 1px solid #eadfd2; border-radius: 8px; padding: 14px; background: #fffaf2; height: 100%; }
    .cron-card h3 { color: #5b1178; font-size: 16px; margin-bottom: 6px; }
    .cron-card p { color: #6d6270; font-size: 13px; min-height: 40px; margin-bottom: 12px; }
    .cron-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .weekly-summary-grid { display: grid; grid-template-columns: minmax(0, 1.3fr) repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 22px; }
    .weekly-summary-card { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 16px; box-shadow: 0 10px 28px rgba(45, 23, 57, .05); }
    .weekly-summary-card h2, .weekly-summary-card h3 { color: #5b1178; font-size: 17px; margin: 0 0 12px; }
    .weekly-visitors-list { display: grid; gap: 7px; }
    .weekly-visitors-row { align-items: center; display: grid; grid-template-columns: 84px 1fr 46px; gap: 8px; color: #4b3d46; font-size: 13px; }
    .weekly-visitors-bar { background: #f4ece3; border-radius: 999px; height: 8px; overflow: hidden; }
    .weekly-visitors-bar span { background: #5b1178; display: block; height: 100%; min-width: 4px; }
    .weekly-metric-label { color: #6d6270; font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .weekly-metric-value { color: #2d1739; font-size: 17px; font-weight: 900; line-height: 1.35; margin-top: 8px; word-break: break-word; }
    .weekly-metric-count { color: #6d6270; font-size: 13px; margin-top: 8px; }
    .status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; background: #2aa85a; }
    .status-dot.warn { background: #e8a100; }
    .status-dot.bad { background: #d53f3f; }
    .table-sm td, .table-sm th { vertical-align: middle; }
    @media (max-width: 1199px) { .dashboard-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .weekly-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575px) { .dashboard-grid, .weekly-summary-grid { grid-template-columns: 1fr; } .dash-card .value { font-size: 24px; } }
</style>

<div class="container admin-dashboard">
    <div class="dashboard-hero">
        <h1>Admin Dashboard</h1>
        <p class="mb-0">Orders, payments, campaigns, reviews, sheet products, and recent website activity at a glance.</p>
        <div class="quick-actions">
            <a href="products" class="btn btn-warning btn-sm">Products</a>
            <a href="manage_orders" class="btn btn-warning btn-sm">Orders</a>
            <a href="manage_users" class="btn btn-warning btn-sm">Customers</a>
            <a href="schedule_email" class="btn btn-light btn-sm">Subscribers</a>
            <a href="wholesale_pricelist" class="btn btn-light btn-sm">Wholesale Pricelist</a>
            <form method="post" action="sheets" class="m-0">
                <input type="hidden" name="sheet_action" value="refresh_all">
                <button type="submit" class="btn btn-light btn-sm">Mega Sync All Sheets</button>
            </form>
            <a href="../products" class="btn btn-outline-light btn-sm" target="_blank" rel="noopener noreferrer">View Shop</a>
        </div>
    </div>

    <?php if ($dashboardMessage): ?>
        <div class="alert <?= $dashboardSuccess ? 'alert-success' : 'alert-danger' ?>"><?= htmlspecialchars($dashboardMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="dashboard-period-bar">
        <div>
            <div class="dashboard-period-title">Order summary: <?= htmlspecialchars($periodConfig['label'], ENT_QUOTES, 'UTF-8') ?></div>
            <small class="text-muted">Monthly is the default view when this page opens.</small>
        </div>
        <div class="dashboard-period-tabs" aria-label="Dashboard order summary period">
            <?php foreach ($periodOptions as $periodKey => $periodOption): ?>
                <a href="dashboard?period=<?= htmlspecialchars($periodKey, ENT_QUOTES, 'UTF-8') ?>" class="<?= $dashboardPeriod === $periodKey ? 'active' : '' ?>">
                    <?= htmlspecialchars($periodOption['button'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dash-card">
            <div class="label"><?= htmlspecialchars($periodConfig['label'], ENT_QUOTES, 'UTF-8') ?> paid sales</div>
            <div class="value"><?= cbAdminMoney($periodSales) ?></div>
            <div class="hint"><?= number_format((float) $periodPaidOrders) ?> paid of <?= number_format((float) $periodOrderCount) ?> total orders</div>
        </div>
        <div class="dash-card">
            <div class="label"><?= htmlspecialchars($periodConfig['label'], ENT_QUOTES, 'UTF-8') ?> movement</div>
            <div class="value"><?= $dashboardPeriod === 'all' ? number_format((float) $paidOrders) : cbAdminPercentChange($periodSales, $previousPeriodSales) ?></div>
            <div class="hint"><?= $dashboardPeriod === 'all' ? 'all paid orders recorded' : htmlspecialchars($periodConfig['comparison'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="dash-card">
            <div class="label"><?= htmlspecialchars($periodConfig['label'], ENT_QUOTES, 'UTF-8') ?> pending</div>
            <div class="value"><?= number_format((float) $periodPendingOrders) ?></div>
            <div class="hint"><?= number_format((float) $todayOrders) ?> orders today, <?= number_format((float) $pendingOrders) ?> pending overall</div>
        </div>
        <div class="dash-card">
            <div class="label">Sheet products</div>
            <div class="value"><?= number_format($sheetProductCount) ?></div>
            <div class="hint">Google Sheet source of truth</div>
        </div>
        <div class="dash-card">
            <div class="label">Subscribers</div>
            <div class="value"><?= number_format((float) $subscribers) ?></div>
            <div class="hint"><?= number_format((float) $scheduledCampaigns) ?> scheduled, <?= number_format((float) $sentCampaigns) ?> sent</div>
        </div>
        <div class="dash-card">
            <div class="label">Reviews</div>
            <div class="value"><?= number_format((float) $averageRating, 1) ?>/5</div>
            <div class="hint"><?= number_format((float) $reviewCount) ?> total reviews</div>
        </div>
        <div class="dash-card">
            <div class="label">New clients</div>
            <div class="value"><?= number_format((float) $newClients) ?></div>
            <div class="hint">Last 30 days</div>
        </div>
        <div class="dash-card">
            <div class="label">Website alerts</div>
            <div class="value"><?= number_format((float) ($paymentIssues + $siteErrors)) ?></div>
            <div class="hint"><?= number_format((float) $paymentIssues) ?> payment checks, <?= number_format((float) $siteErrors) ?> site logs</div>
        </div>
        <div class="dash-card">
            <div class="label">Online now</div>
            <div class="value"><?= number_format((float) $onlineVisitors) ?></div>
            <div class="hint">Geo-qualified, active in the last 5 minutes</div>
        </div>
        <a class="dash-card" href="visitor_breakdown?range=today" title="Open visitor breakdown">
            <div class="label">Qualified visitors today</div>
            <div class="value"><?= number_format((float) $todayVisitors) ?></div>
            <div class="hint"><?= number_format((float) $todayPageViews) ?> geo-qualified page views, <?= number_format((float) $todayRawSessions) ?> raw sessions. Click to inspect.</div>
        </a>
    </div>

    <div class="weekly-summary-grid">
        <div class="weekly-summary-card">
            <h2>Weekly Visitors</h2>
            <?php
            $maxWeeklyVisitors = max(1, max(array_map(static function($item) { return (int) $item['count']; }, $weeklyVisitors)));
            ?>
            <div class="weekly-visitors-list">
                <?php foreach ($weeklyVisitors as $day): 
                    $width = max(4, min(100, round(((int) $day['count'] / $maxWeeklyVisitors) * 100)));
                ?>
                    <div class="weekly-visitors-row">
                        <span><?= htmlspecialchars($day['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="weekly-visitors-bar"><span style="width:<?= (int) $width ?>%"></span></span>
                        <strong><?= number_format((int) $day['count']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php foreach ($weeklyMetricCards as $metric): 
            $row = is_array($metric['row']) ? $metric['row'] : [];
            $metricKind = $metric['kind'] ?? '';
            $metricValue = cbAdminWeeklyReportValue($metricKind, $row['label'] ?? '');
            $metricCount = (int) ($row['total'] ?? 0);
        ?>
            <div class="weekly-summary-card">
                <div class="weekly-metric-label"><?= htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="weekly-metric-value"><?= htmlspecialchars($metricValue, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="weekly-metric-count"><?= htmlspecialchars(cbAdminWeeklyReportCaption($metricKind, $metricCount), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="dash-panel">
                <h2>Important Links</h2>
                <div class="row">
                    <?php foreach ($importantLinks as $link): ?>
                        <div class="col-md-6 col-xl-4 mb-3">
                            <div class="link-card">
                                <h3><?= htmlspecialchars($link['label']) ?></h3>
                                <p><?= htmlspecialchars($link['description']) ?></p>
                                <a href="<?= htmlspecialchars($link['url']) ?>" class="btn btn-primary btn-sm" <?= strpos($link['url'], 'http') === 0 ? 'target="_blank" rel="noopener noreferrer"' : '' ?>><?= htmlspecialchars($link['button']) ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="dash-panel">
                <h2>Run Cron Jobs</h2>
                <p class="text-muted">Trigger approved maintenance jobs manually without opening cPanel. The normal cPanel schedule can still stay active.</p>
                <div class="row">
                    <?php foreach ($dashboardCronJobs as $cronJob): ?>
                        <div class="col-md-6 col-xl-3 mb-3">
                            <div class="cron-card">
                                <h3><?= htmlspecialchars($cronJob['label']) ?></h3>
                                <p><?= htmlspecialchars($cronJob['description']) ?></p>
                                <div class="cron-actions">
                                    <form method="post" action="run_cron" onsubmit="return confirm('Run <?= htmlspecialchars($cronJob['label'], ENT_QUOTES, 'UTF-8') ?> now?');">
                                        <input type="hidden" name="job" value="<?= htmlspecialchars($cronJob['file'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Run now</button>
                                    </form>
                                    <?php if (!empty($cronJob['result_url'])): ?>
                                        <a class="btn btn-outline-dark btn-sm" href="<?= htmlspecialchars($cronJob['result_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">View result</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="dash-panel">
                <h2>Latest Orders</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Order</th><th>Status</th><th>Payment</th><th>Total</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="6">No orders found yet.</td></tr>
                        <?php else: foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><a href="order_details?order_id=<?= urlencode($order['id']) ?>">#<?= htmlspecialchars($order['id']) ?></a></td>
                                <td><?= htmlspecialchars($order['order_status'] ?: 'Pending') ?></td>
                                <td><?= ((int) $order['payment_status'] > 0) ? '<span class="status-dot"></span>Paid' : '<span class="status-dot warn"></span>Waiting' ?></td>
                                <td><?= cbAdminMoney($order['grand_total_amount']) ?></td>
                                <td><?= htmlspecialchars($order['order_date']) ?></td>
                                <td>
                                    <div class="order-actions">
                                        <a class="btn btn-outline-primary btn-sm" href="order_details?order_id=<?= urlencode($order['id']) ?>">View</a>
                                        <a class="btn btn-outline-dark btn-sm" href="manage_order?order_id=<?= urlencode($order['id']) ?>">Edit</a>
                                        <a class="btn btn-outline-warning btn-sm" href="manage_orders">Status & message</a>
                                        <a class="btn btn-outline-danger btn-sm" href="manage_orders">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="dash-panel">
                <h2>Top Products</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Product</th><th>Qty</th><th>Sales</th></tr></thead>
                        <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr><td colspan="3">No product sales yet.</td></tr>
                        <?php else: foreach ($topProducts as $item):
                            $productLabel = $item['product_title'] ?: ('Product #' . $item['product_id']);
                        ?>
                            <tr>
                                <td><a href="../product?id=<?= urlencode($item['product_id']) ?>" target="_blank"><?= htmlspecialchars($productLabel) ?></a></td>
                                <td><?= number_format((float) $item['qty']) ?></td>
                                <td><?= cbAdminMoney($item['total']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="dash-panel">
                <h2>Sheet Health</h2>
                <p><span class="status-dot <?= $sheetProductCount ? '' : 'bad' ?>"></span><?= $sheetProductCount ? 'Products loaded from Google Sheet.' : 'No sheet products loaded.' ?></p>
                <ul class="pl-3">
                    <?php foreach ($sheetSample as $product): ?>
                        <li><a href="../product?id=<?= urlencode($product['id']) ?>" target="_blank"><?= htmlspecialchars(($product['name'] ?? 'Product') . ' ' . ($product['size'] ?? '')) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="dash-panel">
                <h2>Email Broadcasts</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tbody>
                        <?php if (empty($recentCampaigns)): ?>
                            <tr><td>No campaigns found.</td></tr>
                        <?php else: foreach ($recentCampaigns as $campaign): ?>
                            <tr>
                                <td><?= ((int) $campaign['sent'] === 1) ? '<span class="status-dot"></span>' : '<span class="status-dot warn"></span>' ?><?= htmlspecialchars($campaign['subject']) ?><br><small><?= htmlspecialchars($campaign['scheduled_at']) ?></small></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <a href="schedule_email" class="btn btn-primary btn-sm">Create broadcast</a>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="dash-panel">
                <h2>Latest Reviews</h2>
                <?php if (empty($recentReviews)): ?>
                    <p>No reviews yet.</p>
                <?php else: foreach ($recentReviews as $review): ?>
                    <div class="mb-3">
                        <strong><?= htmlspecialchars($review['u_name'] ?: 'Customer') ?></strong>
                        <span class="text-warning"><?= str_repeat('★', max(0, min(5, (int) $review['rating']))) ?></span>
                        <div><a href="../product?id=<?= urlencode($review['product_id']) ?>" target="_blank">Product #<?= htmlspecialchars($review['product_id']) ?></a></div>
                        <small><?= htmlspecialchars(strlen($review['comment'] ?? '') > 90 ? substr($review['comment'], 0, 90) . '...' : ($review['comment'] ?? '')) ?></small>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="dash-panel">
                <h2>Successful Cronjobs</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Job</th><th>Details</th><th>Time</th></tr></thead>
                        <tbody>
                        <?php if (empty($recentCronjobs)): ?>
                            <tr><td colspan="3">No cron updates found.</td></tr>
                        <?php else: foreach ($recentCronjobs as $job): ?>
                            <tr>
                                <td><?= htmlspecialchars($job['job_name']) ?></td>
                                <td><?= htmlspecialchars($job['description']) ?></td>
                                <td><?= htmlspecialchars($job['execution_time'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="dash-panel">
                <h2>Customer UX Intelligence</h2>
                <p class="text-muted">This section now uses qualified visitor signals: bots/previews filtered, repeat sessions grouped by device, and only visitors with suburb or city data included.</p>
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Live / Recent Qualified Visitors</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Visitor</th><th>Signal</th><th>Last seen</th></tr></thead>
                                <tbody>
                                <?php if (empty($liveVisitorRows)): ?>
                                    <tr><td colspan="3">No geo-qualified visitor activity recorded yet. Open <a href="visitor_activity">Visitor Activity</a> after browsing the public site to inspect the trail.</td></tr>
                                <?php else: foreach ($liveVisitorRows as $visitor): ?>
                                    <tr>
                                        <td>
                                            <a href="visitor_activity?session_id=<?= (int)$visitor['id'] ?>"><?= htmlspecialchars($visitor['visitor_name'] ?: 'Guest visitor') ?></a>
                                            <br><small><?= htmlspecialchars(trim(($visitor['suburb'] ? $visitor['suburb'] . ', ' : '') . ($visitor['city'] ?: $visitor['country'])) ?: ($visitor['email'] ?: $visitor['session_id'])) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($visitor['status_label']) ?><br><small>Open visitor activity for the detailed timeline.</small></td>
                                        <td><?= htmlspecialchars($visitor['last_seen']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Most Viewed Pages</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Page</th><th>Views</th><th>Last seen</th></tr></thead>
                                <tbody>
                                <?php if (empty($topViewedPages)): ?>
                                    <tr><td colspan="3">Page views will appear here as customers browse.</td></tr>
                                <?php else: foreach ($topViewedPages as $page): ?>
                                    <tr>
                                        <td><small><?= htmlspecialchars($page['page_url'] ?: '/') ?></small></td>
                                        <td><?= number_format((float) $page['views']) ?></td>
                                        <td><?= htmlspecialchars($page['last_seen']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Traffic Sources</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Source</th><th>Visits</th><th>Last seen</th></tr></thead>
                                <tbody>
                                <?php if (empty($topReferrers)): ?>
                                    <tr><td colspan="3">Referrers will appear here once page views are recorded.</td></tr>
                                <?php else: foreach ($topReferrers as $source): ?>
                                    <tr>
                                        <td><small><?= htmlspecialchars($source['source'] ?: 'Direct') ?></small></td>
                                        <td><?= number_format((float) $source['visits']) ?></td>
                                        <td><?= htmlspecialchars($source['last_seen']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Most Clicked Products</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Click path</th><th>Clicks</th><th>Last seen</th></tr></thead>
                                <tbody>
                                <?php if (empty($topProductClicks)): ?>
                                    <tr><td colspan="3">Product clicks will appear here.</td></tr>
                                <?php else: foreach ($topProductClicks as $click): ?>
                                    <tr>
                                        <td><small><?= htmlspecialchars(substr((string) $click['details'], 0, 140)) ?></small></td>
                                        <td><?= number_format((float) $click['clicks']) ?></td>
                                        <td><?= htmlspecialchars($click['last_seen']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Most Clicked Categories / Links</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Click path</th><th>Clicks</th><th>Last seen</th></tr></thead>
                                <tbody>
                                <?php if (empty($topCategoryClicks)): ?>
                                    <tr><td colspan="3">Category and nav clicks will appear here.</td></tr>
                                <?php else: foreach ($topCategoryClicks as $click): ?>
                                    <tr>
                                        <td><small><?= htmlspecialchars(substr((string) $click['details'], 0, 140)) ?></small></td>
                                        <td><?= number_format((float) $click['clicks']) ?></td>
                                        <td><?= htmlspecialchars($click['last_seen']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Scroll Engagement</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Depth</th><th>Logged sessions</th></tr></thead>
                                <tbody>
                                <?php if (empty($scrollDepths)): ?>
                                    <tr><td colspan="2">Scroll depth starts recording from now.</td></tr>
                                <?php else: foreach ($scrollDepths as $depth): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(str_replace('UX scroll depth ', '', $depth['action'])) ?></td>
                                        <td><?= number_format((float) $depth['sessions']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Email Link Visitors</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Visitor</th><th>URL</th><th>When</th></tr></thead>
                                <tbody>
                                <?php if (empty($emailLinkedVisitors)): ?>
                                    <tr><td colspan="3">No tracked email-link visits yet. New campaign buttons now include tracking.</td></tr>
                                <?php else: foreach ($emailLinkedVisitors as $visit): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($visit['visitor_name'] ?: 'Guest') ?><br><small><?= htmlspecialchars($visit['email'] ?: $visit['session_id'] ?: $visit['ip_address']) ?></small></td>
                                        <td><small><?= htmlspecialchars($visit['url']) ?></small></td>
                                        <td><?= htmlspecialchars($visit['timestamp']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Searches With No Results</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Search term</th><th>Misses</th><th>Last seen</th></tr></thead>
                                <tbody>
                                <?php if (empty($zeroResultSearches)): ?>
                                    <tr><td colspan="3">No zero-result searches recorded.</td></tr>
                                <?php else: foreach ($zeroResultSearches as $search): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($search['term']) ?></td>
                                        <td><?= number_format((float) $search['misses']) ?></td>
                                        <td><?= htmlspecialchars($search['last_seen']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Open / Possibly Abandoned Carts</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Customer</th><th>Items</th><th>Location</th></tr></thead>
                                <tbody>
                                <?php if (empty($openCarts)): ?>
                                    <tr><td colspan="3">No open carts found.</td></tr>
                                <?php else: foreach ($openCarts as $cart): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cart['customer_name'] ?: 'Guest customer') ?><br><small><?= htmlspecialchars($cart['email'] ?: $cart['guest_identifier']) ?></small></td>
                                        <td><?= number_format((float) $cart['item_count']) ?> items<br><small><?= number_format((float) $cart['line_count']) ?> product lines</small></td>
                                        <td><?= htmlspecialchars(trim(($cart['billing_city'] ?? '') . ' ' . ($cart['billing_province'] ?? '')) ?: 'Unknown') ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Orders By City / Province</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Area</th><th>Orders</th><th>Revenue</th></tr></thead>
                                <tbody>
                                <?php if (empty($orderCities)): ?>
                                    <tr><td colspan="3">No order location data yet.</td></tr>
                                <?php else: foreach ($orderCities as $area): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($area['city']) ?><br><small><?= htmlspecialchars($area['province']) ?></small></td>
                                        <td><?= number_format((float) $area['orders']) ?></td>
                                        <td><?= cbAdminMoney($area['revenue']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Orders By Part Of Month</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Month part</th><th>Orders</th><th>Revenue</th></tr></thead>
                                <tbody>
                                <?php if (empty($orderMonthParts)): ?>
                                    <tr><td colspan="3">No order timing data yet.</td></tr>
                                <?php else: foreach ($orderMonthParts as $part): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($part['month_part']) ?></td>
                                        <td><?= number_format((float) $part['order_count']) ?></td>
                                        <td><?= cbAdminMoney($part['revenue']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Cart Activity By Part Of Month</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Month part</th><th>Cart actions</th></tr></thead>
                                <tbody>
                                <?php if (empty($cartMonthParts)): ?>
                                    <tr><td colspan="2">No cart timing data yet.</td></tr>
                                <?php else: foreach ($cartMonthParts as $part): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($part['month_part']) ?></td>
                                        <td><?= number_format((float) $part['cart_actions']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Visitor Activity By City</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>City</th><th>Country</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php if (empty($visitorCities)): ?>
                                    <tr><td colspan="3">No geolocation activity yet. The geolocation cron fills this.</td></tr>
                                <?php else: foreach ($visitorCities as $city): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($city['city']) ?></td>
                                        <td><?= htmlspecialchars($city['country']) ?></td>
                                        <td><?= number_format((float) $city['actions']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <h3 class="h5">Recent User Activity</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Visitor</th><th>Action</th><th>When</th></tr></thead>
                                <tbody>
                                <?php if (empty($recentActivity)): ?>
                                    <tr><td colspan="3">No activity recorded yet.</td></tr>
                                <?php else: foreach ($recentActivity as $activity): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($activity['visitor_name'] ?: 'Guest') ?><br><small><?= htmlspecialchars($activity['guest_identifier'] ?: ('User #' . $activity['user_id'])) ?></small></td>
                                        <td><?= htmlspecialchars($activity['action']) ?><br><small><?= htmlspecialchars(substr((string) $activity['details'], 0, 110)) ?></small></td>
                                        <td><?= htmlspecialchars($activity['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
