<?php
// backend/routes/queue.php

require_once __DIR__ . '/../controllers/QueueController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

$controller = new QueueController();
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();

switch ($action) {
    case 'list':
        if ($method === 'GET') {
            // Everyone needs to see the queue
            $controller->getQueue();
        }
        break;

    case 'add':
        if ($method === 'POST') {
            // Receptionist adds to queue
            RoleMiddleware::allow(['admin', 'receptionist'], $user);
            $controller->addToQueue();
        }
        break;

    case 'update':
        if ($method === 'PUT') {
            // Nurse or Doctor updates status
            RoleMiddleware::allow(['admin', 'nurse', 'doctor'], $user);
            $controller->updateStatus();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Queue endpoint not found"]);
        break;
}
?>
