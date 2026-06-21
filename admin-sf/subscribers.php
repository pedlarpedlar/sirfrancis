<?php
include '../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("subscribers"));
    exit();
}

include 'dbh.inc.php';

function sfSubscriberText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sfSubscribersEnsureTable($conn) {
    if (!($conn instanceof mysqli)) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        is_subscribed TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        subscribed_at TIMESTAMP NULL DEFAULT NULL,
        unsubscribed_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY unique_subscriber_email (email)
    )");

    foreach ([
        'subscribed_at' => "ALTER TABLE subscribers ADD COLUMN subscribed_at TIMESTAMP NULL DEFAULT NULL",
        'unsubscribed_at' => "ALTER TABLE subscribers ADD COLUMN unsubscribed_at TIMESTAMP NULL DEFAULT NULL",
    ] as $column => $alterSql) {
        $safeColumn = $conn->real_escape_string($column);
        $check = $conn->query("SHOW COLUMNS FROM subscribers LIKE '{$safeColumn}'");
        if ($check && $check->num_rows === 0) {
            $conn->query($alterSql);
        }
    }
}

function sfSubscriberDate($value) {
    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return 'Not recorded';
    }

    try {
        return (new DateTime($value, new DateTimeZone('Africa/Johannesburg')))->format('d M Y, H:i');
    } catch (Exception $exception) {
        return $value;
    }
}

sfSubscribersEnsureTable($conn);

$flash = $_SESSION['subscriber_admin_flash'] ?? null;
unset($_SESSION['subscriber_admin_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $subscriberId = (int) ($_POST['subscriber_id'] ?? 0);
        if ($subscriberId > 0) {
            $stmt = $conn->prepare("DELETE FROM subscribers WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $subscriberId);
                $stmt->execute();
                $deleted = $stmt->affected_rows > 0;
                $stmt->close();
                $_SESSION['subscriber_admin_flash'] = [
                    'success' => $deleted,
                    'message' => $deleted ? 'Subscriber deleted.' : 'Subscriber was not found.',
                ];
            } else {
                $_SESSION['subscriber_admin_flash'] = [
                    'success' => false,
                    'message' => 'Subscriber could not be deleted.',
                ];
            }
        } else {
            $_SESSION['subscriber_admin_flash'] = [
                'success' => false,
                'message' => 'Subscriber could not be identified.',
            ];
        }

        $redirectQuery = [];
        foreach (['status', 'q'] as $key) {
            if (isset($_POST[$key]) && trim((string) $_POST[$key]) !== '') {
                $redirectQuery[$key] = trim((string) $_POST[$key]);
            }
        }
        header('Location: subscribers' . ($redirectQuery ? '?' . http_build_query($redirectQuery) : ''));
        exit();
    }
}

$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'active', 'unsubscribed'], true)) {
    $status = 'all';
}

$search = trim((string) ($_GET['q'] ?? ''));
$where = [];
if ($status === 'active') {
    $where[] = 'is_subscribed = 1';
} elseif ($status === 'unsubscribed') {
    $where[] = 'is_subscribed = 0';
}
if ($search !== '') {
    $where[] = "email LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$stats = ['active' => 0, 'unsubscribed' => 0, 'total' => 0];
$statsResult = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN is_subscribed = 1 THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN is_subscribed = 0 THEN 1 ELSE 0 END) AS unsubscribed FROM subscribers");
if ($statsResult && ($statsRow = $statsResult->fetch_assoc())) {
    $stats['total'] = (int) ($statsRow['total'] ?? 0);
    $stats['active'] = (int) ($statsRow['active'] ?? 0);
    $stats['unsubscribed'] = (int) ($statsRow['unsubscribed'] ?? 0);
}

$rows = [];
$sql = "SELECT id, email, is_subscribed, created_at, subscribed_at, unsubscribed_at
        FROM subscribers
        {$whereSql}
        ORDER BY COALESCE(unsubscribed_at, subscribed_at, created_at) DESC, id DESC
        LIMIT 500";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

include 'header.php';
include 'page_menues.php';
?>

<title>Subscribers - Sir Francis Admin</title>

