<?php
use PHPMailer\PHPMailer\PHPMailer;

date_default_timezone_set('Africa/Johannesburg');
const SIRFRANCIS_DATABASES_EMAIL = 'databases@candybird.co.za';

$rootDir = dirname(__DIR__);
$accountRoot = dirname($rootDir);
$liveConfigPath = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: $accountRoot), '/') . '/configs_sirfrancis/sirfrancis_config.php';
$localConfigPath = $rootDir . '/dbh.inc.php';

if (file_exists($liveConfigPath)) {
    require_once $liveConfigPath;
} elseif (file_exists($localConfigPath)) {
    require_once $localConfigPath;
}

function cbBackupEcho($message) {
    echo $message . PHP_EOL;
}

function cbBackupEnsureDir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return is_dir($dir) && is_writable($dir);
}

function cbBackupDirectory($rootDir, $accountRoot) {
    $preferred = $accountRoot . '/candybird_backups';
    if (cbBackupEnsureDir($preferred)) {
        return $preferred;
    }

    $fallback = $rootDir . '/admin-sf/uploads/backups';
    if (cbBackupEnsureDir($fallback)) {
        $denyFile = $fallback . '/.htaccess';
        if (!is_file($denyFile)) {
            @file_put_contents($denyFile, "Require all denied\nDeny from all\n");
        }
        return $fallback;
    }

    throw new RuntimeException('Could not create a writable backup folder.');
}

function cbBackupConnectDatabase() {
    global $DB_servername, $DB_username, $DB_password, $DB_dbname, $conn;

    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        return $conn;
    }

    foreach (['DB_servername', 'DB_username', 'DB_password', 'DB_dbname'] as $required) {
        if (!isset($GLOBALS[$required])) {
            throw new RuntimeException('Database setting missing: ' . $required);
        }
    }

    $db = @new mysqli($DB_servername, $DB_username, $DB_password, $DB_dbname);
    if ($db->connect_error && $DB_servername === 'localhost') {
        $db = @new mysqli('127.0.0.1', $DB_username, $DB_password, $DB_dbname);
    }

    if ($db->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $db->connect_error);
    }

    if (!$db->select_db($DB_dbname)) {
        throw new RuntimeException('Could not select Sir Francis database: ' . $DB_dbname);
    }

    $db->set_charset('utf8mb4');
    return $db;
}

function cbBackupCoreTables() {
    return [
        'users',
        'user_addresses',
        'orders',
        'order_items',
        'order_adjustments',
        'payment_checks',
        'cart',
        'wishlist',
        'compare',
        'reviews',
        'subscribers',
        'scheduled_emails',
        'coupon_email_usage',
        'admin_website_settings',
        'sheet_sources',
        'cron_run_log',
        'page_views',
        'user_sessions',
        'action_logs',
        'ip_geolocation'
    ];
}

function cbBackupDailyBusinessTables() {
    return [
        'users',
        'user_addresses',
        'orders',
        'order_items',
        'order_adjustments',
        'payment_checks',
        'coupon_email_usage',
        'subscribers'
    ];
}

function cbBackupWriteSql($conn, $sqlFile, $onlyTables = null) {
    global $DB_dbname;

    $handle = fopen($sqlFile, 'w');
    if (!$handle) {
        throw new RuntimeException('Could not create SQL backup file: ' . $sqlFile);
    }

    fwrite($handle, "-- Sir Francis database backup\n");
    fwrite($handle, "-- Database: " . $DB_dbname . "\n");
    fwrite($handle, "-- Created: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($handle, "CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '``', $DB_dbname) . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
    fwrite($handle, "USE `" . str_replace('`', '``', $DB_dbname) . "`;\n\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = [];
    $result = $conn->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
    if (!$result) {
        fclose($handle);
        throw new RuntimeException('Could not list database tables: ' . $conn->error);
    }

    $onlyLookup = is_array($onlyTables) ? array_flip($onlyTables) : null;
    while ($row = $result->fetch_row()) {
        if ($onlyLookup !== null && !isset($onlyLookup[$row[0]])) {
            continue;
        }
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $safeTable = str_replace('`', '``', $table);
        $createResult = $conn->query("SHOW CREATE TABLE `$safeTable`");
        if (!$createResult) {
            continue;
        }

        $createRow = $createResult->fetch_row();
        fwrite($handle, "DROP TABLE IF EXISTS `$safeTable`;\n");
        fwrite($handle, $createRow[1] . ";\n\n");

        $rows = $conn->query("SELECT * FROM `$safeTable`", MYSQLI_USE_RESULT);
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array_map(function($column) {
                return '`' . str_replace('`', '``', $column) . '`';
            }, array_keys($data));

            $values = array_map(function($value) use ($conn) {
                return $value === null ? 'NULL' : "'" . $conn->real_escape_string((string) $value) . "'";
            }, array_values($data));

            fwrite($handle, "INSERT INTO `$safeTable` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n");
        }
        $rows->free();
        fwrite($handle, "\n");
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);
}

