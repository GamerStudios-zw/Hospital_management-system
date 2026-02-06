<?php
class RoleMiddleware {

    /**
     * Check if the authenticated user has one of the allowed roles.
     * * @param array $allowed_roles - Array of strings, e.g. ['admin', 'doctor']
     * @param object $user_data - The decoded JWT data returned from AuthMiddleware
     */
    public static function allow($allowed_roles, $user_data) {

        // Ensure user_data is valid and has a role
        if (!isset($user_data->role)) {
            http_response_code(403);
            echo json_encode(["message" => "Access Denied. User role not found."]);
            exit();
        }

        $normalizeRole = function ($r) {
            return str_replace([' ', '-'], '_', strtolower(trim((string)$r)));
        };
        $userRole = $normalizeRole($user_data->role);
        $allowed = array_map($normalizeRole, $allowed_roles);

        // Check if the user's role exists in the allowed list
        if (!in_array($userRole, $allowed, true)) {
            http_response_code(403); // 403 Forbidden
            echo json_encode([
                "message" => "Access Denied. You do not have permission to perform this action.",
                "required_roles" => $allowed_roles,
                "your_role" => $user_data->role
            ]);
            exit();
        }
    }
}
?>
