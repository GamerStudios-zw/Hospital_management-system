<?php
// FILE: backend/routes/doctor.php

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// We inherit $segments from index.php
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // 1. DASHBOARD STATS
    case 'stats':
        $today = date('Y-m-d');

        // Waiting (Waiting + In Triage)
        $stmt = $db->query("SELECT COUNT(*) as count FROM patient_queue WHERE status IN ('Waiting', 'In Triage')");
        $waiting = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Attended Today (Completed today)
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM patient_queue WHERE status = 'Completed' AND DATE(created_at) = ?");
        $stmt->execute([$today]);
        $attended = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total Appointments Today
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM patient_queue WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo json_encode(["waiting" => $waiting, "attended" => $attended, "total" => $total]);
        break;

    // 2. FETCH APPOINTMENTS / QUEUE
    case 'appointments':
        if ($method === 'GET') {
            // Fetch active queue (Oldest first)
            $query = "SELECT q.id as queue_id, p.full_name, p.gender, p.dob, q.created_at, q.status, q.payment_method
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      WHERE q.status != 'Completed'
                      ORDER BY q.created_at ASC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 3. START CONSULTATION
    case 'start_consult':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            $stmt = $db->prepare("UPDATE patient_queue SET status = 'With Doctor' WHERE id = ?");
            if($stmt->execute([$data->queue_id])) echo json_encode(["message" => "Started"]);
        }
        break;

    // 4. COMPLETE CONSULTATION
    case 'complete_consult':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if(!isset($data->queue_id)) { http_response_code(400); exit; }

            $stmt = $db->prepare("UPDATE patient_queue SET status = 'Completed' WHERE id = ?");

            if($stmt->execute([$data->queue_id])) {
                echo json_encode(["message" => "Consultation Completed"]);
            }
        }
        break;

    // 5. MY PATIENTS LIST
    case 'patients':
        if ($method === 'GET') {
            $query = "SELECT p.id, p.full_name, p.phone, p.gender, q.status as queue_status, q.created_at as last_visit
                      FROM patients p
                      LEFT JOIN patient_queue q ON p.id = q.patient_id
                      ORDER BY p.id DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found"]);
        break;
}
?>