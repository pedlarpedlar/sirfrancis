<?php
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('cbCandybirdLoadMailConfig')) {
    function cbCandybirdLoadMailConfig() {
        $liveConfigPath = '/home/candybirdco/configs_candybird/candybird_config.php';
        if (file_exists($liveConfigPath)) {
            require_once $liveConfigPath;
        } elseif (file_exists(__DIR__ . '/configs/email_config.php')) {
            require_once __DIR__ . '/configs/email_config.php';
        } elseif (file_exists(__DIR__ . '/configs/candybird_config.php')) {
            require_once __DIR__ . '/configs/candybird_config.php';
        }
    }
}

if (!function_exists('cbCandybirdMailAccounts')) {
    function cbCandybirdMailAccounts() {
        cbCandybirdLoadMailConfig();
        $accounts = [];
        foreach ([5, 3, 1, 4, 2] as $index) {
            $userVar = 'smtp_username' . $index;
            $passVar = 'smtp_password' . $index;
            $email = $GLOBALS[$userVar] ?? null;
            $password = $GLOBALS[$passVar] ?? null;
            if (!empty($email) && !empty($password)) {
                $accounts[] = ['email' => $email, 'password' => $password];
            }
        }
        return $accounts;
    }
}

if (!function_exists('cbCandybirdSendMail')) {
    function cbCandybirdSendMail($toEmail, $toName, $subject, $htmlBody, $options = []) {
        cbCandybirdLoadMailConfig();
        if (!filter_var((string) $toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid recipient email address.'];
        }

        $accounts = cbCandybirdMailAccounts();
        if (empty($accounts) || empty($GLOBALS['smtp_server']) || empty($GLOBALS['smtp_port'])) {
            return ['success' => false, 'error' => 'SMTP settings are incomplete.'];
        }

        $lastError = '';
        $altBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", (string) $htmlBody)));
        $mailFallbackFrom = $GLOBALS['smtp_username1'] ?? ($accounts[0]['email'] ?? '');

        if (!empty($options['prefer_mail_transport']) && filter_var((string) $mailFallbackFrom, FILTER_VALIDATE_EMAIL)) {
            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                $mail->Sender = $mailFallbackFrom;
                $mail->XMailer = 'CandyBird Mailer';
                $mail->setFrom($mailFallbackFrom, $options['from_name'] ?? 'CandyBird');
                $mail->addAddress($toEmail, $toName ?: $toEmail);
                if (!empty($options['reply_to_email']) && filter_var($options['reply_to_email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addReplyTo($options['reply_to_email'], $options['reply_to_name'] ?? 'CandyBird');
                } elseif (!empty($GLOBALS['smtp_username1']) && filter_var($GLOBALS['smtp_username1'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addReplyTo($GLOBALS['smtp_username1'], 'CandyBird');
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = $altBody;
                $mail->send();
                return ['success' => true, 'sender' => $mailFallbackFrom, 'transport' => 'mail'];
            } catch (Throwable $e) {
                $lastError = 'mail() preferred route: ' . $e->getMessage();
                error_log('CandyBird preferred mail() route failed via ' . $mailFallbackFrom . ': ' . $e->getMessage());
            }
        }

        foreach ($accounts as $account) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $GLOBALS['smtp_server'];
                $mail->SMTPAuth = true;
                $mail->Username = $account['email'];
                $mail->Password = $account['password'];
                if (!empty($GLOBALS['smtp_type'])) {
                    $mail->SMTPSecure = $GLOBALS['smtp_type'];
                }
                $mail->Port = (int) $GLOBALS['smtp_port'];
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                $mail->Sender = $account['email'];
                $mail->XMailer = 'CandyBird Mailer';
                $mail->setFrom($account['email'], $options['from_name'] ?? 'CandyBird');
                $mail->addAddress($toEmail, $toName ?: $toEmail);
                if (!empty($options['reply_to_email']) && filter_var($options['reply_to_email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addReplyTo($options['reply_to_email'], $options['reply_to_name'] ?? 'CandyBird');
                } elseif (!empty($GLOBALS['smtp_username1']) && filter_var($GLOBALS['smtp_username1'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addReplyTo($GLOBALS['smtp_username1'], 'CandyBird');
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = $altBody;
                $mail->send();
                return ['success' => true, 'sender' => $account['email']];
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                error_log('CandyBird mail send failed via ' . $account['email'] . ': ' . $lastError);
            }
        }

        if (filter_var((string) $mailFallbackFrom, FILTER_VALIDATE_EMAIL)) {
            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                $mail->Sender = $mailFallbackFrom;
                $mail->XMailer = 'CandyBird Mailer';
                $mail->setFrom($mailFallbackFrom, $options['from_name'] ?? 'CandyBird');
                $mail->addAddress($toEmail, $toName ?: $toEmail);
                if (!empty($options['reply_to_email']) && filter_var($options['reply_to_email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addReplyTo($options['reply_to_email'], $options['reply_to_name'] ?? 'CandyBird');
                } elseif (!empty($GLOBALS['smtp_username1']) && filter_var($GLOBALS['smtp_username1'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addReplyTo($GLOBALS['smtp_username1'], 'CandyBird');
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = $altBody;
                $mail->send();
                return ['success' => true, 'sender' => $mailFallbackFrom, 'transport' => 'mail'];
            } catch (Throwable $e) {
                $lastError = trim(($lastError ? $lastError . ' | ' : '') . 'mail() fallback: ' . $e->getMessage());
                error_log('CandyBird mail() fallback failed via ' . $mailFallbackFrom . ': ' . $e->getMessage());
            }
        }

        return ['success' => false, 'error' => $lastError ?: 'All SMTP send attempts failed.'];
    }
}
?>
