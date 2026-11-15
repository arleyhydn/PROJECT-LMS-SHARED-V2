<?php

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public function isConfigured(): bool
    {
        return !empty($_ENV['SMTP_USERNAME'])
            && !empty($_ENV['SMTP_PASSWORD'])
            && filter_var($_ENV['SMTP_FROM_EMAIL'] ?? $_ENV['SMTP_USERNAME'], FILTER_VALIDATE_EMAIL) !== false;
    }

    public function sendPasswordReset(string $recipient, string $resetLink): bool
    {
        if (!$this->isConfigured()) {
            error_log('Password reset email is unavailable because SMTP settings are incomplete.');
            return false;
        }

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false
            || filter_var($resetLink, FILTER_VALIDATE_URL) === false) {
            error_log('Password reset email was not sent because its recipient or reset link was invalid.');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = (int) ($_ENV['SMTP_PORT'] ?? 587) === 465
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 587);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom(
                $_ENV['SMTP_FROM_EMAIL'] ?? $_ENV['SMTP_USERNAME'],
                $_ENV['SMTP_FROM_NAME'] ?? 'FEU Marikina LMS'
            );
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = 'Reset your FEU Marikina LMS password';

            $safeLink = htmlspecialchars($resetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $mail->Body = '<p>We received a request to reset your FEU Marikina LMS password.</p>'
                . '<p><a href="' . $safeLink . '">Reset your password</a></p>'
                . '<p>This link expires in one hour and can only be used once. '
                . 'If you did not request a reset, you can ignore this email.</p>';
            $mail->AltBody = "Reset your FEU Marikina LMS password using this link: {$resetLink}\n\n"
                . 'This link expires in one hour and can only be used once.';
            $mail->send();

            return true;
        } catch (\Throwable $exception) {
            error_log('Password reset email delivery failed: ' . $exception->getMessage());
            return false;
        }
    }
}
