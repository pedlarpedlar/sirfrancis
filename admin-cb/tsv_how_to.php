<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("tsv_how_to"));
    exit();
}

include __DIR__ . '/header.php';
include __DIR__ . '/page_menues.php';

function cbTsvHelpText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$steps = [
    [
        'title' => 'Open your file with Google Sheets',
        'body' => 'Upload the downloaded template, or your completed spreadsheet file, to Google Drive. Open it with Google Sheets before publishing.',
        'image' => '../assets/img/screenshot_helpers/1.png',
        'alt' => 'Open the template with Google Sheets',
    ],
    [
        'title' => 'Publish to web',
        'body' => 'In Google Sheets, go to File, then Share, then Publish to web. This is different from sharing permissions.',
        'image' => '../assets/img/screenshot_helpers/2.png',
        'alt' => 'Publish to web menu in Google Sheets',
    ],
    [
        'title' => 'Choose current sheet and TSV',
        'body' => 'Select the current sheet only, then choose Tab-separated values (.tsv). Do not publish the whole workbook unless every tab has the same format.',
        'image' => '../assets/img/screenshot_helpers/3.png',
        'alt' => 'Select current sheet and TSV extension',
    ],
    [
        'title' => 'Copy both links into admin',
        'body' => 'Click Publish and copy the generated TSV link into the Published TSV URL box. The normal browser URL of the editable Google Sheet goes into the Editable Google Sheet URL box.',
        'image' => '../assets/img/screenshot_helpers/4.png',
        'alt' => 'Publish and copy the TSV URL',
    ],
];
?>

<title>Google Sheets TSV How-to - CandyBird Admin</title>

<style>
    .tsv-help-wrap { padding: 28px 0 70px; }
    .tsv-help-hero { background:#2d1739; border-radius:8px; color:#fff; margin-bottom:18px; padding:24px; }
    .tsv-help-hero h1 { color:#fcb42f; font-size:30px; margin:0 0 8px; }
    .tsv-help-hero p { color:#f8ecff; line-height:1.65; margin:0; max-width:920px; }
    .tsv-help-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:16px; }
    .tsv-help-grid { display:grid; gap:16px; grid-template-columns:repeat(2, minmax(0, 1fr)); }
    .tsv-help-card { background:#fff; border:1px solid #eadfd2; border-radius:8px; box-shadow:0 12px 30px rgba(45,23,57,.07); overflow:hidden; padding:14px; }
    .tsv-help-shot { background:#f7f1e8; border:1px solid #eadfd2; border-radius:7px; margin-bottom:16px; overflow:hidden; padding:10px; }
    .tsv-help-card img { background:#fff; border-radius:5px; display:block; height:auto; width:100%; }
    .tsv-help-shot-placeholder { background:#f7f1e8; border:1px dashed #d8c7b7; border-radius:7px; color:#6d6270; padding:40px 18px; text-align:center; }
    .tsv-help-card-body { border-top:1px solid #f0e7de; padding:16px 4px 2px; }
    .tsv-help-card-body span { background:#fcb42f; border-radius:999px; color:#2d1739; display:inline-flex; font-size:12px; font-weight:900; margin-bottom:10px; padding:5px 9px; }
    .tsv-help-card-body h2 { color:#5b1178; font-size:20px; margin:0 0 8px; }
    .tsv-help-card-body p { color:#5d514b; line-height:1.6; margin:0; }
    .tsv-help-note { background:#fff7ed; border:1px solid #eadfd2; border-radius:8px; color:#4b3528; margin:18px 0; padding:18px; }
    @media (max-width: 767px) {
        .tsv-help-grid { grid-template-columns:1fr; }
    }
</style>

<div class="container tsv-help-wrap">
    <div class="tsv-help-hero">
        <h1>Publishing Google Sheets as TSV</h1>
        <p>Use this guide whenever you are setting up product, coupon, clearance or wholesale sheets. The website reads the published TSV feed, while staff use the editable Google Sheet URL to open and update the sheet later.</p>
        <div class="tsv-help-actions">
            <a class="btn btn-light" href="products">Products</a>
            <a class="btn btn-light" href="coupons">Coupons</a>
            <a class="btn btn-light" href="clearance">Clearance</a>
            <a class="btn btn-light" href="wholesale_pricelist">Wholesale</a>
        </div>
    </div>

    <div class="tsv-help-note">
        <strong>Important:</strong> the Published TSV URL is the generated link from the Publish to web popup. The Editable Google Sheet URL is the link in your browser address bar while you are editing the sheet.
    </div>

    <div class="tsv-help-grid">
        <?php foreach ($steps as $index => $step): ?>
            <article class="tsv-help-card">
                <?php
                $imagePath = __DIR__ . '/' . $step['image'];
                if (is_file($imagePath)):
                ?>
                    <div class="tsv-help-shot"><img src="<?= cbTsvHelpText($step['image']) ?>" alt="<?= cbTsvHelpText($step['alt']) ?>" loading="lazy"></div>
                <?php else: ?>
                    <div class="tsv-help-shot-placeholder">Screenshot <?= (int) ($index + 1) ?> will show here once uploaded to <code>assets/img/screenshot_helpers</code>.</div>
                <?php endif; ?>
                <div class="tsv-help-card-body">
                    <span>Step <?= (int) ($index + 1) ?></span>
                    <h2><?= cbTsvHelpText($step['title']) ?></h2>
                    <p><?= cbTsvHelpText($step['body']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
