<?php
// FILE: backend/controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
// JWT support for issuing tokens
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function login() {
        // 1. Get raw input
        $json = file_get_contents("php://input");
        $data = json_decode($json);

        // 2. Debug: Check if input is received
        if (!$data) {
            http_response_code(400);
            echo json_encode(["message" => "No JSON received", "debug_raw" => $json]);
            return;
        }

        $username = trim($data->username ?? '');
        $password = trim($data->password ?? '');

        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(["message" => "Empty username or password"]);
            return;
        }

        // 3. Direct Manual Database Check (Bypassing User.php to find the bug)
        // We run the query HERE to see exactly what is happening.
        $query = "SELECT * FROM users WHERE username = :u LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':u', $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // CHECK 1: Did we find the user?
        if (!$row) {
            http_response_code(401);
            echo json_encode([
                "message" => "Login Failed",
                "reason" => "User '$username' not found in database"
            ]);
            return;
        }

        // CHECK 2: Does the hash verify?
        if (password_verify($password, $row['password_hash'])) {
            // OPTIONAL: If you want to prevent login for inactive users, uncomment this block
            // and remove the code that auto-activates the user below.
            // if ((int)$row['is_active'] !== 1) {
            //     http_response_code(403);
            //     echo json_encode(["message" => "Account inactive. Contact administrator."]);
            //     return;
            // }

            // SUCCESS! Mark the user as active in the DB so the dashboard reflects current session state.
            try {
                $upd = $this->db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                $upd->execute([$row['id']]);
            } catch (Exception $e) {
                // Non-fatal: continue even if we couldn't update the flag
            }
            // Issue a JWT token containing user data
            try {
                $issuedAt = time();
                $expire = $issuedAt + JwtConfig::$expiration_time;
                $payload = [
                    'iss' => JwtConfig::$issuer,
                    'aud' => JwtConfig::$audience,
                    'iat' => $issuedAt,
                    'exp' => $expire,
                    'data' => [
                        'id' => $row['id'],
                        'role' => $row['role'],
                        'full_name' => $row['full_name']
                    ]
                ];
                $jwt = \Firebase\JWT\JWT::encode($payload, JwtConfig::$secret_key, JwtConfig::$algorithm);
            } catch (Exception $e) {
                $err = "[login] jwt encode failed: " . $e->getMessage();
                @file_put_contents(__DIR__ . '/../logs/login_debug.log', date('c') . " " . $err . PHP_EOL, FILE_APPEND);
                $jwt = "debug-token-bypassed";
            }

            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login Successful",
                "token" => $jwt,
                "user" => [
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "role" => $row['role'],
                    "full_name" => $row['full_name'],
                    "is_active" => 1
                ]
            ]);
        } else {
            // FAILURE - PRINT DEBUG INFO
            http_response_code(401);
            echo json_encode([
                "message" => "Login Failed",
                "reason" => "Password Mismatch",
                "debug_info" => [
                    "input_user" => $username,
                    "input_pass" => $password,
                    "stored_hash_start" => substr($row['password_hash'], 0, 10) . "...",
                    "hash_length" => strlen($row['password_hash'])
                ]
            ]);
        }
    }

    public function logout() {
        // Derive user from Authorization Bearer token (server-side only)
        $user_id = null;

        // Ensure JWT libs are available
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            require_once __DIR__ . '/../config/jwt.php';
        } catch (Throwable $e) {
            // continue; we'll handle decode errors below
        }

        // Verbose debug: record incoming headers, server vars, and raw body
        try {
            $debugHeaders = [];
            if (function_exists('getallheaders')) $debugHeaders = getallheaders();
            @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] incoming headers: " . json_encode($debugHeaders) . PHP_EOL, FILE_APPEND);
            @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] server HTTP_AUTHORIZATION: " . ($_SERVER['HTTP_AUTHORIZATION'] ?? '') . PHP_EOL, FILE_APPEND);
            @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] server REDIRECT_HTTP_AUTHORIZATION: " . ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '') . PHP_EOL, FILE_APPEND);
            @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] remote_addr: " . ($_SERVER['REMOTE_ADDR'] ?? '') . " user_agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? '') . PHP_EOL, FILE_APPEND);
            $rawBody = @file_get_contents('php://input');
            @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] raw_body: " . ($rawBody ? $rawBody : '[empty]') . PHP_EOL, FILE_APPEND);
        } catch (Throwable $e) {
            // ignore logging errors
        }

        // Helper: find Authorization header in a case-insensitive way
        $token = null;
        // 1) Typical Apache function
        if (function_exists('apache_request_headers')) {
            $hdrs = apache_request_headers();
            foreach ($hdrs as $k => $v) {
                if (strtolower($k) === 'authorization') {
                    $token = trim(preg_replace('/^Bearer\s+/i', '', $v));
                    break;
                }
            }
        }

        // 2) PHP built-in server or other SAPI: check $_SERVER keys
        if (!$token) {
            if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
                $token = trim(preg_replace('/^Bearer\s+/i', '', $_SERVER['HTTP_AUTHORIZATION']));
            } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $token = trim(preg_replace('/^Bearer\s+/i', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']));
            }
        }

        // 3) getallheaders fallback
        if (!$token && function_exists('getallheaders')) {
            $hdrs = getallheaders();
            foreach ($hdrs as $k => $v) {
                if (strtolower($k) === 'authorization') {
                    $token = trim(preg_replace('/^Bearer\s+/i', '', $v));
                    break;
                }
            }
        }

        if ($token) {
            try {
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key(JwtConfig::$secret_key, JwtConfig::$algorithm));
                if (isset($decoded->data)) {
                    $d = $decoded->data;
                    if (isset($d->id)) $user_id = $d->id;
                    elseif (isset($d->user_id)) $user_id = $d->user_id;
                }
            } catch (Exception $e) {
                // Log decode error and return 401
                $msg = "[logout] token decode failed: " . $e->getMessage() . "\n";
                @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " " . $msg . PHP_EOL, FILE_APPEND);
                http_response_code(401);
                echo json_encode(["message" => "Invalid token", "error" => $e->getMessage()]);
                return;
            }
        } else {
            // No token found: treat as logged out (no DB change)
            @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] no Authorization header found" . PHP_EOL, FILE_APPEND);
            echo json_encode(["message" => "Logged out"]);
            return;
        }

        if ($user_id) {
            try {
                // Log what we are about to do
                @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] decoded user_id=" . $user_id . PHP_EOL, FILE_APPEND);
                $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $res = $stmt->execute([$user_id]);
                @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] update result=" . ($res ? 'success' : 'failure') . PHP_EOL, FILE_APPEND);
                echo json_encode(["message" => "Logged out", "user_id" => $user_id]);
                return;
            } catch (Exception $e) {
                @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] update exception: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
                http_response_code(500);
                echo json_encode(["message" => "Failed to update user state", "error" => $e->getMessage()]);
                return;
            }
        }

        echo json_encode(["message" => "Logged out"]);
    }
}
?>