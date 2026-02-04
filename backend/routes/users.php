<?php
// FILE: backend/routes/users.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';

$method = $_SERVER['REQUEST_METHOD'];
$database = new Database();
$db = $database->getConnection();

switch ($method) {
    // 1. HANDLE POST REQUESTS (Create User OR Reset Password)
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        // --- A. CHECK FOR CHANGE PASSWORD ACTION ---
        if (isset($data->action) && $data->action === 'change_password') {
            $user = AuthMiddleware::isAuthenticated();
            if (!isset($data->current_password) || !isset($data->new_password)) {
                http_response_code(400);
                echo json_encode(["message" => "Current and new password are required"]);
                exit;
            }

            try {
                $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$user->id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row || !password_verify($data->current_password, $row['password_hash'])) {
                    http_response_code(403);
                    echo json_encode(["message" => "Current password is incorrect"]);
                    exit;
                }

                $hash = password_hash($data->new_password, PASSWORD_BCRYPT);
                $upd = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($upd->execute([$hash, $user->id])) {
                    try {
                        ActivityLogger::log($db, (string)$user->id, 'Changed password', 'Success', null, [
                            'event_type' => 'audit',
                            'entity_type' => 'user',
                            'entity_id' => (string)$user->id,
                            'actor_id' => (string)$user->id,
                            'actor_role' => $user->role ?? null,
                            'source' => 'users/change_password'
                        ]);
                    } catch (Exception $e) {
                        // ignore logging errors
                    }
                    echo json_encode(["message" => "Password updated successfully"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Failed to update password"]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error: " . $e->getMessage()]);
            }
            break; // Exit switch
        }

        // --- B. CHECK FOR RESET PASSWORD ACTION ---
        if (isset($data->action) && $data->action === 'reset_password') {
            $actor = AuthMiddleware::isAuthenticated();
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
                    try {
                        ActivityLogger::log($db, $actor->full_name ?? 'admin', 'Password reset by admin', 'Success', null, [
                            'event_type' => 'audit',
                            'entity_type' => 'user',
                            'entity_id' => (string)$data->user_id,
                            'actor_id' => isset($actor->id) ? (string)$actor->id : null,
                            'actor_role' => $actor->role ?? 'admin',
                            'source' => 'users/reset_password'
                        ]);
                    } catch (Exception $e) {
                        // ignore logging errors
                    }
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

        // --- C. CREATE USER (Original Logic) ---
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
            try {
                $actor = AuthMiddleware::isAuthenticated();
                $newId = $db->lastInsertId();
                ActivityLogger::log($db, $actor->full_name ?? 'admin', "Created user: " . $data->username, 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'user',
                    'entity_id' => $newId ? (string)$newId : null,
                    'actor_id' => isset($actor->id) ? (string)$actor->id : null,
                    'actor_role' => $actor->role ?? 'admin',
                    'source' => 'users/create',
                    'new_values' => [
                        'username' => $data->username,
                        'full_name' => $data->full_name,
                        'role' => $data->role
                    ],
                    'safe_fields' => ['username', 'full_name', 'role']
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }
            echo json_encode(["message" => "User created successfully"]);
        }
        break;

    // 2. LIST USERS
    case 'GET':
        try {
            AuthMiddleware::ensureSessionColumns($db);
            AuthMiddleware::ensureSessionTable($db);
        } catch (Exception $e) {
            // ignore schema ensure errors
        }

        $query = "SELECT
                    u.id,
                    u.full_name,
                    u.username,
                    u.email,
                    u.role,
                    u.is_active,
                    u.session_expires_at,
                    SUM(CASE WHEN (us.expires_at IS NULL OR us.expires_at > NOW()) THEN 1 ELSE 0 END) AS active_sessions
                  FROM users u
                  LEFT JOIN user_sessions us ON us.user_id = u.id
                  GROUP BY u.id, u.full_name, u.username, u.email, u.role, u.is_active, u.session_expires_at
                  ORDER BY u.id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$u) {
            $u['active_sessions'] = (int)($u['active_sessions'] ?? 0);
        }
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
            try {
                $actor = AuthMiddleware::isAuthenticated();
                ActivityLogger::log($db, $actor->full_name ?? 'admin', "Deleted user ID: " . $id, 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'user',
                    'entity_id' => (string)$id,
                    'actor_id' => isset($actor->id) ? (string)$actor->id : null,
                    'actor_role' => $actor->role ?? 'admin',
                    'source' => 'users/delete'
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }
            echo json_encode(["message" => "User deleted"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete"]);
        }
        break;
}
?>
