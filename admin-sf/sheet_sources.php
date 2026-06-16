<?php
include __DIR__ . '/../session_logins.php';

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "sheets";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

require_once __DIR__ . '/../product_sheet_helpers.php';
require_once __DIR__ . '/../wholesale_pricelist_helpers.php';
include __DIR__ . '/dbh.inc.php';

$message = '';
$success = false;

if (!function_exists('cbSheetAdminText')) {
    function cbSheetAdminText($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cbSheetCacheInfo')) {
    function cbSheetCacheInfo($key) {
        $cacheFile = dirname(__DIR__) . '/sheet_cache/' . preg_replace('/[^a-z0-9_-]/i', '_', $key) . '.tsv';
        if (!is_file($cacheFile)) {
            return ['exists' => false, 'label' => 'No local cache yet.'];
        }

        $ageSeconds = max(0, time() - filemtime($cacheFile));
        if ($ageSeconds < 60) {
            $age = $ageSeconds . ' seconds ago';
        } elseif ($ageSeconds < 3600) {
            $age = floor($ageSeconds / 60) . ' minutes ago';
        } else {
            $age = floor($ageSeconds / 3600) . ' hours ago';
        }

        return ['exists' => true, 'label' => 'Last cached ' . $age, 'file' => $cacheFile];
    }
}

if (!function_exists('cbSheetClearPublicProductCache')) {
    function cbSheetClearPublicProductCache() {
        $cacheFile = dirname(__DIR__) . '/sheet_cache/products_json_with_reviews.json';
        if (is_file($cacheFile)) {
            @unlink($cacheFile);
        }
    }
}

if (!function_exists('cbSheetRefreshSource')) {
    function cbSheetRefreshSource($key) {
        if ($key === 'products') {
            cbSheetClearPublicProductCache();
            return ['ok' => count(getSheetProducts(true)) > 0, 'count' => count(getSheetProducts())];
        }
        if ($key === 'coupons') {
            $items = getSheetCoupons(true);
            return ['ok' => !empty($items), 'count' => count($items)];
        }
        if ($key === 'clearance') {
            cbSheetClearPublicProductCache();
            $items = getSheetClearanceRows(true);
            return ['ok' => true, 'count' => count($items)];
        }
        if ($key === 'wholesale') {
            $items = getCandybirdWholesaleRows(true);
            return ['ok' => true, 'count' => count($items)];
        }
        return ['ok' => false, 'count' => 0];
    }
}

if (!function_exists('cbSheetEnsureCategoryOrderColumn')) {
    function cbSheetEnsureCategoryOrderColumn($conn) {
        if (!($conn instanceof mysqli)) {
            return false;
        }
        $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_website_settings'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return false;
        }
        $columnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'category_display_order'");
        if ($columnCheck && $columnCheck->num_rows === 0) {
            $conn->query("ALTER TABLE admin_website_settings ADD COLUMN category_display_order TEXT NULL");
        }
        return true;
    }
}

