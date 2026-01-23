<?php
require_once '../config/database.php';
require_once '../models/Prescription.php';

class PrescriptionController {
    private $db;
    private $prescription;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->prescription = new Prescription($this->db);
    }

    // POST: /prescription/add
    public function addPrescription() {
        $data = json_decode(file_get_contents("php://input"));

        if (
            !empty($data->visit_id) &&
            !empty($data->medication_name) &&
            !empty($data->dosage)
        ) {
            $this->prescription->visit_id = $data->visit_id;
            $this->prescription->medication_name = $data->medication_name;
            $this->prescription->dosage = $data->dosage;
            $this->prescription->frequency = $data->frequency ?? "As directed";
            $this->prescription->duration = $data->duration ?? "1 week";

            if ($this->prescription->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Prescription added."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to add prescription."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data."]);
        }
    }

    // GET: /prescription/list?visit_id=123
    public function getByVisit() {
        if (isset($_GET['visit_id'])) {
            $stmt = $this->prescription->readByVisitId($_GET['visit_id']);
            $prescriptions = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($prescriptions, $row);
            }

            http_response_code(200);
            echo json_encode($prescriptions);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Visit ID required."]);
        }
    }
}
?>