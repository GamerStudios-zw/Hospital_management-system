<?php
// FILE: backend/routes/pharmacy.php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

switch ($action) {

    // 1. GET PENDING PRESCRIPTIONS (Matches loadPending() in dashboard)
    case 'pending':
        if ($method === 'GET') {
            // Use LEFT JOIN so manual/external medicines still show up
            $query = "SELECT p.id, p.quantity, p.dosage, p.created_at, p.notes as manual_name,
                             pat.full_name as patient_name, pat.medical_aid_number,
                             med.name as med_name, med.stock_quantity,
                             q.doctor_assigned
                      FROM prescriptions p
                      JOIN patients pat ON p.patient_id = pat.id
                      LEFT JOIN medicines med ON p.medicine_id = med.id
                      JOIN patient_queue q ON p.patient_id = q.patient_id
                      WHERE p.status IN ('Pending', 'External')
                      AND q.status = 'Completed'
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

    default:
        http_response_code(404);
        echo json_encode(["message" => "Pharmacy endpoint not found"]);
        break;
}
?>