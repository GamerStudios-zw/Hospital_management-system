<?php
// FILE: backend/routes/nurse_aid.php
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
RoleMiddleware::allow(['nurse_aid', 'admin'], $user);

switch ($action) {

    // 1. DASHBOARD STATS (Matches triage widget)
    case 'stats':
        $stmt = $db->query("SELECT COUNT(*) as count
                            FROM patient_queue q
                            WHERE q.status IN ('Waiting','waiting','Urgent Care','urgent care')
                              AND NOT EXISTS (
                                  SELECT 1 FROM appointments a
                                  WHERE a.patient_id = q.patient_id
                                    AND DATE(a.scheduled_at) = CURDATE()
                                    AND a.status IN ('scheduled','confirmed','checked_in')
                              )");
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo json_encode([
            "pending_triage" => (int)$pending
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

            $urgentCount = (int)$db->query("SELECT COUNT(*) FROM patient_queue WHERE status IN ('Urgent Care','urgent care')")->fetchColumn();

            echo json_encode([
                "triage_by_day" => $triageRows,
                "urgent_count" => $urgentCount
            ]);
        }
        break;

    // 2. TRIAGE QUEUE (Matches triageTable in dashboard)
    case 'triage_queue':
        if ($method === 'GET') {
            $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.national_id, p.dob, p.gender, q.created_at, q.status
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      WHERE q.status IN ('Waiting', 'waiting', 'Urgent Care', 'urgent care', 'In Triage', 'in triage')
                        AND NOT EXISTS (
                            SELECT 1 FROM appointments a
                            WHERE a.patient_id = q.patient_id
                              AND DATE(a.scheduled_at) = CURDATE()
                              AND a.status IN ('scheduled','confirmed','checked_in')
                        )
                      ORDER BY (q.status IN ('Urgent Care', 'urgent care')) DESC, q.created_at ASC";
            $stmt = $db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 2b. ADMITTED PATIENTS (Daily vitals list)
    case 'admitted_patients':
        if ($method === 'GET') {
            $query = "SELECT b.id as bed_id, b.ward_name, b.bed_number, p.id as patient_id, p.full_name, p.national_id, p.dob, p.gender,
                             q.id as queue_id,
                             (SELECT MAX(created_at) FROM patient_vitals pv WHERE pv.patient_id = p.id) as last_vitals_at,
                             (SELECT temperature FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_temp,
                             (SELECT temperature FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_temp,
                             (SELECT pulse FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_pulse,
                             (SELECT pulse FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_pulse,
                             (SELECT bp FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_bp,
                             (SELECT bp FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_bp,
                             (SELECT spo2 FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_spo2,
                             (SELECT spo2 FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_spo2,
                             (SELECT weight FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_weight,
                             (SELECT weight FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_weight,
                             'admitted' as source
                      FROM beds b
                      JOIN patients p ON b.current_patient_id = p.id
                      LEFT JOIN (
                          SELECT q1.*
                          FROM patient_queue q1
                          INNER JOIN (
                              SELECT patient_id, MAX(id) as latest_id
                              FROM patient_queue
                              GROUP BY patient_id
                          ) latest ON latest.latest_id = q1.id
                      ) q ON q.patient_id = p.id
                      WHERE b.status = 'Occupied'
                      UNION ALL
                      SELECT NULL as bed_id, 'Urgent Care' as ward_name, NULL as bed_number, p.id as patient_id, p.full_name, p.national_id, p.dob, p.gender,
                             q.id as queue_id,
                             (SELECT MAX(created_at) FROM patient_vitals pv WHERE pv.patient_id = p.id) as last_vitals_at,
                             (SELECT temperature FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_temp,
                             (SELECT temperature FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_temp,
                             (SELECT pulse FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_pulse,
                             (SELECT pulse FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_pulse,
                             (SELECT bp FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_bp,
                             (SELECT bp FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_bp,
                             (SELECT spo2 FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_spo2,
                             (SELECT spo2 FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_spo2,
                             (SELECT weight FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1) as last_weight,
                             (SELECT weight FROM patient_vitals pv WHERE pv.patient_id = p.id ORDER BY pv.created_at DESC LIMIT 1,1) as prev_weight,
                             'urgent' as source
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      WHERE q.status IN ('Urgent Care','urgent care')
                        AND NOT EXISTS (
                            SELECT 1 FROM beds b2
                            WHERE b2.current_patient_id = q.patient_id
                              AND b2.status = 'Occupied'
                        )
                      ORDER BY ward_name, bed_number";
            $stmt = $db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 3. SAVE VITALS (Full data mapping for weight and SpO2)
    case 'save_vitals':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if(!isset($data->queue_id) || !isset($data->patient_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Missing Patient or Queue ID"]);
                exit;
            }

            try {
                $db->beginTransaction();

                // Prevent duplicate vitals rows for the same queue item.
                // If already captured once, update the same entry instead of inserting another row.
                $check = $db->prepare("SELECT id FROM patient_vitals WHERE queue_id = ? ORDER BY id DESC LIMIT 1");
                $check->execute([$data->queue_id]);
                $existingVitalId = $check->fetchColumn();

                if ($existingVitalId) {
                    $sql = "UPDATE patient_vitals
                            SET temperature = ?, pulse = ?, bp = ?, weight = ?, spo2 = ?, notes = ?, created_at = NOW()
                            WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $data->temperature,
                        $data->pulse,
                        $data->bp,
                        $data->weight,
                        $data->spo2,
                        $data->notes,
                        $existingVitalId
                    ]);
                } else {
                    $sql = "INSERT INTO patient_vitals (patient_id, queue_id, temperature, pulse, bp, weight, spo2, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $data->patient_id,
                        $data->queue_id,
                        $data->temperature,
                        $data->pulse,
                        $data->bp,
                        $data->weight,
                        $data->spo2,
                        $data->notes
                    ]);
                }

                // Update status to 'With Doctor' after triage is complete
                $upd = $db->prepare("UPDATE patient_queue SET status = 'With Doctor' WHERE id = ?");
                $upd->execute([$data->queue_id]);

                $db->commit();

                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse aid', 'Captured vitals', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$data->queue_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse_aid',
                        'source' => 'nurse_aid/save_vitals'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }

                Realtime::emit('nurse.save_vitals', ['queue_id' => $data->queue_id]);
                echo json_encode(["message" => "Vitals saved successfully."]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Failed to save vitals: " . $e->getMessage()]);
            }
        }
        break;

    // 3c. SAVE DAILY VITALS (Admitted patients)
    case 'save_daily_vitals':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if(!isset($data->queue_id) || !isset($data->patient_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Missing Patient or Queue ID"]);
                exit;
            }

            try {
                $db->beginTransaction();

                $check = $db->prepare("SELECT id FROM patient_vitals WHERE patient_id = ? AND DATE(created_at) = CURDATE() ORDER BY id DESC LIMIT 1");
                $check->execute([$data->patient_id]);
                $existingVitalId = $check->fetchColumn();

                if ($existingVitalId) {
                    $sql = "UPDATE patient_vitals
                            SET temperature = ?, pulse = ?, bp = ?, weight = ?, spo2 = ?, notes = ?
                            WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $data->temperature,
                        $data->pulse,
                        $data->bp,
                        $data->weight,
                        $data->spo2,
                        $data->notes,
                        $existingVitalId
                    ]);
                } else {
                    $sql = "INSERT INTO patient_vitals (patient_id, queue_id, temperature, pulse, bp, weight, spo2, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $data->patient_id,
                        $data->queue_id,
                        $data->temperature,
                        $data->pulse,
                        $data->bp,
                        $data->weight,
                        $data->spo2,
                        $data->notes
                    ]);
                }

                $db->commit();

                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse aid', 'Daily vitals recorded', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_vitals',
                        'entity_id' => isset($existingVitalId) ? (string)$existingVitalId : null,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse_aid',
                        'source' => 'nurse_aid/save_daily_vitals'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }

                echo json_encode(["message" => "Daily vitals saved successfully."]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Failed to save daily vitals: " . $e->getMessage()]);
            }
        }
        break;

    // 3b. START TRIAGE (Move Waiting -> In Triage)
    case 'start_triage':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->queue_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID required"]);
                exit;
            }
            try {
                $stmt = $db->prepare("UPDATE patient_queue SET status = 'In Triage' WHERE id = ? AND status IN ('Waiting','waiting','Urgent Care','urgent care')");
                $stmt->execute([$data->queue_id]);
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse aid', 'Started triage', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$data->queue_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse_aid',
                        'source' => 'nurse_aid/start_triage'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('nurse.start_triage', ['queue_id' => $data->queue_id]);
                echo json_encode(["message" => "Triage started"]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to start triage: " . $e->getMessage()]);
            }
        }
        break;

    // 4. URGENT CARE PATIENTS (for task assignment)
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

    // 5. PATIENT HISTORY (read-only)
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

                echo json_encode([
                    "recent" => $recent,
                    "trend" => [
                        "labels" => $labels,
                        "counts" => $counts
                    ]
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "History Error: " . $e->getMessage()]);
            }
        }
        break;

    // 6. PATIENTS LIST (for nurse aid modules)
    case 'patients':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT id, full_name, national_id, dob, gender FROM patients ORDER BY full_name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 7. TASK BOARD
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

    // 8. ESCALATIONS
    case 'escalation_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || empty($data->reason)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and reason required"]);
                exit;
            }
            $check = $db->prepare("SELECT 1 FROM patient_queue WHERE patient_id = ? AND status IN ('Waiting','waiting','Urgent Care','urgent care','In Triage','in triage') LIMIT 1");
            $check->execute([$data->patient_id]);
            if (!$check->fetchColumn()) {
                http_response_code(400);
                echo json_encode(["message" => "Escalations must be for patients in the triage station."]);
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

    // 9. DISCHARGE SUMMARY (read-only)
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

    default:
        echo json_encode(["message" => "Nurse Aid endpoint not found"]);
        break;
}
?>
