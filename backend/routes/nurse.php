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
DbSchema::ensureBedsCatalog($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['nurse', 'admin'], $user);

function parsePositiveInt($value) {
    $parsed = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $parsed === false ? null : (int)$parsed;
}

function normalizePrescriptionItems($items) {
    if ($items === null) {
        return [[], null];
    }
    if (!is_array($items)) {
        return [null, "Invalid prescriptions payload."];
    }

    $normalized = [];
    foreach ($items as $index => $item) {
        $entry = is_object($item) ? $item : (object)$item;

        $medicineId = null;
        if (isset($entry->medicine_id) && $entry->medicine_id !== '' && $entry->medicine_id !== null) {
            $parsedMedicineId = filter_var($entry->medicine_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($parsedMedicineId === false) {
                return [null, "Prescription #" . ($index + 1) . " has an invalid medicine."];
            }
            $medicineId = (int)$parsedMedicineId;
        }

        $manualName = trim((string)($entry->manual_name ?? ''));
        $dosage = trim((string)($entry->dosage ?? ''));
        $quantity = filter_var($entry->quantity ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);

        if ($quantity === false) {
            return [null, "Prescription #" . ($index + 1) . " has an invalid quantity."];
        }
        if ($dosage === '') {
            return [null, "Prescription #" . ($index + 1) . " requires dosage instructions."];
        }
        if (!$medicineId && $manualName === '') {
            return [null, "Prescription #" . ($index + 1) . " requires a medicine selection or manual medicine name."];
        }

        $normalized[] = [
            'medicine_id' => $medicineId,
            'manual_name' => $manualName !== '' ? $manualName : null,
            'quantity' => (int)$quantity,
            'dosage' => $dosage
        ];
    }

    return [$normalized, null];
}

function normalizeOptionalVitalsPayload($vitals) {
    if ($vitals === null) {
        return [null, null];
    }
    if (!is_object($vitals)) {
        return [null, "Invalid vitals payload."];
    }

    $temperature = filter_var($vitals->temperature ?? null, FILTER_VALIDATE_FLOAT);
    if ($temperature === false || $temperature < 30 || $temperature > 45) {
        return [null, "Temperature must be between 30C and 45C."];
    }

    $pulse = filter_var($vitals->pulse ?? null, FILTER_VALIDATE_INT);
    if ($pulse === false || $pulse < 20 || $pulse > 250) {
        return [null, "Pulse must be between 20 and 250 bpm."];
    }

    $bpRaw = trim((string)($vitals->bp ?? ''));
    if (!preg_match('/^(\d{2,3})\/(\d{2,3})$/', $bpRaw, $bpMatch)) {
        return [null, "Blood pressure must be in systolic/diastolic format."];
    }

    $systolic = (int)$bpMatch[1];
    $diastolic = (int)$bpMatch[2];
    if ($systolic < 50 || $systolic > 300 || $diastolic < 30 || $diastolic > 200 || $systolic <= $diastolic) {
        return [null, "Blood pressure values are out of safe range."];
    }

    $weight = filter_var($vitals->weight ?? null, FILTER_VALIDATE_FLOAT);
    if ($weight === false || $weight < 1 || $weight > 500) {
        return [null, "Weight must be between 1kg and 500kg."];
    }

    $spo2 = filter_var($vitals->spo2 ?? null, FILTER_VALIDATE_INT);
    if ($spo2 === false || $spo2 < 50 || $spo2 > 100) {
        return [null, "SpO2 must be between 50 and 100."];
    }

    $notes = trim((string)($vitals->notes ?? ''));
    if (strlen($notes) > 1000) {
        return [null, "Vitals notes must not exceed 1000 characters."];
    }

    return [[
        "temperature" => round((float)$temperature, 1),
        "pulse" => (int)$pulse,
        "bp" => $systolic . "/" . $diastolic,
        "weight" => round((float)$weight, 1),
        "spo2" => (int)$spo2,
        "notes" => $notes
    ], null];
}

function detectEmergencyVitals($vitals) {
    $reasons = [];
    $temperature = (float)($vitals['temperature'] ?? 0);
    $pulse = (int)($vitals['pulse'] ?? 0);
    $spo2 = (int)($vitals['spo2'] ?? 0);
    $weight = (float)($vitals['weight'] ?? 0);
    $bpRaw = (string)($vitals['bp'] ?? '');
    $bpParts = explode('/', $bpRaw);
    $systolic = isset($bpParts[0]) ? (int)$bpParts[0] : 0;
    $diastolic = isset($bpParts[1]) ? (int)$bpParts[1] : 0;

    if ($temperature >= 40 || $temperature <= 35) {
        $reasons[] = "critical temperature ({$temperature}C)";
    }
    if ($pulse <= 30 || $pulse >= 130) {
        $reasons[] = "critical pulse ({$pulse} bpm)";
    }
    if ($systolic <= 90 || $systolic >= 180 || $diastolic <= 60 || $diastolic >= 120) {
        $reasons[] = "critical blood pressure ({$systolic}/{$diastolic})";
    }
    if ($spo2 < 90) {
        $reasons[] = "low oxygen saturation ({$spo2}%)";
    }
    if ($weight <= 3 || $weight >= 250) {
        $reasons[] = "critical weight ({$weight}kg)";
    }

    return $reasons;
}

function upsertCriticalVitalsEscalation($db, $patientId, $source, $reasons) {
    if (!$patientId || empty($reasons)) return false;

    $sourceLabel = trim((string)$source) !== '' ? trim((string)$source) : 'nurse consultation';
    $reason = "Critical vitals detected during {$sourceLabel}: " . implode('; ', $reasons);

    $existing = $db->prepare("SELECT id
                              FROM nurse_escalations
                              WHERE patient_id = ?
                                AND LOWER(COALESCE(status, 'open')) = 'open'
                                AND reason LIKE 'Critical vitals detected%'
                              ORDER BY id DESC
                              LIMIT 1");
    $existing->execute([$patientId]);
    $existingId = $existing->fetchColumn();

    if ($existingId) {
        $update = $db->prepare("UPDATE nurse_escalations
                                SET reason = ?, severity = 'urgent', status = 'open'
                                WHERE id = ?");
        $update->execute([$reason, $existingId]);
        return true;
    }

    $insert = $db->prepare("INSERT INTO nurse_escalations (patient_id, reason, severity, status)
                            VALUES (?, ?, 'urgent', 'open')");
    $insert->execute([$patientId, $reason]);
    return true;
}

switch ($action) {

    // 1. DASHBOARD STATS (Nurse review + urgent + admission)
    case 'stats':
        $stmt = $db->query("SELECT COUNT(*) as count
                            FROM patient_queue
                            WHERE LOWER(status) IN ('waiting','urgent care','with nurse','admission pending')");
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

    // 3. DIRECT EMERGENCY CHECK-IN (bypass triage to nurse)
    case 'emergency_checkin':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            $patientId = parsePositiveInt($data->patient_id ?? null);
            $reason = trim((string)($data->reason ?? 'Emergency direct intake by nurse.'));
            if (!$patientId) {
                http_response_code(400);
                echo json_encode(["message" => "Patient ID is required."]);
                exit;
            }

            try {
                $patientStmt = $db->prepare("SELECT id, full_name, national_id FROM patients WHERE id = ? LIMIT 1");
                $patientStmt->execute([$patientId]);
                $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);
                if (!$patient) {
                    http_response_code(404);
                    echo json_encode(["message" => "Patient not found."]);
                    exit;
                }

                $db->beginTransaction();

                $activeStmt = $db->prepare("SELECT id, status
                                            FROM patient_queue
                                            WHERE patient_id = ?
                                              AND LOWER(status) NOT IN ('completed','cancelled','discharged')
                                            ORDER BY created_at DESC, id DESC
                                            LIMIT 1");
                $activeStmt->execute([$patientId]);
                $active = $activeStmt->fetch(PDO::FETCH_ASSOC);

                $queueId = null;
                $targetStatus = 'With Nurse';
                $assignedUserId = isset($user->id) ? $user->id : null;

                if ($active) {
                    $queueId = (int)$active['id'];
                    $current = strtolower(trim((string)($active['status'] ?? '')));
                    $takeoverStatuses = ['waiting', 'urgent care', 'in triage', 'with doctor', 'admission pending', 'with nurse'];
                    if (!in_array($current, $takeoverStatuses, true)) {
                        $db->rollBack();
                        http_response_code(409);
                        echo json_encode(["message" => "Patient is already in active workflow ({$active['status']})."]);
                        exit;
                    }
                    $upd = $db->prepare("UPDATE patient_queue SET status = ?, doctor_assigned = ? WHERE id = ?");
                    $upd->execute([$targetStatus, $assignedUserId, $queueId]);
                } else {
                    $ins = $db->prepare("INSERT INTO patient_queue (patient_id, doctor_assigned, status) VALUES (?, ?, ?)");
                    $ins->execute([$patientId, $assignedUserId, $targetStatus]);
                    $queueId = (int)$db->lastInsertId();
                }

                $escalationReason = "Emergency direct intake: " . $reason;
                $dupEsc = $db->prepare("SELECT id
                                        FROM nurse_escalations
                                        WHERE patient_id = ?
                                          AND LOWER(TRIM(reason)) = LOWER(TRIM(?))
                                          AND LOWER(COALESCE(status, 'open')) = 'open'
                                          AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                                        ORDER BY id DESC
                                        LIMIT 1");
                $dupEsc->execute([$patientId, $escalationReason]);
                if (!$dupEsc->fetchColumn()) {
                    $insEsc = $db->prepare("INSERT INTO nurse_escalations (patient_id, reason, severity, status)
                                            VALUES (?, ?, 'urgent', 'open')");
                    $insEsc->execute([$patientId, $escalationReason]);
                }

                $db->commit();

                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Direct emergency nurse intake', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$queueId,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse',
                        'source' => 'nurse/emergency_checkin',
                        'metadata' => ['patient_id' => (string)$patientId, 'reason' => $reason]
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }

                Realtime::emit('nurse.emergency_checkin', ['queue_id' => $queueId, 'patient_id' => $patientId]);
                Realtime::emit('reception.emergency_direct', ['queue_id' => $queueId, 'patient_id' => $patientId, 'to' => 'nurse']);

                echo json_encode([
                    "message" => "Emergency patient checked in to nurse successfully. Reception has been notified.",
                    "queue_id" => $queueId,
                    "patient_id" => (int)$patient['id'],
                    "patient_name" => $patient['full_name'],
                    "status" => $targetStatus
                ]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Failed emergency check-in: " . $e->getMessage()]);
            }
        }
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
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->bed_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Bed ID required"]);
                exit;
            }
            $bedStmt = $db->prepare("SELECT current_patient_id, status FROM beds WHERE id = ? LIMIT 1");
            $bedStmt->execute([$data->bed_id]);
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
            if($stmt->execute([$data->bed_id])) {
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

                $stmt = $db->prepare("UPDATE beds SET status = 'Occupied', current_patient_id = ? WHERE id = ?");
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
    
    // 4d. NURSE REVIEW LIST (post-triage + admission/pharmacy)
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
                                  (SELECT COUNT(*) FROM prescriptions pr WHERE pr.patient_id = p.id AND pr.status IN ('Pending','External')) as pending_prescriptions,
                                  v.bp,
                                  v.temperature,
                                  v.pulse,
                                  v.weight,
                                  v.spo2,
                                  v.notes as triage_notes,
                                  (SELECT COUNT(*)
                                   FROM nurse_escalations ne
                                   WHERE ne.patient_id = p.id
                                     AND LOWER(COALESCE(ne.status, 'open')) = 'open'
                                     AND LOWER(COALESCE(ne.severity, 'urgent')) = 'urgent') as emergency_count
                          FROM patient_queue q
                          JOIN patients p ON q.patient_id = p.id
                          LEFT JOIN (
                              SELECT pv.*
                              FROM patient_vitals pv
                              INNER JOIN (
                                  SELECT queue_id, MAX(id) as latest_id
                                  FROM patient_vitals
                                  WHERE queue_id IS NOT NULL
                                  GROUP BY queue_id
                              ) latest ON latest.latest_id = pv.id
                          ) v ON v.queue_id = q.id
                          WHERE LOWER(q.status) IN ('with nurse','admission pending','waiting pharmacy','ready for admission')
                          ORDER BY emergency_count DESC, q.created_at DESC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Admission waiting list error: " . $e->getMessage()]);
            }
        }
        break;

    // 4d2. MEDICINES (for nurse consultation prescriptions)
    case 'medicines':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT id, name, stock_quantity FROM medicines ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 4d3. SAVE NURSE CONSULTATION (diagnosis + optional prescriptions)
    case 'consult_save':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            $queueId = parsePositiveInt($data->queue_id ?? null);
            $patientId = parsePositiveInt($data->patient_id ?? null);
            $notes = trim((string)($data->notes ?? ''));
            $sendToPharmacy = filter_var($data->send_to_pharmacy ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$queueId || !$patientId) {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID and patient ID are required."]);
                exit;
            }
            if ($notes === '') {
                http_response_code(400);
                echo json_encode(["message" => "Diagnosis notes are required."]);
                exit;
            }
            list($vitalsPayload, $vitalsError) = normalizeOptionalVitalsPayload($data->vitals ?? null);
            if ($vitalsError) {
                http_response_code(400);
                echo json_encode(["message" => $vitalsError]);
                exit;
            }

            list($prescriptions, $prescriptionError) = normalizePrescriptionItems($data->prescriptions ?? []);
            if ($prescriptionError) {
                http_response_code(400);
                echo json_encode(["message" => $prescriptionError]);
                exit;
            }

            try {
                $db->beginTransaction();

                $queueStmt = $db->prepare("SELECT id, patient_id, status FROM patient_queue WHERE id = ? LIMIT 1");
                $queueStmt->execute([$queueId]);
                $queue = $queueStmt->fetch(PDO::FETCH_ASSOC);
                if (!$queue || (int)$queue['patient_id'] !== $patientId) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["message" => "Queue entry does not match patient."]);
                    exit;
                }

                $statusLower = strtolower((string)($queue['status'] ?? ''));
                if (!in_array($statusLower, ['with nurse', 'admission pending', 'waiting pharmacy', 'ready for admission'], true)) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["message" => "This patient cannot be reviewed from the nurse queue right now."]);
                    exit;
                }

                $consultStmt = $db->prepare("INSERT INTO nurse_consultations (queue_id, patient_id, nurse_id, diagnosis_notes)
                                             VALUES (?, ?, ?, ?)");
                $consultStmt->execute([
                    $queueId,
                    $patientId,
                    isset($user->id) ? $user->id : null,
                    $notes
                ]);

                $emergencyReasons = [];
                $isEmergency = false;
                $vitalsSaved = false;
                if ($vitalsPayload) {
                    $vitalsStmt = $db->prepare("INSERT INTO patient_vitals
                                                (patient_id, queue_id, temperature, pulse, bp, weight, spo2, notes)
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $vitalsStmt->execute([
                        $patientId,
                        $queueId,
                        $vitalsPayload['temperature'],
                        $vitalsPayload['pulse'],
                        $vitalsPayload['bp'],
                        $vitalsPayload['weight'],
                        $vitalsPayload['spo2'],
                        $vitalsPayload['notes'] !== '' ? $vitalsPayload['notes'] : 'Captured during nurse consultation.'
                    ]);
                    $vitalsSaved = true;

                    $emergencyReasons = detectEmergencyVitals($vitalsPayload);
                    if (!empty($emergencyReasons)) {
                        upsertCriticalVitalsEscalation($db, $patientId, 'nurse consultation', $emergencyReasons);
                        $isEmergency = true;
                    }
                }

                $rxCount = 0;
                if (!empty($prescriptions)) {
                    $rxStmt = $db->prepare("INSERT INTO prescriptions (patient_id, medicine_id, quantity, dosage, notes, status)
                                            VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($prescriptions as $p) {
                        $status = $p['medicine_id'] ? 'Pending' : 'External';
                        $rxStmt->execute([
                            $patientId,
                            $p['medicine_id'],
                            $p['quantity'],
                            $p['dosage'],
                            $p['manual_name'],
                            $status
                        ]);
                        $rxCount += 1;
                    }
                }

                if ($sendToPharmacy) {
                    $existingReq = $db->prepare("SELECT id FROM pharmacy_requests WHERE patient_id = ? AND LOWER(status) IN ('pending', 'ready') LIMIT 1");
                    $existingReq->execute([$patientId]);
                    if (!$existingReq->fetchColumn()) {
                        $requestStmt = $db->prepare("INSERT INTO pharmacy_requests (patient_id, requested_by, status, notes)
                                                     VALUES (?, ?, 'Pending', ?)");
                        $requestStmt->execute([
                            $patientId,
                            isset($user->id) ? $user->id : null,
                            'Nurse consultation completed and forwarded to pharmacy.'
                        ]);
                    }

                    $db->prepare("UPDATE patient_queue SET status = 'Waiting Pharmacy' WHERE id = ?")
                       ->execute([$queueId]);
                }

                $db->commit();

                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Completed nurse consultation', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$queueId,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse',
                        'source' => 'nurse/consult_save',
                        'metadata' => [
                            'prescriptions_added' => $rxCount,
                            'sent_to_pharmacy' => $sendToPharmacy ? 'yes' : 'no'
                        ]
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }

                Realtime::emit('nurse.consult', ['queue_id' => $queueId, 'patient_id' => $patientId]);
                if ($sendToPharmacy) {
                    Realtime::emit('pharmacy.request', ['patient_id' => $patientId]);
                }
                if ($isEmergency) {
                    Realtime::emit('nurse.escalation', ['patient_id' => $patientId, 'queue_id' => $queueId]);
                }

                echo json_encode([
                    "message" => $sendToPharmacy
                        ? "Consultation saved and sent to pharmacy."
                        : "Consultation saved successfully.",
                    "prescriptions_added" => $rxCount,
                    "sent_to_pharmacy" => $sendToPharmacy,
                    "vitals_saved" => $vitalsSaved,
                    "emergency" => $isEmergency,
                    "emergency_reasons" => $emergencyReasons
                ]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Failed to save consultation: " . $e->getMessage()]);
            }
        }
        break;

    // 4e. REFER CASE TO DOCTOR (when nurse cannot resolve)
    case 'refer_doctor':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->queue_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID required."]);
                exit;
            }
            try {
                $db->beginTransaction();

                $rowStmt = $db->prepare("SELECT patient_id, status FROM patient_queue WHERE id = ? LIMIT 1");
                $rowStmt->execute([$data->queue_id]);
                $queue = $rowStmt->fetch(PDO::FETCH_ASSOC);
                if (!$queue) {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(["message" => "Queue entry not found."]);
                    exit;
                }

                $statusLower = strtolower((string)($queue['status'] ?? ''));
                if (!in_array($statusLower, ['with nurse', 'urgent care', 'admission pending'], true)) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["message" => "Only nurse-reviewed patients can be referred to doctor."]);
                    exit;
                }

                // Clear nurse assignment from queue slot before doctor takes over.
                $upd = $db->prepare("UPDATE patient_queue SET status = 'With Doctor', doctor_assigned = NULL WHERE id = ?");
                $upd->execute([$data->queue_id]);

                $reason = trim((string)($data->reason ?? 'Nurse could not resolve case.'));
                if (!empty($queue['patient_id'])) {
                    $esc = $db->prepare("INSERT INTO nurse_escalations (patient_id, reason, severity, status)
                                         VALUES (?, ?, 'urgent', 'open')");
                    $esc->execute([$queue['patient_id'], $reason]);
                }

                $db->commit();
                Realtime::emit('nurse.refer_doctor', ['queue_id' => $data->queue_id]);
                echo json_encode(["message" => "Patient referred to doctor."]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Failed to refer patient: " . $e->getMessage()]);
            }
        }
        break;

    // 4f. REQUEST PHARMACY (nurse resolved OR doctor-admission path)
    case 'pharmacy_request':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->patient_id) || !isset($data->queue_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient ID and queue ID required."]);
                exit;
            }
            try {
                $db->beginTransaction();

                $qStmt = $db->prepare("SELECT id, patient_id, status FROM patient_queue WHERE id = ? LIMIT 1");
                $qStmt->execute([$data->queue_id]);
                $queue = $qStmt->fetch(PDO::FETCH_ASSOC);
                if (!$queue || (int)$queue['patient_id'] !== (int)$data->patient_id) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["message" => "Queue entry does not match patient."]);
                    exit;
                }

                $statusLower = strtolower((string)($queue['status'] ?? ''));
                if (!in_array($statusLower, ['with nurse', 'admission pending', 'waiting pharmacy', 'ready for admission'], true)) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["message" => "This queue item cannot be sent to pharmacy."]);
                    exit;
                }

                $check = $db->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ? AND status IN ('Pending','External')");
                $check->execute([$data->patient_id]);
                $count = (int)$check->fetchColumn();

                // For 'With Nurse' path we allow pharmacy handoff even without doctor prescriptions.
                if ($count === 0 && $statusLower !== 'with nurse') {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(["message" => "No pending prescriptions for this patient."]);
                    exit;
                }
                // Prevent duplicate pending/ready pharmacy requests for same patient
                $existing = $db->prepare("SELECT id FROM pharmacy_requests WHERE patient_id = ? AND LOWER(status) IN ('pending','ready') LIMIT 1");
                $existing->execute([$data->patient_id]);
                if ($existing->fetchColumn()) {
                    $db->rollBack();
                    echo json_encode(["message" => "Pharmacy request already pending for this patient.", "pending_prescriptions" => $count]);
                    exit;
                }
                $insert = $db->prepare("INSERT INTO pharmacy_requests (patient_id, requested_by, status, notes) VALUES (?, ?, 'Pending', ?)");
                $insert->execute([
                    $data->patient_id,
                    isset($user->id) ? $user->id : null,
                    $data->notes ?? null
                ]);

                // Move nurse/admission flow into pharmacy stage.
                $db->prepare("UPDATE patient_queue
                              SET status = 'Waiting Pharmacy'
                              WHERE id = ?")
                   ->execute([$data->queue_id]);
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
                Realtime::emit('pharmacy.request', ['patient_id' => $data->patient_id]);
                echo json_encode([
                    "message" => "Pharmacy request sent.",
                    "pending_prescriptions" => $count,
                    "path" => ($statusLower === 'with nurse' ? 'nurse_to_pharmacy' : 'doctor_to_pharmacy')
                ]);
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
            $patientId = parsePositiveInt($data->patient_id ?? null);
            $reason = trim((string)($data->reason ?? ''));
            if (!$patientId || $reason === '') {
                http_response_code(400);
                echo json_encode(["message" => "Patient and reason required"]);
                exit;
            }
            $dupStmt = $db->prepare("SELECT id
                                     FROM nurse_escalations
                                     WHERE patient_id = ?
                                       AND LOWER(TRIM(reason)) = LOWER(TRIM(?))
                                       AND LOWER(COALESCE(status, 'open')) = 'open'
                                       AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                                     ORDER BY id DESC
                                     LIMIT 1");
            $dupStmt->execute([$patientId, $reason]);
            $existingId = $dupStmt->fetchColumn();
            if ($existingId) {
                echo json_encode([
                    "message" => "Duplicate escalation ignored.",
                    "duplicate" => true,
                    "escalation_id" => (int)$existingId
                ]);
                break;
            }
            $severity = trim((string)($data->severity ?? 'urgent')) ?: 'urgent';
            $status = trim((string)($data->status ?? 'open')) ?: 'open';
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
