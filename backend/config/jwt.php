<?php
// FILE: backend/config/jwt.php

class JwtConfig {
    public static $secret_key = "YOUR_SECRET_KEY"; // In production, use env variable
    public static $issuer = "http://localhost";
    public static $audience = "http://localhost";
    public static $algorithm = 'HS256';
    public static $expiration_time = 3600; // 1 hr
}
?>