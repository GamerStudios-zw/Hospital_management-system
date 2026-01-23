<?php
include_once '../config/database.php';
include_once '../models/Patient.php';

class PatientController {
    private $db;
    private $patient;


public function searchPatient() {
    // Get the search term from URL: /patients/search?q=12345
    $keywords = isset($_GET['q']) ? $_GET['q'] : "";

    // Simple query to search by Name OR ID
    $query = "SELECT * FROM patients
              WHERE name LIKE ? OR contact LIKE ? OR id LIKE ?
              ORDER BY created_at DESC LIMIT 5";

    $stmt = $this->db->prepare($query);

    // Bind parameters (wildcards for partial matching)
    $term = "%{$keywords}%";
    $stmt->bindParam(1, $term);
    $stmt->bindParam(2, $term);
    $stmt->bindParam(3, $keywords); // Exact match for ID usually better, but loose is okay here

    $stmt->execute();

    $patients_arr = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($patients_arr, $row);
    }

    echo json_encode($patients_arr);
}
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->patient = new Patient($this->db);
    }

    public function registerPatient() {
        // Get raw posted data
        $data = json_decode(file_get_contents("php://input"));

        if(!empty($data->name) && !empty($data->contact)) {
            $this->patient->name = $data->name;
            $this->patient->dob = $data->dob;
            $this->patient->gender = $data->gender;
            $this->patient->contact = $data->contact;

            if($this->patient->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Patient was created."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to create patient."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data."]);
        }
    }
}
?>