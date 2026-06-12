<?php
$categoryPageSlug = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'manage_categories.php'), '.php') === 'category_order' ? 'category_order' : 'manage_categories';

// Start or resume the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = $categoryPageSlug;
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}
// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

require_once __DIR__ . '/../product_sheet_helpers.php';
include __DIR__ . '/dbh.inc.php';

function cbManageCategoryText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbManageCategoryEnsureDisplayColumns($conn) {
    if (!($conn instanceof mysqli)) {
        return false;
    }
    $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_website_settings'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return false;
    }
    $columns = [
        'category_display_config' => "ALTER TABLE admin_website_settings ADD COLUMN category_display_config LONGTEXT NULL",
        'category_display_order' => "ALTER TABLE admin_website_settings ADD COLUMN category_display_order TEXT NULL",
    ];
    foreach ($columns as $column => $alterSql) {
        $columnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE '" . $conn->real_escape_string($column) . "'");
        if ($columnCheck && $columnCheck->num_rows === 0) {
            $conn->query($alterSql);
        }
    }
    return true;
}

function cbManageCategorySaveDisplay($conn, $items, $paths = null) {
    if (!cbManageCategoryEnsureDisplayColumns($conn)) {
        return false;
    }
    $existingConfig = function_exists('getCandybirdCategoryDisplayConfig') ? getCandybirdCategoryDisplayConfig() : ['items' => []];
    $payloadData = is_array($existingConfig) ? $existingConfig : ['items' => []];
    $payloadData['items'] = array_values($items);
    if (is_array($paths)) {
        $payloadData['paths'] = array_values($paths);
    }
    $payload = json_encode($payloadData);
    if ($payload === false) {
        return false;
    }
    $orderText = implode("\n", array_map(static function($item) { return $item['name']; }, $items));
    $settingsResult = $conn->query("SELECT id FROM admin_website_settings ORDER BY id ASC");
    if ($settingsResult && $settingsResult->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE admin_website_settings SET category_display_config = ?, category_display_order = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $payload, $orderText);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) {
            return false;
        }
        $verify = $conn->query("SELECT category_display_config FROM admin_website_settings ORDER BY id ASC LIMIT 1");
        if (!$verify || !($row = $verify->fetch_assoc())) {
            return false;
        }
        $decoded = json_decode((string) ($row['category_display_config'] ?? ''), true);
        return is_array($decoded) && json_encode($decoded['items'] ?? []) === json_encode(array_values($items));
    }

    $stmt = $conn->prepare("INSERT INTO admin_website_settings (category_display_config, category_display_order) VALUES (?, ?)");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $payload, $orderText);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        return false;
    }
    $verify = $conn->query("SELECT category_display_config FROM admin_website_settings ORDER BY id ASC LIMIT 1");
    if (!$verify || !($row = $verify->fetch_assoc())) {
        return false;
    }
    $decoded = json_decode((string) ($row['category_display_config'] ?? ''), true);
    return is_array($decoded) && json_encode($decoded['items'] ?? []) === json_encode(array_values($items));
}

