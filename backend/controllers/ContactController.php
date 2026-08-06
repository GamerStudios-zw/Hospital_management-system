<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Contact.php';

class ContactController {
    private $db;
    private $contact;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->contact = new Contact($this->db);
    }

    public function submitInquiry() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->name) && !empty($data->email) && !empty($data->message)) {
            $this->contact->name = $data->name;
            $this->contact->email = $data->email;
            $this->contact->organization = $data->organization ?? ""; // Optional field
            $this->contact->phone = $data->phone ?? ""; // Optional field
            $this->contact->message = $data->message;

            if ($this->contact->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Inquiry sent successfully."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to send inquiry."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Name, Email and Message are required."]);
        }
    }
}
?>
