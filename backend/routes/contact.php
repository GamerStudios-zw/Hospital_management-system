<?php
require_once __DIR__ . '/../controllers/ContactController.php';

$controller = new ContactController();
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($segments[1]) ? $segments[1] : '';

// URL: /backend/index.php/contact/submit
if ($action === 'submit' && $method === 'POST') {
    $controller->submitInquiry();
} else {
    http_response_code(404);
    echo json_encode(["message" => "Contact endpoint not found"]);
}
?>
