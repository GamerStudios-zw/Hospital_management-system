<?php
// FILE: backend/routes/settings.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

$action = $segments[1] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure settings table exists
$db->exec("CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY,
    hospital_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    maintenance_mode TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Require admin
$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['admin'], $user);

switch ($action) {
    case 'load':
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
            break;
        }

        $stmt = $db->prepare("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $defaultName = "City General Hospital";
            $defaultEmail = "admin@cityhospital.co.zw";
            $ins = $db->prepare("INSERT INTO system_settings (id, hospital_name, contact_email, maintenance_mode) VALUES (1, ?, ?, 0)");
            $ins->execute([$defaultName, $defaultEmail]);
            $row = [
                "id" => 1,
                "hospital_name" => $defaultName,
                "contact_email" => $defaultEmail,
                "maintenance_mode" => 0,
                "updated_at" => date('Y-m-d H:i:s')
            ];
        }

        echo json_encode($row);
        break;

    case 'update':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"));
        $name = trim($data->hospital_name ?? '');
        $email = trim($data->contact_email ?? '');
        $maintenance = !empty($data->maintenance_mode) ? 1 : 0;

        if ($name === '' || $email === '') {
            http_response_code(400);
            echo json_encode(["message" => "Hospital name and contact email are required."]);
            break;
        }

        $stmt = $db->prepare("INSERT INTO system_settings (id, hospital_name, contact_email, maintenance_mode)
            VALUES (1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE hospital_name = VALUES(hospital_name), contact_email = VALUES(contact_email), maintenance_mode = VALUES(maintenance_mode)");
        $stmt->execute([$name, $email, $maintenance]);

        try {
            ActivityLogger::log($db, $user->full_name ?? 'admin', 'Updated system settings', 'Success', null, [
                'event_type' => 'audit',
                'entity_type' => 'system_settings',
                'entity_id' => '1',
                'actor_id' => isset($user->id) ? (string)$user->id : null,
                'actor_role' => $user->role ?? 'admin',
                'source' => 'settings/update',
                'new_values' => [
                    'hospital_name' => $name,
                    'contact_email' => $email,
                    'maintenance_mode' => (int)$maintenance
                ],
                'safe_fields' => ['hospital_name', 'contact_email', 'maintenance_mode']
            ]);
        } catch (Exception $e) {
            // ignore logging errors
        }

        echo json_encode(["message" => "Settings updated"]);
        break;

    case 'reset_passwords':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
            break;
        }

        $defaultPass = "Staff123!";
        $hash = password_hash($defaultPass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id IS NOT NULL");
        $stmt->execute([$hash]);

        try {
            ActivityLogger::log($db, $user->full_name ?? 'admin', 'Reset all passwords', 'Success', null, [
                'event_type' => 'audit',
                'entity_type' => 'user',
                'entity_id' => 'all',
                'actor_id' => isset($user->id) ? (string)$user->id : null,
                'actor_role' => $user->role ?? 'admin',
                'source' => 'settings/reset_passwords'
            ]);
        } catch (Exception $e) {
            // ignore logging errors
        }

        echo json_encode(["message" => "All passwords reset to " . $defaultPass]);
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Settings endpoint not found"]);
        break;
}
?>
