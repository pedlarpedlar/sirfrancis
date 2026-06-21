<?php
include '../session_logins.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please log in as admin.']);
    exit;
}

function gallery_response($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function gallery_root()
{
    $root = realpath(__DIR__ . '/../assets/img');
    if (!$root || !is_dir($root)) {
        gallery_response(['success' => false, 'message' => 'assets/img folder was not found.'], 500);
    }
    return $root;
}

function gallery_default_product_folder()
{
    return 'product';
}

function gallery_clean_folder($folder)
{
    $folder = trim(str_replace('\\', '/', (string)$folder), '/');
    $folder = preg_replace('#^(assets/)?img/?#i', '', $folder);
    $folder = trim($folder, '/');
    $parts = array_filter(explode('/', $folder), function ($part) {
        return $part !== '' && $part !== '.' && $part !== '..';
    });
    $safeParts = [];
    foreach ($parts as $part) {
        $safe = preg_replace('/[^A-Za-z0-9._ -]/', '-', $part);
        $safe = trim(preg_replace('/\s+/', ' ', $safe));
        if ($safe !== '') {
            $safeParts[] = $safe;
        }
    }
    return implode('/', $safeParts);
}

function gallery_folder_path($folder, $create = false)
{
    $root = gallery_root();
    $folder = gallery_clean_folder($folder);
    $path = $folder === '' ? $root : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
    if ($create && !is_dir($path) && !mkdir($path, 0755, true)) {
        gallery_response(['success' => false, 'message' => 'Could not create the selected folder.'], 500);
    }
    $real = realpath($path);
    if (!$real || strpos($real, $root) !== 0 || !is_dir($real)) {
        gallery_response(['success' => false, 'message' => 'Invalid image folder.'], 400);
    }
    return $real;
}

function gallery_file_path($relativePath)
{
    $root = gallery_root();
    $relativePath = trim(str_replace('\\', '/', (string)$relativePath), '/');
    if ($relativePath === '' || strpos($relativePath, '..') !== false) {
        gallery_response(['success' => false, 'message' => 'Invalid image path.'], 400);
    }
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $real = realpath($path);
    if (!$real || strpos($real, $root) !== 0 || !is_file($real)) {
        gallery_response(['success' => false, 'message' => 'Image was not found.'], 404);
    }
    return $real;
}

function gallery_relative($path)
{
    $root = gallery_root();
    return trim(str_replace('\\', '/', substr($path, strlen($root))), '/');
}

function gallery_asset_url($relativePath)
{
    $parts = array_map('rawurlencode', explode('/', str_replace('\\', '/', $relativePath)));
    return 'https://sirfrancis.co.za/assets/img/' . implode('/', $parts);
}

function gallery_safe_name($name)
{
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = preg_replace('/[^A-Za-z0-9._ -]/', '-', $base);
    $base = trim(preg_replace('/\s+/', '-', $base), '.-_ ');
    return $base !== '' ? $base : 'image';
}

function gallery_unique_path($folderPath, $baseName, $extension)
{
    $extension = strtolower($extension);
    $candidate = $folderPath . DIRECTORY_SEPARATOR . $baseName . '.' . $extension;
    $count = 2;
    while (file_exists($candidate)) {
        $candidate = $folderPath . DIRECTORY_SEPARATOR . $baseName . '-' . $count . '.' . $extension;
        $count++;
    }
    return $candidate;
}

function gallery_bool($value)
{
    return in_array(strtolower(trim((string)$value)), ['1', 'yes', 'true', 'on'], true);
}

function gallery_write_compressed_image($extension, $target, $path, $quality)
{
    if ($extension === 'jpg' || $extension === 'jpeg') {
        return imagejpeg($target, $path, $quality);
    }
    if ($extension === 'png') {
        $pngLevel = max(6, min(9, (int)round((100 - $quality) / 100 * 9)));
        return imagepng($target, $path, $pngLevel);
    }
    return imagewebp($target, $path, $quality);
}

function gallery_make_resampled_image($source, $extension, $width, $height, $newWidth, $newHeight)
{
    $target = imagecreatetruecolor($newWidth, $newHeight);
    if (!$target) {
        return false;
    }

    if ($extension === 'png' || $extension === 'webp') {
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    return $target;
}

function gallery_compress_image($path, $targetKb = 400)
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['success' => false, 'message' => 'Compression skipped for this file type.'];
    }
    if (!function_exists('imagecreatetruecolor')) {
        return ['success' => false, 'message' => 'PHP GD image compression is not available on this server.'];
    }

    $info = @getimagesize($path);
    if (!$info || empty($info[0]) || empty($info[1])) {
        return ['success' => false, 'message' => 'Could not read image dimensions.'];
    }

    $targetBytes = max(120, min(1200, (int)$targetKb)) * 1024;
    $width = (int)$info[0];
    $height = (int)$info[1];

    if ($extension === 'jpg' || $extension === 'jpeg') {
        if (!function_exists('imagecreatefromjpeg')) {
            return ['success' => false, 'message' => 'JPEG compression is not available on this server.'];
        }
        $source = @imagecreatefromjpeg($path);
    } elseif ($extension === 'png') {
        if (!function_exists('imagecreatefrompng')) {
            return ['success' => false, 'message' => 'PNG compression is not available on this server.'];
        }
        $source = @imagecreatefrompng($path);
    } else {
        if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) {
            return ['success' => false, 'message' => 'WebP compression is not available on this server.'];
        }
        $source = @imagecreatefromwebp($path);
    }

    if (!$source) {
        return ['success' => false, 'message' => 'Could not open image for compression.'];
    }

    $before = filesize($path);
    $saved = false;
    $finalWidth = $width;
    $finalHeight = $height;
    $scale = 1.0;

    foreach ([82, 76, 70, 64, 58, 52, 46, 40] as $quality) {
        $newWidth = max(1, (int)round($width * $scale));
        $newHeight = max(1, (int)round($height * $scale));
        $target = gallery_make_resampled_image($source, $extension, $width, $height, $newWidth, $newHeight);
        if (!$target) {
            continue;
        }
        $saved = gallery_write_compressed_image($extension, $target, $path, $quality);
        imagedestroy($target);
        clearstatcache(true, $path);
        $finalWidth = $newWidth;
        $finalHeight = $newHeight;
        if (!$saved || filesize($path) <= $targetBytes || max($newWidth, $newHeight) <= 900) {
            break;
        }
        $scale *= 0.88;
    }

    imagedestroy($source);
    clearstatcache(true, $path);

    if (!$saved) {
        return ['success' => false, 'message' => 'Compressed image could not be saved.'];
    }

    return [
        'success' => true,
        'before' => $before,
        'after' => filesize($path),
        'width' => $finalWidth,
        'height' => $finalHeight,
    ];
}

