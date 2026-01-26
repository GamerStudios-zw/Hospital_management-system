<?php
// FILE: backend/install.php

// 1. Database Credentials (EDIT IF NEEDED)
$host = "localhost";
$db_name = "hospital_db"; // Make sure this matches their DB name
$username = "root";
$password = "";

echo "<body style='font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f4f6f9;'>";
echo "<div style='background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #0d6efd; text-align: center;'>🏥 HMS Quick Installer</h1>";

try {
    // 2. Connect to MySQL (Create DB if not exists)
    $conn = new PDO("mysql:host=" . $host, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $conn->exec("USE `$db_name`");
    echo "<p style='color: green;'>✅ Database connected successfully.</p>";

    // 3. Create Users Table
    $sql_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin','doctor','nurse','receptionist','pharmacist') NOT NULL,
        phone VARCHAR(20),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql_table);
    echo "<p style='color: green;'>✅ Users Table checked/created.</p>";

    // 4. Generate Password Hash
    // We generate it HERE so it works on their specific computer/PHP version
    $common_pass = 'admin123';
    $hash = password_hash($common_pass, PASSWORD_BCRYPT);

    // 5. Define Users
    $users = [
        ['admin',  'admin@hms.com',     'System Administrator', 'admin'],
        ['doc1',   'doctor@hms.com',    'Dr. Sarah Moyo',       'doctor'],
        ['nurse1', 'nurse@hms.com',     'Sister Betty',         'nurse'],
        ['rec1',   'reception@hms.com', 'John Frontdesk',       'receptionist'],
        ['pharm1', 'pharmacy@hms.com',  'Mike Meds',            'pharmacist']
    ];

    // 6. Insert Users
    echo "<h3>Creating Accounts...</h3><ul style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";

    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");

    foreach ($users as $u) {
        $stmt->execute([$u[0], $u[1], $hash, $u[2], $u[3]]);
        echo "<li style='margin-bottom: 10px; list-style: none;'>";
        echo "👤 <b>" . ucfirst($u[3]) . ":</b> Username: <code>" . $u[0] . "</code> / Pass: <code>$common_pass</code>";
        echo "</li>";
    }
    echo "</ul>";

    echo "<h2 style='color: green; text-align: center; margin-top: 30px;'>🚀 Installation Complete!</h2>";
    echo "<div style='text-align: center;'><a href='../frontend/pages/auth/login.html' style='background: #0d6efd; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Go to Login Page</a></div>";

} catch(PDOException $e) {
    echo "<div style='color: red; background: #fee2e2; padding: 15px; border-radius: 8px;'>";
    echo "<h3>❌ Error</h3>";
    echo "Connection failed: " . $e->getMessage();
    echo "<br><br><b>Hint:</b> Check if your XAMPP/WAMP MySQL is running and password is empty.";
    echo "</div>";
}

echo "</div></body>";
?>