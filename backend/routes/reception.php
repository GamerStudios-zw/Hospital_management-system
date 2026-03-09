<?php
// FILE: backend/routes/reception.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Realtime.php';
require_once __DIR__ . '/../utils/DbSchema.php';
require_once __DIR__ . '/../utils/RequestValidator.php';

$database = new Database();
$db = $database->getConnection();

DbSchema::ensureAppointments($db);
DbSchema::ensureReceptionHandover($db);
DbSchema::ensureVisitEncounters($db);
DbSchema::ensureReceptionIdentityTables($db);
DbSchema::ensureReceptionPatientMedicalAidColumns($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$parseDoctorId = function ($raw) {
    if ($raw === null || $raw === '') return null;
    if (!is_numeric($raw)) return null;
    $id = (int)$raw;
    return $id > 0 ? $id : null;
};

$upsertVisitEncounter = function (int $patientId, ?int $doctorId = null, ?string $chiefComplaint = null) use ($db) {
    $active = $db->prepare("SELECT id, doctor_id
                            FROM visits
                            WHERE patient_id = ?
                              AND status IN ('waiting','triaged','in_consultation','pharmacy')
                            ORDER BY id DESC
                            LIMIT 1");
    $active->execute([$patientId]);
    $row = $active->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $visitId = (int)$row['id'];
        $existingDoctor = isset($row['doctor_id']) ? (int)$row['doctor_id'] : null;
        if ($doctorId && !$existingDoctor) {
            $upd = $db->prepare("UPDATE visits SET doctor_id = ? WHERE id = ?");
            $upd->execute([$doctorId, $visitId]);
        }
        return ['visit_id' => $visitId, 'created' => false];
    }

    $complaint = trim((string)$chiefComplaint);
    if ($complaint === '') $complaint = 'Reception intake';
    $insert = $db->prepare("INSERT INTO visits (patient_id, doctor_id, status, chief_complaint)
                            VALUES (?, ?, 'waiting', ?)");
    $insert->execute([$patientId, $doctorId, $complaint]);
    return ['visit_id' => (int)$db->lastInsertId(), 'created' => true];
};

$cancelVisitEncounter = function (int $queueId) use ($db) {
    $queueStmt = $db->prepare("SELECT visit_id, patient_id FROM patient_queue WHERE id = ? LIMIT 1");
    $queueStmt->execute([$queueId]);
    $queueRow = $queueStmt->fetch(PDO::FETCH_ASSOC);
    if (!$queueRow) return;

    $visitId = isset($queueRow['visit_id']) ? (int)$queueRow['visit_id'] : 0;
    if ($visitId <= 0) {
        $fallback = $db->prepare("SELECT id
                                  FROM visits
                                  WHERE patient_id = ?
                                    AND status IN ('waiting','triaged','in_consultation','pharmacy')
                                  ORDER BY id DESC
                                  LIMIT 1");
        $fallback->execute([(int)$queueRow['patient_id']]);
        $visitId = (int)$fallback->fetchColumn();
    }
    if ($visitId <= 0) return;

    $cancel = $db->prepare("UPDATE visits
                            SET status = 'cancelled'
                            WHERE id = ?
                              AND status IN ('waiting','triaged','in_consultation','pharmacy')");
    $cancel->execute([$visitId]);
};

$isValidIsoDate = function (?string $date): bool {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
};

$isValidDateTime = function (?string $value): bool {
    if (!is_string($value) || trim($value) === '') return false;
    $v = trim($value);
    if (!preg_match('/^\d{4}\-\d{2}\-\d{2}[ T]\d{2}\:\d{2}(\:\d{2})?$/', $v)) return false;
    $normalized = str_replace('T', ' ', $v);
    $formats = ['Y-m-d H:i:s', 'Y-m-d H:i'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $normalized);
        if ($dt && $dt->format($fmt) === $normalized) return true;
    }
    return false;
};

$patientExists = function (int $patientId) use ($db): bool {
    $stmt = $db->prepare("SELECT id FROM patients WHERE id = ? LIMIT 1");
    $stmt->execute([$patientId]);
    return (bool)$stmt->fetchColumn();
};

$userExists = function (int $userId, array $roles = []) use ($db): bool {
    if ($userId <= 0) return false;
    if (empty($roles)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND COALESCE(is_active,1) = 1 LIMIT 1");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $sql = "SELECT id FROM users WHERE id = ? AND COALESCE(is_active,1) = 1 AND role IN ($placeholders) LIMIT 1";
    $stmt = $db->prepare($sql);
    $params = array_merge([$userId], $roles);
    $stmt->execute($params);
    return (bool)$stmt->fetchColumn();
};

$doctorExists = function (int $doctorId) use ($userExists): bool {
    return $userExists($doctorId, ['doctor']);
};

$appointmentById = function (int $appointmentId) use ($db): ?array {
    $stmt = $db->prepare("SELECT id, patient_id, status, doctor_id FROM appointments WHERE id = ? LIMIT 1");
    $stmt->execute([$appointmentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
};
$normalizeStatusKey = function ($status): string {
    return str_replace([' ', '-'], '_', strtolower(trim((string)$status)));
};
$normalizeNameKey = function ($value): string {
    return preg_replace('/\s+/', ' ', strtolower(trim((string)$value)));
};
$allowedMedicalAidProviders = [
    'Cimas Health Group',
    'CellMed Health Fund',
    'BonVie Medical Aid Society',
    'MASCA (Medical Aid Society of Central Africa)',
    'Alliance Health (Multimed, Northern Alliance)',
    'Corporate 24',
    'Maisha Health Fund',
    'Salutem International Medical Fund',
    'Ultra Med Health Care',
    'SaveLife Medical Aid Fund',
    'For All Medical Aid Society (FA-MAS)',
    'Budget Health'
];
$allowedMedicalAidPlans = [
    'Basic / Essential Medical Aid Cover',
    'Standard Medical Aid Cover',
    'Premium / Comprehensive Medical Aid Cover'
];
$allowedStudentFaculties = [
    'Faculty of Science Education',
    'Faculty of Science and Engineering',
    'Faculty of Agriculture and Environmental Sciences',
    'Faculty of Commerce',
    'Faculty of Social Sciences and Humanities'
];
$allowedLevels = ['1', '2', '3', '4', '5', '6', '7'];
$allowedSemesters = ['1', '2'];
$facultyProgrammes = [
    'Faculty of Science Education' => [
        'BSc Education (Biology)',
        'BSc Education (Chemistry)',
        'BSc Education (Mathematics)',
        'BSc Education (Physics)',
        'Diploma in Science Education'
    ],
    'Faculty of Science and Engineering' => [
        'BSc Computer Science',
        'BSc Information Systems',
        'BSc Software Engineering',
        'BSc Physics',
        'BSc Chemistry',
        'BSc Mathematics',
        'BSc Statistics'
    ],
    'Faculty of Agriculture and Environmental Sciences' => [
        'BSc Agriculture',
        'BSc Crop Science',
        'BSc Animal Science',
        'BSc Agricultural Economics',
        'BSc Environmental Science',
        'BSc Forestry and Wildlife Management'
    ],
    'Faculty of Commerce' => [
        'Bachelor of Accounting',
        'Bachelor of Business Management',
        'Bachelor of Marketing',
        'Bachelor of Economics',
        'Bachelor of Human Resources Management',
        'Bachelor of Finance and Banking'
    ],
    'Faculty of Social Sciences and Humanities' => [
        'Bachelor of Psychology',
        'Bachelor of Sociology',
        'Bachelor of Development Studies',
        'Bachelor of Peace and Governance',
        'Bachelor of Languages and Communication',
        'Bachelor of History and Heritage Studies'
    ]
];

switch ($action) {

    // 1. DASHBOARD STATS (Matches loadDashboardStats() in dashboard.html)
    case 'stats':
        if ($method === 'GET') {
            try {
                // Count today's total visits
                $stmt = $db->query("SELECT COUNT(*) FROM patient_queue WHERE DATE(created_at) = CURDATE()");
                $today = $stmt->fetchColumn();

                // Count Pending Triage (Waiting status)
                $stmt = $db->query("SELECT COUNT(*) FROM patient_queue WHERE LOWER(status) = 'waiting'");
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
            $q = trim((string)($_GET['q'] ?? ''));
            if(strlen($q) < 2) { echo json_encode([]); exit; }
            if(strlen($q) > 80) {
                RequestValidator::fail(400, "q is too long.");
            }
            if (!preg_match('/^[A-Za-z0-9\+\-\s]+$/', $q)) {
                RequestValidator::fail(400, "q contains invalid characters.");
            }

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
            $fullName = RequestValidator::requireString($data, 'full_name', 3, 150, '/^[A-Za-z]+(?:\s+[A-Za-z]+)+$/');
            $nationalId = RequestValidator::requireString($data, 'national_id', 5, 30, '/^\d{2}\-\d{6,8}\-[A-Za-z]\-\d{2}$/');
            $dob = RequestValidator::requireString($data, 'dob', 10, 10, '/^\d{4}\-\d{2}\-\d{2}$/');
            if (!$isValidIsoDate($dob)) {
                RequestValidator::fail(400, "dob format is invalid.");
            }
            if ($dob > date('Y-m-d')) {
                RequestValidator::fail(400, "dob cannot be in the future.");
            }
            $gender = RequestValidator::enum($data->gender ?? '', ['male', 'female'], 'gender');
            $phone = RequestValidator::requireString($data, 'phone', 13, 13, '/^\+263\d{9}$/');
            $address = RequestValidator::requireString($data, 'address', 3, 255);
            $hasAidRaw = $data->has_medical_aid ?? 0;
            if (!(is_bool($hasAidRaw) || in_array((string)$hasAidRaw, ['0', '1'], true))) {
                RequestValidator::fail(400, "has_medical_aid has invalid value.");
            }
            $hasMedicalAid = !empty($hasAidRaw) ? 1 : 0;
            $aidProvider = RequestValidator::optionalString($data, 'medical_aid_provider', 120);
            $aidNumber = RequestValidator::optionalString($data, 'medical_aid_number', 60);
            $aidMemberName = null;
            $aidSuffix = null;
            $aidPlan = null;
            $aidDateJoined = null;
            $kinName = RequestValidator::requireString($data, 'kin_name', 3, 150, '/^[A-Za-z]+(?:\s+[A-Za-z]+)+$/');
            $kinRelation = RequestValidator::requireString($data, 'kin_relation', 2, 80);
            $kinPhone = RequestValidator::requireString($data, 'kin_phone', 13, 13, '/^\+263\d{9}$/');
            $allergies = RequestValidator::requireString($data, 'allergies', 1, 1000);

            if ($hasMedicalAid) {
                if ($aidProvider === null || trim((string)$aidProvider) === '') {
                    RequestValidator::fail(400, "medical_aid_provider is required when has_medical_aid is enabled.");
                }
                if ($aidNumber === null || trim((string)$aidNumber) === '') {
                    RequestValidator::fail(400, "medical_aid_number is required when has_medical_aid is enabled.");
                }
            }

            $medicalAidObj = (isset($data->medical_aid) && is_object($data->medical_aid)) ? $data->medical_aid : null;
            if ($hasMedicalAid && !$medicalAidObj) {
                RequestValidator::fail(400, "medical_aid payload is required when has_medical_aid is enabled.");
            }
            if ($medicalAidObj) {
                $providerObj = trim((string)($medicalAidObj->provider ?? ''));
                $memberNoObj = trim((string)($medicalAidObj->member_number ?? ''));
                if ($hasMedicalAid && ($providerObj === '' || $memberNoObj === '')) {
                    RequestValidator::fail(400, "medical_aid.provider and medical_aid.member_number are required.");
                }
                $memberNameObj = trim((string)($medicalAidObj->member_name ?? ''));
                $suffixObj = trim((string)($medicalAidObj->suffix ?? ''));
                $planObj = trim((string)($medicalAidObj->plan ?? ''));
                if ($hasMedicalAid && !in_array($providerObj, $allowedMedicalAidProviders, true)) {
                    RequestValidator::fail(400, "medical_aid.provider is invalid.");
                }
                if ($hasMedicalAid && $memberNameObj === '') {
                    RequestValidator::fail(400, "medical_aid.member_name is required.");
                }
                if ($hasMedicalAid && ($normalizeNameKey($memberNameObj) !== $normalizeNameKey($fullName))) {
                    RequestValidator::fail(400, "medical_aid.member_name must match full_name.");
                }
                if ($hasMedicalAid && !in_array($planObj, $allowedMedicalAidPlans, true)) {
                    RequestValidator::fail(400, "medical_aid.plan is invalid.");
                }
                if ($memberNameObj !== '' && strlen($memberNameObj) > 150) {
                    RequestValidator::fail(400, "medical_aid.member_name is too long.");
                }
                if ($suffixObj !== '' && strlen($suffixObj) > 30) {
                    RequestValidator::fail(400, "medical_aid.suffix is too long.");
                }
                if ($planObj !== '' && strlen($planObj) > 120) {
                    RequestValidator::fail(400, "medical_aid.plan is too long.");
                }
                $aidDateJoinedObj = trim((string)($medicalAidObj->date_joined ?? ''));
                if ($aidDateJoinedObj !== '' && !$isValidIsoDate($aidDateJoinedObj)) {
                    RequestValidator::fail(400, "medical_aid.date_joined format is invalid.");
                }
                if ($aidDateJoinedObj !== '' && $aidDateJoinedObj > date('Y-m-d')) {
                    RequestValidator::fail(400, "medical_aid.date_joined cannot be in the future.");
                }
                $aidNatIdObj = trim((string)($medicalAidObj->national_id_optional ?? ''));
                if ($aidNatIdObj !== '' && !preg_match('/^\d{2}\-\d{6,8}\-[A-Za-z]\-\d{2}$/', $aidNatIdObj)) {
                    RequestValidator::fail(400, "medical_aid.national_id_optional format is invalid.");
                }
                if ($aidNatIdObj !== '' && $aidNatIdObj !== $nationalId) {
                    RequestValidator::fail(400, "medical_aid.national_id_optional must match national_id.");
                }
                if ($providerObj !== '' && $aidProvider !== null && $providerObj !== $aidProvider) {
                    RequestValidator::fail(400, "medical_aid.provider does not match medical_aid_provider.");
                }
                if ($memberNoObj !== '' && $aidNumber !== null && $memberNoObj !== $aidNumber) {
                    RequestValidator::fail(400, "medical_aid.member_number does not match medical_aid_number.");
                }
                if ($providerObj !== '') $aidProvider = $providerObj;
                if ($memberNoObj !== '') $aidNumber = $memberNoObj;
                $aidMemberName = $memberNameObj !== '' ? $memberNameObj : null;
                $aidSuffix = $suffixObj !== '' ? $suffixObj : null;
                $aidPlan = $planObj !== '' ? $planObj : null;
                $aidDateJoined = $aidDateJoinedObj !== '' ? $aidDateJoinedObj : null;
            }

            $identityDocObj = (isset($data->identity_doc) && is_object($data->identity_doc)) ? $data->identity_doc : null;
            if (!$identityDocObj) {
                RequestValidator::fail(400, "identity_doc payload is required.");
            }
            $identityDocPayload = null;
            if ($identityDocObj) {
                $idType = RequestValidator::enum($identityDocObj->id_type ?? '', ['Student ID', 'Staff ID'], 'identity_doc.id_type');
                $issuer = trim((string)($identityDocObj->issuer ?? ''));
                $cardNumber = trim((string)($identityDocObj->card_number ?? ''));
                if ($issuer === '' || strlen($issuer) > 150) {
                    RequestValidator::fail(400, "identity_doc.issuer is required.");
                }
                $recognitionNumber = trim((string)($identityDocObj->ec_number ?? ''));
                if ($idType === 'Student ID') {
                    if ($cardNumber === '' || !preg_match('/^[A-Za-z0-9\/\-]{2,80}$/', $cardNumber)) {
                        RequestValidator::fail(400, "identity_doc.card_number format is invalid for Student ID.");
                    }
                    if ($recognitionNumber === '' || !preg_match('/^[A-Za-z0-9\/\-]{3,30}$/', $recognitionNumber)) {
                        RequestValidator::fail(400, "identity_doc.student_number is required and format must be valid.");
                    }
                    if ($cardNumber !== $recognitionNumber) {
                        RequestValidator::fail(400, "identity_doc.card_number must match student number for Student ID.");
                    }
                } else {
                    if ($cardNumber === '' || !preg_match('/^\d{4}$/', $cardNumber)) {
                        RequestValidator::fail(400, "identity_doc.card_number must be exactly 4 digits for Staff ID.");
                    }
                    if ($recognitionNumber === '' || !preg_match('/^\d{4}$/', $recognitionNumber)) {
                        RequestValidator::fail(400, "identity_doc.ec_number must be exactly 4 digits for Staff ID.");
                    }
                    if ($cardNumber !== $recognitionNumber) {
                        RequestValidator::fail(400, "identity_doc.card_number must match EC number for Staff ID.");
                    }
                }
                $faculty = trim((string)($identityDocObj->faculty ?? ''));
                $department = trim((string)($identityDocObj->department ?? ''));
                if ($department === '') $department = $faculty;
                if ($idType === 'Student ID' && $faculty === '') {
                    RequestValidator::fail(400, "identity_doc.faculty is required for Student ID.");
                }
                if ($idType === 'Staff ID' && $department === '') {
                    RequestValidator::fail(400, "identity_doc.department is required for Staff ID.");
                }
                if ($idType === 'Student ID' && !in_array($faculty, $allowedStudentFaculties, true)) {
                    RequestValidator::fail(400, "identity_doc.faculty is invalid.");
                }
                if ($faculty !== '' && strlen($faculty) > 120) {
                    RequestValidator::fail(400, "identity_doc.faculty is too long.");
                }
                if ($department !== '' && strlen($department) > 120) {
                    RequestValidator::fail(400, "identity_doc.department is too long.");
                }
                $programme = trim((string)($identityDocObj->programme ?? ''));
                $level = trim((string)($identityDocObj->level ?? ''));
                $semester = trim((string)($identityDocObj->semester ?? ''));
                $status = trim((string)($identityDocObj->status ?? ''));
                if ($idType === 'Student ID' && $programme !== '') {
                    $facultyProgrammeList = $facultyProgrammes[$faculty] ?? [];
                    if (!empty($facultyProgrammeList) && !in_array($programme, $facultyProgrammeList, true)) {
                        RequestValidator::fail(400, "identity_doc.programme is invalid for selected faculty.");
                    }
                }
                if ($programme !== '' && strlen($programme) > 120) {
                    RequestValidator::fail(400, "identity_doc.programme is too long.");
                }
                if ($level !== '' && strlen($level) > 80) {
                    RequestValidator::fail(400, "identity_doc.level is too long.");
                }
                if ($semester !== '' && strlen($semester) > 80) {
                    RequestValidator::fail(400, "identity_doc.semester is too long.");
                }
                if ($level !== '' && !in_array($level, $allowedLevels, true)) {
                    RequestValidator::fail(400, "identity_doc.level must be between 1 and 7.");
                }
                if ($semester !== '' && !in_array($semester, $allowedSemesters, true)) {
                    RequestValidator::fail(400, "identity_doc.semester must be 1 or 2.");
                }
                if ($status !== '' && strlen($status) > 80) {
                    RequestValidator::fail(400, "identity_doc.status is too long.");
                }
                $expiryDate = trim((string)($identityDocObj->expiry_date ?? ''));
                if ($expiryDate !== '' && !$isValidIsoDate($expiryDate)) {
                    RequestValidator::fail(400, "identity_doc.expiry_date format is invalid.");
                }
                $identityDocPayload = [
                    'id_type' => $idType,
                    'issuer' => $issuer,
                    'card_number' => $cardNumber,
                    'recognition_number' => $recognitionNumber,
                    'student_number' => $idType === 'Student ID' ? $recognitionNumber : '',
                    'ec_number' => $idType === 'Staff ID' ? $recognitionNumber : '',
                    'faculty' => $faculty,
                    'department' => $department,
                    'programme' => $programme,
                    'level' => $level,
                    'semester' => $semester,
                    'status' => $status,
                    'expiry_date' => $expiryDate !== '' ? $expiryDate : null
                ];
            }

            $sql = "INSERT INTO patients (
                        full_name, national_id, dob, gender, phone, address,
                        has_medical_aid, medical_aid_provider, medical_aid_number,
                        medical_aid_member_name, medical_aid_suffix, medical_aid_plan, medical_aid_date_joined,
                        kin_name, kin_relation, kin_phone, allergies
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
                $hasMedicalAid ? $aidMemberName : null,
                $hasMedicalAid ? $aidSuffix : null,
                $hasMedicalAid ? $aidPlan : null,
                $hasMedicalAid ? $aidDateJoined : null,
                $kinName,
                $kinRelation,
                $kinPhone,
                $allergies
            ];

            try {
                $db->beginTransaction();

                if (!$stmt->execute($params)) {
                    throw new Exception("Database error during registration");
                }
                $newPatientId = (int)$db->lastInsertId();

                if ($identityDocPayload && $identityDocPayload['id_type'] === 'Student ID') {
                    $studentStmt = $db->prepare("INSERT INTO patient_students (
                            patient_id, issuer_institution, card_number, student_number, faculty,
                            programme, level_name, semester, student_status, expiry_date
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if (!$studentStmt->execute([
                        $newPatientId,
                        $identityDocPayload['issuer'],
                        $identityDocPayload['card_number'],
                        $identityDocPayload['student_number'],
                        $identityDocPayload['faculty'] !== '' ? $identityDocPayload['faculty'] : null,
                        $identityDocPayload['programme'] !== '' ? $identityDocPayload['programme'] : null,
                        $identityDocPayload['level'] !== '' ? $identityDocPayload['level'] : null,
                        $identityDocPayload['semester'] !== '' ? $identityDocPayload['semester'] : null,
                        $identityDocPayload['status'] !== '' ? $identityDocPayload['status'] : null,
                        $identityDocPayload['expiry_date']
                    ])) {
                        throw new Exception("Failed to save student identity details.");
                    }
                }

                if ($identityDocPayload && $identityDocPayload['id_type'] === 'Staff ID') {
                    $staffStmt = $db->prepare("INSERT INTO patient_staff (
                            patient_id, issuer_institution, card_number, ec_number, department, faculty,
                            programme, level_name, semester, staff_status, expiry_date
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if (!$staffStmt->execute([
                        $newPatientId,
                        $identityDocPayload['issuer'],
                        $identityDocPayload['card_number'],
                        $identityDocPayload['ec_number'],
                        $identityDocPayload['department'] !== '' ? $identityDocPayload['department'] : null,
                        $identityDocPayload['faculty'] !== '' ? $identityDocPayload['faculty'] : null,
                        $identityDocPayload['programme'] !== '' ? $identityDocPayload['programme'] : null,
                        $identityDocPayload['level'] !== '' ? $identityDocPayload['level'] : null,
                        $identityDocPayload['semester'] !== '' ? $identityDocPayload['semester'] : null,
                        $identityDocPayload['status'] !== '' ? $identityDocPayload['status'] : null,
                        $identityDocPayload['expiry_date']
                    ])) {
                        throw new Exception("Failed to save staff identity details.");
                    }
                }

                $encounter = $upsertVisitEncounter($newPatientId, null, 'Registration intake');
                $visitId = (int)$encounter['visit_id'];

                // Auto-queue every new registration as a waiting intake linked to a visit encounter.
                $queueStmt = $db->prepare("INSERT INTO patient_queue (patient_id, visit_id, doctor_assigned, status) VALUES (?, ?, ?, 'Waiting')");
                if (!$queueStmt->execute([$newPatientId, $visitId, null])) {
                    throw new Exception("Failed to auto-add patient to queue");
                }
                $queueId = (int)$db->lastInsertId();

                $db->commit();
                Realtime::emit('reception.register', ['patient_id' => $newPatientId, 'queue_id' => $queueId]);
                Realtime::emit('reception.admit', ['queue_id' => $queueId, 'patient_id' => $newPatientId]);
                echo json_encode([
                    "message" => "Patient registered, encounter created, and queued for triage intake.",
                    "patient_id" => $newPatientId,
                    "visit_id" => $visitId,
                    "queue_id" => $queueId,
                    "status" => "Waiting"
                ]);
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                http_response_code(500);
                echo json_encode(["message" => $e->getMessage()]);
            }
        }
        break;

    // 4. ADMIT PATIENT (Simplified workflow)
    case 'admit':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            $doctorRaw = $data->doctor ?? null;
            if ($doctorRaw !== null && $doctorRaw !== '' && !is_numeric($doctorRaw)) {
                RequestValidator::fail(400, "doctor must be a valid doctor id.");
            }
            $doctorId = $parseDoctorId($doctorRaw);
            if (!is_null($doctorId) && !$doctorExists($doctorId)) {
                RequestValidator::fail(400, "doctor was not found.");
            }

            $check = $db->prepare("SELECT id FROM patient_queue WHERE patient_id = ? AND LOWER(status) NOT IN ('completed', 'cancelled', 'discharged')");
            $check->execute([$patientId]);
            if($check->rowCount() > 0) {
                 http_response_code(400);
                 echo json_encode(["message" => "Patient is already active in the queue"]);
                 exit;
            }

            // Reception always sends to Nurse first
            $initial_status = 'Waiting';
            $encounter = $upsertVisitEncounter($patientId, $doctorId, 'Walk-in reception encounter');
            $visitId = (int)$encounter['visit_id'];

            $sql = "INSERT INTO patient_queue (patient_id, visit_id, doctor_assigned, status) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);

            if($stmt->execute([$patientId, $visitId, $doctorId, $initial_status])) {
                $queueId = $db->lastInsertId();
                Realtime::emit('reception.admit', ['queue_id' => $queueId]);
                echo json_encode(["message" => "Patient admitted with encounter.", "visit_id" => $visitId]);
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
                    AND LOWER(q.status) NOT IN ('completed', 'cancelled', 'discharged')) as is_active
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
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            $scheduledAt = RequestValidator::requireString($data, 'scheduled_at', 16, 19, '/^\d{4}\-\d{2}\-\d{2}[ T]\d{2}\:\d{2}(\:\d{2})?$/');
            if (!$isValidDateTime($scheduledAt)) {
                RequestValidator::fail(400, "scheduled_at format is invalid.");
            }
            $scheduledTs = strtotime(str_replace('T', ' ', $scheduledAt));
            if ($scheduledTs === false) {
                RequestValidator::fail(400, "scheduled_at value is invalid.");
            }
            if ($scheduledTs < time()) {
                RequestValidator::fail(400, "scheduled_at cannot be in the past.");
            }
            $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, scheduled_at, status, reason, notes)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $doctorId = isset($data->doctor_id) && $data->doctor_id !== '' ? RequestValidator::requireInt($data, 'doctor_id', 1) : null;
            if (!is_null($doctorId) && !$doctorExists($doctorId)) {
                RequestValidator::fail(400, "doctor_id was not found.");
            }
            $status = RequestValidator::enum($data->status ?? 'scheduled', ['scheduled', 'confirmed', 'checked_in', 'cancelled', 'completed'], 'status');
            $reason = RequestValidator::optionalString($data, 'reason', 1000);
            if (!is_null($reason) && $reason !== '' && strlen($reason) < 3) {
                RequestValidator::fail(400, "reason is too short.");
            }
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
            if ($start !== null && $start !== '' && !$isValidIsoDate((string)$start) && !$isValidDateTime((string)$start)) {
                RequestValidator::fail(400, "start format is invalid.");
            }
            if ($end !== null && $end !== '' && !$isValidIsoDate((string)$end) && !$isValidDateTime((string)$end)) {
                RequestValidator::fail(400, "end format is invalid.");
            }
            if ($start && $end && strtotime(str_replace('T', ' ', (string)$start)) > strtotime(str_replace('T', ' ', (string)$end))) {
                RequestValidator::fail(400, "start cannot be after end.");
            }
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
            $appointmentRow = $appointmentById($appointmentId);
            if (!$appointmentRow) {
                RequestValidator::fail(400, "appointment_id was not found.");
            }
            $status = RequestValidator::enum($data->status ?? '', ['scheduled', 'confirmed', 'checked_in', 'cancelled', 'completed'], 'status');
            $currentStatus = $normalizeStatusKey($appointmentRow['status'] ?? '');
            if (in_array($currentStatus, ['confirmed', 'checked_in', 'with_doctor', 'completed', 'cancelled'], true)) {
                RequestValidator::fail(409, "appointment is locked and cannot be changed by Reception.");
            }
            if (in_array($status, ['checked_in', 'completed'], true)) {
                RequestValidator::fail(403, "Use check-in/doctor workflow for this status.");
            }
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
            $apptRow = $appointmentById($appointmentId);
            if (!$apptRow) {
                RequestValidator::fail(400, "appointment_id was not found.");
            }
            $apptStatus = $normalizeStatusKey($apptRow['status'] ?? '');
            if ($apptStatus !== 'scheduled') {
                RequestValidator::fail(409, "appointment is locked for Reception actions.");
            }
            $doctorAssignedRaw = $data->doctor_assigned ?? null;
            if ($doctorAssignedRaw !== null && $doctorAssignedRaw !== '' && !is_numeric($doctorAssignedRaw)) {
                RequestValidator::fail(400, "doctor_assigned must be a valid doctor id.");
            }
            $doctorAssigned = $parseDoctorId($doctorAssignedRaw);
            if (!is_null($doctorAssigned) && !$doctorExists($doctorAssigned)) {
                RequestValidator::fail(400, "doctor_assigned was not found.");
            }
            $stmt = $db->prepare("UPDATE appointments SET status = 'checked_in' WHERE id = ?");
            $stmt->execute([$appointmentId]);
            if (!empty($data->queue_patient_id)) {
                $queuePatientId = RequestValidator::requireInt($data, 'queue_patient_id', 1);
                if (!$patientExists($queuePatientId)) {
                    RequestValidator::fail(400, "queue_patient_id was not found.");
                }
                if ((int)($apptRow['patient_id'] ?? 0) !== $queuePatientId) {
                    RequestValidator::fail(400, "queue_patient_id does not match appointment patient.");
                }
                $encounter = $upsertVisitEncounter($queuePatientId, $doctorAssigned, 'Appointment check-in');
                $visitId = (int)$encounter['visit_id'];
                // Avoid duplicate active queue rows for same patient
                $active = $db->prepare("SELECT id, visit_id FROM patient_queue
                                        WHERE patient_id = ?
                                          AND LOWER(status) NOT IN ('completed','cancelled','discharged')
                                        ORDER BY created_at DESC, id DESC
                                        LIMIT 1");
                $active->execute([$queuePatientId]);
                $activeRow = $active->fetch(PDO::FETCH_ASSOC);
                $activeId = $activeRow ? (int)$activeRow['id'] : null;
                if ($activeId) {
                    if (!is_null($doctorAssigned)) {
                        $upd = $db->prepare("UPDATE patient_queue SET doctor_assigned = ? WHERE id = ?");
                        $upd->execute([$doctorAssigned, $activeId]);
                    }
                    if ($visitId > 0) {
                        $link = $db->prepare("UPDATE patient_queue SET visit_id = COALESCE(visit_id, ?) WHERE id = ?");
                        $link->execute([$visitId, $activeId]);
                    }
                } else {
                    $stmt2 = $db->prepare("INSERT INTO patient_queue (patient_id, visit_id, doctor_assigned, status) VALUES (?, ?, ?, 'Waiting')");
                    $stmt2->execute([$queuePatientId, $visitId, $doctorAssigned]);
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
                    WHERE LOWER(q.status) NOT IN ('completed','cancelled','discharged')
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
            $queueCheck = $db->prepare("SELECT id, status FROM patient_queue WHERE id = ? LIMIT 1");
            $queueCheck->execute([$queueId]);
            $queueRow = $queueCheck->fetch(PDO::FETCH_ASSOC);
            if (!$queueRow) {
                RequestValidator::fail(400, "queue_id was not found.");
            }
            $queueStatus = $normalizeStatusKey($queueRow['status'] ?? '');
            if (in_array($queueStatus, ['with_doctor', 'completed', 'cancelled', 'discharged'], true)) {
                RequestValidator::fail(409, "queue entry is locked and cannot be changed by Reception.");
            }
            $actionType = RequestValidator::enum($data->action ?? '', ['cancel', 'no_show', 'reassign', 'move_top'], 'action');
            if ($actionType === 'cancel' || $actionType === 'no_show') {
                $stmt = $db->prepare("UPDATE patient_queue SET status = 'Cancelled' WHERE id = ?");
                $stmt->execute([$queueId]);
                $cancelVisitEncounter($queueId);
            } else if ($actionType === 'reassign') {
                $doctorAssigned = isset($data->doctor_assigned) && $data->doctor_assigned !== '' ? RequestValidator::requireInt($data, 'doctor_assigned', 1) : null;
                if (!is_null($doctorAssigned) && !$doctorExists($doctorAssigned)) {
                    RequestValidator::fail(400, "doctor_assigned was not found.");
                }
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
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
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
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
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
            $patient_id_raw = $_POST['patient_id'] ?? null;
            $report_name = trim((string)($_POST['report_name'] ?? ''));
            if (!is_numeric($patient_id_raw)) {
                RequestValidator::fail(400, "patient_id must be a valid number.");
            }
            $patient_id = (int)$patient_id_raw;
            if ($patient_id <= 0 || !$patientExists($patient_id)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            if ($report_name === '' || strlen($report_name) < 3 || strlen($report_name) > 255) {
                RequestValidator::fail(400, "report_name length is invalid.");
            }
            if (empty($_FILES["report_file"]["tmp_name"])) {
                http_response_code(400);
                echo json_encode(["message" => "Patient, name, and file are required"]);
                exit;
            }
            if (!isset($_FILES["report_file"]["error"]) || (int)$_FILES["report_file"]["error"] !== UPLOAD_ERR_OK) {
                RequestValidator::fail(400, "Uploaded file is invalid.");
            }
            $maxFileBytes = 10 * 1024 * 1024;
            $fileSize = isset($_FILES["report_file"]["size"]) ? (int)$_FILES["report_file"]["size"] : 0;
            if ($fileSize <= 0 || $fileSize > $maxFileBytes) {
                RequestValidator::fail(400, "File size must be between 1 byte and 10MB.");
            }
            $originalName = (string)($_FILES["report_file"]["name"] ?? '');
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExt = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx'];
            if (!in_array($ext, $allowedExt, true)) {
                RequestValidator::fail(400, "Unsupported file type.");
            }
            $target_dir = "../../uploads/patient_files/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $safeBase = preg_replace('/[^A-Za-z0-9_\.-]/', '_', basename($originalName));
            $file_name = time() . "_" . $safeBase;
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
            if (!$isValidIsoDate($date)) {
                RequestValidator::fail(400, "date format is invalid.");
            }
            if ($date > date('Y-m-d')) {
                RequestValidator::fail(400, "date cannot be in the future.");
            }
            $stmt = $db->prepare("SELECT COUNT(*) FROM patient_queue WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $admitted = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $registered = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM patient_queue WHERE DATE(created_at) = ? AND LOWER(status) = 'cancelled'");
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
            if (!$userExists($userId, ['receptionist', 'admin'])) {
                RequestValidator::fail(400, "user_id was not found for reception handover.");
            }
            $notes = RequestValidator::requireString($data, 'notes', 3, 2000);
            $shiftStart = RequestValidator::optionalString($data, 'shift_start', 40);
            $shiftEnd = RequestValidator::optionalString($data, 'shift_end', 40);
            if ($shiftStart !== null && $shiftStart !== '' && !$isValidDateTime($shiftStart)) {
                RequestValidator::fail(400, "shift_start format is invalid.");
            }
            if ($shiftEnd !== null && $shiftEnd !== '' && !$isValidDateTime($shiftEnd)) {
                RequestValidator::fail(400, "shift_end format is invalid.");
            }
            if ($shiftStart && $shiftEnd && strtotime($shiftEnd) < strtotime($shiftStart)) {
                RequestValidator::fail(400, "shift_end cannot be before shift_start.");
            }
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
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            $medicineName = RequestValidator::requireString($data, 'medicine_name', 2, 120);
            $quantity = isset($data->quantity) ? RequestValidator::requireInt($data, 'quantity', 1, 1000) : 1;
            $notes = RequestValidator::optionalString($data, 'notes', 1000);
            $requestedBy = isset($data->requested_by) && $data->requested_by !== '' ? RequestValidator::requireInt($data, 'requested_by', 1) : null;
            if (!is_null($requestedBy) && !$userExists($requestedBy)) {
                RequestValidator::fail(400, "requested_by was not found.");
            }
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
                $pidRaw = $_GET['patient_id'] ?? 0;
                if (!is_numeric($pidRaw)) {
                    RequestValidator::fail(400, "patient_id must be a valid number.");
                }
                $pid = (int)$pidRaw;
                if ($pid <= 0) {
                    http_response_code(400);
                    echo json_encode(["message" => "Patient ID required"]);
                    exit;
                }
                if (!$patientExists($pid)) {
                    RequestValidator::fail(400, "patient_id was not found.");
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

                $stmt = $db->prepare("SELECT v.id, v.patient_id, v.status, v.chief_complaint, v.created_at, v.updated_at,
                                             u.full_name AS doctor_assigned
                                      FROM visits v
                                      LEFT JOIN users u ON u.id = v.doctor_id
                                      WHERE v.patient_id = ?
                                      ORDER BY v.created_at DESC
                                      LIMIT 50");
                $stmt->execute([$pid]);
                $visits = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                if (empty($visits)) {
                    $stmt = $db->prepare("SELECT q.id, q.patient_id, q.status, q.created_at, q.doctor_assigned
                                          FROM patient_queue q
                                          WHERE q.patient_id = ?
                                          ORDER BY q.created_at DESC
                                          LIMIT 50");
                    $stmt->execute([$pid]);
                    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }

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
