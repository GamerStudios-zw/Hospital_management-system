<?php
// FILE: backend/routes/reports.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/DbSchema.php';

$database = new Database();
$db = $database->getConnection();
DbSchema::ensureNurseInChargeRole($db, true);
DbSchema::ensureReferralRegistry($db);

// Expecting $segments from index.php router
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['doctor', 'nurse_in_charge', 'admin'], $user);

switch ($action) {

    // 1. DASHBOARD STATS
    case 'stats':
        if ($method === 'GET') {
            // Count Prescriptions
            $stmt = $db->query("SELECT COUNT(*) as count FROM medical_reports WHERE report_type = 'Prescription'");
            $prescriptions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Count Referrals
            $stmt = $db->query("SELECT COUNT(*) as count FROM medical_reports WHERE report_type = 'Referral'");
            $reportReferrals = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $refTableCount = (int)$db->query("SELECT COUNT(*) FROM referrals")->fetchColumn();
            $referrals = $reportReferrals + $refTableCount;

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
            // Include legacy medical reports and the new external referrals registry.
            $query = "SELECT x.id, x.report_name, x.report_type, x.created_at, x.file_path, x.full_name
                      FROM (
                          SELECT r.id,
                                 r.report_name,
                                 r.report_type,
                                 r.created_at,
                                 r.file_path,
                                 p.full_name
                          FROM medical_reports r
                          JOIN patients p ON r.patient_id = p.id

                          UNION ALL

                          SELECT rf.id,
                                 CONCAT('Referral: ', COALESCE(rf.external_provider_name, 'External Specialist')) AS report_name,
                                 'External Referral' AS report_type,
                                 COALESCE(rf.referral_date, rf.created_at) AS created_at,
                                 rf.attachment_path AS file_path,
                                 p2.full_name
                          FROM referrals rf
                          JOIN patients p2 ON rf.patient_id = p2.id
                      ) x
                      ORDER BY x.created_at DESC";

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
