<?php
// FILE: backend/routes/nurse.php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

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
            "pending_triage" => (int)$pending,
            "occupancy" => "$occ/$total"
        ]);
        break;

    // 2. WARD ANALYTICS (Matches Dashboard Header)
    case 'ward_stats':
        $admits = $db->query("SELECT COUNT(*) FROM patient_queue WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $discharged = $db->query("SELECT COUNT(*) FROM patient_queue WHERE status = 'Completed' AND DATE(updated_at) = CURDATE()")->fetchColumn();

        echo json_encode([
            "admits_today" => (int)$admits,
            "discharged_today" => (int)$discharged
        ]);
        break;

    // 3. GET TRIAGE QUEUE (Reception -> Nurse)
    case 'triage_queue':
        if ($method === 'GET') {
            $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.national_id, p.dob, p.gender, q.created_at, q.status
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      WHERE q.status = 'Waiting'
                      ORDER BY q.created_at ASC";
            $stmt = $db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 4. SAVE VITALS (Nurse -> Doctor Flow)
    case 'save_vitals':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if(!isset($data->queue_id) || !isset($data->patient_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Missing Patient or Queue ID"]);
                exit;
            }

            try {
                $db->beginTransaction();

                // MATCHING YOUR SQL: patient_vitals (patient_id, queue_id, temperature, pulse, bp, weight, spo2, notes)
                $sql = "INSERT INTO patient_vitals (patient_id, queue_id, temperature, pulse, bp, weight, spo2, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $data->patient_id,
                    $data->queue_id,
                    $data->temperature,
                    $data->pulse,
                    $data->bp,
                    $data->weight,
                    $data->spo2,
                    $data->notes
                ]);

                // Update status to 'In Triage' so the patient appears for the Doctor
                $upd = $db->prepare("UPDATE patient_queue SET status = 'In Triage' WHERE id = ?");
                $upd->execute([$data->queue_id]);

                $db->commit();
                echo json_encode(["message" => "Vitals Saved. Patient routed to Doctor."]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Database Error: " . $e->getMessage()]);
            }
        }
        break;

    // 5. BED MANAGEMENT
    case 'beds':
        $query = "SELECT b.*, p.full_name as patient_name, p.national_id
                  FROM beds b
                  LEFT JOIN patients p ON b.current_patient_id = p.id
                  ORDER BY b.ward_name, b.bed_number";
        echo json_encode($db->query($query)->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'discharge':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            $sql = "UPDATE beds SET status = 'Cleaning', current_patient_id = NULL, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            if($stmt->execute([$data->bed_id])) echo json_encode(["message" => "Discharged"]);
        }
        break;

    case 'mark_clean':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            $sql = "UPDATE beds SET status = 'Available', updated_at = NOW() WHERE id = ?";
            if($db->prepare($sql)->execute([$data->bed_id])) echo json_encode(["message" => "Ready"]);
        }
        break;

    case 'shifts':
        $stmt = $db->query("SELECT * FROM nurse_shifts ORDER BY shift_start ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Nurse action not found"]);
        break;
}
?>