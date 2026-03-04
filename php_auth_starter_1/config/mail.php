<?php
// =============================================================
// config/mail.php — Email Configuration (PHPMailer)
// =============================================================
// PHPMailer is the most popular PHP email library. It handles
// SMTP authentication, HTML emails, attachments, and more.
//
// Why not use PHP's built-in mail() function?
//  - mail() doesn't support SMTP authentication
//  - mail() often ends up in spam folders
//  - PHPMailer gives us full control and better error messages
//
// Install PHPMailer: composer require phpmailer/phpmailer
// Required version: PHPMailer 6.x (supports PHP 8.0+)
// =============================================================

// Load environment variables so we can read MAIL_* settings
require_once __DIR__ . '/env.php';

// PHPMailer lives in the vendor folder (installed by Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Creates a pre-configured PHPMailer instance.
 *
 * Usage:
 *   $mail = createMailer();
 *   $mail->addAddress('student@example.com', 'Student Name');
 *   $mail->Subject = 'Hello!';
 *   $mail->Body    = '<p>Hi there!</p>';
 *   $mail->send();
 *
 * @return PHPMailer
 * @throws Exception if configuration is wrong
 */
function createMailer(): PHPMailer
{
    // Pass `true` to enable exceptions on errors
    $mail = new PHPMailer(true);

    // Tell PHPMailer to use SMTP (not PHP's mail() function)
    $mail->isSMTP();

    // SMTP server address (from .env)
    $mail->Host = getenv('MAIL_HOST');

    // Enable SMTP authentication (required by most providers)
    $mail->SMTPAuth = true;

    // Login credentials for your SMTP server
    $mail->Username = getenv('MAIL_USERNAME');
    $mail->Password = getenv('MAIL_PASSWORD');

    // Encryption: STARTTLS on port 587, SSL on port 465
    // STARTTLS is recommended for most modern setups
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    // SMTP port number (2525 for Mailtrap, 587 for most providers)
    $mail->Port = (int) getenv('MAIL_PORT');

    // Set the "From" address shown in the recipient's inbox
    $mail->setFrom(
        getenv('MAIL_FROM'),
        getenv('MAIL_FROM_NAME') ?: 'Auth Project'
    );

    // Send emails as HTML (allows styling and formatting)
    $mail->isHTML(true);

    // Character encoding — utf8 handles accented chars, emoji, etc.
    $mail->CharSet = 'UTF-8';

    // In development, show SMTP debug output in the browser
    // Change to SMTP::DEBUG_OFF for production
    if (getenv('APP_ENV') === 'development') {
        // Level 2 shows both client and server conversation
        // Set to 0 to silence debug output
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Change to DEBUG_SERVER to debug email issues
    }

    return $mail;
}

/**
 * Sends a password reset email to the user.
 *
 * @param string $toEmail    Recipient's email address
 * @param string $toName     Recipient's display name
 * @param string $resetLink  The full reset URL (with token)
 * @return bool  true on success, false on failure
 */
function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool
{
    try {
        $mail = createMailer();

        // Who receives this email
        $mail->addAddress($toEmail, $toName);

        $mail->Subject = 'Reset Your Password';

        // HTML email body — students can style this however they like
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #4f46e5;'>Password Reset Request</h2>
                <p>Hi {$toName},</p>
                <p>We received a request to reset your password. Click the button below to choose a new one.</p>
                <p style='margin: 30px 0;'>
                    <a href='{$resetLink}'
                       style='background: #4f46e5; color: white; padding: 12px 24px;
                              text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Reset My Password
                    </a>
                </p>
                <p style='color: #666; font-size: 14px;'>
                    This link expires in <strong>1 hour</strong>.<br>
                    If you didn't request this, simply ignore this email.
                </p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <p style='color: #999; font-size: 12px;'>
                    Or copy this link into your browser:<br>
                    <a href='{$resetLink}' style='color: #4f46e5;'>{$resetLink}</a>
                </p>
            </div>
        ";

        // Plain-text fallback for email clients that don't render HTML
        $mail->AltBody = "Hi {$toName},\n\nReset your password here: {$resetLink}\n\nThis link expires in 1 hour.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the actual error for debugging, but don't expose it to the user
        error_log('Mailer Error: ' . $e->getMessage());
        return false;
    }
}