function gallery_is_image($path)
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true);
}

function gallery_image_payload($path)
{
    $relative = gallery_relative($path);
    return [
        'name' => basename($path),
        'relative_path' => $relative,
        'folder' => trim(dirname($relative), '.') === '' ? '' : str_replace('\\', '/', dirname($relative)),
        'url' => gallery_asset_url($relative),
        'size' => filesize($path),
        'modified' => filemtime($path),
    ];
}

function gallery_cleanup_protected_folder($relativePath)
{
    $relativePath = trim(str_replace('\\', '/', (string)$relativePath), '/');
    $firstFolder = strtolower(strtok($relativePath, '/') ?: $relativePath);
    return in_array($firstFolder, ['product', 'products', 'product_images', 'product-images'], true);
}

function gallery_cleanup_scan_extensions()
{
    return ['php', 'js', 'css', 'html', 'htm', 'json', 'txt', 'csv', 'tsv', 'xml', 'yml', 'yaml', 'md', 'map'];
}

function gallery_cleanup_reference_haystack()
{
    $siteRoot = realpath(__DIR__ . '/..');
    $assetsRoot = gallery_root();
    if (!$siteRoot || !is_dir($siteRoot)) {
        return '';
    }

    $chunks = [];
    $skipDirs = ['.git', 'node_modules', 'vendor', 'PHPMailer'];
    $extensions = gallery_cleanup_scan_extensions();
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($siteRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }

        $path = $item->getPathname();
        $normalizedPath = str_replace('\\', '/', $path);
        foreach ($skipDirs as $skipDir) {
            if (stripos($normalizedPath, '/' . $skipDir . '/') !== false) {
                continue 2;
            }
        }

        $realPath = realpath($path);
        if ($realPath && strpos($realPath, $assetsRoot . DIRECTORY_SEPARATOR) === 0) {
            continue;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $basename = strtolower(basename($path));
        if (!in_array($extension, $extensions, true) && !in_array($basename, ['.htaccess'], true)) {
            continue;
        }

        if ($item->getSize() > 5 * 1024 * 1024) {
            continue;
        }

        $contents = @file_get_contents($path);
        if (is_string($contents) && $contents !== '') {
            $chunks[] = strtolower(rawurldecode($contents));
        }
    }

    return implode("\n", $chunks);
}

