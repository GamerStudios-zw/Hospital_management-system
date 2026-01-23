<?php
require_once '../config/database.php';

class BillingService {
    private $conn;
    private $table_name = "billings";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Create a new invoice for a patient visit
     */
    public function createInvoice($patient_id, $visit_id, $amount, $description) {
        $query = "INSERT INTO " . $this->table_name . "
                  SET patient_id = :pid, visit_id = :vid, total_amount = :amt,
                  balance_due = :amt, status = 'unpaid', description = :desc";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":pid", $patient_id);
        $stmt->bindParam(":vid", $visit_id);
        $stmt->bindParam(":amt", $amount);
        $stmt->bindParam(":desc", $description);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Process a payment against an invoice
     */
    public function processPayment($invoice_id, $amount_paid, $payment_method) {
        // 1. Get current invoice details
        $query = "SELECT total_amount, balance_due FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $invoice_id);
        $stmt->execute();
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) return ["status" => false, "message" => "Invoice not found"];

        // 2. Calculate new balance
        $new_balance = $invoice['balance_due'] - $amount_paid;
        $status = ($new_balance <= 0) ? 'paid' : 'partial';

        // 3. Update the invoice record
        $updateQuery = "UPDATE " . $this->table_name . "
                        SET balance_due = :bal, paid_amount = paid_amount + :paid,
                        status = :status, last_payment_date = NOW(), payment_method = :method
                        WHERE id = :id";

        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(":bal", $new_balance);
        $updateStmt->bindParam(":paid", $amount_paid);
        $updateStmt->bindParam(":status", $status);
        $updateStmt->bindParam(":method", $payment_method);
        $updateStmt->bindParam(":id", $invoice_id);

        if($updateStmt->execute()) {
            return ["status" => true, "new_balance" => $new_balance, "invoice_status" => $status];
        }
        return ["status" => false, "message" => "Payment update failed"];
    }

    /**
     * Get pending bills for a patient
     */
    public function getPendingBills($patient_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE patient_id = :id AND status != 'paid'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $patient_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>