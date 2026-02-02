<?php
// FILE: backend/routes/users.php

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$database = new Database();
$db = $database->getConnection();

switch ($method) {
    // 1. HANDLE POST REQUESTS (Create User OR Reset Password)
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        // --- A. CHECK FOR RESET PASSWORD ACTION ---
        if (isset($data->action) && $data->action === 'reset_password') {
            if (!isset($data->user_id)) {
                http_response_code(400);
                echo json_encode(["message" => "User ID required"]);
                exit;
            }

            try {
                $default_pass = "Staff123!";
                $hash = password_hash($default_pass, PASSWORD_BCRYPT);

                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([$hash, $data->user_id])) {
                    echo json_encode(["message" => "Password reset to '$default_pass'"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Database error"]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error: " . $e->getMessage()]);
            }
            break; // Exit switch
        }

        // --- B. CREATE USER (Original Logic) ---
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

    // 2. LIST USERS
    case 'GET':
        $query = "SELECT id, full_name, username, email, role, is_active FROM users ORDER BY id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
        break;

    // 3. DELETE USER
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