function cbBackupZipDatabaseOnly($zipFile, $sqlFile) {
    global $DB_dbname;

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive is not available on this server.');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Could not create database backup zip file: ' . $zipFile);
    }

    $zip->addFile($sqlFile, 'database/database.sql');
    $zip->addFromString('RESTORE_NOTES.txt',
        "Sir Francis daily business-data backup\n" .
        "Created: " . date('Y-m-d H:i:s') . "\n\n" .
        "This daily backup contains users, user addresses, subscribers, orders, order items, order adjustments, payment checks and coupon usage for " . $DB_dbname . ".\n" .
        "It is only created when these business-data tables have changed since the previous daily backup.\n"
    );
    $zip->close();

    return [
        'type' => 'daily_database',
        'included_files' => 1,
        'included_bytes' => filesize($sqlFile) ?: 0,
        'database' => $DB_dbname,
        'created_at' => date('c')
    ];
}

function cbBackupShouldSkip($path, $rootDir, $backupDir) {
    $normalized = str_replace('\\', '/', $path);
    $backupNormalized = str_replace('\\', '/', $backupDir);
    $rootNormalized = rtrim(str_replace('\\', '/', $rootDir), '/');
    $baseName = basename($normalized);

    if (strpos($normalized, $backupNormalized) === 0) {
        return true;
    }

    $skipParts = [
        '/.git/',
        '/.github/',
        '/node_modules/',
        '/.well-known/acme-challenge/',
        '/backups/',
        '/candybird2025/',
        '/candybird-libs/',
        '/cache/',
        '/expomedia.co.za/',
        '/sheet_cache/',
        '/syncitt.co.za/',
        '/logs/',
        '/tmp/',
        '/temp/',
        '/Website CSV Product Lists/',
        '/error_log',
        '/vendor/bin/',
        '/admin-sf/uploads/backups/',
        '/admin-sf/uploads/databases/',
        '/uploads/backups/',
        '/uploads/databases/',
        '/TCPDF-main/',
        '/TCPDF-main/TCPDF-main.zip',
    ];

    foreach ($skipParts as $part) {
        if (strpos($normalized, $rootNormalized . $part) === 0 || strpos($normalized, $part) !== false) {
            return true;
        }
    }

    $skipExtensions = ['zip', 'tar', 'gz', 'rar', '7z', 'bak', 'tmp', 'log', 'sql'];
    $extension = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));
    if (in_array($extension, $skipExtensions, true)) {
        return true;
    }

    $skipExactFiles = [
        'debug.log',
        'error_log',
        'PHPMailer.zip',
    ];
    if (in_array($baseName, $skipExactFiles, true)) {
        return true;
    }

    return false;
}

function cbBackupFormatBytes($bytes) {
    $bytes = (float) $bytes;
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return number_format($bytes, 0) . ' bytes';
}

function cbBackupFileType($relativePath) {
    $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    if ($extension === '') {
        return 'no extension';
    }
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
        return 'images';
    }
    if (in_array($extension, ['php', 'inc'], true)) {
        return 'php';
    }
    if (in_array($extension, ['css', 'scss'], true)) {
        return 'css';
    }
    if (in_array($extension, ['js', 'map'], true)) {
        return 'javascript';
    }
    if (in_array($extension, ['html', 'txt', 'md'], true)) {
        return 'text/templates';
    }
    return $extension;
}