function gallery_cleanup_reference_variants($relativePath)
{
    $relativePath = trim(str_replace('\\', '/', (string)$relativePath), '/');
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $relativePath)));
    return array_values(array_unique([
        strtolower($relativePath),
        strtolower($encodedPath),
        strtolower('assets/img/' . $relativePath),
        strtolower('/assets/img/' . $relativePath),
        strtolower('../assets/img/' . $relativePath),
        strtolower('assets/img/' . $encodedPath),
        strtolower('/assets/img/' . $encodedPath),
        strtolower('https://sirfrancis.co.za/assets/img/' . $relativePath),
        strtolower('https://www.sirfrancis.co.za/assets/img/' . $relativePath),
        strtolower('https://sirfrancis.co.za/assets/img/' . $encodedPath),
        strtolower('https://www.sirfrancis.co.za/assets/img/' . $encodedPath),
    ]));
}

function gallery_cleanup_image_is_referenced($relativePath, $haystack)
{
    $relativePath = trim(str_replace('\\', '/', (string)$relativePath), '/');
    $haystack = (string)$haystack;
    foreach (gallery_cleanup_reference_variants($relativePath) as $needle) {
        if ($needle !== '' && strpos($haystack, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function gallery_cleanup_candidates()
{
    $root = gallery_root();
    $haystack = gallery_cleanup_reference_haystack();
    $candidates = [];
    $protectedCount = 0;
    $referencedCount = 0;
    $scannedCount = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $item) {
        if (!$item->isFile() || !gallery_is_image($item->getPathname())) {
            continue;
        }

        $scannedCount++;
        $relativePath = gallery_relative($item->getPathname());
        if (gallery_cleanup_protected_folder($relativePath)) {
            $protectedCount++;
            continue;
        }

        if (gallery_cleanup_image_is_referenced($relativePath, $haystack)) {
            $referencedCount++;
            continue;
        }

        $candidates[] = gallery_image_payload($item->getPathname());
    }

    usort($candidates, function ($a, $b) {
        return ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0);
    });

    return [
        'images' => $candidates,
        'scanned' => $scannedCount,
        'protected' => $protectedCount,
        'referenced' => $referencedCount,
    ];
}

function gallery_folders()
{
    $root = gallery_root();
    $folders = [''];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            $folders[] = gallery_relative($item->getPathname());
        }
    }
    sort($folders, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values(array_unique($folders));
}

function gallery_list_images($folder, $offset, $limit, $query = '')
{
    $root = gallery_root();
    $files = [];
    if ($folder === '__all__') {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile() && gallery_is_image($item->getPathname())) {
                $files[] = $item->getPathname();
            }
        }
    } else {
        $folderPath = gallery_folder_path($folder);
        foreach (new DirectoryIterator($folderPath) as $item) {
            if ($item->isFile() && gallery_is_image($item->getPathname())) {
                $files[] = $item->getPathname();
            }
        }
    }

    $query = strtolower(trim((string)$query));
    if ($query !== '') {
        $tokens = preg_split('/\s+/', $query);
        $files = array_values(array_filter($files, function ($path) use ($tokens) {
            $haystack = strtolower(gallery_relative($path));
            foreach ($tokens as $token) {
                if ($token !== '' && strpos($haystack, $token) === false) {
                    return false;
                }
            }
            return true;
        }));
    }

    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });

    $total = count($files);
    $slice = array_slice($files, $offset, $limit);
    return [
        'images' => array_map('gallery_image_payload', $slice),
        'total' => $total,
        'next_offset' => $offset + count($slice),
        'has_more' => ($offset + count($slice)) < $total,
    ];
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'folders') {
    gallery_folder_path(gallery_default_product_folder(), true);
    gallery_response([
        'success' => true,
        'folders' => gallery_folders(),
        'can_compress' => function_exists('imagecreatetruecolor'),
    ]);
}