$categoryMessage = '';
$categorySuccess = false;
if (isset($_GET['category_saved'])) {
    $categorySuccess = $_GET['category_saved'] === '1';
    $categoryMessage = $categorySuccess ? 'Category display settings saved.' : 'Category display settings could not be saved.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['category_action'] ?? '') === 'save_display_categories') {
    $names = $_POST['category_name'] ?? [];
    $labels = $_POST['category_label'] ?? [];
    $positions = $_POST['category_position'] ?? [];
    $visible = $_POST['category_visible'] ?? [];
    $pathKeys = $_POST['category_path'] ?? [];
    $pathLabels = $_POST['category_path_label'] ?? [];
    $pathPositions = $_POST['category_path_position'] ?? [];
    $pathVisible = $_POST['category_path_visible'] ?? [];
    $items = [];
    $paths = [];

    foreach ($names as $index => $name) {
        $name = trim((string) $name);
        if ($name === '') {
            continue;
        }
        $items[] = [
            'name' => $name,
            'label' => trim((string) ($labels[$index] ?? $name)) ?: $name,
            'position' => is_numeric($positions[$index] ?? null) ? (int) $positions[$index] : ($index + 1),
            'visible' => isset($visible[$index]),
        ];
    }

    usort($items, static function($a, $b) {
        $posCompare = ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999);
        return $posCompare !== 0 ? $posCompare : strnatcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
    foreach ($items as $itemIndex => $item) {
        $items[$itemIndex]['position'] = $itemIndex + 1;
    }

    $submittedPaths = is_array($pathKeys) && count($pathKeys) > 0;
    if ($submittedPaths) {
        foreach ($pathKeys as $index => $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            $paths[] = [
                'path' => $path,
                'label' => trim((string) ($pathLabels[$index] ?? $path)) ?: $path,
                'position' => is_numeric($pathPositions[$index] ?? null) ? (int) $pathPositions[$index] : ($index + 1),
                'visible' => isset($pathVisible[$index]),
            ];
        }

        usort($paths, static function($a, $b) {
            $posCompare = ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999);
            return $posCompare !== 0 ? $posCompare : strnatcasecmp($a['path'] ?? '', $b['path'] ?? '');
        });
        foreach ($paths as $pathIndex => $path) {
            $paths[$pathIndex]['position'] = $pathIndex + 1;
        }
    }

    $categorySuccess = cbManageCategorySaveDisplay($conn ?? null, $items, $submittedPaths ? $paths : null);
    $categoryMessage = $categorySuccess ? 'Category display settings saved.' : 'Category display settings could not be saved.';
    if ($categorySuccess) {
        $publicProductCache = dirname(__DIR__) . '/sheet_cache/products_json_with_reviews.json';
        if (is_file($publicProductCache)) {
            @unlink($publicProductCache);
        }
    }
    header('Location: ' . $categoryPageSlug . '?category_saved=' . ($categorySuccess ? '1' : '0'));
    exit;
}

$products = function_exists('getSheetProductsWithClearance') ? getSheetProductsWithClearance() : getSheetProducts();
$sourceCategories = [];
$sourceCategoryPaths = [];
foreach ($products as $product) {
    $parent = trim((string) ($product['parent_category'] ?? ''));
    if ($parent !== '') {
        $sourceCategories[$parent] = true;
    }
    $parts = [];
    foreach (['parent_category', 'child_category_1', 'child_category_2'] as $field) {
        $value = trim((string) ($product[$field] ?? ''));
        if ($value !== '' && !in_array($value, $parts, true)) {
            $parts[] = $value;
        }
    }
    if (!empty($parts)) {
        $sourceCategoryPaths[implode(' > ', $parts)] = true;
    }
}

$displayConfig = getCandybirdCategoryDisplayConfig();
$displayMap = getCandybirdCategoryDisplayMap();
$displayRows = [];
foreach ($displayMap as $name => $item) {
    $displayRows[$name] = [
        'name' => $name,
        'label' => $item['label'] ?? $name,
        'position' => $item['position'] ?? 9999,
        'visible' => !empty($item['visible']),
        'missing_from_sheet' => !isset($sourceCategories[$name]),
    ];
}
foreach (array_keys($sourceCategories) as $name) {
    if (!isset($displayRows[$name])) {
        $displayRows[$name] = [
            'name' => $name,
            'label' => $name,
            'position' => 9999,
            'visible' => true,
        ];
    }
}
uasort($displayRows, static function($a, $b) {
    $posA = $a['position'] ?? 9999;
    $posB = $b['position'] ?? 9999;
    if ($posA === $posB) {
        return strnatcasecmp($a['name'], $b['name']);
    }
    return $posA <=> $posB;
});
$rowPosition = 1;
foreach ($displayRows as $name => $row) {
    $displayRows[$name]['display_position'] = $rowPosition++;
}

