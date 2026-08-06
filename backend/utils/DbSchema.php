<?php
// FILE: backend/utils/DbSchema.php

class DbSchema {
    private static function tableExists($db, $table) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        return ((int)$stmt->fetchColumn() > 0);
    }

    private static function columnExists($db, $table, $column) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return ((int)$stmt->fetchColumn() > 0);
    }

    private static function indexExists($db, $table, $indexName) {
        $stmt = $db->prepare("SELECT COUNT(*)
                              FROM INFORMATION_SCHEMA.STATISTICS
                              WHERE TABLE_SCHEMA = DATABASE()
                                AND TABLE_NAME = ?
                                AND INDEX_NAME = ?");
        $stmt->execute([$table, $indexName]);
        return ((int)$stmt->fetchColumn() > 0);
    }

    private static function ensureColumn($db, $table, $column, $definition) {
        if (!self::columnExists($db, $table, $column)) {
            $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    public static function ensureCoreClinicalSchema($db) {
        if (!$db) return;

        // Patients table used across reception/doctor/nurse/pharmacy flows.
        $db->exec("CREATE TABLE IF NOT EXISTS patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NULL,
            name VARCHAR(150) NULL,
            national_id VARCHAR(50) NULL,
            dob DATE NULL,
            gender VARCHAR(20) NULL,
            phone VARCHAR(30) NULL,
            contact VARCHAR(50) NULL,
            address VARCHAR(255) NULL,
            has_medical_aid TINYINT(1) NOT NULL DEFAULT 0,
            medical_aid_provider VARCHAR(120) NULL,
            medical_aid_number VARCHAR(80) NULL,
            kin_name VARCHAR(120) NULL,
            kin_relation VARCHAR(80) NULL,
            kin_phone VARCHAR(30) NULL,
            allergies TEXT NULL,
            fingerprint_hash TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_patients_full_name (full_name),
            INDEX idx_patients_national_id (national_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn($db, 'patients', 'full_name', 'VARCHAR(150) NULL');
        self::ensureColumn($db, 'patients', 'name', 'VARCHAR(150) NULL');
        self::ensureColumn($db, 'patients', 'national_id', 'VARCHAR(50) NULL');
        self::ensureColumn($db, 'patients', 'phone', 'VARCHAR(30) NULL');
        self::ensureColumn($db, 'patients', 'contact', 'VARCHAR(50) NULL');
        self::ensureColumn($db, 'patients', 'address', 'VARCHAR(255) NULL');
        self::ensureColumn($db, 'patients', 'has_medical_aid', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn($db, 'patients', 'medical_aid_provider', 'VARCHAR(120) NULL');
        self::ensureColumn($db, 'patients', 'medical_aid_number', 'VARCHAR(80) NULL');
        self::ensureColumn($db, 'patients', 'kin_name', 'VARCHAR(120) NULL');
        self::ensureColumn($db, 'patients', 'kin_relation', 'VARCHAR(80) NULL');
        self::ensureColumn($db, 'patients', 'kin_phone', 'VARCHAR(30) NULL');
        self::ensureColumn($db, 'patients', 'allergies', 'TEXT NULL');
        self::ensureColumn($db, 'patients', 'fingerprint_hash', 'TEXT NULL');

        // Backfill between legacy and current columns.
        $db->exec("UPDATE patients SET full_name = COALESCE(NULLIF(full_name, ''), name) WHERE (full_name IS NULL OR full_name = '') AND name IS NOT NULL");
        $db->exec("UPDATE patients SET name = COALESCE(NULLIF(name, ''), full_name) WHERE (name IS NULL OR name = '') AND full_name IS NOT NULL");
        $db->exec("UPDATE patients SET phone = COALESCE(NULLIF(phone, ''), contact) WHERE (phone IS NULL OR phone = '') AND contact IS NOT NULL");
        $db->exec("UPDATE patients SET contact = COALESCE(NULLIF(contact, ''), phone) WHERE (contact IS NULL OR contact = '') AND phone IS NOT NULL");

        // Current queue table expected by operational routes.
        $db->exec("CREATE TABLE IF NOT EXISTS patient_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_assigned INT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Waiting',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_queue_patient (patient_id),
            INDEX idx_queue_status (status),
            INDEX idx_queue_doctor (doctor_assigned),
            INDEX idx_queue_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn($db, 'patient_queue', 'patient_id', 'INT NULL');
        self::ensureColumn($db, 'patient_queue', 'doctor_assigned', 'INT NULL');
        self::ensureColumn($db, 'patient_queue', 'status', "VARCHAR(50) NOT NULL DEFAULT 'Waiting'");
        self::ensureColumn($db, 'patient_queue', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        self::ensureColumn($db, 'patient_queue', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        // Current vitals table expected by nurse/nurse-aid/doctor/reception routes.
        $db->exec("CREATE TABLE IF NOT EXISTS patient_vitals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            queue_id INT NULL,
            temperature VARCHAR(10) NULL,
            pulse VARCHAR(10) NULL,
            bp VARCHAR(20) NULL,
            weight VARCHAR(10) NULL,
            spo2 VARCHAR(10) NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_patient_vitals_patient (patient_id),
            INDEX idx_patient_vitals_queue (queue_id),
            INDEX idx_patient_vitals_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn($db, 'patient_vitals', 'patient_id', 'INT NULL');
        self::ensureColumn($db, 'patient_vitals', 'queue_id', 'INT NULL');
        self::ensureColumn($db, 'patient_vitals', 'temperature', 'VARCHAR(10) NULL');
        self::ensureColumn($db, 'patient_vitals', 'pulse', 'VARCHAR(10) NULL');
        self::ensureColumn($db, 'patient_vitals', 'bp', 'VARCHAR(20) NULL');
        self::ensureColumn($db, 'patient_vitals', 'weight', 'VARCHAR(10) NULL');
        self::ensureColumn($db, 'patient_vitals', 'spo2', 'VARCHAR(10) NULL');
        self::ensureColumn($db, 'patient_vitals', 'notes', 'TEXT NULL');
        self::ensureColumn($db, 'patient_vitals', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // Current prescriptions contract used by doctor + pharmacy routes.
        $db->exec("CREATE TABLE IF NOT EXISTS prescriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NULL,
            medicine_id INT NULL,
            quantity INT NOT NULL DEFAULT 1,
            dosage VARCHAR(100) NULL,
            notes TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            visit_id INT NULL,
            medication_name VARCHAR(100) NULL,
            frequency VARCHAR(50) NULL,
            duration VARCHAR(50) NULL,
            INDEX idx_prescriptions_patient (patient_id),
            INDEX idx_prescriptions_status (status),
            INDEX idx_prescriptions_medicine (medicine_id),
            INDEX idx_prescriptions_visit (visit_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn($db, 'prescriptions', 'patient_id', 'INT NULL');
        self::ensureColumn($db, 'prescriptions', 'medicine_id', 'INT NULL');
        self::ensureColumn($db, 'prescriptions', 'quantity', 'INT NOT NULL DEFAULT 1');
        self::ensureColumn($db, 'prescriptions', 'notes', 'TEXT NULL');
        self::ensureColumn($db, 'prescriptions', 'visit_id', 'INT NULL');
        self::ensureColumn($db, 'prescriptions', 'medication_name', 'VARCHAR(100) NULL');
        self::ensureColumn($db, 'prescriptions', 'frequency', 'VARCHAR(50) NULL');
        self::ensureColumn($db, 'prescriptions', 'duration', 'VARCHAR(50) NULL');
        self::ensureColumn($db, 'prescriptions', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        // Normalize legacy visit FK linkage so modern queue-based routes can insert prescriptions
        // without requiring an old visits row.
        try {
            $fkStmt = $db->prepare("SELECT CONSTRAINT_NAME
                                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                    WHERE TABLE_SCHEMA = DATABASE()
                                      AND TABLE_NAME = 'prescriptions'
                                      AND COLUMN_NAME = 'visit_id'
                                      AND REFERENCED_TABLE_NAME = 'visits'");
            $fkStmt->execute();
            $fkNames = $fkStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($fkNames as $fkName) {
                $safeFk = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$fkName);
                if ($safeFk !== '') {
                    $db->exec("ALTER TABLE prescriptions DROP FOREIGN KEY `{$safeFk}`");
                }
            }
        } catch (Exception $e) {
            // Ignore if FK already absent or table metadata is unavailable.
        }
        try {
            $db->exec("ALTER TABLE prescriptions MODIFY visit_id INT NULL");
        } catch (Exception $e) {
            // Ignore when schema already matches.
        }
        try {
            $db->exec("UPDATE prescriptions SET visit_id = NULL WHERE visit_id = 0");
        } catch (Exception $e) {
            // Ignore if no legacy zero values.
        }
        try {
            $db->exec("ALTER TABLE prescriptions MODIFY medication_name VARCHAR(100) NULL");
        } catch (Exception $e) {
            // Ignore when schema already matches.
        }
        try {
            $db->exec("ALTER TABLE prescriptions MODIFY dosage VARCHAR(100) NULL");
        } catch (Exception $e) {
            // Ignore when schema already matches.
        }
        try {
            $db->exec("ALTER TABLE prescriptions MODIFY status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
        } catch (Exception $e) {
            // Leave existing status definition if alteration is blocked.
        }

        // Migrate legacy visits -> patient_queue when present.
        if (self::tableExists($db, 'visits')) {
            $db->exec("INSERT INTO patient_queue (patient_id, doctor_assigned, status, created_at, updated_at)
                       SELECT v.patient_id,
                              v.doctor_id,
                              CASE
                                  WHEN LOWER(v.status) = 'waiting' THEN 'Waiting'
                                  WHEN LOWER(v.status) = 'triaged' THEN 'In Triage'
                                  WHEN LOWER(v.status) = 'in_consultation' THEN 'With Doctor'
                                  WHEN LOWER(v.status) = 'pharmacy' THEN 'Waiting Pharmacy'
                                  WHEN LOWER(v.status) = 'completed' THEN 'Completed'
                                  WHEN LOWER(v.status) = 'cancelled' THEN 'Cancelled'
                                  ELSE 'Waiting'
                              END,
                              v.created_at,
                              COALESCE(v.updated_at, v.created_at)
                       FROM visits v
                       WHERE NOT EXISTS (
                           SELECT 1
                           FROM patient_queue q
                           WHERE q.patient_id = v.patient_id
                             AND q.created_at = v.created_at
                       )");
        }

        // Migrate legacy vital_signs -> patient_vitals when present.
        if (self::tableExists($db, 'vital_signs')) {
            $db->exec("INSERT INTO patient_vitals (patient_id, queue_id, temperature, pulse, bp, weight, spo2, notes, created_at)
                       SELECT vs.patient_id,
                              (
                                  SELECT q.id
                                  FROM patient_queue q
                                  WHERE q.patient_id = vs.patient_id
                                  ORDER BY ABS(TIMESTAMPDIFF(SECOND, q.created_at, vs.created_at)) ASC, q.id DESC
                                  LIMIT 1
                              ) AS queue_id,
                              vs.temperature,
                              vs.pulse_rate,
                              vs.blood_pressure,
                              vs.weight,
                              NULL,
                              NULL,
                              vs.created_at
                       FROM vital_signs vs
                       WHERE NOT EXISTS (
                           SELECT 1
                           FROM patient_vitals pv
                           WHERE pv.patient_id = vs.patient_id
                             AND pv.created_at = vs.created_at
                       )");
        }

        // Backfill prescription patient_id from legacy visit links when needed.
        if (self::tableExists($db, 'visits')) {
            $db->exec("UPDATE prescriptions pr
                       JOIN visits v ON v.id = pr.visit_id
                       SET pr.patient_id = v.patient_id
                       WHERE pr.patient_id IS NULL AND pr.visit_id IS NOT NULL");
        }

        // Normalize common status values for current route filters.
        $db->exec("UPDATE prescriptions SET status = 'Pending' WHERE status IS NULL OR status = '' OR LOWER(status) = 'pending'");
        $db->exec("UPDATE prescriptions SET status = 'Dispensed' WHERE LOWER(status) = 'dispensed'");

        self::ensureWardCatalog($db);
    }

    public static function ensureWardCatalog($db) {
        if (!$db) return;

        $db->exec("CREATE TABLE IF NOT EXISTS wards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            capacity INT DEFAULT 0,
            notes TEXT DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensureColumn($db, 'wards', 'name', 'VARCHAR(191) NOT NULL');
        self::ensureColumn($db, 'wards', 'capacity', 'INT DEFAULT 0');
        self::ensureColumn($db, 'wards', 'notes', 'TEXT DEFAULT NULL');
        self::ensureColumn($db, 'wards', 'is_active', 'TINYINT(1) DEFAULT 1');
        self::ensureColumn($db, 'wards', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

        if (!self::indexExists($db, 'wards', 'uq_wards_name')) {
            try { $db->exec("UPDATE wards SET name = TRIM(name) WHERE name IS NOT NULL"); } catch (Exception $e) {}
            try {
                $db->exec("DELETE w1
                           FROM wards w1
                           INNER JOIN wards w2
                                   ON w1.id > w2.id
                                  AND UPPER(TRIM(w1.name)) = UPPER(TRIM(w2.name))");
            } catch (Exception $e) {}
            try {
                $db->exec("ALTER TABLE wards ADD UNIQUE KEY uq_wards_name (name)");
            } catch (Exception $e) {
                // Ignore when duplicates still exist or index already present under another name.
            }
        }

        self::ensureBedsCatalog($db);
    }

    public static function ensureBedsCatalog($db) {
        if (!$db) return;

        $db->exec("CREATE TABLE IF NOT EXISTS beds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ward_name VARCHAR(191) NOT NULL,
            bed_number VARCHAR(50) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'Available',
            current_patient_id INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_beds_status (status),
            INDEX idx_beds_patient (current_patient_id),
            UNIQUE KEY uq_beds_ward_bed (ward_name, bed_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn($db, 'beds', 'ward_name', 'VARCHAR(191) NOT NULL');
        self::ensureColumn($db, 'beds', 'bed_number', 'VARCHAR(50) NOT NULL');
        self::ensureColumn($db, 'beds', 'status', "VARCHAR(30) NOT NULL DEFAULT 'Available'");
        self::ensureColumn($db, 'beds', 'current_patient_id', 'INT NULL');
        self::ensureColumn($db, 'beds', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        self::ensureColumn($db, 'beds', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        if (!self::indexExists($db, 'beds', 'idx_beds_status')) {
            try { $db->exec("CREATE INDEX idx_beds_status ON beds (status)"); } catch (Exception $e) {}
        }
        if (!self::indexExists($db, 'beds', 'idx_beds_patient')) {
            try { $db->exec("CREATE INDEX idx_beds_patient ON beds (current_patient_id)"); } catch (Exception $e) {}
        }
        if (!self::indexExists($db, 'beds', 'uq_beds_ward_bed')) {
            try {
                $db->exec("DELETE b1
                           FROM beds b1
                           INNER JOIN beds b2
                                   ON b1.id > b2.id
                                  AND UPPER(TRIM(b1.ward_name)) = UPPER(TRIM(b2.ward_name))
                                  AND UPPER(TRIM(b1.bed_number)) = UPPER(TRIM(b2.bed_number))");
            } catch (Exception $e) {}
            try { $db->exec("ALTER TABLE beds ADD UNIQUE KEY uq_beds_ward_bed (ward_name, bed_number)"); } catch (Exception $e) {}
        }

        // Auto-provision bed rows from ward capacity so Ward Monitor has data after wards are added.
        try {
            $wardStmt = $db->query("SELECT name, capacity
                                    FROM wards
                                    WHERE COALESCE(is_active, 1) = 1
                                      AND COALESCE(capacity, 0) > 0");
            $wards = $wardStmt ? ($wardStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            if (!empty($wards)) {
                $countStmt = $db->prepare("SELECT COUNT(*) FROM beds WHERE UPPER(TRIM(ward_name)) = UPPER(TRIM(?))");
                $insertStmt = $db->prepare("INSERT INTO beds (ward_name, bed_number, status, current_patient_id)
                                            VALUES (?, ?, 'Available', NULL)");
                foreach ($wards as $ward) {
                    $wardName = trim((string)($ward['name'] ?? ''));
                    $capacity = (int)($ward['capacity'] ?? 0);
                    if ($wardName === '' || $capacity <= 0) continue;

                    $countStmt->execute([$wardName]);
                    $existing = (int)$countStmt->fetchColumn();
                    if ($existing >= $capacity) continue;

                    for ($i = $existing + 1; $i <= $capacity; $i++) {
                        $insertStmt->execute([$wardName, 'Bed ' . $i]);
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore auto-provisioning failures to keep request path resilient.
        }
    }

    public static function ensureStaffShifts($db) {
        if (!$db) return;

        $db->exec("CREATE TABLE IF NOT EXISTS staff_shifts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role VARCHAR(50) NULL,
            shift_start DATETIME NOT NULL,
            shift_end DATETIME NOT NULL,
            shift_type VARCHAR(50) NULL,
            ward_name VARCHAR(100) NULL,
            status VARCHAR(50) NULL DEFAULT 'Confirmed',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Add missing columns if table exists but is incomplete
        $cols = $db->query("SHOW COLUMNS FROM staff_shifts")->fetchAll(PDO::FETCH_ASSOC);
        $existing = [];
        foreach ($cols as $c) $existing[$c['Field']] = true;

        $alter = [];
        if (!isset($existing['role'])) $alter[] = "ADD COLUMN role VARCHAR(50) NULL";
        if (!isset($existing['shift_type'])) $alter[] = "ADD COLUMN shift_type VARCHAR(50) NULL";
        if (!isset($existing['ward_name'])) $alter[] = "ADD COLUMN ward_name VARCHAR(100) NULL";
        if (!isset($existing['status'])) $alter[] = "ADD COLUMN status VARCHAR(50) NULL DEFAULT 'Confirmed'";
        if (!isset($existing['created_at'])) $alter[] = "ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";

        if (!empty($alter)) {
            $db->exec("ALTER TABLE staff_shifts " . implode(", ", $alter));
        }

        if (!self::indexExists($db, 'staff_shifts', 'uq_staff_shift_slot')) {
            try {
                $db->exec("DELETE s1
                           FROM staff_shifts s1
                           INNER JOIN staff_shifts s2
                                   ON s1.id > s2.id
                                  AND s1.user_id = s2.user_id
                                  AND s1.shift_start = s2.shift_start
                                  AND s1.shift_end = s2.shift_end");
            } catch (Exception $e) {}
            try {
                $db->exec("ALTER TABLE staff_shifts
                           ADD UNIQUE KEY uq_staff_shift_slot (user_id, shift_start, shift_end)");
            } catch (Exception $e) {
                // Ignore if existing data prevents unique index creation.
            }
        }
    }

    public static function ensureAppointments($db) {
        if (!$db) return;
        $db->exec("CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NULL,
            scheduled_at DATETIME NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
            reason TEXT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_appointments_patient (patient_id),
            INDEX idx_appointments_doctor (doctor_id),
            INDEX idx_appointments_date (scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function ensureReceptionHandover($db) {
        if (!$db) return;
        $db->exec("CREATE TABLE IF NOT EXISTS reception_handover (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            shift_start DATETIME NULL,
            shift_end DATETIME NULL,
            notes TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_handover_user (user_id),
            INDEX idx_handover_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function ensureNurseModules($db) {
        if (!$db) return;
        $db->exec("CREATE TABLE IF NOT EXISTS nurse_handover (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            shift_start DATETIME NULL,
            shift_end DATETIME NULL,
            notes TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nurse_handover_user (user_id),
            INDEX idx_nurse_handover_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS nurse_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NULL,
            assigned_to INT NULL,
            task TEXT NOT NULL,
            priority VARCHAR(20) NOT NULL DEFAULT 'normal',
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            due_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nurse_tasks_patient (patient_id),
            INDEX idx_nurse_tasks_status (status),
            INDEX idx_nurse_tasks_due (due_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS nurse_escalations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            reason TEXT NOT NULL,
            severity VARCHAR(20) NOT NULL DEFAULT 'urgent',
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nurse_escalations_patient (patient_id),
            INDEX idx_nurse_escalations_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS nurse_consultations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue_id INT NOT NULL,
            patient_id INT NOT NULL,
            nurse_id INT NULL,
            diagnosis_notes TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nurse_consult_queue (queue_id),
            INDEX idx_nurse_consult_patient (patient_id),
            INDEX idx_nurse_consult_nurse (nurse_id),
            INDEX idx_nurse_consult_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS discharge_summaries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            summary TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_discharge_patient (patient_id),
            INDEX idx_discharge_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function ensureDoctorModules($db) {
        $db->exec("CREATE TABLE IF NOT EXISTS doctor_handover (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            shift_start DATETIME NULL,
            shift_end DATETIME NULL,
            notes TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_doctor_handover_user (user_id),
            INDEX idx_doctor_handover_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS doctor_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NULL,
            assigned_to INT NULL,
            task VARCHAR(255) NOT NULL,
            priority VARCHAR(20) DEFAULT 'normal',
            status VARCHAR(20) DEFAULT 'open',
            due_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_doctor_tasks_patient (patient_id),
            INDEX idx_doctor_tasks_status (status),
            INDEX idx_doctor_tasks_due (due_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS doctor_escalations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            severity VARCHAR(20) DEFAULT 'urgent',
            status VARCHAR(20) DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_doctor_escalations_patient (patient_id),
            INDEX idx_doctor_escalations_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function ensurePharmacyModules($db) {
        $db->exec("CREATE TABLE IF NOT EXISTS drug_interactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            drug_a VARCHAR(120) NOT NULL,
            drug_b VARCHAR(120) NOT NULL,
            severity VARCHAR(20) DEFAULT 'moderate',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_drug_a (drug_a),
            INDEX idx_drug_b (drug_b)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS controlled_substances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            medicine_id INT NOT NULL,
            schedule VARCHAR(20) NOT NULL,
            requires_approval TINYINT(1) DEFAULT 1,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_controlled_med (medicine_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS controlled_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prescription_id INT NOT NULL,
            requested_by INT NULL,
            approved_by INT NULL,
            status VARCHAR(20) DEFAULT 'Pending',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            approved_at DATETIME NULL,
            INDEX idx_ctrl_req_presc (prescription_id),
            INDEX idx_ctrl_req_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS refill_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            medicine_name VARCHAR(120) NOT NULL,
            quantity INT DEFAULT 1,
            notes TEXT NULL,
            requested_by INT NULL,
            status VARCHAR(20) DEFAULT 'Requested',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_refill_patient (patient_id),
            INDEX idx_refill_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS pharmacy_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            requested_by INT NULL,
            status VARCHAR(20) DEFAULT 'Pending',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pharm_req_patient (patient_id),
            INDEX idx_pharm_req_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            contact_name VARCHAR(120) NULL,
            phone VARCHAR(40) NULL,
            email VARCHAR(120) NULL,
            address VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_supplier_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_id INT NOT NULL,
            order_date DATE NOT NULL,
            status VARCHAR(20) DEFAULT 'Pending',
            total_amount DECIMAL(12,2) DEFAULT 0,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_po_supplier (supplier_id),
            INDEX idx_po_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            medicine_name VARCHAR(120) NOT NULL,
            quantity INT DEFAULT 1,
            unit_cost DECIMAL(10,2) DEFAULT 0,
            total_cost DECIMAL(12,2) DEFAULT 0,
            INDEX idx_po_items_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS goods_receipts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            received_by INT NULL,
            received_date DATE NOT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_grn_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS purchase_invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            invoice_number VARCHAR(60) NOT NULL,
            amount DECIMAL(12,2) DEFAULT 0,
            invoice_date DATE NOT NULL,
            status VARCHAR(20) DEFAULT 'Open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_inv_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS quarantine_batches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            medicine_id INT NOT NULL,
            batch_number VARCHAR(60) NULL,
            quantity INT DEFAULT 0,
            reason VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'Quarantined',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_quarantine_med (medicine_id),
            INDEX idx_quarantine_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS stock_adjustments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            medicine_id INT NOT NULL,
            adjustment INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            adjusted_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_adjust_med (medicine_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS insurance_claims (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prescription_id INT NOT NULL,
            patient_id INT NOT NULL,
            status VARCHAR(20) DEFAULT 'Submitted',
            submitted_by INT NULL,
            submitted_at DATETIME NULL,
            notes TEXT NULL,
            INDEX idx_claim_presc (prescription_id),
            INDEX idx_claim_patient (patient_id),
            INDEX idx_claim_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function ensureITModules($db) {
        if (!$db) return;
        $db->exec("CREATE TABLE IF NOT EXISTS it_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'open',
            priority VARCHAR(20) NOT NULL DEFAULT 'normal',
            source VARCHAR(20) NOT NULL DEFAULT 'local',
            requester_name VARCHAR(120) NULL,
            requester_email VARCHAR(120) NULL,
            created_by INT NULL,
            assigned_to INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_it_status (status),
            INDEX idx_it_priority (priority),
            INDEX idx_it_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function ensureUserRole($db, $role) {
        if (!$db || !$role) return;
        $stmt = $db->prepare("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role' LIMIT 1");
        $stmt->execute();
        $type = $stmt->fetchColumn();
        if (!$type || stripos($type, 'enum(') !== 0) return;

        $raw = trim($type);
        $raw = substr($raw, 5, -1);
        $values = array_filter(array_map(function ($v) {
            $v = trim($v);
            return trim($v, "'");
        }, explode("','", $raw)));

        if (in_array($role, $values, true)) return;
        $values[] = $role;
        $enum = "ENUM('" . implode("','", $values) . "')";
        $db->exec("ALTER TABLE users MODIFY role {$enum} NOT NULL");
    }
}
?>
