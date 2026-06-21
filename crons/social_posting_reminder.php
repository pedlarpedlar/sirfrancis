<?php
date_default_timezone_set('Africa/Johannesburg');

$root = dirname(__DIR__);
$dbh = file_exists($root . '/dbh.inc.php') ? $root . '/dbh.inc.php' : $root . '/admin-sf/dbh.inc.php';
include $dbh;
require_once $root . '/admin-sf/business_ops_helpers.php';
require_once $root . '/candybird_mail_helpers.php';

cbOpsEnsureTables($conn);

function cbSocialReminderEcho($message) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

$settings = cbOpsRows($conn, "SELECT * FROM admin_social_reminder_settings WHERE id = 1 LIMIT 1");
$settings = $settings[0] ?? null;
if (!$settings || (int) ($settings['enabled'] ?? 0) !== 1) {
    cbSocialReminderEcho('Social posting reminder is disabled or not configured.');
    exit(0);
}

$recipient = trim((string) ($settings['recipient_email'] ?? ''));
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    cbSocialReminderEcho('No valid reminder recipient email is configured.');
    exit(1);
}

$now = new DateTimeImmutable('now', new DateTimeZone('Africa/Johannesburg'));
$reminderTime = substr((string) ($settings['reminder_time'] ?? '08:00:00'), 0, 5);
if ($reminderTime !== '' && $now->format('H:i') < $reminderTime) {
    cbSocialReminderEcho('Reminder time has not arrived yet.');
    exit(0);
}

$lastSentAt = !empty($settings['last_sent_at']) ? new DateTimeImmutable((string) $settings['last_sent_at'], new DateTimeZone('Africa/Johannesburg')) : null;
if ($lastSentAt && $lastSentAt->format('Y-m-d') === $now->format('Y-m-d')) {
    cbSocialReminderEcho('Reminder was already sent today.');
    exit(0);
}

$weeklyDay = strtolower((string) ($settings['reminder_day'] ?? 'Monday'));
$isWeeklyDay = strtolower($now->format('l')) === $weeklyDay;
$accounts = cbOpsRows($conn, "SELECT * FROM admin_social_accounts WHERE is_active = 1 ORDER BY most_active DESC, platform ASC, handle ASC");
$dailyAccounts = array_values(array_filter($accounts, function ($account) {
    return (int) ($account['most_active'] ?? 0) === 1 || strtolower((string) ($account['reminder_frequency'] ?? 'weekly')) === 'daily';
}));

if (!$isWeeklyDay && empty($dailyAccounts)) {
    cbSocialReminderEcho('No daily platform reminders are due today.');
    exit(0);
}

$focusAccounts = $isWeeklyDay ? $accounts : $dailyAccounts;
if (empty($focusAccounts)) {
    cbSocialReminderEcho('No active social accounts found.');
    exit(0);
}

$subject = cbOpsCleanHeader($settings['subject'] ?: 'Sir Francis social posting reminder');
$title = $isWeeklyDay ? 'Weekly social posting reminder' : 'Daily social posting reminder';
$rowsHtml = '';
foreach ($focusAccounts as $account) {
    $profile = trim((string) ($account['profile_url'] ?? ''));
    $profileHtml = $profile !== ''
        ? '<a href="' . cbOpsText($profile) . '" style="color:#28364B;font-weight:700;">Open profile</a>'
        : '<span style="color:#867b73;">No profile link saved</span>';
    $lastPosted = !empty($account['last_posted_at']) ? date('d M Y H:i', strtotime((string) $account['last_posted_at'])) : 'Not recorded';
    $rowsHtml .= '<tr>'
        . '<td style="padding:10px;border-bottom:1px solid #eadfd2;"><strong>' . cbOpsText($account['platform']) . '</strong><br><span style="color:#75675d;">' . cbOpsText($account['handle']) . '</span></td>'
        . '<td style="padding:10px;border-bottom:1px solid #eadfd2;">' . cbOpsText($account['reminder_frequency'] ?: 'weekly') . ((int) $account['most_active'] === 1 ? '<br><span style="color:#138a45;font-weight:700;">Most active platform</span>' : '') . '</td>'
        . '<td style="padding:10px;border-bottom:1px solid #eadfd2;">' . cbOpsText($lastPosted) . '</td>'
        . '<td style="padding:10px;border-bottom:1px solid #eadfd2;">' . $profileHtml . '</td>'
        . '</tr>';
}

$adminLink = 'https://sirfrancis.co.za/admin-sf/social_accounts';
$body = '<div style="font-family:Arial,sans-serif;background:#fff7ec;padding:24px;color:#251810;">'
    . '<div style="max-width:760px;margin:0 auto;background:#fff;border:1px solid #eadfd2;border-radius:10px;overflow:hidden;">'
    . '<div style="background:#2d1739;color:#fff;padding:20px 24px;"><h1 style="margin:0;color:#fcb42f;font-size:24px;">' . cbOpsText($title) . '</h1><p style="margin:8px 0 0;color:#f7eafc;">Keep the business visible: post at least once a week everywhere, and daily on the platforms that bring the most attention.</p></div>'
    . '<div style="padding:22px 24px;">'
    . '<p style="margin-top:0;">Today&apos;s focus is below. Use this as a quick accountability nudge before the day gets busy.</p>'
    . '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #eadfd2;">'
    . '<thead><tr style="background:#fff3d9;"><th align="left" style="padding:10px;">Platform</th><th align="left" style="padding:10px;">Rhythm</th><th align="left" style="padding:10px;">Last posted</th><th align="left" style="padding:10px;">Link</th></tr></thead>'
    . '<tbody>' . $rowsHtml . '</tbody></table>'
    . '<p style="margin:18px 0;"><a href="' . cbOpsText($adminLink) . '" style="background:#5b1178;color:#fff;text-decoration:none;padding:11px 16px;border-radius:6px;font-weight:700;">Open social accounts</a></p>'
    . '<p style="color:#75675d;font-size:13px;margin-bottom:0;">This reminder was generated automatically by the Sir Francis website cron.</p>'
    . '</div></div></div>';

$send = cbCandybirdSendMail($recipient, 'Sir Francis Admin', $subject, $body, [
    'from_name' => 'Sir Francis Admin',
    'reply_to_email' => $GLOBALS['smtp_username1'] ?? '',
    'reply_to_name' => 'Sir Francis',
]);

if (empty($send['success'])) {
    cbSocialReminderEcho('Reminder email failed: ' . ($send['error'] ?? 'Unknown mail error'));
    exit(1);
}

$stmt = $conn->prepare("UPDATE admin_social_reminder_settings SET last_sent_at = NOW(), updated_at = NOW() WHERE id = 1");
if ($stmt) {
    $stmt->execute();
    $stmt->close();
}

cbSocialReminderEcho('Reminder email sent to ' . $recipient . '.');
exit(0);
