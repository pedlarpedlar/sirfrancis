<?php
date_default_timezone_set('Africa/Johannesburg');

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    return;
}

$configCandidates = [
    '/home2/rukbanor/configs_sirfrancis/sirfrancis_config.php',
    '/home2/rukbanor/configs_candybird/candybird_config.php',
    '/home2/rukbanor/configs_Candybird/Candybird_config.php',
    '/home2/rukbanor/configs/candybird_config.php',
    '/home2/rukbanor/fishgelatine.co.za/configs/candybird_config.php',
    '/home/candybirdco/configs_candybird/candybird_config.php',
    __DIR__ . '/../configs/candybird_config.php',
];

foreach ($configCandidates as $configPath) {
    if (is_file($configPath)) {
        require_once $configPath;
    }
    if (!empty($DB_username) && !empty($DB_dbname)) {
        break;
    }
}

$DB_servername = $DB_servername ?? (defined('DB_HOST') ? DB_HOST : getenv('DB_HOST'));
$DB_username = $DB_username ?? (defined('DB_USER') ? DB_USER : getenv('DB_USER'));
$DB_password = $DB_password ?? (defined('DB_PASS') ? DB_PASS : getenv('DB_PASS'));
$DB_dbname = $DB_dbname ?? (defined('DB_NAME') ? DB_NAME : getenv('DB_NAME'));

if (empty($DB_servername)) {
    $DB_servername = 'localhost';
}

if (empty($DB_username) || empty($DB_dbname)) {
    error_log('Sir Francis admin DB credentials missing. Checked /home2/rukbanor config paths and repo configs/candybird_config.php');
    die('Database configuration is missing. Please check the Sir Francis server configuration.');
}

$conn = @new mysqli($DB_servername, $DB_username, (string) $DB_password, $DB_dbname);
if ($conn->connect_error) {
    error_log('Sir Francis admin DB connection failed: ' . $conn->connect_error);
    die('Database connection failed. Please check the Sir Francis server configuration.');
}

$conn->set_charset('utf8');
$conn->query("SET time_zone = '+02:00'");
