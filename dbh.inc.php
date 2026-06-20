<?php
date_default_timezone_set('Africa/Johannesburg');

if (!function_exists('sirFrancisDbConfigCandidates')) {
    function sirFrancisDbConfigCandidates($baseDir) {
        $paths = [];
        $addPath = static function($path) use (&$paths) {
            $path = str_replace('\\', '/', trim((string) $path));
            if ($path !== '') {
                $paths[$path] = true;
            }
        };
        $addRoot = static function($root) use ($addPath) {
            $root = rtrim(str_replace('\\', '/', trim((string) $root)), '/');
            if ($root !== '') {
                $addPath($root . '/configs_sirfrancis/sirfrancis_config.php');
            }
        };
        $addParents = static function($path, $levels = 5) use ($addRoot) {
            $path = str_replace('\\', '/', trim((string) $path));
            if ($path === '') {
                return;
            }
            if (is_file($path)) {
                $path = dirname($path);
            }
            for ($i = 0; $i <= $levels; $i++) {
                $addRoot($path);
                $parent = dirname($path);
                if ($parent === $path || $parent === '.' || $parent === '') {
                    break;
                }
                $path = $parent;
            }
        };

        $explicit = getenv('SIRFRANCIS_CONFIG') ?: '';
        if ($explicit !== '') {
            $addPath($explicit);
        }

        $addRoot($_SERVER['HOME'] ?? '');
        $addRoot(getenv('HOME') ?: '');
        $addParents($baseDir);
        $addParents($_SERVER['DOCUMENT_ROOT'] ?? '');
        $addParents($_SERVER['SCRIPT_FILENAME'] ?? '');

        return array_keys($paths);
    }
}

$configCandidates = sirFrancisDbConfigCandidates(__DIR__);
$sirFrancisDbConfigCandidates = $configCandidates;

foreach ($configCandidates as $configPath) {
    if (is_readable($configPath)) {
        require_once($configPath);
    }
    $candidateUser = $DB_username ?? $DB_user ?? $db_user ?? $username ?? null;
    $candidateDb = $DB_dbname ?? $DB_name ?? $db_name ?? $dbname ?? null;
    if (!empty($candidateUser) && !empty($candidateDb)) {
        break;
    }
}

$DB_servername = $DB_servername ?? $DB_host ?? $db_host ?? $servername ?? (defined('DB_HOST') ? DB_HOST : getenv('DB_HOST'));
$DB_username = $DB_username ?? $DB_user ?? $db_user ?? $username ?? (defined('DB_USER') ? DB_USER : getenv('DB_USER'));
$DB_password = $DB_password ?? $DB_pass ?? $db_pass ?? $password ?? (defined('DB_PASS') ? DB_PASS : getenv('DB_PASS'));
$DB_dbname = $DB_dbname ?? $DB_name ?? $db_name ?? $dbname ?? (defined('DB_NAME') ? DB_NAME : getenv('DB_NAME'));

if (empty($DB_servername)) {
    $DB_servername = "localhost";
}

if (empty($DB_username) || empty($DB_dbname)) {
    $dbAvailable = false;
    $conn = null;
    return;
}

$conn = @new mysqli($DB_servername, $DB_username, $DB_password, $DB_dbname);
$dbAvailable = true;

// Check the connection
if ($conn->connect_error) {
    $dbAvailable = false;
    $conn = null;
    return;
}

// Set the character set to UTF-8
$conn->set_charset("utf8");
$conn->query("SET time_zone = '+02:00'");
