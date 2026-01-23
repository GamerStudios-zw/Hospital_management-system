<?php
require_once '../config/database.php';
require_once '../models/Prescription.php';
require_once '../models/Medicine.php';

class PharmacyController {
    private $db;
    private $prescription;
    private $medicine;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->prescription = new Prescription($this->db);
        $this->medicine = new Medicine($this->db);
    }

    // POST: /pharmacy/dispense
    // Dispense a specific prescription item
    public function dispenseItem() {
        $data = json_decode(file_get_contents("php://input"));

        // We need prescription ID and the medicine ID to reduce stock from
        if (!empty($data->prescription_id) && !empty($data->medicine_id)) {

            // 1. Mark prescription as dispensed
            $dispensed = $this->prescription->dispense($data->prescription_id);

            // 2. Reduce stock (Assuming 1 unit per prescription for simplicity,
            // or pass 'quantity' in payload)
            $quantity = $data->quantity ?? 1;
            $stockReduced = $this->medicine->reduceStock($data->medicine_id, $quantity);

            if ($dispensed && $stockReduced) {
                http_response_code(200);
                echo json_encode(["message" => "Medicine dispensed and stock updated."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Error dispensing or insufficient stock."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Prescription ID and Medicine ID required."]);
        }
    }
}
?>