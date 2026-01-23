<?php
// backend/routes/nurse.php

include_once 'controllers/ConsultationController.php'; // or NurseController
include_once 'middleware/AuthMiddleware.php';
include_once 'middleware/RoleMiddleware.php';

$controller = new ConsultationController();
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();

// Only Nurses (and Admin) allowed here
RoleMiddleware::allow(['admin', 'nurse'], $user);

switch ($action) {
    case 'vitals':
        if ($method === 'POST') {
            // Logic to add vitals.
            // Ensure ConsultationController has an addVitals() method
            // or use a generic create method if available.

            // If you implemented addVitals in ConsultationController:
             // $controller->addVitals();

             // Placeholder response until method is added:
             http_response_code(501);
             echo json_encode(["message" => "Vitals submission endpoint ready. Implement addVitals() in Controller."]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Nurse endpoint not found"]);
        break;
}
?>