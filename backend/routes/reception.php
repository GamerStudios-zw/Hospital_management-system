<?php
// FILE: backend/routes/reception.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Realtime.php';
require_once __DIR__ . '/../utils/DbSchema.php';

$database = new Database();
$db = $database->getConnection();

DbSchema::ensureAppointments($db);
DbSchema::ensureReceptionHandover($db);
DbSchema::ensureStaffShifts($db);

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

                // Count Pending Triage (Waiting + Urgent)
                $stmt = $db->query("SELECT COUNT(*) FROM patient_queue WHERE LOWER(status) IN ('waiting', 'urgent care')");
                $pending = $stmt->fetchColumn();

                // Get Patient Flow for the current day
                $flowQuery = "SELECT q.created_at, p.full_name,
                                     COALESCE(u.full_name, CAST(q.doctor_assigned AS CHAR)) AS doctor_assigned,
                                     q.status
                              FROM patient_queue q
                              JOIN patients p ON q.patient_id = p.id
                              LEFT JOIN users u ON u.id = q.doctor_assigned
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
            $data = json_decode(file_get_contents("php://input"));
            $payload = is_object($data) ? $data : (object)[];

            $fullName = trim((string)($payload->full_name ?? ''));
            $nationalId = strtoupper(trim((string)($payload->national_id ?? '')));
            $dob = trim((string)($payload->dob ?? ''));
            $gender = trim((string)($payload->gender ?? ''));
            $phone = trim((string)($payload->phone ?? ''));
            $phoneKey = preg_replace('/\s+/', '', $phone);
            $address = trim((string)($payload->address ?? ''));
            $hasAid = !empty($payload->has_medical_aid) ? 1 : 0;
            $aidProvider = trim((string)($payload->medical_aid_provider ?? ''));
            $aidNumber = trim((string)($payload->medical_aid_number ?? ''));
            $kinName = trim((string)($payload->kin_name ?? ''));
            $kinRelation = trim((string)($payload->kin_relation ?? ''));
            $kinPhone = trim((string)($payload->kin_phone ?? ''));
            $allergies = trim((string)($payload->allergies ?? ''));

            if ($fullName === '' || $nationalId === '' || $dob === '' || $gender === '' || $phone === '') {
                http_response_code(400);
                echo json_encode(["message" => "Full name, ID, DOB, gender, and phone are required.", "success" => false]);
                break;
            }

            $lockSeed = $nationalId !== '' ? $nationalId : strtoupper($fullName . '|' . $dob . '|' . $phoneKey);
            $lockKey = 'hms:register:' . substr(hash('sha256', $lockSeed), 0, 48);
            $lockAcquired = false;

            try {
                $lockStmt = $db->prepare("SELECT GET_LOCK(?, 5)");
                $lockStmt->execute([$lockKey]);
                $lockAcquired = ((int)$lockStmt->fetchColumn() === 1);

                if (!$lockAcquired) {
                    http_response_code(429);
                    echo json_encode(["message" => "Registration is busy. Please try again.", "success" => false]);
                    break;
                }

                $duplicateStmt = $db->prepare("SELECT id, full_name FROM patients WHERE UPPER(TRIM(national_id)) = ? ORDER BY id ASC LIMIT 1");
                $duplicateStmt->execute([$nationalId]);
                $existing = $duplicateStmt->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    http_response_code(409);
                    echo json_encode([
                        "message" => "A patient with this National ID already exists.",
                        "patient_id" => (int)$existing['id'],
                        "full_name" => $existing['full_name'] ?? null,
                        "success" => false
                    ]);
                    break;
                }

                // Secondary guard when IDs are accidentally reused/blank in old records.
                $identityStmt = $db->prepare("SELECT id, full_name
                                              FROM patients
                                              WHERE UPPER(TRIM(full_name)) = ?
                                                AND dob = ?
                                                AND REPLACE(TRIM(phone), ' ', '') = ?
                                              ORDER BY id ASC
                                              LIMIT 1");
                $identityStmt->execute([strtoupper($fullName), $dob, $phoneKey]);
                $identityMatch = $identityStmt->fetch(PDO::FETCH_ASSOC);
                if ($identityMatch) {
                    http_response_code(409);
                    echo json_encode([
                        "message" => "An identical patient record already exists.",
                        "patient_id" => (int)$identityMatch['id'],
                        "full_name" => $identityMatch['full_name'] ?? null,
                        "success" => false
                    ]);
                    break;
                }

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
                    $hasAid,
                    $aidProvider,
                    $aidNumber,
                    $kinName,
                    $kinRelation,
                    $kinPhone,
                    $allergies
                ];

                if ($stmt->execute($params)) {
                    $newPatientId = (int)$db->lastInsertId();
                    Realtime::emit('reception.register', ['patient_id' => $newPatientId]);
                    echo json_encode([
                        "message" => "Patient Registered Successfully",
                        "patient_id" => $newPatientId,
                        "success" => true
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Database error during registration", "success" => false]);
                }
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '23000') {
                    http_response_code(409);
                    echo json_encode(["message" => "A matching patient record already exists.", "success" => false]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Database error during registration", "success" => false]);
                }
            } finally {
                if ($lockAcquired) {
                    try {
                        $unlockStmt = $db->prepare("SELECT RELEASE_LOCK(?)");
                        $unlockStmt->execute([$lockKey]);
                    } catch (Exception $e) {
                        // Ignore unlock errors.
                    }
                }
            }
        }
        break;

    // 4. ADMIT PATIENT (Simplified workflow)
    case 'admit':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if (!isset($data->patient_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient ID required"]);
                exit;
            }

            $check = $db->prepare("SELECT id FROM patient_queue WHERE patient_id = ? AND status NOT IN ('Completed', 'Cancelled', 'completed', 'cancelled')");
            $check->execute([$data->patient_id]);
            if($check->rowCount() > 0) {
                 http_response_code(400);
                 echo json_encode(["message" => "Patient is already active in the queue"]);
                 exit;
            }

            // Critical admissions are marked urgent so triage picks them first.
            $isCritical = !empty($data->is_critical);
            $initial_status = $isCritical ? 'Urgent Care' : 'Waiting';

            $sql = "INSERT INTO patient_queue (patient_id, doctor_assigned, status) VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);

            // Reception sends patient to triage first; nurse assignment happens after vitals capture.
            if($stmt->execute([$data->patient_id, null, $initial_status])) {
                $queueId = $db->lastInsertId();
                Realtime::emit('reception.admit', ['queue_id' => $queueId]);
                echo json_encode([
                    "message" => "Patient admitted to triage",
                    "queue_id" => (int)$queueId,
                    "status" => $initial_status,
                    "next_stage" => "triage"
                ]);
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
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || !isset($data->scheduled_at)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient ID and schedule required"]);
                exit;
            }
            $patientId = (int)$data->patient_id;
            $scheduledAt = trim((string)$data->scheduled_at);
            $doctorId = isset($data->doctor_id) && $data->doctor_id !== '' ? (int)$data->doctor_id : null;
            if ($patientId <= 0 || $scheduledAt === '') {
                http_response_code(400);
                echo json_encode(["message" => "Valid patient and schedule required"]);
                exit;
            }

            $lockSeed = $patientId . '|' . ($doctorId !== null ? $doctorId : 0) . '|' . $scheduledAt;
            $lockKey = 'hms:appt:' . substr(hash('sha256', $lockSeed), 0, 48);
            $lockAcquired = false;

            try {
                $lockStmt = $db->prepare("SELECT GET_LOCK(?, 5)");
                $lockStmt->execute([$lockKey]);
                $lockAcquired = ((int)$lockStmt->fetchColumn() === 1);
                if (!$lockAcquired) {
                    http_response_code(429);
                    echo json_encode(["message" => "Appointment booking is busy. Please retry."]);
                    exit;
                }

                $dupStmt = $db->prepare("SELECT id
                                         FROM appointments
                                         WHERE patient_id = ?
                                           AND scheduled_at = ?
                                           AND ((doctor_id IS NULL AND ? IS NULL) OR doctor_id = ?)
                                           AND LOWER(COALESCE(status, 'scheduled')) NOT IN ('cancelled', 'completed')
                                         LIMIT 1");
                $dupStmt->execute([$patientId, $scheduledAt, $doctorId, $doctorId]);
                $duplicate = $dupStmt->fetch(PDO::FETCH_ASSOC);
                if ($duplicate) {
                    http_response_code(409);
                    echo json_encode([
                        "message" => "An identical appointment already exists.",
                        "appointment_id" => (int)$duplicate['id']
                    ]);
                    exit;
                }

                $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, scheduled_at, status, reason, notes)
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $status = $data->status ?? 'scheduled';
                $reason = $data->reason ?? null;
                $notes = $data->notes ?? null;
                if ($stmt->execute([$patientId, $doctorId, $scheduledAt, $status, $reason, $notes])) {
                    $newId = $db->lastInsertId();
                    Realtime::emit('reception.appointment', ['appointment_id' => $newId]);
                    echo json_encode(["message" => "Appointment created"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Failed to create appointment"]);
                }
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '23000') {
                    http_response_code(409);
                    echo json_encode(["message" => "An identical appointment already exists."]);
                } else {
                    throw $e;
                }
            } finally {
                if ($lockAcquired) {
                    try {
                        $unlockStmt = $db->prepare("SELECT RELEASE_LOCK(?)");
                        $unlockStmt->execute([$lockKey]);
                    } catch (Exception $e) {
                        // Ignore unlock errors.
                    }
                }
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
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->appointment_id) || !isset($data->status)) {
                http_response_code(400);
                echo json_encode(["message" => "Appointment ID and status required"]);
                exit;
            }
            $stmt = $db->prepare("UPDATE appointments SET status = ?, notes = COALESCE(?, notes) WHERE id = ?");
            if ($stmt->execute([$data->status, $data->notes ?? null, $data->appointment_id])) {
                Realtime::emit('reception.appointment_update', ['appointment_id' => $data->appointment_id]);
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
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->appointment_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Appointment ID required"]);
                exit;
            }
            $stmt = $db->prepare("UPDATE appointments SET status = 'checked_in' WHERE id = ?");
            $stmt->execute([$data->appointment_id]);
            if (!empty($data->queue_patient_id)) {
                // Avoid duplicate active queue rows for same patient
                $active = $db->prepare("SELECT id FROM patient_queue
                                        WHERE patient_id = ?
                                          AND LOWER(status) NOT IN ('completed','cancelled','discharged')
                                        ORDER BY created_at DESC, id DESC
                                        LIMIT 1");
                $active->execute([$data->queue_patient_id]);
                $activeId = $active->fetchColumn();
                if ($activeId) {
                    if (!empty($data->doctor_assigned)) {
                        $upd = $db->prepare("UPDATE patient_queue SET doctor_assigned = ? WHERE id = ?");
                        $upd->execute([$data->doctor_assigned, $activeId]);
                    }
                } else {
                    $stmt2 = $db->prepare("INSERT INTO patient_queue (patient_id, doctor_assigned, status) VALUES (?, ?, 'Waiting')");
                    $stmt2->execute([$data->queue_patient_id, $data->doctor_assigned ?? null]);
                }
            }
            Realtime::emit('reception.checkin', ['appointment_id' => $data->appointment_id]);
            echo json_encode(["message" => "Checked in"]);
        }
        break;

    // 4f. QUEUE LIST (Active)
    case 'queue_list':
        if ($method === 'GET') {
            $sql = "SELECT q.id as queue_id, q.status, q.created_at,
                           COALESCE(u.full_name, CAST(q.doctor_assigned AS CHAR)) AS doctor_assigned,
                           p.full_name, p.national_id, p.phone
                    FROM patient_queue q
                    JOIN patients p ON q.patient_id = p.id
                    LEFT JOIN users u ON u.id = q.doctor_assigned
                    WHERE LOWER(q.status) NOT IN ('completed','cancelled','discharged')
                    ORDER BY q.created_at ASC";
            $stmt = $db->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 4g. QUEUE UPDATE (cancel, reassign, no_show, move_top)
    case 'queue_update':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->queue_id) || !isset($data->action)) {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID and action required"]);
                exit;
            }
            $stateCheck = $db->prepare("SELECT status FROM patient_queue WHERE id = ? LIMIT 1");
            $stateCheck->execute([$data->queue_id]);
            $currentStatus = strtolower((string)$stateCheck->fetchColumn());
            if ($currentStatus === '') {
                http_response_code(404);
                echo json_encode(["message" => "Queue item not found"]);
                exit;
            }
            if (!in_array($currentStatus, ['waiting', 'urgent care'], true)) {
                http_response_code(409);
                echo json_encode(["message" => "This queue item is already in clinical workflow and cannot be updated by reception."]);
                exit;
            }
            $actionType = $data->action;
            if ($actionType === 'cancel' || $actionType === 'no_show') {
                $stmt = $db->prepare("UPDATE patient_queue SET status = 'Cancelled' WHERE id = ?");
                $stmt->execute([$data->queue_id]);
            } else if ($actionType === 'reassign') {
                $stmt = $db->prepare("UPDATE patient_queue SET doctor_assigned = ? WHERE id = ?");
                $stmt->execute([$data->doctor_assigned ?? null, $data->queue_id]);
            } else if ($actionType === 'move_top') {
                $stmt = $db->prepare("UPDATE patient_queue SET created_at = NOW() WHERE id = ?");
                $stmt->execute([$data->queue_id]);
            }
            Realtime::emit('reception.queue_update', ['queue_id' => $data->queue_id]);
            echo json_encode(["message" => "Queue updated"]);
        }
        break;

    // 4h. REFERRAL LOG (create)
    case 'create_referral':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || !isset($data->report_name)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and report name required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO medical_reports (patient_id, report_type, report_name, file_path)
                                  VALUES (?, 'Referral', ?, ?)");
            $stmt->execute([$data->patient_id, $data->report_name, $data->file_path ?? null]);
            Realtime::emit('reception.referral', ['patient_id' => $data->patient_id]);
            echo json_encode(["message" => "Referral logged"]);
        }
        break;

    // 4i. CONSENT LOG (create)
    case 'create_consent':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || !isset($data->report_name)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and report name required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO medical_reports (patient_id, report_type, report_name, file_path)
                                  VALUES (?, 'Consent', ?, ?)");
            $stmt->execute([$data->patient_id, $data->report_name, $data->file_path ?? null]);
            Realtime::emit('reception.consent', ['patient_id' => $data->patient_id]);
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
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->user_id) || !isset($data->notes)) {
                http_response_code(400);
                echo json_encode(["message" => "User ID and notes required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO reception_handover (user_id, shift_start, shift_end, notes)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->user_id, $data->shift_start ?? null, $data->shift_end ?? null, $data->notes]);
            Realtime::emit('reception.handover', ['user_id' => $data->user_id]);
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
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || empty($data->medicine_name)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and medicine required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO refill_requests (patient_id, medicine_name, quantity, notes, requested_by, status)
                                  VALUES (?, ?, ?, ?, ?, 'Requested')");
            $stmt->execute([
                $data->patient_id,
                $data->medicine_name,
                $data->quantity ?? 1,
                $data->notes ?? null,
                $data->requested_by ?? null
            ]);
            Realtime::emit('pharmacy.refill', ['patient_id' => $data->patient_id]);
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

                $stmt = $db->prepare("SELECT q.id, q.patient_id, q.status, q.created_at, q.updated_at,
                                             COALESCE(u.full_name, CAST(q.doctor_assigned AS CHAR)) AS doctor_assigned
                                      FROM patient_queue q
                                      LEFT JOIN users u ON u.id = q.doctor_assigned
                                      WHERE q.patient_id = ?
                                      ORDER BY q.created_at DESC
                                      LIMIT 50");
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
