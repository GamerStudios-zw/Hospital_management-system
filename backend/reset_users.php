<?php
// FILE: backend/reset_users.php
require_once 'config/database.php';

echo "<h2>🛠️ Fixing User Accounts...</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();

    // The KNOWN WORKING hash for password 'admin123'
    $hash = '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa';

    // List of users to fix
    $users = [
        // Username,  Email,               Role
        ['doc1',     'doc@test.com',      'nurse_in_charge'],
        ['nurse1',   'nurse@test.com',    'nurse'],
        ['rec1',     'rec@test.com',      'receptionist'],
        ['pharm1',   'pharm@test.com',    'pharmacist']
    ];

    foreach ($users as $u) {
        $username = $u[0];
        $email = $u[1];
        $role = $u[2];

        // 1. Delete the user if they exist (to clear bad data)
        $stmt = $db->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$username]);

        // 2. Insert the user fresh with the correct hash
        $sql = "INSERT INTO users (username, email, password_hash, full_name, role, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $email, $hash, ucfirst($role) . " Test User", $role]);

        echo "✅ Reset User: <b>$username</b> (Role: $role)<br>";
    }

    echo "<h3>🚀 SUCCESS! All passwords are now: <code>admin123</code></h3>";
    echo "<a href='../frontend/pages/auth/login.html'>Go to Login Page</a>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