$savedPathRows = [];
foreach (($displayConfig['paths'] ?? []) as $pathItem) {
    $path = trim((string) ($pathItem['path'] ?? ''));
    if ($path === '') {
        continue;
    }
    $savedPathRows[$path] = [
        'path' => $path,
        'label' => trim((string) ($pathItem['label'] ?? $path)) ?: $path,
        'position' => isset($pathItem['position']) ? (int) $pathItem['position'] : 9999,
        'visible' => !array_key_exists('visible', $pathItem) || filter_var($pathItem['visible'], FILTER_VALIDATE_BOOLEAN),
        'missing_from_sheet' => !isset($sourceCategoryPaths[$path]),
    ];
}
foreach (array_keys($sourceCategoryPaths) as $path) {
    if (!isset($savedPathRows[$path])) {
        $savedPathRows[$path] = [
            'path' => $path,
            'label' => $path,
            'position' => 9999,
            'visible' => true,
        ];
    }
}
uasort($savedPathRows, static function($a, $b) {
    $posA = $a['position'] ?? 9999;
    $posB = $b['position'] ?? 9999;
    if ($posA === $posB) {
        return strnatcasecmp($a['path'], $b['path']);
    }
    return $posA <=> $posB;
});
$pathPosition = 1;
foreach ($savedPathRows as $path => $row) {
    $savedPathRows[$path]['display_position'] = $pathPosition++;
}

include 'header.php';
?>

<title>Categories - CandyBird Admin</title>

