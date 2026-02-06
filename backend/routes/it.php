<?php
// FILE: backend/routes/it.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';
require_once __DIR__ . '/../utils/DbSchema.php';
require_once __DIR__ . '/../services/NotificationService.php';

$database = new Database();
$db = $database->getConnection();
DbSchema::ensureITModules($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('html_errors', '0');
ob_start();

$user = AuthMiddleware::isAuthenticated();
$normalizeRole = function ($r) {
    return str_replace([' ', '-'], '_', strtolower(trim((string)$r)));
};

$requireIt = function () use ($user) {
    RoleMiddleware::allow(['it_support', 'admin'], $user);
};

function it_uptime_info() {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $file = $logDir . '/it_uptime.json';
    $startedAt = null;
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $json = $raw ? json_decode($raw, true) : null;
        $startedAt = $json['started_at'] ?? null;
    }
    if (!$startedAt) {
        $startedAt = time();
        @file_put_contents($file, json_encode(['started_at' => $startedAt]));
    }
    $seconds = max(0, time() - (int)$startedAt);
    $hours = floor($seconds / 3600);
    $mins = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return [
        'started_at' => date('c', (int)$startedAt),
        'uptime_seconds' => $seconds,
        'uptime_human' => sprintf('%dh %dm %ds', $hours, $mins, $secs)
    ];
}

