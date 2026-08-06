<?php
// FILE: backend/routes/wards.php
require_once __DIR__ . '/../controllers/WardController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

$controller = new WardController();
$action = $segments[1] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();

switch ($action) {
    case 'add':
        if ($method === 'POST') {
            RoleMiddleware::allow(['admin'], $user);
            $controller->create();
        }
        break;

    default:
        // Handle GET list or single, PUT update, DELETE delete when action is empty
        if ($method === 'GET') {
            $controller->listOrGet();
            return;
        }
        if ($method === 'PUT') {
            $controller->update();
            return;
        }
        if ($method === 'DELETE') {
            $controller->delete();
            return;
        }
        http_response_code(404);
        echo json_encode(["message" => "Wards endpoint not found"]);
        break;
}
