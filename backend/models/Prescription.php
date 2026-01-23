<?php
class Prescription {
    private $conn;
    private $table_name = "prescriptions";

    public $id;
    public $visit_id;
    public $medication_name;
    public $dosage;
    public $frequency; // e.g., "3 times a day"
    public $duration;  // e.g., "5 days"
    public $status;    // 'pending', 'dispensed'

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Create Prescription (Doctor Action)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET visit_id = :visit_id,
                      medication_name = :med_name,
                      dosage = :dosage,
                      frequency = :frequency,
                      duration = :duration,
                      status = 'pending'";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->medication_name = htmlspecialchars(strip_tags($this->medication_name));
        $this->dosage = htmlspecialchars(strip_tags($this->dosage));

        // Bind
        $stmt->bindParam(":visit_id", $this->visit_id);
        $stmt->bindParam(":med_name", $this->medication_name);
        $stmt->bindParam(":dosage", $this->dosage);
        $stmt->bindParam(":frequency", $this->frequency);
        $stmt->bindParam(":duration", $this->duration);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // 2. Read Prescriptions by Visit (Pharmacy View)
    public function readByVisitId($visit_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE visit_id = :visit_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":visit_id", $visit_id);
        $stmt->execute();
        return $stmt;
    }

    // 3. Mark as Dispensed (Pharmacist Action)
    public function dispense($id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'dispensed' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
?>