<?php
// FILE: backend/utils/ActivityLogger.php

class ActivityLogger {
    private static $requestId = null;

    public static function ensureTables($db) {
        $baseSql = "CREATE TABLE IF NOT EXISTS %s (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(150) NOT NULL,
            action VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Success',
            event_type VARCHAR(50) NULL,
            entity_type VARCHAR(100) NULL,
            entity_id VARCHAR(64) NULL,
            actor_id VARCHAR(64) NULL,
            actor_role VARCHAR(50) NULL,
            source VARCHAR(150) NULL,
            request_id VARCHAR(64) NULL,
            session_id VARCHAR(128) NULL,
            severity VARCHAR(20) NULL,
            duration_ms INT NULL,
            status_code INT NULL,
            error_code VARCHAR(50) NULL,
            error_message TEXT NULL,
            user_agent TEXT NULL,
            device_type VARCHAR(30) NULL,
            client_ip VARCHAR(45) NULL,
            hostname VARCHAR(255) NULL,
            old_values LONGTEXT NULL,
            new_values LONGTEXT NULL,
            diff_json LONGTEXT NULL,
            metadata_json LONGTEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $db->exec(sprintf($baseSql, "activity_logs"));
        $db->exec(sprintf($baseSql, "audit_logs"));

        $columns = [
            "event_type" => "VARCHAR(50) NULL",
            "entity_type" => "VARCHAR(100) NULL",
            "entity_id" => "VARCHAR(64) NULL",
            "actor_id" => "VARCHAR(64) NULL",
            "actor_role" => "VARCHAR(50) NULL",
            "source" => "VARCHAR(150) NULL",
            "request_id" => "VARCHAR(64) NULL",
            "session_id" => "VARCHAR(128) NULL",
            "severity" => "VARCHAR(20) NULL",
            "duration_ms" => "INT NULL",
            "status_code" => "INT NULL",
            "error_code" => "VARCHAR(50) NULL",
            "error_message" => "TEXT NULL",
            "user_agent" => "TEXT NULL",
            "device_type" => "VARCHAR(30) NULL",
            "client_ip" => "VARCHAR(45) NULL",
            "hostname" => "VARCHAR(255) NULL",
            "old_values" => "LONGTEXT NULL",
            "new_values" => "LONGTEXT NULL",
            "diff_json" => "LONGTEXT NULL",
            "metadata_json" => "LONGTEXT NULL"
        ];

        self::ensureColumns($db, "activity_logs", $columns);
        self::ensureColumns($db, "audit_logs", $columns);

        $indexes = [
            "idx_activity_logs_created_at" => "created_at",
            "idx_activity_logs_event_type" => "event_type",
            "idx_activity_logs_actor_id" => "actor_id",
            "idx_activity_logs_entity_type" => "entity_type",
            "idx_activity_logs_entity_id" => "entity_id",
            "idx_activity_logs_request_id" => "request_id"
        ];

        $auditIndexes = [
            "idx_audit_logs_created_at" => "created_at",
            "idx_audit_logs_event_type" => "event_type",
            "idx_audit_logs_actor_id" => "actor_id",
            "idx_audit_logs_entity_type" => "entity_type",
            "idx_audit_logs_entity_id" => "entity_id",
            "idx_audit_logs_request_id" => "request_id"
        ];

        self::ensureIndexes($db, "activity_logs", $indexes);
        self::ensureIndexes($db, "audit_logs", $auditIndexes);
    }

    public static function log($db, $username, $action, $status = 'Success', $ip = null, $context = []) {
        if (!$db) return;
        if (is_array($ip) && empty($context)) {
            $context = $ip;
            $ip = null;
        }

        self::ensureTables($db);

        $record = self::buildRecord($username, $action, $status, $ip, $context);
        $sql = "INSERT INTO activity_logs
            (username, action, ip_address, status, event_type, entity_type, entity_id, actor_id, actor_role, source, request_id, session_id, severity, duration_ms, status_code, error_code, error_message, user_agent, device_type, client_ip, hostname, old_values, new_values, diff_json, metadata_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $record['username'],
            $record['action'],
            $record['ip_address'],
            $record['status'],
            $record['event_type'],
            $record['entity_type'],
            $record['entity_id'],
            $record['actor_id'],
            $record['actor_role'],
            $record['source'],
            $record['request_id'],
            $record['session_id'],
            $record['severity'],
            $record['duration_ms'],
            $record['status_code'],
            $record['error_code'],
            $record['error_message'],
            $record['user_agent'],
            $record['device_type'],
            $record['client_ip'],
            $record['hostname'],
            $record['old_values'],
            $record['new_values'],
            $record['diff_json'],
            $record['metadata_json']
        ]);

        if ($record['event_type'] === 'audit' || !empty($context['write_audit'])) {
            $auditSql = "INSERT INTO audit_logs
                (username, action, ip_address, status, event_type, entity_type, entity_id, actor_id, actor_role, source, request_id, session_id, severity, duration_ms, status_code, error_code, error_message, user_agent, device_type, client_ip, hostname, old_values, new_values, diff_json, metadata_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $auditStmt = $db->prepare($auditSql);
            $auditStmt->execute([
                $record['username'],
                $record['action'],
                $record['ip_address'],
                $record['status'],
                $record['event_type'],
                $record['entity_type'],
                $record['entity_id'],
                $record['actor_id'],
                $record['actor_role'],
                $record['source'],
                $record['request_id'],
                $record['session_id'],
                $record['severity'],
                $record['duration_ms'],
                $record['status_code'],
                $record['error_code'],
                $record['error_message'],
                $record['user_agent'],
                $record['device_type'],
                $record['client_ip'],
                $record['hostname'],
                $record['old_values'],
                $record['new_values'],
                $record['diff_json'],
                $record['metadata_json']
            ]);
        }

        self::writeFileLog($record);
    }

