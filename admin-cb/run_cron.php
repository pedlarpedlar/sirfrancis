<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "index";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';

$allowedJobs = [
    'generate_google_shopping_items.php' => [
        'label' => 'Google Shopping Feed',
        'description' => 'Regenerates uploads/google_products/google_shopping_feed.txt from the product sheet.'
    ],
    'generate_sitemap.php' => [
        'label' => 'Sitemap',
        'description' => 'Regenerates the XML sitemap output from current pages, categories, products, and recipes.'
    ],
    'geolocation.php' => [
        'label' => 'Geolocation',
        'description' => 'Updates visitor city/country information for analytics.'
    ],
    'db_backup_and_email.php' => [
        'label' => 'Full Website Backup',
        'description' => 'Creates a restorable zip containing website files and a database SQL export.'
    ],
    'social_posting_reminder.php' => [
        'label' => 'Social Posting Reminder',
        'description' => 'Sends the admin reminder to post weekly on all platforms and daily on the most active platforms.'
    ],
];

function cbCronRunnerText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbCronPhpBinary() {
    $candidates = [
        PHP_BINDIR . DIRECTORY_SEPARATOR . 'php',
        PHP_BINARY,
        'php',
    ];

    foreach ($candidates as $candidate) {
        if ($candidate === 'php' || is_file($candidate)) {
            return $candidate;
        }
    }

    return 'php';
}

function cbCronRunInPage($script) {
    $started = microtime(true);
    $oldDir = getcwd();
    $oldReporting = error_reporting(E_ALL);

    if (function_exists('set_time_limit')) {
        @set_time_limit(300);
    }

    ob_start();
    try {
        chdir(dirname($script));
        include $script;
        $output = ob_get_clean();

        if (function_exists('header_remove')) {
            header_remove('Content-Type');
        }

        return [
            'success' => true,
            'exit_code' => 0,
            'duration' => round(microtime(true) - $started, 2),
            'output' => trim((string) $output),
            'error' => '',
        ];
    } catch (Throwable $e) {
        $output = ob_get_clean();

        if (function_exists('header_remove')) {
            header_remove('Content-Type');
        }

        return [
            'success' => false,
            'exit_code' => 1,
            'duration' => round(microtime(true) - $started, 2),
            'output' => trim((string) $output),
            'error' => $e->getMessage(),
        ];
    } finally {
        if ($oldDir) {
            @chdir($oldDir);
        }
        error_reporting($oldReporting);
    }
}

$job = basename((string) ($_POST['job'] ?? $_GET['job'] ?? ''));
$result = [
    'success' => false,
    'message' => '',
    'output' => '',
    'exit_code' => null,
    'duration' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($allowedJobs[$job])) {
        $result['message'] = 'Unknown or blocked cron job.';
    } else {
        $script = realpath(__DIR__ . '/../crons/' . $job);
        $cronDir = realpath(__DIR__ . '/../crons');

        if (!$script || !$cronDir || strpos($script, $cronDir) !== 0 || !is_file($script)) {
            $result['message'] = 'Cron file could not be found.';
        } elseif (!function_exists('exec')) {
            $started = microtime(true);
            $run = cbCronRunInPage($script);
            $result['exit_code'] = $run['exit_code'];
            $result['duration'] = $run['duration'];
            $result['output'] = trim($run['output'] . ($run['error'] !== '' ? "\n" . $run['error'] : ''));
            $result['success'] = $run['success'];
            $result['message'] = $result['success']
                ? $allowedJobs[$job]['label'] . ' ran successfully using dashboard fallback mode.'
                : $allowedJobs[$job]['label'] . ' could not run using dashboard fallback mode.';

            include 'dbh.inc.php';
            if ($conn instanceof mysqli) {
                $stmt = $conn->prepare("INSERT INTO cronjobs (job_name, description) VALUES (?, ?)");
                if ($stmt) {
                    $description = 'Manual dashboard fallback run: ' . $result['message'] . ' Exit code: ' . $result['exit_code'];
                    $stmt->bind_param('ss', $job, $description);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } else {
            $started = microtime(true);
            $php = cbCronPhpBinary();
            $command = escapeshellarg($php) . ' -q -f ' . escapeshellarg($script) . ' 2>&1';
            $output = [];
            $exitCode = 1;
            exec($command, $output, $exitCode);

            $result['exit_code'] = $exitCode;
            $result['duration'] = round(microtime(true) - $started, 2);
            $result['output'] = trim(implode("\n", array_slice($output, -80)));
            $result['success'] = $exitCode === 0;
            $result['message'] = $result['success']
                ? $allowedJobs[$job]['label'] . ' ran successfully.'
                : $allowedJobs[$job]['label'] . ' finished with an error.';

            if ($conn instanceof mysqli) {
                $stmt = $conn->prepare("INSERT INTO cronjobs (job_name, description) VALUES (?, ?)");
                if ($stmt) {
                    $description = 'Manual dashboard run: ' . $result['message'] . ' Exit code: ' . $exitCode;
                    $stmt->bind_param('ss', $job, $description);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
}

include 'header.php';
include 'page_menues.php';
?>

<title>Run Cron Job - CandyBird Admin</title>

<div class="container" style="padding: 36px 0 60px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-3">Run Cron Job</h1>

                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <div class="alert <?= $result['success'] ? 'alert-success' : 'alert-danger' ?>">
                            <strong><?= cbCronRunnerText($result['message']) ?></strong>
                            <?php if ($result['exit_code'] !== null): ?>
                                <div>Exit code: <?= cbCronRunnerText($result['exit_code']) ?> | Time: <?= cbCronRunnerText($result['duration']) ?>s</div>
                            <?php endif; ?>
                        </div>

                        <?php if ($result['output'] !== ''): ?>
                            <pre style="max-height: 360px; overflow:auto; background:#1f1f1f; color:#f6f6f6; padding:14px; border-radius:6px; white-space:pre-wrap;"><?= cbCronRunnerText($result['output']) ?></pre>
                        <?php endif; ?>
                    <?php endif; ?>

                    <p class="text-muted">Choose one approved cron job to run now. Longer jobs may take a little while.</p>

                    <div class="list-group mb-3">
                        <?php foreach ($allowedJobs as $file => $info): ?>
                            <div class="list-group-item">
                                <div class="d-flex flex-column flex-md-row justify-content-between" style="gap: 12px;">
                                    <div>
                                        <strong><?= cbCronRunnerText($info['label']) ?></strong>
                                        <div class="text-muted small"><?= cbCronRunnerText($info['description']) ?></div>
                                        <code><?= cbCronRunnerText($file) ?></code>
                                    </div>
                                    <form method="post" action="run_cron" onsubmit="return confirm('Run <?= cbCronRunnerText($info['label']) ?> now?');">
                                        <input type="hidden" name="job" value="<?= cbCronRunnerText($file) ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Run now</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <a href="index" class="btn btn-outline-dark">Back to dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
