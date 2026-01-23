<?php
// FILE: backend/models/User.php

class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        // Find user by username OR email
        $query = "SELECT id, username, email, password_hash, full_name, role, phone
                  FROM " . $this->table . "
                  WHERE username = :username OR email = :username
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $username = htmlspecialchars(strip_tags($username));
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify Password
            if (password_verify($password, $row['password_hash'])) {
                // Return user info (Success)
                return $row;
            }
        }
        // Failure
        return false;
    }

    // Stub for findById to prevent crashes if called
    public function findById($id) {
        return null;
    }
}
?>