function cbBackupFolderKey($relativePath) {
    $relativePath = trim(str_replace('\\', '/', (string) $relativePath), '/');
    $parts = explode('/', $relativePath);

    if (count($parts) <= 2) {
        return dirname($relativePath) !== '.' ? dirname($relativePath) : 'website root';
    }

    return implode('/', array_slice($parts, 0, min(4, count($parts) - 1)));
}

function cbBackupZipWebsite($zipFile, $rootDir, $backupDir, $sqlFile) {
    global $DB_dbname;

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive is not available on this server.');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Could not create backup zip file: ' . $zipFile);
    }

    $zip->addFile($sqlFile, 'database/database.sql');
    $zip->addFromString('RESTORE_NOTES.txt',
        "Sir Francis full backup\n" .
        "Created: " . date('Y-m-d H:i:s') . "\n\n" .
        "Contents:\n" .
        "- website/: website files from public_html\n" .
        "- database/database.sql: database export for " . $DB_dbname . "\n\n" .
        "Restore outline:\n" .
        "1. Upload website/ contents back to public_html.\n" .
        "2. Import database/database.sql into MySQL.\n" .
        "3. Confirm ~/configs_sirfrancis/sirfrancis_config.php database details.\n"
    );

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $includedFiles = 0;
    $includedBytes = 0;
    $largestFiles = [];
    $typeSummary = [];
    $folderSummary = [];

    foreach ($iterator as $file) {
        $path = $file->getPathname();
        if (cbBackupShouldSkip($path, $rootDir, $backupDir) || $file->isDir()) {
            continue;
        }

        $relative = 'website/' . ltrim(str_replace('\\', '/', substr($path, strlen($rootDir))), '/');
        $zip->addFile($path, $relative);
        $size = $file->getSize();
        $includedFiles++;
        $includedBytes += $size;
        $largestFiles[] = ['path' => $relative, 'size' => $size];
        $type = cbBackupFileType($relative);
        if (!isset($typeSummary[$type])) {
            $typeSummary[$type] = ['files' => 0, 'bytes' => 0];
        }
        $typeSummary[$type]['files']++;
        $typeSummary[$type]['bytes'] += $size;

        $folder = cbBackupFolderKey($relative);
        if (!isset($folderSummary[$folder])) {
            $folderSummary[$folder] = ['files' => 0, 'bytes' => 0, 'images' => 0];
        }
        $folderSummary[$folder]['files']++;
        $folderSummary[$folder]['bytes'] += $size;
        if ($type === 'images') {
            $folderSummary[$folder]['images']++;
        }
    }

    usort($largestFiles, function($a, $b) {
        return $b['size'] <=> $a['size'];
    });
    $largestFiles = array_slice($largestFiles, 0, 50);
    uasort($typeSummary, function($a, $b) {
        return $b['bytes'] <=> $a['bytes'];
    });
    uasort($folderSummary, function($a, $b) {
        return $b['bytes'] <=> $a['bytes'];
    });

    $manifest = [
        'created_at' => date('Y-m-d H:i:s'),
        'included_files' => $includedFiles,
        'included_file_bytes' => $includedBytes,
        'included_file_mb' => round($includedBytes / 1048576, 2),
        'database' => $DB_dbname,
        'type_summary' => $typeSummary,
        'folder_summary' => $folderSummary,
        'largest_files' => $largestFiles,
        'excluded_note' => 'Archives, logs, temporary folders, sheet/cache folders, previous backups, .git, node_modules, old CSV export folders, and database backup folders are excluded.',
    ];
    $zip->addFromString('BACKUP_MANIFEST.json', json_encode($manifest, JSON_PRETTY_PRINT));

    $zip->close();
    return $manifest;
}

function cbBackupRotatePattern($backupDir, $pattern, $daysToKeep) {
    $cutoff = time() - ($daysToKeep * 86400);
    foreach (glob($backupDir . '/' . $pattern) ?: [] as $file) {
        if (is_file($file) && filemtime($file) < $cutoff) {
            @unlink($file);
        }
    }
}

function cbBackupRotate($backupDir) {
    cbBackupRotatePattern($backupDir, 'candybird_daily_backup_*.zip', 31);
    cbBackupRotatePattern($backupDir, 'candybird_full_backup_*.zip', 370);
}

