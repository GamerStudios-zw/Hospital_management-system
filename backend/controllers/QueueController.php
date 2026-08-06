<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Visit.php';

class QueueController {
    private $db;
    private $visit;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->visit = new Visit($this->db);
    }

    // GET: /queue/list
    // Returns list of patients waiting or triaged
    public function getQueue() {
        $stmt = $this->visit->readQueue();
        $visits_arr = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($visits_arr, $row);
        }

        http_response_code(200);
        echo json_encode($visits_arr);
    }

    // POST: /queue/add
    // Receptionist adds a patient to the queue
    public function addToQueue() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->patient_id) && !empty($data->chief_complaint)) {
            $this->visit->patient_id = $data->patient_id;
            $this->visit->chief_complaint = $data->chief_complaint;

            if ($this->visit->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Patient added to queue."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to add patient."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data."]);
        }
    }

    // PUT: /queue/update
    // Update status (e.g. from 'waiting' to 'triaged')
    public function updateStatus() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->visit_id) && !empty($data->status)) {
            $this->visit->id = $data->visit_id;

            if ($this->visit->updateStatus($data->status)) {
                http_response_code(200);
                echo json_encode(["message" => "Status updated."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to update status."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Missing ID or Status."]);
        }
    }
}
?>
