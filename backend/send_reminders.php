<?php
// CLI utility: send pending/standby reminder emails to staff.
// Usage:
//   php backend/send_reminders.php
//   php backend/send_reminders.php --dry-run
//   php backend/send_reminders.php --force
//   php backend/send_reminders.php --no-internal
//   php backend/send_reminders.php --emails="a@gmail.com,b@gmail.com"
//   php backend/send_reminders.php --user-ids="2,5"

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/DbSchema.php';
require_once __DIR__ . '/services/ReminderService.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(["message" => "This script must be run from CLI."]);
    exit(1);
}

$opts = getopt('', ['dry-run', 'force', 'no-internal', 'emails:', 'user-ids:']);
$dryRun = isset($opts['dry-run']);
$force = isset($opts['force']);
$includeInternal = !isset($opts['no-internal']);
$targetEmails = [];
$targetUserIds = [];
if (!empty($opts['emails'])) {
    $targetEmails = array_filter(array_map('trim', explode(',', (string)$opts['emails'])));
}
if (!empty($opts['user-ids'])) {
    $targetUserIds = array_filter(array_map('trim', explode(',', (string)$opts['user-ids'])));
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed.');
    }

    DbSchema::ensureVisitEncounters($db);
    DbSchema::ensureDoctorModules($db);
    DbSchema::ensureNurseModules($db);
    DbSchema::ensurePrescriptionWorkflow($db);
    DbSchema::ensureITModules($db);

    $service = new ReminderService($db);
    $result = $service->dispatchPendingTaskReminders([
        'dry_run' => $dryRun,
        'force' => $force,
        'include_internal' => $includeInternal,
        'target_emails' => $targetEmails,
        'target_user_ids' => $targetUserIds
    ]);

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    $payload = [
        'generated_at' => date('c'),
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}
?>
