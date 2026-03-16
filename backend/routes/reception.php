<?php
// FILE: backend/routes/reception.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/Realtime.php';
require_once __DIR__ . '/../utils/DbSchema.php';
require_once __DIR__ . '/../utils/RequestValidator.php';

$database = new Database();
$db = $database->getConnection();

DbSchema::ensureNurseInChargeRole($db, true);
DbSchema::ensureAppointments($db);
DbSchema::ensureReceptionHandover($db);
DbSchema::ensureVisitEncounters($db);
DbSchema::ensureCounsellingWorkflow($db);
DbSchema::ensurePrescriptionWorkflow($db);
DbSchema::ensureReceptionIdentityTables($db);
DbSchema::ensureReceptionPatientMedicalAidColumns($db);
DbSchema::ensureReferralRegistry($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
$allowedRoles = ['receptionist', 'admin'];
if ($action === 'search') {
    $allowedRoles[] = 'nurse_aid';
}
RoleMiddleware::allow($allowedRoles, $user);

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
    return $userExists($doctorId, ['doctor', 'nurse_in_charge']);
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
$studentStatuses = ['Block Release', 'Conventional'];
$staffStatuses = ['Fulltime', 'Parttime'];
$fullNamePattern = "/^[A-Za-z]+(?:[ '\\-][A-Za-z]+)*(?:\\s+[A-Za-z]+(?:[ '\\-][A-Za-z]+)*)+$/";
$addressPattern = "/^[A-Za-z0-9#.,'()\\/\\-\\s]{3,255}$/";
$relationPattern = '/^[A-Za-z][A-Za-z\s\-\/&]{1,79}$/';
$issuerPattern = '/^[A-Za-z0-9&(),.\/\-\s]{2,150}$/';
$departmentPattern = '/^[A-Za-z0-9&(),.\/\-\s]{2,120}$/';
$memberNoPattern = '/^[A-Za-z0-9\/\-]{2,60}$/';
$suffixPattern = '/^[A-Za-z0-9\/\-]{1,30}$/';
$notesPattern = '/^[A-Za-z0-9\s\.,;:\(\)\'"!\?\/\-\&%#]+$/';
$reportNamePattern = '/^[A-Za-z0-9\s\.,\(\)\-\/&]{3,255}$/';
$assertAllowedFields = function ($payload, array $allowed, string $context) {
    if (!is_object($payload)) {
        RequestValidator::fail(400, "$context payload is invalid.");
    }
    foreach (array_keys(get_object_vars($payload)) as $field) {
        if (!in_array($field, $allowed, true)) {
            RequestValidator::fail(400, "$context contains unsupported field: $field.");
        }
    }
};
$normalizeText = function ($value): string {
    return trim(preg_replace('/\s+/', ' ', (string)$value));
};
$validatePattern = function (?string $value, string $pattern): bool {
    if ($value === null) return false;
    return (bool)preg_match($pattern, $value);
};
$ensureReasonableAge = function (string $dobIso) {
    try {
        $dobDt = new DateTime($dobIso);
        $todayDt = new DateTime(date('Y-m-d'));
        $years = (int)$dobDt->diff($todayDt)->y;
        if ($years > 130) {
            RequestValidator::fail(400, "dob indicates an invalid age.");
        }
    } catch (Exception $e) {
        RequestValidator::fail(400, "dob value is invalid.");
    }
};
$normalizeDateTime = function (string $value): string {
    return str_replace('T', ' ', trim($value));
};

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
            foreach (array_keys($_GET) as $queryKey) {
                if (!in_array($queryKey, ['q'], true)) {
                    RequestValidator::fail(400, "Unsupported query parameter: $queryKey.");
                }
            }
            $q = trim((string)($_GET['q'] ?? ''));
            if(strlen($q) < 2) { echo json_encode([]); exit; }
            if(strlen($q) > 80) {
                RequestValidator::fail(400, "q is too long.");
            }
            if (!preg_match('/^[A-Za-z0-9\+\-\s]+$/', $q)) {
                RequestValidator::fail(400, "q contains invalid characters.");
            }

            $sql = "SELECT p.*,
                           ps.student_number,
                           sf.ec_number,
                           CASE
                               WHEN ps.student_number IS NOT NULL AND TRIM(ps.student_number) <> '' THEN ps.student_number
                               WHEN sf.ec_number IS NOT NULL AND TRIM(sf.ec_number) <> '' THEN sf.ec_number
                               ELSE p.national_id
                           END AS primary_identifier,
                           CASE
                               WHEN ps.student_number IS NOT NULL AND TRIM(ps.student_number) <> '' THEN 'Student Number'
                               WHEN sf.ec_number IS NOT NULL AND TRIM(sf.ec_number) <> '' THEN 'EC Number'
                               ELSE 'National ID'
                           END AS identity_type,
                           (SELECT COUNT(*) FROM patient_queue q
                            WHERE q.patient_id = p.id
                              AND q.status NOT IN ('Completed', 'Cancelled', 'completed', 'cancelled')) as is_active
                    FROM patients p
                    LEFT JOIN patient_students ps ON ps.patient_id = p.id
                    LEFT JOIN patient_staff sf ON sf.patient_id = p.id
                    WHERE p.full_name LIKE ?
                       OR p.phone LIKE ?
                       OR p.national_id LIKE ?
                       OR ps.student_number LIKE ?
                       OR sf.ec_number LIKE ?
                    LIMIT 5";

            $stmt = $db->prepare($sql);
            $stmt->execute(["%$q%", "%$q%", "%$q%", "%$q%", "%$q%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 3. REGISTER NEW PATIENT
    case 'register':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $assertAllowedFields($data, [
                'full_name',
                'patient_number',
                'national_id',
                'dob',
                'gender',
                'visit_type',
                'doctor_assigned',
                'phone',
                'address',
                'has_medical_aid',
                'medical_aid_provider',
                'medical_aid_number',
                'medical_aid_member_name',
                'medical_aid_suffix',
                'medical_aid_plan',
                'medical_aid_date_joined',
                'kin_name',
                'kin_relation',
                'kin_phone',
                'allergies',
                'patient',
                'medical_aid',
                'identity_doc'
            ], 'register');

            $fullName = RequestValidator::requireString($data, 'full_name', 3, 150, $fullNamePattern);
            $fullName = $normalizeText($fullName);
            $nationalId = RequestValidator::optionalString($data, 'national_id', 30);
            if (!is_null($nationalId)) {
                $nationalId = $normalizeText($nationalId);
            }
            if ($nationalId !== null && $nationalId !== '' && !preg_match('/^\d{2}\-\d{6,8}\-[A-Za-z]\-\d{2}$/', $nationalId)) {
                RequestValidator::fail(400, "national_id format is invalid.");
            }
            if ($nationalId === '') {
                $nationalId = null;
            }
            $dob = RequestValidator::requireString($data, 'dob', 10, 10, '/^\d{4}\-\d{2}\-\d{2}$/');
            if (!$isValidIsoDate($dob)) {
                RequestValidator::fail(400, "dob format is invalid.");
            }
            if ($dob > date('Y-m-d')) {
                RequestValidator::fail(400, "dob cannot be in the future.");
            }
            $ensureReasonableAge($dob);
            $gender = RequestValidator::enum($data->gender ?? '', ['male', 'female'], 'gender');
            $visitType = RequestValidator::enum($data->visit_type ?? 'general', ['general', 'counselling_only'], 'visit_type');
            $doctorAssignedRaw = $data->doctor_assigned ?? null;
            if ($doctorAssignedRaw !== null && $doctorAssignedRaw !== '' && !is_numeric($doctorAssignedRaw)) {
                RequestValidator::fail(400, "doctor_assigned must be a valid nurse_in_charge id.");
            }
            $doctorAssigned = $parseDoctorId($doctorAssignedRaw);
            if ($visitType === 'counselling_only' && is_null($doctorAssigned)) {
                RequestValidator::fail(400, "doctor_assigned is required for counselling_only visits.");
            }
            if (!is_null($doctorAssigned) && !$doctorExists($doctorAssigned)) {
                RequestValidator::fail(400, "doctor_assigned (nurse_in_charge) was not found.");
            }
            $phone = RequestValidator::requireString($data, 'phone', 13, 13, '/^\+263\d{9}$/');
            $address = RequestValidator::requireString($data, 'address', 3, 255);
            $address = $normalizeText($address);
            if (!$validatePattern($address, $addressPattern)) {
                RequestValidator::fail(400, "address format is invalid.");
            }
            $hasAidRaw = $data->has_medical_aid ?? 0;
            if (!(is_bool($hasAidRaw) || in_array((string)$hasAidRaw, ['0', '1'], true))) {
                RequestValidator::fail(400, "has_medical_aid has invalid value.");
            }
            $hasMedicalAid = !empty($hasAidRaw) ? 1 : 0;
            if ($hasMedicalAid !== 1) {
                RequestValidator::fail(400, "Medical aid coverage is mandatory for reception registration.");
            }
            $aidProvider = RequestValidator::optionalString($data, 'medical_aid_provider', 120);
            $aidNumber = RequestValidator::optionalString($data, 'medical_aid_number', 60);
            $aidMemberNameFlat = RequestValidator::optionalString($data, 'medical_aid_member_name', 150);
            $aidSuffixFlat = RequestValidator::optionalString($data, 'medical_aid_suffix', 30);
            $aidPlanFlat = RequestValidator::optionalString($data, 'medical_aid_plan', 120);
            $aidDateJoinedFlat = RequestValidator::optionalString($data, 'medical_aid_date_joined', 10);
            $aidMemberName = null;
            $aidSuffix = null;
            $aidPlan = null;
            $aidDateJoined = null;
            if ($aidProvider !== null) $aidProvider = $normalizeText($aidProvider);
            if ($aidNumber !== null) $aidNumber = $normalizeText($aidNumber);
            if ($aidMemberNameFlat !== null) $aidMemberNameFlat = $normalizeText($aidMemberNameFlat);
            if ($aidSuffixFlat !== null) $aidSuffixFlat = $normalizeText($aidSuffixFlat);
            if ($aidPlanFlat !== null) $aidPlanFlat = $normalizeText($aidPlanFlat);
            if ($aidNumber !== null && $aidNumber !== '' && !$validatePattern($aidNumber, $memberNoPattern)) {
                RequestValidator::fail(400, "medical_aid_number format is invalid.");
            }
            if ($aidSuffixFlat !== null && $aidSuffixFlat !== '' && !$validatePattern($aidSuffixFlat, $suffixPattern)) {
                RequestValidator::fail(400, "medical_aid_suffix format is invalid.");
            }
            if ($aidPlanFlat !== null && $aidPlanFlat !== '' && !in_array($aidPlanFlat, $allowedMedicalAidPlans, true)) {
                RequestValidator::fail(400, "medical_aid_plan is invalid.");
            }
            if ($aidDateJoinedFlat !== null && $aidDateJoinedFlat !== '' && !$isValidIsoDate($aidDateJoinedFlat)) {
                RequestValidator::fail(400, "medical_aid_date_joined format is invalid.");
            }
            if ($aidDateJoinedFlat !== null && $aidDateJoinedFlat !== '' && $aidDateJoinedFlat > date('Y-m-d')) {
                RequestValidator::fail(400, "medical_aid_date_joined cannot be in the future.");
            }
            if ($aidDateJoinedFlat !== null && $aidDateJoinedFlat !== '' && $aidDateJoinedFlat < $dob) {
                RequestValidator::fail(400, "medical_aid_date_joined cannot be before dob.");
            }

            $kinName = RequestValidator::requireString($data, 'kin_name', 3, 150, $fullNamePattern);
            $kinName = $normalizeText($kinName);
            $kinRelation = RequestValidator::requireString($data, 'kin_relation', 2, 80);
            $kinRelation = $normalizeText($kinRelation);
            if (!$validatePattern($kinRelation, $relationPattern)) {
                RequestValidator::fail(400, "kin_relation format is invalid.");
            }
            $kinPhone = RequestValidator::requireString($data, 'kin_phone', 13, 13, '/^\+263\d{9}$/');
            $allergies = RequestValidator::requireString($data, 'allergies', 1, 1000);
            $allergies = $normalizeText($allergies);
            if (!$validatePattern($allergies, $notesPattern)) {
                RequestValidator::fail(400, "allergies contains invalid characters.");
            }

            $patientObj = (isset($data->patient) && is_object($data->patient)) ? $data->patient : null;
            if ($patientObj) {
                $assertAllowedFields($patientObj, [
                    'full_name',
                    'patient_number',
                    'national_id',
                    'dob',
                    'gender',
                    'phone',
                    'address',
                    'next_of_kin_name',
                    'next_of_kin_relationship',
                    'next_of_kin_phone',
                    'allergies_notes'
                ], 'patient');
                $patientName = $normalizeText((string)($patientObj->full_name ?? ''));
                $patientNatId = $normalizeText((string)($patientObj->national_id ?? ''));
                $patientDob = $normalizeText((string)($patientObj->dob ?? ''));
                $patientGender = strtolower($normalizeText((string)($patientObj->gender ?? '')));
                $patientPhone = $normalizeText((string)($patientObj->phone ?? ''));
                $patientAddress = $normalizeText((string)($patientObj->address ?? ''));
                $patientKinName = $normalizeText((string)($patientObj->next_of_kin_name ?? ''));
                $patientKinRel = $normalizeText((string)($patientObj->next_of_kin_relationship ?? ''));
                $patientKinPhone = $normalizeText((string)($patientObj->next_of_kin_phone ?? ''));
                $patientAllergies = $normalizeText((string)($patientObj->allergies_notes ?? ''));
                if ($patientName !== '' && $patientName !== $fullName) {
                    RequestValidator::fail(400, "patient.full_name must match full_name.");
                }
                if ($patientNatId !== '' && $patientNatId !== $nationalId) {
                    RequestValidator::fail(400, "patient.national_id must match national_id.");
                }
                if ($patientDob !== '' && $patientDob !== $dob) {
                    RequestValidator::fail(400, "patient.dob must match dob.");
                }
                if ($patientGender !== '' && $patientGender !== strtolower($gender)) {
                    RequestValidator::fail(400, "patient.gender must match gender.");
                }
                if ($patientPhone !== '' && $patientPhone !== $phone) {
                    RequestValidator::fail(400, "patient.phone must match phone.");
                }
                if ($patientAddress !== '' && $patientAddress !== $address) {
                    RequestValidator::fail(400, "patient.address must match address.");
                }
                if ($patientKinName !== '' && $patientKinName !== $kinName) {
                    RequestValidator::fail(400, "patient.next_of_kin_name must match kin_name.");
                }
                if ($patientKinRel !== '' && $patientKinRel !== $kinRelation) {
                    RequestValidator::fail(400, "patient.next_of_kin_relationship must match kin_relation.");
                }
                if ($patientKinPhone !== '' && $patientKinPhone !== $kinPhone) {
                    RequestValidator::fail(400, "patient.next_of_kin_phone must match kin_phone.");
                }
                if ($patientAllergies !== '' && $patientAllergies !== $allergies) {
                    RequestValidator::fail(400, "patient.allergies_notes must match allergies.");
                }
            }

            if ($hasMedicalAid) {
                if ($aidProvider === null || trim((string)$aidProvider) === '') {
                    RequestValidator::fail(400, "medical_aid_provider is required when has_medical_aid is enabled.");
                }
                if ($aidNumber === null || trim((string)$aidNumber) === '') {
                    RequestValidator::fail(400, "medical_aid_number is required when has_medical_aid is enabled.");
                }
                if (!$validatePattern($aidNumber, $memberNoPattern)) {
                    RequestValidator::fail(400, "medical_aid_number format is invalid.");
                }
                if (!in_array($aidProvider, $allowedMedicalAidProviders, true)) {
                    RequestValidator::fail(400, "medical_aid_provider is invalid.");
                }
                if ($aidMemberNameFlat !== null && $aidMemberNameFlat !== '' && $normalizeNameKey($aidMemberNameFlat) !== $normalizeNameKey($fullName)) {
                    RequestValidator::fail(400, "medical_aid_member_name must match full_name.");
                }
            }

            $medicalAidObj = (isset($data->medical_aid) && is_object($data->medical_aid)) ? $data->medical_aid : null;
            if ($hasMedicalAid && !$medicalAidObj) {
                RequestValidator::fail(400, "medical_aid payload is required when has_medical_aid is enabled.");
            }
            if ($medicalAidObj) {
                $assertAllowedFields($medicalAidObj, [
                    'provider',
                    'member_name',
                    'member_number',
                    'suffix',
                    'plan',
                    'date_joined',
                    'national_id_optional'
                ], 'medical_aid');

                $providerObj = $normalizeText((string)($medicalAidObj->provider ?? ''));
                $memberNoObj = $normalizeText((string)($medicalAidObj->member_number ?? ''));
                if ($hasMedicalAid && ($providerObj === '' || $memberNoObj === '')) {
                    RequestValidator::fail(400, "medical_aid.provider and medical_aid.member_number are required.");
                }
                $memberNameObj = $normalizeText((string)($medicalAidObj->member_name ?? ''));
                $suffixObj = $normalizeText((string)($medicalAidObj->suffix ?? ''));
                $planObj = $normalizeText((string)($medicalAidObj->plan ?? ''));
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
                if (!$validatePattern($memberNoObj, $memberNoPattern)) {
                    RequestValidator::fail(400, "medical_aid.member_number format is invalid.");
                }
                if ($suffixObj !== '' && !$validatePattern($suffixObj, $suffixPattern)) {
                    RequestValidator::fail(400, "medical_aid.suffix format is invalid.");
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
                if ($aidDateJoinedObj !== '' && $aidDateJoinedObj < $dob) {
                    RequestValidator::fail(400, "medical_aid.date_joined cannot be before dob.");
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
                if ($aidMemberNameFlat !== null && $aidMemberNameFlat !== '' && $aidMemberNameFlat !== $memberNameObj) {
                    RequestValidator::fail(400, "medical_aid_member_name does not match medical_aid.member_name.");
                }
                if ($aidSuffixFlat !== null && $aidSuffixFlat !== '' && $aidSuffixFlat !== $suffixObj) {
                    RequestValidator::fail(400, "medical_aid_suffix does not match medical_aid.suffix.");
                }
                if ($aidPlanFlat !== null && $aidPlanFlat !== '' && $aidPlanFlat !== $planObj) {
                    RequestValidator::fail(400, "medical_aid_plan does not match medical_aid.plan.");
                }
                if ($aidDateJoinedFlat !== null && $aidDateJoinedFlat !== '' && $aidDateJoinedFlat !== $aidDateJoinedObj) {
                    RequestValidator::fail(400, "medical_aid_date_joined does not match medical_aid.date_joined.");
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
                $assertAllowedFields($identityDocObj, [
                    'id_type',
                    'issuer',
                    'card_number',
                    'ec_number',
                    'faculty',
                    'department',
                    'programme',
                    'level',
                    'semester',
                    'status',
                    'expiry_date'
                ], 'identity_doc');
                $idType = RequestValidator::enum($identityDocObj->id_type ?? '', ['Student ID', 'Staff ID'], 'identity_doc.id_type');
                $issuer = $normalizeText((string)($identityDocObj->issuer ?? ''));
                $cardNumber = $normalizeText((string)($identityDocObj->card_number ?? ''));
                if ($issuer === '' || strlen($issuer) > 150 || !$validatePattern($issuer, $issuerPattern)) {
                    RequestValidator::fail(400, "identity_doc.issuer is required.");
                }
                $recognitionNumber = $normalizeText((string)($identityDocObj->ec_number ?? ''));
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
                    $dupStudent = $db->prepare("SELECT id FROM patient_students WHERE student_number = ? LIMIT 1");
                    $dupStudent->execute([$recognitionNumber]);
                    if ($dupStudent->fetchColumn()) {
                        RequestValidator::fail(409, "identity_doc.student_number is already registered.");
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
                    $dupStaff = $db->prepare("SELECT id FROM patient_staff WHERE ec_number = ? LIMIT 1");
                    $dupStaff->execute([$recognitionNumber]);
                    if ($dupStaff->fetchColumn()) {
                        RequestValidator::fail(409, "identity_doc.ec_number is already registered.");
                    }
                }
                $faculty = $normalizeText((string)($identityDocObj->faculty ?? ''));
                $department = $normalizeText((string)($identityDocObj->department ?? ''));
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
                if ($department !== '' && (strlen($department) > 120 || !$validatePattern($department, $departmentPattern))) {
                    RequestValidator::fail(400, "identity_doc.department format is invalid.");
                }
                $programme = $normalizeText((string)($identityDocObj->programme ?? ''));
                $level = $normalizeText((string)($identityDocObj->level ?? ''));
                $semester = $normalizeText((string)($identityDocObj->semester ?? ''));
                $status = $normalizeText((string)($identityDocObj->status ?? ''));
                if ($idType === 'Student ID' && $programme === '') {
                    RequestValidator::fail(400, "identity_doc.programme is required for Student ID.");
                }
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
                if ($idType === 'Student ID' && $level === '') {
                    RequestValidator::fail(400, "identity_doc.level is required for Student ID.");
                }
                if ($idType === 'Student ID' && $semester === '') {
                    RequestValidator::fail(400, "identity_doc.semester is required for Student ID.");
                }
                if ($level !== '' && !in_array($level, $allowedLevels, true)) {
                    RequestValidator::fail(400, "identity_doc.level must be between 1 and 7.");
                }
                if ($semester !== '' && !in_array($semester, $allowedSemesters, true)) {
                    RequestValidator::fail(400, "identity_doc.semester must be 1 or 2.");
                }
                if ($idType === 'Student ID' && !in_array($status, $studentStatuses, true)) {
                    RequestValidator::fail(400, "identity_doc.status is invalid for Student ID.");
                }
                if ($idType === 'Staff ID' && !in_array($status, $staffStatuses, true)) {
                    RequestValidator::fail(400, "identity_doc.status is invalid for Staff ID.");
                }
                if ($status !== '' && strlen($status) > 80) {
                    RequestValidator::fail(400, "identity_doc.status is too long.");
                }
                $expiryDate = trim((string)($identityDocObj->expiry_date ?? ''));
                if ($expiryDate !== '' && !$isValidIsoDate($expiryDate)) {
                    RequestValidator::fail(400, "identity_doc.expiry_date format is invalid.");
                }
                if ($expiryDate !== '' && $expiryDate < $dob) {
                    RequestValidator::fail(400, "identity_doc.expiry_date cannot be before dob.");
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

                $chiefComplaint = ($visitType === 'counselling_only') ? 'Counselling only intake' : 'Registration intake';
                $encounter = $upsertVisitEncounter($newPatientId, $doctorAssigned, $chiefComplaint);
                $visitId = (int)$encounter['visit_id'];

                $initialStatus = ($visitType === 'counselling_only') ? 'With Doctor' : 'Waiting';
                $queueStmt = $db->prepare("INSERT INTO patient_queue (patient_id, visit_id, doctor_assigned, status, visit_type) VALUES (?, ?, ?, ?, ?)");
                if (!$queueStmt->execute([$newPatientId, $visitId, $doctorAssigned, $initialStatus, $visitType])) {
                    throw new Exception("Failed to auto-add patient to queue");
                }
                $queueId = (int)$db->lastInsertId();

                $db->commit();
                Realtime::emit('reception.register', ['patient_id' => $newPatientId, 'queue_id' => $queueId]);
                Realtime::emit('reception.admit', ['queue_id' => $queueId, 'patient_id' => $newPatientId]);
                if ($visitType === 'counselling_only') {
                    Realtime::emit('reception.counselling_assigned', [
                        'queue_id' => $queueId,
                        'patient_id' => $newPatientId,
                        'patient_name' => $fullName,
                        'doctor_assigned' => $doctorAssigned,
                        'source' => 'registration'
                    ]);
                }
                echo json_encode([
                    "message" => ($visitType === 'counselling_only')
                        ? "Patient registered and assigned to Nurse In Charge for counselling."
                        : "Patient registered, encounter created, and queued for triage intake.",
                    "patient_id" => $newPatientId,
                    "visit_id" => $visitId,
                    "queue_id" => $queueId,
                    "status" => $initialStatus,
                    "visit_type" => $visitType,
                    "doctor_assigned" => $doctorAssigned
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
            $assertAllowedFields($data, ['patient_id', 'doctor', 'visit_type'], 'admit');
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            $visitType = RequestValidator::enum($data->visit_type ?? 'general', ['general', 'counselling_only'], 'visit_type');
            $doctorRaw = $data->doctor ?? null;
            if ($doctorRaw !== null && $doctorRaw !== '' && !is_numeric($doctorRaw)) {
                RequestValidator::fail(400, "assigned clinician must be a valid nurse_in_charge id.");
            }
            $doctorId = $parseDoctorId($doctorRaw);
            if ($visitType === 'counselling_only' && is_null($doctorId)) {
                RequestValidator::fail(400, "assigned clinician is required for counselling_only visits.");
            }
            if (!is_null($doctorId) && !$doctorExists($doctorId)) {
                RequestValidator::fail(400, "assigned clinician was not found.");
            }

            $check = $db->prepare("SELECT id FROM patient_queue WHERE patient_id = ? AND LOWER(status) NOT IN ('completed', 'cancelled', 'discharged')");
            $check->execute([$patientId]);
            if($check->rowCount() > 0) {
                 http_response_code(400);
                 echo json_encode(["message" => "Patient is already active in the queue"]);
                 exit;
            }

            $initial_status = ($visitType === 'counselling_only') ? 'With Doctor' : 'Waiting';
            $chiefComplaint = ($visitType === 'counselling_only') ? 'Counselling only walk-in' : 'Walk-in reception encounter';
            $encounter = $upsertVisitEncounter($patientId, $doctorId, $chiefComplaint);
            $visitId = (int)$encounter['visit_id'];

            $sql = "INSERT INTO patient_queue (patient_id, visit_id, doctor_assigned, status, visit_type) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);

            if($stmt->execute([$patientId, $visitId, $doctorId, $initial_status, $visitType])) {
                $queueId = $db->lastInsertId();
                Realtime::emit('reception.admit', ['queue_id' => $queueId]);
                if ($visitType === 'counselling_only') {
                    Realtime::emit('reception.counselling_assigned', [
                        'queue_id' => (int)$queueId,
                        'patient_id' => $patientId,
                        'doctor_assigned' => $doctorId,
                        'source' => 'admit'
                    ]);
                }
                echo json_encode([
                    "message" => ($visitType === 'counselling_only')
                        ? "Patient admitted directly to Nurse In Charge for counselling."
                        : "Patient admitted with encounter.",
                    "visit_id" => $visitId,
                    "queue_id" => (int)$queueId,
                    "status" => $initial_status,
                    "visit_type" => $visitType
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
                           ps.student_number,
                           sf.ec_number,
                           CASE
                               WHEN ps.student_number IS NOT NULL AND TRIM(ps.student_number) <> '' THEN ps.student_number
                               WHEN sf.ec_number IS NOT NULL AND TRIM(sf.ec_number) <> '' THEN sf.ec_number
                               ELSE p.national_id
                           END AS primary_identifier,
                           CASE
                               WHEN ps.student_number IS NOT NULL AND TRIM(ps.student_number) <> '' THEN 'Student Number'
                               WHEN sf.ec_number IS NOT NULL AND TRIM(sf.ec_number) <> '' THEN 'EC Number'
                               ELSE 'National ID'
                           END AS identity_type,
                           (SELECT COUNT(*) FROM patient_queue q
                            WHERE q.patient_id = p.id
                              AND LOWER(q.status) NOT IN ('completed', 'cancelled', 'discharged')) as is_active
                    FROM patients p
                    LEFT JOIN patient_students ps ON ps.patient_id = p.id
                    LEFT JOIN patient_staff sf ON sf.patient_id = p.id
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
            $assertAllowedFields($data, ['patient_id', 'doctor_id', 'scheduled_at', 'status', 'reason', 'notes'], 'create_appointment');
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            $scheduledAt = RequestValidator::requireString($data, 'scheduled_at', 16, 19, '/^\d{4}\-\d{2}\-\d{2}[ T]\d{2}\:\d{2}(\:\d{2})?$/');
            if (!$isValidDateTime($scheduledAt)) {
                RequestValidator::fail(400, "scheduled_at format is invalid.");
            }
            $scheduledDb = $normalizeDateTime($scheduledAt);
            $scheduledTs = strtotime($scheduledDb);
            if ($scheduledTs === false) {
                RequestValidator::fail(400, "scheduled_at value is invalid.");
            }
            if ($scheduledTs < time()) {
                RequestValidator::fail(400, "scheduled_at cannot be in the past.");
            }
            if ($scheduledTs > strtotime('+1 year')) {
                RequestValidator::fail(400, "scheduled_at cannot be more than 12 months ahead.");
            }
            $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, scheduled_at, status, reason, notes)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $doctorId = isset($data->doctor_id) && $data->doctor_id !== '' ? RequestValidator::requireInt($data, 'doctor_id', 1) : null;
            if (!is_null($doctorId) && !$doctorExists($doctorId)) {
                RequestValidator::fail(400, "doctor_id (nurse_in_charge) was not found.");
            }
            $status = RequestValidator::enum($data->status ?? 'scheduled', ['scheduled', 'confirmed'], 'status');
            $reason = RequestValidator::optionalString($data, 'reason', 1000);
            if (!is_null($reason)) {
                $reason = $normalizeText($reason);
            }
            if (!is_null($reason) && $reason !== '') {
                if (strlen($reason) < 3) {
                    RequestValidator::fail(400, "reason is too short.");
                }
                if (!$validatePattern($reason, $notesPattern)) {
                    RequestValidator::fail(400, "reason format is invalid.");
                }
            }
            $notes = RequestValidator::optionalString($data, 'notes', 2000);
            if (!is_null($notes)) {
                $notes = $normalizeText($notes);
            }
            if (!is_null($notes) && $notes !== '' && !$validatePattern($notes, $notesPattern)) {
                RequestValidator::fail(400, "notes format is invalid.");
            }
            if ($stmt->execute([$patientId, $doctorId, $scheduledDb, $status, $reason, $notes])) {
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
            foreach (array_keys($_GET) as $queryKey) {
                if (!in_array($queryKey, ['start', 'end'], true)) {
                    RequestValidator::fail(400, "Unsupported query parameter: $queryKey.");
                }
            }
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
            if ($start && strtotime(str_replace('T', ' ', (string)$start)) < strtotime('-2 years')) {
                RequestValidator::fail(400, "start is too far in the past.");
            }
            if ($end && strtotime(str_replace('T', ' ', (string)$end)) > strtotime('+1 year')) {
                RequestValidator::fail(400, "end is too far in the future.");
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
            $assertAllowedFields($data, ['appointment_id', 'status', 'notes'], 'appointment_update');
            $appointmentId = RequestValidator::requireInt($data, 'appointment_id', 1);
            $appointmentRow = $appointmentById($appointmentId);
            if (!$appointmentRow) {
                RequestValidator::fail(400, "appointment_id was not found.");
            }
            $status = RequestValidator::enum($data->status ?? '', ['confirmed', 'cancelled'], 'status');
            $currentStatus = $normalizeStatusKey($appointmentRow['status'] ?? '');
            if (in_array($currentStatus, ['confirmed', 'checked_in', 'with_doctor', 'completed', 'cancelled'], true)) {
                RequestValidator::fail(409, "appointment is locked and cannot be changed by Reception.");
            }
            if (!in_array($currentStatus, ['scheduled'], true)) {
                RequestValidator::fail(409, "Only scheduled appointments can be updated by Reception.");
            }
            $notes = RequestValidator::optionalString($data, 'notes', 2000);
            if (!is_null($notes)) {
                $notes = $normalizeText($notes);
            }
            if (!is_null($notes) && $notes !== '' && !$validatePattern($notes, $notesPattern)) {
                RequestValidator::fail(400, "notes format is invalid.");
            }
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
            $assertAllowedFields($data, ['appointment_id', 'queue_patient_id', 'doctor_assigned', 'visit_type'], 'checkin');
            $appointmentId = RequestValidator::requireInt($data, 'appointment_id', 1);
            $apptRow = $appointmentById($appointmentId);
            if (!$apptRow) {
                RequestValidator::fail(400, "appointment_id was not found.");
            }
            $apptStatus = $normalizeStatusKey($apptRow['status'] ?? '');
            if ($apptStatus !== 'scheduled') {
                RequestValidator::fail(409, "appointment is locked for Reception actions.");
            }
            $queuePatientId = RequestValidator::requireInt($data, 'queue_patient_id', 1);
            if (!$patientExists($queuePatientId)) {
                RequestValidator::fail(400, "queue_patient_id was not found.");
            }
            if ((int)($apptRow['patient_id'] ?? 0) !== $queuePatientId) {
                RequestValidator::fail(400, "queue_patient_id does not match appointment patient.");
            }
            $visitType = RequestValidator::enum($data->visit_type ?? 'general', ['general', 'counselling_only'], 'visit_type');
            $doctorAssignedRaw = $data->doctor_assigned ?? null;
            if ($doctorAssignedRaw !== null && $doctorAssignedRaw !== '' && !is_numeric($doctorAssignedRaw)) {
                RequestValidator::fail(400, "doctor_assigned must be a valid nurse_in_charge id.");
            }
            $doctorAssigned = $parseDoctorId($doctorAssignedRaw);
            if ($visitType === 'counselling_only' && is_null($doctorAssigned)) {
                RequestValidator::fail(400, "doctor_assigned is required for counselling_only check-in.");
            }
            if (!is_null($doctorAssigned) && !$doctorExists($doctorAssigned)) {
                RequestValidator::fail(400, "doctor_assigned (nurse_in_charge) was not found.");
            }
            $stmt = $db->prepare("UPDATE appointments SET status = 'checked_in' WHERE id = ? AND LOWER(status) = 'scheduled'");
            $stmt->execute([$appointmentId]);
            if ($stmt->rowCount() < 1) {
                RequestValidator::fail(409, "appointment could not be checked in due to status change.");
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
                $visitUpd = $db->prepare("UPDATE patient_queue SET visit_type = ? WHERE id = ?");
                $visitUpd->execute([$visitType, $activeId]);
                if ($visitType === 'counselling_only') {
                    $toDoctor = $db->prepare("UPDATE patient_queue SET status = 'With Doctor' WHERE id = ?");
                    $toDoctor->execute([$activeId]);
                }
            } else {
                $initialStatus = ($visitType === 'counselling_only') ? 'With Doctor' : 'Waiting';
                $stmt2 = $db->prepare("INSERT INTO patient_queue (patient_id, visit_id, doctor_assigned, status, visit_type) VALUES (?, ?, ?, ?, ?)");
                $stmt2->execute([$queuePatientId, $visitId, $doctorAssigned, $initialStatus, $visitType]);
            }
            Realtime::emit('reception.checkin', ['appointment_id' => $appointmentId]);
            if ($visitType === 'counselling_only') {
                Realtime::emit('reception.counselling_assigned', [
                    'appointment_id' => $appointmentId,
                    'patient_id' => $queuePatientId,
                    'doctor_assigned' => $doctorAssigned,
                    'source' => 'checkin'
                ]);
            }
            echo json_encode([
                "message" => ($visitType === 'counselling_only') ? "Checked in and sent directly to Nurse In Charge." : "Checked in",
                "visit_type" => $visitType
            ]);
        }
        break;

    // 4f. QUEUE LIST (Active)
    case 'queue_list':
        if ($method === 'GET') {
            $sql = "SELECT q.id as queue_id, q.status, q.visit_type, q.created_at, q.doctor_assigned,
                           u.full_name AS doctor_name,
                           p.full_name, p.national_id, p.phone,
                           ps.student_number, sf.ec_number,
                           CASE
                               WHEN ps.student_number IS NOT NULL AND TRIM(ps.student_number) <> '' THEN ps.student_number
                               WHEN sf.ec_number IS NOT NULL AND TRIM(sf.ec_number) <> '' THEN sf.ec_number
                               ELSE p.national_id
                           END AS primary_identifier
                    FROM patient_queue q
                    JOIN patients p ON q.patient_id = p.id
                    LEFT JOIN patient_students ps ON ps.patient_id = p.id
                    LEFT JOIN patient_staff sf ON sf.patient_id = p.id
                    LEFT JOIN users u ON u.id = q.doctor_assigned
                    WHERE LOWER(q.status) NOT IN ('completed','cancelled','discharged')
                    ORDER BY q.created_at ASC";
            $stmt = $db->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 4f-b. VISIT ENCOUNTERS (All, newest first)
    case 'visits':
        if ($method === 'GET') {
            foreach (array_keys($_GET) as $queryKey) {
                if (!in_array($queryKey, ['limit'], true)) {
                    RequestValidator::fail(400, "Unsupported query parameter: $queryKey.");
                }
            }
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 300;
            if ($limit < 1 || $limit > 1000) {
                RequestValidator::fail(400, "limit must be between 1 and 1000.");
            }

            $sql = "SELECT v.id AS visit_id,
                           v.patient_id,
                           v.status,
                           v.chief_complaint,
                           v.created_at,
                           v.updated_at,
                           p.full_name,
                           p.national_id,
                           ps.student_number,
                           sf.ec_number,
                           CASE
                               WHEN ps.student_number IS NOT NULL AND TRIM(ps.student_number) <> '' THEN ps.student_number
                               WHEN sf.ec_number IS NOT NULL AND TRIM(sf.ec_number) <> '' THEN sf.ec_number
                               ELSE p.national_id
                           END AS primary_identifier,
                           p.phone,
                           COALESCE(d.full_name, qd.full_name) AS doctor_name
                    FROM visits v
                    JOIN patients p ON p.id = v.patient_id
                    LEFT JOIN patient_students ps ON ps.patient_id = p.id
                    LEFT JOIN patient_staff sf ON sf.patient_id = p.id
                    LEFT JOIN users d ON d.id = v.doctor_id
                    LEFT JOIN (
                        SELECT visit_id, MAX(id) AS latest_queue_id
                        FROM patient_queue
                        WHERE visit_id IS NOT NULL
                        GROUP BY visit_id
                    ) qx ON qx.visit_id = v.id
                    LEFT JOIN patient_queue q ON q.id = qx.latest_queue_id
                    LEFT JOIN users qd ON qd.id = q.doctor_assigned
                    ORDER BY v.created_at DESC
                    LIMIT $limit";
            $stmt = $db->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 4g. QUEUE UPDATE (cancel, reassign, no_show, move_top)
    case 'queue_update':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $assertAllowedFields($data, ['queue_id', 'action', 'doctor_assigned'], 'queue_update');
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
            if ($actionType !== 'reassign' && isset($data->doctor_assigned) && $data->doctor_assigned !== null && $data->doctor_assigned !== '') {
                RequestValidator::fail(400, "doctor_assigned is only allowed for reassign action.");
            }
            if ($actionType === 'cancel' || $actionType === 'no_show') {
                $stmt = $db->prepare("UPDATE patient_queue SET status = 'Cancelled' WHERE id = ?");
                $stmt->execute([$queueId]);
                $cancelVisitEncounter($queueId);
            } else if ($actionType === 'reassign') {
                $doctorAssigned = RequestValidator::requireInt($data, 'doctor_assigned', 1);
                if (!$doctorExists($doctorAssigned)) {
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
            $assertAllowedFields($data, ['patient_id', 'report_name', 'file_path', 'external_doctor_name', 'destination', 'urgency', 'reason', 'status', 'referral_date', 'notes'], 'create_referral');
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            $reportName = RequestValidator::optionalString($data, 'report_name', 255);
            if ($reportName === null) $reportName = 'External referral';
            $reportName = $normalizeText($reportName);
            if ($reportName === '' || !$validatePattern($reportName, $reportNamePattern)) {
                RequestValidator::fail(400, "report_name format is invalid.");
            }
            $filePath = RequestValidator::optionalString($data, 'file_path', 255);
            if ($filePath !== null) {
                $filePath = $normalizeText($filePath);
                if ($filePath !== '' && !preg_match('/^[A-Za-z0-9_\-\.\/]{1,255}$/', $filePath)) {
                    RequestValidator::fail(400, "file_path format is invalid.");
                }
            }
            $externalDoctor = RequestValidator::optionalString($data, 'external_doctor_name', 180);
            $destination = RequestValidator::optionalString($data, 'destination', 180);
            $urgency = RequestValidator::enum($data->urgency ?? 'normal', ['normal', 'high', 'urgent'], 'urgency');
            $status = RequestValidator::enum($data->status ?? 'pending', ['pending', 'sent', 'accepted', 'declined', 'completed'], 'status');
            $reason = RequestValidator::optionalString($data, 'reason', 4000);
            $notes = RequestValidator::optionalString($data, 'notes', 4000);
            $referralDateRaw = RequestValidator::optionalString($data, 'referral_date', 30);

            $externalDoctor = is_null($externalDoctor) ? null : $normalizeText($externalDoctor);
            $destination = is_null($destination) ? null : $normalizeText($destination);
            $reason = is_null($reason) ? null : $normalizeText($reason);
            $notes = is_null($notes) ? null : $normalizeText($notes);

            $hasExternal = !empty($externalDoctor) || !empty($destination) || !empty($reason);
            if ($hasExternal) {
                $referralDate = null;
                if (!is_null($referralDateRaw) && trim((string)$referralDateRaw) !== '') {
                    $candidate = str_replace('T', ' ', trim((string)$referralDateRaw));
                    $parsed = DateTime::createFromFormat('Y-m-d H:i:s', $candidate)
                        ?: DateTime::createFromFormat('Y-m-d H:i', $candidate);
                    if (!$parsed) {
                        RequestValidator::fail(400, "referral_date must be a valid datetime.");
                    }
                    $referralDate = $parsed->format('Y-m-d H:i:s');
                }

                $stmt = $db->prepare("INSERT INTO referrals
                                      (patient_id, referred_by, referral_type, external_provider_name, destination, reason, urgency, status, referral_date, notes, attachment_path)
                                      VALUES (?, ?, 'External Doctor', ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $patientId,
                    isset($user->id) ? (int)$user->id : null,
                    $externalDoctor,
                    $destination,
                    $reason ?: $reportName,
                    strtolower(trim((string)$urgency)),
                    strtolower(trim((string)$status)),
                    $referralDate,
                    $notes,
                    $filePath
                ]);
            } else {
                $stmt = $db->prepare("INSERT INTO medical_reports (patient_id, report_type, report_name, file_path)
                                      VALUES (?, 'Referral', ?, ?)");
                $stmt->execute([$patientId, $reportName, $filePath]);
            }
            Realtime::emit('reception.referral', ['patient_id' => $patientId]);
            echo json_encode(["message" => "Referral logged"]);
        }
        break;

    // 4i. CONSENT LOG (create)
    case 'create_consent':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            $assertAllowedFields($data, ['patient_id', 'report_name', 'file_path'], 'create_consent');
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            $reportName = RequestValidator::requireString($data, 'report_name', 3, 255);
            $reportName = $normalizeText($reportName);
            if (!$validatePattern($reportName, $reportNamePattern)) {
                RequestValidator::fail(400, "report_name format is invalid.");
            }
            $filePath = RequestValidator::optionalString($data, 'file_path', 255);
            if ($filePath !== null) {
                $filePath = $normalizeText($filePath);
                if ($filePath !== '' && !preg_match('/^[A-Za-z0-9_\-\.\/]{1,255}$/', $filePath)) {
                    RequestValidator::fail(400, "file_path format is invalid.");
                }
            }
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
            foreach (array_keys($_POST) as $postKey) {
                if (!in_array($postKey, ['patient_id', 'report_name'], true)) {
                    RequestValidator::fail(400, "Unsupported form field: $postKey.");
                }
            }
            foreach (array_keys($_FILES) as $fileKey) {
                if (!in_array($fileKey, ['report_file'], true)) {
                    RequestValidator::fail(400, "Unsupported file field: $fileKey.");
                }
            }
            $patient_id_raw = $_POST['patient_id'] ?? null;
            $report_name = $normalizeText((string)($_POST['report_name'] ?? ''));
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
            if (!$validatePattern($report_name, $reportNamePattern)) {
                RequestValidator::fail(400, "report_name format is invalid.");
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
            $mimeByExt = [
                'pdf' => ['application/pdf'],
                'png' => ['image/png'],
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'doc' => ['application/msword', 'application/octet-stream'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream']
            ];
            $tmpPath = (string)($_FILES["report_file"]["tmp_name"] ?? '');
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
            $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
            if ($finfo) finfo_close($finfo);
            $allowedMimes = $mimeByExt[$ext] ?? [];
            if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
                RequestValidator::fail(400, "Uploaded file MIME type is invalid.");
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
            foreach (array_keys($_GET) as $queryKey) {
                if (!in_array($queryKey, ['date'], true)) {
                    RequestValidator::fail(400, "Unsupported query parameter: $queryKey.");
                }
            }
            $date = $_GET['date'] ?? date('Y-m-d');
            if (!$isValidIsoDate($date)) {
                RequestValidator::fail(400, "date format is invalid.");
            }
            if ($date > date('Y-m-d')) {
                RequestValidator::fail(400, "date cannot be in the future.");
            }
            if ($date < '2000-01-01') {
                RequestValidator::fail(400, "date is too far in the past.");
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
            $assertAllowedFields($data, ['user_id', 'notes', 'shift_start', 'shift_end'], 'handover_create');
            $userId = RequestValidator::requireInt($data, 'user_id', 1);
            if (!$userExists($userId, ['receptionist', 'admin'])) {
                RequestValidator::fail(400, "user_id was not found for reception handover.");
            }
            $notes = RequestValidator::requireString($data, 'notes', 3, 2000);
            $notes = $normalizeText($notes);
            if (!$validatePattern($notes, $notesPattern)) {
                RequestValidator::fail(400, "notes format is invalid.");
            }
            $shiftStart = RequestValidator::optionalString($data, 'shift_start', 40);
            $shiftEnd = RequestValidator::optionalString($data, 'shift_end', 40);
            if ($shiftStart !== null && $shiftStart !== '' && !$isValidDateTime($shiftStart)) {
                RequestValidator::fail(400, "shift_start format is invalid.");
            }
            if ($shiftEnd !== null && $shiftEnd !== '' && !$isValidDateTime($shiftEnd)) {
                RequestValidator::fail(400, "shift_end format is invalid.");
            }
            if ($shiftStart) $shiftStart = $normalizeDateTime($shiftStart);
            if ($shiftEnd) $shiftEnd = $normalizeDateTime($shiftEnd);
            if ($shiftStart && $shiftEnd && strtotime($shiftEnd) < strtotime($shiftStart)) {
                RequestValidator::fail(400, "shift_end cannot be before shift_start.");
            }
            if ($shiftStart && $shiftEnd) {
                $duration = strtotime($shiftEnd) - strtotime($shiftStart);
                if ($duration > (36 * 60 * 60)) {
                    RequestValidator::fail(400, "shift duration cannot exceed 36 hours.");
                }
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
            $assertAllowedFields($data, ['patient_id', 'medicine_name', 'quantity', 'notes', 'requested_by'], 'refill_create');
            $patientId = RequestValidator::requireInt($data, 'patient_id', 1);
            if (!$patientExists($patientId)) {
                RequestValidator::fail(400, "patient_id was not found.");
            }
            $medicineName = RequestValidator::requireString($data, 'medicine_name', 2, 120);
            $medicineName = $normalizeText($medicineName);
            $quantity = isset($data->quantity) ? RequestValidator::requireInt($data, 'quantity', 1, 1000) : 1;
            $notes = RequestValidator::optionalString($data, 'notes', 1000);
            if (!is_null($notes)) {
                $notes = $normalizeText($notes);
                if ($notes !== '' && !$validatePattern($notes, $notesPattern)) {
                    RequestValidator::fail(400, "notes format is invalid.");
                }
            }
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
                foreach (array_keys($_GET) as $queryKey) {
                    if (!in_array($queryKey, ['patient_id'], true)) {
                        RequestValidator::fail(400, "Unsupported query parameter: $queryKey.");
                    }
                }
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

                $stmt = $db->prepare("SELECT p.*,
                                             ps.student_number,
                                             sf.ec_number,
                                             CASE
                                                 WHEN ps.student_number IS NOT NULL AND TRIM(ps.student_number) <> '' THEN ps.student_number
                                                 WHEN sf.ec_number IS NOT NULL AND TRIM(sf.ec_number) <> '' THEN sf.ec_number
                                                 ELSE p.national_id
                                             END AS primary_identifier,
                                             CASE
                                                 WHEN ps.student_number IS NOT NULL AND TRIM(ps.student_number) <> '' THEN 'Student Number'
                                                 WHEN sf.ec_number IS NOT NULL AND TRIM(sf.ec_number) <> '' THEN 'EC Number'
                                                 ELSE 'National ID'
                                             END AS identity_type
                                      FROM patients p
                                      LEFT JOIN patient_students ps ON ps.patient_id = p.id
                                      LEFT JOIN patient_staff sf ON sf.patient_id = p.id
                                      WHERE p.id = ?
                                      LIMIT 1");
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

                $stmt = $db->prepare("SELECT pr.*, COALESCE(m.name, pr.medication_name) as medicine_name
                                      FROM prescriptions pr
                                      LEFT JOIN medicines m ON pr.medicine_id = m.id
                                      WHERE pr.patient_id = ?
                                      ORDER BY pr.created_at DESC");
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
