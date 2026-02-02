<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn instanceof PDO) {
    echo "Connected to MySQL.\n";
    try {
        $stmt = $conn->query("SELECT DATABASE() AS db");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Current database: " . ($row['db'] ?? 'NULL') . "\n";
    } catch (Exception $e) {
        echo "Query error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Connection returned null. Check error messages above.\n";
}
