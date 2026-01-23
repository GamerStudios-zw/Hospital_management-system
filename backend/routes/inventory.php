<?php
// backend/routes/inventory.php

include_once 'controllers/InventoryController.php';
include_once 'middleware/AuthMiddleware.php';
include_once 'middleware/RoleMiddleware.php';

$controller = new InventoryController();
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();

switch ($action) {
    // /inventory/list
    case 'list':
        if ($method === 'GET') {
            // Doctors and Pharmacists need to see list
            RoleMiddleware::allow(['admin', 'pharmacist', 'doctor'], $user);
            $controller->listInventory();
        }
        break;

    // /inventory/add
    case 'add':
        if ($method === 'POST') {
            // Only Pharmacists update stock
            RoleMiddleware::allow(['admin', 'pharmacist'], $user);
            $controller->addMedicine();
        }
        break;

    // /inventory/search?q=aspirin
    case 'search':
        if ($method === 'GET') {
            RoleMiddleware::allow(['admin', 'pharmacist', 'doctor'], $user);
            $controller->searchMedicine();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Inventory endpoint not found"]);
        break;
}
?>