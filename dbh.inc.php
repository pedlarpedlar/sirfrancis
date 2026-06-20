<?php
date_default_timezone_set('Africa/Johannesburg');

// Connect to the single Sir Francis database config.
$configRoots = array_filter(array_unique([
    $_SERVER['HOME'] ?? '',
    getenv('HOME') ?: '',
    dirname(__DIR__, 2),
    dirname(__DIR__),
]));
$configCandidates = [];
foreach ($configRoots as $configRoot) {
    $configCandidates[] = rtrim((string) $configRoot, '/') . '/configs_sirfrancis/sirfrancis_config.php';
}

foreach ($configCandidates as $configPath) {
    if (file_exists($configPath)) {
        require_once($configPath);
    }
    if (!empty($DB_username) && !empty($DB_dbname)) {
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
