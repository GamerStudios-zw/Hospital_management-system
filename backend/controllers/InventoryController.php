<?php
require_once '../config/database.php';
require_once '../models/Medicine.php';

class InventoryController {
    private $db;
    private $medicine;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->medicine = new Medicine($this->db);
    }

    // GET: /inventory/list
    public function listInventory() {
        $stmt = $this->medicine->read();
        $meds = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($meds, $row);
        }

        http_response_code(200);
        echo json_encode($meds);
    }

    // POST: /inventory/add
    public function addMedicine() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->name) && !empty($data->price)) {
            $this->medicine->name = $data->name;
            $this->medicine->description = $data->description ?? "";
            $this->medicine->category = $data->category ?? "General";
            $this->medicine->price = $data->price;
            $this->medicine->stock_quantity = $data->stock_quantity ?? 0;
            $this->medicine->expiry_date = $data->expiry_date ?? null;

            if ($this->medicine->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Medicine added to inventory."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to add medicine."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Name and Price are required."]);
        }
    }

    // GET: /inventory/search?q=aspirin
    public function searchMedicine() {
        if (isset($_GET['q'])) {
            $stmt = $this->medicine->search($_GET['q']);
            $meds = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($meds, $row);
            }
            echo json_encode($meds);
        }
    }
}
?>