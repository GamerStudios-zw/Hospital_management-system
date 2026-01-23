<?php
require_once '../config/database.php';

class NotificationService {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
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
        // Basic PHP mail (In production, use PHPMailer/SMTP for reliability)
        $headers = "From: no-reply@hospital-system.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return mail($to_email, $subject, $body, $headers);
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