if ($action === 'list') {
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $limit = min(60, max(12, (int)($_GET['limit'] ?? 24)));
    gallery_response(['success' => true] + gallery_list_images($_GET['folder'] ?? '__all__', $offset, $limit, $_GET['q'] ?? ''));
}

if ($action === 'cleanup_preview') {
    gallery_response(['success' => true] + gallery_cleanup_candidates());
}

if ($action === 'upload') {
    $newFolder = trim((string)($_POST['new_folder'] ?? ''));
    $folder = $newFolder !== '' ? $newFolder : ($_POST['folder'] ?? gallery_default_product_folder());
    $folderPath = gallery_folder_path($folder, true);
    $files = $_FILES['images'] ?? null;
    if (!$files || empty($files['name'])) {
        gallery_response(['success' => false, 'message' => 'Choose at least one image to upload.'], 400);
    }

    $uploaded = [];
    $errors = [];
    $compressionNotes = [];
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $customNames = $_POST['image_names'] ?? [];
    $compressUploads = gallery_bool($_POST['compress_images'] ?? '');
    $compressTargetKb = (int)($_POST['compress_target_kb'] ?? 400);
    foreach ((array)$files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = $name . ' could not upload.';
            continue;
        }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed, true)) {
            $errors[] = $name . ' is not an allowed image type.';
            continue;
        }
        if (!@getimagesize($files['tmp_name'][$index])) {
            $errors[] = $name . ' is not a valid image.';
            continue;
        }
        $customName = is_array($customNames) ? ($customNames[$index] ?? '') : '';
        $target = gallery_unique_path($folderPath, gallery_safe_name($customName !== '' ? $customName : $name), $extension);
        if (!move_uploaded_file($files['tmp_name'][$index], $target)) {
            $errors[] = $name . ' could not be saved.';
            continue;
        }
        if ($compressUploads) {
            $compression = gallery_compress_image($target, $compressTargetKb);
            if (empty($compression['success'])) {
                $compressionNotes[] = basename($target) . ': ' . ($compression['message'] ?? 'Compression skipped.');
            } else {
                $savedBytes = max(0, (int)$compression['before'] - (int)$compression['after']);
                $afterKb = round(((int)$compression['after']) / 1024, 1);
                $targetNote = ((int)$compression['after'] > max(120, min(1200, $compressTargetKb)) * 1024)
                    ? ' - still larger than target, kept quality reasonable'
                    : '';
                $compressionNotes[] = basename($target) . ': saved server file is ' . $afterKb . ' KB' . ($savedBytes > 0 ? ' (saved ' . round($savedBytes / 1024, 1) . ' KB)' : '') . $targetNote;
            }
        }
        $uploaded[] = gallery_image_payload($target);
    }

    gallery_response([
        'success' => count($uploaded) > 0,
        'message' => count($uploaded) . ' image(s) uploaded.',
        'uploaded' => $uploaded,
        'errors' => $errors,
        'compression_notes' => $compressionNotes,
        'folders' => gallery_folders(),
    ], count($uploaded) > 0 ? 200 : 400);
}

