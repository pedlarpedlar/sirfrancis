<?php
date_default_timezone_set('Africa/Johannesburg');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (PHP_SAPI !== 'cli') {
    include '../session_logins.php';
    if (!isset($_SESSION['admin_id'])) {
        $redirect_url = "sync_sheet_products";
        header("Location: admin_login?redirect=" . urlencode($redirect_url));
        exit();
    }
}

include 'dbh.inc.php';
require_once __DIR__ . '/../product_sheet_helpers.php';

function candybirdSendSheetSyncAdminEmail($summary, $sourceLabel) {
    global $conn, $smtp_server, $smtp_port, $smtp_type, $smtp_username1, $smtp_username5, $smtp_password5, $support_email, $website_company_name;

    $recipient = $support_email ?: ($smtp_username1 ?? '');
    if (empty($recipient) && isset($conn) && $conn instanceof mysqli) {
        $settings = $conn->query("SELECT support_email, email_1, website_company_name FROM admin_website_settings LIMIT 1");
        if ($settings && ($row = $settings->fetch_assoc())) {
            $recipient = trim((string) ($row['support_email'] ?: $row['email_1']));
            $website_company_name = $website_company_name ?: ($row['website_company_name'] ?? 'CandyBird');
        }
    }

    if (empty($recipient) || empty($smtp_server) || empty($smtp_username5) || empty($smtp_password5)) {
        error_log('CandyBird sheet sync email skipped: SMTP or support recipient missing.');
        return false;
    }

    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/SMTP.php';

    $success = !empty($summary['success']) && (int) ($summary['failed'] ?? 0) === 0 && empty($summary['errors']);
    $statusText = $success ? 'Successful' : 'Needs Attention';
    $subject = 'CandyBird sheet sync ' . strtolower($statusText) . ' - ' . date('Y-m-d H:i');
    $errors = array_slice($summary['errors'] ?? [], 0, 25);

    $rows = [
        'Source' => $sourceLabel,
        'Status' => $statusText,
        'Valid sheet rows' => number_format((float) ($summary['sheet_rows'] ?? $summary['processed'] ?? 0)),
        'Unique sheet IDs' => number_format((float) ($summary['unique_sheet_ids'] ?? $summary['processed'] ?? 0)),
        'Synced' => number_format((float) ($summary['synced'] ?? 0)),
        'Failed' => number_format((float) ($summary['failed'] ?? 0)),
        'Disabled missing products' => number_format((float) ($summary['disabled_missing'] ?? 0)),
        'Duplicate-ID rows collapsed' => number_format((float) ($summary['duplicate_id_row_count'] ?? 0)),
        'New high-discount alerts' => number_format((float) ($summary['high_discount_alert']['count'] ?? 0)),
        'High-discount alert email sent' => !empty($summary['high_discount_alert']['email_sent']) ? 'Yes' : 'No',
    ];

    $body = '<div style="font-family:Arial,sans-serif;color:#2c2926;line-height:1.55;">'
        . '<h2 style="margin:0 0 12px;color:#5b1178;">CandyBird Sheet Product Sync</h2>'
        . '<p>The product sheet sync finished with status: <strong>' . htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;border:1px solid #eadfd2;width:100%;max-width:680px;">';

    foreach ($rows as $label => $value) {
        $body .= '<tr><th align="left" style="border:1px solid #eadfd2;background:#fff7ed;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</th><td style="border:1px solid #eadfd2;">' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }
    $body .= '</table>';

    if (!empty($summary['duplicate_ids'])) {
        $body .= '<p><strong>Duplicate IDs:</strong> ' . htmlspecialchars(implode(', ', array_slice($summary['duplicate_ids'], 0, 30)), ENT_QUOTES, 'UTF-8') . '</p>';
    }

    if (!empty($errors)) {
        $body .= '<h3 style="color:#b42318;">Errors / warnings</h3><ul>';
        foreach ($errors as $error) {
            $body .= '<li>' . htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $body .= '</ul>';
    }

    $body .= '<p style="color:#6d6270;font-size:13px;">This email is sent automatically whenever sync_sheet_products.php runs.</p></div>';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username5;
        $mail->Password = $smtp_password5;
        if (!empty($smtp_type)) {
            $mail->SMTPSecure = $smtp_type;
        }
        $mail->Port = (int) ($smtp_port ?? 587);
        $mail->setFrom($smtp_username5, $website_company_name ?: 'CandyBird');
        $mail->addAddress($recipient, 'CandyBird Support');
        if (!empty($smtp_username1) && $smtp_username1 !== $recipient) {
            $mail->addReplyTo($smtp_username1, 'CandyBird');
        }
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = "CandyBird sheet sync {$statusText}\n"
            . "Source: {$sourceLabel}\n"
            . "Synced: " . ($summary['synced'] ?? 0) . "\n"
            . "Failed: " . ($summary['failed'] ?? 0) . "\n"
            . (!empty($errors) ? "Errors:\n- " . implode("\n- ", $errors) : '');
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('CandyBird sheet sync admin email failed: ' . $e->getMessage());
        return false;
    }
}

