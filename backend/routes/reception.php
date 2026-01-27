<?php
// FILE: backend/routes/reception.php

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// FIX: Do not recalculate $uri or $segments here.
// We inherit them correctly from index.php
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // 1. DASHBOARD STATS & PATIENT FLOW
    case 'stats':
        $today = date('Y-m-d');
        $stats = [];

        // A. Count Patients Registered/Admitted Today
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM patient_queue WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // B. Get Patient Flow (The Table Data)
        $flowQuery = "SELECT
                        q.id,
                        q.status,
                        q.created_at,
                        q.doctor_assigned,
                        p.full_name
                      FROM patient_queue q
                      JOIN patients p ON q.patient_id = p.id
                      WHERE DATE(q.created_at) = ?
                      ORDER BY q.created_at DESC
                      LIMIT 10";

        $stmt = $db->prepare($flowQuery);
        $stmt->execute([$today]);
        $stats['flow'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($stats);
        break;

    // 2. SEARCH PATIENTS (This is what was failing)
    case 'search':
        $q = $_GET['q'] ?? '';
        // Allow searching even with 1 letter for testing, but typically 2 is better
        if(strlen($q) < 1) { echo json_encode([]); exit; }

        // Search by name (case insensitive usually in SQL)
        $stmt = $db->prepare("SELECT id, full_name, dob, gender, phone FROM patients WHERE full_name LIKE ? LIMIT 5");
        $stmt->execute(["%$q%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // 3. REGISTER NEW PATIENT
    case 'register':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if(!isset($data->full_name)) { http_response_code(400); exit; }

            $sql = "INSERT INTO patients (full_name, dob, gender, phone, address, has_medical_aid, medical_aid_provider, medical_aid_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $res = $stmt->execute([
                $data->full_name, $data->dob, $data->gender, $data->phone,
                $data->address, $data->has_medical_aid, $data->medical_aid_provider, $data->medical_aid_number
            ]);

            if($res) echo json_encode(["message" => "Patient Registered", "id" => $db->lastInsertId()]);
            else http_response_code(500);
        }
        break;

    // 4. ADMIT PATIENT (Send to Queue)
    case 'admit':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            $sql = "INSERT INTO patient_queue (patient_id, doctor_assigned, payment_method, status) VALUES (?, ?, ?, 'Waiting')";
            $stmt = $db->prepare($sql);

            if($stmt->execute([$data->patient_id, $data->doctor, $data->payment])) {
                echo json_encode(["message" => "Patient Sent to Nurse/Triage"]);
            } else {
                http_response_code(500);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found: " . $action]);
        break;
}
?>