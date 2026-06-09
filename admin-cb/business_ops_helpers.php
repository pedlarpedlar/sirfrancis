<?php
date_default_timezone_set('Africa/Johannesburg');

function cbOpsText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cbOpsEnsureTables($conn) {
    if (!($conn instanceof mysqli)) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS admin_social_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform VARCHAR(120) NOT NULL,
        handle VARCHAR(255) DEFAULT '',
        profile_url VARCHAR(500) DEFAULT '',
        login_email VARCHAR(255) DEFAULT '',
        login_username VARCHAR(255) DEFAULT '',
        encrypted_password LONGTEXT NULL,
        notes TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        most_active TINYINT(1) DEFAULT 0,
        reminder_frequency VARCHAR(20) DEFAULT 'weekly',
        last_posted_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS admin_social_reminder_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_email VARCHAR(255) DEFAULT '',
        reminder_day VARCHAR(20) DEFAULT 'Monday',
        reminder_time TIME DEFAULT '08:00:00',
        subject VARCHAR(255) DEFAULT 'CandyBird social posting reminder',
        enabled TINYINT(1) DEFAULT 1,
        last_sent_at DATETIME NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS admin_business_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(120) NOT NULL,
        document_date DATE NULL,
        expiry_date DATE NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(700) NOT NULL,
        file_size INT DEFAULT 0,
        mime_type VARCHAR(160) DEFAULT '',
        notes TEXT NULL,
        uploaded_by_admin_id INT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    cbOpsEnsureColumn($conn, 'admin_social_accounts', 'most_active', "TINYINT(1) DEFAULT 0");
    cbOpsEnsureColumn($conn, 'admin_social_accounts', 'reminder_frequency', "VARCHAR(20) DEFAULT 'weekly'");
    cbOpsEnsureColumn($conn, 'admin_social_accounts', 'last_posted_at', "DATETIME NULL");
}

function cbOpsEnsureColumn($conn, $table, $column, $definition) {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
    if ($safeTable === '' || $safeColumn === '') {
        return;
    }
    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    if ($result && $result->num_rows > 0) {
        return;
    }
    $conn->query("ALTER TABLE `{$safeTable}` ADD COLUMN `{$safeColumn}` {$definition}");
}

function cbOpsSecretKey() {
    $material = implode('|', array_filter([
        $GLOBALS['DB_password'] ?? '',
        $GLOBALS['smtp_password1'] ?? '',
        $GLOBALS['smtp_password5'] ?? '',
        __DIR__,
    ]));
    return hash('sha256', $material !== '' ? $material : 'candybird-business-ops');
}

function cbOpsEncryptSecret($plainText) {
    $plainText = (string) $plainText;
    if ($plainText === '') {
        return '';
    }
    if (!function_exists('openssl_encrypt')) {
        return 'plain:' . base64_encode($plainText);
    }
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plainText, 'AES-256-CBC', hex2bin(cbOpsSecretKey()), OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        return 'plain:' . base64_encode($plainText);
    }
    return 'enc:' . base64_encode($iv . $cipher);
}

function cbOpsDecryptSecret($stored) {
    $stored = (string) $stored;
    if ($stored === '') {
        return '';
    }
    if (strpos($stored, 'plain:') === 0) {
        return (string) base64_decode(substr($stored, 6));
    }
    if (strpos($stored, 'enc:') !== 0 || !function_exists('openssl_decrypt')) {
        return '';
    }
    $raw = base64_decode(substr($stored, 4), true);
    if ($raw === false || strlen($raw) <= 16) {
        return '';
    }
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', hex2bin(cbOpsSecretKey()), OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}

function cbOpsCleanHeader($value) {
    $value = preg_replace('/[\r\n]+/', ' ', (string) $value);
    return trim($value);
}

function cbOpsRows($conn, $sql) {
    $rows = [];
    if (!($conn instanceof mysqli)) {
        return $rows;
    }
    $result = $conn->query($sql);
    if (!$result) {
        return $rows;
    }
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function cbOpsDocumentUploadDir() {
    return __DIR__ . '/uploads/business_documents';
}

function cbOpsDocumentCategories() {
    return [
        'CIPC',
        'SARS / Tax',
        'Trademarks',
        'Payment Providers',
        'Contracts',
        'Banking',
        'Compliance',
        'Insurance',
        'Staff / HR',
        'Other',
    ];
}
?>
