<?php
/**
 * Mail Configuration for CampusLink
 * 
 * Uses PHPMailer to send emails via Gmail SMTP.
 * 
 * SETUP INSTRUCTIONS:
 * 1. Go to https://myaccount.google.com/apppasswords
 *    (You must have 2-Step Verification enabled on your Google account)
 * 2. Generate an App Password for "Mail"
 * 3. Replace the credentials below with your Gmail + App Password
 */

// ============================================================
// CHANGE THESE TO YOUR GMAIL CREDENTIALS
// ============================================================
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'chunkiy.wong05@gmail.com');       // <-- Your Gmail address
define('MAIL_PASSWORD', 'nfmi rzid uszj wigu');           // <-- 16-char app password
define('MAIL_FROM_NAME', 'CampusLink');
// ============================================================

require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using PHPMailer + Gmail SMTP
 *
 * @param string $toEmail    Recipient email address
 * @param string $toName     Recipient name
 * @param string $subject    Email subject
 * @param string $htmlBody   Email body (HTML)
 * @return array             ['success' => bool, 'error' => string]
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): array {
    $mail = new PHPMailer(true); // true = enable exceptions

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        // Sender & Recipient
        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        // Plain-text fallback for email clients that don't support HTML
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        $mail->send();

        return ['success' => true, 'error' => ''];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}
