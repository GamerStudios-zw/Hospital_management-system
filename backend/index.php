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
require_once 'utils/DbSchema.php';

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

// 4.5 Maintenance Mode Gate
try {
    $db = (new Database())->getConnection();
    DbSchema::ensureUserRole($db, 'it_support');
    if ($db) {
        $stmt = $db->prepare("SELECT maintenance_mode FROM system_settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $maintenance = false;
        if ($row && array_key_exists('maintenance_mode', $row)) {
            $maintenance = ((int)$row['maintenance_mode'] === 1);
        }
        if ($maintenance) {
            $action = $segments[1] ?? '';
            $isAuthLogin = ($module === 'auth' && $action === 'login');
            $isAuthLogout = ($module === 'auth' && $action === 'logout');
            $isHealth = ($module === '' || $module === 'health');
            if (!$isAuthLogin && !$isAuthLogout && !$isHealth) {
                require_once __DIR__ . '/middleware/AuthMiddleware.php';
                require_once __DIR__ . '/middleware/RoleMiddleware.php';
                $user = AuthMiddleware::isAuthenticated();
                $role = strtolower(trim((string)($user->role ?? '')));
                if ($role !== 'admin') {
                    http_response_code(503);
                    echo json_encode(["message" => "System in maintenance mode."]);
                    exit();
                }
            }
        }
    }
} catch (Exception $e) {
    // If settings table is missing, skip maintenance gating.
}

// 5. Route Switcher
switch ($module) {
    case 'auth':
        require_once 'routes/auth.php';
        break;

    case 'admin':
        require_once 'routes/admin.php';
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
    
    case 'nurse_aid':
        require_once 'routes/nurse_aid.php';
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

    case 'shifts':
        require_once 'routes/shifts.php';
        break;

    case 'settings':
        require_once 'routes/settings.php';
        break;
    case 'it':
        require_once 'routes/it.php';
        break;

    case '':
    case 'health':
        require_once 'routes/health.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found: " . $module]);
        break;
}
?>
