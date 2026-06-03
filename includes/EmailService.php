<?php
/**
 * KABUTO ESPORTS - Email Service (PHPMailer-compatible SMTP)
 * Uses PHP's native mail() as fallback; intended for SMTP via Hostinger.
 */

require_once __DIR__ . '/../config/config.php';

class EmailService
{
    /**
     * Send email via SMTP using cURL/socket or native mail().
     * For production, replace with PHPMailer library.
     * 
     * IMPORTANT: Install PHPMailer via Composer:
     *   composer require phpmailer/phpmailer
     * 
     * Then replace this class body with PHPMailer implementation.
     */

    /**
     * Send registration confirmation email.
     */
    public static function sendRegistrationConfirmation(array $reg, array $tournament): bool
    {
        $to      = $reg['email'];
        $subject = 'Registration Confirmed - ' . $tournament['name'] . ' | Kabuto Esports';

        $paymentInfo = $reg['payment_status'] === 'free'
            ? '<span style="color:#4ade80;font-weight:bold;">FREE ENTRY ✓</span>'
            : match($reg['payment_status']) {
                'paid'    => '<span style="color:#4ade80;font-weight:bold;">PAID ✓</span>',
                'pending' => '<span style="color:#fbbf24;font-weight:bold;">PENDING (Payment Awaited)</span>',
                default   => '<span style="color:#f87171;font-weight:bold;">FAILED</span>',
            };

        $body = self::getEmailTemplate([
            'title'           => 'Registration Confirmed!',
            'registration_id' => $reg['registration_id'],
            'team_name'       => htmlspecialchars($reg['team_name']),
            'tournament_name' => htmlspecialchars($tournament['name']),
            'leader_name'     => htmlspecialchars($reg['leader_name']),
            'payment_info'    => $paymentInfo,
            'tournament_date' => date('d M Y', strtotime($tournament['tournament_start'] ?? 'now')),
            'tournament_url'  => APP_URL . '/tournament/' . $tournament['slug'],
        ]);

        return self::send($to, $subject, $body);
    }

    /**
     * Send admin notification for new registration.
     */
    public static function sendAdminNotification(array $reg, array $tournament): bool
    {
        $to      = ADMIN_NOTIFY_EMAIL;
        $subject = '[New Registration] ' . $reg['team_name'] . ' - ' . $tournament['name'];

        $body = '
        <div style="font-family:Arial,sans-serif;padding:20px;background:#1a1a2e;color:#fff;border-radius:8px;">
            <h2 style="color:#f5a623;">New Tournament Registration</h2>
            <table style="width:100%;border-collapse:collapse;">
                <tr><td style="padding:8px;border-bottom:1px solid #333;"><strong>Registration ID:</strong></td><td style="padding:8px;border-bottom:1px solid #333;">' . htmlspecialchars($reg['registration_id']) . '</td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #333;"><strong>Tournament:</strong></td><td style="padding:8px;border-bottom:1px solid #333;">' . htmlspecialchars($tournament['name']) . '</td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #333;"><strong>Team:</strong></td><td style="padding:8px;border-bottom:1px solid #333;">' . htmlspecialchars($reg['team_name']) . '</td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #333;"><strong>Leader:</strong></td><td style="padding:8px;border-bottom:1px solid #333;">' . htmlspecialchars($reg['leader_name']) . '</td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #333;"><strong>Email:</strong></td><td style="padding:8px;border-bottom:1px solid #333;">' . htmlspecialchars($reg['email']) . '</td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #333;"><strong>Mobile:</strong></td><td style="padding:8px;border-bottom:1px solid #333;">' . htmlspecialchars($reg['mobile']) . '</td></tr>
                <tr><td style="padding:8px;"><strong>Payment Status:</strong></td><td style="padding:8px;">' . htmlspecialchars($reg['payment_status']) . '</td></tr>
            </table>
            <p style="margin-top:20px;"><a href="' . APP_URL . '/admin/registrations.php" style="background:#f5a623;color:#0a0a0f;padding:10px 20px;text-decoration:none;border-radius:4px;font-weight:bold;">View in Admin Panel</a></p>
        </div>';

        return self::send($to, $subject, $body);
    }

