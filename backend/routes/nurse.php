<?php
// FILE: backend/routes/nurse.php

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// OPENING THE SWITCH
switch ($action) {

    // 1. NURSE DASHBOARD STATS
    case 'stats':
        $stmt = $db->query("SELECT COUNT(*) as count FROM patient_queue WHERE status = 'Waiting'");
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->query("SELECT COUNT(*) as occupied FROM beds WHERE status = 'Occupied'");
        $occ = $stmt->fetch(PDO::FETCH_ASSOC)['occupied'];

        $stmt = $db->query("SELECT COUNT(*) as total FROM beds");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode([
            "pending_triage" => $pending,
            "occupancy" => "$occ/$total",
            "alerts" => 0
        ]);
        break;

    // 2. GET TRIAGE QUEUE
    case 'triage_queue':
        if ($method === 'GET') {
            $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, q.created_at, q.status
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      WHERE q.status = 'Waiting'
                      ORDER BY q.created_at ASC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 3. SAVE VITALS
    case 'save_vitals':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if(!isset($data->patient_id)) { http_response_code(400); exit; }

            $sql = "INSERT INTO vital_signs (patient_id, temperature, blood_pressure, heart_rate, weight, oxygen_saturation, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $res = $stmt->execute([
                $data->patient_id, $data->temperature, $data->bp, $data->heart_rate,
                $data->weight, $data->spo2, $data->notes
            ]);

            if($res) {
                if(isset($data->queue_id)) {
                    $upd = $db->prepare("UPDATE patient_queue SET status = 'In Triage' WHERE id = ?");
                    $upd->execute([$data->queue_id]);
                }
                echo json_encode(["message" => "Vitals Saved Successfully"]);
            } else {
                http_response_code(500);
            }
        }
        break;

    // 4. GET WARD/BED STATUS
    case 'beds':
        $query = "SELECT b.*, p.full_name as patient_name
                  FROM beds b
                  LEFT JOIN patients p ON b.current_patient_id = p.id
                  ORDER BY b.ward_name, b.bed_number";
        $stmt = $db->prepare($query);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // 5. GET PATIENT LIST (For Dropdown)
    case 'patients':
        $stmt = $db->prepare("SELECT id, full_name FROM patients ORDER BY full_name ASC");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // 6. ADMIT PATIENT
    case 'admit':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            // Update Bed to Occupied
            $sql = "UPDATE beds SET status = 'Occupied', current_patient_id = ? WHERE id = ?";
            $stmt = $db->prepare($sql);

            if($stmt->execute([$data->patient_id, $data->bed_id])) {
                echo json_encode(["message" => "Patient Admitted Successfully"]);
            } else {
                http_response_code(500);
            }
        }
        break;

    // 7. GET SHIFT ROSTER
    case 'shifts':
        $stmt = $db->query("SELECT * FROM nurse_shifts ORDER BY shift_start ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // 8. DISCHARGE PATIENT (Occupied -> Cleaning)
    case 'discharge':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            // Set bed to 'Cleaning' and remove patient link
            $sql = "UPDATE beds SET status = 'Cleaning', current_patient_id = NULL WHERE id = ?";
            $stmt = $db->prepare($sql);

            if($stmt->execute([$data->bed_id])) {
                echo json_encode(["message" => "Patient Discharged. Bed marked for Cleaning."]);
            } else {
                http_response_code(500);
            }
        }
        break;

    // 9. MARK BED CLEAN (Cleaning -> Available)
    case 'mark_clean':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            $sql = "UPDATE beds SET status = 'Available' WHERE id = ?";
            $stmt = $db->prepare($sql);

            if($stmt->execute([$data->bed_id])) {
                echo json_encode(["message" => "Bed is now Available."]);
            } else {
                http_response_code(500);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Nurse endpoint not found"]);
        break;
}
// CLOSING THE SWITCH HERE
?>