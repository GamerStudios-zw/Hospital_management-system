<?php
function tryConnect($dsn, $user='root', $pass=''){
    try {
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->query('SELECT DATABASE() AS db');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "SUCCESS (" . $dsn . "): " . ($row['db'] ?? 'NULL') . "\n";
    } catch (PDOException $e) {
        echo "ERROR (" . $dsn . "): " . $e->getMessage() . "\n";
    }
}

tryConnect('mysql:host=localhost;dbname=hospital_db');
tryConnect('mysql:host=127.0.0.1;dbname=hospital_db');
tryConnect('mysql:host=127.0.0.1;port=3306;dbname=hospital_db');
tryConnect('mysql:host=localhost;port=3306;dbname=hospital_db');
tryConnect('mysql:host=127.0.0.1');
tryConnect('mysql:host=localhost');

// Try issuing a USE statement after connecting without dbname
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "\nAttempting USE hospital_db after connecting without dbname...\n";
    $pdo->exec('USE hospital_db');
    $stmt = $pdo->query('SELECT DATABASE() AS db');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "USE succeeded. Current DB: " . ($row['db'] ?? 'NULL') . "\n";
} catch (PDOException $e) {
    echo "USE failed: " . $e->getMessage() . "\n";
}
