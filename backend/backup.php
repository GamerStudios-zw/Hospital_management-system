<?php
require_once 'config/database.php';

// Set headers to force download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="hms_users_backup_' . date('Y-m-d') . '.csv"');

$database = new Database();
$db = $database->getConnection();

// Fetch all users
$stmt = $db->query("SELECT id, username, email, full_name, role, created_at FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Open output stream
$output = fopen('php://output', 'w');

// Add Column Headers
fputcsv($output, array('ID', 'Username', 'Email', 'Full Name', 'Role', 'Date Created'));

// Add Data rows
foreach ($users as $user) {
    fputcsv($output, $user);
}

fclose($output);
exit();
?>