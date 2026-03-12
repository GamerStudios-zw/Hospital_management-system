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
DbSchema::ensureNurseInChargeRole($db, true);
DbSchema::ensureVisitEncounters($db);
DbSchema::ensureCounsellingWorkflow($db);
DbSchema::ensurePrescriptionWorkflow($db);
DbSchema::ensureDoctorModules($db);
DbSchema::ensureReferralRegistry($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['doctor', 'nurse_in_charge', 'admin'], $user);

$normalizeDateTimeInput = function ($raw): ?string {
    if ($raw === null) return null;
    $value = trim((string)$raw);
    if ($value === '') return null;
    $candidate = str_replace('T', ' ', $value);
    $formats = ['Y-m-d H:i:s', 'Y-m-d H:i'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $candidate);
        if ($dt && $dt->format($fmt) === $candidate) {
            return $dt->format('Y-m-d H:i:s');
        }
    }
    return null;
};

$resolveVisitIdForPatient = function (int $patientId, ?int $visitId = null) use ($db): int {
    $candidate = (int)$visitId;
    if ($candidate > 0) return $candidate;

    $fallback = $db->prepare("SELECT id
                              FROM visits
                              WHERE patient_id = ?
                              ORDER BY id DESC
                              LIMIT 1");
    $fallback->execute([$patientId]);
    return (int)$fallback->fetchColumn();
};

$persistVisitClinicalRecord = function (
    int $patientId,
    ?int $visitId,
    string $notes,
    ?int $doctorId,
    string $visitStatus = 'completed',
    ?string $outcome = null
) use ($db, $resolveVisitIdForPatient) {
    $targetVisitId = $resolveVisitIdForPatient($patientId, $visitId);
    if ($targetVisitId <= 0) return;

    $status = in_array($visitStatus, ['waiting', 'triaged', 'in_consultation', 'pharmacy', 'completed', 'cancelled'], true)
        ? $visitStatus
        : 'completed';
    $cleanNotes = trim((string)$notes);
    $cleanOutcome = $outcome === null ? null : trim((string)$outcome);
    if ($cleanOutcome === '') $cleanOutcome = null;

    $stmt = $db->prepare("UPDATE visits
                          SET clinical_notes = ?,
                              doctor_id = COALESCE(doctor_id, ?),
                              status = ?,
                              outcome = COALESCE(?, outcome),
                              completed_at = NOW()
                          WHERE id = ?");
    $stmt->execute([$cleanNotes, $doctorId, $status, $cleanOutcome, $targetVisitId]);
};

$insertCounsellingSession = function (
    int $patientId,
    ?int $queueId,
    ?int $visitId,
    ?int $counsellorId,
    string $notes,
    string $outcome,
    ?string $safetyPlan = null,
    ?string $followUpAt = null,
    ?string $escalationReason = null
) use ($db) {
    $stmt = $db->prepare("INSERT INTO counselling_sessions
                          (patient_id, queue_id, visit_id, counsellor_id, notes, outcome, safety_plan, follow_up_at, escalation_reason)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $patientId,
        $queueId,
        $visitId,
        $counsellorId,
        trim((string)$notes),
        trim((string)$outcome),
        $safetyPlan !== null && trim((string)$safetyPlan) !== '' ? trim((string)$safetyPlan) : null,
        $followUpAt,
        $escalationReason !== null && trim((string)$escalationReason) !== '' ? trim((string)$escalationReason) : null
    ]);
};

$insertPrescriptionRecord = function (
    int $patientId,
    int $queueId,
    ?int $visitId,
    $payload
) use ($db) {
    if (!is_object($payload)) return null;

    $medicineId = isset($payload->medicine_id) && is_numeric($payload->medicine_id) ? (int)$payload->medicine_id : 0;
    if ($medicineId <= 0) $medicineId = null;

    $quantity = isset($payload->quantity) && is_numeric($payload->quantity) ? (int)$payload->quantity : 1;
    if ($quantity <= 0) $quantity = 1;

    $isInjection = false;
    $dosage = trim((string)($payload->dosage ?? ''));
    if ($dosage === '') {
        $dosage = 'As directed';
    } else {
        $dosage = preg_replace('/\s+/', ' ', $dosage);
        $dosage = preg_replace('/\bi\s*x\s*(\d+)\b/i', 'Injection for $1 days', $dosage);
        $dosage = preg_replace('/\binj(?:ection)?\b/i', 'Injection', $dosage);
        $dosage = preg_replace('/\bim\b/i', 'IM', $dosage);
        $dosage = preg_replace('/\biv\b/i', 'IV', $dosage);
        $dosage = preg_replace('/\bsc\b/i', 'SC', $dosage);
        $dosage = preg_replace('/\bid\b/i', 'ID', $dosage);
        $dosage = preg_replace('/\bq\s*(\d+)\s*h\b/i', 'every $1 hours', $dosage);
        $dosage = preg_replace('/\bInjection\s*x\s*(\d+)\b/i', 'Injection for $1 days', $dosage);
        $dosage = preg_replace('/\bx\s*(\d+)\b/i', 'for $1 days', $dosage);
        $dosage = preg_replace('/\bod\b/i', 'once daily', $dosage);
        $dosage = preg_replace('/\bbd\b/i', 'twice daily', $dosage);
        $dosage = preg_replace('/\btds\b/i', 'three times daily', $dosage);
        $dosage = preg_replace('/\bqid\b/i', 'four times daily', $dosage);
        $dosage = preg_replace('/\bnocte\b/i', 'at night', $dosage);
        $dosage = preg_replace('/\bprn\b/i', 'as needed', $dosage);
        $dosage = preg_replace('/\bstat\b/i', 'immediately', $dosage);
        $dosage = trim((string)$dosage);

        if (strlen($dosage) > 180) {
            throw new Exception("Dosage instruction is too long.");
        }

        $isInjection = preg_match('/\bInjection\b|\bIM\b|\bIV\b|\bSC\b|\bID\b/i', $dosage) === 1;
        if ($isInjection) {
            $hasRoute = preg_match('/\b(IM|IV|SC|ID|intramuscular|intravenous|subcutaneous|intradermal)\b/i', $dosage) === 1;
            $hasSchedule = preg_match('/\b(immediately|once|daily|every \d+ hours|twice daily|three times daily|four times daily|as needed|at night|for \d+ days|for \d+ doses)\b/i', $dosage) === 1;
            if (!$hasRoute || !$hasSchedule) {
                throw new Exception("Injection dosage must include route and schedule.");
            }
        }
    }

    $manualName = trim((string)($payload->manual_name ?? ''));
    $uiMedicineName = trim((string)($payload->medicine_name ?? ''));
    $medicationName = $manualName !== '' ? $manualName : $uiMedicineName;

    if ($medicationName === '' && $medicineId) {
        $medStmt = $db->prepare("SELECT name FROM medicines WHERE id = ? LIMIT 1");
        $medStmt->execute([$medicineId]);
        $resolvedName = $medStmt->fetchColumn();
        if ($resolvedName !== false && $resolvedName !== null) {
            $medicationName = trim((string)$resolvedName);
        }
    }
    if ($medicationName === '') {
        $medicationName = $medicineId ? ('Medicine #' . $medicineId) : 'External medication';
    }

    $status = $medicineId ? 'pending' : 'external';
    if ($isInjection && $medicineId) {
        // Route injectable in-stock medications to Nurse for direct administration.
        $status = 'nurse_admin_pending';
    }
    $visitRef = ($visitId !== null && (int)$visitId > 0) ? (int)$visitId : null;
    $queueRef = $queueId > 0 ? $queueId : null;

    $stmt = $db->prepare("INSERT INTO prescriptions
                          (patient_id, visit_id, queue_id, medicine_id, medication_name, quantity, dosage, notes, status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $patientId,
        $visitRef,
        $queueRef,
        $medicineId,
        $medicationName,
        $quantity,
        $dosage,
        $manualName !== '' ? $manualName : null,
        $status
    ]);
    return $status;
};

switch ($action) {

    // 1. GET WAITING LIST (Strictly Triage Cleared)
    case 'waiting_list':
        if ($method === 'GET') {
            $where = ["LOWER(q.status) = 'with doctor'"];
            $params = [];
            if (($user->role ?? '') !== 'admin') {
                $where[] = "(q.doctor_assigned IS NULL OR q.doctor_assigned = ?)";
                $params[] = (int)$user->id;
            }
            $whereSql = implode(' AND ', $where);

            $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.dob, p.gender,
                             q.status, q.visit_type, q.created_at, q.doctor_assigned,
                             v.bp, v.temperature, v.pulse, v.spo2, v.notes as nurse_notes
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      LEFT JOIN (
                          SELECT pv.*
                          FROM patient_vitals pv
                          INNER JOIN (
                              SELECT queue_id, MAX(id) as latest_id
                              FROM patient_vitals
                              GROUP BY queue_id
                          ) latest ON latest.latest_id = pv.id
                      ) v ON q.id = v.queue_id
                      WHERE $whereSql
                      ORDER BY q.created_at ASC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as &$row) {
                $row = array_merge($row, VitalRisk::classify($row['temperature'] ?? null, $row['pulse'] ?? null, $row['bp'] ?? null, $row['spo2'] ?? null));
            }
            unset($row);
            usort($rows, function($a, $b) {
                $aPriority = (int)($a['risk_priority'] ?? 0);
                $bPriority = (int)($b['risk_priority'] ?? 0);
                if ($aPriority !== $bPriority) return $bPriority <=> $aPriority;
                $aTime = isset($a['created_at']) ? strtotime((string)$a['created_at']) : 0;
                $bTime = isset($b['created_at']) ? strtotime((string)$b['created_at']) : 0;
                return $aTime <=> $bTime;
            });
            echo json_encode($rows);
        }
        break;

    // 2. COMPLETE VISIT WITH MULTIPLE/EXTERNAL PRESCRIPTIONS
    case 'complete_multiple':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $notes = trim((string)($data->notes ?? ''));
            $queueId = isset($data->queue_id) ? (int)$data->queue_id : 0;
            if ($queueId <= 0 || $notes === '') {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID and Clinical Notes are required."]);
                exit;
            }
            try {
                $db->beginTransaction();
                $routing = ['nurse_injections' => 0, 'pharmacy_pickup' => 0, 'external' => 0];

                $stmt = $db->prepare("SELECT patient_id, visit_id, visit_type
                                      FROM patient_queue
                                      WHERE id = ?
                                      LIMIT 1");
                $stmt->execute([$queueId]);
                $queueRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$queueRow) {
                    throw new Exception("Queue item was not found.");
                }
                $patient_id = (int)($queueRow['patient_id'] ?? 0);
                $visitId = isset($queueRow['visit_id']) ? (int)$queueRow['visit_id'] : 0;
                $visitType = strtolower(trim((string)($queueRow['visit_type'] ?? 'general')));
                if ($patient_id <= 0) {
                    throw new Exception("Queue item has no patient.");
                }

                // Ensure doctor_assigned is set for downstream modules (e.g., pharmacy)
                $db->prepare("UPDATE patient_queue SET doctor_assigned = COALESCE(doctor_assigned, ?) WHERE id = ?")
                   ->execute([isset($user->id) ? (int)$user->id : null, $queueId]);

                if(!empty($data->prescriptions) && is_array($data->prescriptions)) {
                    foreach($data->prescriptions as $p) {
                        $rxStatus = $insertPrescriptionRecord($patient_id, $queueId, $visitId > 0 ? $visitId : null, $p);
                        if ($rxStatus === 'nurse_admin_pending') {
                            $routing['nurse_injections']++;
                        } elseif ($rxStatus === 'pending') {
                            $routing['pharmacy_pickup']++;
                        } elseif ($rxStatus === 'external') {
                            $routing['external']++;
                        }
                    }
                }

                $persistVisitClinicalRecord(
                    $patient_id,
                    $visitId,
                    $notes,
                    isset($user->id) ? (int)$user->id : null,
                    'completed',
                    ($visitType === 'counselling_only') ? 'resolved' : 'completed'
                );

                if ($visitType === 'counselling_only') {
                    $insertCounsellingSession(
                        $patient_id,
                        $queueId,
                        $visitId > 0 ? $visitId : null,
                        isset($user->id) ? (int)$user->id : null,
                        $notes,
                        'resolved'
                    );
                }

                // Close the visit in the queue
                $db->prepare("UPDATE patient_queue SET status = 'Completed' WHERE id = ?")
                   ->execute([$queueId]);

                $db->commit();
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'doctor', 'Completed consultation', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$queueId,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'doctor',
                        'source' => 'doctor/complete_multiple'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('doctor.complete', ['queue_id' => $queueId]);
                echo json_encode([
                    "message" => "Consultation finalized successfully.",
                    "routing" => $routing
                ]);
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
            $data = RequestValidator::json();
            if (!isset($data->queue_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID required."]);
                exit;
            }
            try {
                $db->beginTransaction();
                $routing = ['nurse_injections' => 0, 'pharmacy_pickup' => 0, 'external' => 0];

                $queueStmt = $db->prepare("SELECT patient_id, visit_id FROM patient_queue WHERE id = ? LIMIT 1");
                $queueStmt->execute([(int)$data->queue_id]);
                $queueRow = $queueStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                $patient_id = $queueRow ? (int)($queueRow['patient_id'] ?? 0) : 0;
                $visitId = $queueRow ? (int)($queueRow['visit_id'] ?? 0) : 0;

                $stmt = $db->prepare("UPDATE patient_queue SET status = 'Urgent Care' WHERE id = ?");
                $stmt->execute([$data->queue_id]);
                if ($patient_id > 0 && !empty($data->reason)) {
                    $persistVisitClinicalRecord(
                        $patient_id,
                        $visitId,
                        trim((string)$data->reason),
                        isset($user->id) ? (int)$user->id : null,
                        'triaged',
                        'risk_escalation'
                    );
                }

                $bedAssigned = false;
                if ($patient_id) {
                    $existing = $db->prepare("SELECT id FROM beds WHERE current_patient_id = ? AND status = 'Occupied' LIMIT 1");
                    $existing->execute([$patient_id]);
                    $existingBedId = $existing->fetchColumn();

                    if (!$existingBedId) {
                        $bed = $db->query("SELECT id FROM beds WHERE status = 'Available' ORDER BY ward_name, bed_number LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                        if ($bed && !empty($bed['id'])) {
                            $assign = $db->prepare("UPDATE beds SET status = 'Occupied', current_patient_id = ? WHERE id = ?");
                            $assign->execute([$patient_id, $bed['id']]);
                            $bedAssigned = true;
                            Realtime::emit('nurse.assign_bed', ['bed_id' => $bed['id'], 'patient_id' => $patient_id]);
                        }
                    }
                }

                $db->commit();
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'doctor', 'Returned to nurse (urgent)', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$data->queue_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'doctor',
                        'source' => 'doctor/urgent_care',
                        'metadata' => [
                            'reason' => !empty($data->reason) ? $data->reason : null,
                            'bed_assigned' => $bedAssigned ? 'yes' : 'no'
                        ]
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('doctor.urgent_care', ['queue_id' => $data->queue_id]);
                echo json_encode(["message" => "Patient returned to nurse for urgent care.", "bed_assigned" => $bedAssigned]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
            }
        }
        break;
    
    // 2a. COMPLETE VISIT + REFER FOR ADMISSION (Urgent Care)
    case 'complete_refer':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $notes = trim((string)($data->notes ?? ''));
            $queueId = isset($data->queue_id) ? (int)$data->queue_id : 0;
            if ($queueId <= 0 || $notes === '') {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID and Clinical Notes are required."]);
                exit;
            }
            try {
                $db->beginTransaction();

                $stmt = $db->prepare("SELECT patient_id, visit_id FROM patient_queue WHERE id = ? LIMIT 1");
                $stmt->execute([$queueId]);
                $queueRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$queueRow) {
                    throw new Exception("Queue item was not found.");
                }
                $patient_id = (int)($queueRow['patient_id'] ?? 0);
                $visitId = isset($queueRow['visit_id']) ? (int)$queueRow['visit_id'] : 0;
                if ($patient_id <= 0) {
                    throw new Exception("Queue item has no patient.");
                }
                // Ensure doctor_assigned is set for downstream modules (e.g., pharmacy)
                $db->prepare("UPDATE patient_queue SET doctor_assigned = COALESCE(doctor_assigned, ?) WHERE id = ?")
                   ->execute([isset($user->id) ? (int)$user->id : null, $queueId]);

                if(!empty($data->prescriptions) && is_array($data->prescriptions)) {
                    foreach($data->prescriptions as $p) {
                        $rxStatus = $insertPrescriptionRecord($patient_id, $queueId, $visitId > 0 ? $visitId : null, $p);
                        if ($rxStatus === 'nurse_admin_pending') {
                            $routing['nurse_injections']++;
                        } elseif ($rxStatus === 'pending') {
                            $routing['pharmacy_pickup']++;
                        } elseif ($rxStatus === 'external') {
                            $routing['external']++;
                        }
                    }
                }

                $persistVisitClinicalRecord(
                    $patient_id,
                    $visitId,
                    $notes,
                    isset($user->id) ? (int)$user->id : null,
                    'completed',
                    'admission_pending'
                );

                // Refer to nurse for admission (Admission Pending)
                $db->prepare("UPDATE patient_queue SET status = 'Admission Pending' WHERE id = ?")
                   ->execute([$queueId]);

                // Auto-assign bed if available
                $bedAssigned = false;
                if ($patient_id) {
                    $existing = $db->prepare("SELECT id FROM beds WHERE current_patient_id = ? AND status = 'Occupied' LIMIT 1");
                    $existing->execute([$patient_id]);
                    $existingBedId = $existing->fetchColumn();

                    if (!$existingBedId) {
                        $bed = $db->query("SELECT id FROM beds WHERE status = 'Available' ORDER BY ward_name, bed_number LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                        if ($bed && !empty($bed['id'])) {
                            $assign = $db->prepare("UPDATE beds SET status = 'Occupied', current_patient_id = ? WHERE id = ?");
                            $assign->execute([$patient_id, $bed['id']]);
                            $bedAssigned = true;
                            Realtime::emit('nurse.assign_bed', ['bed_id' => $bed['id'], 'patient_id' => $patient_id]);
                        }
                    }
                }

                $db->commit();
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'doctor', 'Completed consultation and referred for admission', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'patient_queue',
                        'entity_id' => (string)$queueId,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'doctor',
                        'source' => 'doctor/complete_refer',
                        'metadata' => [
                            'bed_assigned' => $bedAssigned ? 'yes' : 'no'
                        ]
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('doctor.urgent_care', ['queue_id' => $queueId]);
                echo json_encode([
                    "message" => "Consultation completed and referred for admission.",
                    "bed_assigned" => $bedAssigned,
                    "routing" => $routing
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
            }
        }
        break;

    // 2d. COMPLETE COUNSELLING WORKFLOW
    case 'counselling_complete':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $queueId = isset($data->queue_id) ? (int)$data->queue_id : 0;
            $notes = trim((string)($data->notes ?? ''));
            $outcome = strtolower(trim((string)($data->outcome ?? '')));
            if ($queueId <= 0 || $notes === '' || $outcome === '') {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID, counselling notes, and outcome are required."]);
                exit;
            }
            $allowedOutcomes = ['resolved', 'follow_up_needed', 'risk_escalation'];
            if (!in_array($outcome, $allowedOutcomes, true)) {
                http_response_code(400);
                echo json_encode(["message" => "Outcome is invalid."]);
                exit;
            }

            $followUpRaw = $data->follow_up_at ?? null;
            $followUpInput = trim((string)$followUpRaw);
            $followUpAt = $normalizeDateTimeInput($followUpRaw);
            if ($followUpInput !== '' && !$followUpAt) {
                http_response_code(400);
                echo json_encode(["message" => "follow_up_at must be a valid datetime."]);
                exit;
            }
            if ($outcome === 'follow_up_needed' && !$followUpAt) {
                http_response_code(400);
                echo json_encode(["message" => "follow_up_at is required when outcome is follow_up_needed."]);
                exit;
            }
            if ($followUpAt && strtotime($followUpAt) < (time() - 300)) {
                http_response_code(400);
                echo json_encode(["message" => "follow_up_at cannot be in the past."]);
                exit;
            }

            $followUpReason = trim((string)($data->follow_up_reason ?? 'Counselling follow-up'));
            if ($followUpReason === '') $followUpReason = 'Counselling follow-up';
            $safetyPlan = trim((string)($data->safety_plan ?? ''));
            $escalationReason = trim((string)($data->escalation_reason ?? ''));
            if ($outcome === 'risk_escalation' && $escalationReason === '') {
                $escalationReason = $notes;
            }

            try {
                $db->beginTransaction();

                $queueStmt = $db->prepare("SELECT q.id, q.patient_id, q.visit_id, q.visit_type, q.doctor_assigned, p.full_name AS patient_name
                                           FROM patient_queue q
                                           JOIN patients p ON p.id = q.patient_id
                                           WHERE q.id = ?
                                           LIMIT 1");
                $queueStmt->execute([$queueId]);
                $queueRow = $queueStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$queueRow) {
                    throw new Exception("Queue item was not found.");
                }
                $visitType = strtolower(trim((string)($queueRow['visit_type'] ?? 'general')));
                if ($visitType !== 'counselling_only') {
                    throw new Exception("Queue item is not a counselling-only visit.");
                }

                $patientId = (int)($queueRow['patient_id'] ?? 0);
                $visitId = isset($queueRow['visit_id']) ? (int)$queueRow['visit_id'] : 0;
                if ($patientId <= 0) {
                    throw new Exception("Queue item has no patient.");
                }

                $currentDoctorAssigned = isset($queueRow['doctor_assigned']) ? (int)$queueRow['doctor_assigned'] : 0;
                $doctorAssigned = $currentDoctorAssigned > 0
                    ? $currentDoctorAssigned
                    : (isset($user->id) ? (int)$user->id : 0);
                if ($doctorAssigned <= 0) $doctorAssigned = null;

                $db->prepare("UPDATE patient_queue SET doctor_assigned = COALESCE(doctor_assigned, ?) WHERE id = ?")
                   ->execute([$doctorAssigned, $queueId]);

                $visitStatus = ($outcome === 'risk_escalation') ? 'triaged' : 'completed';
                $persistVisitClinicalRecord(
                    $patientId,
                    $visitId,
                    $notes,
                    $doctorAssigned,
                    $visitStatus,
                    $outcome
                );

                $insertCounsellingSession(
                    $patientId,
                    $queueId,
                    $visitId > 0 ? $visitId : null,
                    $doctorAssigned,
                    $notes,
                    $outcome,
                    $safetyPlan !== '' ? $safetyPlan : null,
                    $followUpAt,
                    $outcome === 'risk_escalation' ? $escalationReason : null
                );

                $appointmentId = null;
                if ($outcome === 'risk_escalation') {
                    $db->prepare("UPDATE patient_queue SET status = 'Urgent Care' WHERE id = ?")
                       ->execute([$queueId]);
                } else {
                    $db->prepare("UPDATE patient_queue SET status = 'Completed' WHERE id = ?")
                       ->execute([$queueId]);
                }

                if ($outcome === 'follow_up_needed') {
                    $apptStmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, scheduled_at, status, reason, notes)
                                              VALUES (?, ?, ?, 'scheduled', ?, ?)");
                    $apptStmt->execute([
                        $patientId,
                        $doctorAssigned,
                        $followUpAt,
                        $followUpReason,
                        'Auto-created from counselling completion.'
                    ]);
                    $appointmentId = (int)$db->lastInsertId();
                }

                $db->commit();

                $eventPayload = [
                    'queue_id' => $queueId,
                    'patient_id' => $patientId,
                    'patient_name' => $queueRow['patient_name'] ?? null,
                    'outcome' => $outcome
                ];
                if ($appointmentId) $eventPayload['appointment_id'] = $appointmentId;

                Realtime::emit('doctor.counselling_completed', $eventPayload);
                if ($appointmentId) {
                    Realtime::emit('doctor.follow_up_created', $eventPayload);
                    Realtime::emit('reception.appointment_update', ['appointment_id' => $appointmentId, 'patient_id' => $patientId]);
                }
                if ($outcome === 'risk_escalation') {
                    Realtime::emit('doctor.urgent_care', ['queue_id' => $queueId, 'patient_id' => $patientId]);
                    Realtime::emit('nurse.counselling_escalated', [
                        'queue_id' => $queueId,
                        'patient_id' => $patientId,
                        'reason' => $escalationReason
                    ]);
                }

                $message = "Counselling case completed.";
                if ($outcome === 'follow_up_needed') $message = "Counselling completed and follow-up appointment created.";
                if ($outcome === 'risk_escalation') $message = "Counselling escalated to urgent care.";

                echo json_encode([
                    "message" => $message,
                    "queue_id" => $queueId,
                    "patient_id" => $patientId,
                    "outcome" => $outcome,
                    "appointment_id" => $appointmentId
                ]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
            }
        }
        break;
    
    // 2c. LIST APPOINTMENTS (Doctor-specific)
    case 'appointments':
        if ($method === 'GET') {
            $start = $_GET['start'] ?? null;
            $end = $_GET['end'] ?? null;
            $status = $_GET['status'] ?? null;
            $scope = strtolower(trim((string)($_GET['scope'] ?? 'mine')));
            $includeUnassigned = isset($_GET['include_unassigned']) && $_GET['include_unassigned'] === '1';
            if ($scope === 'mine_or_unassigned') $includeUnassigned = true;

            $where = ["(a.doctor_id = ?" . ($includeUnassigned ? " OR a.doctor_id IS NULL)" : ")")];
            $params = [$user->id];

            if ($start) { $where[] = "a.scheduled_at >= ?"; $params[] = $start; }
            if ($end) { $where[] = "a.scheduled_at <= ?"; $params[] = $end; }
            if ($status) { $where[] = "a.status = ?"; $params[] = $status; }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $sql = "SELECT a.id, a.patient_id, a.scheduled_at, a.status, a.reason, a.notes,
                           (a.doctor_id IS NULL) as is_unassigned,
                           p.full_name, p.gender, p.dob
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    $whereSql
                    ORDER BY a.scheduled_at DESC
                    LIMIT 200";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;
    
    // 2d. UPDATE APPOINTMENT STATUS (Doctor-specific)
    case 'appointment_update':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            if (!isset($data->appointment_id) || !isset($data->status)) {
                http_response_code(400);
                echo json_encode(["error" => true, "message" => "Appointment ID and status required"]);
                exit;
            }
            $targetStatus = strtolower(trim((string)$data->status));
            if ($targetStatus !== 'completed') {
                http_response_code(403);
                echo json_encode(["error" => true, "message" => "Nurse In Charge can only complete appointments after Reception check-in."]);
                exit;
            }

            $appt = $db->prepare("SELECT id, patient_id, doctor_id, status FROM appointments WHERE id = ? LIMIT 1");
            $appt->execute([$data->appointment_id]);
            $row = $appt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(["error" => true, "message" => "Appointment not found."]);
                exit;
            }

            $assignedDoctorId = isset($row['doctor_id']) ? (int)$row['doctor_id'] : null;
            if (!is_null($assignedDoctorId) && $assignedDoctorId !== (int)$user->id) {
                http_response_code(403);
                echo json_encode(["error" => true, "message" => "Appointment is not assigned to you."]);
                exit;
            }

            $currentStatus = strtolower(trim((string)($row['status'] ?? '')));
            if ($currentStatus !== 'checked_in') {
                http_response_code(409);
                echo json_encode(["error" => true, "message" => "Appointment not ready. Reception must mark patient as arrived (checked_in)."]);
                exit;
            }

            $stmt = $db->prepare("UPDATE appointments
                                  SET status = 'completed', notes = COALESCE(?, notes), doctor_id = COALESCE(doctor_id, ?)
                                  WHERE id = ?");
            if ($stmt->execute([$data->notes ?? null, $user->id, $data->appointment_id])) {
                $patientId = (int)($row['patient_id'] ?? 0);
                if ($patientId > 0) {
                    // Keep waiting-room queue in sync when consultation is completed from appointments.
                    $queueUpdate = $db->prepare("UPDATE patient_queue
                                                 SET status = 'Completed',
                                                     doctor_assigned = COALESCE(doctor_assigned, ?)
                                                 WHERE patient_id = ?
                                                   AND LOWER(status) = 'with doctor'");
                    $queueUpdate->execute([(int)$user->id, $patientId]);

                    $visitUpdate = $db->prepare("UPDATE visits
                                                 SET status = 'completed',
                                                     doctor_id = COALESCE(doctor_id, ?),
                                                     clinical_notes = COALESCE(NULLIF(?, ''), clinical_notes),
                                                     completed_at = COALESCE(completed_at, NOW())
                                                 WHERE patient_id = ?
                                                   AND status IN ('waiting','triaged','in_consultation','pharmacy')");
                    $visitUpdate->execute([(int)$user->id, trim((string)($data->notes ?? '')), $patientId]);
                }
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'doctor', 'Completed appointment after reception check-in', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'appointments',
                        'entity_id' => (string)$data->appointment_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'doctor',
                        'source' => 'doctor/appointment_update',
                        'metadata' => ['status' => 'completed', 'pre_status' => $currentStatus]
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('doctor.complete', ['appointment_id' => $data->appointment_id, 'patient_id' => (int)($row['patient_id'] ?? 0)]);
                Realtime::emit('doctor.appointment_update', ['appointment_id' => $data->appointment_id]);
                echo json_encode(["error" => false, "message" => "Appointment completed"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => true, "message" => "Failed to update appointment"]);
            }
        }
        break;

    // 3. GET NURSE IN CHARGE SHIFTS (New logic for Roster tab)
    case 'shifts':
        if ($method === 'GET') {
            try {
                // Include legacy 'doctor' rows and new 'nurse_in_charge' role rows.
                $query = "SELECT * FROM staff_shifts WHERE role IN ('doctor', 'nurse_in_charge') ORDER BY shift_start ASC";
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
            $pid = (int)($_GET['patient_id'] ?? 0);
            if ($pid <= 0) {
                http_response_code(400);
                echo json_encode(["message" => "Patient ID required"]);
                exit;
            }
            $patientStmt = $db->prepare("SELECT * FROM patients WHERE id = ?");
            $patientStmt->execute([$pid]);
            $vitalsStmt = $db->prepare("SELECT * FROM patient_vitals WHERE patient_id = ? ORDER BY created_at DESC");
            $vitalsStmt->execute([$pid]);
            $vitals = $vitalsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($vitals as &$v) {
                $v = array_merge($v, VitalRisk::classify($v['temperature'] ?? null, $v['pulse'] ?? null, $v['bp'] ?? null, $v['spo2'] ?? null));
            }
            unset($v);
            $visitStmt = $db->prepare("SELECT * FROM patient_queue WHERE patient_id = ? ORDER BY created_at DESC");
            $visitStmt->execute([$pid]);
            $rxStmt = $db->prepare("SELECT pr.*, COALESCE(m.name, pr.medication_name) as medicine_name
                                    FROM prescriptions pr
                                    LEFT JOIN medicines m ON pr.medicine_id = m.id
                                    WHERE pr.patient_id = ?
                                    ORDER BY pr.created_at DESC");
            $rxStmt->execute([$pid]);
            $fileStmt = $db->prepare("SELECT * FROM medical_reports WHERE patient_id = ? ORDER BY created_at DESC");
            $fileStmt->execute([$pid]);
            $counsellingStmt = $db->prepare("SELECT * FROM counselling_sessions WHERE patient_id = ? ORDER BY created_at DESC");
            $counsellingStmt->execute([$pid]);
            $history = [
                "patient" => $patientStmt->fetch(PDO::FETCH_ASSOC),
                "vitals" => $vitals,
                "visits" => $visitStmt->fetchAll(PDO::FETCH_ASSOC),
                "prescriptions" => $rxStmt->fetchAll(PDO::FETCH_ASSOC),
                "files" => $fileStmt->fetchAll(PDO::FETCH_ASSOC),
                "counselling_sessions" => $counsellingStmt->fetchAll(PDO::FETCH_ASSOC)
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
                                     WHERE LOWER(status) = 'completed'
                                     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                     GROUP BY DATE(created_at)
                                     ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $rxRows = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count
                                  FROM prescriptions
                                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                  GROUP BY DATE(created_at)
                                  ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $urgent = (int)$db->query("SELECT COUNT(*) FROM patient_queue WHERE LOWER(status) = 'urgent care'")->fetchColumn();

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
            $data = RequestValidator::json();
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
            $data = RequestValidator::json();
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
            $data = RequestValidator::json();
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
            $data = RequestValidator::json();
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

    // 10. EXTERNAL REFERRALS (to external doctors/facilities)
    case 'referral_create':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            $externalProvider = trim(RequestValidator::requireString($data, 'external_doctor_name', 3, 180));
            $destination = trim(RequestValidator::requireString($data, 'destination', 2, 180));
            $urgency = RequestValidator::enum($data->urgency ?? 'normal', ['normal', 'high', 'urgent'], 'urgency');
            $reason = trim(RequestValidator::requireString($data, 'reason', 3, 4000));
            $status = RequestValidator::enum($data->status ?? 'pending', ['pending', 'sent', 'accepted', 'declined', 'completed'], 'status');
            $notes = RequestValidator::optionalString($data, 'notes', 4000);
            $referralDateRaw = RequestValidator::optionalString($data, 'referral_date', 30);

            $patientCheck = $db->prepare("SELECT id FROM patients WHERE id = ? LIMIT 1");
            $patientCheck->execute([$patientId]);
            if (!$patientCheck->fetchColumn()) {
                RequestValidator::fail(400, "patient_id was not found.");
            }

            $referralDate = null;
            if (!is_null($referralDateRaw)) {
                $candidate = trim((string)$referralDateRaw);
                if ($candidate !== '') {
                    $candidate = str_replace('T', ' ', $candidate);
                    $parsed = DateTime::createFromFormat('Y-m-d H:i:s', $candidate)
                        ?: DateTime::createFromFormat('Y-m-d H:i', $candidate);
                    if (!$parsed) {
                        RequestValidator::fail(400, "referral_date must be a valid datetime.");
                    }
                    $referralDate = $parsed->format('Y-m-d H:i:s');
                }
            }

            $stmt = $db->prepare("INSERT INTO referrals
                                  (patient_id, referred_by, referral_type, external_provider_name, destination, reason, urgency, status, referral_date, notes)
                                  VALUES (?, ?, 'External Doctor', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $patientId,
                isset($user->id) ? (int)$user->id : null,
                $externalProvider,
                $destination,
                $reason,
                strtolower(trim((string)$urgency)),
                strtolower(trim((string)$status)),
                $referralDate,
                $notes
            ]);
            Realtime::emit('doctor.referral', ['patient_id' => $patientId]);
            echo json_encode(["message" => "External referral created"]);
        }
        break;

    case 'referral_list':
        if ($method === 'GET') {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 150;
            if ($limit < 1 || $limit > 500) {
                RequestValidator::fail(400, "limit must be between 1 and 500.");
            }
            $stmt = $db->query("SELECT r.id,
                                       r.patient_id,
                                       p.full_name AS patient_name,
                                       r.referral_type,
                                       r.external_provider_name,
                                       r.destination,
                                       r.reason,
                                       r.urgency,
                                       r.status,
                                       r.referral_date,
                                       r.notes,
                                       r.created_at,
                                       u.full_name AS referred_by_name
                                FROM referrals r
                                JOIN patients p ON p.id = r.patient_id
                                LEFT JOIN users u ON u.id = r.referred_by
                                ORDER BY COALESCE(r.referral_date, r.created_at) DESC, r.id DESC
                                LIMIT {$limit}");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 11. DISCHARGE SUMMARY
    case 'discharge_candidates':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT p.id, p.full_name, p.national_id, b.ward_name, b.bed_number
                                FROM beds b
                                JOIN patients p ON b.current_patient_id = p.id
                                WHERE b.status = 'Occupied'
                                ORDER BY b.ward_name, b.bed_number");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'discharge_create':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            if (!isset($data->patient_id) || empty($data->summary)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient and summary required"]);
                exit;
            }
            $check = $db->prepare("SELECT 1 FROM beds WHERE current_patient_id = ? AND status = 'Occupied' LIMIT 1");
            $check->execute([$data->patient_id]);
            if (!$check->fetchColumn()) {
                http_response_code(400);
                echo json_encode(["message" => "Only admitted/ICU patients can be discharged."]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO discharge_summaries (patient_id, summary, status)
                                  VALUES (?, ?, ?)");
            // Doctor discharge summary serves as the command to release a patient
            $stmt->execute([$data->patient_id, $data->summary, $data->status ?? 'approved']);
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

    // 12. PATIENT TIMELINE
    case 'timeline':
        if ($method === 'GET') {
            try {
                $pid = (int)($_GET['patient_id'] ?? 0);
                if ($pid <= 0) {
                    http_response_code(400);
                    echo json_encode(["message" => "Patient ID required"]);
                    exit;
                }

                $safeQuery = function(string $sql, array $params = []) use ($db) {
                    try {
                        $stmt = $db->prepare($sql);
                        $stmt->execute($params);
                        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    } catch (Throwable $e) {
                        return [];
                    }
                };

                $vitalsRaw = $safeQuery(
                    "SELECT created_at, bp, temperature, pulse, spo2, notes
                     FROM patient_vitals
                     WHERE patient_id = ?
                     ORDER BY created_at DESC
                     LIMIT 30",
                    [$pid]
                );
                $vitals = [];
                foreach ($vitalsRaw as $v) {
                    $risk = VitalRisk::classify($v['temperature'] ?? null, $v['pulse'] ?? null, $v['bp'] ?? null, $v['spo2'] ?? null);
                    $note = "Vitals: BP {$v['bp']}, T {$v['temperature']} C, SpO2 {$v['spo2']}% | {$risk['risk_label']}";
                    if (!empty($v['notes'])) {
                        $note .= " | Nurse notes: " . $v['notes'];
                    }
                    $vitals[] = ['created_at' => $v['created_at'], 'note' => $note];
                }

                $visits = $safeQuery(
                    "SELECT created_at, CONCAT('Visit status: ', status) as note
                     FROM patient_queue
                     WHERE patient_id = ?
                     ORDER BY created_at DESC
                     LIMIT 30",
                    [$pid]
                );

                $meds = $safeQuery(
                    "SELECT pr.created_at, CONCAT('Prescription: ', COALESCE(m.name, pr.medication_name, pr.notes)) as note
                     FROM prescriptions pr
                     LEFT JOIN medicines m ON pr.medicine_id = m.id
                     WHERE pr.patient_id = ?
                     ORDER BY pr.created_at DESC
                     LIMIT 30",
                    [$pid]
                );
                if (empty($meds)) {
                    $meds = $safeQuery(
                        "SELECT pr.created_at, CONCAT('Prescription: ', COALESCE(m.name, pr.medication_name, pr.notes)) as note
                         FROM prescriptions pr
                         LEFT JOIN medicines m ON pr.medicine_id = m.id
                         INNER JOIN visits v ON pr.visit_id = v.id
                         WHERE v.patient_id = ?
                         ORDER BY pr.created_at DESC
                         LIMIT 30",
                        [$pid]
                    );
                }

                $docs = $safeQuery(
                    "SELECT created_at, CONCAT('Document: ', report_name) as note
                     FROM medical_reports
                     WHERE patient_id = ?
                     ORDER BY created_at DESC
                     LIMIT 30",
                    [$pid]
                );

                $referrals = $safeQuery(
                    "SELECT COALESCE(referral_date, created_at) AS created_at,
                            CONCAT('External referral to ', COALESCE(destination, 'N/A'),
                                   ' (', COALESCE(external_provider_name, 'N/A'), ')') AS note
                     FROM referrals
                     WHERE patient_id = ?
                     ORDER BY COALESCE(referral_date, created_at) DESC
                     LIMIT 30",
                    [$pid]
                );

                $counselling = $safeQuery(
                    "SELECT created_at,
                            CONCAT('Counselling outcome: ', outcome,
                                   CASE WHEN follow_up_at IS NOT NULL THEN CONCAT(' (Follow-up: ', follow_up_at, ')') ELSE '' END,
                                   CASE WHEN escalation_reason IS NOT NULL AND TRIM(escalation_reason) <> '' THEN CONCAT(' | Escalation: ', escalation_reason) ELSE '' END,
                                   CASE WHEN notes IS NOT NULL AND TRIM(notes) <> '' THEN CONCAT(' | Notes: ', LEFT(notes, 180)) ELSE '' END
                            ) AS note
                     FROM counselling_sessions
                     WHERE patient_id = ?
                     ORDER BY created_at DESC
                     LIMIT 30",
                    [$pid]
                );

                $timeline = array_merge($vitals, $visits, $meds, $docs, $referrals, $counselling);
                usort($timeline, function($a, $b) {
                    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
                });

                echo json_encode(array_slice($timeline, 0, 50));
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to load timeline"]);
            }
        }
        break;
    default:
        http_response_code(404);
        echo json_encode(["message" => "Action not found"]);
        break;
}
?>