    private static function ensureColumns($db, $table, $columns) {
        foreach ($columns as $name => $definition) {
            $exists = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $exists->execute([$table, $name]);
            if ((int)$exists->fetchColumn() === 0) {
                $db->exec("ALTER TABLE {$table} ADD COLUMN {$name} {$definition}");
            }
        }
    }

    private static function ensureIndexes($db, $table, $indexes) {
        foreach ($indexes as $indexName => $columnName) {
            $exists = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
            $exists->execute([$table, $indexName]);
            if ((int)$exists->fetchColumn() === 0) {
                $db->exec("CREATE INDEX {$indexName} ON {$table} ({$columnName})");
            }
        }
    }

    private static function buildRecord($username, $action, $status, $ip, $context) {
        $ipAddress = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $clientIp = $context['client_ip'] ?? $ipAddress;
        $userAgent = $context['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
        $deviceType = $context['device_type'] ?? self::detectDeviceType($userAgent);
        $source = $context['source'] ?? ($_SERVER['REQUEST_URI'] ?? null);
        $requestId = $context['request_id'] ?? self::getRequestId();
        $sessionId = $context['session_id'] ?? (function_exists('session_id') ? session_id() : null);
        $severity = $context['severity'] ?? self::statusToSeverity($status);
        $durationMs = $context['duration_ms'] ?? self::calculateDurationMs();
        $statusCode = $context['status_code'] ?? (function_exists('http_response_code') ? http_response_code() : null);

        $safeFields = isset($context['safe_fields']) && is_array($context['safe_fields']) ? $context['safe_fields'] : null;
        $oldValues = self::sanitizePayload($context['old_values'] ?? null, $safeFields);
        $newValues = self::sanitizePayload($context['new_values'] ?? null, $safeFields);
        $diffJson = self::sanitizePayload($context['diff_json'] ?? null, $safeFields);
        $metadata = self::sanitizePayload($context['metadata'] ?? null, $safeFields);

        return [
            'username' => $username,
            'action' => $action,
            'ip_address' => $ipAddress,
            'status' => $status,
            'event_type' => $context['event_type'] ?? 'activity',
            'entity_type' => $context['entity_type'] ?? null,
            'entity_id' => $context['entity_id'] ?? null,
            'actor_id' => $context['actor_id'] ?? null,
            'actor_role' => $context['actor_role'] ?? null,
            'source' => $source,
            'request_id' => $requestId,
            'session_id' => $sessionId,
            'severity' => $severity,
            'duration_ms' => $durationMs,
            'status_code' => $statusCode,
            'error_code' => $context['error_code'] ?? null,
            'error_message' => $context['error_message'] ?? null,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'client_ip' => $clientIp,
            'hostname' => $context['hostname'] ?? ($_SERVER['REMOTE_HOST'] ?? null),
            'old_values' => is_null($oldValues) ? null : json_encode($oldValues),
            'new_values' => is_null($newValues) ? null : json_encode($newValues),
            'diff_json' => is_null($diffJson) ? null : json_encode($diffJson),
            'metadata_json' => is_null($metadata) ? null : json_encode($metadata),
            'created_at' => date('c')
        ];
    }

    private static function getRequestId() {
        if (self::$requestId) return self::$requestId;
        $header = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
        if ($header) {
            self::$requestId = trim($header);
            return self::$requestId;
        }
        self::$requestId = bin2hex(random_bytes(8));
        return self::$requestId;
    }

    private static function detectDeviceType($userAgent) {
        if (!$userAgent) return null;
        $ua = strtolower($userAgent);
        if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) return 'tablet';
        if (strpos($ua, 'mobi') !== false || strpos($ua, 'android') !== false) return 'mobile';
        return 'desktop';
    }

    private static function statusToSeverity($status) {
        $s = strtolower((string)$status);
        if ($s === 'failed' || $s === 'error') return 'ERROR';
        if ($s === 'warning') return 'WARN';
        return 'INFO';
    }

    private static function calculateDurationMs() {
        if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) return null;
        $elapsed = (microtime(true) - (float)$_SERVER['REQUEST_TIME_FLOAT']) * 1000;
        return (int)max(0, round($elapsed));
    }

    private static function sanitizePayload($payload, $safeFields = null) {
        if (is_null($payload)) return null;
        if (is_string($payload)) return $payload;
        if (!is_array($payload)) return $payload;

        if ($safeFields) {
            $filtered = [];
            foreach ($safeFields as $field) {
                if (array_key_exists($field, $payload)) {
                    $filtered[$field] = $payload[$field];
                }
            }
            $payload = $filtered;
        }

        $sensitiveKeys = [
            'password',
            'password_hash',
            'token',
            'authorization',
            'jwt',
            'secret',
            'api_key',
            'access_token',
            'refresh_token',
            'ssn',
            'medical_note'
        ];

        $clean = [];
        foreach ($payload as $key => $value) {
            $keyLower = strtolower((string)$key);
            if (in_array($keyLower, $sensitiveKeys, true)) {
                $clean[$key] = '[REDACTED]';
                continue;
            }
            if (is_array($value)) {
                $clean[$key] = self::sanitizePayload($value, $safeFields);
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    private static function writeFileLog($record) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/system.jsonl';

        if (file_exists($logFile)) {
            $maxBytes = 5 * 1024 * 1024;
            $size = filesize($logFile);
            if ($size !== false && $size > $maxBytes) {
                $rotated = $logDir . '/system-' . date('Ymd-His') . '.jsonl';
                @rename($logFile, $rotated);
            }
        }

        $line = json_encode($record, JSON_UNESCAPED_SLASHES);
        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
    }
}
?>
