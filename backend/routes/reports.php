<?php
// FILE: backend/routes/reports.php

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Expecting $segments from index.php router
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // 1. DASHBOARD STATS
    case 'stats':
        if ($method === 'GET') {
            // Count Prescriptions
            $stmt = $db->query("SELECT COUNT(*) as count FROM medical_reports WHERE report_type = 'Prescription'");
            $prescriptions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Count Referrals
            $stmt = $db->query("SELECT COUNT(*) as count FROM medical_reports WHERE report_type = 'Referral'");
            $referrals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Return JSON (Includes +140 offset as requested)
            echo json_encode([
                "prescriptions" => $prescriptions + 140,
                "avg_time" => "18m",
                "referrals" => $referrals
            ]);
        }
        break;

    // 2. GET REPORTS LIST
    case 'list':
        if ($method === 'GET') {
            // Join with Patients table to get the full name
            $query = "SELECT r.id, r.report_name, r.report_type, r.created_at, r.file_path, p.full_name
                      FROM medical_reports r
                      JOIN patients p ON r.patient_id = p.id
                      ORDER BY r.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 3. UPLOAD NEW REPORT
    case 'upload':
        if ($method === 'POST') {
            // A. Validation
            if (!isset($_FILES['report_file']) || !isset($_POST['patient_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing file or patient data"]);
                exit;
            }

            $patient_id = $_POST['patient_id'];
            $report_type = $_POST['report_type'];
            $report_name = $_POST['report_name'];
            $file = $_FILES['report_file'];

            // B. Prepare File Path
            // Naming convention: timestamp_originalName
            $fileName = time() . '_' . basename($file['name']);
            // Target: backend/uploads/reports/
            $targetDir = __DIR__ . '/../uploads/reports/';
            $targetFilePath = $targetDir . $fileName;

            // Create directory if it doesn't exist
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // C. Move File & Save to DB
            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {

                // We store the relative path ("uploads/reports/...") so the frontend can link to it
                $dbPath = 'uploads/reports/' . $fileName;

                $sql = "INSERT INTO medical_reports (patient_id, report_type, report_name, file_path) VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($sql);

                if ($stmt->execute([$patient_id, $report_type, $report_name, $dbPath])) {
                    echo json_encode(["message" => "File uploaded successfully"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Database save failed"]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "File upload failed"]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Reports endpoint not found"]);
        break;
}
?>