<?php
require '../vendor/autoload.php';
include_once '../config/jwt.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {

    public static function isAuthenticated() {
        $headers = apache_request_headers();
        $jwt = null;

        // 1. Get token from Authorization header (Bearer <token>)
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            $arr = explode(" ", $authHeader);
            $jwt = isset($arr[1]) ? $arr[1] : null;
        }

        if ($jwt) {
            try {
                // 2. Decode and Validate the token
                $decoded = JWT::decode($jwt, new Key(JwtConfig::$secret_key, JwtConfig::$algorithm));

                // Return user data (ID and Role) to be used by the controller
                return $decoded->data;

            } catch (Exception $e) {
                // Token invalid or expired
                http_response_code(401);
                echo json_encode(array("message" => "Access denied. Invalid or expired token.", "error" => $e->getMessage()));
                exit();
            }
        } else {
            // No token found
            http_response_code(401);
            echo json_encode(array("message" => "Access denied. No token provided."));
            exit();
        }
    }
}
?>