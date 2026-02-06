<?php
/**
 * HOSPITAL MANAGEMENT SYSTEM - API ENTRY POINT
 */

// 1. HEADER CONFIGURATION
// Allow access from any origin (or specify your frontend URL http://localhost:5500)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. HANDLE PREFLIGHT REQUESTS (CORS)
// Browsers send an "OPTIONS" request before POST/PUT to check security. We must answer "OK".
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. LOAD DEPENDENCIES
// Use Composer's autoloader for JWT and other libraries
require_once 'vendor/autoload.php';

// Load Database Config (Available globally)
require_once 'config/database.php';

// 4. URL PARSING (THE ROUTER)
// This breaks down the URL: /backend/index.php/patients/add -> ['patients', 'add']
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME']; // /backend/index.php
$requestUri = str_replace(dirname($scriptName), '', $uri); // Remove base path
$requestUri = trim($requestUri, '/'); // Remove trailing slashes
$segments = explode('/', $requestUri);

// The first segment tells us which module we need (e.g., 'auth', 'patients', 'doctor')
$module = isset($segments[0]) ? $segments[0] : '';

// 5. TRAFFIC DIRECTION
// We delegate the actual logic to the specific file in the /routes/ folder.
switch ($module) {
    case 'auth':
        require_once 'routes/auth.php';
        break;

    case 'patients':
        require_once 'routes/patients.php';
        break;

    case 'queue':
        require_once 'routes/queue.php';
        break;

    case 'doctor':
        require_once 'routes/doctor.php';
        break;

    case 'pharmacy':
        require_once 'routes/pharmacy.php';
        break;

    case 'inventory':
        require_once 'routes/inventory.php';
        break;

    case 'nurse':
        require_once 'routes/nurse.php';
        break;
    case 'nurse_aid':
        require_once 'routes/nurse_aid.php';
        break;

    // Health Check (To test if API is alive)
    case '':
    case 'health':
        echo json_encode(["status" => "active", "message" => "Hospital API is running."]);
        break;

    default:
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Endpoint not found."]);
        break;
}
?>