switch ($action) {
    case 'overview':
        $requireIt();
        if ($method === 'GET') {
            $uptime = it_uptime_info();
            $activeUsers = 0;
            $queueCount = 0;
            $expired = 0;
            $expiringSoon = 0;
            $errors = [];
            $tickets = [];

            try {
                $activeUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active = 1 AND current_session_id IS NOT NULL AND (session_expires_at IS NULL OR session_expires_at >= NOW())")->fetchColumn();
            } catch (Exception $e) {
                $activeUsers = 0;
            }

            try {
                $queueCount = (int)$db->query("SELECT COUNT(*) FROM patient_queue WHERE status NOT IN ('Completed','completed','Cancelled','cancelled')")->fetchColumn();
            } catch (Exception $e) {
                $queueCount = 0;
            }

            try {
                $stockStmt = $db->query("SELECT
                    SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired,
                    SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 1 ELSE 0 END) AS expiring
                  FROM medicines");
                $stockRow = $stockStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $expired = (int)($stockRow['expired'] ?? 0);
                $expiringSoon = (int)($stockRow['expiring'] ?? 0);
            } catch (Exception $e) {
                $expired = 0;
                $expiringSoon = 0;
            }

            try {
                ActivityLogger::ensureTables($db);
                $errStmt = $db->query("SELECT created_at, action, status, severity, error_message, source
                                       FROM audit_logs
                                       WHERE (severity IN ('WARN','ERROR') OR status <> 'Success' OR status_code >= 400)
                                       ORDER BY created_at DESC
                                       LIMIT 10");
                $errors = $errStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $errors = [];
            }

            try {
                $tStmt = $db->query("SELECT id, title, status, priority, created_at
                                     FROM it_tickets
                                     ORDER BY created_at DESC
                                     LIMIT 10");
                $tickets = $tStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $tickets = [];
            }

            if (ob_get_length()) { @ob_clean(); }
            echo json_encode([
                'uptime' => $uptime,
                'active_users' => $activeUsers,
                'queue_count' => $queueCount,
                'stock_alerts' => [
                    'expired' => $expired,
                    'expiring_soon' => $expiringSoon
                ],
                'recent_errors' => $errors,
                'recent_tickets' => $tickets
            ]);
        }
        break;

    case 'tickets':
        $requireIt();
        if ($method === 'GET') {
            $status = trim($_GET['status'] ?? '');
            $sql = "SELECT id, title, description, status, priority, source, requester_name, requester_email, created_at, updated_at
                    FROM it_tickets";
            $params = [];
            if ($status !== '') {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            $sql .= " ORDER BY created_at DESC LIMIT 200";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if (ob_get_length()) { @ob_clean(); }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;
    case 'audit':
        $requireIt();
        if ($method === 'GET') {
            try {
                ActivityLogger::ensureTables($db);
            } catch (Exception $e) {
                // ignore
            }
            $role = trim($_GET['role'] ?? '');
            $department = trim($_GET['department'] ?? '');
            if ($role === '' && $department !== '') $role = $department;
            $actorId = trim($_GET['actor_id'] ?? '');
            $entityType = trim($_GET['entity_type'] ?? '');
            $source = trim($_GET['source'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $q = trim($_GET['q'] ?? '');
            $limit = (int)($_GET['limit'] ?? 100);
            if ($limit <= 0 || $limit > 500) $limit = 100;

            $sql = "SELECT created_at, username, action, status, severity, actor_id, actor_role, entity_type, entity_id, source, error_message
                    FROM audit_logs WHERE 1=1";
            $params = [];
            if ($role !== '') {
                $sql .= " AND actor_role = ?";
                $params[] = $role;
            }
            if ($actorId !== '') {
                $sql .= " AND actor_id = ?";
                $params[] = $actorId;
            }
            if ($entityType !== '') {
                $sql .= " AND entity_type = ?";
                $params[] = $entityType;
            }
            if ($source !== '') {
                $sql .= " AND source LIKE ?";
                $params[] = "%" . $source . "%";
            }
            if ($status !== '') {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            if ($q !== '') {
                $sql .= " AND (action LIKE ? OR error_message LIKE ? OR username LIKE ?)";
                $params[] = "%" . $q . "%";
                $params[] = "%" . $q . "%";
                $params[] = "%" . $q . "%";
            }
            $sql .= " ORDER BY created_at DESC LIMIT " . $limit;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if (ob_get_length()) { @ob_clean(); }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'ticket_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            $title = trim($data->title ?? '');
            $description = trim($data->description ?? '');
            $priority = strtolower(trim($data->priority ?? 'normal'));
            if ($title === '' || $description === '') {
                http_response_code(400);
                echo json_encode(["message" => "Title and description are required."]);
                exit;
            }
            if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
                $priority = 'normal';
            }

            $requesterName = $user->full_name ?? 'User';
            $requesterEmail = null;
            try {
                $uStmt = $db->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
                $uStmt->execute([$user->id]);
                $requesterEmail = $uStmt->fetchColumn() ?: null;
            } catch (Exception $e) {
                $requesterEmail = null;
            }

            $stmt = $db->prepare("INSERT INTO it_tickets (title, description, status, priority, source, requester_name, requester_email, created_by)
                                  VALUES (?, ?, 'open', ?, 'local', ?, ?, ?)");
            $stmt->execute([$title, $description, $priority, $requesterName, $requesterEmail, $user->id ?? null]);

            try {
                ActivityLogger::log($db, $requesterName, 'Created IT ticket', 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'it_ticket',
                    'entity_id' => (string)$db->lastInsertId(),
                    'actor_id' => isset($user->id) ? (string)$user->id : null,
                    'actor_role' => $user->role ?? null,
                    'source' => 'it/ticket_create'
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }

            try {
                $email = null;
                $stmt = $db->query("SELECT contact_email FROM system_settings WHERE id = 1 LIMIT 1");
                $email = $stmt ? $stmt->fetchColumn() : null;
                if ($email) {
                    $notifier = new NotificationService();
                    $subject = "New IT Ticket: {$title}";
                    $body = "<strong>New IT Ticket</strong><br><br>"
                        . "<strong>Title:</strong> " . htmlspecialchars($title) . "<br>"
                        . "<strong>Priority:</strong> " . htmlspecialchars($priority) . "<br>"
                        . "<strong>Submitted by:</strong> " . htmlspecialchars($requesterName)
                        . ($requesterEmail ? " (" . htmlspecialchars($requesterEmail) . ")" : "") . "<br><br>"
                        . "<pre style=\"white-space:pre-wrap;\">" . htmlspecialchars($description) . "</pre>";
                    $notifier->sendEmail($email, $subject, $body);
                }
            } catch (Exception $e) {
                // ignore email errors
            }

            if (ob_get_length()) { @ob_clean(); }
            echo json_encode(["message" => "Ticket created"]);
        }
        break;

    case 'ticket_update':
        $requireIt();
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->id)) {
                http_response_code(400);
                echo json_encode(["message" => "Ticket ID required"]);
                exit;
            }
            $fields = [];
            $params = [];
            if (isset($data->status)) {
                $fields[] = "status = ?";
                $params[] = $data->status;
            }
            if (isset($data->priority)) {
                $fields[] = "priority = ?";
                $params[] = $data->priority;
            }
            if (isset($data->assigned_to)) {
                $fields[] = "assigned_to = ?";
                $params[] = $data->assigned_to;
            }
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(["message" => "No updates provided"]);
                exit;
            }
            $params[] = $data->id;
            $sql = "UPDATE it_tickets SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if (ob_get_length()) { @ob_clean(); }
            echo json_encode(["message" => "Ticket updated"]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "IT endpoint not found"]);
        break;
}
