<?php
// FILE: backend/routes/auth.php

require_once __DIR__ . '/../controllers/AuthController.php';

$controller = new AuthController();

// $segments comes from index.php. $segments[0] is 'auth', $segments[1] is 'login'
$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'login':
        if ($method === 'POST') {
            $controller->login();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method Not Allowed"]);
        }
        break;

    case 'logout':
        if ($method === 'POST') $controller->logout();
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Auth Action Not Found"]);
        break;
}
?>