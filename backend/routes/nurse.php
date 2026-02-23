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
DbSchema::ensurePharmacyModules($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['nurse', 'admin'], $user);

switch ($action) {

    // 1. DASHBOARD STATS (Matches triage widget)
    case 'stats':
        $stmt = $db->query("SELECT COUNT(*) as count FROM patient_queue WHERE status IN ('Waiting','waiting','Urgent Care','urgent care','Admission Pending','admission pending')");
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

            $urgentCount = (int)$db->query("SELECT COUNT(*) FROM patient_queue WHERE status IN ('Urgent Care','urgent care','Admission Pending','admission pending')")->fetchColumn();

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
        $query = "SELECT b.*,
                         p.full_name as patient_name,
                         p.national_id,
                         (
                             SELECT d.status
                             FROM discharge_summaries d
                             WHERE d.patient_id = b.current_patient_id
                             ORDER BY d.created_at DESC
                             LIMIT 1
                         ) as discharge_status,
                         (
                             SELECT COUNT(*)
                             FROM discharge_summaries d2
                             WHERE d2.patient_id = b.current_patient_id
                               AND d2.status = 'approved'
                         ) as discharge_ready
                  FROM beds b
                  LEFT JOIN patients p ON b.current_patient_id = p.id
                  ORDER BY b.ward_name, b.bed_number";
        echo json_encode($db->query($query)->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'discharge':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $bedId = RequestValidator::requireInt($data, 'bed_id', 1);
            $bedStmt = $db->prepare("SELECT current_patient_id, status FROM beds WHERE id = ? LIMIT 1");
            $bedStmt->execute([$bedId]);
            $bedRow = $bedStmt->fetch(PDO::FETCH_ASSOC);
            if (!$bedRow || $bedRow['status'] !== 'Occupied' || empty($bedRow['current_patient_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Bed is not occupied."]);
                exit;
            }
            $auth = $db->prepare("SELECT 1 FROM discharge_summaries WHERE patient_id = ? AND status = 'approved' LIMIT 1");
            $auth->execute([$bedRow['current_patient_id']]);
            if (!$auth->fetchColumn()) {
                http_response_code(400);
                echo json_encode(["message" => "Doctor discharge command required."]);
                exit;
            }
            // Sets status to 'Cleaning' to trigger custodial workflow
            $sql = "UPDATE beds SET status = 'Cleaning', current_patient_id = NULL WHERE id = ?";
            $stmt = $db->prepare($sql);
            if($stmt->execute([$bedId])) {
                // Mark any discharge summary for this patient as completed
                $db->prepare("UPDATE discharge_summaries SET status = 'completed' WHERE patient_id = ?")
                   ->execute([$bedRow['current_patient_id']]);
                // Update latest queue status so triage/admission lists no longer show the patient
                $db->prepare("UPDATE patient_queue
                              SET status = 'Discharged'
                              WHERE patient_id = ?
                                AND LOWER(status) IN ('urgent care','admission pending','with doctor','in triage','waiting')")
                   ->execute([$bedRow['current_patient_id']]);
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Discharged patient from bed', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'bed',
                        'entity_id' => (string)$bedId,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse',
                        'source' => 'nurse/discharge'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('nurse.discharge', ['bed_id' => $bedId]);
                echo json_encode(["message" => "Discharge initiated. Bed sent for cleaning."]);
            }
        }
        break;

    // 4b. ASSIGN BED (Nurse admits patient to ward)
    case 'assign_bed':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $bedId = RequestValidator::requireInt($data, 'bed_id', 1);
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            try {
                $check = $db->prepare("SELECT status FROM beds WHERE id = ?");
                $check->execute([$bedId]);
                $currentStatus = $check->fetchColumn();
                if ($currentStatus && $currentStatus !== 'Available') {
                    http_response_code(400);
                    echo json_encode(["message" => "Bed is not available"]);
                    exit;
                }

                $stmt = $db->prepare("UPDATE beds SET status = 'Occupied', current_patient_id = ? WHERE id = ?");
                $stmt->execute([$patientId, $bedId]);
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Assigned bed', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'bed',
                        'entity_id' => (string)$bedId,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse',
                        'source' => 'nurse/assign_bed',
                        'metadata' => ['patient_id' => $patientId]
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('nurse.assign_bed', ['bed_id' => $bedId, 'patient_id' => $patientId]);
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
    
    // 4d. ADMISSION WAITING LIST (Sent by Doctor)
    case 'admission_waiting_list':
        if ($method === 'GET') {
            try {
                $query = "SELECT q.id as queue_id,
                                 p.id as patient_id,
                                 p.full_name,
                                 p.national_id,
                                 p.dob,
                                 p.gender,
                                 q.created_at,
                                 q.status,
                                 (SELECT COUNT(*) FROM prescriptions pr WHERE pr.patient_id = p.id AND pr.status IN ('Pending','External')) as pending_prescriptions
                          FROM patient_queue q
                          JOIN patients p ON q.patient_id = p.id
                          WHERE LOWER(q.status) IN ('admission pending','waiting pharmacy','ready for admission')
                          ORDER BY q.created_at DESC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Admission waiting list error: " . $e->getMessage()]);
            }
        }
        break;
    
    // 4e. REQUEST PHARMACY TO PREPARE PRESCRIPTIONS
    case 'pharmacy_request':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $notes = RequestValidator::optionalString($data, 'notes', 1000);
            try {
                $db->beginTransaction();
                $check = $db->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ? AND status IN ('Pending','External')");
                $check->execute([$patientId]);
                $count = (int)$check->fetchColumn();
                if ($count === 0) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["message" => "No pending prescriptions for this patient."]);
                    exit;
                }
                // Prevent duplicate pending/ready pharmacy requests for same patient
                $existing = $db->prepare("SELECT id FROM pharmacy_requests WHERE patient_id = ? AND LOWER(status) IN ('pending','ready') LIMIT 1");
                $existing->execute([$patientId]);
                if ($existing->fetchColumn()) {
                    $db->rollBack();
                    echo json_encode(["message" => "Pharmacy request already pending for this patient.", "pending_prescriptions" => $count]);
                    exit;
                }
                $insert = $db->prepare("INSERT INTO pharmacy_requests (patient_id, requested_by, status, notes) VALUES (?, ?, 'Pending', ?)");
                $insert->execute([
                    $patientId,
                    isset($user->id) ? $user->id : null,
                    $notes
                ]);
                // Mark admission as waiting on pharmacy prep
                $db->prepare("UPDATE patient_queue
                              SET status = 'Waiting Pharmacy'
                              WHERE patient_id = ?
                                AND status IN ('Admission Pending','admission pending')")
                   ->execute([$patientId]);
                $db->commit();
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Requested pharmacy meds', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'pharmacy_request',
                        'entity_id' => (string)$db->lastInsertId(),
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse',
                        'source' => 'nurse/pharmacy_request'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('pharmacy.request', ['patient_id' => $patientId]);
                echo json_encode(["message" => "Pharmacy request sent.", "pending_prescriptions" => $count]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Failed to send pharmacy request: " . $e->getMessage()]);
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
                foreach ($recent as &$r) {
                    $r = array_merge($r, VitalRisk::classify($r['temperature'] ?? null, $r['pulse'] ?? null, $r['bp'] ?? null, $r['spo2'] ?? null));
                }
                unset($r);

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
            $data = RequestValidator::json();
            $userId = RequestValidator::requireInt($data, 'user_id', 1);
            $notes = RequestValidator::requireString($data, 'notes', 3, 2000);
            $shiftStart = RequestValidator::optionalString($data, 'shift_start', 40);
            $shiftEnd = RequestValidator::optionalString($data, 'shift_end', 40);
            $stmt = $db->prepare("INSERT INTO nurse_handover (user_id, shift_start, shift_end, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $shiftStart, $shiftEnd, $notes]);
            Realtime::emit('nurse.handover', ['user_id' => $userId]);
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
            $data = RequestValidator::json();
            $task = RequestValidator::requireString($data, 'task', 3, 500);
            $patientId = isset($data->patient_id) ? RequestValidator::requireInt($data, 'patient_id', 1) : null;
            $assignedTo = isset($data->assigned_to) ? RequestValidator::requireInt($data, 'assigned_to', 1) : null;
            $priority = RequestValidator::enum($data->priority ?? 'normal', ['low', 'normal', 'high', 'urgent'], 'priority');
            $status = RequestValidator::enum($data->status ?? 'open', ['open', 'in_progress', 'done', 'cancelled'], 'status');
            $dueAt = RequestValidator::optionalString($data, 'due_at', 40);
            if (!empty($data->patient_id)) {
                $check = $db->prepare("SELECT 1 FROM patient_queue WHERE patient_id = ? AND status IN ('Urgent Care','urgent care') LIMIT 1");
                $check->execute([$patientId]);
                if (!$check->fetchColumn()) {
                    http_response_code(400);
                    echo json_encode(["message" => "Only Urgent Care patients can be assigned to tasks."]);
                    exit;
                }
            }
            $stmt = $db->prepare("INSERT INTO nurse_tasks (patient_id, assigned_to, task, priority, status, due_at)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $patientId,
                $assignedTo,
                $task,
                $priority,
                $status,
                $dueAt
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
            $data = RequestValidator::json();
            $taskId = RequestValidator::requireInt($data, 'task_id', 1);
            $status = RequestValidator::enum($data->status ?? '', ['open', 'in_progress', 'done', 'cancelled'], 'status');
            $stmt = $db->prepare("UPDATE nurse_tasks SET status = ? WHERE id = ?");
            $stmt->execute([$status, $taskId]);
            Realtime::emit('nurse.task', []);
            echo json_encode(["message" => "Task updated"]);
        }
        break;

    // 10. ESCALATIONS
    case 'escalation_create':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $reason = RequestValidator::requireString($data, 'reason', 3, 1000);
            $severity = RequestValidator::enum($data->severity ?? 'urgent', ['low', 'normal', 'high', 'urgent'], 'severity');
            $status = RequestValidator::enum($data->status ?? 'open', ['open', 'triaged', 'closed'], 'status');
            $stmt = $db->prepare("INSERT INTO nurse_escalations (patient_id, reason, severity, status)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$patientId, $reason, $severity, $status]);
            Realtime::emit('nurse.escalation', ['patient_id' => $patientId]);
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
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $summary = RequestValidator::requireString($data, 'summary', 5, 4000);
            $status = RequestValidator::enum($data->status ?? 'pending', ['pending', 'approved', 'completed'], 'status');
            $check = $db->prepare("SELECT 1 FROM nurse_escalations WHERE patient_id = ? LIMIT 1");
            $check->execute([$patientId]);
            if (!$check->fetchColumn()) {
                http_response_code(400);
                echo json_encode(["message" => "Only escalated patients can be discharged."]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO discharge_summaries (patient_id, summary, status)
                                  VALUES (?, ?, ?)");
            $stmt->execute([$patientId, $summary, $status]);
            Realtime::emit('nurse.discharge_summary', ['patient_id' => $patientId]);
            echo json_encode(["message" => "Discharge summary saved"]);
        }
        break;

    case 'discharge_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT d.*, p.full_name as patient_name
                                FROM discharge_summaries d
                                JOIN patients p ON d.patient_id = p.id
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
