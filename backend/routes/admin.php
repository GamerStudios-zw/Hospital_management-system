<?php
// FILE: backend/routes/admin.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';
require_once __DIR__ . '/../utils/DbSchema.php';
require_once __DIR__ . '/../utils/Realtime.php';

$database = new Database();
$db = $database->getConnection();
DbSchema::ensureUserRole($db, 'it_support');

// Determine the resource (admin, users, or logs) based on the URL
// Assuming URL structure: /backend/index.php/{resource}/{action}
$resource = isset($segments[0]) ? $segments[0] : '';
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

// Require admin for all admin routes
$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['admin'], $user);

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
                $queue = $db->query("SELECT COUNT(*) FROM patient_queue WHERE status NOT IN ('Completed','completed')")->fetchColumn();

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

        // [GET] /admin/monitor
        case 'monitor':
            if ($method === 'GET') {
                try {
                    ActivityLogger::ensureTables($db);
                    $start = $_GET['start'] ?? null;
                    $end = $_GET['end'] ?? null;
                    $staffId = $_GET['staff_id'] ?? null;

                    $logFilters = [];
                    $logParams = [];
                    if (!empty($start)) {
                        $logFilters[] = "created_at >= ?";
                        $logParams[] = $start;
                    }
                    if (!empty($end)) {
                        $logFilters[] = "created_at <= ?";
                        $logParams[] = $end;
                    }
                    if (!empty($staffId)) {
                        $logFilters[] = "actor_id = ?";
                        $logParams[] = $staffId;
                    }
                    $logWhere = $logFilters ? (" AND " . implode(" AND ", $logFilters)) : "";

                    $countBySource = function($source) use ($db, $logWhere, $logParams) {
                        $stmt = $db->prepare("SELECT COUNT(*) FROM activity_logs WHERE source = ?" . $logWhere);
                        $stmt->execute(array_merge([$source], $logParams));
                        return (int)$stmt->fetchColumn();
                    };

                    $recentBySource = function($source) use ($db, $logWhere, $logParams) {
                        $stmt = $db->prepare("SELECT created_at, username, action, entity_type, entity_id, status, actor_role
                                              FROM activity_logs
                                              WHERE source = ?" . $logWhere . "
                                              ORDER BY created_at DESC
                                              LIMIT 5");
                        $stmt->execute(array_merge([$source], $logParams));
                        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    };

                    $rangeFilters = [];
                    $rangeParams = [];
                    if (!empty($start)) {
                        $rangeFilters[] = "created_at >= ?";
                        $rangeParams[] = $start;
                    }
                    if (!empty($end)) {
                        $rangeFilters[] = "created_at <= ?";
                        $rangeParams[] = $end;
                    }
                    $rangeWhere = $rangeFilters ? (" WHERE " . implode(" AND ", $rangeFilters)) : "";

                    $pendingPrescriptionsStmt = $db->prepare("SELECT COUNT(*) FROM prescriptions WHERE status IN ('Pending', 'External')" . $rangeWhere);
                    $pendingPrescriptionsStmt->execute($rangeParams);
                    $pendingPrescriptions = (int)$pendingPrescriptionsStmt->fetchColumn();

                    $triageStmt = $db->prepare("SELECT COUNT(*) FROM patient_queue WHERE status IN ('Waiting','waiting','Urgent Care','urgent care','In Triage','in triage')" . $rangeWhere);
                    $triageStmt->execute($rangeParams);
                    $pendingTriage = (int)$triageStmt->fetchColumn();

                    $monitor = [
                        "filters" => [
                            "start" => $start,
                            "end" => $end,
                            "staff_id" => $staffId
                        ],
                        "pharmacy" => [
                            "pending_prescriptions" => $pendingPrescriptions,
                            "dispensed_count" => $countBySource('pharmacy/dispense'),
                            "recent_dispensed" => $recentBySource('pharmacy/dispense')
                        ],
                        "nurse" => [
                            "pending_triage" => $pendingTriage,
                            "vitals_recorded" => $countBySource('nurse/save_vitals'),
                            "recent_vitals" => $recentBySource('nurse/save_vitals')
                        ],
                        "reception" => [
                            "patients_registered" => $countBySource('reception/register'),
                            "patients_admitted" => $countBySource('reception/admit'),
                            "recent_activity" => array_merge(
                                $recentBySource('reception/register'),
                                $recentBySource('reception/admit')
                            )
                        ]
                    ];

                    echo json_encode($monitor);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["message" => "Monitor Error: " . $e->getMessage()]);
                }
            }
            break;

        // [GET] /admin/staff_perf
        case 'staff_perf':
            if ($method === 'GET') {
                try {
                    DbSchema::ensureStaffShifts($db);
                    AuthMiddleware::ensureSessionColumns($db);
                    AuthMiddleware::ensureSessionTable($db);
                    $date = $_GET['date'] ?? null;
                    $dateClause = '';
                    $dateParams = [];
                    if (!empty($date)) {
                        $dateClause = " AND DATE(s.shift_start) = ?";
                        $dateParams[] = $date;
                    }

                    $totalStaff = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    $doctors = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
                    $nurses = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'nurse'")->fetchColumn();

                    $presentStmt = $db->prepare("SELECT COUNT(DISTINCT s.user_id)
                                                 FROM staff_shifts s
                                                 WHERE s.shift_start <= NOW() AND s.shift_end >= NOW()
                                                 AND (s.status IS NULL OR s.status NOT IN ('Cancelled','cancelled'))" . $dateClause);
                    $presentStmt->execute($dateParams);
                    $presentByShifts = (int)$presentStmt->fetchColumn();

                    // Live presence straight from DB sessions (independent of shift assignment windows).
                    $presentSessionStmt = $db->prepare("SELECT COUNT(DISTINCT u.id)
                                                        FROM users u
                                                        LEFT JOIN user_sessions us
                                                          ON us.user_id = u.id
                                                         AND (us.expires_at IS NULL OR us.expires_at > NOW())
                                                        WHERE u.is_active = 1
                                                          AND (
                                                              (u.current_session_id IS NOT NULL AND (u.session_expires_at IS NULL OR u.session_expires_at > NOW()))
                                                              OR us.session_id IS NOT NULL
                                                          )");
                    $presentSessionStmt->execute();
                    $presentBySessions = (int)$presentSessionStmt->fetchColumn();

                    $present = max($presentByShifts, $presentBySessions);

                    $utilization = $totalStaff > 0 ? round(($present / $totalStaff) * 100, 2) : 0;

                    $shiftTypeStmt = $db->prepare("SELECT COALESCE(s.shift_type, 'General') as shift_type, COUNT(*) as count
                                                   FROM staff_shifts s
                                                   WHERE 1=1" . $dateClause . "
                                                   GROUP BY COALESCE(s.shift_type, 'General')
                                                   ORDER BY count DESC");
                    $shiftTypeStmt->execute($dateParams);
                    $shiftTypes = $shiftTypeStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    $presentRoleStmt = $db->prepare("SELECT u.role, COUNT(DISTINCT s.user_id) as count
                                                     FROM staff_shifts s
                                                     JOIN users u ON s.user_id = u.id
                                                     WHERE s.shift_start <= NOW() AND s.shift_end >= NOW()
                                                     AND (s.status IS NULL OR s.status NOT IN ('Cancelled','cancelled'))" . $dateClause . "
                                                     GROUP BY u.role");
                    $presentRoleStmt->execute($dateParams);
                    $presentByRole = $presentRoleStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    if (empty($presentByRole)) {
                        $presentRoleLiveStmt = $db->prepare("SELECT u.role, COUNT(DISTINCT u.id) as count
                                                             FROM users u
                                                             LEFT JOIN user_sessions us
                                                               ON us.user_id = u.id
                                                              AND (us.expires_at IS NULL OR us.expires_at > NOW())
                                                             WHERE u.is_active = 1
                                                               AND (
                                                                   (u.current_session_id IS NOT NULL AND (u.session_expires_at IS NULL OR u.session_expires_at > NOW()))
                                                                   OR us.session_id IS NOT NULL
                                                               )
                                                             GROUP BY u.role");
                        $presentRoleLiveStmt->execute();
                        $presentByRole = $presentRoleLiveStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    }

                    $roleUtilStmt = $db->prepare("SELECT u.role,
                                                         COUNT(*) as total,
                                                         SUM(CASE WHEN s.user_id IS NOT NULL THEN 1 ELSE 0 END) as present
                                                  FROM users u
                                                  LEFT JOIN (
                                                      SELECT DISTINCT s.user_id
                                                      FROM staff_shifts s
                                                      WHERE s.shift_start <= NOW() AND s.shift_end >= NOW()
                                                      AND (s.status IS NULL OR s.status NOT IN ('Cancelled','cancelled'))" . $dateClause . "
                                                  ) s ON s.user_id = u.id
                                                  GROUP BY u.role
                                                  ORDER BY total DESC");
                    $roleUtilStmt->execute($dateParams);
                    $roleRows = $roleUtilStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    $roleUtil = [];
                    foreach ($roleRows as $row) {
                        $total = (int)$row['total'];
                        $presentCount = (int)$row['present'];
                        $roleUtil[] = [
                            'role' => $row['role'],
                            'present' => $presentCount,
                            'total' => $total,
                            'percent' => $total > 0 ? round(($presentCount / $total) * 100, 2) : 0
                        ];
                    }

                    $deptStmt = $db->prepare("SELECT COALESCE(s.ward_name, 'General') as department,
                                                     COUNT(*) as total,
                                                     SUM(CASE WHEN s.shift_start <= NOW() AND s.shift_end >= NOW() THEN 1 ELSE 0 END) as present
                                              FROM staff_shifts s
                                              WHERE 1=1" . $dateClause . "
                                              GROUP BY COALESCE(s.ward_name, 'General')
                                              ORDER BY total DESC");
                    $deptStmt->execute($dateParams);
                    $deptRows = $deptStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    $deptUtil = [];
                    foreach ($deptRows as $row) {
                        $total = (int)$row['total'];
                        $presentCount = (int)$row['present'];
                        $deptUtil[] = [
                            'department' => $row['department'],
                            'present' => $presentCount,
                            'total' => $total,
                            'percent' => $total > 0 ? round(($presentCount / $total) * 100, 2) : 0
                        ];
                    }

                    echo json_encode([
                        'total_staff' => $totalStaff,
                        'doctors' => $doctors,
                        'nurses' => $nurses,
                        'present' => $present,
                        'utilization_percent' => $utilization,
                        'shift_types' => $shiftTypes,
                        'present_by_role' => $presentByRole,
                        'utilization_by_department' => $deptUtil,
                        'utilization_by_role' => $roleUtil
                    ]);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["message" => "Staff perf error: " . $e->getMessage()]);
                }
            }
            break;

        // [POST] /admin/add_user
        case 'add_user':
            if ($method === 'POST') {
                $data = RequestValidator::json();

                if (empty($data->full_name) || empty($data->email) || empty($data->username) || empty($data->role) || empty($data->password)) {
                    http_response_code(400);
                    echo json_encode(["message" => "All fields are required."]);
                    exit;
                }
                $role = strtolower(trim((string)$data->role));
                $allowedRoles = ['admin', 'doctor', 'nurse', 'nurse_aid', 'receptionist', 'pharmacist', 'senior_pharmacist', 'it_support'];
                if (!in_array($role, $allowedRoles, true)) {
                    http_response_code(400);
                    echo json_encode(["message" => "Invalid role selected."]);
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

                    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
                    $check->execute([$data->email]);
                    if($check->rowCount() > 0) {
                        http_response_code(409);
                        echo json_encode(["message" => "Email already exists."]);
                        exit;
                    }

                    $hash = password_hash($data->password, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO users (full_name, email, username, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, 1)";
                    $stmt = $db->prepare($sql);

                    if($stmt->execute([$data->full_name, $data->email, $data->username, $hash, $role])) {
                        $newId = $db->lastInsertId();
                        // Log the action
                        ActivityLogger::log($db, $user->full_name ?? 'Admin', "Created user: " . $data->username, 'Success', null, [
                            'event_type' => 'audit',
                            'entity_type' => 'user',
                            'entity_id' => $newId ? (string)$newId : null,
                            'actor_id' => isset($user->id) ? (string)$user->id : null,
                            'actor_role' => $user->role ?? 'admin',
                            'source' => 'admin/add_user',
                            'new_values' => [
                                'username' => $data->username,
                                'full_name' => $data->full_name,
                                'role' => $role,
                                'email' => $data->email
                            ],
                            'safe_fields' => ['username', 'full_name', 'role', 'email']
                        ]);
                        Realtime::emit('admin.add_user', ['user_id' => $newId]);
                        echo json_encode(["message" => "User created successfully"]);
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(["message" => "Error: " . $e->getMessage()]);
                }
            }
            break;

        // [POST] /admin/force_logout
        case 'force_logout':
            if ($method === 'POST') {
                $data = RequestValidator::json();
                $targetUserId = isset($data->user_id) ? (int)$data->user_id : 0;
                if ($targetUserId <= 0) {
                    http_response_code(400);
                    echo json_encode(["message" => "Valid user_id is required"]);
                    exit;
                }

                try {
                    AuthMiddleware::ensureSessionColumns($db);
                    AuthMiddleware::ensureSessionTable($db);
                } catch (Exception $e) {
                    // ignore schema ensure errors
                }

                try {
                    $db->beginTransaction();

                    $del = $db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                    $del->execute([$targetUserId]);

                    $upd = $db->prepare("UPDATE users SET is_active = 0, current_session_id = NULL, session_expires_at = NULL WHERE id = ?");
                    $upd->execute([$targetUserId]);

                    $db->commit();
                    Realtime::emit('admin.force_logout', ['user_id' => $targetUserId]);
                    try {
                        ActivityLogger::log($db, $user->full_name ?? 'Admin', "Force logout user ID: " . $targetUserId, 'Success', null, [
                            'event_type' => 'audit',
                            'entity_type' => 'user',
                            'entity_id' => (string)$targetUserId,
                            'actor_id' => isset($user->id) ? (string)$user->id : null,
                            'actor_role' => $user->role ?? 'admin',
                            'source' => 'admin/force_logout'
                        ]);
                    } catch (Exception $e) {
                        // ignore logging errors
                    }
                    echo json_encode(["success" => true, "message" => "User sessions revoked", "user_id" => $targetUserId]);
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    http_response_code(500);
                    echo json_encode(["success" => false, "message" => "Failed to revoke sessions: " . $e->getMessage()]);
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
        $stmt = $db->query("SELECT id, full_name, username, role, created_at, is_active, session_expires_at FROM users ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    // [DELETE] /users?id=X
    else if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                ActivityLogger::log($db, $user->full_name ?? 'Admin', "Deleted user ID: " . $id, 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'user',
                    'entity_id' => (string)$id,
                    'actor_id' => isset($user->id) ? (string)$user->id : null,
                    'actor_role' => $user->role ?? 'admin',
                    'source' => 'admin/delete_user'
                ]);
                echo json_encode(["message" => "User deleted"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID required"]);
        }
    }
    // [POST] /users (Handle Reset Password action)
    else if ($method === 'POST') {
        $data = RequestValidator::json();
        if (isset($data->action) && $data->action === 'reset_password' && isset($data->user_id)) {
            $hash = password_hash('Staff123!', PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$data->user_id])) {
                ActivityLogger::log($db, $user->full_name ?? 'Admin', "Reset password for user ID: " . $data->user_id, 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'user',
                    'entity_id' => (string)$data->user_id,
                    'actor_id' => isset($user->id) ? (string)$user->id : null,
                    'actor_role' => $user->role ?? 'admin',
                    'source' => 'admin/reset_password'
                ]);
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
            ActivityLogger::ensureTables($db);
            $includeAudit = isset($_GET['include_audit']) ? (int)$_GET['include_audit'] === 1 : true;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $limit = max(1, min(500, $limit));

            $fields = "id, username, action, ip_address, status, event_type, entity_type, entity_id, actor_id, actor_role, source, request_id, session_id, severity, duration_ms, status_code, error_code, error_message, user_agent, device_type, client_ip, hostname, created_at";
            $selectActivity = "SELECT 'activity' AS log_bucket, {$fields} FROM activity_logs";
            $selectAudit = "SELECT 'audit' AS log_bucket, {$fields} FROM audit_logs";

            $query = $selectActivity;
            if ($includeAudit) {
                $query = "(" . $selectActivity . ") UNION ALL (" . $selectAudit . ")";
            }
            $query = "SELECT * FROM (" . $query . ") AS logs ORDER BY created_at DESC LIMIT " . $limit;

            $stmt = $db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            // Return empty array if table doesn't exist yet
            echo json_encode([]);
        }
    }

    // [GET] /logs/analytics
    if ($action === 'analytics' && $method === 'GET') {
        try {
            ActivityLogger::ensureTables($db);
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
            $selectActivity = "SELECT source, severity, created_at, username, action, event_type, entity_type, entity_id, actor_id, actor_role, status, request_id, error_message FROM activity_logs";
            $selectAudit = "SELECT source, severity, created_at, username, action, event_type, entity_type, entity_id, actor_id, actor_role, status, request_id, error_message FROM audit_logs";
            $base = $selectActivity . $whereSql;
            $baseParams = $params;

            if ($includeAudit) {
                $base = "(" . $selectActivity . $whereSql . ") UNION ALL (" . $selectAudit . $whereSql . ")";
                $baseParams = array_merge($params, $params);
            }

            $sourceStmt = $db->prepare("SELECT COALESCE(NULLIF(source,''),'unknown') as source, COUNT(*) as count
                                        FROM ({$base}) t
                                        GROUP BY COALESCE(NULLIF(source,''),'unknown')
                                        ORDER BY count DESC
                                        LIMIT 8");
            $sourceStmt->execute($baseParams);
            $sourceRows = $sourceStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $severityStmt = $db->prepare("SELECT UPPER(COALESCE(NULLIF(severity,''),'INFO')) as severity, COUNT(*) as count
                                          FROM ({$base}) t
                                          GROUP BY UPPER(COALESCE(NULLIF(severity,''),'INFO'))
                                          ORDER BY count DESC");
            $severityStmt->execute($baseParams);
            $severityRows = $severityStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $dayExtraWhere = (!empty($filters['start']) || !empty($filters['end'])) ? "" : " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
            $dayStmt = $db->prepare("SELECT DATE(created_at) as day, COUNT(*) as count
                                     FROM ({$base}) t" . $dayExtraWhere . "
                                     GROUP BY DATE(created_at)
                                     ORDER BY day");
            $dayStmt->execute($baseParams);
            $dayRows = $dayStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                "by_source" => $sourceRows,
                "by_severity" => $severityRows,
                "by_day" => $dayRows
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "by_source" => [],
                "by_severity" => [],
                "by_day" => []
            ]);
        }
    }
}

?>
