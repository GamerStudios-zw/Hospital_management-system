<?php
// FILE: backend/utils/RequestValidator.php

class RequestValidator {
    private static $jsonCache = null;

    public static function json($required = true, $maxBytes = 131072) {
        if (self::$jsonCache !== null) {
            return self::$jsonCache;
        }

        $raw = file_get_contents("php://input");
        if ($raw === false) {
            self::fail(400, "Invalid request body.");
        }

        if (strlen($raw) > $maxBytes) {
            self::fail(413, "Payload too large.");
        }

        if (trim($raw) === '') {
            if ($required) {
                self::fail(400, "Request body is required.");
            }
            self::$jsonCache = (object)[];
            return self::$jsonCache;
        }

        $decoded = json_decode($raw);
        if (json_last_error() !== JSON_ERROR_NONE || !is_object($decoded)) {
            self::fail(400, "Malformed JSON payload.");
        }

        self::$jsonCache = $decoded;
        return self::$jsonCache;
    }

    public static function requireInt($data, $field, $min = null, $max = null) {
        if (!isset($data->$field) || !is_numeric($data->$field)) {
            self::fail(400, "{$field} must be a valid number.");
        }
        $value = (int)$data->$field;
        if ($min !== null && $value < $min) self::fail(400, "{$field} must be >= {$min}.");
        if ($max !== null && $value > $max) self::fail(400, "{$field} must be <= {$max}.");
        return $value;
    }

    public static function requireFloat($data, $field, $min = null, $max = null) {
        if (!isset($data->$field) || !is_numeric($data->$field)) {
            self::fail(400, "{$field} must be a valid number.");
        }
        $value = (float)$data->$field;
        if ($min !== null && $value < $min) self::fail(400, "{$field} must be >= {$min}.");
        if ($max !== null && $value > $max) self::fail(400, "{$field} must be <= {$max}.");
        return $value;
    }

    public static function requireString($data, $field, $minLen = 1, $maxLen = 255, $pattern = null) {
        if (!isset($data->$field)) self::fail(400, "{$field} is required.");
        $value = trim((string)$data->$field);
        $len = strlen($value);
        if ($len < $minLen) self::fail(400, "{$field} is too short.");
        if ($len > $maxLen) self::fail(400, "{$field} is too long.");
        if ($pattern !== null && !preg_match($pattern, $value)) {
            self::fail(400, "{$field} format is invalid.");
        }
        return $value;
    }

    public static function optionalString($data, $field, $maxLen = 255) {
        if (!isset($data->$field) || $data->$field === null) return null;
        $value = trim((string)$data->$field);
        if (strlen($value) > $maxLen) self::fail(400, "{$field} is too long.");
        return $value;
    }

    public static function enum($value, $allowed, $field = 'field') {
        $candidate = strtolower(trim((string)$value));
        foreach ($allowed as $v) {
            if ($candidate === strtolower((string)$v)) return $v;
        }
        self::fail(400, "{$field} has invalid value.");
    }

    public static function parseBp($bp) {
        $bpValue = trim((string)$bp);
        if (!preg_match('/^(\d{2,3})\s*\/\s*(\d{2,3})$/', $bpValue, $m)) {
            self::fail(400, "bp format is invalid. Expected 120/80.");
        }
        $sys = (int)$m[1];
        $dia = (int)$m[2];
        if ($sys < 60 || $sys > 260 || $dia < 30 || $dia > 160 || $dia >= $sys) {
            self::fail(400, "bp value is outside safe limits.");
        }
        return ['sys' => $sys, 'dia' => $dia, 'raw' => "{$sys}/{$dia}"];
    }

    public static function fail($code, $message) {
        http_response_code($code);
        echo json_encode(["message" => $message]);
        exit;
    }
}
?>
