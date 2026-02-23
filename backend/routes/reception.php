<?php
// FILE: backend/routes/reception.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Realtime.php';
require_once __DIR__ . '/../utils/DbSchema.php';

$database = new Database();
$db = $database->getConnection();

DbSchema::ensureAppointments($db);
DbSchema::ensureReceptionHandover($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

switch ($action) {

    // 1. DASHBOARD STATS (Matches loadDashboardStats() in dashboard.html)
    case 'stats':
        if ($method === 'GET') {
            try {
                // Count today's total visits
                $stmt = $db->query("SELECT COUNT(*) FROM patient_queue WHERE DATE(created_at) = CURDATE()");
                $today = $stmt->fetchColumn();

                // Count Pending Triage (Waiting status)
                $stmt = $db->query("SELECT COUNT(*) FROM patient_queue WHERE status = 'Waiting'");
                $pending = $stmt->fetchColumn();

                // Get Patient Flow for the current day
                $flowQuery = "SELECT q.created_at, p.full_name, q.doctor_assigned, q.status
                              FROM patient_queue q
                              JOIN patients p ON q.patient_id = p.id
                              WHERE DATE(q.created_at) = CURDATE()
                              ORDER BY q.created_at DESC";
                $flow = $db->query($flowQuery)->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    "today" => (int)$today,
                    "pending" => (int)$pending,
                    "flow" => $flow ?: []
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Stats Error: " . $e->getMessage()]);
            }
        }
        break;

    // 2. SEARCH PATIENTS (Matches searchPatient() in dashboard.html)
    case 'search':
        if ($method === 'GET') {
            $q = isset($_GET['q']) ? $_GET['q'] : '';
            if(strlen($q) < 2) { echo json_encode([]); exit; }

            $sql = "SELECT p.*,
                   (SELECT COUNT(*) FROM patient_queue q
                    WHERE q.patient_id = p.id
                    AND q.status NOT IN ('Completed', 'Cancelled', 'completed', 'cancelled')) as is_active
                    FROM patients p
                    WHERE p.full_name LIKE ? OR p.phone LIKE ? OR p.national_id LIKE ?
                    LIMIT 5";

            $stmt = $db->prepare($sql);
            $stmt->execute(["%$q%", "%$q%", "%$q%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 3. REGISTER NEW PATIENT
    case 'register':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $fullName = RequestValidator::requireString($data, 'full_name', 3, 150);
            $nationalId = RequestValidator::requireString($data, 'national_id', 5, 30, '/^[A-Za-z0-9\-]+$/');
            $dob = RequestValidator::requireString($data, 'dob', 10, 10, '/^\d{4}\-\d{2}\-\d{2}$/');
            $gender = RequestValidator::enum($data->gender ?? '', ['male', 'female', 'other'], 'gender');
            $phone = RequestValidator::requireString($data, 'phone', 7, 25, '/^[0-9\+\-\s]+$/');
            $address = RequestValidator::requireString($data, 'address', 3, 255);
            $hasMedicalAid = !empty($data->has_medical_aid) ? 1 : 0;
            $aidProvider = RequestValidator::optionalString($data, 'medical_aid_provider', 120);
            $aidNumber = RequestValidator::optionalString($data, 'medical_aid_number', 60);
            $kinName = RequestValidator::optionalString($data, 'kin_name', 150);
            $kinRelation = RequestValidator::optionalString($data, 'kin_relation', 80);
            $kinPhone = RequestValidator::optionalString($data, 'kin_phone', 25);
            $allergies = RequestValidator::optionalString($data, 'allergies', 1000);

            $sql = "INSERT INTO patients (
                        full_name, national_id, dob, gender, phone, address,
                        has_medical_aid, medical_aid_provider, medical_aid_number,
                        kin_name, kin_relation, kin_phone, allergies
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);

            $params = [
                $fullName,
                $nationalId,
                $dob,
                $gender,
                $phone,
                $address,
                $hasMedicalAid,
                $aidProvider,
                $aidNumber,
                $kinName,
                $kinRelation,
                $kinPhone,
                $allergies
            ];

            if($stmt->execute($params)) {
                $newPatientId = $db->lastInsertId();
                Realtime::emit('reception.register', ['patient_id' => $newPatientId]);
                echo json_encode(["message" => "Patient Registered Successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Database error during registration"]);
            }
        }
        break;

    // 4. ADMIT PATIENT (Simplified workflow)
    case 'admit':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $doctorId = isset($data->doctor) && $data->doctor !== '' ? RequestValidator::requireInt($data, 'doctor', 1) : null;

            $check = $db->prepare("SELECT id FROM patient_queue WHERE patient_id = ? AND status NOT IN ('Completed', 'Cancelled', 'completed', 'cancelled')");
            $check->execute([$patientId]);
            if($check->rowCount() > 0) {
                 http_response_code(400);
                 echo json_encode(["message" => "Patient is already active in the queue"]);
                 exit;
            }

            // Reception always sends to Nurse first
            $initial_status = 'Waiting';

            $sql = "INSERT INTO patient_queue (patient_id, doctor_assigned, status) VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);

            if($stmt->execute([$patientId, $doctorId, $initial_status])) {
                $queueId = $db->lastInsertId();
                Realtime::emit('reception.admit', ['queue_id' => $queueId]);
                echo json_encode(["message" => "Patient Admitted"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Admission failed"]);
            }
        }
        break;

    // 5. GET ALL PATIENTS
    case 'all_patients':
        if ($method === 'GET') {
            $sql = "SELECT p.*,
                   (SELECT COUNT(*) FROM patient_queue q
                    WHERE q.patient_id = p.id
                    AND q.status NOT IN ('Completed', 'Cancelled', 'completed', 'cancelled')) as is_active
                    FROM patients p
                    ORDER BY p.created_at DESC LIMIT 50";

            $stmt = $db->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 6. GET RECEPTIONIST SHIFTS (Matches loadMyShifts() in dashboard.html)
    case 'shifts':
        if ($method === 'GET') {
            try {
                // Queries the staff_shifts table identified in your phpMyAdmin
                $query = "SELECT * FROM staff_shifts WHERE role = 'receptionist' ORDER BY shift_start ASC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to load roster: " . $e->getMessage()]);
            }
        }
        break;

    // 4b. CREATE APPOINTMENT
    case 'create_appointment':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $scheduledAt = RequestValidator::requireString($data, 'scheduled_at', 16, 19, '/^\d{4}\-\d{2}\-\d{2}[ T]\d{2}\:\d{2}(\:\d{2})?$/');
            $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, scheduled_at, status, reason, notes)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $doctorId = isset($data->doctor_id) && $data->doctor_id !== '' ? RequestValidator::requireInt($data, 'doctor_id', 1) : null;
            $status = RequestValidator::enum($data->status ?? 'scheduled', ['scheduled', 'confirmed', 'checked_in', 'cancelled', 'completed'], 'status');
            $reason = RequestValidator::optionalString($data, 'reason', 1000);
            $notes = RequestValidator::optionalString($data, 'notes', 2000);
            if ($stmt->execute([$patientId, $doctorId, $scheduledAt, $status, $reason, $notes])) {
                $newId = $db->lastInsertId();
                Realtime::emit('reception.appointment', ['appointment_id' => $newId]);
                echo json_encode(["message" => "Appointment created"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to create appointment"]);
            }
        }
        break;

    // 1b. DASHBOARD ANALYTICS
    case 'analytics':
        if ($method === 'GET') {
            $admitRows = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count
                                     FROM patient_queue
                                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                     GROUP BY DATE(created_at)
                                     ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $regRows = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count
                                   FROM patients
                                   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                   GROUP BY DATE(created_at)
                                   ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $statusRows = $db->query("SELECT status, COUNT(*) as count
                                      FROM patient_queue
                                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                      GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $apptRows = $db->query("SELECT status, COUNT(*) as count
                                    FROM appointments
                                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                    GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                "admissions_by_day" => $admitRows,
                "registrations_by_day" => $regRows,
                "queue_by_status" => $statusRows,
                "appointments_by_status" => $apptRows
            ]);
        }
        break;

    // 4c. LIST APPOINTMENTS
    case 'appointments':
        if ($method === 'GET') {
            $start = $_GET['start'] ?? null;
            $end = $_GET['end'] ?? null;
            $where = [];
            $params = [];
            if ($start) { $where[] = "scheduled_at >= ?"; $params[] = $start; }
            if ($end) { $where[] = "scheduled_at <= ?"; $params[] = $end; }
            $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";
            $sql = "SELECT a.*, p.full_name as patient_name, u.full_name as doctor_name
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    LEFT JOIN users u ON a.doctor_id = u.id
                    $whereSql
                    ORDER BY a.scheduled_at DESC
                    LIMIT 200";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 4d. UPDATE APPOINTMENT STATUS
    case 'appointment_update':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $appointmentId = RequestValidator::requireInt($data, 'appointment_id', 1);
            $status = RequestValidator::enum($data->status ?? '', ['scheduled', 'confirmed', 'checked_in', 'cancelled', 'completed'], 'status');
            $notes = RequestValidator::optionalString($data, 'notes', 2000);
            $stmt = $db->prepare("UPDATE appointments SET status = ?, notes = COALESCE(?, notes) WHERE id = ?");
            if ($stmt->execute([$status, $notes, $appointmentId])) {
                Realtime::emit('reception.appointment_update', ['appointment_id' => $appointmentId]);
                echo json_encode(["message" => "Appointment updated"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to update appointment"]);
            }
        }
        break;

    // 4e. CHECK-IN APPOINTMENT (optionally push to queue)
    case 'checkin':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $appointmentId = RequestValidator::requireInt($data, 'appointment_id', 1);
            $stmt = $db->prepare("UPDATE appointments SET status = 'checked_in' WHERE id = ?");
            $stmt->execute([$appointmentId]);
            if (!empty($data->queue_patient_id)) {
                $queuePatientId = RequestValidator::requireInt($data, 'queue_patient_id', 1);
                // Avoid duplicate active queue rows for same patient
                $active = $db->prepare("SELECT id FROM patient_queue
                                        WHERE patient_id = ?
                                          AND LOWER(status) NOT IN ('completed','cancelled','discharged')
                                        ORDER BY created_at DESC, id DESC
                                        LIMIT 1");
                $active->execute([$queuePatientId]);
                $activeId = $active->fetchColumn();
                if ($activeId) {
                    if (!empty($data->doctor_assigned)) {
                        $doctorAssigned = RequestValidator::requireInt($data, 'doctor_assigned', 1);
                        $upd = $db->prepare("UPDATE patient_queue SET doctor_assigned = ? WHERE id = ?");
                        $upd->execute([$doctorAssigned, $activeId]);
                    }
                } else {
                    $stmt2 = $db->prepare("INSERT INTO patient_queue (patient_id, doctor_assigned, status) VALUES (?, ?, 'Waiting')");
                    $doctorAssigned = !empty($data->doctor_assigned) ? RequestValidator::requireInt($data, 'doctor_assigned', 1) : null;
                    $stmt2->execute([$queuePatientId, $doctorAssigned]);
                }
            }
            Realtime::emit('reception.checkin', ['appointment_id' => $appointmentId]);
            echo json_encode(["message" => "Checked in"]);
        }
        break;

    // 4f. QUEUE LIST (Active)
    case 'queue_list':
        if ($method === 'GET') {
            $sql = "SELECT q.id as queue_id, q.status, q.created_at, q.doctor_assigned,
                           p.full_name, p.national_id, p.phone
                    FROM patient_queue q
                    JOIN patients p ON q.patient_id = p.id
                    WHERE q.status NOT IN ('Completed','Cancelled','completed','cancelled')
                    ORDER BY q.created_at ASC";
            $stmt = $db->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 4g. QUEUE UPDATE (cancel, reassign, no_show, move_top)
    case 'queue_update':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $queueId = RequestValidator::requireInt($data, 'queue_id', 1);
            $actionType = RequestValidator::enum($data->action ?? '', ['cancel', 'no_show', 'reassign', 'move_top'], 'action');
            if ($actionType === 'cancel' || $actionType === 'no_show') {
                $stmt = $db->prepare("UPDATE patient_queue SET status = 'Cancelled' WHERE id = ?");
                $stmt->execute([$queueId]);
            } else if ($actionType === 'reassign') {
                $doctorAssigned = isset($data->doctor_assigned) && $data->doctor_assigned !== '' ? RequestValidator::requireInt($data, 'doctor_assigned', 1) : null;
                $stmt = $db->prepare("UPDATE patient_queue SET doctor_assigned = ? WHERE id = ?");
                $stmt->execute([$doctorAssigned, $queueId]);
            } else if ($actionType === 'move_top') {
                $stmt = $db->prepare("UPDATE patient_queue SET created_at = NOW() WHERE id = ?");
                $stmt->execute([$queueId]);
            }
            Realtime::emit('reception.queue_update', ['queue_id' => $queueId]);
            echo json_encode(["message" => "Queue updated"]);
        }
        break;

    // 4h. REFERRAL LOG (create)
    case 'create_referral':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $reportName = RequestValidator::requireString($data, 'report_name', 3, 255);
            $filePath = RequestValidator::optionalString($data, 'file_path', 255);
            $stmt = $db->prepare("INSERT INTO medical_reports (patient_id, report_type, report_name, file_path)
                                  VALUES (?, 'Referral', ?, ?)");
            $stmt->execute([$patientId, $reportName, $filePath]);
            Realtime::emit('reception.referral', ['patient_id' => $patientId]);
            echo json_encode(["message" => "Referral logged"]);
        }
        break;

    // 4i. CONSENT LOG (create)
    case 'create_consent':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $reportName = RequestValidator::requireString($data, 'report_name', 3, 255);
            $filePath = RequestValidator::optionalString($data, 'file_path', 255);
            $stmt = $db->prepare("INSERT INTO medical_reports (patient_id, report_type, report_name, file_path)
                                  VALUES (?, 'Consent', ?, ?)");
            $stmt->execute([$patientId, $reportName, $filePath]);
            Realtime::emit('reception.consent', ['patient_id' => $patientId]);
            echo json_encode(["message" => "Consent logged"]);
        }
        break;

    // 4i-b. UPLOAD DOCUMENT
    case 'upload_document':
        if ($method === 'POST') {
            $patient_id = $_POST['patient_id'] ?? null;
            $report_name = $_POST['report_name'] ?? null;
            if (!$patient_id || !$report_name || empty($_FILES["report_file"]["tmp_name"])) {
                http_response_code(400);
                echo json_encode(["message" => "Patient, name, and file are required"]);
                exit;
            }
            $target_dir = "../../uploads/patient_files/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $file_name = time() . "_" . basename($_FILES["report_file"]["name"]);
            if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_dir . $file_name)) {
                $db->prepare("INSERT INTO medical_reports (patient_id, report_type, report_name, file_path) VALUES (?, 'Document', ?, ?)")
                   ->execute([$patient_id, $report_name, "uploads/patient_files/" . $file_name]);
                Realtime::emit('reception.document', ['patient_id' => $patient_id]);
                echo json_encode(["message" => "Upload success"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "File upload failed"]);
            }
        }
        break;

    // 4j. DAILY SUMMARY
    case 'summary':
        if ($method === 'GET') {
            $date = $_GET['date'] ?? date('Y-m-d');
            $stmt = $db->prepare("SELECT COUNT(*) FROM patient_queue WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $admitted = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $registered = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM patient_queue WHERE DATE(created_at) = ? AND status IN ('Cancelled','cancelled')");
            $stmt->execute([$date]);
            $cancelled = (int)$stmt->fetchColumn();

            echo json_encode([
                'date' => $date,
                'registered' => $registered,
                'admitted' => $admitted,
                'cancelled' => $cancelled
            ]);
        }
        break;

    // 4k. SHIFT HANDOVER
    case 'handover_create':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $userId = RequestValidator::requireInt($data, 'user_id', 1);
            $notes = RequestValidator::requireString($data, 'notes', 3, 2000);
            $shiftStart = RequestValidator::optionalString($data, 'shift_start', 40);
            $shiftEnd = RequestValidator::optionalString($data, 'shift_end', 40);
            $stmt = $db->prepare("INSERT INTO reception_handover (user_id, shift_start, shift_end, notes)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $shiftStart, $shiftEnd, $notes]);
            Realtime::emit('reception.handover', ['user_id' => $userId]);
            echo json_encode(["message" => "Handover saved"]);
        }
        break;

    case 'handover_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT h.*, u.full_name
                                FROM reception_handover h
                                LEFT JOIN users u ON h.user_id = u.id
                                ORDER BY h.created_at DESC
                                LIMIT 50");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 6b. REFILL REQUEST (Receptionist)
    case 'refill_create':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $medicineName = RequestValidator::requireString($data, 'medicine_name', 2, 120);
            $quantity = isset($data->quantity) ? RequestValidator::requireInt($data, 'quantity', 1, 1000) : 1;
            $notes = RequestValidator::optionalString($data, 'notes', 1000);
            $requestedBy = isset($data->requested_by) && $data->requested_by !== '' ? RequestValidator::requireInt($data, 'requested_by', 1) : null;
            $stmt = $db->prepare("INSERT INTO refill_requests (patient_id, medicine_name, quantity, notes, requested_by, status)
                                  VALUES (?, ?, ?, ?, ?, 'Requested')");
            $stmt->execute([
                $patientId,
                $medicineName,
                $quantity,
                $notes,
                $requestedBy
            ]);
            Realtime::emit('pharmacy.refill', ['patient_id' => $patientId]);
            echo json_encode(["message" => "Refill request submitted"]);
        }
        break;

    // 7. GET PATIENT HISTORY (Vitals + Visits)
    case 'history':
        if ($method === 'GET') {
            try {
                $pid = $_GET['patient_id'] ?? 0;
                if (!$pid) {
                    http_response_code(400);
                    echo json_encode(["message" => "Patient ID required"]);
                    exit;
                }

                $stmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
                $stmt->execute([$pid]);
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $db->prepare("SELECT * FROM patient_vitals WHERE patient_id = ? ORDER BY created_at DESC LIMIT 50");
                $stmt->execute([$pid]);
                $vitals = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($vitals as &$v) {
                    $v = array_merge($v, VitalRisk::classify($v['temperature'] ?? null, $v['pulse'] ?? null, $v['bp'] ?? null, $v['spo2'] ?? null));
                }
                unset($v);

                $stmt = $db->prepare("SELECT * FROM patient_queue WHERE patient_id = ? ORDER BY created_at DESC LIMIT 50");
                $stmt->execute([$pid]);
                $visits = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $stmt = $db->prepare("SELECT pr.*, m.name as medicine_name FROM prescriptions pr LEFT JOIN medicines m ON pr.medicine_id = m.id WHERE pr.patient_id = ? ORDER BY pr.created_at DESC");
                $stmt->execute([$pid]);
                $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $stmt = $db->prepare("SELECT * FROM medical_reports WHERE patient_id = ? ORDER BY created_at DESC");
                $stmt->execute([$pid]);
                $files = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                echo json_encode([
                    "patient" => $patient ?: [],
                    "vitals" => $vitals,
                    "visits" => $visits,
                    "prescriptions" => $prescriptions,
                    "files" => $files
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "History Error: " . $e->getMessage()]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Reception action not found"]);
        break;
}
?>
