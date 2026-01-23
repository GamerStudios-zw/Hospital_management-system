<?php
class VitalSigns {
    private $conn;
    private $table_name = "vital_signs";

    public $id;
    public $visit_id;
    public $patient_id;
    public $temperature;
    public $blood_pressure;
    public $pulse_rate;
    public $weight;
    public $height;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Add Vitals (Nurse Action)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET visit_id = :visit_id,
                      patient_id = :patient_id,
                      temperature = :temp,
                      blood_pressure = :bp,
                      pulse_rate = :pulse,
                      weight = :weight,
                      height = :height";

        $stmt = $this->conn->prepare($query);

        // Bind Data
        $stmt->bindParam(":visit_id", $this->visit_id);
        $stmt->bindParam(":patient_id", $this->patient_id);
        $stmt->bindParam(":temp", $this->temperature);
        $stmt->bindParam(":bp", $this->blood_pressure);
        $stmt->bindParam(":pulse", $this->pulse_rate);
        $stmt->bindParam(":weight", $this->weight);
        $stmt->bindParam(":height", $this->height);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // 2. Get Vitals by Visit ID (Doctor View)
    public function readByVisitId($visit_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE visit_id = :visit_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":visit_id", $visit_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>