<style>
    .subscribers-wrap { padding: 28px 0 70px; }
    .subscribers-hero { background: var(--sf-navy); border-radius: 0; color: #fff; padding: 22px; }
    .subscribers-hero h2 { color: var(--sf-gold); margin-bottom: 6px; }
    .subscribers-stats { display: grid; gap: 12px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 18px 0; }
    .subscriber-stat { background: #fff; border: 1px solid #e8ded2; border-radius: 0; padding: 16px; }
    .subscriber-stat strong { color: var(--sf-navy); display: block; font-size: 28px; line-height: 1; }
    .subscriber-stat span { color: #70695f; font-weight: 700; text-transform: uppercase; }
    .subscribers-card { background: #fff; border: 1px solid #e8ded2; border-radius: 0; padding: 18px; }
    .subscriber-filters { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; margin-bottom: 14px; }
    .subscriber-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
    .subscriber-tabs a,
    .subscriber-search button { background: #172235; border: 0; border-radius: 0; color: #fff; display: inline-block; font-weight: 800; padding: 9px 13px; text-decoration: none; }
    .subscriber-tabs a.active { background: #CEBD88; color: #172235; }
    .subscriber-search { display: flex; gap: 8px; }
    .subscriber-search input { border: 1px solid #d8c895; border-radius: 0; min-width: 260px; padding: 9px 11px; }
    .subscriber-delete-form { margin: 0; }
    .subscriber-delete-btn { background: #8a1f1f; border: 0; border-radius: 0; color: #fff; font-weight: 800; padding: 7px 10px; }
    .subscriber-delete-btn:hover { background: #5f1111; color: #fff; }
    .subscriber-pill { border-radius: 999px; display: inline-block; font-size: 12px; font-weight: 900; padding: 5px 9px; text-transform: uppercase; }
    .subscriber-pill.active { background: #e8f5e9; color: #1b5e20; }
    .subscriber-pill.unsubscribed { background: #fff3e0; color: #8a4b00; }
    .subscribers-table th { color: var(--sf-navy); white-space: nowrap; }
    .subscribers-muted { color: #756f66; font-size: 13px; }
    @media (max-width: 767px) {
        .subscribers-stats { grid-template-columns: 1fr; }
        .subscriber-search,
        .subscriber-search input { width: 100%; }
    }
</style>

<main class="container subscribers-wrap">
    <section class="subscribers-hero">
        <h2>Subscribers</h2>
        <p class="mb-0">View active subscribers, unsubscribed addresses and the recorded subscription dates for broadcast compliance.</p>
    </section>

    <?php if ($flash): ?>
        <div class="alert <?= !empty($flash['success']) ? 'alert-success' : 'alert-danger' ?> mt-3">
            <?= sfSubscriberText($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <section class="subscribers-stats" aria-label="Subscriber totals">
        <div class="subscriber-stat"><strong><?= number_format($stats['active']) ?></strong><span>Subscribed</span></div>
        <div class="subscriber-stat"><strong><?= number_format($stats['unsubscribed']) ?></strong><span>Unsubscribed</span></div>
        <div class="subscriber-stat"><strong><?= number_format($stats['total']) ?></strong><span>Total records</span></div>
    </section>

    <section class="subscribers-card">
        <div class="subscriber-filters">
            <nav class="subscriber-tabs" aria-label="Subscriber filters">
                <a href="subscribers" class="<?= $status === 'all' ? 'active' : '' ?>">All</a>
                <a href="subscribers?status=active" class="<?= $status === 'active' ? 'active' : '' ?>">Subscribed</a>
                <a href="subscribers?status=unsubscribed" class="<?= $status === 'unsubscribed' ? 'active' : '' ?>">Unsubscribed</a>
            </nav>
            <form class="subscriber-search" method="get" action="subscribers">
                <input type="hidden" name="status" value="<?= sfSubscriberText($status) ?>">
                <input type="search" name="q" value="<?= sfSubscriberText($search) ?>" placeholder="Search email address">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table subscribers-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Subscribed Date</th>
                        <th>Unsubscribed Date</th>
                        <th>First Seen</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6">No subscriber records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $isActive = (int) $row['is_subscribed'] === 1;
                            $subscribedDate = $row['subscribed_at'] ?: $row['created_at'];
                            $unsubscribedDate = $row['unsubscribed_at'] ?: (!$isActive ? $row['created_at'] : '');
                        ?>
                        <tr>
                            <td>
                                <strong><?= sfSubscriberText($row['email']) ?></strong>
                                <div class="subscribers-muted">ID #<?= (int) $row['id'] ?></div>
                            </td>
                            <td><span class="subscriber-pill <?= $isActive ? 'active' : 'unsubscribed' ?>"><?= $isActive ? 'Subscribed' : 'Unsubscribed' ?></span></td>
                            <td><?= sfSubscriberText(sfSubscriberDate($subscribedDate)) ?></td>
                            <td><?= sfSubscriberText(sfSubscriberDate($unsubscribedDate)) ?></td>
                            <td><?= sfSubscriberText(sfSubscriberDate($row['created_at'])) ?></td>
                            <td>
                                <form class="subscriber-delete-form" method="post" action="subscribers" onsubmit="return confirm('Delete this subscriber from the list? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="subscriber_id" value="<?= (int) $row['id'] ?>">
                                    <input type="hidden" name="status" value="<?= sfSubscriberText($status) ?>">
                                    <input type="hidden" name="q" value="<?= sfSubscriberText($search) ?>">
                                    <button type="submit" class="subscriber-delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="subscribers-muted mb-0">Showing up to 500 most recent records. Older records created before this update may use First Seen as the fallback date if an exact subscribe or unsubscribe timestamp was not previously stored.</p>
    </section>
</main>

<?php include 'footer.php'; ?>
