<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode('business_documents'));
    exit();
}

include 'dbh.inc.php';
require_once __DIR__ . '/business_ops_helpers.php';
cbOpsEnsureTables($conn);

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT file_name, file_path, mime_type FROM admin_business_documents WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    exit('Document lookup failed.');
}
$stmt->bind_param('i', $id);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document) {
    http_response_code(404);
    exit('Document not found.');
}

$uploadRoot = realpath(cbOpsDocumentUploadDir());
$file = realpath((string) $document['file_path']);
if (!$uploadRoot || !$file || strpos($file, $uploadRoot) !== 0 || !is_file($file) || !is_readable($file)) {
    http_response_code(404);
    exit('Document file not found.');
}

$fileName = basename((string) $document['file_name']);
$mimeType = (string) ($document['mime_type'] ?: 'application/octet-stream');
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $fileName) . '"');
header('X-Content-Type-Options: nosniff');
readfile($file);
exit();
