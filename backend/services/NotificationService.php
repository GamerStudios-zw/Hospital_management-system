<?php
require_once __DIR__ . '/../config/database.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
use PHPMailer\PHPMailer\PHPMailer;

class NotificationService {
    private $conn;
    private $hospitalName = 'Hospital Management System';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->hospitalName = $this->loadHospitalName();
    }

    /**
     * Send an internal system notification (User will see it in their dashboard)
     */
    public function createSystemNotification($user_id, $message, $type = 'info') {
        $query = "INSERT INTO notifications (user_id, message, type, is_read, created_at)
                  VALUES (:uid, :msg, :type, 0, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uid", $user_id);
        $stmt->bindParam(":msg", $message);
        $stmt->bindParam(":type", $type);

        return $stmt->execute();
    }

    /**
     * Send an Email (Wrapper for PHP mail or PHPMailer)
     */
    public function sendEmail($to_email, $subject, $body) {
        $to = trim((string)$to_email);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Email send skipped: invalid recipient '{$to_email}'.");
            return false;
        }
        $themedBody = $this->applyHmsTheme($subject, (string)$body);
        $plainBody = trim(strip_tags($themedBody));
        if ($plainBody === '') {
            $plainBody = trim(strip_tags((string)$subject));
        }

        $smtpHost = trim((string)(getenv('HMS_SMTP_HOST') ?: ''));
        if ($smtpHost !== '' && class_exists(PHPMailer::class)) {
            try {
                $mailer = new PHPMailer(true);
                $mailer->isSMTP();
                $mailer->Host = $smtpHost;
                $mailer->Port = (int)(getenv('HMS_SMTP_PORT') ?: 587);
                $mailer->SMTPAuth = true;
                $mailer->Username = (string)(getenv('HMS_SMTP_USER') ?: '');
                $mailer->Password = (string)(getenv('HMS_SMTP_PASS') ?: '');
                $secure = strtolower(trim((string)(getenv('HMS_SMTP_SECURE') ?: 'tls')));
                $mailer->SMTPSecure = ($secure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mailer->CharSet = 'UTF-8';

                $fromEmail = trim((string)(getenv('HMS_SMTP_FROM') ?: $mailer->Username));
                if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                    $fromEmail = 'no-reply@hospital-system.com';
                }
                $fromName = trim((string)(getenv('HMS_SMTP_FROM_NAME') ?: 'Hospital Management System'));

                $mailer->setFrom($fromEmail, $fromName);
                $mailer->addAddress($to);
                $mailer->isHTML(true);
                $mailer->Subject = (string)$subject;
                $mailer->Body = $themedBody;
                $mailer->AltBody = $plainBody;
                $mailer->send();
                return true;
            } catch (Throwable $e) {
                error_log("SMTP email send failed for {$to}: " . $e->getMessage());
                return false;
            }
        }

        // Fallback to PHP mail() when SMTP is not configured.
        $headers = "From: no-reply@hospital-system.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $ok = @mail($to, $subject, $themedBody, $headers);
        if (!$ok) {
            error_log("Email send failed for {$to}. Check SMTP/php.ini configuration.");
        }
        return $ok;
    }

    private function applyHmsTheme($subject, $bodyHtml) {
        $rawBody = trim((string)$bodyHtml);
        if ($rawBody === '') {
            $rawBody = '<p>No additional details provided.</p>';
        }
        if (stripos($rawBody, 'data-hms-email-shell') !== false) {
            return $rawBody;
        }

        $safeHospital = htmlspecialchars($this->hospitalName, ENT_QUOTES, 'UTF-8');
        $safeSubject = htmlspecialchars((string)$subject, ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        return "<!doctype html>
<html lang=\"en\" data-hms-email-shell=\"1\">
<head>
  <meta charset=\"UTF-8\">
  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
  <title>{$safeSubject}</title>
</head>
<body style=\"margin:0;padding:0;background:#f8f9fa;color:#212529;font-family:'Space Grotesk','Segoe UI',Arial,sans-serif;\">
  <table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"background:#f8f9fa;padding:24px 12px;\">
    <tr>
      <td align=\"center\">
        <table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"max-width:680px;\">
          <tr>
            <td style=\"background:#294a70;border-top:4px solid #e44a3c;padding:18px 22px;color:#ffffff;border-radius:14px 14px 0 0;\">
              <div style=\"font-size:20px;font-weight:700;line-height:1.2;\">{$safeHospital}</div>
              <div style=\"font-size:12px;opacity:0.9;letter-spacing:0.08em;text-transform:uppercase;margin-top:4px;\">HMS Update</div>
            </td>
          </tr>
          <tr>
            <td style=\"background:#ffffff;border:1px solid #e5e7eb;border-top:none;padding:22px;\">
              <h2 style=\"margin:0 0 14px;font-size:20px;line-height:1.3;color:#0f233a;font-family:'Sora','Segoe UI',Arial,sans-serif;\">{$safeSubject}</h2>
              <div style=\"font-size:14px;line-height:1.65;color:#212529;\">{$rawBody}</div>
            </td>
          </tr>
          <tr>
            <td style=\"background:#f1f5f9;border:1px solid #e5e7eb;border-top:none;padding:14px 22px;border-radius:0 0 14px 14px;\">
              <div style=\"font-size:12px;color:#64748b;\">This is an automated message from {$safeHospital} HMS.</div>
              <div style=\"font-size:11px;color:#94a3b8;margin-top:6px;\">&copy; {$year} {$safeHospital}</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>";
    }

    private function loadHospitalName() {
        try {
            if (!$this->conn) return $this->hospitalName;
            $stmt = $this->conn->query("SELECT hospital_name FROM system_settings WHERE id = 1 LIMIT 1");
            $name = trim((string)$stmt->fetchColumn());
            if ($name !== '') {
                return $name;
            }
        } catch (Throwable $e) {
            // ignore settings lookup issues
        }
        return $this->hospitalName;
    }

    /**
     * Send SMS (Placeholder for API integration)
     * You would integrate Twilio, Africa's Talking, or a local gateway here.
     */
    public function sendSMS($phone_number, $message) {
        // Example logic for an SMS API:
        /*
        $apiKey = "YOUR_API_KEY";
        $url = "https://api.sms-provider.com/send?key=$apiKey&to=$phone_number&msg=" . urlencode($message);
        $response = file_get_contents($url);
        return json_decode($response);
        */

        // For now, we just log it to a file
        error_log("SMS to $phone_number: $message");
        return true;
    }
}
?>
