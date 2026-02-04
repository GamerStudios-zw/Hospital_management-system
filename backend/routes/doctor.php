<?php
// FILE: backend/routes/doctor.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';
require_once __DIR__ . '/../utils/Realtime.php';
require_once __DIR__ . '/../utils/DbSchema.php';

$database = new Database();
$db = $database->getConnection();
DbSchema::ensureDoctorModules($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['doctor', 'admin'], $user);

switch ($action) {

    // 1. GET WAITING LIST (Strictly Triage Cleared)
    case 'waiting_list':
        if ($method === 'GET') {
            // REMOVED 'Waiting' from the IN clause to ensure Nurse priority
            $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.dob, p.gender, q.status,
                             v.bp, v.temperature, v.pulse, v.spo2
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      LEFT JOIN patient_vitals v ON q.id = v.queue_id
                      WHERE q.status IN ('With Doctor', 'with doctor')
                      ORDER BY q.created_at ASC";
            $stmt = $db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 2. COMPLETE VISIT WITH MULTIPLE/EXTERNAL PRESCRIPTIONS
    case 'complete_multiple':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->queue_id) || empty($data->notes)) {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID and Clinical Notes are required."]);
                exit;
            }
            try {
                $db->beginTransaction();

                // Fetch Patient ID
                $stmt = $db->prepare("SELECT patient_id FROM patient_queue WHERE id = ?");
                $stmt->execute([$data->queue_id]);
                $patient_id = $stmt->fetchColumn();

                if(!empty($data->prescriptions)) {
                    // medicine_id is NULL for external items; name stored in 'notes' column
                    $sql = "INSERT INTO prescriptions (patient_id, medicine_id, quantity, dosage, notes, status)
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    foreach($data->prescriptions as $p) {
                        $status = $p->medicine_id ? 'Pending' : 'External';
                        $stmt->execute([
                            $patient_id,
                            $p->medicine_id ?: null,
                            $p->quantity,
                            $p->dosage,
                            $p->manual_name ?: null,
                            $status
                        ]);
                    }
                }

                // Close the visit in the queue
                $db->prepare("UPDATE patient_queue SET status = 'Completed' WHERE id = ?")
                   ->execute([$data->queue_id]);

                $db->commit();
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'doctor', 'Completed consultation', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$data->queue_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'doctor',
                        'source' => 'doctor/complete_multiple'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('doctor.complete', ['queue_id' => $data->queue_id]);
                echo json_encode(["message" => "Consultation finalized successfully."]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
            }
        }
        break;

    // 2b. SEND PATIENT BACK TO NURSE (Urgent Care)
    case 'urgent_care':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->queue_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID required."]);
                exit;
            }
            try {
                $stmt = $db->prepare("UPDATE patient_queue SET status = 'Urgent Care' WHERE id = ?");
                $stmt->execute([$data->queue_id]);
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'doctor', 'Returned to nurse (urgent)', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$data->queue_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'doctor',
                        'source' => 'doctor/urgent_care',
                        'metadata' => !empty($data->reason) ? ['reason' => $data->reason] : null
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('doctor.urgent_care', ['queue_id' => $data->queue_id]);
                echo json_encode(["message" => "Patient returned to nurse for urgent care."]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
            }
        }
        break;

    // 3. GET DOCTOR SHIFTS (New logic for Roster tab)
    case 'shifts':
        if ($method === 'GET') {
            try {
                // Filters for 'doctor' role to match Admin assignments
                $query = "SELECT * FROM staff_shifts WHERE role = 'doctor' ORDER BY shift_start ASC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to load roster: " . $e->getMessage()]);
            }
        }
        break;

    // 4. GET PATIENT HISTORY
    case 'history':
        if ($method === 'GET') {
            $pid = $_GET['patient_id'] ?? 0;
            $history = [
                "patient" => $db->query("SELECT * FROM patients WHERE id = $pid")->fetch(PDO::FETCH_ASSOC),
                "vitals" => $db->query("SELECT * FROM patient_vitals WHERE patient_id = $pid ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC),
                "visits" => $db->query("SELECT * FROM patient_queue WHERE patient_id = $pid ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC),
                "prescriptions" => $db->query("SELECT pr.*, m.name as medicine_name FROM prescriptions pr LEFT JOIN medicines m ON pr.medicine_id = m.id WHERE pr.patient_id = $pid ORDER BY pr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC),
                "files" => $db->query("SELECT * FROM medical_reports WHERE patient_id = $pid ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC)
            ];
            echo json_encode($history);
        }
        break;

    // 5. UPLOAD PATIENT DOCUMENTS
    case 'upload_file':
        if ($method === 'POST') {
            $patient_id = $_POST['patient_id'];
            $report_name = $_POST['report_name'];
            $target_dir = "../../uploads/patient_files/";

            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $file_name = time() . "_" . basename($_FILES["report_file"]["name"]);
            if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_dir . $file_name)) {
                $db->prepare("INSERT INTO medical_reports (patient_id, report_name, file_path) VALUES (?, ?, ?)")
                   ->execute([$patient_id, $report_name, "uploads/patient_files/" . $file_name]);
                echo json_encode(["message" => "Upload success"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "File upload failed"]);
            }
        }
        break;

    // 6. HELPER DATA
    case 'medicines':
        echo json_encode($db->query("SELECT id, name, stock_quantity FROM medicines WHERE stock_quantity > 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'patients':
        echo json_encode($db->query("SELECT * FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC));
        break;

    // 6b. ANALYTICS
    case 'analytics':
        if ($method === 'GET') {
            $completed = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count
                                     FROM patient_queue
                                     WHERE status IN ('Completed','completed')
                                     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                     GROUP BY DATE(created_at)
                                     ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $rxRows = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count
                                  FROM prescriptions
                                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                  GROUP BY DATE(created_at)
                                  ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $urgent = (int)$db->query("SELECT COUNT(*) FROM patient_queue WHERE status IN ('Urgent Care','urgent care')")->fetchColumn();

            echo json_encode([
                "completed_by_day" => $completed,
                "prescriptions_by_day" => $rxRows,
                "urgent_current" => $urgent
            ]);
        }
        break;

    // 7. DOCTOR HANDOVER
    case 'handover_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->user_id) || empty($data->notes)) {
                http_response_code(400);
                echo json_encode(["message" => "User and notes required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO doctor_handover (user_id, shift_start, shift_end, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->user_id, $data->shift_start ?? null, $data->shift_end ?? null, $data->notes]);
            Realtime::emit('doctor.handover', ['user_id' => $data->user_id]);
            echo json_encode(["message" => "Handover saved"]);
        }
        break;

    case 'handover_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT h.*, u.full_name FROM doctor_handover h LEFT JOIN users u ON h.user_id = u.id ORDER BY h.created_at DESC LIMIT 50");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 8. DOCTOR TASK BOARD
    case 'task_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->task)) {
                http_response_code(400);
                echo json_encode(["message" => "Task required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO doctor_tasks (patient_id, assigned_to, task, priority, status, due_at)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data->patient_id ?? null,
                $data->assigned_to ?? null,
                $data->task,
                $data->priority ?? 'normal',
                $data->status ?? 'open',
                $data->due_at ?? null
            ]);
            Realtime::emit('doctor.task', []);
            echo json_encode(["message" => "Task created"]);
        }
        break;

    case 'task_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT t.*, p.full_name as patient_name, u.full_name as doctor_name
                                FROM doctor_tasks t
                                LEFT JOIN patients p ON t.patient_id = p.id
                                LEFT JOIN users u ON t.assigned_to = u.id
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
            $stmt = $db->prepare("UPDATE doctor_tasks SET status = ? WHERE id = ?");
            $stmt->execute([$data->status, $data->task_id]);
            Realtime::emit('doctor.task', []);
            echo json_encode(["message" => "Task updated"]);
        }
        break;

    // 9. DOCTOR ESCALATIONS
    case 'escalation_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || empty($data->reason)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and reason required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO doctor_escalations (patient_id, reason, severity, status)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->patient_id, $data->reason, $data->severity ?? 'urgent', $data->status ?? 'open']);
            Realtime::emit('doctor.escalation', ['patient_id' => $data->patient_id]);
            echo json_encode(["message" => "Escalation created"]);
        }
        break;

    case 'escalation_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT e.*, p.full_name as patient_name
                                FROM doctor_escalations e
                                JOIN patients p ON e.patient_id = p.id
                                ORDER BY e.created_at DESC
                                LIMIT 100");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 10. DISCHARGE SUMMARY
    case 'discharge_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || empty($data->summary)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and summary required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO discharge_summaries (patient_id, summary, status)
                                  VALUES (?, ?, ?)");
            $stmt->execute([$data->patient_id, $data->summary, $data->status ?? 'pending']);
            Realtime::emit('doctor.discharge_summary', ['patient_id' => $data->patient_id]);
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

    // 11. PATIENT TIMELINE
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
        echo json_encode(["message" => "Action not found"]);
        break;
}
?>
