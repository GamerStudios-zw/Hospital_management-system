<?php
// FILE: backend/controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
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
        try {
            AuthMiddleware::ensureSessionColumns($this->db);
            AuthMiddleware::ensureSessionTable($this->db);
        } catch (Exception $e) {
            // ignore schema ensure errors
        }

        // 1. Get raw input
        $json = file_get_contents("php://input");
        $data = json_decode($json);

        // 2. Debug: Check if input is received
        if (!$data) {
            http_response_code(400);
            echo json_encode(["message" => "No JSON received", "debug_raw" => $json]);
            return;
        }

        $loginId = trim($data->username ?? '');
        $password = trim($data->password ?? '');

        if (empty($loginId) || empty($password)) {
            http_response_code(400);
            echo json_encode(["message" => "Empty username or password"]);
            return;
        }

        // 3. Allow login using either username or email.
        $query = "SELECT * FROM users WHERE username = :login OR email = :login LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':login', $loginId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // CHECK 1: Did we find the user?
        if (!$row) {
            $userCount = 0;
            try {
                $userCount = (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            } catch (Exception $e) {
                $userCount = 0;
            }
            try {
                ActivityLogger::log($this->db, $loginId ?: 'unknown', 'Login Failed', 'Failed', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'user',
                    'entity_id' => $loginId ?: null,
                    'source' => 'auth/login',
                    'severity' => 'WARN',
                    'status_code' => 401,
                    'error_message' => "User not found"
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }
            http_response_code(401);
            echo json_encode([
                "message" => "Login Failed",
                "reason" => ($userCount === 0)
                    ? "No user accounts found. Run backend/install.php to create default users."
                    : "User '$loginId' not found in database"
            ]);
            return;
        }

        // CHECK 2: Does the hash verify?
        if (password_verify($password, $row['password_hash'])) {
            // Clean expired sessions for this user and enforce max 3 active sessions
            try {
                $cleanup = $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at IS NOT NULL AND expires_at < NOW()");
                $cleanup->execute([$row['id']]);
            } catch (Exception $e) {
                // ignore cleanup errors
            }

            $activeCount = 0;
            try {
                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM user_sessions WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())");
                $countStmt->execute([$row['id']]);
                $activeCount = (int)$countStmt->fetchColumn();
            } catch (Exception $e) {
                $activeCount = 0;
            }

            if ($activeCount >= 3) {
                try {
                    ActivityLogger::log($this->db, $row['username'], 'Login Blocked (Active Sessions Limit)', 'Failed', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'user',
                        'entity_id' => (string)$row['id'],
                        'actor_id' => (string)$row['id'],
                        'actor_role' => $row['role'],
                        'source' => 'auth/login',
                        'severity' => 'WARN',
                        'status_code' => 409,
                        'error_message' => 'Active sessions limit reached'
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
                http_response_code(409);
                echo json_encode(["message" => "Login blocked. This account already has 3 active sessions."]);
                return;
            }

            // OPTIONAL: If you want to prevent login for inactive users, uncomment this block
            // and remove the code that auto-activates the user below.
            // if ((int)$row['is_active'] !== 1) {
            //     http_response_code(403);
            //     echo json_encode(["message" => "Account inactive. Contact administrator."]);
            //     return;
            // }

            // SUCCESS! Session info will be updated after token creation.
            // Issue a JWT token containing user data
            $sessionId = null;
            $sessionExpiresAt = null;
            try {
                $issuedAt = time();
                $expire = $issuedAt + JwtConfig::$expiration_time;
                $sessionId = bin2hex(random_bytes(16));
                $sessionExpiresAt = date('Y-m-d H:i:s', $expire);
                $payload = [
                    'iss' => JwtConfig::$issuer,
                    'aud' => JwtConfig::$audience,
                    'iat' => $issuedAt,
                    'exp' => $expire,
                    'sid' => $sessionId,
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
                http_response_code(500);
                echo json_encode(["message" => "Login failed. Token generation error."]);
                return;
            }

            try {
                $ins = $this->db->prepare("INSERT INTO user_sessions (user_id, session_id, expires_at, last_seen) VALUES (?, ?, ?, NOW())");
                $ins->execute([$row['id'], $sessionId ?? null, $sessionExpiresAt ?? null]);
            } catch (Exception $e) {
                // Non-fatal: continue even if we couldn't insert the session
            }

            try {
                $upd = $this->db->prepare("UPDATE users SET is_active = 1, last_login = NOW(), current_session_id = ?, session_expires_at = ? WHERE id = ?");
                $upd->execute([$sessionId ?? null, $sessionExpiresAt ?? null, $row['id']]);
            } catch (Exception $e) {
                // Non-fatal: continue even if we couldn't update the session
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
            try {
                ActivityLogger::log($this->db, $row['username'], 'Login', 'Success', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'user',
                    'entity_id' => (string)$row['id'],
                    'actor_id' => (string)$row['id'],
                    'actor_role' => $row['role'],
                    'source' => 'auth/login',
                    'session_id' => $sessionId ?? null
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }
        } else {
            // FAILURE - PRINT DEBUG INFO
            try {
                ActivityLogger::log($this->db, $loginId ?: 'unknown', 'Login Failed', 'Failed', null, [
                    'event_type' => 'audit',
                    'entity_type' => 'user',
                    'entity_id' => $loginId ?: null,
                    'source' => 'auth/login',
                    'severity' => 'WARN',
                    'status_code' => 401,
                    'error_message' => 'Password mismatch'
                ]);
            } catch (Exception $e) {
                // ignore logging errors
            }
            http_response_code(401);
            echo json_encode([
                "message" => "Login Failed",
                "reason" => "Password Mismatch",
                "debug_info" => [
                    "input_user" => $loginId,
                    "input_pass" => $password,
                    "stored_hash_start" => substr($row['password_hash'], 0, 10) . "...",
                    "hash_length" => strlen($row['password_hash'])
                ]
            ]);
        }
    }

    public function logout() {
        try {
            AuthMiddleware::ensureSessionColumns($this->db);
            AuthMiddleware::ensureSessionTable($this->db);
        } catch (Exception $e) {
            // ignore schema ensure errors
        }

        // Derive user from Authorization Bearer token (server-side only)
        $user_id = null;
        $user_role = null;

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

        // 4) Fallback: allow token in JSON body for beacon/logout-on-close
        if (!$token) {
            $rawBody = @file_get_contents('php://input');
            if ($rawBody) {
                $parsed = json_decode($rawBody, true);
                if (is_array($parsed) && !empty($parsed['token'])) {
                    $token = $parsed['token'];
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
                    $user_role = $d->role ?? null;
                }
                $session_id = $decoded->sid ?? null;
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
                $check = $this->db->prepare("SELECT expires_at FROM user_sessions WHERE user_id = ? AND session_id = ? LIMIT 1");
                $check->execute([$user_id, $session_id]);
                $row = $check->fetch(PDO::FETCH_ASSOC);
                $dbExpires = $row['expires_at'] ?? null;
                $dbExpiresTs = $dbExpires ? strtotime($dbExpires) : null;
                if (!$session_id || !$row || ($dbExpiresTs !== null && $dbExpiresTs < time())) {
                    http_response_code(401);
                    echo json_encode(["message" => "Session already invalidated"]);
                    return;
                }

                // Log what we are about to do
                @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] decoded user_id=" . $user_id . PHP_EOL, FILE_APPEND);
                $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?");
                $res = $stmt->execute([$user_id, $session_id]);
                @file_put_contents(__DIR__ . '/../logs/logout_debug.log', date('c') . " [logout] delete session result=" . ($res ? 'success' : 'failure') . PHP_EOL, FILE_APPEND);

                // Update user status based on remaining sessions
                try {
                    $rem = $this->db->prepare("SELECT session_id, expires_at FROM user_sessions WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY expires_at DESC, created_at DESC LIMIT 1");
                    $rem->execute([$user_id]);
                    $remaining = $rem->fetch(PDO::FETCH_ASSOC);
                    if ($remaining) {
                        $upd = $this->db->prepare("UPDATE users SET is_active = 1, current_session_id = ?, session_expires_at = ? WHERE id = ?");
                        $upd->execute([$remaining['session_id'], $remaining['expires_at'], $user_id]);
                    } else {
                        $upd = $this->db->prepare("UPDATE users SET is_active = 0, current_session_id = NULL, session_expires_at = NULL WHERE id = ?");
                        $upd->execute([$user_id]);
                    }
                } catch (Exception $e) {
                    // ignore user status update errors
                }

                echo json_encode(["message" => "Logged out", "user_id" => $user_id]);
                try {
                    ActivityLogger::log($this->db, (string)$user_id, 'Logout', 'Success', null, [
                        'event_type' => 'audit',
                        'entity_type' => 'user',
                        'entity_id' => (string)$user_id,
                        'actor_id' => (string)$user_id,
                        'actor_role' => $user_role ?? null,
                        'source' => 'auth/logout',
                        'session_id' => $session_id ?? null
                    ]);
                } catch (Exception $e) {
                    // ignore logging errors
                }
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
