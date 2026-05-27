<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "backups";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

function cbBackupAdminDirs() {
    $rootDir = dirname(__DIR__);
    $accountRoot = dirname($rootDir);
    return [
        $accountRoot . '/candybird_backups',
        $rootDir . '/admin-cb/uploads/backups',
    ];
}

function cbBackupAdminText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbBackupAdminFormatBytes($bytes) {
    $bytes = (float) $bytes;
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    return number_format($bytes / 1024, 2) . ' KB';
}

function cbBackupAdminResolveFile($fileName) {
    $fileName = basename((string) $fileName);
    if (!preg_match('/^candybird_(full|daily)_backup_[a-zA-Z0-9_-]+\.zip$/', $fileName)) {
        return null;
    }

    foreach (cbBackupAdminDirs() as $dir) {
        $path = realpath($dir . '/' . $fileName);
        $realDir = realpath($dir);
        if ($path && $realDir && strpos($path, $realDir) === 0 && is_file($path)) {
            return $path;
        }
    }

    return null;
}

if (isset($_GET['download'])) {
    $file = cbBackupAdminResolveFile($_GET['download']);
    if (!$file) {
        http_response_code(404);
        echo 'Backup file not found.';
        exit;
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $file = cbBackupAdminResolveFile($_POST['delete']);
    if ($file) {
        @unlink($file);
        header('Location: backups?deleted=1');
        exit;
    }
}

$backupFiles = [];
foreach (cbBackupAdminDirs() as $dir) {
    foreach (['candybird_daily_backup_*.zip', 'candybird_full_backup_*.zip'] as $pattern) {
        foreach (glob($dir . '/' . $pattern) ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }
            $backupFiles[basename($file)] = [
                'name' => basename($file),
                'type' => strpos(basename($file), 'candybird_full_backup_') === 0 ? 'Full website' : 'Daily business data',
                'path' => $file,
                'size' => filesize($file),
                'created' => filemtime($file),
                'location' => $dir,
            ];
        }
    }
}

usort($backupFiles, function($a, $b) {
    return $b['created'] <=> $a['created'];
});

include 'header.php';
include 'page_menues.php';
?>

<title>Backups - CandyBird Admin</title>

<style>
    .backup-shell { padding: 34px 0 60px; }
    .backup-hero { background: #2d1739; color: #fff; border-radius: 8px; padding: 22px; margin-bottom: 18px; }
    .backup-hero h1 { color: #fcb42f; margin-bottom: 6px; }
    .backup-panel { background: #fff; border: 1px solid #eadfd2; border-radius: 8px; padding: 18px; }
    .backup-note { background: #fffaf2; border: 1px solid #eadfd2; border-radius: 8px; padding: 12px 14px; color: #5f5366; margin-bottom: 16px; }
    .backup-location { color: #6d6270; font-size: 12px; word-break: break-all; }
</style>

<div class="container backup-shell">
    <div class="backup-hero">
        <h1>Backups</h1>
        <p class="mb-0">Download daily business-data backups and monthly full website snapshots.</p>
        <div class="mt-3">
            <a href="run_cron" class="btn btn-warning btn-sm">Run backup now</a>
            <a href="index" class="btn btn-light btn-sm">Dashboard</a>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Backup deleted.</div>
    <?php endif; ?>

    <div class="backup-note">
        Keep at least one recent backup off the hosting account too. These files are admin-only downloads, but a separate copy on your computer or cloud storage is the real safety net.
    </div>

    <div class="backup-panel">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Backup</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th>Size</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($backupFiles)): ?>
                        <tr><td colspan="6">No backups found yet. Run the backup cron to create the first one.</td></tr>
                    <?php else: foreach ($backupFiles as $backup): ?>
                        <tr>
                            <td><?= cbBackupAdminText($backup['name']) ?></td>
                            <td><?= cbBackupAdminText($backup['type']) ?></td>
                            <td><?= cbBackupAdminText(date('Y-m-d H:i', $backup['created'])) ?></td>
                            <td><?= cbBackupAdminText(cbBackupAdminFormatBytes($backup['size'])) ?></td>
                            <td><span class="backup-location"><?= cbBackupAdminText($backup['location']) ?></span></td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="backups?download=<?= urlencode($backup['name']) ?>">Download</a>
                                <form method="post" action="backups" style="display:inline;" onsubmit="return confirm('Delete this backup?');">
                                    <input type="hidden" name="delete" value="<?= cbBackupAdminText($backup['name']) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
