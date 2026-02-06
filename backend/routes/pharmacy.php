<?php
// FILE: backend/routes/pharmacy.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';
require_once __DIR__ . '/../utils/Realtime.php';
require_once __DIR__ . '/../utils/DbSchema.php';

$database = new Database();
$db = $database->getConnection();
DbSchema::ensurePharmacyModules($db);

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['pharmacist', 'admin', 'senior_pharmacist'], $user);
$normalizedRole = str_replace([' ', '-'], '_', strtolower(trim((string)($user->role ?? ''))));
$requireSeniorPharmacy = function () use ($normalizedRole) {
    if ($normalizedRole !== 'senior_pharmacist') {
        http_response_code(403);
        echo json_encode(["message" => "This action requires senior pharmacist access."]);
        exit;
    }
};

switch ($action) {

    // 1. GET PENDING PRESCRIPTIONS (Matches loadPending() in dashboard)
    case 'pending':
        if ($method === 'GET') {
            // Use LEFT JOIN so manual/external medicines still show up
            $query = "SELECT p.id, p.quantity, p.dosage, p.created_at, p.notes as manual_name,
                             pat.full_name as patient_name, pat.medical_aid_number,
                             med.name as med_name, med.stock_quantity,
                             q.doctor_assigned,
                             cs.requires_approval as controlled_requires_approval
                      FROM prescriptions p
                      JOIN patients pat ON p.patient_id = pat.id
                      LEFT JOIN medicines med ON p.medicine_id = med.id
                      LEFT JOIN controlled_substances cs ON cs.medicine_id = p.medicine_id
                      JOIN patient_queue q ON p.patient_id = q.patient_id
                      WHERE p.status IN ('Pending', 'External')
                      AND q.status IN ('Completed', 'completed')
                      ORDER BY p.created_at ASC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 2. DISPENSE MEDICATION (Transaction Safe)
    case 'dispense':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if(!isset($data->prescription_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Missing ID"]);
                exit;
            }

            try {
                $db->beginTransaction();

                $stmt = $db->prepare("SELECT medicine_id, quantity FROM prescriptions WHERE id = ?");
                $stmt->execute([$data->prescription_id]);
                $presc = $stmt->fetch(PDO::FETCH_ASSOC);

                if(!$presc) throw new Exception("Prescription not found");

                // Block controlled substances without approval
                if ($presc['medicine_id']) {
                    $ctrl = $db->prepare("SELECT requires_approval FROM controlled_substances WHERE medicine_id = ?");
                    $ctrl->execute([$presc['medicine_id']]);
                    $requires = $ctrl->fetchColumn();
                    if ($requires && !in_array($normalizedRole, ['pharmacist', 'senior_pharmacist', 'admin'], true)) {
                        $appr = $db->prepare("SELECT COUNT(*) FROM controlled_requests WHERE prescription_id = ? AND status = 'Approved'");
                        $appr->execute([$data->prescription_id]);
                        if ((int)$appr->fetchColumn() === 0) {
                            throw new Exception("Controlled substance requires approval before dispensing.");
                        }
                    }
                }

                // Only deduct stock if it's a system-tracked medicine
                if($presc['medicine_id']) {
                    $checkStock = $db->prepare("SELECT stock_quantity FROM medicines WHERE id = ?");
                    $checkStock->execute([$presc['medicine_id']]);
                    $currentStock = $checkStock->fetchColumn();

                    if($currentStock < $presc['quantity']) {
                        throw new Exception("Insufficient stock! Available: $currentStock");
                    }

                    $updateStock = $db->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ?");
                    $updateStock->execute([$presc['quantity'], $presc['medicine_id']]);
                }

                $updateStatus = $db->prepare("UPDATE prescriptions SET status = 'Dispensed' WHERE id = ?");
                $updateStatus->execute([$data->prescription_id]);

                $db->commit();
                try {
                    ActivityLogger::log($db, $user->full_name ?? 'pharmacist', 'Dispensed medication', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'prescription',
                        'entity_id' => (string)$data->prescription_id,
                        'actor_id' => isset($user->id) ? (string)$user->id : null,
                        'actor_role' => $user->role ?? 'pharmacist',
                        'source' => 'pharmacy/dispense'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                Realtime::emit('pharmacy.dispense', ['prescription_id' => $data->prescription_id]);
                echo json_encode(["message" => "Medication dispensed successfully"]);

            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Error: " . $e->getMessage()]);
            }
        }
        break;

    // 3. GET DISPENSING HISTORY (Matches loadHistory() in dashboard)
    case 'history':
        if ($method === 'GET') {
            $query = "SELECT p.id, p.quantity, p.dosage, p.created_at,
                             pat.full_name as patient_name,
                             IFNULL(med.name, p.notes) as med_name
                      FROM prescriptions p
                      JOIN patients pat ON p.patient_id = pat.id
                      LEFT JOIN medicines med ON p.medicine_id = med.id
                      WHERE p.status = 'Dispensed'
                      ORDER BY p.created_at DESC
                      LIMIT 100";

            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 4. GET PHARMACIST SHIFTS (Matches loadPharmacistShifts() in dashboard)
    case 'shifts':
        if ($method === 'GET') {
            try {
                // Filters for 'pharmacist' role to ensure correct roster display
                $query = "SELECT * FROM staff_shifts WHERE role = 'pharmacist' ORDER BY shift_start ASC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to load roster: " . $e->getMessage()]);
            }
        }
        break;

    // 5. DASHBOARD STATS
    case 'stats':
        $pending = $db->query("SELECT COUNT(*) FROM prescriptions WHERE status IN ('Pending', 'External')")->fetchColumn();
        $today = $db->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'Dispensed' AND DATE(created_at) = CURDATE()")->fetchColumn();
        echo json_encode([
            "pending" => (int)$pending,
            "dispensed_today" => (int)$today
        ]);
        break;

    // 6. ANALYTICS
    case 'analytics':
        if ($method === 'GET') {
            $statusRows = $db->query("SELECT status, COUNT(*) as count
                                      FROM prescriptions
                                      GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $dispensedRows = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count
                                         FROM prescriptions
                                         WHERE status = 'Dispensed'
                                         AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                         GROUP BY DATE(created_at)
                                         ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $pendingRows = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count
                                       FROM prescriptions
                                       WHERE status IN ('Pending','External')
                                       AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                       GROUP BY DATE(created_at)
                                       ORDER BY day")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                "status_counts" => $statusRows,
                "dispensed_by_day" => $dispensedRows,
                "pending_by_day" => $pendingRows
            ]);
        }
        break;

    // 7. PATIENT LIST (for pharmacy modules)
    case 'patients':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT id, full_name, national_id FROM patients ORDER BY full_name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 8. DRUG INTERACTIONS (Rule based)
    case 'interactions_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT * FROM drug_interactions ORDER BY created_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'interactions_add':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->drug_a) || empty($data->drug_b)) {
                http_response_code(400);
                echo json_encode(["message" => "Both drugs required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO drug_interactions (drug_a, drug_b, severity, notes)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data->drug_a,
                $data->drug_b,
                $data->severity ?? 'moderate',
                $data->notes ?? null
            ]);
            echo json_encode(["message" => "Interaction rule added"]);
        }
        break;

    case 'interactions_check':
        if ($method === 'GET') {
            $pid = $_GET['patient_id'] ?? 0;
            $med = trim($_GET['medicine'] ?? '');
            if (!$pid || $med === '') {
                http_response_code(400);
                echo json_encode(["message" => "Patient and medicine required"]);
                exit;
            }
            $current = $db->query("SELECT COALESCE(m.name, pr.notes) as med
                                   FROM prescriptions pr
                                   LEFT JOIN medicines m ON pr.medicine_id = m.id
                                   WHERE pr.patient_id = $pid
                                   ORDER BY pr.created_at DESC
                                   LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
            $matches = [];
            foreach ($current as $existing) {
                $stmt = $db->prepare("SELECT * FROM drug_interactions
                                      WHERE (LOWER(drug_a) = LOWER(?) AND LOWER(drug_b) = LOWER(?))
                                         OR (LOWER(drug_a) = LOWER(?) AND LOWER(drug_b) = LOWER(?))");
                $stmt->execute([$med, $existing, $existing, $med]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $row['with'] = $existing;
                    $matches[] = $row;
                }
            }
            echo json_encode($matches);
        }
        break;

    // 9. CONTROLLED SUBSTANCES
    case 'controlled_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT c.*, m.name as med_name
                                FROM controlled_substances c
                                LEFT JOIN medicines m ON c.medicine_id = m.id
                                ORDER BY c.created_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'controlled_add':
        if ($method === 'POST') {
            $requireSeniorPharmacy();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->medicine_id) || empty($data->schedule)) {
                http_response_code(400);
                echo json_encode(["message" => "Medicine and schedule required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO controlled_substances (medicine_id, schedule, requires_approval, notes)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->medicine_id, $data->schedule, $data->requires_approval ? 1 : 0, $data->notes ?? null]);
            echo json_encode(["message" => "Controlled substance saved"]);
        }
        break;

    case 'controlled_request':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->prescription_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Prescription required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO controlled_requests (prescription_id, requested_by, status, notes)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->prescription_id, $data->requested_by ?? null, 'Pending', $data->notes ?? null]);
            echo json_encode(["message" => "Approval requested"]);
        }
        break;

    case 'controlled_approve':
        if ($method === 'POST') {
            $role = str_replace([' ', '-'], '_', strtolower(trim((string)($user->role ?? ''))));
            if (!in_array($role, ['admin', 'senior_pharmacist'], true)) {
                http_response_code(403);
                echo json_encode(["message" => "Approval requires admin or senior pharmacist"]);
                exit;
            }
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->request_id) || empty($data->status)) {
                http_response_code(400);
                echo json_encode(["message" => "Request and status required"]);
                exit;
            }
            $stmt = $db->prepare("UPDATE controlled_requests
                                  SET status = ?, approved_by = ?, approved_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([$data->status, $user->id ?? null, $data->request_id]);
            echo json_encode(["message" => "Approval updated"]);
        }
        break;

    case 'controlled_requests':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT r.*, pr.id as prescription_id, pat.full_name as patient_name,
                                       COALESCE(m.name, pr.notes) as med_name
                                FROM controlled_requests r
                                JOIN prescriptions pr ON r.prescription_id = pr.id
                                JOIN patients pat ON pr.patient_id = pat.id
                                LEFT JOIN medicines m ON pr.medicine_id = m.id
                                ORDER BY r.created_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 10. REFILL REQUESTS
    case 'refill_list':
        if ($method === 'GET') {
            $requireSeniorPharmacy();
            $stmt = $db->query("SELECT r.*, p.full_name as patient_name
                                FROM refill_requests r
                                JOIN patients p ON r.patient_id = p.id
                                ORDER BY r.created_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'refill_update':
        if ($method === 'POST') {
            $requireSeniorPharmacy();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->request_id) || empty($data->status)) {
                http_response_code(400);
                echo json_encode(["message" => "Request and status required"]);
                exit;
            }
            $stmt = $db->prepare("UPDATE refill_requests SET status = ? WHERE id = ?");
            $stmt->execute([$data->status, $data->request_id]);
            echo json_encode(["message" => "Refill updated"]);
        }
        break;

    // 11. SUPPLIERS
    case 'supplier_list':
        if ($method === 'GET') {
            $requireSeniorPharmacy();
            $stmt = $db->query("SELECT * FROM suppliers ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'supplier_create':
        if ($method === 'POST') {
            $requireSeniorPharmacy();
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data->name)) {
                http_response_code(400);
                echo json_encode(["message" => "Supplier name required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO suppliers (name, contact_name, phone, email, address)
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data->name, $data->contact_name ?? null, $data->phone ?? null, $data->email ?? null, $data->address ?? null]);
            echo json_encode(["message" => "Supplier created"]);
        }
        break;

    // 12. PURCHASE ORDERS + GRN + INVOICES
    case 'po_create':
        if ($method === 'POST') {
            $requireSeniorPharmacy();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->supplier_id) || empty($data->items)) {
                http_response_code(400);
                echo json_encode(["message" => "Supplier and items required"]);
                exit;
            }
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO purchase_orders (supplier_id, order_date, status, total_amount, created_by)
                                  VALUES (?, ?, ?, ?, ?)");
            $total = 0;
            foreach ($data->items as $i) {
                $total += ((float)$i->unit_cost) * ((int)$i->quantity);
            }
            $stmt->execute([$data->supplier_id, $data->order_date ?? date('Y-m-d'), 'Pending', $total, $data->created_by ?? null]);
            $orderId = $db->lastInsertId();
            $itemStmt = $db->prepare("INSERT INTO purchase_order_items (order_id, medicine_name, quantity, unit_cost, total_cost)
                                      VALUES (?, ?, ?, ?, ?)");
            foreach ($data->items as $i) {
                $itemTotal = ((float)$i->unit_cost) * ((int)$i->quantity);
                $itemStmt->execute([$orderId, $i->medicine_name, $i->quantity, $i->unit_cost, $itemTotal]);
            }
            $db->commit();
            echo json_encode(["message" => "PO created"]);
        }
        break;

    case 'po_list':
        if ($method === 'GET') {
            $requireSeniorPharmacy();
            $stmt = $db->query("SELECT po.*, s.name as supplier_name
                                FROM purchase_orders po
                                LEFT JOIN suppliers s ON po.supplier_id = s.id
                                ORDER BY po.created_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'po_items':
        if ($method === 'GET') {
            $requireSeniorPharmacy();
            $orderId = $_GET['order_id'] ?? 0;
            $stmt = $db->prepare("SELECT * FROM purchase_order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'grn_create':
        if ($method === 'POST') {
            $requireSeniorPharmacy();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->order_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Order required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO goods_receipts (order_id, received_by, received_date, notes)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->order_id, $data->received_by ?? null, $data->received_date ?? date('Y-m-d'), $data->notes ?? null]);
            $db->prepare("UPDATE purchase_orders SET status = 'Received' WHERE id = ?")->execute([$data->order_id]);
            echo json_encode(["message" => "GRN recorded"]);
        }
        break;

    case 'invoice_create':
        if ($method === 'POST') {
            $requireSeniorPharmacy();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->order_id) || empty($data->invoice_number)) {
                http_response_code(400);
                echo json_encode(["message" => "Order and invoice number required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO purchase_invoices (order_id, invoice_number, amount, invoice_date, status)
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data->order_id, $data->invoice_number, $data->amount ?? 0, $data->invoice_date ?? date('Y-m-d'), $data->status ?? 'Open']);
            $db->prepare("UPDATE purchase_orders SET status = 'Invoiced' WHERE id = ?")->execute([$data->order_id]);
            echo json_encode(["message" => "Invoice recorded"]);
        }
        break;

    // 13. QUARANTINE / RETURNS
    case 'quarantine_add':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->medicine_id) || empty($data->reason)) {
                http_response_code(400);
                echo json_encode(["message" => "Medicine and reason required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO quarantine_batches (medicine_id, batch_number, quantity, reason, status)
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data->medicine_id, $data->batch_number ?? null, $data->quantity ?? 0, $data->reason, $data->status ?? 'Quarantined']);
            echo json_encode(["message" => "Quarantine recorded"]);
        }
        break;

    case 'quarantine_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT q.*, m.name as med_name
                                FROM quarantine_batches q
                                LEFT JOIN medicines m ON q.medicine_id = m.id
                                ORDER BY q.created_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'quarantine_update':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->id) || empty($data->status)) {
                http_response_code(400);
                echo json_encode(["message" => "Entry and status required"]);
                exit;
            }
            $stmt = $db->prepare("UPDATE quarantine_batches SET status = ? WHERE id = ?");
            $stmt->execute([$data->status, $data->id]);
            echo json_encode(["message" => "Quarantine updated"]);
        }
        break;

    // 14. STOCK ADJUSTMENTS
    case 'adjustment_add':
        if ($method === 'POST') {
            $requireSeniorPharmacy();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->medicine_id) || !isset($data->adjustment) || empty($data->reason)) {
                http_response_code(400);
                echo json_encode(["message" => "Medicine, adjustment, and reason required"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO stock_adjustments (medicine_id, adjustment, reason, adjusted_by)
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$data->medicine_id, $data->adjustment, $data->reason, $data->adjusted_by ?? null]);
            $db->prepare("UPDATE medicines SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$data->adjustment, $data->medicine_id]);
            echo json_encode(["message" => "Stock adjusted"]);
        }
        break;

    case 'adjustment_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT a.*, m.name as med_name
                                FROM stock_adjustments a
                                LEFT JOIN medicines m ON a.medicine_id = m.id
                                ORDER BY a.created_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    // 15. INSURANCE CLAIMS
    case 'claimable':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT p.id, p.patient_id, pat.full_name as patient_name,
                                       COALESCE(m.name, p.notes) as med_name,
                                       p.status, p.created_at
                                FROM prescriptions p
                                JOIN patients pat ON p.patient_id = pat.id
                                LEFT JOIN medicines m ON p.medicine_id = m.id
                                WHERE p.status IN ('Pending','External','Dispensed')
                                AND pat.has_medical_aid = 1
                                ORDER BY p.created_at DESC
                                LIMIT 100");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'claim_create':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->prescription_id) || !isset($data->patient_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Prescription and patient required"]);
                exit;
            }
            $aid = $db->prepare("SELECT has_medical_aid FROM patients WHERE id = ?");
            $aid->execute([$data->patient_id]);
            if ((int)$aid->fetchColumn() !== 1) {
                http_response_code(400);
                echo json_encode(["message" => "Patient does not have medical aid"]);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO insurance_claims (prescription_id, patient_id, status, submitted_by, submitted_at, notes)
                                  VALUES (?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([$data->prescription_id, $data->patient_id, $data->status ?? 'Submitted', $data->submitted_by ?? null, $data->notes ?? null]);
            echo json_encode(["message" => "Claim submitted"]);
        }
        break;

    case 'claim_list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT c.*, pat.full_name as patient_name,
                                       COALESCE(m.name, p.notes) as med_name
                                FROM insurance_claims c
                                JOIN prescriptions p ON c.prescription_id = p.id
                                JOIN patients pat ON c.patient_id = pat.id
                                LEFT JOIN medicines m ON p.medicine_id = m.id
                                ORDER BY c.submitted_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }
        break;

    case 'claim_update':
        if ($method === 'POST') {
            $requireSeniorPharmacy();
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->claim_id) || empty($data->status)) {
                http_response_code(400);
                echo json_encode(["message" => "Claim and status required"]);
                exit;
            }
            $stmt = $db->prepare("UPDATE insurance_claims SET status = ? WHERE id = ?");
            $stmt->execute([$data->status, $data->claim_id]);
            echo json_encode(["message" => "Claim updated"]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Pharmacy endpoint not found"]);
        break;
}
?>
