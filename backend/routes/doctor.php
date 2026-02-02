<?php
// FILE: backend/routes/doctor.php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // FILE: backend/routes/doctor.php

// ... inside switch ($action) ...

    case 'waiting_list':
        if ($method === 'GET') {
            // REMOVED 'Waiting' from the IN clause to ensure Nurse priority
            $query = "SELECT q.id as queue_id, p.id as patient_id, p.full_name, p.dob, p.gender, q.status,
                             v.bp, v.temperature, v.pulse, v.spo2
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      LEFT JOIN patient_vitals v ON q.id = v.queue_id
                      WHERE q.status IN ('In Triage', 'With Doctor')
                      ORDER BY q.created_at ASC";
            $stmt = $db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 2. COMPLETE VISIT WITH MULTIPLE/EXTERNAL PRESCRIPTIONS
    case 'complete_multiple':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->queue_id) || empty($data->notes)) {
                http_response_code(400);
                echo json_encode(["message" => "Queue ID and Clinical Notes are required."]);
                exit;
            }
            try {
                $db->beginTransaction();

                // Fetch Patient ID
                $stmt = $db->prepare("SELECT patient_id FROM patient_queue WHERE id = ?");
                $stmt->execute([$data->queue_id]);
                $patient_id = $stmt->fetchColumn();

                if(!empty($data->prescriptions)) {
                    // medicine_id is NULL for external items; name stored in 'notes' column
                    $sql = "INSERT INTO prescriptions (patient_id, medicine_id, quantity, dosage, notes, status)
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    foreach($data->prescriptions as $p) {
                        $status = $p->medicine_id ? 'Pending' : 'External';
                        $stmt->execute([
                            $patient_id,
                            $p->medicine_id ?: null,
                            $p->quantity,
                            $p->dosage,
                            $p->manual_name ?: null,
                            $status
                        ]);
                    }
                }

                // Close the visit in the queue
                $db->prepare("UPDATE patient_queue SET status = 'Completed' WHERE id = ?")
                   ->execute([$data->queue_id]);

                $db->commit();
                echo json_encode(["message" => "Consultation finalized successfully."]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
            }
        }
        break;

    // 3. GET PATIENT HISTORY (Vitals, Visits, and Prescriptions)
    case 'history':
        if ($method === 'GET') {
            $pid = $_GET['patient_id'] ?? 0;
            $history = [
                "patient" => $db->query("SELECT * FROM patients WHERE id = $pid")->fetch(PDO::FETCH_ASSOC),
                "vitals" => $db->query("SELECT * FROM patient_vitals WHERE patient_id = $pid ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC),
                "visits" => $db->query("SELECT * FROM patient_queue WHERE patient_id = $pid AND status = 'Completed' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC),
                "prescriptions" => $db->query("SELECT pr.*, m.name as medicine_name FROM prescriptions pr LEFT JOIN medicines m ON pr.medicine_id = m.id WHERE pr.patient_id = $pid ORDER BY pr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC),
                "files" => $db->query("SELECT * FROM medical_reports WHERE patient_id = $pid ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC)
            ];
            echo json_encode($history);
        }
        break;

    // 4. UPLOAD PATIENT DOCUMENTS
    case 'upload_file':
        if ($method === 'POST') {
            $patient_id = $_POST['patient_id'];
            $report_name = $_POST['report_name'];
            $target_dir = "../../uploads/patient_files/";

            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $file_name = time() . "_" . basename($_FILES["report_file"]["name"]);
            if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_dir . $file_name)) {
                $db->prepare("INSERT INTO medical_reports (patient_id, report_name, file_path) VALUES (?, ?, ?)")
                   ->execute([$patient_id, $report_name, "uploads/patient_files/" . $file_name]);
                echo json_encode(["message" => "Upload success"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "File upload failed"]);
            }
        }
        break;

    // 5. HELPER DATA
    case 'medicines':
        echo json_encode($db->query("SELECT id, name, stock_quantity FROM medicines WHERE stock_quantity > 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'patients':
        echo json_encode($db->query("SELECT * FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Action not found"]);
        break;
}
?>