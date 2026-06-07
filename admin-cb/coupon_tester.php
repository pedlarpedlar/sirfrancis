<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("coupon_tester"));
    exit();
}

include 'dbh.inc.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

date_default_timezone_set('Africa/Johannesburg');

function cbCtText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbCtMoney($value) {
    return 'R' . number_format((float) $value, 2);
}

function cbCtYes($value) {
    $value = strtolower(trim((string) $value));
    return in_array($value, ['yes', 'y', 'true', '1'], true);
}

function cbCtProductOptions() {
    $products = function_exists('getSheetProductsWithClearance') ? getSheetProductsWithClearance() : getSheetProducts();
    $options = [];
    foreach ($products as $product) {
        if (!is_array($product)) {
            continue;
        }
        $id = trim((string) ($product['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $price = isset($product['price']) ? candybirdParseSheetMoney($product['price']) : 0;
        $finalPrice = getSheetProductPrice($product);
        $categories = array_filter([
            trim((string) ($product['parent_category'] ?? '')),
            trim((string) ($product['child_category_1'] ?? '')),
            trim((string) ($product['child_category_2'] ?? '')),
        ]);
        $label = getSheetProductDisplayTitle($product) . ' - ' . cbCtMoney($finalPrice);
        if ($finalPrice < $price) {
            $label .= ' (was ' . cbCtMoney($price) . ')';
        }
        $options[] = [
            'key' => $id,
            'label' => $label,
            'sort' => strtolower(getSheetProductDisplayTitle($product)),
            'product' => $product,
            'categories' => implode(' > ', $categories),
        ];
    }
    usort($options, function ($a, $b) {
        return strcmp($a['sort'], $b['sort']);
    });
    return $options;
}

function cbCtBuildCartItem($product, $quantity) {
    $price = isset($product['price']) ? candybirdParseSheetMoney($product['price']) : 0;
    $discountedPrice = getSheetProductPrice($product);
    return [
        'id' => $product['id'] ?? '',
        'product_id' => $product['source_product_id'] ?? $product['id'] ?? '',
        'source_product_id' => $product['source_product_id'] ?? $product['id'] ?? '',
        'title' => getSheetProductDisplayTitle($product),
        'parent_category' => $product['parent_category'] ?? '',
        'child_category_1' => $product['child_category_1'] ?? '',
        'child_category_2' => $product['child_category_2'] ?? '',
        'product_type' => $product['product_type'] ?? '',
        'price' => $price,
        'discounted_price' => $discountedPrice,
        'discount_amount' => max(0, $price - $discountedPrice),
        'quantity' => max(1, (int) $quantity),
    ];
}

$productOptions = cbCtProductOptions();
$productMap = [];
foreach ($productOptions as $option) {
    $productMap[$option['key']] = $option['product'];
}

$result = null;
$selectedRows = [];
$couponCode = strtoupper(trim((string) ($_POST['coupon_code'] ?? '')));
$testEmail = trim((string) ($_POST['test_email'] ?? ''));
$testPhone = trim((string) ($_POST['test_phone'] ?? ''));
$testNow = trim((string) ($_POST['test_now'] ?? date('Y-m-d\TH:i')));
$postedProducts = $_POST['product_id'] ?? [];
$postedQty = $_POST['quantity'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartItems = [];
    foreach ((array) $postedProducts as $index => $productId) {
        $productId = trim((string) $productId);
        if ($productId === '' || !isset($productMap[$productId])) {
            continue;
        }
        $quantity = (int) ($postedQty[$index] ?? 1);
        $cartItem = cbCtBuildCartItem($productMap[$productId], $quantity);
        $cartItems[] = $cartItem;
        $selectedRows[] = $cartItem;
    }

    $context = ['conn' => $conn, 'exclude_order_id' => -1];
    if ($testEmail !== '') {
        $context['email'] = $testEmail;
    }
    if ($testPhone !== '') {
        $context['phone'] = $testPhone;
    }
    if ($testNow !== '') {
        $context['now'] = str_replace('T', ' ', $testNow);
    }

    if ($couponCode === '') {
        $result = ['valid' => false, 'message' => 'Enter a coupon code to test.'];
    } elseif (empty($cartItems)) {
        $result = ['valid' => false, 'message' => 'Choose at least one product for the test basket.'];
    } else {
        $result = selectBestSheetCouponForCart($couponCode, $cartItems, $context);
    }
}

include 'header.php';
include 'page_menues.php';
?>

<title>Coupon Tester - CandyBird Admin</title>

<style>
    .coupon-tester-wrap { padding: 28px 0 50px; }
    .coupon-tester-hero { background: #2d1739; color: #fff; border-radius: 8px; padding: 22px; margin-bottom: 18px; }
    .coupon-tester-hero h1 { color: #fcb42f; font-size: 26px; margin: 0 0 6px; }
    .coupon-tester-hero p { color: #f7e9ff; margin: 0; }
    .coupon-panel { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 18px; margin-bottom: 16px; }
    .coupon-product-row { display: grid; grid-template-columns: minmax(0, 1fr) 90px 42px; gap: 8px; margin-bottom: 8px; }
    .coupon-result { border-radius: 8px; padding: 16px; }
    .coupon-result.success { background: #eefaf1; border: 1px solid #b7e3c1; color: #1d6f36; }
    .coupon-result.error { background: #fff4f4; border: 1px solid #edb8b8; color: #982929; }
    .coupon-pill { border-radius: 999px; display: inline-block; font-size: 12px; font-weight: 800; padding: 4px 9px; }
    .coupon-pill.ok { background: #e8f7ed; color: #1d6f36; }
    .coupon-pill.no { background: #fce8e8; color: #9c2727; }
    .coupon-pill.warn { background: #fff4cd; color: #6f5000; }
    .coupon-detail-table td, .coupon-detail-table th { vertical-align: top; }
    @media (max-width: 575px) {
        .coupon-product-row { grid-template-columns: 1fr; }
    }
</style>

<div class="container coupon-tester-wrap">
    <div class="coupon-tester-hero">
        <h1>Coupon Tester</h1>
        <p>Test a coupon against live sheet products exactly like checkout, without creating carts, orders, emails or usage records.</p>
    </div>

    <form method="post" action="coupon_tester">
        <div class="coupon-panel">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="coupon_code">Coupon code</label>
                    <input type="text" class="form-control" id="coupon_code" name="coupon_code" value="<?= cbCtText($couponCode) ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="test_email">Customer email</label>
                    <input type="email" class="form-control" id="test_email" name="test_email" value="<?= cbCtText($testEmail) ?>" placeholder="optional, for email restrictions">
                </div>
                <div class="form-group col-md-4">
                    <label for="test_phone">Customer phone</label>
                    <input type="text" class="form-control" id="test_phone" name="test_phone" value="<?= cbCtText($testPhone) ?>" placeholder="optional, for phone restrictions">
                </div>
            </div>
            <div class="form-group">
                <label for="test_now">Pretend current date/time</label>
                <input type="datetime-local" class="form-control" id="test_now" name="test_now" value="<?= cbCtText($testNow) ?>">
                <small class="text-muted">Use this to test a coupon before it starts or close to expiry.</small>
            </div>
        </div>

        <div class="coupon-panel">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2" style="gap:8px;">
                <h2 class="h5 mb-0">Test basket</h2>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-product-row">Add product</button>
            </div>
            <div id="coupon-products">
                <?php
                $rowCount = max(1, count((array) $postedProducts));
                for ($i = 0; $i < $rowCount; $i++):
                    $postedProductId = trim((string) ($postedProducts[$i] ?? ''));
                    $postedQuantity = max(1, (int) ($postedQty[$i] ?? 1));
                ?>
                    <div class="coupon-product-row">
                        <select class="form-control" name="product_id[]">
                            <option value="">Choose product</option>
                            <?php foreach ($productOptions as $option): ?>
                                <option value="<?= cbCtText($option['key']) ?>" <?= $postedProductId === (string) $option['key'] ? 'selected' : '' ?>>
                                    <?= cbCtText($option['label'] . ($option['categories'] !== '' ? ' | ' . $option['categories'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" class="form-control" name="quantity[]" value="<?= cbCtText($postedQuantity) ?>" min="1" step="1">
                        <button type="button" class="btn btn-outline-danger remove-product-row" title="Remove row">&times;</button>
                    </div>
                <?php endfor; ?>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Test coupon</button>
            <a href="coupons" class="btn btn-outline-dark mt-2">Back to coupons</a>
        </div>
    </form>

    <?php if ($result !== null): ?>
        <?php
        $isValid = !empty($result['valid']);
        $discount = $result['discount'] ?? null;
        $coupon = $result['coupon'] ?? null;
        $cartSubtotal = 0;
        foreach ($selectedRows as $item) {
            $cartSubtotal += (float) $item['price'] * (int) $item['quantity'];
        }
        ?>
        <div class="coupon-result <?= $isValid ? 'success' : 'error' ?>">
            <h2 class="h5 mb-2"><?= $isValid ? 'Coupon would apply successfully' : 'Coupon would not apply' ?></h2>
            <p class="mb-0"><?= cbCtText($result['message'] ?? '') ?></p>
        </div>

        <div class="coupon-panel mt-3">
            <h2 class="h5">Calculation</h2>
            <div class="table-responsive">
                <table class="table table-sm coupon-detail-table">
                    <tbody>
                        <tr><th>Coupon</th><td><?= cbCtText($couponCode) ?></td></tr>
                        <tr><th>Basket before coupon</th><td><?= cbCtMoney($cartSubtotal) ?></td></tr>
                        <tr><th>Eligible amount</th><td><?= cbCtMoney($discount['eligible_amount'] ?? 0) ?></td></tr>
                        <tr><th>Coupon saving</th><td><?= cbCtMoney($discount['coupon_savings'] ?? 0) ?></td></tr>
                        <tr><th>Eligible amount after coupon</th><td><?= cbCtMoney($discount['total_after_coupon'] ?? 0) ?></td></tr>
                        <?php if ($coupon): ?>
                            <tr><th>Discount type</th><td><?= cbCtText($coupon['discount_type'] ?? '') ?> <?= cbCtText($coupon['discount_value'] ?? '') ?></td></tr>
                            <tr><th>Minimum order</th><td><?= cbCtMoney($coupon['min_order_value'] ?? 0) ?></td></tr>
                            <tr><th>Categories</th><td><?= cbCtText($coupon['category_restriction'] ?? $coupon['valid_categories'] ?? 'All') ?></td></tr>
                            <tr><th>Excluded product types</th><td><?= cbCtText($coupon['product_type_exclusion'] ?? $coupon['excluded_product_types'] ?? 'None') ?></td></tr>
                            <tr><th>Sale items allowed</th><td><?= cbCtYes($coupon['valid_on_sale_items'] ?? 'no') ? 'Yes' : 'No' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="coupon-panel">
            <h2 class="h5">Product eligibility</h2>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Product</th><th>Categories</th><th>Type</th><th>Qty</th><th>Price</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($selectedRows as $item):
                        $saleBlocked = $coupon && !cbCtYes($coupon['valid_on_sale_items'] ?? 'no') && ((float) ($item['discounted_price'] ?? 0) < (float) ($item['price'] ?? 0) || (float) ($item['discount_amount'] ?? 0) > 0);
                        $restrictionBlocked = $coupon && !candybirdCouponItemIsEligible($coupon, $item);
                        $eligible = $coupon && !$saleBlocked && !$restrictionBlocked;
                        $categories = implode(' > ', array_filter([$item['parent_category'] ?? '', $item['child_category_1'] ?? '', $item['child_category_2'] ?? '']));
                    ?>
                        <tr>
                            <td><?= cbCtText($item['title']) ?></td>
                            <td><?= cbCtText($categories) ?></td>
                            <td><?= cbCtText($item['product_type'] ?: 'standard') ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td><?= cbCtMoney($item['price']) ?></td>
                            <td>
                                <?php if ($eligible): ?>
                                    <span class="coupon-pill ok">Eligible</span>
                                <?php elseif ($saleBlocked): ?>
                                    <span class="coupon-pill warn">Excluded: sale item</span>
                                <?php elseif ($restrictionBlocked): ?>
                                    <span class="coupon-pill no">Excluded: restriction</span>
                                <?php else: ?>
                                    <span class="coupon-pill no">Not eligible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<template id="coupon-product-row-template">
    <div class="coupon-product-row">
        <select class="form-control" name="product_id[]">
            <option value="">Choose product</option>
            <?php foreach ($productOptions as $option): ?>
                <option value="<?= cbCtText($option['key']) ?>">
                    <?= cbCtText($option['label'] . ($option['categories'] !== '' ? ' | ' . $option['categories'] : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" class="form-control" name="quantity[]" value="1" min="1" step="1">
        <button type="button" class="btn btn-outline-danger remove-product-row" title="Remove row">&times;</button>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var wrap = document.getElementById('coupon-products');
    var template = document.getElementById('coupon-product-row-template');
    document.getElementById('add-product-row').addEventListener('click', function () {
        wrap.appendChild(template.content.cloneNode(true));
    });
    wrap.addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-product-row')) {
            return;
        }
        var rows = wrap.querySelectorAll('.coupon-product-row');
        if (rows.length <= 1) {
            rows[0].querySelector('select').value = '';
            rows[0].querySelector('input').value = '1';
            return;
        }
        event.target.closest('.coupon-product-row').remove();
    });
});
</script>

<?php include '../footer.php'; ?>