<style>
#category-list {
    max-height: 250px;
    overflow-y: auto;
}
.category-admin-shell { padding: 26px 0 70px; }
.category-admin-hero { background: #2d1739; color: #fff; border-radius: 8px; padding: 22px; margin-bottom: 18px; }
.category-admin-hero h1 { color: #fcb42f; margin-bottom: 6px; }
.category-admin-panel { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 18px; margin-bottom: 18px; }
.category-display-table { width: 100%; border-collapse: collapse; }
.category-display-table th, .category-display-table td { border-bottom: 1px solid #f0e7de; padding: 10px 8px; vertical-align: middle; }
.category-display-table th { color: #5b1178; font-size: 13px; text-transform: uppercase; }
.category-display-table input[type="number"] { max-width: 90px; }
.category-muted { color: #75675d; font-size: 13px; }
.category-admin-panel h3 { color:#5b1178; font-size:19px; margin:20px 0 8px; }
@media (max-width: 767px) {
    .category-display-table, .category-display-table tbody, .category-display-table tr, .category-display-table td { display: block; width: 100%; }
    .category-display-table thead { display: none; }
    .category-display-table tr { border: 1px solid #eadfd2; border-radius: 8px; margin-bottom: 12px; padding: 8px; }
    .category-display-table td { border-bottom: 0; padding: 6px 0; }
}
</style>


<?php
include 'page_menues.php';
?>

    <div class="container category-admin-shell text-md-start">
        <div class="category-admin-hero">
            <h1>Categories</h1>
            <p class="mb-0">Control how sheet categories display on the website. Google Sheets remain the source of truth, so a sheet sync/fetch can bring category names back if products still use them.</p>
        </div>

        <?php if ($categoryMessage): ?>
            <div class="alert <?= $categorySuccess ? 'alert-success' : 'alert-danger' ?>"><?= cbManageCategoryText($categoryMessage) ?></div>
        <?php endif; ?>

        <div class="category-admin-panel">
            <h2>Website Category Display</h2>
            <p class="category-muted">Untick Show to remove a category from the public menu, product filters, and pricelist. Edit Website label to rename it publicly without changing the sheet. Change Order to place categories first or last. Product categories themselves still come from the Google product and clearance sheets.</p>
            <form method="post" action="<?= cbManageCategoryText($categoryPageSlug) ?>">
                <input type="hidden" name="category_action" value="save_display_categories">
                <div class="table-responsive">
                    <table class="category-display-table">
                        <thead>
                            <tr>
                                <th>Show</th>
                                <th>Sheet category</th>
                                <th>Website label</th>
                                <th>Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 0; foreach ($displayRows as $row): ?>
                                <tr>
                                    <td><input type="checkbox" name="category_visible[<?= $i ?>]" <?= !empty($row['visible']) ? 'checked' : '' ?>></td>
                                    <td>
                                        <strong><?= cbManageCategoryText($row['name']) ?></strong>
                                        <?php if (!empty($row['missing_from_sheet'])): ?>
                                            <div class="category-muted">Not found in current sheet.</div>
                                        <?php endif; ?>
                                        <input type="hidden" name="category_name[<?= $i ?>]" value="<?= cbManageCategoryText($row['name']) ?>">
                                    </td>
                                    <td><input type="text" class="form-control" name="category_label[<?= $i ?>]" value="<?= cbManageCategoryText($row['label']) ?>"></td>
                                    <td><input type="number" class="form-control" name="category_position[<?= $i ?>]" value="<?= cbManageCategoryText($row['display_position'] ?? ($i + 1)) ?>"></td>
                                </tr>
                            <?php $i++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Save category display</button>
            </form>
        </div>

        <div class="category-admin-panel">
            <h2>Pricelist Category Order</h2>
            <p class="category-muted">This controls how grouped pricelist sections appear, including child categories. Example: put plain/raw nuts first, then salted/roasted, then caramelized/coated, then dried fruit, sweets, gifting and other sections. Sheet syncs can add new category paths; new ones appear at the bottom until you order them here.</p>
            <form method="post" action="<?= cbManageCategoryText($categoryPageSlug) ?>">
                <input type="hidden" name="category_action" value="save_display_categories">
                <?php $i = 0; foreach ($displayRows as $row): ?>
                    <input type="hidden" name="category_name[<?= $i ?>]" value="<?= cbManageCategoryText($row['name']) ?>">
                    <input type="hidden" name="category_label[<?= $i ?>]" value="<?= cbManageCategoryText($row['label']) ?>">
                    <input type="hidden" name="category_position[<?= $i ?>]" value="<?= cbManageCategoryText($row['display_position'] ?? ($i + 1)) ?>">
                    <?php if (!empty($row['visible'])): ?><input type="hidden" name="category_visible[<?= $i ?>]" value="1"><?php endif; ?>
                <?php $i++; endforeach; ?>
                <div class="table-responsive">
                    <table class="category-display-table">
                        <thead>
                            <tr>
                                <th>Show</th>
                                <th>Sheet category path</th>
                                <th>Pricelist label</th>
                                <th>Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $p = 0; foreach ($savedPathRows as $row): ?>
                                <tr>
                                    <td><input type="checkbox" name="category_path_visible[<?= $p ?>]" <?= !empty($row['visible']) ? 'checked' : '' ?>></td>
                                    <td>
                                        <strong><?= cbManageCategoryText($row['path']) ?></strong>
                                        <?php if (!empty($row['missing_from_sheet'])): ?>
                                            <div class="category-muted">Not found in current sheet.</div>
                                        <?php endif; ?>
                                        <input type="hidden" name="category_path[<?= $p ?>]" value="<?= cbManageCategoryText($row['path']) ?>">
                                    </td>
                                    <td><input type="text" class="form-control" name="category_path_label[<?= $p ?>]" value="<?= cbManageCategoryText($row['label']) ?>"></td>
                                    <td><input type="number" class="form-control" name="category_path_position[<?= $p ?>]" value="<?= cbManageCategoryText($row['display_position'] ?? ($p + 1)) ?>"></td>
                                </tr>
                            <?php $p++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Save pricelist order</button>
            </form>
        </div>
    </div>

<?php
include '../footer.php';
?>
