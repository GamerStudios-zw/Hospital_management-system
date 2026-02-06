<?php
// FILE: backend/routes/nurse.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';
require_once __DIR__ . '/../utils/Realtime.php';
require_once __DIR__ . '/../utils/DbSchema.php';

$database = new Database();
$db = $database->getConnection();

DbSchema::ensureNurseModules($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['nurse', 'admin'], $user);

switch ($action) {

    // 1. DASHBOARD STATS (Matches triage widget)
    case 'stats':
        $stmt = $db->query("SELECT COUNT(*) as count FROM patient_queue WHERE status IN ('Waiting','waiting','Urgent Care','urgent care')");
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->query("SELECT COUNT(*) as occupied FROM beds WHERE status = 'Occupied'");
        $occ = $stmt->fetch(PDO::FETCH_ASSOC)['occupied'];

        $stmt = $db->query("SELECT COUNT(*) as total FROM beds");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode([
            "pending_triage" => (int)$pending,
            "occupancy" => "$occ/$total"
        ]);
        break;

    // 1b. ANALYTICS
    case 'analytics':
        if ($method === 'GET') {
            $triageRows = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count
                                      FROM patient_vitals
                                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                      GROUP BY DATE(created_at)
                                      ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $bedRows = $db->query("SELECT status, COUNT(*) as count FROM beds GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $escRows = $db->query("SELECT status, COUNT(*) as count FROM nurse_escalations GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $urgentCount = (int)$db->query("SELECT COUNT(*) FROM patient_queue WHERE status IN ('Urgent Care','urgent care')")->fetchColumn();

            echo json_encode([
                "triage_by_day" => $triageRows,
                "beds_by_status" => $bedRows,
                "escalations_by_status" => $escRows,
                "urgent_count" => $urgentCount
            ]);
        }
        break;

    // 2. TRIAGE MOVED TO NURSE AID
    case 'triage_queue':
    case 'save_vitals':
    case 'start_triage':
        http_response_code(403);
        echo json_encode([
            "message" => "Triage is now handled by Nurse Aid.",
            "required_roles" => ["nurse_aid", "admin"]
        ]);
        break;

    // 4. BED MANAGEMENT
    case 'beds':
        $query = "SELECT b.*, p.full_name as patient_name, p.national_id
                  FROM beds b
                  LEFT JOIN patients p ON b.current_patient_id = p.id
                  ORDER BY b.ward_name, b.bed_number";
        echo json_encode($db->query($query)->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'discharge':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            // Sets status to 'Cleaning' to trigger custodial workflow
            $sql = "UPDATE beds SET status = 'Cleaning', current_patient_id = NULL, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            if($stmt->execute([$data->bed_id])) {
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Discharged patient from bed', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'bed',
                        'entity_id' => (string)$data->bed_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse',
                        'source' => 'nurse/discharge'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('nurse.discharge', ['bed_id' => $data->bed_id]);
                echo json_encode(["message" => "Discharge initiated. Bed sent for cleaning."]);
            }
        }
        break;

    // 4b. ASSIGN BED (Nurse admits patient to ward)
    case 'assign_bed':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->bed_id) || !isset($data->patient_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Bed ID and Patient ID required"]);
                exit;
            }
            try {
                $check = $db->prepare("SELECT status FROM beds WHERE id = ?");
                $check->execute([$data->bed_id]);
                $currentStatus = $check->fetchColumn();
                if ($currentStatus && $currentStatus !== 'Available') {
                    http_response_code(400);
                    echo json_encode(["message" => "Bed is not available"]);
                    exit;
                }

                $stmt = $db->prepare("UPDATE beds SET status = 'Occupied', current_patient_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$data->patient_id, $data->bed_id]);
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Assigned bed', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'bed',
                        'entity_id' => (string)$data->bed_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse',
                        'source' => 'nurse/assign_bed',
                        'metadata' => ['patient_id' => $data->patient_id]
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('nurse.assign_bed', ['bed_id' => $data->bed_id, 'patient_id' => $data->patient_id]);
                echo json_encode(["message" => "Bed assigned"]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to assign bed: " . $e->getMessage()]);
            }
        }
        break;

    // 4c. URGENT CARE PATIENTS (for ward admission)
    case 'urgent_patients':
        if ($method === 'GET') {
            try {
                $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.national_id, p.dob, p.gender, q.created_at, q.status
                          FROM patient_queue q
                          JOIN patients p ON q.patient_id = p.id
                          WHERE q.status IN ('Urgent Care', 'urgent care')
                          ORDER BY q.created_at DESC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Urgent list error: " . $e->getMessage()]);
            }
        }
        break;
    
    case 'urgent_care_list':
        if ($method === 'GET') {
            try {
                $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.national_id, p.dob, p.gender, q.created_at, q.status
                          FROM patient_queue q
                          JOIN patients p ON q.patient_id = p.id
                          WHERE q.status IN ('Urgent Care', 'urgent care')
                          ORDER BY q.created_at DESC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Urgent care list error: " . $e->getMessage()]);
            }
        }
        break;

    // 5. SHIFT ROSTER (Matches rosterBody in dashboard)
    case 'shifts':
        if ($method === 'GET') {
            // Updated to fetch from staff_shifts to match your admin assignments
            $query = "SELECT * FROM staff_shifts WHERE role = 'nurse' ORDER BY shift_start ASC";
            $stmt = $db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 6. PATIENT HISTORY (Recent vitals + trends)
    case 'history':
        if ($method === 'GET') {
            try {
                $recentQuery = "SELECT v.id, v.created_at, v.temperature, v.pulse, v.bp, v.weight, v.spo2,
                                       p.full_name as patient_name
                                FROM patient_vitals v
                                JOIN patients p ON v.patient_id = p.id
                                ORDER BY v.created_at DESC
                                LIMIT 50";
                $recent = $db->query($recentQuery)->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $trendQuery = "SELECT DATE(created_at) as day, COUNT(*) as count
                               FROM patient_vitals
                               WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                               GROUP BY DATE(created_at)
                               ORDER BY day ASC";
                $trendRows = $db->query($trendQuery)->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $labels = [];
                $counts = [];
                $trendMap = [];
                foreach ($trendRows as $row) {
                    $trendMap[$row['day']] = (int)$row['count'];
                }
                for ($i = 6; $i >= 0; $i--) {
                    $day = date('Y-m-d', strtotime("-{$i} day"));
                    $labels[] = $day;
                    $counts[] = $trendMap[$day] ?? 0;
                }

                $triageQuery = "SELECT status, COUNT(*) as count
                                FROM patient_queue
                                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                GROUP BY status";
                $triageRows = $db->query($triageQuery)->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $triageLabels = [];
                $triageCounts = [];
                foreach ($triageRows as $row) {
                    $triageLabels[] = $row['status'];
                    $triageCounts[] = (int)$row['count'];
                }

                echo json_encode([
                    "recent" => $recent,
                    "vitals_trend" => [
                        "labels" => $labels,
                        "counts" => $counts
                    ],
                    "triage_status" => [
                        "labels" => $triageLabels,
                        "counts" => $triageCounts
                    ]
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "History Error: " . $e->getMessage()]);
            }
        }
        break;

    // 7. PATIENTS LIST (for nurse modules)
    case 'patients':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT id, full_name, national_id, dob, gender FROM patients ORDER BY full_name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 8. NURSE HANDOVER
    case 'handover_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->user_id) || empty($data->notes)) {
                http_response_code(400);
                echo json_encode(["message" => "User and notes required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO nurse_handover (user_id, shift_start, shift_end, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->user_id, $data->shift_start ?? null, $data->shift_end ?? null, $data->notes]);
            Realtime::emit('nurse.handover', ['user_id' => $data->user_id]);
            echo json_encode(["message" => "Handover saved"]);
        }
        break;

    case 'handover_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT h.*, u.full_name FROM nurse_handover h LEFT JOIN users u ON h.user_id = u.id ORDER BY h.created_at DESC LIMIT 50");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 9. TASK BOARD
    case 'task_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->task)) {
                http_response_code(400);
                echo json_encode(["message" => "Task required"]);
                exit;
            }
            if (!empty($data->patient_id)) {
                $check = $db->prepare("SELECT 1 FROM patient_queue WHERE patient_id = ? AND status IN ('Urgent Care','urgent care') LIMIT 1");
                $check->execute([$data->patient_id]);
                if (!$check->fetchColumn()) {
                    http_response_code(400);
                    echo json_encode(["message" => "Only Urgent Care patients can be assigned to tasks."]);
                    exit;
                }
            }
            $stmt = $db->prepare("INSERT INTO nurse_tasks (patient_id, assigned_to, task, priority, status, due_at)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data->patient_id ?? null,
                $data->assigned_to ?? null,
                $data->task,
                $data->priority ?? 'normal',
                $data->status ?? 'open',
                $data->due_at ?? null
            ]);
            Realtime::emit('nurse.task', []);
            echo json_encode(["message" => "Task created"]);
        }
        break;

    case 'task_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT t.*, p.full_name as patient_name, u.full_name as nurse_name
                                FROM nurse_tasks t
                                LEFT JOIN patients p ON t.patient_id = p.id
                                LEFT JOIN users u ON t.assigned_to = u.id
                                WHERE t.patient_id IS NULL
                                   OR EXISTS (
                                       SELECT 1 FROM patient_queue q
                                       WHERE q.patient_id = t.patient_id
                                         AND q.status IN ('Urgent Care','urgent care')
                                   )
                                ORDER BY t.created_at DESC
                                LIMIT 100");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'task_update':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->task_id) || !isset($data->status)) {
                http_response_code(400);
                echo json_encode(["message" => "Task ID and status required"]);
                exit;
            }
            $stmt = $db->prepare("UPDATE nurse_tasks SET status = ? WHERE id = ?");
            $stmt->execute([$data->status, $data->task_id]);
            Realtime::emit('nurse.task', []);
            echo json_encode(["message" => "Task updated"]);
        }
        break;

    // 10. ESCALATIONS
    case 'escalation_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || empty($data->reason)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and reason required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO nurse_escalations (patient_id, reason, severity, status)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->patient_id, $data->reason, $data->severity ?? 'urgent', $data->status ?? 'open']);
            Realtime::emit('nurse.escalation', ['patient_id' => $data->patient_id]);
            echo json_encode(["message" => "Escalation created"]);
        }
        break;

    case 'escalation_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT e.*, p.full_name as patient_name
                                FROM nurse_escalations e
                                JOIN patients p ON e.patient_id = p.id
                                ORDER BY e.created_at DESC
                                LIMIT 100");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;
    
    case 'escalation_patients':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT DISTINCT e.patient_id, p.full_name, p.national_id
                                FROM nurse_escalations e
                                JOIN patients p ON e.patient_id = p.id
                                ORDER BY p.full_name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 11. DISCHARGE SUMMARY
    case 'discharge_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || empty($data->summary)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and summary required"]);
                exit;
            }
            $check = $db->prepare("SELECT 1 FROM nurse_escalations WHERE patient_id = ? LIMIT 1");
            $check->execute([$data->patient_id]);
            if (!$check->fetchColumn()) {
                http_response_code(400);
                echo json_encode(["message" => "Only escalated patients can be discharged."]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO discharge_summaries (patient_id, summary, status)
                                  VALUES (?, ?, ?)");
            $stmt->execute([$data->patient_id, $data->summary, $data->status ?? 'pending']);
            Realtime::emit('nurse.discharge_summary', ['patient_id' => $data->patient_id]);
            echo json_encode(["message" => "Discharge summary saved"]);
        }
        break;

    case 'discharge_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT d.*, p.full_name as patient_name
                                FROM discharge_summaries d
                                JOIN patients p ON d.patient_id = p.id
                                WHERE EXISTS (
                                    SELECT 1 FROM nurse_escalations e
                                    WHERE e.patient_id = d.patient_id
                                )
                                ORDER BY d.created_at DESC
                                LIMIT 100");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 12. PATIENT TIMELINE
    case 'timeline':
        if ($method === 'GET') {
            $pid = $_GET['patient_id'] ?? 0;
            if (!$pid) {
                http_response_code(400);
                echo json_encode(["message" => "Patient ID required"]);
                exit;
            }
            $vitals = $db->query("SELECT created_at, CONCAT('Vitals: BP ', bp, ', T ', temperature, '°C') as note FROM patient_vitals WHERE patient_id = $pid ORDER BY created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
            $visits = $db->query("SELECT created_at, CONCAT('Visit status: ', status) as note FROM patient_queue WHERE patient_id = $pid ORDER BY created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
            $meds = $db->query("SELECT created_at, CONCAT('Prescription: ', COALESCE(m.name, pr.notes)) as note FROM prescriptions pr LEFT JOIN medicines m ON pr.medicine_id = m.id WHERE pr.patient_id = $pid ORDER BY created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
            $docs = $db->query("SELECT created_at, CONCAT('Document: ', report_name) as note FROM medical_reports WHERE patient_id = $pid ORDER BY created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);

            $timeline = array_merge($vitals ?: [], $visits ?: [], $meds ?: [], $docs ?: []);
            usort($timeline, function($a, $b) {
                return strtotime($b['created_at']) <=> strtotime($a['created_at']);
            });

            echo json_encode(array_slice($timeline, 0, 50));
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Nurse action not found"]);
        break;
}
?>
