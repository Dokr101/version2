<?php
/**
 * email_helper.php — HRMS Email Notification Helper
 *
 * Sends transactional emails using PHPMailer when SMTP credentials
 * are configured in config.local.php. Falls back to logging the
 * message to includes/mail_log.txt for demo/dev environments.
 */

/**
 * Send an email notification.
 *
 * @param string $to        Recipient email address
 * @param string $subject   Email subject
 * @param string $body      HTML body content
 * @return bool             True if sent / logged successfully
 */
function sendNotificationEmail(string $to, string $subject, string $body): bool
{
    // Check if SMTP credentials exist
    $smtpConfigured = defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS')
                      && SMTP_HOST !== '' && SMTP_USER !== '' && SMTP_PASS !== '';

    if ($smtpConfigured) {
        // Attempt to use PHPMailer if installed
        $phpMailerPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($phpMailerPath)) {
            require_once $phpMailerPath;
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;

                $mail->setFrom(SMTP_USER, defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'HRMS');
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("Email send failed: " . $e->getMessage());
                // Fall through to log fallback
            }
        }
    }

    // Fallback: log to file
    $logFile = __DIR__ . '/mail_log.txt';
    $entry  = str_repeat('=', 60) . "\n";
    $entry .= "Date:    " . date('Y-m-d H:i:s') . "\n";
    $entry .= "To:      " . $to . "\n";
    $entry .= "Subject: " . $subject . "\n";
    $entry .= "Body:\n" . strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body)) . "\n";
    $entry .= str_repeat('=', 60) . "\n\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    return true;
}

/**
 * Build a branded HTML email template.
 *
 * @param string $heading   Email heading
 * @param string $content   HTML content block
 * @return string           Complete HTML email
 */
function buildEmailTemplate(string $heading, string $content): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:40px 0;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(135deg,#5c2d91,#7b3fa8);padding:28px 32px;text-align:center;">
          <h1 style="color:#fff;margin:0;font-size:22px;letter-spacing:-.02em;">HRMS</h1>
        </td>
      </tr>
      <!-- Body -->
      <tr>
        <td style="padding:32px;">
          <h2 style="margin:0 0 16px;color:#2d3748;font-size:18px;">{$heading}</h2>
          <div style="color:#4a5568;font-size:14px;line-height:1.7;">
            {$content}
          </div>
        </td>
      </tr>
      <!-- Footer -->
      <tr>
        <td style="background:#f7fafc;padding:18px 32px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="margin:0;color:#a0aec0;font-size:12px;">&copy; HRMS — Hotel Management System</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}
?>
