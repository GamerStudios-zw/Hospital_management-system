<?php
// FILE: backend/routes/pharmacy.php

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Inherit $segments from index.php
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // 1. DASHBOARD STATS (Low Stock + Pending Count)
    case 'stats':
        // Count Pending Prescriptions
        $stmt = $db->query("SELECT COUNT(*) as count FROM prescriptions WHERE status = 'Pending'");
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get Low Stock Items (< 20)
        $stmt = $db->query("SELECT COUNT(*) as count FROM medicines WHERE stock_quantity < 20");
        $lowStock = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo json_encode(["pending" => $pending, "low_stock" => $lowStock]);
        break;

    // 2. FETCH PENDING PRESCRIPTIONS
    case 'pending':
        if ($method === 'GET') {
            $query = "SELECT p.id, p.quantity, p.dosage, p.created_at,
                             pat.full_name as patient_name,
                             med.name as med_name, med.stock_quantity
                      FROM prescriptions p
                      JOIN patients pat ON p.patient_id = pat.id
                      JOIN medicines med ON p.medicine_id = med.id
                      WHERE p.status = 'Pending'
                      ORDER BY p.created_at ASC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 3. DISPENSE MEDICATION
    case 'dispense':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if(!isset($data->prescription_id)) { http_response_code(400); exit; }

            try {
                $db->beginTransaction();

                // A. Get Prescription Details
                $stmt = $db->prepare("SELECT medicine_id, quantity FROM prescriptions WHERE id = ?");
                $stmt->execute([$data->prescription_id]);
                $presc = $stmt->fetch(PDO::FETCH_ASSOC);

                if(!$presc) throw new Exception("Prescription not found");

                // B. Deduct Stock
                $updateStock = $db->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
                $res = $updateStock->execute([$presc['quantity'], $presc['medicine_id'], $presc['quantity']]);

                if($updateStock->rowCount() == 0) {
                    throw new Exception("Insufficient stock!");
                }

                // C. Mark Prescription as Dispensed
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

    // 4. LOW STOCK ALERT LIST
    case 'low_stock':
        $stmt = $db->query("SELECT name, stock_quantity FROM medicines WHERE stock_quantity < 20");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Pharmacy endpoint not found"]);
        break;
}
?>