<?php
// FILE: backend/index.php

// 1. CORS Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. Load Dependencies
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

require_once 'config/database.php';

// 4. Router Logic
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];

// Fix URI parsing to get the correct module
if (strpos($uri, $scriptName) === 0) {
    $requestUri = substr($uri, strlen($scriptName));
} elseif (strpos($uri, dirname($scriptName)) === 0) {
    $requestUri = substr($uri, strlen(dirname($scriptName)));
} else {
    $requestUri = $uri;
}

$requestUri = trim($requestUri, '/');
$segments = explode('/', $requestUri);
$module = isset($segments[0]) ? $segments[0] : '';

// 5. Route Switcher
switch ($module) {
    case 'auth':
        require_once 'routes/auth.php';
        break;

    case 'users':
        require_once 'routes/users.php';
        break;

    case 'reception':
        require_once 'routes/reception.php';
        break;

    case 'doctor':
        require_once 'routes/doctor.php';
        break;

    case 'patients':
        require_once 'routes/patients.php';
        break;

    case 'nurse':
        require_once 'routes/nurse.php';
        break;

    case 'logs':
    require_once 'routes/logs.php';
    break;

    case 'pharmacy':
        require_once 'routes/pharmacy.php';
        break;

    case 'inventory':
        require_once 'routes/inventory.php';
        break;

    // This now correctly points to the new file we just made
    case 'reports':
        require_once 'routes/reports.php';
        break;

    case 'logs':
        require_once 'routes/logs.php';
        break;

    case '':
    case 'health':
        echo json_encode(["status" => "active", "message" => "API Running"]);
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found: " . $module]);
        break;
}
?>