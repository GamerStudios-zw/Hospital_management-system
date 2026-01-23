<?php
// FILE: backend/controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function login() {
        // 1. Get raw input
        $json = file_get_contents("php://input");
        $data = json_decode($json);

        // 2. Debug: Check if input is received
        if (!$data) {
            http_response_code(400);
            echo json_encode(["message" => "No JSON received", "debug_raw" => $json]);
            return;
        }

        $username = trim($data->username ?? '');
        $password = trim($data->password ?? '');

        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(["message" => "Empty username or password"]);
            return;
        }

        // 3. Direct Manual Database Check (Bypassing User.php to find the bug)
        // We run the query HERE to see exactly what is happening.
        $query = "SELECT * FROM users WHERE username = :u LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':u', $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // CHECK 1: Did we find the user?
        if (!$row) {
            http_response_code(401);
            echo json_encode([
                "message" => "Login Failed",
                "reason" => "User '$username' not found in database"
            ]);
            return;
        }

        // CHECK 2: Does the hash verify?
        if (password_verify($password, $row['password_hash'])) {
            // SUCCESS!
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login Successful",
                "token" => "debug-token-bypassed",
                "user" => [
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "role" => $row['role'],
                    "full_name" => $row['full_name']
                ]
            ]);
        } else {
            // FAILURE - PRINT DEBUG INFO
            http_response_code(401);
            echo json_encode([
                "message" => "Login Failed",
                "reason" => "Password Mismatch",
                "debug_info" => [
                    "input_user" => $username,
                    "input_pass" => $password,
                    "stored_hash_start" => substr($row['password_hash'], 0, 10) . "...",
                    "hash_length" => strlen($row['password_hash'])
                ]
            ]);
        }
    }

    public function logout() {
        echo json_encode(["message" => "Logged out"]);
    }
}
?>