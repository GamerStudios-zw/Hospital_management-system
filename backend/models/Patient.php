<?php
class Patient {
    private $conn;
    private $table_name = "patients";

    public $id;
    public $name;
    public $dob;
    public $gender;
    public $contact;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new patient
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, dob=:dob, gender=:gender, contact=:contact";
        $stmt = $this->conn->prepare($query);

        // Sanitize and Bind
        $this->name = htmlspecialchars(strip_tags($this->name));
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":dob", $this->dob);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":contact", $this->contact);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read patients (for search or list)
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>