if ($action === 'rename') {
    $path = gallery_file_path($_POST['path'] ?? '');
    $newName = gallery_safe_name($_POST['name'] ?? '');
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $target = gallery_unique_path(dirname($path), $newName, $extension);
    if (!rename($path, $target)) {
        gallery_response(['success' => false, 'message' => 'Image could not be renamed.'], 500);
    }
    gallery_response(['success' => true, 'image' => gallery_image_payload($target), 'folders' => gallery_folders()]);
}

if ($action === 'compress_copy') {
    $path = gallery_file_path($_POST['path'] ?? '');
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        gallery_response(['success' => false, 'message' => 'Only JPG, PNG and WebP images can be compressed.'], 400);
    }

    $compressTargetKb = (int)($_POST['compress_target_kb'] ?? 400);
    $target = gallery_unique_path(dirname($path), gallery_safe_name(pathinfo($path, PATHINFO_FILENAME) . '-compressed'), $extension);
    if (!copy($path, $target)) {
        gallery_response(['success' => false, 'message' => 'Compressed copy could not be created.'], 500);
    }

    $compression = gallery_compress_image($target, $compressTargetKb);
    if (empty($compression['success'])) {
        @unlink($target);
        gallery_response(['success' => false, 'message' => $compression['message'] ?? 'Compression could not be completed.'], 500);
    }

    $savedBytes = max(0, (int)$compression['before'] - (int)$compression['after']);
    $afterKb = round(((int)$compression['after']) / 1024, 1);
    $targetNote = ((int)$compression['after'] > max(120, min(1200, $compressTargetKb)) * 1024)
        ? ' Still larger than target, kept quality reasonable.'
        : '';

    gallery_response([
        'success' => true,
        'image' => gallery_image_payload($target),
        'compression_note' => basename($target) . ' is ' . $afterKb . ' KB' . ($savedBytes > 0 ? ' and saved ' . round($savedBytes / 1024, 1) . ' KB.' : '.') . $targetNote,
        'folders' => gallery_folders(),
    ]);
}

if ($action === 'move') {
    $path = gallery_file_path($_POST['path'] ?? '');
    $folderPath = gallery_folder_path($_POST['folder'] ?? '', true);
    $target = gallery_unique_path($folderPath, gallery_safe_name(basename($path)), pathinfo($path, PATHINFO_EXTENSION));
    if (!rename($path, $target)) {
        gallery_response(['success' => false, 'message' => 'Image could not be moved.'], 500);
    }
    gallery_response(['success' => true, 'image' => gallery_image_payload($target), 'folders' => gallery_folders()]);
}

if ($action === 'delete') {
    $path = gallery_file_path($_POST['path'] ?? '');
    if (!unlink($path)) {
        gallery_response(['success' => false, 'message' => 'Image could not be deleted.'], 500);
    }
    gallery_response(['success' => true, 'message' => 'Image deleted.']);
}

if ($action === 'cleanup_delete') {
    $requestedPaths = $_POST['paths'] ?? [];
    if (!is_array($requestedPaths) || empty($requestedPaths)) {
        gallery_response(['success' => false, 'message' => 'No cleanup images were selected.'], 400);
    }

    $candidateMap = [];
    foreach (gallery_cleanup_candidates()['images'] as $image) {
        $candidateMap[$image['relative_path']] = true;
    }

    $deleted = [];
    $skipped = [];
    foreach ($requestedPaths as $requestedPath) {
        $relativePath = trim(str_replace('\\', '/', (string)$requestedPath), '/');
        if ($relativePath === '' || empty($candidateMap[$relativePath])) {
            $skipped[] = $relativePath;
            continue;
        }

        $path = gallery_file_path($relativePath);
        if (@unlink($path)) {
            $deleted[] = $relativePath;
        } else {
            $skipped[] = $relativePath;
        }
    }

    gallery_response([
        'success' => true,
        'message' => count($deleted) . ' unused image(s) deleted.',
        'deleted' => $deleted,
        'skipped' => $skipped,
        'folders' => gallery_folders(),
    ]);
}

gallery_response(['success' => false, 'message' => 'Unknown gallery action.'], 400);
