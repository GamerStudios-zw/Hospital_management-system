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
}
?>
