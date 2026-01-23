<?php
require_once '../config/database.php';

class FingerprintService {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Store a fingerprint hash for a specific patient
     * @param int $patient_id
     * @param string $fingerprint_data (Base64 encoded template from scanner SDK)
     */
    public function registerFingerprint($patient_id, $fingerprint_data) {
        $query = "UPDATE patients SET fingerprint_hash = :hash WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":hash", $fingerprint_data);
        $stmt->bindParam(":id", $patient_id);

        return $stmt->execute();
    }

    /**
     * Identify a patient by scanning their finger.
     * * REAL WORLD NOTE:
     * In a real biometric system, you cannot use simple SQL 'WHERE' for matching templates.
     * You typically need a Biometric SDK (like Neurotechnology or ZKTeco) to compare templates.
     * * However, for a simple implementation, we assume the scanner generates a
     * consistent hash (exact match) or we fetch all patients and compare in PHP loop.
     */
    public function findPatientByFingerprint($scanned_template) {
        // Option 1: Exact Match (Only works if scanner output is identical every time)
        $query = "SELECT * FROM patients WHERE fingerprint_hash = :hash LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hash", $scanned_template);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return null;
    }
}
?>