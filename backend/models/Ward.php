<?php
class Ward {
    private $conn;
    private $table_name = "wards";

    public $id;
    public $name;
    public $capacity;
    public $notes;
    public $is_active;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name = :name, capacity = :capacity, notes = :notes, is_active = :active";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $active = empty($this->is_active) ? 0 : 1;

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':capacity', $this->capacity);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':active', $active);

        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update($id) {
        $query = "UPDATE " . $this->table_name . " SET name = :name, capacity = :capacity, notes = :notes, is_active = :active WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $active = empty($this->is_active) ? 0 : 1;

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':capacity', $this->capacity);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':active', $active);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
}
?>
