<?php
// backend/routes/doctor.php

include_once 'controllers/ConsultationController.php';
include_once 'controllers/PrescriptionController.php';
include_once 'middleware/AuthMiddleware.php';
include_once 'middleware/RoleMiddleware.php';

$consultController = new ConsultationController();
$prescController = new PrescriptionController();

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();

// Mostly for Doctors
RoleMiddleware::allow(['admin', 'doctor'], $user);

switch ($action) {
    // /doctor/start
    case 'start':
        if ($method === 'POST') {
            $consultController->startConsultation();
        }
        break;

    // /doctor/vitals?visit_id=1
    case 'vitals':
        if ($method === 'GET') {
            $consultController->getPatientVitals();
        }
        break;

    // /doctor/prescribe
    case 'prescribe':
        if ($method === 'POST') {
            $prescController->addPrescription();
        }
        break;

    // /doctor/end
    case 'end':
        if ($method === 'PUT') {
            $consultController->endConsultation();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Doctor endpoint not found"]);
        break;
}
?>