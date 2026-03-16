<?php
// FILE: backend/utils/DbSchema.php

class DbSchema {
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

    public static function ensureReceptionIdentityTables($db) {
        if (!$db) return;

        $db->exec("CREATE TABLE IF NOT EXISTS patient_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            issuer_institution VARCHAR(180) NOT NULL,
            card_number VARCHAR(80) NOT NULL,
            student_number VARCHAR(30) NULL,
            faculty VARCHAR(120) NULL,
            programme VARCHAR(120) NULL,
            level_name VARCHAR(80) NULL,
            semester VARCHAR(80) NULL,
            student_status VARCHAR(80) NULL,
            expiry_date DATE NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_patient_students_patient (patient_id),
            INDEX idx_patient_students_card (card_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS patient_staff (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            issuer_institution VARCHAR(180) NOT NULL,
            card_number VARCHAR(80) NOT NULL,
            ec_number VARCHAR(30) NOT NULL,
            department VARCHAR(120) NULL,
            faculty VARCHAR(120) NULL,
            programme VARCHAR(120) NULL,
            level_name VARCHAR(80) NULL,
            semester VARCHAR(80) NULL,
            staff_status VARCHAR(80) NULL,
            expiry_date DATE NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_patient_staff_patient (patient_id),
            UNIQUE KEY uq_patient_staff_ec_number (ec_number),
            INDEX idx_patient_staff_card (card_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $studentsCols = $db->query("SHOW COLUMNS FROM patient_students")->fetchAll(PDO::FETCH_ASSOC);
        $studentsHasPatient = false;
        foreach ($studentsCols as $col) {
            if (($col['Field'] ?? '') === 'patient_id') {
                $studentsHasPatient = true;
                break;
            }
        }
        $studentsHasStudentNumber = false;
        foreach ($studentsCols as $col) {
            if (($col['Field'] ?? '') === 'student_number') {
                $studentsHasStudentNumber = true;
                break;
            }
        }
        if (!$studentsHasStudentNumber) {
            $db->exec("ALTER TABLE patient_students ADD COLUMN student_number VARCHAR(30) NULL AFTER card_number");
        }
        $studentsIdx = $db->query("SHOW INDEX FROM patient_students")->fetchAll(PDO::FETCH_ASSOC);
        $studentsHasStudentNumberIdx = false;
        $studentsHasStudentNumberUnique = false;
        foreach ($studentsIdx as $idx) {
            if (($idx['Key_name'] ?? '') === 'idx_patient_students_student_number') {
                $studentsHasStudentNumberIdx = true;
            }
            if (($idx['Key_name'] ?? '') === 'uq_patient_students_student_number' && (int)($idx['Non_unique'] ?? 1) === 0) {
                $studentsHasStudentNumberUnique = true;
            }
        }
        if (!$studentsHasStudentNumberIdx) {
            $db->exec("ALTER TABLE patient_students ADD INDEX idx_patient_students_student_number (student_number)");
        }
        if (!$studentsHasStudentNumberUnique) {
            $dupStudentNumber = $db->query("SELECT student_number
                                            FROM patient_students
                                            WHERE student_number IS NOT NULL AND TRIM(student_number) <> ''
                                            GROUP BY student_number
                                            HAVING COUNT(*) > 1
                                            LIMIT 1")->fetchColumn();
            if (!$dupStudentNumber) {
                $db->exec("ALTER TABLE patient_students ADD UNIQUE KEY uq_patient_students_student_number (student_number)");
            }
        }
        if ($studentsHasPatient) {
            $studentsFk = $db->query("SELECT CONSTRAINT_NAME
                                      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                      WHERE TABLE_SCHEMA = DATABASE()
                                        AND TABLE_NAME = 'patient_students'
                                        AND COLUMN_NAME = 'patient_id'
                                        AND REFERENCED_TABLE_NAME = 'patients'
                                      LIMIT 1")->fetchColumn();
            if (!$studentsFk) {
                $db->exec("ALTER TABLE patient_students
                           ADD CONSTRAINT fk_patient_students_patient
                           FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE");
            }
        }

        $staffCols = $db->query("SHOW COLUMNS FROM patient_staff")->fetchAll(PDO::FETCH_ASSOC);
        $staffHasPatient = false;
        foreach ($staffCols as $col) {
            if (($col['Field'] ?? '') === 'patient_id') {
                $staffHasPatient = true;
                break;
            }
        }
        $staffHasDepartment = false;
        foreach ($staffCols as $col) {
            if (($col['Field'] ?? '') === 'department') {
                $staffHasDepartment = true;
                break;
            }
        }
        if (!$staffHasDepartment) {
            $db->exec("ALTER TABLE patient_staff ADD COLUMN department VARCHAR(120) NULL AFTER ec_number");
        }
        if ($staffHasPatient) {
            $staffFk = $db->query("SELECT CONSTRAINT_NAME
                                   FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                   WHERE TABLE_SCHEMA = DATABASE()
                                     AND TABLE_NAME = 'patient_staff'
                                     AND COLUMN_NAME = 'patient_id'
                                     AND REFERENCED_TABLE_NAME = 'patients'
                                   LIMIT 1")->fetchColumn();
            if (!$staffFk) {
                $db->exec("ALTER TABLE patient_staff
                           ADD CONSTRAINT fk_patient_staff_patient
                           FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE");
            }
        }
    }

    public static function ensureReceptionPatientMedicalAidColumns($db) {
        if (!$db) return;

        $patientsExists = $db->query("SHOW TABLES LIKE 'patients'")->fetchColumn();
        if (!$patientsExists) return;

        $cols = $db->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_ASSOC);
        $existing = [];
        foreach ($cols as $col) {
            $existing[$col['Field'] ?? ''] = true;
        }

        if (!isset($existing['has_medical_aid'])) {
            $db->exec("ALTER TABLE patients ADD COLUMN has_medical_aid TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!isset($existing['medical_aid_provider'])) {
            $db->exec("ALTER TABLE patients ADD COLUMN medical_aid_provider VARCHAR(120) NULL");
        }
        if (!isset($existing['medical_aid_number'])) {
            $db->exec("ALTER TABLE patients ADD COLUMN medical_aid_number VARCHAR(60) NULL");
        }
        if (!isset($existing['medical_aid_member_name'])) {
            $db->exec("ALTER TABLE patients ADD COLUMN medical_aid_member_name VARCHAR(150) NULL");
        }
        if (!isset($existing['medical_aid_suffix'])) {
            $db->exec("ALTER TABLE patients ADD COLUMN medical_aid_suffix VARCHAR(30) NULL");
        }
        if (!isset($existing['medical_aid_plan'])) {
            $db->exec("ALTER TABLE patients ADD COLUMN medical_aid_plan VARCHAR(120) NULL");
        }
        if (!isset($existing['medical_aid_date_joined'])) {
            $db->exec("ALTER TABLE patients ADD COLUMN medical_aid_date_joined DATE NULL");
        }

        $nationalIdCol = null;
        foreach ($cols as $col) {
            if (($col['Field'] ?? '') === 'national_id') {
                $nationalIdCol = $col;
                break;
            }
        }
        if ($nationalIdCol) {
            $nationalType = strtoupper((string)($nationalIdCol['Type'] ?? 'VARCHAR(50)'));
            if ($nationalType === '') $nationalType = 'VARCHAR(50)';
            if (strtoupper((string)($nationalIdCol['Null'] ?? 'YES')) !== 'YES') {
                $db->exec("ALTER TABLE patients MODIFY COLUMN national_id {$nationalType} NULL");
            }
        }

        if (isset($existing['national_id'])) {
            $idxRows = $db->query("SHOW INDEX FROM patients")->fetchAll(PDO::FETCH_ASSOC);
            $indexMeta = [];
            foreach ($idxRows as $idx) {
                $keyName = (string)($idx['Key_name'] ?? '');
                if ($keyName === '') continue;
                if (!isset($indexMeta[$keyName])) {
                    $indexMeta[$keyName] = [
                        'unique' => ((int)($idx['Non_unique'] ?? 1) === 0),
                        'columns' => []
                    ];
                }
                $seq = (int)($idx['Seq_in_index'] ?? 0);
                if ($seq <= 0) $seq = count($indexMeta[$keyName]['columns']) + 1;
                $indexMeta[$keyName]['columns'][$seq] = (string)($idx['Column_name'] ?? '');
            }

            $hasNationalIdIndex = false;
            foreach ($indexMeta as $keyName => $meta) {
                $columnsBySeq = $meta['columns'] ?? [];
                if (empty($columnsBySeq)) continue;
                ksort($columnsBySeq);
                $columns = array_values($columnsBySeq);
                $isSingleNationalId = (count($columns) === 1 && $columns[0] === 'national_id');

                if ($isSingleNationalId && !empty($meta['unique']) && $keyName !== 'PRIMARY') {
                    $db->exec("ALTER TABLE patients DROP INDEX `$keyName`");
                    continue;
                }
                if ($isSingleNationalId && empty($meta['unique'])) {
                    $hasNationalIdIndex = true;
                }
            }

            if (!$hasNationalIdIndex) {
                $db->exec("ALTER TABLE patients ADD INDEX idx_patients_national_id (national_id)");
            }
        }
    }

    public static function ensureVisitEncounters($db) {
        if (!$db) return;

        // Canonical encounter table for each clinical visit.
        $db->exec("CREATE TABLE IF NOT EXISTS visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NULL,
            status ENUM('waiting','triaged','in_consultation','pharmacy','completed','cancelled') DEFAULT 'waiting',
            chief_complaint TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_visits_patient (patient_id),
            INDEX idx_visits_status (status),
            INDEX idx_visits_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Link queue rows to encounter rows.
        $queueExists = $db->query("SHOW TABLES LIKE 'patient_queue'")->fetchColumn();
        if (!$queueExists) return;

        $queueCols = $db->query("SHOW COLUMNS FROM patient_queue")->fetchAll(PDO::FETCH_ASSOC);
        $hasVisitId = false;
        foreach ($queueCols as $col) {
            if (($col['Field'] ?? '') === 'visit_id') {
                $hasVisitId = true;
                break;
            }
        }
        if (!$hasVisitId) {
            $db->exec("ALTER TABLE patient_queue ADD COLUMN visit_id INT NULL AFTER patient_id");
        }

        $queueIdx = $db->query("SHOW INDEX FROM patient_queue")->fetchAll(PDO::FETCH_ASSOC);
        $hasVisitIdx = false;
        foreach ($queueIdx as $idx) {
            if (($idx['Key_name'] ?? '') === 'idx_patient_queue_visit') {
                $hasVisitIdx = true;
                break;
            }
        }
        if (!$hasVisitIdx) {
            $db->exec("ALTER TABLE patient_queue ADD INDEX idx_patient_queue_visit (visit_id)");
        }
    }

    public static function ensureCounsellingWorkflow($db) {
        if (!$db) return;
        self::ensureVisitEncounters($db);

        $visitsExists = $db->query("SHOW TABLES LIKE 'visits'")->fetchColumn();
        if ($visitsExists) {
            $visitCols = $db->query("SHOW COLUMNS FROM visits")->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $visitExisting = [];
            foreach ($visitCols as $col) {
                $visitExisting[$col['Field'] ?? ''] = true;
            }
            $visitAlter = [];
            if (!isset($visitExisting['clinical_notes'])) $visitAlter[] = "ADD COLUMN clinical_notes TEXT NULL";
            if (!isset($visitExisting['outcome'])) $visitAlter[] = "ADD COLUMN outcome VARCHAR(40) NULL";
            if (!isset($visitExisting['completed_at'])) $visitAlter[] = "ADD COLUMN completed_at DATETIME NULL";
            if (!empty($visitAlter)) {
                $db->exec("ALTER TABLE visits " . implode(", ", $visitAlter));
            }
        }

        $queueExists = $db->query("SHOW TABLES LIKE 'patient_queue'")->fetchColumn();
        if ($queueExists) {
            $queueCols = $db->query("SHOW COLUMNS FROM patient_queue")->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $queueHasVisitType = false;
            foreach ($queueCols as $col) {
                if (($col['Field'] ?? '') === 'visit_type') {
                    $queueHasVisitType = true;
                    break;
                }
            }
            if (!$queueHasVisitType) {
                $db->exec("ALTER TABLE patient_queue ADD COLUMN visit_type VARCHAR(40) NOT NULL DEFAULT 'general' AFTER status");
            }

            $queueIdx = $db->query("SHOW INDEX FROM patient_queue")->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $hasVisitTypeIdx = false;
            foreach ($queueIdx as $idx) {
                if (($idx['Key_name'] ?? '') === 'idx_patient_queue_visit_type') {
                    $hasVisitTypeIdx = true;
                    break;
                }
            }
            if (!$hasVisitTypeIdx) {
                $db->exec("ALTER TABLE patient_queue ADD INDEX idx_patient_queue_visit_type (visit_type, status)");
            }
        }

        $db->exec("CREATE TABLE IF NOT EXISTS counselling_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            queue_id INT NULL,
            visit_id INT NULL,
            counsellor_id INT NULL,
            notes TEXT NOT NULL,
            outcome VARCHAR(40) NOT NULL,
            safety_plan TEXT NULL,
            follow_up_at DATETIME NULL,
            escalation_reason TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_counselling_patient (patient_id),
            INDEX idx_counselling_queue (queue_id),
            INDEX idx_counselling_visit (visit_id),
            INDEX idx_counselling_outcome (outcome),
            INDEX idx_counselling_created (created_at)
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

        $db->exec("CREATE TABLE IF NOT EXISTS discharge_summaries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            summary TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_discharge_patient (patient_id),
            INDEX idx_discharge_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Track queue origin so Nurse Aid triage can include only patients
        // explicitly pulled from Nurse Aid search.
        $queueExists = $db->query("SHOW TABLES LIKE 'patient_queue'")->fetchColumn();
        if ($queueExists) {
            $queueCols = $db->query("SHOW COLUMNS FROM patient_queue")->fetchAll(PDO::FETCH_ASSOC);
            $hasQueueOrigin = false;
            foreach ($queueCols as $col) {
                if (($col['Field'] ?? '') === 'queue_origin') {
                    $hasQueueOrigin = true;
                    break;
                }
            }
            if (!$hasQueueOrigin) {
                $db->exec("ALTER TABLE patient_queue
                           ADD COLUMN queue_origin VARCHAR(50) NULL DEFAULT NULL AFTER status,
                           ADD INDEX idx_patient_queue_origin_status (queue_origin, status)");
            }
        }
    }

    public static function ensureBedManagement($db) {
        if (!$db) return;

        $db->exec("CREATE TABLE IF NOT EXISTS beds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ward_name VARCHAR(100) NOT NULL,
            bed_number VARCHAR(30) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'Available',
            ward_status VARCHAR(30) NOT NULL DEFAULT 'Open',
            current_patient_id INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $cols = $db->query("SHOW COLUMNS FROM beds")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $existing = [];
        foreach ($cols as $col) {
            $existing[$col['Field']] = true;
        }

        $alter = [];
        if (!isset($existing['ward_name'])) $alter[] = "ADD COLUMN ward_name VARCHAR(100) NOT NULL DEFAULT 'General Ward'";
        if (!isset($existing['bed_number'])) $alter[] = "ADD COLUMN bed_number VARCHAR(30) NOT NULL DEFAULT 'Bed'";
        if (!isset($existing['status'])) $alter[] = "ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Available'";
        if (!isset($existing['ward_status'])) $alter[] = "ADD COLUMN ward_status VARCHAR(30) NOT NULL DEFAULT 'Open'";
        if (!isset($existing['current_patient_id'])) $alter[] = "ADD COLUMN current_patient_id INT NULL";
        if (!isset($existing['created_at'])) $alter[] = "ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";

        if (!empty($alter)) {
            $db->exec("ALTER TABLE beds " . implode(", ", $alter));
        }

        $db->exec("UPDATE beds SET status = 'Available' WHERE status IS NULL OR TRIM(status) = ''");
        $db->exec("UPDATE beds SET ward_status = 'Open' WHERE ward_status IS NULL OR TRIM(ward_status) = ''");
    }

    public static function ensureClinicalOperationsModules($db) {
        if (!$db) return;

        // Structured nursing assessment records (beyond basic vitals).
        $db->exec("CREATE TABLE IF NOT EXISTS nursing_assessments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            queue_id INT NULL,
            visit_id INT NULL,
            assessed_by INT NULL,
            assessment_time DATETIME NULL,
            temperature DECIMAL(4,1) NULL,
            pulse INT NULL,
            respiratory_rate INT NULL,
            bp VARCHAR(20) NULL,
            spo2 INT NULL,
            pain_score TINYINT NULL,
            consciousness_level VARCHAR(50) NULL,
            mobility_status VARCHAR(100) NULL,
            neuro_notes TEXT NULL,
            respiratory_notes TEXT NULL,
            cardiovascular_notes TEXT NULL,
            skin_notes TEXT NULL,
            notes TEXT NULL,
            severity_level VARCHAR(20) NULL,
            escalation_required TINYINT(1) NOT NULL DEFAULT 0,
            escalated_to_doctor TINYINT(1) NOT NULL DEFAULT 0,
            escalated_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nursing_assess_patient (patient_id),
            INDEX idx_nursing_assess_queue (queue_id),
            INDEX idx_nursing_assess_time (assessment_time),
            INDEX idx_nursing_assess_severity (severity_level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Medication administration record (MAR).
        $db->exec("CREATE TABLE IF NOT EXISTS medication_administration (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            prescription_id INT NULL,
            medicine_id INT NULL,
            administered_by INT NULL,
            medication_name VARCHAR(150) NULL,
            dose VARCHAR(60) NULL,
            route VARCHAR(40) NULL,
            frequency VARCHAR(40) NULL,
            scheduled_at DATETIME NULL,
            administered_at DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
            right_patient TINYINT(1) NOT NULL DEFAULT 0,
            right_drug TINYINT(1) NOT NULL DEFAULT 0,
            right_dose TINYINT(1) NOT NULL DEFAULT 0,
            right_route TINYINT(1) NOT NULL DEFAULT 0,
            right_time TINYINT(1) NOT NULL DEFAULT 0,
            adverse_reaction TINYINT(1) NOT NULL DEFAULT 0,
            reaction_notes TEXT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_mar_patient (patient_id),
            INDEX idx_mar_prescription (prescription_id),
            INDEX idx_mar_status (status),
            INDEX idx_mar_administered_at (administered_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Nurse procedures such as wound care, catheterization, IV maintenance, oxygen therapy.
        $db->exec("CREATE TABLE IF NOT EXISTS nursing_procedures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            queue_id INT NULL,
            performed_by INT NULL,
            procedure_type VARCHAR(100) NOT NULL,
            body_site VARCHAR(120) NULL,
            indication TEXT NULL,
            findings TEXT NULL,
            outcome TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'completed',
            performed_at DATETIME NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nursing_proc_patient (patient_id),
            INDEX idx_nursing_proc_type (procedure_type),
            INDEX idx_nursing_proc_status (status),
            INDEX idx_nursing_proc_time (performed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // High-level nursing care plan header.
        $db->exec("CREATE TABLE IF NOT EXISTS care_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            created_by INT NULL,
            primary_diagnosis VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            start_date DATE NULL,
            target_end_date DATE NULL,
            evaluation_notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_care_plan_patient (patient_id),
            INDEX idx_care_plan_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Measurable goals and interventions for each care plan.
        $db->exec("CREATE TABLE IF NOT EXISTS care_plan_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            care_plan_id INT NOT NULL,
            goal TEXT NOT NULL,
            intervention TEXT NOT NULL,
            due_at DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'planned',
            completed_at DATETIME NULL,
            evaluation TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_care_plan_item_plan (care_plan_id),
            INDEX idx_care_plan_item_status (status),
            INDEX idx_care_plan_item_due (due_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Patient/family education log.
        $db->exec("CREATE TABLE IF NOT EXISTS patient_education (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            provided_by INT NULL,
            topic VARCHAR(150) NOT NULL,
            details TEXT NULL,
            language VARCHAR(40) NULL,
            understanding_level VARCHAR(40) NULL,
            materials_given TEXT NULL,
            follow_up_required TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_patient_education_patient (patient_id),
            INDEX idx_patient_education_topic (topic),
            INDEX idx_patient_education_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Infection prevention and control checklist logging.
        $db->exec("CREATE TABLE IF NOT EXISTS infection_control_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NULL,
            logged_by INT NULL,
            isolation_type VARCHAR(50) NULL,
            ppe_used VARCHAR(255) NULL,
            hand_hygiene_compliant TINYINT(1) NOT NULL DEFAULT 1,
            sterile_protocol_followed TINYINT(1) NOT NULL DEFAULT 1,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_infection_log_patient (patient_id),
            INDEX idx_infection_log_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Hospital-acquired infection (HAI) reporting.
        $db->exec("CREATE TABLE IF NOT EXISTS hai_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            reported_by INT NULL,
            infection_type VARCHAR(150) NOT NULL,
            suspected_source VARCHAR(255) NULL,
            severity VARCHAR(20) NOT NULL DEFAULT 'moderate',
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            report_date DATETIME NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_hai_patient (patient_id),
            INDEX idx_hai_status (status),
            INDEX idx_hai_report_date (report_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Emergency response registry (code events, pre-doctor stabilization).
        $db->exec("CREATE TABLE IF NOT EXISTS emergency_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            queue_id INT NULL,
            triggered_by INT NULL,
            event_type VARCHAR(100) NOT NULL,
            code_type VARCHAR(50) NULL,
            priority VARCHAR(20) NOT NULL DEFAULT 'urgent',
            started_at DATETIME NULL,
            ended_at DATETIME NULL,
            interventions TEXT NULL,
            outcome VARCHAR(120) NULL,
            doctor_notified TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_emergency_patient (patient_id),
            INDEX idx_emergency_type (event_type),
            INDEX idx_emergency_priority (priority),
            INDEX idx_emergency_started (started_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Adverse drug reaction log.
        $db->exec("CREATE TABLE IF NOT EXISTS adverse_drug_reactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            prescription_id INT NULL,
            medicine_id INT NULL,
            reported_by INT NULL,
            reaction VARCHAR(255) NOT NULL,
            severity VARCHAR(20) NOT NULL DEFAULT 'moderate',
            onset_at DATETIME NULL,
            action_taken TEXT NULL,
            outcome TEXT NULL,
            reported_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_adr_patient (patient_id),
            INDEX idx_adr_severity (severity),
            INDEX idx_adr_reported_at (reported_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Dedicated consent forms store (separate from generic document uploads).
        $db->exec("CREATE TABLE IF NOT EXISTS consent_forms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            recorded_by INT NULL,
            consent_type VARCHAR(120) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'signed',
            document_path VARCHAR(255) NULL,
            notes TEXT NULL,
            consented_at DATETIME NULL,
            expires_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_consent_patient (patient_id),
            INDEX idx_consent_type (consent_type),
            INDEX idx_consent_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Dedicated referral register (separate from generic medical reports).
        $db->exec("CREATE TABLE IF NOT EXISTS referrals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            referred_by INT NULL,
            referral_type VARCHAR(120) NOT NULL,
            destination VARCHAR(180) NULL,
            reason TEXT NULL,
            urgency VARCHAR(20) NOT NULL DEFAULT 'normal',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            referral_date DATETIME NULL,
            notes TEXT NULL,
            attachment_path VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_referral_patient (patient_id),
            INDEX idx_referral_status (status),
            INDEX idx_referral_date (referral_date)
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

    public static function ensureReferralRegistry($db) {
        if (!$db) return;
        $db->exec("CREATE TABLE IF NOT EXISTS referrals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            referred_by INT NULL,
            referral_type VARCHAR(120) NOT NULL,
            external_provider_name VARCHAR(180) NULL,
            destination VARCHAR(180) NULL,
            reason TEXT NULL,
            urgency VARCHAR(20) NOT NULL DEFAULT 'normal',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            referral_date DATETIME NULL,
            notes TEXT NULL,
            attachment_path VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_referral_patient (patient_id),
            INDEX idx_referral_status (status),
            INDEX idx_referral_date (referral_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $cols = $db->query("SHOW COLUMNS FROM referrals")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $hasExternalProvider = false;
        foreach ($cols as $col) {
            if (($col['Field'] ?? '') === 'external_provider_name') {
                $hasExternalProvider = true;
                break;
            }
        }
        if (!$hasExternalProvider) {
            $db->exec("ALTER TABLE referrals ADD COLUMN external_provider_name VARCHAR(180) NULL AFTER referral_type");
        }
    }

    public static function ensureNurseInChargeRole($db, $migrateFromDoctor = true) {
        if (!$db) return;
        self::ensureUserRole($db, 'nurse_in_charge');
        if (!$migrateFromDoctor) return;

        // Clinic profile no longer uses in-house doctor accounts.
        $db->exec("UPDATE users SET role = 'nurse_in_charge' WHERE role = 'doctor'");

        $hasShiftsTable = (bool)$db->query("SHOW TABLES LIKE 'staff_shifts'")->fetchColumn();
        if ($hasShiftsTable) {
            $db->exec("UPDATE staff_shifts SET role = 'nurse_in_charge' WHERE role = 'doctor'");
        }
    }

    public static function ensurePrescriptionWorkflow($db) {
        if (!$db) return;

        $db->exec("CREATE TABLE IF NOT EXISTS prescriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NULL,
            visit_id INT NULL,
            queue_id INT NULL,
            medicine_id INT NULL,
            medication_name VARCHAR(150) NULL,
            quantity INT NOT NULL DEFAULT 1,
            dosage VARCHAR(100) NOT NULL DEFAULT 'As directed',
            frequency VARCHAR(50) NULL,
            duration VARCHAR(50) NULL,
            notes TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_prescriptions_patient_status (patient_id, status),
            INDEX idx_prescriptions_visit (visit_id),
            INDEX idx_prescriptions_queue (queue_id),
            INDEX idx_prescriptions_medicine (medicine_id),
            INDEX idx_prescriptions_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $cols = $db->query("SHOW COLUMNS FROM prescriptions")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $existing = [];
        foreach ($cols as $col) {
            $existing[$col['Field'] ?? ''] = $col;
        }

        if (!isset($existing['patient_id'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN patient_id INT NULL AFTER id");
        if (!isset($existing['visit_id'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN visit_id INT NULL AFTER patient_id");
        if (!isset($existing['queue_id'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN queue_id INT NULL AFTER visit_id");
        if (!isset($existing['medicine_id'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN medicine_id INT NULL AFTER queue_id");
        if (!isset($existing['medication_name'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN medication_name VARCHAR(150) NULL AFTER medicine_id");
        if (!isset($existing['quantity'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER medication_name");
        if (!isset($existing['dosage'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN dosage VARCHAR(100) NOT NULL DEFAULT 'As directed' AFTER quantity");
        if (!isset($existing['notes'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN notes TEXT NULL AFTER duration");
        if (!isset($existing['status'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER notes");
        if (!isset($existing['created_at'])) $db->exec("ALTER TABLE prescriptions ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status");

        $cols = $db->query("SHOW COLUMNS FROM prescriptions")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $existing = [];
        foreach ($cols as $col) {
            $existing[$col['Field'] ?? ''] = $col;
        }

        if (isset($existing['visit_id']) && strtoupper((string)($existing['visit_id']['Null'] ?? 'YES')) !== 'YES') {
            $db->exec("ALTER TABLE prescriptions MODIFY COLUMN visit_id INT NULL");
        }
        if (isset($existing['medication_name']) && strtoupper((string)($existing['medication_name']['Null'] ?? 'YES')) !== 'YES') {
            $db->exec("ALTER TABLE prescriptions MODIFY COLUMN medication_name VARCHAR(150) NULL");
        }
        if (isset($existing['dosage'])) {
            $dosageNull = strtoupper((string)($existing['dosage']['Null'] ?? 'YES'));
            $dosageType = (string)($existing['dosage']['Type'] ?? 'VARCHAR(100)');
            if ($dosageType === '') $dosageType = 'VARCHAR(100)';
            if ($dosageNull === 'YES') {
                $db->exec("ALTER TABLE prescriptions MODIFY COLUMN dosage $dosageType NOT NULL");
            }
        }
        if (isset($existing['status'])) {
            $statusType = strtolower((string)($existing['status']['Type'] ?? ''));
            $statusNull = strtoupper((string)($existing['status']['Null'] ?? 'YES'));
            $statusDefault = strtolower((string)($existing['status']['Default'] ?? ''));
            if (strpos($statusType, 'enum(') === 0 || strpos($statusType, 'varchar') !== 0 || $statusNull === 'YES' || $statusDefault === '') {
                $db->exec("ALTER TABLE prescriptions MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");
            }
        }

        $db->exec("UPDATE prescriptions
                   SET status = CASE
                       WHEN status IS NULL OR TRIM(status) = '' THEN 'pending'
                       WHEN LOWER(TRIM(status)) IN ('pending', 'new', 'requested') THEN 'pending'
                       WHEN LOWER(TRIM(status)) IN ('external', 'manual', 'outside') THEN 'external'
                       WHEN LOWER(TRIM(status)) IN ('dispensed', 'completed', 'done') THEN 'dispensed'
                       WHEN LOWER(TRIM(status)) IN ('cancelled', 'canceled') THEN 'cancelled'
                       ELSE LOWER(TRIM(status))
                   END");

        $db->exec("UPDATE prescriptions
                   SET dosage = 'As directed'
                   WHERE dosage IS NULL OR TRIM(dosage) = ''");

        $visitsExists = $db->query("SHOW TABLES LIKE 'visits'")->fetchColumn();
        if ($visitsExists && isset($existing['patient_id']) && isset($existing['visit_id'])) {
            $db->exec("UPDATE prescriptions pr
                       INNER JOIN visits v ON v.id = pr.visit_id
                       SET pr.patient_id = v.patient_id
                       WHERE (pr.patient_id IS NULL OR pr.patient_id = 0) AND pr.visit_id IS NOT NULL");
        }

        if (isset($existing['medication_name']) && isset($existing['notes'])) {
            $db->exec("UPDATE prescriptions
                       SET medication_name = TRIM(notes)
                       WHERE (medication_name IS NULL OR TRIM(medication_name) = '')
                         AND notes IS NOT NULL
                         AND TRIM(notes) <> ''");
        }
        $medicinesExists = $db->query("SHOW TABLES LIKE 'medicines'")->fetchColumn();
        if ($medicinesExists && isset($existing['medication_name']) && isset($existing['medicine_id'])) {
            $db->exec("UPDATE prescriptions pr
                       INNER JOIN medicines m ON m.id = pr.medicine_id
                       SET pr.medication_name = m.name
                       WHERE (pr.medication_name IS NULL OR TRIM(pr.medication_name) = '')");
        }

        $idxRows = $db->query("SHOW INDEX FROM prescriptions")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $indexNames = [];
        foreach ($idxRows as $idx) {
            $keyName = (string)($idx['Key_name'] ?? '');
            if ($keyName !== '') $indexNames[$keyName] = true;
        }
        if (!isset($indexNames['idx_prescriptions_patient_status']) && isset($existing['patient_id']) && isset($existing['status'])) {
            $db->exec("ALTER TABLE prescriptions ADD INDEX idx_prescriptions_patient_status (patient_id, status)");
        }
        if (!isset($indexNames['idx_prescriptions_visit']) && isset($existing['visit_id'])) {
            $db->exec("ALTER TABLE prescriptions ADD INDEX idx_prescriptions_visit (visit_id)");
        }
        if (!isset($indexNames['idx_prescriptions_queue']) && isset($existing['queue_id'])) {
            $db->exec("ALTER TABLE prescriptions ADD INDEX idx_prescriptions_queue (queue_id)");
        }
        if (!isset($indexNames['idx_prescriptions_medicine']) && isset($existing['medicine_id'])) {
            $db->exec("ALTER TABLE prescriptions ADD INDEX idx_prescriptions_medicine (medicine_id)");
        }
        if (!isset($indexNames['idx_prescriptions_created']) && isset($existing['created_at'])) {
            $db->exec("ALTER TABLE prescriptions ADD INDEX idx_prescriptions_created (created_at)");
        }
    }

    public static function ensurePharmacyModules($db) {
        if (!$db) return;
        self::ensureVisitEncounters($db);
        self::ensurePrescriptionWorkflow($db);

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
