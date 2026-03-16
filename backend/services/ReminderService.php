<?php
require_once __DIR__ . '/NotificationService.php';

class ReminderService {
    private $db;
    private $notifier;
    private $hospitalName;

    public function __construct($db) {
        $this->db = $db;
        $this->notifier = new NotificationService();
        $this->hospitalName = $this->loadHospitalName();
    }

    public function dispatchPendingTaskReminders(array $options = []): array {
        $dryRun = $this->toBool($options['dry_run'] ?? false);
        $force = $this->toBool($options['force'] ?? false);
        $includeInternal = $this->toBool($options['include_internal'] ?? true);
        $targetUserIds = $this->normalizeIntList($options['target_user_ids'] ?? []);
        $targetEmails = $this->normalizeEmailList($options['target_emails'] ?? []);

        $users = $this->fetchRecipients($targetUserIds, $targetEmails);
        $results = [];
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $userId = (int)($user['id'] ?? 0);
            $email = (string)($user['email'] ?? '');
            $role = (string)($user['role'] ?? '');
            $fullName = (string)($user['full_name'] ?? 'Staff Member');

            $items = $this->buildRoleReminderItems($userId, $role);
            $totalPending = 0;
            foreach ($items as $item) {
                $totalPending += (int)($item['count'] ?? 0);
            }

            if ($totalPending <= 0 && !$force) {
                $skipped += 1;
                $results[] = [
                    'user_id' => $userId,
                    'role' => $role,
                    'email' => $email,
                    'pending_count' => 0,
                    'status' => 'skipped',
                    'reason' => 'No pending or standby items.'
                ];
                continue;
            }

            $subject = $totalPending > 0
                ? sprintf('%s HMS Reminder: %d pending item%s', $this->hospitalName, $totalPending, $totalPending === 1 ? '' : 's')
                : sprintf('%s HMS Reminder: All clear', $this->hospitalName);

            $body = $this->buildEmailBody($fullName, $role, $items, $totalPending);

            $ok = true;
            $error = null;

            if (!$dryRun) {
                try {
                    $ok = (bool)$this->notifier->sendEmail($email, $subject, $body);
                    if (!$ok) {
                        $error = 'mail() returned false.';
                    }
                } catch (Throwable $e) {
                    $ok = false;
                    $error = $e->getMessage();
                }

                if ($ok && $includeInternal && $totalPending > 0) {
                    $this->safeCreateInAppNotification($userId, $items, $totalPending);
                }
            }

            if ($ok) {
                $sent += 1;
            } else {
                $failed += 1;
            }

            $results[] = [
                'user_id' => $userId,
                'role' => $role,
                'email' => $email,
                'pending_count' => $totalPending,
                'status' => $ok ? ($dryRun ? 'dry_run' : 'sent') : 'failed',
                'error' => $error
            ];
        }

