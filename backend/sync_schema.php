<?php
// FILE: backend/sync_schema.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/DbSchema.php';
require_once __DIR__ . '/utils/ActivityLogger.php';

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: application/json; charset=UTF-8');
}

try {
    $db = (new Database())->getConnection();
    if (!$db) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'DB connection failed']);
        exit;
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $steps = [
        'ensure_staff_shifts' => function () use ($db) { DbSchema::ensureStaffShifts($db); },
        'ensure_appointments' => function () use ($db) { DbSchema::ensureAppointments($db); },
        'ensure_reception_handover' => function () use ($db) { DbSchema::ensureReceptionHandover($db); },
        'ensure_visit_encounters' => function () use ($db) { DbSchema::ensureVisitEncounters($db); },
        'ensure_counselling_workflow' => function () use ($db) { DbSchema::ensureCounsellingWorkflow($db); },
        'ensure_prescription_workflow' => function () use ($db) { DbSchema::ensurePrescriptionWorkflow($db); },
        'ensure_reception_identity_tables' => function () use ($db) { DbSchema::ensureReceptionIdentityTables($db); },
        'ensure_reception_medical_aid_columns' => function () use ($db) { DbSchema::ensureReceptionPatientMedicalAidColumns($db); },
        'ensure_nurse_modules' => function () use ($db) { DbSchema::ensureNurseModules($db); },
        'ensure_bed_management' => function () use ($db) { DbSchema::ensureBedManagement($db); },
        'ensure_clinical_operations' => function () use ($db) { DbSchema::ensureClinicalOperationsModules($db); },
        'ensure_doctor_modules' => function () use ($db) { DbSchema::ensureDoctorModules($db); },
        'ensure_referral_registry' => function () use ($db) { DbSchema::ensureReferralRegistry($db); },
        'ensure_pharmacy_modules' => function () use ($db) { DbSchema::ensurePharmacyModules($db); },
        'ensure_it_modules' => function () use ($db) { DbSchema::ensureITModules($db); },
        'ensure_user_role_nurse_in_charge' => function () use ($db) { DbSchema::ensureUserRole($db, 'nurse_in_charge'); },
        'ensure_user_role_nurse_aid' => function () use ($db) { DbSchema::ensureUserRole($db, 'nurse_aid'); },
        'ensure_user_role_senior_pharmacist' => function () use ($db) { DbSchema::ensureUserRole($db, 'senior_pharmacist'); },
        'ensure_user_role_it_support' => function () use ($db) { DbSchema::ensureUserRole($db, 'it_support'); },
        'migrate_doctor_to_nurse_in_charge' => function () use ($db) { DbSchema::ensureNurseInChargeRole($db, true); },
        'ensure_activity_logs' => function () use ($db) { ActivityLogger::ensureTables($db); }
    ];

    $result = [];
    foreach ($steps as $name => $fn) {
        $fn();
        $result[] = ['step' => $name, 'status' => 'ok'];
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Schema synchronized for current HMS modules.',
        'steps' => $result
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Schema sync failed.',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
