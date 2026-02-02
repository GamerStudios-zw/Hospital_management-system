<?php
// FILE: backend/controllers/ShiftController.php

require_once __DIR__ . '/../config/database.php';

class ShiftController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Assign a new shift to a staff member
    public function createShift() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->user_id) || !isset($data->shift_start) || !isset($data->shift_end)) {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete shift data."]);
            return;
        }

        try {
            $sql = "INSERT INTO staff_shifts (user_id, shift_start, shift_end, ward_name, status) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            $status = $data->status ?? 'Confirmed';
            $ward = $data->ward_name ?? 'General';

            if ($stmt->execute([$data->user_id, $data->shift_start, $data->shift_end, $ward, $status])) {
                echo json_encode(["message" => "Shift assigned successfully."]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    }

    // Get all shifts (for Admin Roster View)
    public function getAllShifts() {
        try {
            $query = "SELECT s.*, u.full_name, u.role 
                      FROM staff_shifts s 
                      JOIN users u ON s.user_id = u.id 
                      ORDER BY s.shift_start DESC";
            $stmt = $this->db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch roster."]);
        }
    }
}