    /**
     * Core email sender using PHP mail() with HTML headers.
     * Replace with PHPMailer for SMTP support.
     */
    private static function send(string $to, string $subject, string $htmlBody): bool
    {
        // --- PHPMailer Implementation (uncomment when composer is available) ---
        /*
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\SMTP;
        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;

            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Email send error: ' . $e->getMessage());
            return false;
        }
        */

        // Fallback: native PHP mail()
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>' . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();

        try {
            return mail($to, $subject, $htmlBody, $headers);
        } catch (Exception $e) {
            error_log('Email error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build a professional HTML email template.
     */
    private static function getEmailTemplate(array $data): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($data['title']) . '</title>
</head>
<body style="margin:0;padding:0;background-color:#0a0a0f;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0f;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:16px;overflow:hidden;border:1px solid #2a2a4e;">

  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#f5a623,#e8940a);padding:30px 40px;text-align:center;">
    <h1 style="margin:0;color:#0a0a0f;font-size:28px;font-weight:900;letter-spacing:2px;">⚔️ KABUTO ESPORTS</h1>
    <p style="margin:8px 0 0;color:#0a0a0f;opacity:0.8;font-size:14px;">Tournament Registration Platform</p>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:40px;">
    <h2 style="color:#f5a623;margin:0 0 24px;font-size:22px;">' . htmlspecialchars($data['title']) . '</h2>

    <div style="background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.3);border-radius:12px;padding:20px;margin-bottom:24px;text-align:center;">
      <p style="color:#9ca3af;margin:0 0 4px;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Registration ID</p>
      <p style="color:#f5a623;margin:0;font-size:24px;font-weight:900;letter-spacing:3px;">' . htmlspecialchars($data['registration_id']) . '</p>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0">
      <tr><td style="padding:12px 0;border-bottom:1px solid #2a2a4e;color:#9ca3af;font-size:14px;">Team Name</td>
          <td style="padding:12px 0;border-bottom:1px solid #2a2a4e;color:#fff;font-size:14px;text-align:right;font-weight:600;">' . $data['team_name'] . '</td></tr>
      <tr><td style="padding:12px 0;border-bottom:1px solid #2a2a4e;color:#9ca3af;font-size:14px;">Tournament</td>
          <td style="padding:12px 0;border-bottom:1px solid #2a2a4e;color:#fff;font-size:14px;text-align:right;font-weight:600;">' . $data['tournament_name'] . '</td></tr>
      <tr><td style="padding:12px 0;border-bottom:1px solid #2a2a4e;color:#9ca3af;font-size:14px;">Team Leader</td>
          <td style="padding:12px 0;border-bottom:1px solid #2a2a4e;color:#fff;font-size:14px;text-align:right;font-weight:600;">' . $data['leader_name'] . '</td></tr>
      <tr><td style="padding:12px 0;border-bottom:1px solid #2a2a4e;color:#9ca3af;font-size:14px;">Tournament Date</td>
          <td style="padding:12px 0;border-bottom:1px solid #2a2a4e;color:#fff;font-size:14px;text-align:right;font-weight:600;">' . $data['tournament_date'] . '</td></tr>
      <tr><td style="padding:12px 0;color:#9ca3af;font-size:14px;">Payment Status</td>
          <td style="padding:12px 0;text-align:right;">' . $data['payment_info'] . '</td></tr>
    </table>

    <div style="margin-top:32px;text-align:center;">
      <a href="' . $data['tournament_url'] . '" style="display:inline-block;background:linear-gradient(135deg,#f5a623,#e8940a);color:#0a0a0f;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;letter-spacing:0.5px;">View Tournament Details</a>
    </div>

    <p style="color:#6b7280;font-size:13px;margin-top:32px;line-height:1.6;">
      Keep this email for your records. Join our Discord server for match schedules, room IDs, and announcements.
    </p>
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#0d0d1a;padding:20px 40px;text-align:center;border-top:1px solid #2a2a4e;">
    <p style="color:#4b5563;margin:0;font-size:12px;">© 2026 Kabuto Esports · kabutoesports.com</p>
    <p style="color:#4b5563;margin:4px 0 0;font-size:12px;">This is an automated message. Please do not reply.</p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>';
    }
}