function cbBackupLogCron($conn, $description) {
    if (!($conn instanceof mysqli)) {
        return;
    }

    $stmt = $conn->prepare("INSERT INTO cronjobs (job_name, description) VALUES (?, ?)");
    if ($stmt) {
        $jobName = 'db_backup_and_email.php';
        $stmt->bind_param('ss', $jobName, $description);
        $stmt->execute();
        $stmt->close();
    }
}

function cbBackupManifestRows($manifest, $mode = 'types') {
    if (empty($manifest) || !is_array($manifest)) {
        return '';
    }

    $rows = '';
    if ($mode === 'largest') {
        $files = array_slice($manifest['largest_files'] ?? [], 0, 8);
        foreach ($files as $file) {
            $rows .= '<tr>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eee;color:#777;">' . htmlspecialchars((string) ($file['path'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right;font-weight:700;">' . htmlspecialchars(cbBackupFormatBytes($file['size'] ?? 0), ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }
        return $rows;
    }

    if ($mode === 'folders') {
        $folders = array_slice($manifest['folder_summary'] ?? [], 0, 12, true);
        foreach ($folders as $folder => $summary) {
            $imageText = !empty($summary['images']) ? ' | ' . number_format((int) $summary['images']) . ' images' : '';
            $rows .= '<tr>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eee;color:#777;">' . htmlspecialchars((string) $folder, ENT_QUOTES, 'UTF-8') . ' <span style="color:#999;">(' . number_format((int) ($summary['files'] ?? 0)) . ' files' . $imageText . ')</span></td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right;font-weight:700;">' . htmlspecialchars(cbBackupFormatBytes($summary['bytes'] ?? 0), ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }
        return $rows;
    }

    $types = array_slice($manifest['type_summary'] ?? [], 0, 8, true);
    foreach ($types as $type => $summary) {
        $rows .= '<tr>'
            . '<td style="padding:8px 0;border-bottom:1px solid #eee;color:#777;">' . htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') . ' <span style="color:#999;">(' . number_format((int) ($summary['files'] ?? 0)) . ' files)</span></td>'
            . '<td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right;font-weight:700;">' . htmlspecialchars(cbBackupFormatBytes($summary['bytes'] ?? 0), ENT_QUOTES, 'UTF-8') . '</td>'
            . '</tr>';
    }
    return $rows;
}

function cbBackupNotify($zipFile, $sizeBytes, $backupType = 'Backup', $manifest = []) {
    global $smtp_server, $smtp_username1, $smtp_username5, $smtp_password, $smtp_password5, $smtp_type, $smtp_port;

    if (empty($smtp_server) || empty($smtp_username5) || (empty($smtp_password) && empty($smtp_password5))) {
        return 'SMTP settings missing; notification not sent.';
    }

    $phpmailer = dirname(__DIR__) . '/PHPMailer/PHPMailer/src/PHPMailer.php';
    $exception = dirname(__DIR__) . '/PHPMailer/PHPMailer/src/Exception.php';
    $smtp = dirname(__DIR__) . '/PHPMailer/PHPMailer/src/SMTP.php';
    if (!is_file($phpmailer)) {
        return 'PHPMailer not found; notification not sent.';
    }

    require_once $phpmailer;
    require_once $exception;
    require_once $smtp;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username5;
        $mail->Password = $smtp_password5 ?? $smtp_password;
        if (!empty($smtp_type)) {
            $mail->SMTPSecure = $smtp_type;
        }
        $mail->Port = (int) ($smtp_port ?? 587);
        $mail->setFrom($smtp_username5, 'Sir Francis Backups');
        $mail->addAddress(SIRFRANCIS_DATABASES_EMAIL, 'Sir Francis Databases');
        if (!empty($smtp_username1)) {
            $mail->addReplyTo($smtp_username1, 'Sir Francis');
        }
        $fileName = basename($zipFile);
        $sizeMb = number_format($sizeBytes / 1048576, 2);
        $backupTypeText = htmlspecialchars($backupType, ENT_QUOTES, 'UTF-8');
        $fileNameText = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $downloadUrl = 'https://sirfrancis.co.za/admin-sf/backups';
        $createdAt = date('d M Y H:i');
        $includedFiles = number_format((int) ($manifest['included_files'] ?? 0));
        $includedSize = cbBackupFormatBytes((float) ($manifest['included_file_bytes'] ?? 0));
        $typeRows = cbBackupManifestRows($manifest, 'types');
        $folderRows = cbBackupManifestRows($manifest, 'folders');
        $largestRows = cbBackupManifestRows($manifest, 'largest');
        $manifestHtml = '';
        if ($typeRows !== '' || $folderRows !== '' || $largestRows !== '') {
            $manifestHtml = '<h2 style="font-size:16px;margin:22px 0 8px;color:#201717;">Monthly backup contents</h2>'
                . '<table style="width:100%;border-collapse:collapse;margin:0 0 14px;font-size:13px;">'
                . '<tr><td style="padding:8px 0;border-bottom:1px solid #eee;color:#777;">Included website files</td><td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right;font-weight:700;">' . $includedFiles . '</td></tr>'
                . '<tr><td style="padding:8px 0;border-bottom:1px solid #eee;color:#777;">Included website file size before zip compression</td><td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right;font-weight:700;">' . htmlspecialchars($includedSize, ENT_QUOTES, 'UTF-8') . '</td></tr>'
                . '</table>'
                . ($folderRows !== '' ? '<h3 style="font-size:14px;margin:16px 0 6px;color:#201717;">Largest folders included</h3><table style="width:100%;border-collapse:collapse;margin:0 0 14px;font-size:13px;">' . $folderRows . '</table>' : '')
                . ($typeRows !== '' ? '<h3 style="font-size:14px;margin:16px 0 6px;color:#201717;">Biggest file groups</h3><table style="width:100%;border-collapse:collapse;margin:0 0 14px;font-size:13px;">' . $typeRows . '</table>' : '')
                . ($largestRows !== '' ? '<h3 style="font-size:14px;margin:16px 0 6px;color:#201717;">Largest included files</h3><table style="width:100%;border-collapse:collapse;margin:0 0 14px;font-size:13px;">' . $largestRows . '</table>' : '');
        }

        $mail->Subject = 'Sir Francis ' . $backupType . ' completed';
        $mail->isHTML(true);
        $mail->Body = '<div style="margin:0;background:#f6f7fb;padding:28px 12px;font-family:Arial,Helvetica,sans-serif;color:#252525;">'
            . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e8e8ef;border-radius:14px;overflow:hidden;">'
            . '<div style="background:#201717;color:#ffffff;padding:22px 26px;">'
            . '<div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#f3c9aa;">Sir Francis Admin</div>'
            . '<h1 style="margin:8px 0 0;font-size:24px;line-height:1.25;font-weight:700;">Backup completed</h1>'
            . '</div>'
            . '<div style="padding:26px;">'
            . '<p style="margin:0 0 18px;font-size:16px;line-height:1.6;">Your ' . $backupTypeText . ' was created successfully and is ready to download from the protected admin backup area.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:0 0 22px;font-size:14px;">'
            . '<tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#777;">Type</td><td style="padding:10px 0;border-bottom:1px solid #eee;text-align:right;font-weight:700;">' . $backupTypeText . '</td></tr>'
            . '<tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#777;">File</td><td style="padding:10px 0;border-bottom:1px solid #eee;text-align:right;font-weight:700;">' . $fileNameText . '</td></tr>'
            . '<tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#777;">Size</td><td style="padding:10px 0;border-bottom:1px solid #eee;text-align:right;font-weight:700;">' . $sizeMb . ' MB</td></tr>'
            . '<tr><td style="padding:10px 0;color:#777;">Created</td><td style="padding:10px 0;text-align:right;font-weight:700;">' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '</table>'
            . $manifestHtml
            . '<p style="margin:0 0 24px;text-align:center;"><a href="' . $downloadUrl . '" style="display:inline-block;background:#c96f38;color:#ffffff;text-decoration:none;border-radius:8px;padding:13px 22px;font-weight:700;">Open backup downloads</a></p>'
            . '<p style="margin:0;color:#777;font-size:13px;line-height:1.6;">For security, the backup file is not attached to this email and is not exposed as a public link. Log in to admin to download it, then keep an offsite copy somewhere safe.</p>'
            . '</div>'
            . '</div>'
            . '</div>';
        $mail->AltBody = "Sir Francis backup completed\n\n"
            . "Type: " . $backupType . "\n"
            . "File: " . $fileName . "\n"
            . "Size: " . $sizeMb . " MB\n"
            . (!empty($manifest['included_files']) ? "Included files: " . number_format((int) $manifest['included_files']) . "\n" : '')
            . (!empty($manifest['included_file_bytes']) ? "Included file size before compression: " . cbBackupFormatBytes($manifest['included_file_bytes']) . "\n" : '')
            . "Created: " . $createdAt . "\n"
            . "Download: " . $downloadUrl . "\n\n"
            . "For security, log in to admin to download the backup.";
        $mail->send();
        return 'Notification email sent.';
    } catch (Throwable $e) {
        return 'Notification email failed: ' . $e->getMessage();
    }
}

try {
    if (function_exists('set_time_limit')) {
        @set_time_limit(900);
    }

    $conn = cbBackupConnectDatabase();
    $backupDir = cbBackupDirectory($rootDir, $accountRoot);
    $stamp = date('Y-m-d_His');
    $requestedMode = strtolower(trim((string) ($argv[1] ?? $_GET['mode'] ?? 'auto')));
    if (!in_array($requestedMode, ['auto', 'daily', 'full'], true)) {
        throw new RuntimeException('Unknown backup mode. Use daily or full.');
    }

    $isMonthlyFullBackup = $requestedMode === 'full' || ($requestedMode === 'auto' && date('j') === '1');
    $sqlFile = $backupDir . '/candybird_database_' . $stamp . '.sql';
    $zipFile = $backupDir . ($isMonthlyFullBackup ? '/candybird_full_backup_' : '/candybird_daily_backup_') . $stamp . '.zip';

    cbBackupWriteSql($conn, $sqlFile, $isMonthlyFullBackup ? null : cbBackupDailyBusinessTables());

    if (!$isMonthlyFullBackup) {
        $dailyHash = hash_file('sha256', $sqlFile);
        $dailyHashFile = $backupDir . '/candybird_daily_business_data.sha256';
        $previousHash = is_file($dailyHashFile) ? trim((string) @file_get_contents($dailyHashFile)) : '';

        if ($previousHash !== '' && hash_equals($previousHash, $dailyHash)) {
            @unlink($sqlFile);
            cbBackupRotate($backupDir);
            $description = 'Daily business-data backup skipped because users, subscribers and order data have not changed since the previous daily backup.';
            cbBackupLogCron($conn, $description);
            cbBackupEcho($description);
            return;
        }
    }

    $manifest = $isMonthlyFullBackup
        ? cbBackupZipWebsite($zipFile, $rootDir, $backupDir, $sqlFile)
        : cbBackupZipDatabaseOnly($zipFile, $sqlFile);
    @unlink($sqlFile);
    cbBackupRotate($backupDir);

    $sizeBytes = is_file($zipFile) ? filesize($zipFile) : 0;
    $backupType = $isMonthlyFullBackup ? 'monthly full website backup' : 'daily business-data backup';
    $notice = cbBackupNotify($zipFile, $sizeBytes, $backupType, $manifest);

    if (!$isMonthlyFullBackup && isset($dailyHash, $dailyHashFile)) {
        @file_put_contents($dailyHashFile, $dailyHash);
    }

    $description = ($isMonthlyFullBackup ? 'Full website backup' : 'Daily business-data backup') . ' created: ' . basename($zipFile) . ' (' . number_format($sizeBytes / 1048576, 2) . ' MB, ' . number_format((float) ($manifest['included_files'] ?? 0)) . ' files). ' . $notice;
    cbBackupLogCron($conn, $description);

    cbBackupEcho('Backup completed: ' . $zipFile);
    cbBackupEcho('Size: ' . number_format($sizeBytes / 1048576, 2) . ' MB');
    cbBackupEcho('Included files: ' . number_format((float) ($manifest['included_files'] ?? 0)));
    cbBackupEcho($notice);
} catch (Throwable $e) {
    cbBackupEcho('Backup failed: ' . $e->getMessage());
    throw $e;
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
