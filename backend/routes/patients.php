<?php
// backend/routes/patients.php

include_once 'controllers/PatientController.php';
include_once 'middleware/AuthMiddleware.php';
include_once 'middleware/RoleMiddleware.php';

$controller = new PatientController();
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

// All patient routes require login
$user = AuthMiddleware::isAuthenticated();

switch ($action) {
    case 'register':
        if ($method === 'POST') {
            // Only Receptionists or Admins can register patients
            RoleMiddleware::allow(['admin', 'receptionist'], $user);
            $controller->registerPatient();
        }
        break;

    // Example: /patients/search?q=John
    case 'search':
        if ($method === 'GET') {
            // Any logged in staff can search
            // $controller->searchPatient(); // Assuming you add this method later
            echo json_encode(["message" => "Search functionality pending implementation"]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Patient endpoint not found"]);
        break;
}
?>