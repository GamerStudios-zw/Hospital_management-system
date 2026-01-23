<?php
require_once '../config/database.php';
require_once '../models/Visit.php';
require_once '../models/VitalSigns.php';

class ConsultationController {
    private $db;
    private $visit;
    private $vitals;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->visit = new Visit($this->db);
        $this->vitals = new VitalSigns($this->db);
    }

    // POST: /consultation/start
    // Doctor clicks "Attend" on a patient
    public function startConsultation() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->visit_id) && !empty($data->doctor_id)) {
            $this->visit->id = $data->visit_id;

            if ($this->visit->assignDoctor($data->doctor_id)) {
                http_response_code(200);
                echo json_encode(["message" => "Consultation started."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to start consultation."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data."]);
        }
    }

    // GET: /consultation/vitals?visit_id=123
    // Fetch vitals for the current visit so doctor can see them
    public function getPatientVitals() {
        if (isset($_GET['visit_id'])) {
            $result = $this->vitals->readByVisitId($_GET['visit_id']);

            if ($result) {
                http_response_code(200);
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "No vitals found for this visit."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Visit ID required."]);
        }
    }

    // PUT: /consultation/end
    // Finish the visit
    public function endConsultation() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->visit_id)) {
            $this->visit->id = $data->visit_id;
            // Usually moves to 'pharmacy' or 'completed'
            if ($this->visit->updateStatus('pharmacy')) {
                http_response_code(200);
                echo json_encode(["message" => "Consultation ended. Patient sent to pharmacy."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Error ending consultation."]);
            }
        }
    }
}
?>