function candybirdEnsureDiscountAlertTable($conn) {
    if (!($conn instanceof mysqli) || $conn->connect_error) {
        return false;
    }
    return $conn->query("CREATE TABLE IF NOT EXISTS product_discount_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        alert_hash VARCHAR(64) NOT NULL,
        discount_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
        product_name VARCHAR(255) NULL,
        normal_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        discounted_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        alerted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY product_discount_alert_once (product_id, alert_hash)
    )") !== false;
}

function candybirdAdminAlertRecipient() {
    global $conn, $support_email, $smtp_username1, $website_company_name;

    $recipient = $support_email ?: ($smtp_username1 ?? '');
    if (isset($conn) && $conn instanceof mysqli) {
        $settings = $conn->query("SELECT support_email, email_1, website_company_name FROM admin_website_settings LIMIT 1");
        if ($settings && ($row = $settings->fetch_assoc())) {
            $recipient = trim((string) ($row['support_email'] ?: $row['email_1'] ?: $recipient));
            $website_company_name = $website_company_name ?: ($row['website_company_name'] ?? 'CandyBird');
        }
    }

    return $recipient;
}

function candybirdSendHighDiscountAlertEmail($alerts, $sourceLabel) {
    global $smtp_server, $smtp_port, $smtp_type, $smtp_username1, $smtp_username5, $smtp_password5, $website_company_name;

    if (empty($alerts)) {
        return false;
    }

    $recipient = candybirdAdminAlertRecipient();
    if (empty($recipient) || empty($smtp_server) || empty($smtp_username5) || empty($smtp_password5)) {
        error_log('CandyBird high discount alert skipped: SMTP or recipient missing.');
        return false;
    }

    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer/src/SMTP.php';

    $rows = '';
    foreach ($alerts as $alert) {
        $rows .= '<tr>'
            . '<td style="border:1px solid #eadfd2;padding:8px;">' . htmlspecialchars((string) $alert['id'], ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="border:1px solid #eadfd2;padding:8px;"><a href="https://www.candybird.co.za/product?id=' . urlencode((string) $alert['id']) . '">' . htmlspecialchars((string) $alert['title'], ENT_QUOTES, 'UTF-8') . '</a></td>'
            . '<td style="border:1px solid #eadfd2;padding:8px;">R' . number_format((float) $alert['normal_price'], 2) . '</td>'
            . '<td style="border:1px solid #eadfd2;padding:8px;">R' . number_format((float) $alert['discounted_price'], 2) . '</td>'
            . '<td style="border:1px solid #eadfd2;padding:8px;color:#b42318;font-weight:700;">' . number_format((float) $alert['discount_percent'], 2) . '%</td>'
            . '</tr>';
    }

    $body = '<div style="font-family:Arial,sans-serif;color:#2c2926;line-height:1.55;">'
        . '<h2 style="margin:0 0 12px;color:#b42318;">CandyBird High Discount Alert</h2>'
        . '<p>The product sheet sync found product discounts higher than 35%. This email is sent once per product and discount value so accidental specials can be caught quickly.</p>'
        . '<p><strong>Source:</strong> ' . htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #eadfd2;width:100%;max-width:780px;">'
        . '<thead><tr style="background:#fff7ed;"><th style="border:1px solid #eadfd2;padding:8px;text-align:left;">ID</th><th style="border:1px solid #eadfd2;padding:8px;text-align:left;">Product</th><th style="border:1px solid #eadfd2;padding:8px;text-align:left;">Normal</th><th style="border:1px solid #eadfd2;padding:8px;text-align:left;">Discounted</th><th style="border:1px solid #eadfd2;padding:8px;text-align:left;">Discount</th></tr></thead>'
        . '<tbody>' . $rows . '</tbody></table>'
        . '<p style="color:#6d6270;font-size:13px;">If this discount is intentional, no action is needed. The same product and discount will not alert again unless the discount changes.</p>'
        . '</div>';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username5;
        $mail->Password = $smtp_password5;
        if (!empty($smtp_type)) {
            $mail->SMTPSecure = $smtp_type;
        }
        $mail->Port = (int) ($smtp_port ?? 587);
        $mail->setFrom($smtp_username5, $website_company_name ?: 'CandyBird');
        $mail->addAddress($recipient, 'CandyBird Admin');
        if (!empty($smtp_username1) && $smtp_username1 !== $recipient) {
            $mail->addReplyTo($smtp_username1, 'CandyBird');
        }
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'CandyBird high discount alert - ' . count($alerts) . ' product(s)';
        $mail->Body = $body;
        $mail->AltBody = "CandyBird high discount alert\n\n" . implode("\n", array_map(static function ($alert) {
            return '#' . $alert['id'] . ' ' . $alert['title'] . ' - ' . number_format((float) $alert['discount_percent'], 2) . '% discount';
        }, $alerts));
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('CandyBird high discount alert email failed: ' . $e->getMessage());
        return false;
    }
}

