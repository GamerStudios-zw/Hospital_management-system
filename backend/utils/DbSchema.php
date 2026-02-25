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
