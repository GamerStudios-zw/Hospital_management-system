<?php
// FILE: backend/routes/logs.php
ob_start(); // Buffer output to prevent premature headers
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['admin'], $user);

ActivityLogger::ensureTables($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'list':
        if ($method === 'GET') {
            try {
                $filters = [
                    'start' => $_GET['start'] ?? null,
                    'end' => $_GET['end'] ?? null,
                    'username' => $_GET['username'] ?? null,
                    'event_type' => $_GET['event_type'] ?? null,
                    'entity_type' => $_GET['entity_type'] ?? null,
                    'entity_id' => $_GET['entity_id'] ?? null,
                    'actor_id' => $_GET['actor_id'] ?? null,
                    'actor_role' => $_GET['actor_role'] ?? null,
                    'status' => $_GET['status'] ?? null,
                    'severity' => $_GET['severity'] ?? null,
                    'source' => $_GET['source'] ?? null,
                    'request_id' => $_GET['request_id'] ?? null,
                    'q' => $_GET['q'] ?? null
                ];
                $includeAudit = isset($_GET['include_audit']) ? (int)$_GET['include_audit'] === 1 : true;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
                $limit = max(1, min(500, $limit));

                $fields = "id, username, action, ip_address, status, event_type, entity_type, entity_id, actor_id, actor_role, source, request_id, session_id, severity, duration_ms, status_code, error_code, error_message, user_agent, device_type, client_ip, hostname, created_at";
                $selectActivity = "SELECT 'activity' AS log_bucket, {$fields} FROM activity_logs";
                $selectAudit = "SELECT 'audit' AS log_bucket, {$fields} FROM audit_logs";

                $where = [];
                $params = [];

                if (!empty($filters['start'])) {
                    $where[] = "created_at >= ?";
                    $params[] = $filters['start'];
                }
                if (!empty($filters['end'])) {
                    $where[] = "created_at <= ?";
                    $params[] = $filters['end'];
                }
                if (!empty($filters['username'])) {
                    $where[] = "username = ?";
                    $params[] = $filters['username'];
                }
                if (!empty($filters['event_type'])) {
                    $where[] = "event_type = ?";
                    $params[] = $filters['event_type'];
                }
                if (!empty($filters['entity_type'])) {
                    $where[] = "entity_type = ?";
                    $params[] = $filters['entity_type'];
                }
                if (!empty($filters['entity_id'])) {
                    $where[] = "entity_id = ?";
                    $params[] = $filters['entity_id'];
                }
                if (!empty($filters['actor_id'])) {
                    $where[] = "actor_id = ?";
                    $params[] = $filters['actor_id'];
                }
                if (!empty($filters['actor_role'])) {
                    $where[] = "actor_role = ?";
                    $params[] = $filters['actor_role'];
                }
                if (!empty($filters['status'])) {
                    $where[] = "status = ?";
                    $params[] = $filters['status'];
                }
                if (!empty($filters['severity'])) {
                    $where[] = "severity = ?";
                    $params[] = $filters['severity'];
                }
                if (!empty($filters['source'])) {
                    $where[] = "source = ?";
                    $params[] = $filters['source'];
                }
                if (!empty($filters['request_id'])) {
                    $where[] = "request_id = ?";
                    $params[] = $filters['request_id'];
                }
                if (!empty($filters['q'])) {
                    $where[] = "(action LIKE ? OR username LIKE ? OR event_type LIKE ? OR entity_type LIKE ? OR entity_id LIKE ? OR error_message LIKE ?)";
                    $like = "%" . $filters['q'] . "%";
                    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
                }

                $whereSql = $where ? (" WHERE " . implode(" AND ", $where)) : "";
                $query = $selectActivity . $whereSql;
                $queryParams = $params;

                if ($includeAudit) {
                    $query = "(" . $selectActivity . $whereSql . ") UNION ALL (" . $selectAudit . $whereSql . ")";
                    $queryParams = array_merge($params, $params);
                }

                $query = "SELECT * FROM (" . $query . ") AS logs ORDER BY created_at DESC LIMIT " . $limit;
                $stmt = $db->prepare($query);
                $stmt->execute($queryParams);
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

    case 'export':
        if ($method === 'GET') {
            try {
                $filters = [
                    'start' => $_GET['start'] ?? null,
                    'end' => $_GET['end'] ?? null,
                    'username' => $_GET['username'] ?? null,
                    'event_type' => $_GET['event_type'] ?? null,
                    'entity_type' => $_GET['entity_type'] ?? null,
                    'entity_id' => $_GET['entity_id'] ?? null,
                    'actor_id' => $_GET['actor_id'] ?? null,
                    'actor_role' => $_GET['actor_role'] ?? null,
                    'status' => $_GET['status'] ?? null,
                    'severity' => $_GET['severity'] ?? null,
                    'source' => $_GET['source'] ?? null,
                    'request_id' => $_GET['request_id'] ?? null,
                    'q' => $_GET['q'] ?? null
                ];
                $includeAudit = isset($_GET['include_audit']) ? (int)$_GET['include_audit'] === 1 : true;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
                $limit = max(1, min(1000, $limit));

                $fields = "id, username, action, ip_address, status, event_type, entity_type, entity_id, actor_id, actor_role, source, request_id, session_id, severity, duration_ms, status_code, error_code, error_message, user_agent, device_type, client_ip, hostname, created_at";
                $selectActivity = "SELECT 'activity' AS log_bucket, {$fields} FROM activity_logs";
                $selectAudit = "SELECT 'audit' AS log_bucket, {$fields} FROM audit_logs";

                $where = [];
                $params = [];

                if (!empty($filters['start'])) {
                    $where[] = "created_at >= ?";
                    $params[] = $filters['start'];
                }
                if (!empty($filters['end'])) {
                    $where[] = "created_at <= ?";
                    $params[] = $filters['end'];
                }
                if (!empty($filters['username'])) {
                    $where[] = "username = ?";
                    $params[] = $filters['username'];
                }
                if (!empty($filters['event_type'])) {
                    $where[] = "event_type = ?";
                    $params[] = $filters['event_type'];
                }
                if (!empty($filters['entity_type'])) {
                    $where[] = "entity_type = ?";
                    $params[] = $filters['entity_type'];
                }
                if (!empty($filters['entity_id'])) {
                    $where[] = "entity_id = ?";
                    $params[] = $filters['entity_id'];
                }
                if (!empty($filters['actor_id'])) {
                    $where[] = "actor_id = ?";
                    $params[] = $filters['actor_id'];
                }
                if (!empty($filters['actor_role'])) {
                    $where[] = "actor_role = ?";
                    $params[] = $filters['actor_role'];
                }
                if (!empty($filters['status'])) {
                    $where[] = "status = ?";
                    $params[] = $filters['status'];
                }
                if (!empty($filters['severity'])) {
                    $where[] = "severity = ?";
                    $params[] = $filters['severity'];
                }
                if (!empty($filters['source'])) {
                    $where[] = "source = ?";
                    $params[] = $filters['source'];
                }
                if (!empty($filters['request_id'])) {
                    $where[] = "request_id = ?";
                    $params[] = $filters['request_id'];
                }
                if (!empty($filters['q'])) {
                    $where[] = "(action LIKE ? OR username LIKE ? OR event_type LIKE ? OR entity_type LIKE ? OR entity_id LIKE ? OR error_message LIKE ?)";
                    $like = "%" . $filters['q'] . "%";
                    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
                }

                $whereSql = $where ? (" WHERE " . implode(" AND ", $where)) : "";
                $query = $selectActivity . $whereSql;
                $queryParams = $params;

                if ($includeAudit) {
                    $query = "(" . $selectActivity . $whereSql . ") UNION ALL (" . $selectAudit . $whereSql . ")";
                    $queryParams = array_merge($params, $params);
                }

                $query = "SELECT * FROM (" . $query . ") AS logs ORDER BY created_at DESC LIMIT " . $limit;
                $stmt = $db->prepare($query);
                $stmt->execute($queryParams);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                ob_clean();
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="system_logs.csv"');

                $out = fopen('php://output', 'w');
                if ($out) {
                    if (!empty($rows)) {
                        fputcsv($out, array_keys($rows[0]));
                        foreach ($rows as $row) {
                            fputcsv($out, $row);
                        }
                    } else {
                        fputcsv($out, ['message']);
                        fputcsv($out, ['No logs found']);
                    }
                    fclose($out);
                }
            } catch (Exception $e) {
                ob_clean();
                http_response_code(500);
                echo json_encode(["message" => "Export error: " . $e->getMessage()]);
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
                $context = isset($data->context) && is_object($data->context) ? (array)$data->context : [];
                ActivityLogger::log(
                    $db,
                    $data->username,
                    $data->action,
                    $data->status ?? 'Success',
                    null,
                    $context
                );
                echo json_encode(["message" => "Log saved"]);
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
