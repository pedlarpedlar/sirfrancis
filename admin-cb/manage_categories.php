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

function cbManageCategorySaveDisplay($conn, $items) {
    if (!cbManageCategoryEnsureDisplayColumns($conn)) {
        return false;
    }
    $payload = json_encode(['items' => array_values($items)]);
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
    $items = [];

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
        return ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999);
    });

    $categorySuccess = cbManageCategorySaveDisplay($conn ?? null, $items);
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
foreach ($products as $product) {
    $parent = trim((string) ($product['parent_category'] ?? ''));
    if ($parent !== '') {
        $sourceCategories[$parent] = true;
    }
}

$displayMap = getCandybirdCategoryDisplayMap();
$displayRows = [];
foreach (array_keys($sourceCategories) as $name) {
    $displayRows[$name] = [
        'name' => $name,
        'label' => $displayMap[$name]['label'] ?? $name,
        'position' => $displayMap[$name]['position'] ?? 9999,
        'visible' => !isset($displayMap[$name]) || !empty($displayMap[$name]['visible']),
    ];
}
foreach ($displayMap as $name => $item) {
    if (!isset($displayRows[$name])) {
        $displayRows[$name] = [
            'name' => $name,
            'label' => $item['label'] ?? $name,
            'position' => $item['position'] ?? 9999,
            'visible' => !empty($item['visible']),
            'missing_from_sheet' => true,
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

include 'header.php';
?>

<title>Manage Categories</title>

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
            <h1>Manage Categories</h1>
            <p class="mb-0">Control how sheet categories display on the website. Google Sheets remain the source of truth, so a sheet sync/fetch can bring category names back if products still use them.</p>
        </div>

        <?php if ($categoryMessage): ?>
            <div class="alert <?= $categorySuccess ? 'alert-success' : 'alert-danger' ?>"><?= cbManageCategoryText($categoryMessage) ?></div>
        <?php endif; ?>

        <div class="category-admin-panel">
            <h2>Website Category Display</h2>
            <p class="category-muted">Untick Show to remove a category from the public menu and products sidebar. Edit Website label to rename it publicly without changing the sheet. Change Order to place categories first or last.</p>
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
                                    <td><input type="number" class="form-control" name="category_position[<?= $i ?>]" value="<?= cbManageCategoryText($row['position'] === 9999 ? ($i + 1) : $row['position']) ?>"></td>
                                </tr>
                            <?php $i++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Save category display</button>
            </form>
        </div>

        <div class="category-admin-panel">
            <h2>Legacy Database Categories</h2>
            <p class="category-muted">These older database categories are kept for compatibility. Website product categories now come from the Google product sheet, so sheet data can override this list.</p>
        <div class="container-md d-flex justify-content-center align-items-center">
            <div class="row">

                <!-- Display existing categories -->
                <div class="col-lg-6 mx-auto">
                    <div class="mb-4">
                        <h2>Existing Categories</h2>
                        <select id="category-list" class="form-select mt-2" size="11">
                            <option value="" disabled>No categories found!</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-lg-6 mx-auto">
                    <h2>Add New Category</h2>
                    <div class="mb-4"></div>
                    <form action="manage_categories.inc.php" method="post" id="submit-form">
                        <!-- Product Information -->
                        <input type="hidden" name="c_id" id="c_id" value="">

                        <div class="mb-3">
                            <label for="c_name" class="form-label">Category Name:</label>
                            <input type="text" class="form-control" name="c_name" id="c_name" required>
                        </div>


                        <div class="mb-3">
                            <label for="c_parent" class="form-label">Parent Category ID (optional):</label>
                            <input type="text" class="form-control" name="c_parent" id="c_parent">
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submit-button" class="btn btn-primary">Submit</button>

                    </form>
                </div>

            </div>
        </div>
        </div>
    </div>

<?php
include '../footer.php';
?>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>


// Function to display categories in the list
function displayCategories(categories) {
    const categoryList = $('#category-list');

    // Clear existing list items
    categoryList.empty();

    const static_option = $('<option>')
            .val('')
            .text('Select a Category')
            .data('parent-id', ''); // Add parent ID as a data attribute
    categoryList.append(static_option);

    // Iterate over the categories and append list items to the ul
    for (const category of categories) {
        // Example: Append category names to the ul as list items in breadcrumb style
        const breadcrumb = getCategoryBreadcrumb(category, categories);
        const option = $('<option>')
            .val(category.id)
            .text('(' + category.id + ') ' + breadcrumb)
            .data('parent-id', category.parent_id); // Add parent ID as a data attribute
        categoryList.append(option);
    }
}

function getCategoryBreadcrumb(category, categories) {
    const breadcrumbItems = [];
    let currentCategory = category;

    while (currentCategory) {
        breadcrumbItems.unshift(currentCategory.name); // Add to the beginning of the array
        currentCategory = getParentCategory(currentCategory.parent_id, categories);
    }

    return breadcrumbItems.join(' > ');
}

function getParentCategory(parentId, categories) {
    return categories.find(category => category.id === parentId);
}


// Wrap the code in a document ready function
$(document).ready(function() {


    // Add change event listener to the select element
    $('#category-list').change(function () {
        // Get the selected option
        const selectedOption = $(this).find('option:selected');

        // Update input with id "c_id" with the selected category's ID
        $('#c_id').val(selectedOption.val());

        // Get the last item in the breadcrumb
        const breadcrumbText = selectedOption.text();
        const lastItem = breadcrumbText.substring(breadcrumbText.lastIndexOf('>') + 1).trim();

        // Update input with id "c_name" with the selected category's name (last item)
        $('#c_name').val(lastItem);

        // Update input with id "c_parent" with the selected category's parent ID
        const parentCategoryId = selectedOption.data('parent-id');
        $('#c_parent').val(parentCategoryId);

        // Change the button text to "Update"
        $('#submit-button').text('Update');
    });



    $.ajax({
        url: 'fetch_categories.php', // Replace with your server-side script to get categories
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            // Check if the response is an array and not an error message
            if (Array.isArray(response)) {
                displayCategories(response);
            } else {

            }
        },
        error: function (xhr, status, error) {
            console.error(xhr.responseText);
            console.error('An unexpected error occurred');
        }
    });

     // Function to handle form submission
    $('#submit-form').submit(function(event) {
        // Prevent the default form submission
        event.preventDefault();

        // Serialize the form data
        var formData = $(this).serialize();

        // Make an AJAX request to submit the form
        $.ajax({
            url: 'manage_categories.inc.php',
            type: 'POST',
            data: formData,
            dataType: 'json', // Assuming the response is in JSON format
            success: function(response) {
                // Handle the success response
                console.log(response);
                // You can handle the response as needed, e.g., show a success message
                alert(response.message);
            },
            error: function(xhr, status, error) {
                // Handle the error response
                console.error(xhr.responseText);
                // You can display an error message to the user
                alert(xhr.responseText);
            }
        });
    });

    $('#c_parent').autocomplete({
    source: function(request, response) {
        $.ajax({
            url: 'fetch_categories.php', // Replace with the path to your PHP script
            type: 'POST',
            dataType: 'json',
            data: {
                term: request.term
            },
            success: function(data) {
                response($.map(data, function(item) {
                    // Create a hierarchy representation for display
                    var displayText = item.name;
                    if (item.parent_id) {
                        displayText = getHierarchyRepresentation(data, item);
                    }

                    return {
                        label: displayText,
                        value: item.id
                    };
                }));
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    },
    minLength: 1,
    select: function(event, ui) {
        console.log('Selected ID:', ui.item.value);
    }
});

    // Helper function to get the hierarchy representation
    function getHierarchyRepresentation(categories, currentItem) {
        var hierarchy = currentItem.name;

        var parentId = currentItem.parent_id;
        while (parentId) {
            var parentItem = categories.find(item => item.id === parentId);
            if (parentItem) {
                hierarchy = parentItem.name + ' > ' + hierarchy;
                parentId = parentItem.parent_id;
            } else {
                parentId = null; // Stop if parent not found
            }
        }

        return hierarchy;
    }

});

</script>