        return [
            'generated_at' => date('c'),
            'dry_run' => $dryRun,
            'force' => $force,
            'include_internal' => $includeInternal,
            'target_user_ids' => $targetUserIds,
            'target_emails' => $targetEmails,
            'total_recipients' => count($users),
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'results' => $results
        ];
    }

    private function fetchRecipients(array $targetUserIds = [], array $targetEmails = []): array {
        try {
            $stmt = $this->db->query("SELECT id, full_name, email, role
                                      FROM users
                                      WHERE email IS NOT NULL
                                        AND TRIM(email) <> ''
                                      ORDER BY id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }

        $users = [];
        foreach ($rows as $row) {
            $email = strtolower(trim((string)($row['email'] ?? '')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $userId = (int)($row['id'] ?? 0);
            if (!empty($targetUserIds) && !in_array($userId, $targetUserIds, true)) {
                continue;
            }
            if (!empty($targetEmails) && !in_array($email, $targetEmails, true)) {
                continue;
            }
            $row['email'] = $email;
            $users[] = $row;
        }
        return $users;
    }

    private function buildRoleReminderItems(int $userId, string $roleRaw): array {
        $role = $this->normalizeRole($roleRaw);
        $items = [];

        if ($role === 'receptionist') {
            $count = $this->countQuery("SELECT COUNT(*) FROM appointments
                                        WHERE LOWER(TRIM(status)) IN ('scheduled', 'confirmed')
                                          AND scheduled_at <= NOW()");
            $this->addItem($items, 'Appointments awaiting check-in', $count);

            $count = $this->countQuery("SELECT COUNT(*) FROM patient_queue
                                        WHERE LOWER(TRIM(status)) IN ('waiting')");
            $this->addItem($items, 'Queue patients waiting at reception', $count);
            return $items;
        }

        if ($role === 'doctor' || $role === 'nurse_in_charge') {
            $count = $this->countQuery("SELECT COUNT(*) FROM patient_queue
                                        WHERE doctor_assigned = ?
                                          AND LOWER(TRIM(status)) IN ('with doctor', 'waiting', 'urgent care', 'in triage', 'admission pending')", [$userId]);
            if ($count === 0) {
                // Fallback for setups where doctor assignment is not populated.
                $count = $this->countQuery("SELECT COUNT(*) FROM patient_queue
                                            WHERE LOWER(TRIM(status)) IN ('with doctor', 'urgent care')");
            }
            $this->addItem($items, 'Patients waiting for consultation', $count);

            $count = $this->countQuery("SELECT COUNT(*) FROM appointments
                                        WHERE doctor_id = ?
                                          AND LOWER(TRIM(status)) IN ('scheduled', 'confirmed', 'checked_in')
                                          AND scheduled_at <= NOW()", [$userId]);
            $this->addItem($items, 'Assigned appointments pending action', $count);
            return $items;
        }

        if ($role === 'nurse_aid') {
            $count = $this->countQuery("SELECT COUNT(*) FROM patient_queue
                                        WHERE LOWER(TRIM(status)) IN ('waiting', 'urgent care', 'in triage')");
            $this->addItem($items, 'Patients waiting for triage', $count);
            return $items;
        }

        if ($role === 'nurse') {
            $count = $this->countQuery("SELECT COUNT(*) FROM patient_queue
                                        WHERE LOWER(TRIM(status)) IN ('urgent care', 'admission pending', 'waiting pharmacy', 'ready for admission')");
            $this->addItem($items, 'Ward/admission patients on standby', $count);

            $count = $this->countQuery("SELECT COUNT(*) FROM nurse_tasks
                                        WHERE (assigned_to IS NULL OR assigned_to = ?)
                                          AND LOWER(TRIM(status)) IN ('open', 'in_progress', 'pending')", [$userId]);
            $this->addItem($items, 'Nursing tasks still open', $count);
            return $items;
        }

        if ($role === 'pharmacist' || $role === 'senior_pharmacist') {
            $count = $this->countQuery("SELECT COUNT(*) FROM prescriptions
                                        WHERE LOWER(TRIM(status)) IN ('pending', 'external')");
            $this->addItem($items, 'Pending prescriptions to dispense', $count);

            $count = $this->countQuery("SELECT COUNT(*) FROM controlled_requests
                                        WHERE LOWER(TRIM(status)) = 'pending'");
            $this->addItem($items, 'Controlled requests awaiting approval', $count);
            return $items;
        }

        if ($role === 'it_support') {
            $count = $this->countQuery("SELECT COUNT(*) FROM it_tickets
                                        WHERE LOWER(TRIM(status)) IN ('open', 'in_progress', 'pending')
                                          AND (assigned_to IS NULL OR assigned_to = ?)", [$userId]);
            $this->addItem($items, 'IT tickets needing action', $count);
            return $items;
        }

        if ($role === 'admin') {
            $count = $this->countQuery("SELECT COUNT(*) FROM patient_queue
                                        WHERE LOWER(TRIM(status)) NOT IN ('completed', 'cancelled', 'discharged')");
            $this->addItem($items, 'Active patient queue items', $count);

            $count = $this->countQuery("SELECT COUNT(*) FROM prescriptions
                                        WHERE LOWER(TRIM(status)) IN ('pending', 'external')");
            $this->addItem($items, 'Pending pharmacy prescriptions', $count);

            $count = $this->countQuery("SELECT COUNT(*) FROM it_tickets
                                        WHERE LOWER(TRIM(status)) IN ('open', 'in_progress', 'pending')");
            $this->addItem($items, 'Open IT tickets', $count);
            return $items;
        }

        // Generic fallback for any future roles.
        $count = $this->countQuery("SELECT COUNT(*) FROM notifications
                                    WHERE user_id = ? AND is_read = 0", [$userId]);
        $this->addItem($items, 'Unread system notifications', $count);
        return $items;
    }

    private function addItem(array &$items, string $label, int $count): void {
        if ($count <= 0) return;
        $items[] = [
            'label' => $label,
            'count' => $count
        ];
    }

    private function countQuery(string $sql, array $params = []): int {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    private function safeCreateInAppNotification(int $userId, array $items, int $totalPending): void {
        if ($userId <= 0 || empty($items)) return;
        $top = [];
        foreach (array_slice($items, 0, 3) as $item) {
            $top[] = sprintf('%s: %d', $item['label'], (int)$item['count']);
        }
        $message = sprintf('Pending/standby work (%d): %s', $totalPending, implode('; ', $top));
        try {
            $this->notifier->createSystemNotification($userId, $message, 'warning');
        } catch (Exception $e) {
            // Ignore in-app notification failures.
        }
    }

    private function buildEmailBody(string $fullName, string $role, array $items, int $totalPending): string {
        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeRole = htmlspecialchars(str_replace('_', ' ', $this->normalizeRole($role)), ENT_QUOTES, 'UTF-8');
        $safeHospital = htmlspecialchars($this->hospitalName, ENT_QUOTES, 'UTF-8');
        $generatedAt = htmlspecialchars(date('Y-m-d H:i'), ENT_QUOTES, 'UTF-8');

        if (empty($items)) {
            return "<div style=\"font-family:Arial,Helvetica,sans-serif;line-height:1.5\">"
                . "<h3 style=\"margin:0 0 12px\">{$safeHospital} HMS Reminder</h3>"
                . "<p>Hello {$safeName},</p>"
                . "<p>No pending or standby tasks were detected for your {$safeRole} role at {$generatedAt}.</p>"
                . "<p style=\"color:#64748b\">This is an automated reminder.</p>"
                . "</div>";
        }

        $rows = '';
        foreach ($items as $item) {
            $label = htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8');
            $count = (int)($item['count'] ?? 0);
            $rows .= "<li>{$label}: <strong>{$count}</strong></li>";
        }

        return "<div style=\"font-family:Arial,Helvetica,sans-serif;line-height:1.5\">"
            . "<h3 style=\"margin:0 0 12px\">{$safeHospital} HMS Reminder</h3>"
            . "<p>Hello {$safeName},</p>"
            . "<p>You still have <strong>{$totalPending}</strong> pending/standby item(s) for your {$safeRole} role:</p>"
            . "<ul>{$rows}</ul>"
            . "<p>Please update the relevant dashboard items as soon as possible.</p>"
            . "<p style=\"color:#64748b\">Generated at {$generatedAt}. This is an automated reminder.</p>"
            . "</div>";
    }

    private function loadHospitalName(): string {
        try {
            $stmt = $this->db->query("SELECT hospital_name FROM system_settings WHERE id = 1 LIMIT 1");
            $name = trim((string)$stmt->fetchColumn());
            if ($name !== '') return $name;
        } catch (Exception $e) {
            // Ignore settings lookup failures.
        }
        return 'Hospital';
    }

    private function normalizeRole(string $role): string {
        return str_replace([' ', '-'], '_', strtolower(trim($role)));
    }

    private function toBool($value): bool {
        if (is_bool($value)) return $value;
        if ($value === null) return false;
        if (is_int($value) || is_float($value)) return ((int)$value) === 1;
        $parsed = filter_var((string)$value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === true;
    }

    private function normalizeIntList($raw): array {
        $values = [];
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }
        foreach ($raw as $item) {
            if (!is_numeric($item)) continue;
            $id = (int)$item;
            if ($id > 0) $values[] = $id;
        }
        $values = array_values(array_unique($values));
        return $values;
    }

    private function normalizeEmailList($raw): array {
        $values = [];
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }
        foreach ($raw as $item) {
            $email = strtolower(trim((string)$item));
            if ($email === '') continue;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            $values[] = $email;
        }
        $values = array_values(array_unique($values));
        return $values;
    }
}
?>