function candybirdCheckHighDiscountAlerts($conn, $sourceLabel) {
    if (!candybirdEnsureDiscountAlertTable($conn)) {
        return ['count' => 0, 'email_sent' => false];
    }

    $newAlerts = [];
    foreach (getSheetProducts(false) as $product) {
        $product = normalizeCandybirdProductSpecial($product);
        $productId = (int) ($product['id'] ?? 0);
        $normalPrice = isset($product['price']) ? (float) $product['price'] : 0;
        $discountedPrice = getSheetProductPrice($product);
        if ($productId <= 0 || $normalPrice <= 0 || $discountedPrice <= 0 || $discountedPrice >= $normalPrice) {
            continue;
        }

        $discountPercent = (($normalPrice - $discountedPrice) / $normalPrice) * 100;
        if ($discountPercent <= 35) {
            continue;
        }

        $hash = hash('sha256', implode('|', [
            $productId,
            number_format($normalPrice, 2, '.', ''),
            number_format($discountedPrice, 2, '.', ''),
            number_format($discountPercent, 2, '.', ''),
            trim((string) ($product['discount_valid_from'] ?? '')),
            trim((string) ($product['discount_valid_until'] ?? '')),
        ]));

        $title = getSheetProductDisplayTitle($product);
        $stmt = $conn->prepare("INSERT IGNORE INTO product_discount_alerts (product_id, alert_hash, discount_percent, product_name, normal_price, discounted_price) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param("isdsdd", $productId, $hash, $discountPercent, $title, $normalPrice, $discountedPrice);
        $stmt->execute();
        $inserted = $stmt->affected_rows > 0;
        $stmt->close();

        if ($inserted) {
            $newAlerts[] = [
                'id' => $productId,
                'title' => $title,
                'normal_price' => $normalPrice,
                'discounted_price' => $discountedPrice,
                'discount_percent' => $discountPercent,
            ];
        }
    }

    return [
        'count' => count($newAlerts),
        'email_sent' => !empty($newAlerts) ? candybirdSendHighDiscountAlertEmail($newAlerts, $sourceLabel) : false,
    ];
}

$forceRefresh = true;
$disableMissing = true;
if (PHP_SAPI === 'cli') {
    $disableMissing = !in_array('--keep-old-active', $argv ?? [], true);
    $forceRefresh = !in_array('--use-cache', $argv ?? [], true);
} else {
    $disableMissing = !isset($_POST['keep_old_active']);
}

$summary = syncSheetProductsMirrorToDb($conn, $disableMissing, $forceRefresh);
foreach ([
    __DIR__ . '/../sheet_cache/products_json_with_reviews.json',
    __DIR__ . '/../sheet_cache/homepage_products.json',
] as $cacheFile) {
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
}

$sourceLabel = PHP_SAPI === 'cli' ? 'cron/CLI' : 'admin page';
$summary['high_discount_alert'] = candybirdCheckHighDiscountAlerts($conn, $sourceLabel);
$summary['admin_email_sent'] = candybirdSendSheetSyncAdminEmail($summary, $sourceLabel);

if (PHP_SAPI === 'cli') {
    echo json_encode($summary, JSON_PRETTY_PRINT) . PHP_EOL;
    exit($summary['success'] ? 0 : 1);
}

include 'header.php';
include 'page_menues.php';
?>

<title>Sync Sheet Products</title>

<div class="container" style="padding: 40px 0;">
    <div class="row">
        <div class="col-lg-8">
            <h2>Sheet Product Sync</h2>
            <div class="alert <?= $summary['success'] ? 'alert-success' : 'alert-warning' ?>">
                <strong><?= $summary['success'] ? 'Sync complete.' : 'Sync completed with warnings.' ?></strong>
                <?= number_format($summary['synced']) ?> unique sheet product IDs were mirrored into the old product table.
                Products not in the sheet were <?= $summary['authoritative'] ? 'disabled' : 'left unchanged' ?>.
                The product sheet cache was refreshed from Google first.
            </div>

            <ul class="list-group mb-4">
                <li class="list-group-item d-flex justify-content-between"><span>Valid sheet rows</span><strong><?= number_format($summary['sheet_rows'] ?? $summary['processed']) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Unique sheet IDs</span><strong><?= number_format($summary['unique_sheet_ids'] ?? $summary['processed']) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Duplicate-ID rows collapsed</span><strong><?= number_format($summary['duplicate_id_row_count'] ?? 0) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Processed mirror rows</span><strong><?= number_format($summary['processed']) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Synced</span><strong><?= number_format($summary['synced']) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Failed</span><strong><?= number_format($summary['failed']) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Disabled missing products</span><strong><?= number_format($summary['disabled_missing']) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>New high-discount alerts</span><strong><?= number_format((float)($summary['high_discount_alert']['count'] ?? 0)) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>High-discount alert email sent</span><strong><?= !empty($summary['high_discount_alert']['email_sent']) ? 'Yes' : 'No' ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Admin email sent</span><strong><?= !empty($summary['admin_email_sent']) ? 'Yes' : 'No' ?></strong></li>
            </ul>

            <?php if (!empty($summary['duplicate_ids'])): ?>
                <div class="alert alert-info">
                    <strong>Duplicate product IDs found:</strong>
                    <?= htmlspecialchars(implode(', ', $summary['duplicate_ids']), ENT_QUOTES, 'UTF-8') ?>
                    <?php if (($summary['duplicate_id_row_count'] ?? 0) > count($summary['duplicate_ids'])): ?>
                        and more.
                    <?php endif; ?>
                    Each product ID can only exist once in the database mirror.
                </div>
            <?php endif; ?>

            <?php if (!empty($summary['errors'])): ?>
                <div class="alert alert-danger">
                    <strong>Errors</strong>
                    <ul>
                        <?php foreach (array_slice($summary['errors'], 0, 20) as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="sync_sheet_products" class="mb-3">
                <button type="submit" class="btn btn-primary">Run sync again</button>
                <label style="margin-left: 14px;">
                    <input type="checkbox" name="keep_old_active" value="1">
                    Keep old database products active even if missing from the sheet
                </label>
            </form>

            <p class="text-muted">The Google Sheet remains the product source of truth. This sync overwrites mirror rows by sheet ID and disables old database products that are no longer in the sheet, while preserving order, cart, review, wishlist and compare relationships.</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
