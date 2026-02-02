<?php
// FILE: backend/routes/logs.php
ob_start(); // Buffer output to prevent premature headers
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'list':
        if ($method === 'GET') {
            try {
                $query = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                ob_clean(); // Clear any accidental PHP warnings before sending JSON
                echo json_encode($logs ?: []);
            } catch (Exception $e) {
                ob_clean();
                http_response_code(500);
                echo json_encode(["message" => "Database error: " . $e->getMessage()]);
            }
        }
        break;

    // 2. CREATE NEW LOG (Internal Use)
    case 'create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if(!isset($data->username) || !isset($data->action)) {
                http_response_code(400);
                echo json_encode(["message" => "Missing log data"]);
                exit;
            }

            try {
                $sql = "INSERT INTO activity_logs (username, action, ip_address, status) VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($sql);

                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $status = $data->status ?? 'Success';

                if($stmt->execute([$data->username, $data->action, $ip, $status])) {
                    echo json_encode(["message" => "Log saved"]);
                } else {
                    throw new Exception("Execute failed");
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => $e->getMessage()]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Logs endpoint not found"]);
        break;
}
?>