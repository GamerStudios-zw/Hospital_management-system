<?php
class Visit {
    private $conn;
    private $table_name = "visits";

    // Properties matching database columns
    public $id;
    public $patient_id;
    public $doctor_id;
    public $status; // 'waiting', 'triaged', 'in_consultation', 'pharmacy', 'completed'
    public $chief_complaint;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Create a new Visit (Receptionist adds patient to queue)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET patient_id = :patient_id,
                      chief_complaint = :chief_complaint,
                      status = 'waiting'";

        $stmt = $this->conn->prepare($query);

        $this->chief_complaint = htmlspecialchars(strip_tags($this->chief_complaint));

        $stmt->bindParam(":patient_id", $this->patient_id);
        $stmt->bindParam(":chief_complaint", $this->chief_complaint);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // 2. Read Active Queue (Patients waiting for nurse or doctor)
    public function readQueue() {
        // Joins with patients table to get names
        $query = "SELECT v.id, v.patient_id, v.status, v.chief_complaint, v.created_at, p.name as patient_name
                  FROM " . $this->table_name . " v
                  LEFT JOIN patients p ON v.patient_id = p.id
                  WHERE v.status IN ('waiting', 'triaged')
                  ORDER BY v.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 3. Update Status (e.g., Nurse finishes triage -> 'triaged')
    public function updateStatus($new_status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $new_status);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // 4. Assign Doctor (When doctor clicks 'Start Consultation')
    public function assignDoctor($doctor_id) {
        $query = "UPDATE " . $this->table_name . "
                  SET doctor_id = :doctor_id, status = 'in_consultation'
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }
}
?>