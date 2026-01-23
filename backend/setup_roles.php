<?php
// FILE: backend/setup_roles.php
require_once 'config/database.php';

echo "<h2>🏥 Setting up Staff Accounts...</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Generate the hash for 'admin123'
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // 2. Define the Users to Create
    $staff_members = [
        [
            'username' => 'nurse1',
            'email' => 'nurse@hospital.com',
            'role' => 'nurse',
            'name' => 'Sister Betty'
        ],
        [
            'username' => 'rec1',
            'email' => 'reception@hospital.com',
            'role' => 'receptionist',
            'name' => 'John Frontdesk'
        ],
        [
            'username' => 'pharm1',
            'email' => 'pharmacy@hospital.com',
            'role' => 'pharmacist',
            'name' => 'Mike Meds'
        ]
    ];

    foreach ($staff_members as $staff) {
        // A. Delete if exists (Clean Slate)
        $del = $db->prepare("DELETE FROM users WHERE username = ?");
        $del->execute([$staff['username']]);

        // B. Insert New
        $sql = "INSERT INTO users (username, email, password_hash, full_name, role, is_active)
                VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $staff['username'],
            $staff['email'],
            $hash,
            $staff['name'],
            $staff['role']
        ]);

        echo "✅ Created <b>" . ucfirst($staff['role']) . "</b>: User = <code>" . $staff['username'] . "</code> / Pass = <code>admin123</code><br>";
    }

    echo "<h3>🚀 Roles Configured!</h3>";
    echo "<a href='../frontend/pages/auth/login.html'>Go to Login Page</a>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>