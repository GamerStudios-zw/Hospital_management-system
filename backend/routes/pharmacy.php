<?php
// backend/routes/pharmacy.php

include_once 'controllers/PharmacyController.php';
include_once 'controllers/PrescriptionController.php';
include_once 'middleware/AuthMiddleware.php';
include_once 'middleware/RoleMiddleware.php';

$pharmController = new PharmacyController();
$prescController = new PrescriptionController();

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['admin', 'pharmacist'], $user);

switch ($action) {
    // /pharmacy/prescriptions?visit_id=123
    case 'prescriptions':
        if ($method === 'GET') {
            $prescController->getByVisit();
        }
        break;

    // /pharmacy/dispense
    case 'dispense':
        if ($method === 'POST') {
            $pharmController->dispenseItem();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Pharmacy endpoint not found"]);
        break;
}
?>