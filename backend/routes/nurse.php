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
DbSchema::ensureClinicalOperationsModules($db);
DbSchema::ensureBedManagement($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['nurse', 'admin'], $user);
$allowedWardNames = ['General Ward', 'ICU', 'Martenity', 'Recovering Ward'];
$wardPrefixByName = [
    'General Ward' => 'G',
    'ICU' => 'I',
    'Martenity' => 'M',
    'Recovering Ward' => 'R'
];
$allowedBedStatuses = ['Available', 'Occupied', 'Cleaning', 'Maintenance', 'Reserved'];
$allowedWardStatuses = ['Open', 'High Load', 'Isolation', 'Closed', 'Maintenance'];

switch ($action) {

    // 1. DASHBOARD STATS (Matches triage widget)
    case 'stats':
        $stmt = $db->query("SELECT COUNT(*) as count FROM patient_queue WHERE LOWER(status) IN ('waiting','urgent care','admission pending')");
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

            $urgentCount = (int)$db->query("SELECT COUNT(*) FROM patient_queue WHERE LOWER(status) IN ('urgent care','admission pending')")->fetchColumn();

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

    case 'bed_create':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $wardName = RequestValidator::enum($data->ward_name ?? '', $allowedWardNames, 'ward_name');
            $bedInput = RequestValidator::requireString($data, 'bed_number', 1, 30);
            $status = RequestValidator::enum($data->status ?? 'Available', $allowedBedStatuses, 'status');
            $wardStatus = RequestValidator::enum($data->ward_status ?? 'Open', $allowedWardStatuses, 'ward_status');
            $wardPrefix = $wardPrefixByName[$wardName] ?? strtoupper(substr($wardName, 0, 1));

            if (!preg_match('/^([A-Za-z]+)-(\d{1,2})$/', $bedInput, $bedMatch)) {
                http_response_code(400);
                echo json_encode(["message" => "Bed number must follow {$wardPrefix}-01 to {$wardPrefix}-15."]);
                exit;
            }
            $inputPrefix = strtoupper($bedMatch[1]);
            $sequence = (int)$bedMatch[2];
            if ($inputPrefix !== strtoupper($wardPrefix)) {
                http_response_code(400);
                echo json_encode(["message" => "For {$wardName}, bed number must start with {$wardPrefix}-."]);
                exit;
            }
            if ($sequence < 1 || $sequence > 15) {
                http_response_code(400);
                echo json_encode(["message" => "For {$wardName}, bed number must be from {$wardPrefix}-01 to {$wardPrefix}-15."]);
                exit;
            }
            $bedNumber = sprintf('%s-%02d', $wardPrefix, $sequence);

            if ($status === 'Occupied') {
                http_response_code(400);
                echo json_encode(["message" => "New bed cannot start as Occupied."]);
                exit;
            }

            $exists = $db->prepare("SELECT id FROM beds WHERE LOWER(TRIM(ward_name)) = LOWER(TRIM(?)) AND LOWER(TRIM(bed_number)) = LOWER(TRIM(?)) LIMIT 1");
            $exists->execute([$wardName, $bedNumber]);
            if ($exists->fetchColumn()) {
                http_response_code(409);
                echo json_encode(["message" => "A bed with this ward and bed number already exists."]);
                exit;
            }

            $wardCountStmt = $db->prepare("SELECT COUNT(*) FROM beds WHERE LOWER(TRIM(ward_name)) = LOWER(TRIM(?))");
            $wardCountStmt->execute([$wardName]);
            $wardBedCount = (int)$wardCountStmt->fetchColumn();
            if ($wardBedCount >= 15) {
                http_response_code(400);
                echo json_encode(["message" => "Ward bed limit reached. Each ward can have at most 15 beds."]);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO beds (ward_name, bed_number, status, ward_status, current_patient_id) VALUES (?, ?, ?, ?, NULL)");
            $stmt->execute([$wardName, $bedNumber, $status, $wardStatus]);
            $bedId = (int)$db->lastInsertId();

            try {
                ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Added bed', 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'bed',
                    'entity_id' => (string)$bedId,
                    'actor_id' => isset($user->id) ? (string)$user->id : null,
                    'actor_role' => $user->role ?? 'nurse',
                    'source' => 'nurse/bed_create',
                    'metadata' => ['ward_name' => $wardName, 'bed_number' => $bedNumber]
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }

            Realtime::emit('nurse.bed_create', ['bed_id' => $bedId]);
            echo json_encode(["message" => "Bed added successfully.", "bed_id" => $bedId]);
        }
        break;

    case 'bed_delete':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $bedId = RequestValidator::requireInt($data, 'bed_id', 1);

            $check = $db->prepare("SELECT ward_name, bed_number, status, current_patient_id FROM beds WHERE id = ? LIMIT 1");
            $check->execute([$bedId]);
            $bed = $check->fetch(PDO::FETCH_ASSOC);
            if (!$bed) {
                http_response_code(404);
                echo json_encode(["message" => "Bed not found."]);
                exit;
            }

            if (($bed['status'] ?? '') === 'Occupied' || !empty($bed['current_patient_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Cannot remove an occupied bed."]);
                exit;
            }

            $db->prepare("DELETE FROM beds WHERE id = ?")->execute([$bedId]);

            try {
                ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Removed bed', 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'bed',
                    'entity_id' => (string)$bedId,
                    'actor_id' => isset($user->id) ? (string)$user->id : null,
                    'actor_role' => $user->role ?? 'nurse',
                    'source' => 'nurse/bed_delete',
                    'metadata' => ['ward_name' => $bed['ward_name'] ?? null, 'bed_number' => $bed['bed_number'] ?? null]
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }

            Realtime::emit('nurse.bed_delete', ['bed_id' => $bedId]);
            echo json_encode(["message" => "Bed removed successfully."]);
        }
        break;

    case 'bed_status_update':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $bedId = RequestValidator::requireInt($data, 'bed_id', 1);
            $status = RequestValidator::enum($data->status ?? '', $allowedBedStatuses, 'status');

            $check = $db->prepare("SELECT current_patient_id, status FROM beds WHERE id = ? LIMIT 1");
            $check->execute([$bedId]);
            $bed = $check->fetch(PDO::FETCH_ASSOC);
            if (!$bed) {
                http_response_code(404);
                echo json_encode(["message" => "Bed not found."]);
                exit;
            }

            if ($status === 'Occupied' && empty($bed['current_patient_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Use Admit action to set a bed to Occupied."]);
                exit;
            }

            if (($bed['status'] ?? '') === 'Occupied' && $status !== 'Occupied') {
                http_response_code(400);
                echo json_encode(["message" => "Use discharge workflow for occupied beds."]);
                exit;
            }

            if ($status === 'Occupied') {
                $stmt = $db->prepare("UPDATE beds SET status = ? WHERE id = ?");
                $stmt->execute([$status, $bedId]);
            } else {
                $stmt = $db->prepare("UPDATE beds SET status = ?, current_patient_id = NULL WHERE id = ?");
                $stmt->execute([$status, $bedId]);
            }

            try {
                ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Updated bed status', 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'bed',
                    'entity_id' => (string)$bedId,
                    'actor_id' => isset($user->id) ? (string)$user->id : null,
                    'actor_role' => $user->role ?? 'nurse',
                    'source' => 'nurse/bed_status_update',
                    'metadata' => ['status' => $status]
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }

            Realtime::emit('nurse.bed_status', ['bed_id' => $bedId, 'status' => $status]);
            echo json_encode(["message" => "Bed status updated."]);
        }
        break;

    case 'ward_status_update':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $wardName = RequestValidator::requireString($data, 'ward_name', 2, 100);
            $wardStatus = RequestValidator::enum($data->ward_status ?? '', $allowedWardStatuses, 'ward_status');

            $check = $db->prepare("SELECT COUNT(*) FROM beds WHERE ward_name = ?");
            $check->execute([$wardName]);
            if ((int)$check->fetchColumn() === 0) {
                http_response_code(404);
                echo json_encode(["message" => "Ward not found."]);
                exit;
            }

            $stmt = $db->prepare("UPDATE beds SET ward_status = ? WHERE ward_name = ?");
            $stmt->execute([$wardStatus, $wardName]);

            try {
                ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Updated ward status', 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'ward',
                    'entity_id' => (string)$wardName,
                    'actor_id' => isset($user->id) ? (string)$user->id : null,
                    'actor_role' => $user->role ?? 'nurse',
                    'source' => 'nurse/ward_status_update',
                    'metadata' => ['ward_status' => $wardStatus]
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }

            Realtime::emit('nurse.ward_status', ['ward_name' => $wardName, 'ward_status' => $wardStatus]);
            echo json_encode(["message" => "Ward status updated."]);
        }
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
                echo json_encode(["message" => "Nurse In Charge discharge command required."]);
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
                $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.national_id, p.dob, p.gender, q.created_at, q.status,
                                 v.temperature as last_temp, v.pulse as last_pulse, v.bp as last_bp, v.spo2 as last_spo2, v.created_at as last_vitals_at
                          FROM patient_queue q
                          JOIN patients p ON q.patient_id = p.id
                          LEFT JOIN (
                              SELECT pv1.patient_id, pv1.temperature, pv1.pulse, pv1.bp, pv1.spo2, pv1.created_at
                              FROM patient_vitals pv1
                              INNER JOIN (
                                  SELECT patient_id, MAX(id) as latest_id
                                  FROM patient_vitals
                                  GROUP BY patient_id
                              ) lv ON lv.latest_id = pv1.id
                          ) v ON v.patient_id = p.id
                          WHERE LOWER(q.status) = 'urgent care'
                          ORDER BY q.created_at DESC";
                $stmt = $db->query($query);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as &$row) {
                    $row = array_merge($row, VitalRisk::classify($row['last_temp'] ?? null, $row['last_pulse'] ?? null, $row['last_bp'] ?? null, $row['last_spo2'] ?? null));
                }
                unset($row);
                usort($rows, function($a, $b) {
                    $aPriority = (int)($a['risk_priority'] ?? 0);
                    $bPriority = (int)($b['risk_priority'] ?? 0);
                    if ($aPriority !== $bPriority) return $bPriority <=> $aPriority;
                    $aTime = isset($a['created_at']) ? strtotime((string)$a['created_at']) : 0;
                    $bTime = isset($b['created_at']) ? strtotime((string)$b['created_at']) : 0;
                    return $bTime <=> $aTime;
                });
                echo json_encode($rows);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Urgent list error: " . $e->getMessage()]);
            }
        }
        break;
    
    case 'urgent_care_list':
        if ($method === 'GET') {
            try {
                $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.national_id, p.dob, p.gender, q.created_at, q.status,
                                 v.temperature as last_temp, v.pulse as last_pulse, v.bp as last_bp, v.spo2 as last_spo2, v.created_at as last_vitals_at
                          FROM patient_queue q
                          JOIN patients p ON q.patient_id = p.id
                          LEFT JOIN (
                              SELECT pv1.patient_id, pv1.temperature, pv1.pulse, pv1.bp, pv1.spo2, pv1.created_at
                              FROM patient_vitals pv1
                              INNER JOIN (
                                  SELECT patient_id, MAX(id) as latest_id
                                  FROM patient_vitals
                                  GROUP BY patient_id
                              ) lv ON lv.latest_id = pv1.id
                          ) v ON v.patient_id = p.id
                          WHERE LOWER(q.status) = 'urgent care'
                          ORDER BY q.created_at DESC";
                $stmt = $db->query($query);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as &$row) {
                    $row = array_merge($row, VitalRisk::classify($row['last_temp'] ?? null, $row['last_pulse'] ?? null, $row['last_bp'] ?? null, $row['last_spo2'] ?? null));
                }
                unset($row);
                usort($rows, function($a, $b) {
                    $aPriority = (int)($a['risk_priority'] ?? 0);
                    $bPriority = (int)($b['risk_priority'] ?? 0);
                    if ($aPriority !== $bPriority) return $bPriority <=> $aPriority;
                    $aTime = isset($a['created_at']) ? strtotime((string)$a['created_at']) : 0;
                    $bTime = isset($b['created_at']) ? strtotime((string)$b['created_at']) : 0;
                    return $bTime <=> $aTime;
                });
                echo json_encode($rows);
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
                                 v.temperature as last_temp, v.pulse as last_pulse, v.bp as last_bp, v.spo2 as last_spo2, v.created_at as last_vitals_at,
                                 (SELECT COUNT(*) FROM prescriptions pr WHERE pr.patient_id = p.id AND LOWER(pr.status) IN ('pending','external')) as pending_prescriptions,
                                 (SELECT COUNT(*) FROM prescriptions pr2 WHERE pr2.patient_id = p.id AND LOWER(pr2.status) = 'nurse_admin_pending') as pending_injections
                          FROM patient_queue q
                          JOIN patients p ON q.patient_id = p.id
                          LEFT JOIN (
                              SELECT pv1.patient_id, pv1.temperature, pv1.pulse, pv1.bp, pv1.spo2, pv1.created_at
                              FROM patient_vitals pv1
                              INNER JOIN (
                                  SELECT patient_id, MAX(id) as latest_id
                                  FROM patient_vitals
                                  GROUP BY patient_id
                              ) lv ON lv.latest_id = pv1.id
                          ) v ON v.patient_id = p.id
                          WHERE LOWER(q.status) IN ('admission pending','waiting pharmacy','ready for admission')
                          ORDER BY q.created_at DESC";
                $stmt = $db->query($query);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as &$row) {
                    $row = array_merge($row, VitalRisk::classify($row['last_temp'] ?? null, $row['last_pulse'] ?? null, $row['last_bp'] ?? null, $row['last_spo2'] ?? null));
                }
                unset($row);
                usort($rows, function($a, $b) {
                    $aPriority = (int)($a['risk_priority'] ?? 0);
                    $bPriority = (int)($b['risk_priority'] ?? 0);
                    if ($aPriority !== $bPriority) return $bPriority <=> $aPriority;
                    $aTime = isset($a['created_at']) ? strtotime((string)$a['created_at']) : 0;
                    $bTime = isset($b['created_at']) ? strtotime((string)$b['created_at']) : 0;
                    return $bTime <=> $aTime;
                });
                echo json_encode($rows);
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
                $check = $db->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ? AND LOWER(status) IN ('pending','external')");
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
                $insert = $db->prepare("INSERT INTO pharmacy_requests (patient_id, requested_by, status, notes) VALUES (?, ?, 'pending', ?)");
                $insert->execute([
                    $patientId,
                    isset($user->id) ? $user->id : null,
                    $notes
                ]);
                // Mark admission as waiting on pharmacy prep
                $db->prepare("UPDATE patient_queue
                              SET status = 'Waiting Pharmacy'
                              WHERE patient_id = ?
                                AND LOWER(status) = 'admission pending'")
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

    // 4f. INJECTION ADMINISTRATION QUEUE (Nurse In Charge)
    case 'injection_queue':
        if ($method === 'GET') {
            try {
                $query = "SELECT pr.id as prescription_id,
                                 pr.patient_id,
                                 pat.full_name,
                                 pat.national_id,
                                 pr.quantity,
                                 pr.dosage,
                                 pr.created_at,
                                 COALESCE(med.name, NULLIF(TRIM(pr.medication_name), ''), pr.notes, 'Injection') as medication_name,
                                 q.id as queue_id,
                                 q.status as queue_status
                          FROM prescriptions pr
                          JOIN patients pat ON pat.id = pr.patient_id
                          LEFT JOIN medicines med ON med.id = pr.medicine_id
                          LEFT JOIN patient_queue q ON q.id = COALESCE(
                              pr.queue_id,
                              (
                                  SELECT q2.id
                                  FROM patient_queue q2
                                  WHERE q2.patient_id = pr.patient_id
                                    AND (pr.visit_id IS NULL OR q2.visit_id = pr.visit_id)
                                  ORDER BY q2.id DESC
                                  LIMIT 1
                              ),
                              (
                                  SELECT q3.id
                                  FROM patient_queue q3
                                  WHERE q3.patient_id = pr.patient_id
                                  ORDER BY q3.id DESC
                                  LIMIT 1
                              )
                          )
                          WHERE LOWER(TRIM(pr.status)) = 'nurse_admin_pending'
                          ORDER BY pr.created_at ASC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to load injection queue: " . $e->getMessage()]);
            }
        }
        break;

    // 4g. MARK INJECTION AS ADMINISTERED
    case 'administer_injection':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $prescriptionId = RequestValidator::requireInt($data, 'prescription_id', 1);
            try {
                $db->beginTransaction();

                $rxStmt = $db->prepare("SELECT pr.id, pr.patient_id, pr.medicine_id, pr.quantity, pr.status, pr.notes,
                                               COALESCE(m.name, NULLIF(TRIM(pr.medication_name), ''), pr.notes, 'Injection') as medication_name
                                        FROM prescriptions pr
                                        LEFT JOIN medicines m ON m.id = pr.medicine_id
                                        WHERE pr.id = ?
                                        LIMIT 1
                                        FOR UPDATE");
                $rxStmt->execute([$prescriptionId]);
                $rx = $rxStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$rx) {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(["message" => "Injection prescription not found."]);
                    exit;
                }

                $rxStatus = strtolower(trim((string)($rx['status'] ?? '')));
                if ($rxStatus !== 'nurse_admin_pending') {
                    $db->rollBack();
                    http_response_code(409);
                    echo json_encode(["message" => "This prescription is not waiting for nurse administration."]);
                    exit;
                }

                $medicineId = isset($rx['medicine_id']) ? (int)$rx['medicine_id'] : 0;
                $quantity = isset($rx['quantity']) ? (int)$rx['quantity'] : 1;
                if ($quantity <= 0) $quantity = 1;

                if ($medicineId > 0) {
                    $stockStmt = $db->prepare("SELECT stock_quantity FROM medicines WHERE id = ? LIMIT 1 FOR UPDATE");
                    $stockStmt->execute([$medicineId]);
                    $stock = $stockStmt->fetchColumn();
                    if ($stock === false) {
                        throw new Exception("Medication stock record not found.");
                    }
                    $stockQty = (int)$stock;
                    if ($stockQty < $quantity) {
                        throw new Exception("Insufficient stock to administer injection. Available: " . $stockQty);
                    }
                    $db->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ?")
                       ->execute([$quantity, $medicineId]);
                }

                $notes = trim((string)($rx['notes'] ?? ''));
                $stamp = '[Injection administered by ' . trim((string)($user->full_name ?? 'nurse')) . ' on ' . date('Y-m-d H:i:s') . ']';
                $nextNotes = $notes === '' ? $stamp : ($notes . ' ' . $stamp);
                $db->prepare("UPDATE prescriptions SET status = 'nurse_administered', notes = ? WHERE id = ?")
                   ->execute([$nextNotes, $prescriptionId]);

                $patientId = isset($rx['patient_id']) ? (int)$rx['patient_id'] : 0;
                $remaining = 0;
                if ($patientId > 0) {
                    $remainingStmt = $db->prepare("SELECT COUNT(*)
                                                   FROM prescriptions
                                                   WHERE patient_id = ?
                                                     AND LOWER(status) IN ('pending','external','nurse_admin_pending')");
                    $remainingStmt->execute([$patientId]);
                    $remaining = (int)$remainingStmt->fetchColumn();
                    if ($remaining === 0) {
                        $db->prepare("UPDATE patient_queue
                                      SET status = 'Ready for Admission'
                                      WHERE patient_id = ?
                                        AND LOWER(status) = 'waiting pharmacy'")
                           ->execute([$patientId]);
                    }
                }

                $db->commit();
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'nurse', 'Administered injection', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'prescription',
                        'entity_id' => (string)$prescriptionId,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'nurse',
                        'source' => 'nurse/administer_injection',
                        'metadata' => [
                            'patient_id' => $patientId > 0 ? (string)$patientId : null,
                            'remaining_medications' => (string)$remaining
                        ]
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('nurse.injection_administered', ['prescription_id' => $prescriptionId, 'patient_id' => $patientId]);
                echo json_encode([
                    "message" => "Injection administered and recorded.",
                    "prescription_id" => $prescriptionId,
                    "remaining_medications" => $remaining
                ]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Failed to administer injection: " . $e->getMessage()]);
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
                foreach (array_keys($_GET) as $queryKey) {
                    if (!in_array($queryKey, ['patient_id'], true)) {
                        http_response_code(400);
                        echo json_encode(["message" => "Unsupported query parameter: $queryKey."]);
                        exit;
                    }
                }

                $pid = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
                if ($pid > 0) {
                    $patientStmt = $db->prepare("SELECT id, full_name, national_id, dob, gender, phone FROM patients WHERE id = ? LIMIT 1");
                    $patientStmt->execute([$pid]);
                    $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$patient) {
                        http_response_code(404);
                        echo json_encode(["message" => "Patient not found."]);
                        exit;
                    }

                    $vitalsStmt = $db->prepare("SELECT * FROM patient_vitals WHERE patient_id = ? ORDER BY created_at DESC LIMIT 100");
                    $vitalsStmt->execute([$pid]);
                    $vitals = $vitalsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    foreach ($vitals as &$v) {
                        $v = array_merge($v, VitalRisk::classify($v['temperature'] ?? null, $v['pulse'] ?? null, $v['bp'] ?? null, $v['spo2'] ?? null));
                    }
                    unset($v);

                    $visitsStmt = $db->prepare("SELECT id, patient_id, status, doctor_assigned, created_at
                                                FROM patient_queue
                                                WHERE patient_id = ?
                                                ORDER BY created_at DESC
                                                LIMIT 50");
                    $visitsStmt->execute([$pid]);

                    $rxStmt = $db->prepare("SELECT pr.*, COALESCE(m.name, pr.medication_name) as medicine_name
                                            FROM prescriptions pr
                                            LEFT JOIN medicines m ON pr.medicine_id = m.id
                                            WHERE pr.patient_id = ?
                                            ORDER BY pr.created_at DESC
                                            LIMIT 50");
                    $rxStmt->execute([$pid]);

                    $filesStmt = $db->prepare("SELECT * FROM medical_reports WHERE patient_id = ? ORDER BY created_at DESC LIMIT 50");
                    $filesStmt->execute([$pid]);

                    echo json_encode([
                        "patient" => $patient,
                        "vitals" => $vitals,
                        "visits" => $visitsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
                        "prescriptions" => $rxStmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
                        "files" => $filesStmt->fetchAll(PDO::FETCH_ASSOC) ?: []
                    ]);
                    exit;
                }

                $recentQuery = "SELECT v.id, v.patient_id, v.created_at, v.temperature, v.pulse, v.bp, v.weight, v.spo2,
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

    // 7. SEARCH PATIENTS (scoped nurse search)
    case 'search':
        if ($method === 'GET') {
            foreach (array_keys($_GET) as $queryKey) {
                if (!in_array($queryKey, ['q'], true)) {
                    http_response_code(400);
                    echo json_encode(["message" => "Unsupported query parameter: $queryKey."]);
                    exit;
                }
            }

            $q = trim((string)($_GET['q'] ?? ''));
            if (strlen($q) < 2) {
                echo json_encode([]);
                exit;
            }
            if (strlen($q) > 80) {
                http_response_code(400);
                echo json_encode(["message" => "q is too long."]);
                exit;
            }
            if (!preg_match('/^[A-Za-z0-9\+\-\s]+$/', $q)) {
                http_response_code(400);
                echo json_encode(["message" => "q contains invalid characters."]);
                exit;
            }

            $like = '%' . $q . '%';
            $stmt = $db->prepare("SELECT id, full_name, national_id, dob, gender
                                  FROM patients
                                  WHERE full_name LIKE ?
                                     OR national_id LIKE ?
                                  ORDER BY full_name ASC
                                  LIMIT 25");
            $stmt->execute([$like, $like]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 8. PATIENTS LIST (for nurse modules)
    case 'patients':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT id, full_name, national_id, dob, gender FROM patients ORDER BY full_name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 9. NURSE HANDOVER
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
                $check = $db->prepare("SELECT 1 FROM patient_queue WHERE patient_id = ? AND LOWER(status) = 'urgent care' LIMIT 1");
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
                                         AND LOWER(q.status) = 'urgent care'
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

                $vitals = $safeQuery(
                    "SELECT created_at, CONCAT('Vitals: BP ', bp, ', T ', temperature, ' C') as note
                     FROM patient_vitals
                     WHERE patient_id = ?
                     ORDER BY created_at DESC
                     LIMIT 30",
                    [$pid]
                );
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

                $timeline = array_merge($vitals, $visits, $meds, $docs);
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
        echo json_encode(["message" => "Nurse action not found"]);
        break;
}
?>
