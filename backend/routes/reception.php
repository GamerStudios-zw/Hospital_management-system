<?php
// FILE: backend/routes/reception.php

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure JSON header is set to prevent "Unexpected token <" errors in frontend
header('Content-Type: application/json');

switch ($action) {

    // 1. DASHBOARD STATS (Matches loadDashboardStats() in dashboard.html)
    case 'stats':
        if ($method === 'GET') {
            try {
                // Count today's total visits
                $stmt = $db->query("SELECT COUNT(*) FROM patient_queue WHERE DATE(created_at) = CURDATE()");
                $today = $stmt->fetchColumn();

                // Count Pending Triage (Waiting status)
                $stmt = $db->query("SELECT COUNT(*) FROM patient_queue WHERE status = 'Waiting'");
                $pending = $stmt->fetchColumn();

                // Get Patient Flow for the current day
                $flowQuery = "SELECT q.created_at, p.full_name, q.doctor_assigned, q.status
                              FROM patient_queue q
                              JOIN patients p ON q.patient_id = p.id
                              WHERE DATE(q.created_at) = CURDATE()
                              ORDER BY q.created_at DESC";
                $flow = $db->query($flowQuery)->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    "today" => (int)$today,
                    "pending" => (int)$pending,
                    "flow" => $flow ?: []
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Stats Error: " . $e->getMessage()]);
            }
        }
        break;

    // 2. SEARCH PATIENTS (Matches searchPatient() in dashboard.html)
    case 'search':
        if ($method === 'GET') {
            $q = isset($_GET['q']) ? $_GET['q'] : '';
            if(strlen($q) < 2) { echo json_encode([]); exit; }

            $sql = "SELECT p.*,
                   (SELECT COUNT(*) FROM patient_queue q
                    WHERE q.patient_id = p.id
                    AND q.status NOT IN ('Completed', 'Cancelled')) as is_active
                    FROM patients p
                    WHERE p.full_name LIKE ? OR p.phone LIKE ? OR p.national_id LIKE ?
                    LIMIT 5";

            $stmt = $db->prepare($sql);
            $stmt->execute(["%$q%", "%$q%", "%$q%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 3. REGISTER NEW PATIENT
    case 'register':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            $sql = "INSERT INTO patients (
                        full_name, national_id, dob, gender, phone, address,
                        has_medical_aid, medical_aid_provider, medical_aid_number,
                        kin_name, kin_relation, kin_phone, allergies
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);

            $params = [
                $data->full_name,
                $data->national_id,
                $data->dob,
                $data->gender,
                $data->phone,
                $data->address,
                $data->has_medical_aid,
                $data->medical_aid_provider,
                $data->medical_aid_number,
                $data->kin_name,
                $data->kin_relation,
                $data->kin_phone,
                $data->allergies
            ];

            if($stmt->execute($params)) {
                echo json_encode(["message" => "Patient Registered Successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Database error during registration"]);
            }
        }
        break;

    // 4. ADMIT PATIENT (Simplified workflow)
    case 'admit':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));

            if (!isset($data->patient_id)) {
                http_response_code(400);
                echo json_encode(["message" => "Patient ID required"]);
                exit;
            }

            $check = $db->prepare("SELECT id FROM patient_queue WHERE patient_id = ? AND status NOT IN ('Completed', 'Cancelled')");
            $check->execute([$data->patient_id]);
            if($check->rowCount() > 0) {
                 http_response_code(400);
                 echo json_encode(["message" => "Patient is already active in the queue"]);
                 exit;
            }

            // Logic: Critical bypasses 'Waiting' and goes straight to 'With Doctor'
            $initial_status = ($data->is_critical) ? 'With Doctor' : 'Waiting';

            $sql = "INSERT INTO patient_queue (patient_id, doctor_assigned, status) VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);

            if($stmt->execute([$data->patient_id, $data->doctor, $initial_status])) {
                echo json_encode(["message" => "Patient Admitted"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Admission failed"]);
            }
        }
        break;

    // 5. GET ALL PATIENTS
    case 'all_patients':
        if ($method === 'GET') {
            $sql = "SELECT p.*,
                   (SELECT COUNT(*) FROM patient_queue q
                    WHERE q.patient_id = p.id
                    AND q.status NOT IN ('Completed', 'Cancelled')) as is_active
                    FROM patients p
                    ORDER BY p.created_at DESC LIMIT 50";

            $stmt = $db->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 6. GET RECEPTIONIST SHIFTS (Matches loadMyShifts() in dashboard.html)
    case 'shifts':
        if ($method === 'GET') {
            try {
                // Queries the staff_shifts table identified in your phpMyAdmin
                $query = "SELECT * FROM staff_shifts WHERE role = 'receptionist' ORDER BY shift_start ASC";
                $stmt = $db->query($query);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to load roster: " . $e->getMessage()]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Reception action not found"]);
        break;
}
?>