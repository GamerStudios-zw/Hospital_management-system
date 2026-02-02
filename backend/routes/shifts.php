<?php
// FILE: backend/routes/shifts.php

require_once __DIR__ . '/../controllers/ShiftController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

$controller = new ShiftController();
$action = $segments[1] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure the user is logged in before accessing any shift data
$user = AuthMiddleware::isAuthenticated();

switch ($action) {
    case 'add':
        if ($method === 'POST') {
            // Only 'admin' is allowed to create and assign shifts
            RoleMiddleware::allow(['admin'], $user);
            $controller->createShift();
        }
        break;

    case 'list':
        if ($method === 'GET') {
            // Admin needs to see the full roster for all departments
            $controller->getAllShifts();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Shift endpoint not found."]);
        break;
}