<?php
date_default_timezone_set('Africa/Johannesburg');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin login required.']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use POST to update images.']);
    exit;
}

require_once __DIR__ . '/site_image_helpers.php';

$key = preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string) ($_POST['image_key'] ?? ''));
if ($key === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image key is missing.']);
    exit;
}

if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Choose an image file first.']);
    exit;
}

$file = $_FILES['image'];
if ((int) ($file['size'] ?? 0) > 8 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image is too large. Maximum size is 8MB.']);
    exit;
}

$info = @getimagesize($file['tmp_name']);
if (!$info || empty($info['mime'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'The uploaded file is not a valid image.']);
    exit;
}

$extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

$extension = $extensions[$info['mime']] ?? '';
if ($extension === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, WEBP and GIF images are supported.']);
    exit;
}

$uploadDir = __DIR__ . '/assets/img/site-overrides';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload folder is not writable.']);
    exit;
}

$filename = $key . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
$target = $uploadDir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save uploaded image.']);
    exit;
}

$relativePath = 'assets/img/site-overrides/' . $filename;
$overrides = sfSiteImageOverrides();
$overrides[$key] = [
    'path' => $relativePath,
    'updated_at' => date('c'),
    'updated_by_admin_id' => (int) $_SESSION['admin_id'],
];

$overrideFile = sfSiteImageOverridesFile();
$overrideDir = dirname($overrideFile);
if (!is_dir($overrideDir)) {
    @mkdir($overrideDir, 0755, true);
}

if (!(bool) @file_put_contents($overrideFile, json_encode($overrides, JSON_PRETTY_PRINT), LOCK_EX)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Image uploaded, but the override file could not be saved.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Image updated.',
    'path' => $relativePath,
    'key' => $key,
]);