if (!function_exists('cbSheetSaveCategoryOrder')) {
    function cbSheetSaveCategoryOrder($conn, $categoryOrder) {
        if (!cbSheetEnsureCategoryOrderColumn($conn)) {
            return false;
        }
        $categoryOrder = trim((string) $categoryOrder);
        $settingsResult = $conn->query("SELECT id FROM admin_website_settings ORDER BY id ASC LIMIT 1");
        if ($settingsResult && ($row = $settingsResult->fetch_assoc())) {
            $id = (int) $row['id'];
            $stmt = $conn->prepare("UPDATE admin_website_settings SET category_display_order = ? WHERE id = ?");
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('si', $categoryOrder, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }

        $stmt = $conn->prepare("INSERT INTO admin_website_settings (category_display_order) VALUES (?)");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $categoryOrder);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

if (!function_exists('cbSheetGetSavedCategoryOrder')) {
    function cbSheetGetSavedCategoryOrder($conn) {
        if (cbSheetEnsureCategoryOrderColumn($conn)) {
            $result = $conn->query("SELECT category_display_order FROM admin_website_settings ORDER BY id ASC LIMIT 1");
            if ($result && ($row = $result->fetch_assoc()) && trim((string) ($row['category_display_order'] ?? '')) !== '') {
                return (string) $row['category_display_order'];
            }
        }
        return implode("\n", getCandybirdCategoryDisplayOrder());
    }
}

if (!function_exists('cbSheetStatusBadge')) {
    function cbSheetStatusBadge($check) {
        if (!empty($check['ok'])) {
            return '<span class="sheet-badge sheet-good">Healthy</span>';
        }
        return '<span class="sheet-badge sheet-bad">Needs attention</span>';
    }
}

$sourceKeys = ['products', 'coupons', 'clearance', 'wholesale'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['sheet_action'] ?? 'save_sources';

    if ($action === 'save_sources') {
        $sources = [
            'products' => [
                'published_url' => $_POST['products_published_url'] ?? '',
                'edit_url' => $_POST['products_edit_url'] ?? '',
            ],
            'coupons' => [
                'published_url' => $_POST['coupons_published_url'] ?? '',
                'edit_url' => $_POST['coupons_edit_url'] ?? '',
            ],
            'clearance' => [
                'published_url' => $_POST['clearance_published_url'] ?? '',
                'edit_url' => $_POST['clearance_edit_url'] ?? '',
            ],
            'wholesale' => [
                'published_url' => $_POST['wholesale_published_url'] ?? '',
                'edit_url' => $_POST['wholesale_edit_url'] ?? '',
            ],
        ];

        if (saveCandybirdSheetSources($sources)) {
            foreach (glob(dirname(__DIR__) . '/sheet_cache/*.tsv') ?: [] as $cacheFile) {
                @unlink($cacheFile);
            }
            cbSheetClearPublicProductCache();
            $success = true;
            $message = 'Sheet links saved. Website sheet caches were cleared so fresh data can load.';
        } else {
            $message = 'The sheet links could not be saved. Check that the sheet_cache folder is writable.';
        }
    } elseif ($action === 'refresh_all') {
        $parts = [];
        $success = true;
        foreach ($sourceKeys as $key) {
            $result = cbSheetRefreshSource($key);
            $success = $success && !empty($result['ok']);
            $parts[] = ucfirst($key) . ': ' . number_format((int) $result['count']);
        }
        $message = 'Mega refresh finished. ' . implode(' | ', $parts);
    } elseif (strpos($action, 'refresh_') === 0) {
        $key = substr($action, 8);
        $result = in_array($key, $sourceKeys, true) ? cbSheetRefreshSource($key) : ['ok' => false, 'count' => 0];
        $success = !empty($result['ok']);
        $message = $success
            ? ucfirst($key) . ' cache refreshed. Rows/groups loaded: ' . number_format((int) $result['count']) . '.'
            : ucfirst($key) . ' could not be refreshed. Please check the TSV link and headers.';
    }
}

$sheetSources = getCandybirdSheetSources();
$health = [];
$cacheInfo = [];
foreach ($sourceKeys as $key) {
    $health[$key] = checkCandybirdSheetHealth($key);
    $cacheInfo[$key] = cbSheetCacheInfo($key);
}

include __DIR__ . '/header.php';
include __DIR__ . '/page_menues.php';
?>

<title>Sheet Sources</title>

<style>
    .sheet-shell { padding: 30px 0 60px; }
    .sheet-hero { background: var(--sf-navy); color: #fff; border-radius: 8px; padding: 24px; margin-bottom: 18px; }
    .sheet-hero h1 { color: var(--sf-gold); margin-bottom: 6px; }
    .sheet-panel { background: #fff; border: 1px solid var(--sf-border); border-radius: 8px; padding: 20px; margin-bottom: 18px; }
    .sheet-panel h2 { color: #28364B; font-size: 21px; margin-bottom: 12px; }
    .sheet-badge { display: inline-block; border-radius: 999px; padding: 5px 10px; font-size: 12px; font-weight: 800; }
    .sheet-good { background: #e3f8e8; color: #186f33; }
    .sheet-bad { background: #ffe4e4; color: #9f1d1d; }
    .header-list { display: flex; flex-wrap: wrap; gap: 6px; padding: 0; margin: 8px 0 0; list-style: none; }
    .header-list li { background: #f6f1ea; border: 1px solid var(--sf-border); border-radius: 999px; padding: 4px 8px; font-size: 12px; }
    .missing-list li { color: #9f1d1d; font-weight: 700; }
    .sheet-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .sheet-source-note { color: #6d6270; font-size: 13px; }
    .sheet-link-grid { display: grid; grid-template-columns: minmax(150px, 180px) 1fr; gap: 10px 14px; align-items: center; }
    .sheet-link-grid code { white-space: normal; word-break: break-all; }
    @media screen and (max-width: 767px) {
        .sheet-link-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="container sheet-shell">
    <div class="sheet-hero">
        <h1>Sheet Sources</h1>
        <p class="mb-0">Manage the Google Sheet links that power products, coupons and the Clearance Basket. Use force refresh after editing a sheet when you want the website to pull fresh data immediately.</p>
        <div class="sheet-actions mt-3">
            <form method="post" action="sheets" class="m-0">
                <input type="hidden" name="sheet_action" value="refresh_all">
                <button class="btn btn-warning" type="submit">Mega-force refresh all sheets</button>
            </form>
            <a class="btn btn-light" href="sheets">Mega Sync All Sheets</a>
            <a class="btn btn-outline-light" href="category_order">Categories</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>"><?= cbSheetAdminText($message) ?></div>
    <?php endif; ?>

    <form method="post" action="sheets">
        <input type="hidden" name="sheet_action" value="save_sources">
        <div class="sheet-panel">
            <h2>Editable Sheet Links</h2>
            <p class="sheet-source-note">Published TSV links are what the website reads. Edit links are for staff/admin buttons.</p>

            <?php foreach ($sourceKeys as $key): ?>
                <?php $source = $sheetSources[$key]; ?>
                <div class="border rounded p-3 mb-3" id="sheet-<?= cbSheetAdminText($key) ?>">
                    <h3 class="h5 mb-3"><?= cbSheetAdminText($source['label']) ?></h3>
                    <div class="form-group">
                        <label>Published TSV URL</label>
                        <input type="url" class="form-control" name="<?= cbSheetAdminText($key) ?>_published_url" value="<?= cbSheetAdminText($source['published_url'] ?? '') ?>" <?= $key === 'clearance' ? '' : 'required' ?>>
                    </div>
                    <div class="form-group">
                        <label>Editable Google Sheet URL</label>
                        <input type="url" class="form-control" name="<?= cbSheetAdminText($key) ?>_edit_url" value="<?= cbSheetAdminText($source['edit_url'] ?? '') ?>" <?= $key === 'clearance' ? '' : 'required' ?>>
                    </div>
                    <div class="sheet-actions">
                        <?php if (!empty($source['edit_url'])): ?>
                            <a class="btn btn-outline-primary btn-sm" href="<?= cbSheetAdminText($source['edit_url']) ?>" target="_blank" rel="noopener noreferrer">Open editable sheet</a>
                        <?php endif; ?>
                        <?php if (!empty($source['published_url'])): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= cbSheetAdminText($source['published_url']) ?>" target="_blank" rel="noopener noreferrer">Open TSV feed</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Save sheet links</button>
        </div>
    </form>

    <div class="row">
        <?php foreach ($sourceKeys as $key): ?>
            <?php $check = $health[$key]; $source = $sheetSources[$key]; ?>
            <div class="col-lg-6 mb-4">
                <div class="sheet-panel h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2" style="gap: 10px;">
                        <h2><?= cbSheetAdminText($source['label']) ?> Health</h2>
                        <?= cbSheetStatusBadge($check) ?>
                    </div>
                    <p><?= cbSheetAdminText($check['message']) ?></p>
                    <p><strong>Valid rows:</strong> <?= number_format((int) ($check['row_count'] ?? 0)) ?></p>
                    <p class="sheet-source-note"><strong>Rows scanned:</strong> <?= number_format((int) ($check['scanned_row_count'] ?? 0)) ?> before validation.</p>
                    <p class="sheet-source-note"><strong>Website cache:</strong> <?= cbSheetAdminText($cacheInfo[$key]['label'] ?? 'Unknown') ?></p>

                    <div class="sheet-link-grid mb-3">
                        <strong>Editable sheet</strong>
                        <span><?php if (!empty($source['edit_url'])): ?><a href="<?= cbSheetAdminText($source['edit_url']) ?>" target="_blank" rel="noopener noreferrer"><?= cbSheetAdminText($source['edit_url']) ?></a><?php else: ?><em>Not set</em><?php endif; ?></span>
                        <strong>Published TSV</strong>
                        <span><?php if (!empty($source['published_url'])): ?><a href="<?= cbSheetAdminText($source['published_url']) ?>" target="_blank" rel="noopener noreferrer"><?= cbSheetAdminText($source['published_url']) ?></a><?php else: ?><em>Not set</em><?php endif; ?></span>
                    </div>

                    <form method="post" action="sheets" class="mb-3">
                        <input type="hidden" name="sheet_action" value="refresh_<?= cbSheetAdminText($key) ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Force refresh <?= cbSheetAdminText(strtolower($source['label'])) ?></button>
                    </form>

                    <?php if (!empty($check['explanation_row_skipped'])): ?>
                        <p class="sheet-source-note">The first explainer/instruction row was skipped correctly.</p>
                    <?php endif; ?>
                    <?php if (!empty($check['stopped_at_row'])): ?>
                        <p class="sheet-source-note"><strong>Stopped at sheet row:</strong> <?= number_format((int) $check['stopped_at_row']) ?> because the first cell contained END/STOP.</p>
                    <?php endif; ?>
                    <?php if ($key === 'products'): ?>
                        <p class="sheet-source-note"><strong>Unique product IDs:</strong> <?= number_format((int) ($check['unique_id_count'] ?? 0)) ?></p>
                        <?php if (!empty($check['duplicate_id_row_count'])): ?>
                            <p class="sheet-source-note"><strong>Duplicate-ID rows:</strong> <?= number_format((int) $check['duplicate_id_row_count']) ?>. The sync mirror can only keep one row per product ID.</p>
                        <?php endif; ?>
                    <?php elseif ($key === 'coupons'): ?>
                        <p class="sheet-source-note"><strong>Phone-restricted coupons:</strong> <?= number_format((int) ($check['coupon_phone_restriction_count'] ?? 0)) ?></p>
                        <p class="sheet-source-note"><strong>Email-restricted coupons:</strong> <?= number_format((int) ($check['coupon_email_restriction_count'] ?? 0)) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($check['ignored_row_count'])): ?>
                        <p class="sheet-source-note"><strong>Ignored blank/incomplete rows:</strong> <?= number_format((int) $check['ignored_row_count']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($check['missing_headers'])): ?>
                        <p class="mb-1"><strong>Missing required headers:</strong></p>
                        <ul class="missing-list">
                            <?php foreach ($check['missing_headers'] as $header): ?>
                                <li><?= cbSheetAdminText($header) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <p class="mb-1"><strong>Required headers:</strong></p>
                    <ul class="header-list">
                        <?php foreach ($source['required_headers'] as $header): ?>
                            <li><?= cbSheetAdminText($header) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!empty($source['optional_headers'])): ?>
                        <p class="mb-1 mt-3"><strong>Supported optional headers:</strong></p>
                        <ul class="header-list">
                            <?php foreach ($source['optional_headers'] as $header): ?>
                                <li><?= cbSheetAdminText($header) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <p class="mb-1 mt-3"><strong>Detected headers:</strong></p>
                    <ul class="header-list">
                        <?php foreach (($check['headers'] ?? []) as $header): ?>
                            <li><?= cbSheetAdminText($header) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
