<?php
// FILE: backend/routes/users.php

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$database = new Database();
$db = $database->getConnection();

switch ($method) {
    // 1. CREATE USER (You already have this)
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->username)) {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
            exit();
        }

        $hash = password_hash($data->password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (full_name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);

        if($stmt->execute([$data->full_name, $data->username, $data->email, $hash, $data->role])) {
            http_response_code(201);
            echo json_encode(["message" => "User created successfully"]);
        }
        break;

    // 2. LIST USERS (New Feature)
    case 'GET':
        // Select all users, ordered by newest first
        $query = "SELECT id, full_name, email, role, is_active FROM users ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
        break;

    // 3. DELETE USER (New Feature)
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if(!$id) {
            http_response_code(400);
            echo json_encode(["message" => "No ID provided"]);
            exit;
        }
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        if($stmt->execute([$id])) {
            echo json_encode(["message" => "User deleted"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete"]);
        }
        break;
}
?>