<?php
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query('SHOW DATABASES');
    echo "Databases visible to PHP/PDO:\n";
    foreach ($stmt as $row) {
        echo $row[0] . "\n";
    }
} catch (PDOException $e) {
    echo "Connection/list error: " . $e->getMessage() . "\n";
}
