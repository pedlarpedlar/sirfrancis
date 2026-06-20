<?php
date_default_timezone_set('Africa/Johannesburg');

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    return;
}

require_once dirname(__DIR__) . '/dbh.inc.php';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $checked = isset($sirFrancisDbConfigCandidates) && is_array($sirFrancisDbConfigCandidates)
        ? implode(', ', $sirFrancisDbConfigCandidates)
        : 'No config candidates recorded.';
    error_log('Sir Francis admin DB connection unavailable. Checked: ' . $checked);
    die('Database configuration is missing. Please check the Sir Francis server configuration.');
}
