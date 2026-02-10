<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$dbOk = false;
$dbError = null;

try {
    $database = new Database();
    $conn = $database->getConnection();
    if ($conn) {
        $dbOk = true;
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$wsHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
$wsPort = 8090;
$wsOk = false;
$wsError = null;
try {
    $socket = @fsockopen($wsHost, $wsPort, $errno, $errstr, 1.5);
    if ($socket) {
        $wsOk = true;
        fclose($socket);
    } else {
        $wsError = $errstr ?: ('Connection failed (' . $errno . ')');
    }
} catch (Throwable $e) {
    $wsError = $e->getMessage();
}

echo json_encode([
    'status' => 'active',
    'version' => '1.0.0',
    'server_time' => date('c'),
    'db' => [
        'ok' => $dbOk,
        'error' => $dbError
    ],
    'realtime' => [
        'host' => $wsHost,
        'port' => $wsPort,
        'ok' => $wsOk,
        'error' => $wsError
    ]
]);
