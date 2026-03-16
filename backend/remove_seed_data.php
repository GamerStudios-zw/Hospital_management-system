<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=UTF-8');

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_PRETTY_PRINT);
    exit;
}

function tableExists(PDO $db, string $table): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $db, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '::' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    if (!tableExists($db, $table)) {
        $cache[$key] = false;
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    $cache[$key] = (int)$stmt->fetchColumn() > 0;
    return $cache[$key];
}

function deleteByIds(PDO $db, string $table, string $column, array $ids): int
{
    if (empty($ids) || !tableExists($db, $table) || !columnExists($db, $table, $column)) {
        return 0;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("DELETE FROM `{$table}` WHERE `{$column}` IN ({$placeholders})");
    $stmt->execute($ids);
    return $stmt->rowCount();
}

function nullifyByIds(PDO $db, string $table, string $column, array $ids): int
{
    if (empty($ids) || !tableExists($db, $table) || !columnExists($db, $table, $column)) {
        return 0;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("UPDATE `{$table}` SET `{$column}` = NULL WHERE `{$column}` IN ({$placeholders})");
    $stmt->execute($ids);
    return $stmt->rowCount();
}

function deleteLike(PDO $db, string $table, string $column, string $pattern): int
{
    if (!tableExists($db, $table) || !columnExists($db, $table, $column)) {
        return 0;
    }
    $stmt = $db->prepare("DELETE FROM `{$table}` WHERE `{$column}` LIKE ?");
    $stmt->execute([$pattern]);
    return $stmt->rowCount();
}

function addSummary(array &$summary, string $key, int $count): void
{
    if ($count <= 0) return;
    if (!isset($summary[$key])) $summary[$key] = 0;
    $summary[$key] += $count;
}

try {
    $db = (new Database())->getConnection();
    if (!$db) {
        respond(['ok' => false, 'message' => 'Database connection failed.'], 500);
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

    $summary = [];

    $demoPatientIds = [];
    if (tableExists($db, 'patients') && columnExists($db, 'patients', 'id') && columnExists($db, 'patients', 'national_id')) {
        $stmt = $db->query("SELECT id FROM patients WHERE national_id LIKE 'DEMO-%'");
        $demoPatientIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    $demoUserIds = [];
    if (tableExists($db, 'users') && columnExists($db, 'users', 'id') && columnExists($db, 'users', 'username')) {
        $stmt = $db->query("SELECT id FROM users WHERE username LIKE 'demo_%'");
        $demoUserIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    if (!empty($demoPatientIds)) {
        if (tableExists($db, 'controlled_requests') && tableExists($db, 'prescriptions') && columnExists($db, 'controlled_requests', 'prescription_id') && columnExists($db, 'prescriptions', 'patient_id')) {
            $placeholders = implode(',', array_fill(0, count($demoPatientIds), '?'));
            $stmt = $db->prepare("DELETE cr FROM controlled_requests cr
                                  INNER JOIN prescriptions pr ON pr.id = cr.prescription_id
                                  WHERE pr.patient_id IN ({$placeholders})");
            $stmt->execute($demoPatientIds);
            addSummary($summary, 'controlled_requests', $stmt->rowCount());
        }

        foreach ([
            ['insurance_claims', 'patient_id'],
            ['counselling_sessions', 'patient_id'],
            ['referrals', 'patient_id'],
            ['medical_reports', 'patient_id'],
            ['discharge_summaries', 'patient_id'],
            ['doctor_escalations', 'patient_id'],
            ['nurse_escalations', 'patient_id'],
            ['doctor_tasks', 'patient_id'],
            ['nurse_tasks', 'patient_id'],
            ['refill_requests', 'patient_id'],
            ['pharmacy_requests', 'patient_id'],
            ['appointments', 'patient_id'],
            ['patient_vitals', 'patient_id'],
            ['prescriptions', 'patient_id'],
            ['patient_queue', 'patient_id'],
            ['visits', 'patient_id']
        ] as $target) {
            addSummary($summary, $target[0], deleteByIds($db, $target[0], $target[1], $demoPatientIds));
        }

        if (tableExists($db, 'beds') && columnExists($db, 'beds', 'current_patient_id')) {
            $placeholders = implode(',', array_fill(0, count($demoPatientIds), '?'));
            if (columnExists($db, 'beds', 'status')) {
                $stmt = $db->prepare("UPDATE beds SET current_patient_id = NULL, status = 'Available' WHERE current_patient_id IN ({$placeholders})");
            } else {
                $stmt = $db->prepare("UPDATE beds SET current_patient_id = NULL WHERE current_patient_id IN ({$placeholders})");
            }
            $stmt->execute($demoPatientIds);
            addSummary($summary, 'beds_reset', $stmt->rowCount());
        }

        addSummary($summary, 'patients', deleteByIds($db, 'patients', 'id', $demoPatientIds));
    }

    foreach ([
        ['it_tickets', 'title', '[DEMO]%'],
        ['reception_handover', 'notes', '[DEMO]%'],
        ['nurse_handover', 'notes', '[DEMO]%'],
        ['doctor_handover', 'notes', '[DEMO]%'],
        ['pharmacy_requests', 'notes', '[DEMO]%'],
        ['refill_requests', 'notes', '[DEMO]%'],
        ['controlled_requests', 'notes', '[DEMO]%'],
        ['goods_receipts', 'notes', '[DEMO]%'],
        ['insurance_claims', 'notes', '[DEMO]%'],
        ['nurse_tasks', 'task', '[DEMO]%'],
        ['doctor_tasks', 'task', '[DEMO]%'],
        ['nurse_escalations', 'reason', '[DEMO]%'],
        ['doctor_escalations', 'reason', '[DEMO]%'],
        ['purchase_invoices', 'invoice_number', 'DEMO-INV-%'],
        ['quarantine_batches', 'batch_number', 'Q-DEMO-%'],
        ['suppliers', 'email', '%@demo.local']
    ] as $markerDelete) {
        addSummary($summary, $markerDelete[0], deleteLike($db, $markerDelete[0], $markerDelete[1], $markerDelete[2]));
    }

    foreach (['system_logs', 'activity_logs', 'audit_logs'] as $logTable) {
        addSummary($summary, $logTable, deleteLike($db, $logTable, 'username', 'demo_%'));
        addSummary($summary, $logTable, deleteLike($db, $logTable, 'error_message', '[DEMO]%'));
    }

    if (!empty($demoUserIds)) {
        foreach ([
            ['staff_shifts', 'user_id'],
            ['reception_handover', 'user_id'],
            ['nurse_handover', 'user_id'],
            ['doctor_handover', 'user_id']
        ] as $target) {
            addSummary($summary, $target[0], deleteByIds($db, $target[0], $target[1], $demoUserIds));
        }

        foreach ([
            ['patient_queue', 'doctor_assigned'],
            ['appointments', 'doctor_id'],
            ['nurse_tasks', 'assigned_to'],
            ['doctor_tasks', 'assigned_to'],
            ['pharmacy_requests', 'requested_by'],
            ['refill_requests', 'requested_by'],
            ['controlled_requests', 'requested_by'],
            ['controlled_requests', 'approved_by'],
            ['stock_adjustments', 'adjusted_by'],
            ['insurance_claims', 'submitted_by'],
            ['it_tickets', 'created_by'],
            ['it_tickets', 'assigned_to'],
            ['goods_receipts', 'received_by'],
            ['purchase_orders', 'created_by'],
            ['visits', 'doctor_id']
        ] as $target) {
            addSummary($summary, $target[0] . '_nullified', nullifyByIds($db, $target[0], $target[1], $demoUserIds));
        }

        $deletedUsers = 0;
        $deactivatedUsers = 0;
        try {
            $deletedUsers = deleteByIds($db, 'users', 'id', $demoUserIds);
        } catch (Throwable $e) {
            if (tableExists($db, 'users') && columnExists($db, 'users', 'is_active')) {
                $placeholders = implode(',', array_fill(0, count($demoUserIds), '?'));
                $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id IN ({$placeholders})");
                $stmt->execute($demoUserIds);
                $deactivatedUsers = $stmt->rowCount();
            } else {
                throw $e;
            }
        }
        addSummary($summary, 'users_deleted', $deletedUsers);
        addSummary($summary, 'users_deactivated', $deactivatedUsers);
    }

    $db->commit();

    respond([
        'ok' => true,
        'message' => 'Seed/demo data cleanup completed.',
        'demo_patient_ids_found' => count($demoPatientIds),
        'demo_user_ids_found' => count($demoUserIds),
        'changes' => $summary
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    respond([
        'ok' => false,
        'message' => 'Failed to remove seed data.',
        'error' => $e->getMessage()
    ], 500);
}

