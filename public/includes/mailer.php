<?php
/**
 * Thin SMTP mail wrapper around PHPMailer.
 * Requires `composer require phpmailer/phpmailer` (see /vendor).
 * Falls back to returning false (no fatal error) if PHPMailer isn't installed yet,
 * so forms still work in dev before Composer dependencies are pulled in.
 */

require_once __DIR__ . '/helpers.php';

function send_mail(string $toEmail, string $toName, string $subject, string $bodyHtml, ?string $replyToEmail = null, ?string $replyToName = null): bool
{
    $autoload = BASE_PATH . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('send_mail: vendor/autoload.php not found — run `composer require phpmailer/phpmailer`.');
        return false;
    }
    require_once $autoload;

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        error_log('send_mail: PHPMailer class not available.');
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = env('SMTP_HOST', '');
        $mail->SMTPAuth = true;
        $mail->Username = env('SMTP_USER', '');
        $mail->Password = env('SMTP_PASS', '');
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) env('SMTP_PORT', 587);

        $mail->setFrom(env('SMTP_FROM_EMAIL', 'no-reply@localhost'), env('SMTP_FROM_NAME', 'Southeastern Archdeaconry'));
        $mail->addAddress($toEmail, $toName);
        if ($replyToEmail) {
            $mail->addReplyTo($replyToEmail, $replyToName ?? '');
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('send_mail failed: ' . $e->getMessage());
        return false;
    }
}
