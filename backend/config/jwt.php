<?php
// FILE: backend/config/jwt.php

class JwtConfig {
    // NOTE: HS256 requires a sufficiently long secret key. Replace with a secure value in production.
    public static $secret_key = "change_this_to_a_long_secure_secret_key_please_2026_!@#";
    public static $issuer = "http://localhost";
    public static $audience = "http://localhost";
    public static $algorithm = 'HS256';
    public static $expiration_time = 3600; // 1 hr
}
?>