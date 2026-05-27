<?php
include 'session_logins.php';

header('Content-Type: application/json');

$email = trim((string) ($_POST['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => true,
        'exists' => false
    ]);
    exit();
}

if (!($conn instanceof mysqli)) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Account check is temporarily unavailable.'
    ]);
    exit();
}

$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Account check is temporarily unavailable.'
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

$exists = mysqli_stmt_num_rows($stmt) > 0;
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'exists' => $exists,
    'login_url' => 'login?redirect=checkout'
]);
