<?php
date_default_timezone_set('Africa/Johannesburg');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "business_documents";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';
require_once __DIR__ . '/business_ops_helpers.php';
cbOpsEnsureTables($conn);

$message = '';
$messageType = 'success';
$categories = cbOpsDocumentCategories();
$allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'png', 'jpg', 'jpeg', 'webp'];
$maxBytes = 20 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'upload');

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT file_path FROM admin_business_documents WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $file = (string) $row['file_path'];
                $uploadRoot = realpath(cbOpsDocumentUploadDir());
                $fileReal = $file !== '' ? realpath($file) : false;
                $delete = $conn->prepare("DELETE FROM admin_business_documents WHERE id = ?");
                if ($delete) {
                    $delete->bind_param('i', $id);
                    $delete->execute();
                    $delete->close();
                }
                if ($uploadRoot && $fileReal && strpos($fileReal, $uploadRoot) === 0 && is_file($fileReal)) {
                    @unlink($fileReal);
                }
                $message = 'Business document deleted.';
            }
        }
    } else {
        $title = trim((string) ($_POST['title'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? 'Other'));
        $documentDate = trim((string) ($_POST['document_date'] ?? ''));
        $expiryDate = trim((string) ($_POST['expiry_date'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $file = $_FILES['document'] ?? null;

        if ($title === '') {
            $message = 'Please add a document title.';
            $messageType = 'danger';
        } elseif (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $message = 'Please choose a file to upload.';
            $messageType = 'danger';
        } elseif ((int) ($file['size'] ?? 0) > $maxBytes) {
            $message = 'That file is too large. Please upload files smaller than 20MB.';
            $messageType = 'danger';
        } else {
            $originalName = (string) ($file['name'] ?? '');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $message = 'Unsupported file type. Upload PDF, Word, Excel, CSV, or image files.';
                $messageType = 'danger';
            } else {
                $uploadDir = cbOpsDocumentUploadDir();
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($title));
                $safeBase = trim($safeBase, '-') ?: 'business-document';
                $storedName = $safeBase . '-' . date('Ymd-His') . '.' . $extension;
                $destination = $uploadDir . '/' . $storedName;

                if (move_uploaded_file((string) $file['tmp_name'], $destination)) {
                    $mimeType = function_exists('mime_content_type') ? (string) @mime_content_type($destination) : '';
                    $fileSize = (int) filesize($destination);
                    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
                    $docDateSql = $documentDate !== '' ? $documentDate : null;
                    $expDateSql = $expiryDate !== '' ? $expiryDate : null;
                    $stmt = $conn->prepare("INSERT INTO admin_business_documents (title, category, document_date, expiry_date, file_name, file_path, file_size, mime_type, notes, uploaded_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param('ssssssissi', $title, $category, $docDateSql, $expDateSql, $originalName, $destination, $fileSize, $mimeType, $notes, $adminId);
                        $stmt->execute();
                        $stmt->close();
                        $message = 'Business document uploaded.';
                    } else {
                        $message = 'The document could not be saved to the database.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Upload failed. Please try again.';
                    $messageType = 'danger';
                }
            }
        }
    }
}

$selectedCategory = trim((string) ($_GET['category'] ?? ''));
$where = '';
if ($selectedCategory !== '') {
    $safeCategory = $conn->real_escape_string($selectedCategory);
    $where = "WHERE category = '{$safeCategory}'";
}
$documents = cbOpsRows($conn, "SELECT * FROM admin_business_documents {$where} ORDER BY created_at DESC, id DESC");

include 'header.php';
include 'page_menues.php';
?>

<title>Business Documents - Sir Francis Admin</title>

<style>
    .ops-hero { background:var(--sf-navy); border-radius:8px; color:#fff; padding:22px; margin-bottom:18px; }
    .ops-hero h1 { color:var(--sf-gold); font-size:28px; margin:0 0 6px; }
    .ops-card { background:#fff; border:1px solid var(--sf-border); border-radius:8px; padding:18px; margin-bottom:18px; }
    .ops-card h2 { color:#28364B; font-size:20px; margin-bottom:12px; }
    .ops-category-pills { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
    .ops-category-pills a { border:1px solid var(--sf-border); border-radius:999px; color:var(--sf-navy); font-weight:800; padding:7px 12px; text-decoration:none; }
    .ops-category-pills a.active { background:var(--sf-navy); color:#fff; }
    .doc-grid { display:grid; gap:14px; grid-template-columns:repeat(3, minmax(0, 1fr)); }
    .doc-card { border:1px solid #eee0d2; border-radius:8px; padding:14px; }
    .doc-card h3 { color:#251810; font-size:16px; margin-bottom:5px; }
    .doc-meta { color:#75675d; font-size:12px; line-height:1.45; }
    .doc-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
    @media (max-width: 991px) { .doc-grid { grid-template-columns:1fr; } }
</style>

<div class="container" style="padding:32px 0 70px;">
    <div class="ops-hero">
        <h1>Business Documents</h1>
        <p class="mb-0">Store CIPC, SARS, contracts, payment provider paperwork, trademarks and other operating documents in one admin-only place.</p>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= cbOpsText($messageType) ?>"><?= cbOpsText($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4">
            <section class="ops-card">
                <h2>Upload Document</h2>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label>Document title</label>
                        <input type="text" name="title" class="form-control" required placeholder="PayFast merchant agreement">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= cbOpsText($category) ?>"><?= cbOpsText($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Document date</label>
                            <input type="date" name="document_date" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Expiry date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>File</label>
                        <input type="file" name="document" class="form-control-file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.png,.jpg,.jpeg,.webp">
                        <small class="text-muted">PDF, Word, Excel, CSV or images. Max 20MB.</small>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Renewal notes, account numbers, or who to contact."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Upload Document</button>
                </form>
            </section>
        </div>
        <div class="col-lg-8">
            <section class="ops-card">
                <h2>Document Vault</h2>
                <div class="ops-category-pills">
                    <a href="business_documents" class="<?= $selectedCategory === '' ? 'active' : '' ?>">All</a>
                    <?php foreach ($categories as $category): ?>
                        <a href="business_documents?category=<?= urlencode($category) ?>" class="<?= $selectedCategory === $category ? 'active' : '' ?>"><?= cbOpsText($category) ?></a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($documents)): ?>
                    <div class="alert alert-light border">No documents found yet.</div>
                <?php else: ?>
                    <div class="doc-grid">
                        <?php foreach ($documents as $doc): ?>
                            <article class="doc-card">
                                <h3><?= cbOpsText($doc['title']) ?></h3>
                                <div class="doc-meta">
                                    <strong><?= cbOpsText($doc['category']) ?></strong><br>
                                    File: <?= cbOpsText($doc['file_name']) ?><br>
                                    Size: <?= number_format(((int) $doc['file_size']) / 1024, 1) ?> KB<br>
                                    Uploaded: <?= cbOpsText(date('d M Y H:i', strtotime((string) $doc['created_at']))) ?>
                                    <?php if (!empty($doc['expiry_date'])): ?><br>Expires: <?= cbOpsText(date('d M Y', strtotime((string) $doc['expiry_date']))) ?><?php endif; ?>
                                </div>
                                <?php if (!empty($doc['notes'])): ?>
                                    <p class="small mt-2 mb-0"><?= nl2br(cbOpsText($doc['notes'])) ?></p>
                                <?php endif; ?>
                                <div class="doc-actions">
                                    <a href="download_business_document?id=<?= (int) $doc['id'] ?>" class="btn btn-sm btn-outline-primary">Download</a>
                                    <form method="post" onsubmit="return confirm('Delete this business document?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
