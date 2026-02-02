<?php
// FILE: backend/routes/admin.php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Determine the resource (admin, users, or logs) based on the URL
// Assuming URL structure: /backend/index.php/{resource}/{action}
$resource = isset($segments[0]) ? $segments[0] : '';
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

// --- 1. ADMIN DASHBOARD ACTIONS ---
if ($resource === 'admin') {
    switch ($action) {
        // [GET] /admin/stats
        case 'stats':
            try {
                $doctors = $db->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
                $nurses = $db->query("SELECT COUNT(*) FROM users WHERE role = 'nurse'")->fetchColumn();
                $receptionists = $db->query("SELECT COUNT(*) FROM users WHERE role = 'receptionist'")->fetchColumn();
                $patients = $db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
                $queue = $db->query("SELECT COUNT(*) FROM patient_queue WHERE status != 'Completed'")->fetchColumn();

                echo json_encode([
                    "doctors" => (int)$doctors,
                    "nurses" => (int)$nurses,
                    "receptionists" => (int)$receptionists,
                    "queue" => (int)$queue,
                    "total_patients" => (int)$patients
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Stats Error: " . $e->getMessage()]);
            }
            break;

        // [POST] /admin/add_user
        case 'add_user':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"));

                if (empty($data->full_name) || empty($data->username) || empty($data->role) || empty($data->password)) {
                    http_response_code(400);
                    echo json_encode(["message" => "All fields are required."]);
                    exit;
                }

                try {
                    $check = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $check->execute([$data->username]);
                    if($check->rowCount() > 0) {
                        http_response_code(409); // Conflict
                        echo json_encode(["message" => "Username already exists."]);
                        exit;
                    }

                    $sql = "INSERT INTO users (full_name, username, password, role, is_active) VALUES (?, ?, ?, ?, 1)";
                    $stmt = $db->prepare($sql);

                    // In a real app, use password_hash($data->password, PASSWORD_DEFAULT)
                    if($stmt->execute([$data->full_name, $data->username, $data->password, $data->role])) {
                        // Log the action
                        logActivity($db, 'Admin', "Created user: " . $data->username);
                        echo json_encode(["message" => "User created successfully"]);
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["message" => "Error: " . $e->getMessage()]);
                }
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(["message" => "Admin action not found"]);
            break;
    }
}

// --- 2. USER MANAGEMENT ACTIONS (Matches staff_management.html) ---
else if ($resource === 'users') {
    // [GET] /users (List all staff)
    if ($method === 'GET' && empty($action)) {
        $stmt = $db->query("SELECT id, full_name, username, role, created_at, is_active FROM users ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    // [DELETE] /users?id=X
    else if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                logActivity($db, 'Admin', "Deleted user ID: " . $id);
                echo json_encode(["message" => "User deleted"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID required"]);
        }
    }
    // [POST] /users (Handle Reset Password action)
    else if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"));
        if (isset($data->action) && $data->action === 'reset_password' && isset($data->user_id)) {
            $stmt = $db->prepare("UPDATE users SET password = 'Staff123!' WHERE id = ?");
            if ($stmt->execute([$data->user_id])) {
                logActivity($db, 'Admin', "Reset password for user ID: " . $data->user_id);
                echo json_encode(["message" => "Password reset successfully"]);
            }
        }
    }
}

// --- 3. SYSTEM LOGS (Matches system_logs.html) ---
else if ($resource === 'logs') {
    // [GET] /logs/list
    if ($action === 'list' && $method === 'GET') {
        try {
            // Ensure table exists or fail gracefully
            $stmt = $db->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 100");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            // Return empty array if table doesn't exist yet
            echo json_encode([]);
        }
    }
}

// Helper Function for Logs
function logActivity($db, $user, $action) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $db->prepare("INSERT INTO system_logs (username, action, ip_address, status) VALUES (?, ?, ?, 'Success')");
        $stmt->execute([$user, $action, $ip]);
    } catch (Exception $e) {
        // Silently fail logging if table missing
    }
}
?>