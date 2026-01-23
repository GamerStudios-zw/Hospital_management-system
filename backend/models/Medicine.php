<?php
class Medicine {
    private $conn;
    private $table_name = "medicines";

    // Properties matching database columns
    public $id;
    public $name;
    public $description;
    public $category;       // e.g., 'Antibiotic', 'Painkiller', 'Syrup'
    public $price;
    public $stock_quantity;
    public $expiry_date;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Add New Medicine (Inventory Manager)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET name = :name,
                      description = :description,
                      category = :category,
                      price = :price,
                      stock_quantity = :stock,
                      expiry_date = :expiry";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->category = htmlspecialchars(strip_tags($this->category));

        // Bind
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock", $this->stock_quantity);
        $stmt->bindParam(":expiry", $this->expiry_date);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // 2. Read All Medicines (For Pharmacy/Doctor List)
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 3. Search Medicine (For Doctor Prescription Autocomplete)
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE name LIKE ? OR category LIKE ?
                  ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);

        $keywords = htmlspecialchars(strip_tags($keywords));
        $term = "%{$keywords}%";

        $stmt->bindParam(1, $term);
        $stmt->bindParam(2, $term);

        $stmt->execute();
        return $stmt;
    }

    // 4. Reduce Stock (Called when Prescription is Dispensed)
    public function reduceStock($id, $quantity_dispensed) {
        // First check current stock
        $queryCheck = "SELECT stock_quantity FROM " . $this->table_name . " WHERE id = :id";
        $stmtCheck = $this->conn->prepare($queryCheck);
        $stmtCheck->bindParam(":id", $id);
        $stmtCheck->execute();
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if($row && $row['stock_quantity'] >= $quantity_dispensed) {
            // Proceed to reduce
            $query = "UPDATE " . $this->table_name . "
                      SET stock_quantity = stock_quantity - :qty
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":qty", $quantity_dispensed);
            $stmt->bindParam(":id", $id);

            return $stmt->execute();
        }
        return false; // Not enough stock or medicine not found
    }

    // 5. Get Single Medicine Details
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->price = $row['price'];
            $this->stock_quantity = $row['stock_quantity'];
            return true;
        }
        return false;
    }
}
?>