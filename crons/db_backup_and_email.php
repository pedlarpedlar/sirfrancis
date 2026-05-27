<?php
use PHPMailer\PHPMailer\PHPMailer;

date_default_timezone_set('Africa/Johannesburg');
const CANDYBIRD_DATABASES_EMAIL = 'databases@candybird.co.za';

$rootDir = dirname(__DIR__);
$accountRoot = dirname($rootDir);
$liveConfigPath = '/home/candybirdco/configs_candybird/candybird_config.php';
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

    $fallback = $rootDir . '/admin-cb/uploads/backups';
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
        throw new RuntimeException('Could not select CandyBird database: ' . $DB_dbname);
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

    fwrite($handle, "-- CandyBird database backup\n");
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
        "CandyBird daily business-data backup\n" .
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
    $baseName = basename($normalized);

    if (strpos($normalized, $backupNormalized) === 0) {
        return true;
    }

    $skipParts = [
        '/.git/',
        '/node_modules/',
        '/.well-known/acme-challenge/',
        '/cache/',
        '/logs/',
        '/tmp/',
        '/temp/',
        '/error_log',
        '/vendor/bin/',
        '/admin-cb/uploads/backups/',
        '/admin-cb/uploads/databases/',
        '/TCPDF-main/TCPDF-main.zip',
    ];

    foreach ($skipParts as $part) {
        if (strpos($normalized, str_replace('\\', '/', $rootDir) . $part) === 0 || strpos($normalized, $part) !== false) {
            return true;
        }
    }

    $skipExtensions = ['zip', 'tar', 'gz', 'rar', '7z', 'bak', 'tmp', 'log'];
    $extension = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));
    if (in_array($extension, $skipExtensions, true)) {
        return true;
    }

    return false;
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
        "CandyBird full backup\n" .
        "Created: " . date('Y-m-d H:i:s') . "\n\n" .
        "Contents:\n" .
        "- website/: website files from public_html\n" .
        "- database/database.sql: database export for " . $DB_dbname . "\n\n" .
        "Restore outline:\n" .
        "1. Upload website/ contents back to public_html.\n" .
        "2. Import database/database.sql into MySQL.\n" .
        "3. Confirm configs_candybird/candybird_config.php database details.\n"
    );

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $includedFiles = 0;
    $includedBytes = 0;
    $largestFiles = [];

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
    }

    usort($largestFiles, function($a, $b) {
        return $b['size'] <=> $a['size'];
    });
    $largestFiles = array_slice($largestFiles, 0, 50);

    $manifest = [
        'created_at' => date('Y-m-d H:i:s'),
        'included_files' => $includedFiles,
        'included_file_bytes' => $includedBytes,
        'included_file_mb' => round($includedBytes / 1048576, 2),
        'database' => $DB_dbname,
        'largest_files' => $largestFiles,
        'excluded_note' => 'Archives, logs, temporary folders, cache folders, previous backups, .git, node_modules, and database backup folders are excluded.',
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

function cbBackupNotify($zipFile, $sizeBytes, $backupType = 'Backup') {
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
        $mail->setFrom($smtp_username5, 'CandyBird Backups');
        $mail->addAddress(CANDYBIRD_DATABASES_EMAIL, 'CandyBird Databases');
        if (!empty($smtp_username1)) {
            $mail->addReplyTo($smtp_username1, 'CandyBird');
        }
        $fileName = basename($zipFile);
        $sizeMb = number_format($sizeBytes / 1048576, 2);
        $backupTypeText = htmlspecialchars($backupType, ENT_QUOTES, 'UTF-8');
        $fileNameText = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $downloadUrl = 'https://www.candybird.co.za/admin-cb/backups';
        $createdAt = date('d M Y H:i');

        $mail->Subject = 'CandyBird ' . $backupType . ' completed';
        $mail->isHTML(true);
        $mail->Body = '<div style="margin:0;background:#f6f7fb;padding:28px 12px;font-family:Arial,Helvetica,sans-serif;color:#252525;">'
            . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e8e8ef;border-radius:14px;overflow:hidden;">'
            . '<div style="background:#201717;color:#ffffff;padding:22px 26px;">'
            . '<div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#f3c9aa;">CandyBird Admin</div>'
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
            . '<p style="margin:0 0 24px;text-align:center;"><a href="' . $downloadUrl . '" style="display:inline-block;background:#c96f38;color:#ffffff;text-decoration:none;border-radius:8px;padding:13px 22px;font-weight:700;">Open backup downloads</a></p>'
            . '<p style="margin:0;color:#777;font-size:13px;line-height:1.6;">For security, the backup file is not attached to this email and is not exposed as a public link. Log in to admin to download it, then keep an offsite copy somewhere safe.</p>'
            . '</div>'
            . '</div>'
            . '</div>';
        $mail->AltBody = "CandyBird backup completed\n\n"
            . "Type: " . $backupType . "\n"
            . "File: " . $fileName . "\n"
            . "Size: " . $sizeMb . " MB\n"
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
    $notice = cbBackupNotify($zipFile, $sizeBytes, $backupType);

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
