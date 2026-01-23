<?php
require_once 'config/database.php';
echo "<h1>Resetting Admin User...</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Delete old admin
    $db->exec("DELETE FROM users WHERE username = 'admin'");

    // 2. Insert fresh admin with 'admin123'
    // Hash for 'admin123'
    $hash = '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa';

    $sql = "INSERT INTO users (username, email, password_hash, full_name, role)
            VALUES ('admin', 'admin@hospital.com', '$hash', 'System Admin', 'admin')";

    $db->exec($sql);

    echo "✅ <strong style='color:green'>SUCCESS!</strong><br>";
    echo "Username: <b>admin</b><br>";
    echo "Password: <b>admin123</b><br><br>";
    echo "<a href='../frontend/pages/auth/login.html'>Go to Login Page</a>";

} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>