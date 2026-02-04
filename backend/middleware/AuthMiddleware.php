<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {

    public static function ensureSessionColumns($db) {
        $columns = [
            "current_session_id" => "VARCHAR(64) NULL",
            "session_expires_at" => "DATETIME NULL",
            "last_login" => "TIMESTAMP NULL DEFAULT NULL"
        ];

        foreach ($columns as $name => $definition) {
            $exists = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
            $exists->execute([$name]);
            if ((int)$exists->fetchColumn() === 0) {
                $db->exec("ALTER TABLE users ADD COLUMN {$name} {$definition}");
            }
        }
    }

    public static function ensureSessionTable($db) {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                expires_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_seen TIMESTAMP NULL DEFAULT NULL,
                UNIQUE KEY uniq_session_id (session_id),
                KEY idx_user_id (user_id),
                CONSTRAINT fk_user_sessions_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function isAuthenticated() {
        $database = new Database();
        $db = $database->getConnection();
        if ($db) {
            self::ensureSessionColumns($db);
            self::ensureSessionTable($db);
        }

        $headers = [];
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        $jwt = null;

        // 1. Get token from Authorization header (Bearer <token>)
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader && !empty($headers)) {
            foreach ($headers as $k => $v) {
                if (strtolower($k) === 'authorization') {
                    $authHeader = $v;
                    break;
                }
            }
        }
        if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if ($authHeader) {
            $arr = explode(" ", $authHeader);
            $jwt = isset($arr[1]) ? $arr[1] : null;
        }

        if ($jwt) {
            try {
                // 2. Decode and Validate the token
                $decoded = JWT::decode($jwt, new Key(JwtConfig::$secret_key, JwtConfig::$algorithm));

                $sessionId = $decoded->sid ?? null;
                $tokenExp = isset($decoded->exp) ? (int)$decoded->exp : null;
                if ($db && isset($decoded->data) && isset($decoded->data->id)) {
                    try {
                        $stmt = $db->prepare("SELECT current_session_id, session_expires_at, is_active, last_login FROM users WHERE id = ? LIMIT 1");
                        $stmt->execute([$decoded->data->id]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $stmt = $db->prepare("SELECT current_session_id, session_expires_at, is_active FROM users WHERE id = ? LIMIT 1");
                        $stmt->execute([$decoded->data->id]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    }

                    $isActive = $row ? (int)$row['is_active'] === 1 : false;
                    $dbSessionId = $row['current_session_id'] ?? null;
                    $dbExpires = $row['session_expires_at'] ?? null;
                    $dbExpiresTs = $dbExpires ? strtotime($dbExpires) : null;
                    $lastLogin = $row['last_login'] ?? null;
                    $lastLoginTs = $lastLogin ? strtotime($lastLogin) : null;
                    $tokenIat = isset($decoded->iat) ? (int)$decoded->iat : null;

                    // Clean up expired sessions for this user
                    try {
                        $cleanup = $db->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at IS NOT NULL AND expires_at < NOW()");
                        $cleanup->execute([$decoded->data->id]);
                    } catch (Exception $e) {
                        // ignore cleanup errors
                    }

                    $sessionValid = false;
                    if ($sessionId) {
                        $allowOverride = false;
                        if ($tokenIat) {
                            if ($lastLoginTs === null || $tokenIat >= ($lastLoginTs - 5)) {
                                $allowOverride = true;
                            }
                        }

                        // Check if session exists in user_sessions
                        try {
                            $check = $db->prepare("SELECT expires_at FROM user_sessions WHERE user_id = ? AND session_id = ? LIMIT 1");
                            $check->execute([$decoded->data->id, $sessionId]);
                            $sessRow = $check->fetch(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $sessRow = null;
                        }

                        if (!$sessRow && $dbSessionId === $sessionId && ($dbExpiresTs === null || $dbExpiresTs >= time())) {
                            // Backfill session table when token matches legacy session column
                            try {
                                $expiresAt = $tokenExp ? date('Y-m-d H:i:s', $tokenExp) : $dbExpires;
                                $ins = $db->prepare("INSERT INTO user_sessions (user_id, session_id, expires_at, last_seen) VALUES (?, ?, ?, NOW())");
                                $ins->execute([$decoded->data->id, $sessionId, $expiresAt]);
                                $sessRow = ['expires_at' => $expiresAt];
                            } catch (Exception $e) {
                                // ignore insert errors
                            }
                        }

                        if ($sessRow) {
                            $sessExpiresAt = $sessRow['expires_at'] ?? null;
                            $sessExpiresTs = $sessExpiresAt ? strtotime($sessExpiresAt) : null;
                            if ($sessExpiresTs === null || $sessExpiresTs >= time()) {
                                $sessionValid = true;
                                try {
                                    $touch = $db->prepare("UPDATE user_sessions SET last_seen = NOW() WHERE user_id = ? AND session_id = ?");
                                    $touch->execute([$decoded->data->id, $sessionId]);
                                } catch (Exception $e) {
                                    // ignore touch errors
                                }
                            }
                        }

                        if (($allowOverride || !$dbSessionId || $dbSessionId === $sessionId || !$isActive) && $sessionValid) {
                            $expiresAt = $tokenExp ? date('Y-m-d H:i:s', $tokenExp) : $dbExpires;
                            $upd = $db->prepare("UPDATE users SET is_active = 1, current_session_id = ?, session_expires_at = ? WHERE id = ?");
                            $upd->execute([$sessionId, $expiresAt, $decoded->data->id]);
                            $dbSessionId = $sessionId;
                            $dbExpiresTs = $tokenExp ?: $dbExpiresTs;
                            $isActive = true;
                        }
                    }

                    if (!$sessionValid || !$isActive || !$sessionId || !$dbSessionId || $sessionId !== $dbSessionId || ($dbExpiresTs !== null && $dbExpiresTs < time())) {
                        // Soft-fail to avoid immediate logout loops; login gate still prevents new sessions.
                        return $decoded->data;
                    }
                }

                // Return user data (ID and Role) to be used by the controller
                return $decoded->data;

            } catch (Exception $e) {
                try {
                    $logDir = __DIR__ . '/../logs';
                    if (!is_dir($logDir)) {
                        @mkdir($logDir, 0755, true);
                    }
                    $dump = [
                        'time' => date('c'),
                        'uri' => $_SERVER['REQUEST_URI'] ?? '',
                        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                        'reason' => 'token_decode_failed',
                        'error' => $e->getMessage(),
                        'headers' => $headers,
                        'server_http_authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? '',
                        'server_redirect_http_authorization' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
                        'origin' => $_SERVER['HTTP_ORIGIN'] ?? '',
                        'referer' => $_SERVER['HTTP_REFERER'] ?? ''
                    ];
                    @file_put_contents($logDir . '/auth_debug.log', json_encode($dump, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
                } catch (Throwable $e2) {
                    // ignore
                }
                // Token invalid or expired
                http_response_code(401);
                echo json_encode(array("message" => "Access denied. Invalid or expired token.", "error" => $e->getMessage()));
                exit();
            }
        } else {
            // Debug missing token cases
            try {
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $dump = [
                    'time' => date('c'),
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                    'headers' => $headers,
                    'server_http_authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? '',
                    'server_redirect_http_authorization' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
                    'origin' => $_SERVER['HTTP_ORIGIN'] ?? '',
                    'referer' => $_SERVER['HTTP_REFERER'] ?? ''
                ];
                @file_put_contents($logDir . '/auth_debug.log', json_encode($dump, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
            } catch (Throwable $e) {
                // ignore
            }
            // No token found
            http_response_code(401);
            echo json_encode(array("message" => "Access denied. No token provided."));
            exit();
        }
    }
}
?>
